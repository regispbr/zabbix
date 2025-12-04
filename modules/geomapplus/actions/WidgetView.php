<?php declare(strict_types = 1);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


namespace Modules\GeomapPlus\Actions;

use CControllerDashboardWidgetView;
use CControllerResponseData;
use CGeomapCoordinatesParser;
use CParser;
use CProfile;
use CSettingsHelper;
use function getSubGroups;
use function getTileProviders;
use const TAG_EVAL_TYPE_AND_OR;
use const HOST_INVENTORY_MANUAL;
use const HOST_INVENTORY_AUTOMATIC;
use const GEOMAP_LAT_MIN;
use const GEOMAP_LAT_MAX;
use const GEOMAP_LNG_MIN;
use const GEOMAP_LNG_MAX;
use const TRIGGER_VALUE_TRUE;
use const TRIGGER_SEVERITY_DISASTER;
use const TRIGGER_SEVERITY_HIGH;
use const TRIGGER_SEVERITY_AVERAGE;
use const TRIGGER_SEVERITY_WARNING;
use const TRIGGER_SEVERITY_INFORMATION;
use const TRIGGER_SEVERITY_NOT_CLASSIFIED;



class WidgetView extends CControllerDashboardWidgetView {

	protected function init(): void {
		parent::init();

		$this->addValidationRules([
			'initial_load' => 'in 0,1',
			'widgetid' => 'db widget.widgetid',
			'unique_id' => 'required|string'
		]);
	}

	protected function doAction(): void {
		$widgetid = $this->getInput('widgetid', 0);

		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'hostgroups' => self::convertToRFC7946($this->getHostGroups()),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			],
			'unique_id' => $this->getInput('unique_id')
		];

		if ($this->getInput('initial_load', 0)) {
			$geomap_config = self::getMapConfig();

			$data['config'] = $geomap_config + $this->getMapCenter($geomap_config) + [
				'filter' => $this->getUserProfileFilter((int)$widgetid),
				'marker_shape' => $this->fields_values['marker_shape'] ?? 0,
				'colors' => [
					'no_problems' => '#' . ($this->fields_values['color_no_problems'] ?? '00AA00'),
					'information' => '#' . ($this->fields_values['color_information'] ?? '7499FF'),
					'warning' => '#' . ($this->fields_values['color_warning'] ?? 'FFC859'),
					'average' => '#' . ($this->fields_values['color_average'] ?? 'FFA059'),
					'high' => '#' . ($this->fields_values['color_high'] ?? 'E97659'),
					'disaster' => '#' . ($this->fields_values['color_disaster'] ?? 'E45959')
				]
			];
		}

		$this->setResponse(new CControllerResponseData($data));
	}

	/**
	 * Apply regex transformation to group name.
	 */
	private function transformGroupName(string $name): string {
		$pattern = $this->fields_values['group_name_pattern'] ?? '';
		$replacement = $this->fields_values['group_name_replacement'] ?? '';

		if (empty($pattern) || empty($replacement)) {
			return $name;
		}

		// Validate regex pattern
		if (@preg_match($pattern, '') === false) {
			return $name;
		}

		$transformed = @preg_replace($pattern, $replacement, $name);
		
		return ($transformed !== null && $transformed !== '') ? $transformed : $name;
	}

	/**
	 * Get host groups with their hosts and problems.
	 */
	private function getHostGroups(): array {
		if ($this->isTemplateDashboard()) {
			return [];
		}

		$filter_groupids = !empty($this->fields_values['groupids']) 
			? getSubGroups($this->fields_values['groupids']) 
			: null;

		// Get host groups
		$groups = \API::HostGroup()->get([
			'output' => ['groupid', 'name'],
			'groupids' => $filter_groupids,
			'preservekeys' => true
		]);

		if (empty($groups)) {
			return [];
		}

		// Get hosts for each group
		$result_groups = [];
		foreach ($groups as $group) {
			$hosts = \API::Host()->get([
				'output' => ['hostid', 'name'],
				'selectInventory' => ['location_lat', 'location_lon'],
				'groupids' => $group['groupid'],
				'hostids' => !empty($this->fields_values['hostids']) ? $this->fields_values['hostids'] : null,
				'evaltype' => $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR,
				'tags' => $this->fields_values['tags'] ?? [],
				'filter' => [
					'inventory_mode' => [HOST_INVENTORY_MANUAL, HOST_INVENTORY_AUTOMATIC]
				],
				'monitored_hosts' => true,
				'preservekeys' => true
			]);

			// Filter hosts with valid coordinates
			$hosts = array_filter($hosts, static function ($host) {
				if (!isset($host['inventory']['location_lat']) || !isset($host['inventory']['location_lon'])) {
					return false;
				}

				$lat = $host['inventory']['location_lat'];
				$lng = $host['inventory']['location_lon'];

				return (is_numeric($lat) && $lat >= GEOMAP_LAT_MIN && $lat <= GEOMAP_LAT_MAX
					&& is_numeric($lng) && $lng >= GEOMAP_LNG_MIN && $lng <= GEOMAP_LNG_MAX);
			});

			if (empty($hosts)) {
				continue;
			}

			// Calculate average coordinates for the group
			$avg_lat = 0;
			$avg_lng = 0;
			foreach ($hosts as $host) {
				$avg_lat += (float)$host['inventory']['location_lat'];
				$avg_lng += (float)$host['inventory']['location_lon'];
			}
			$avg_lat /= count($hosts);
			$avg_lng /= count($hosts);

			// Get triggers for hosts in this group
			$triggers = \API::Trigger()->get([
				'output' => ['triggerid', 'description', 'priority'],
				'selectHosts' => ['hostid', 'name'],
				'hostids' => array_keys($hosts),
				'filter' => [
					'value' => TRIGGER_VALUE_TRUE
				],
				'monitored' => true,
				'preservekeys' => true
			]);

			// Get problems
			$problems = [];
			if (!empty($triggers)) {
				$problems = \API::Problem()->get([
					'output' => ['eventid', 'objectid', 'severity', 'name', 'clock'],
					'objectids' => array_keys($triggers),
					'symptom' => false,
					'recent' => true
				]);
			}

			// Organize problems by severity
			$problems_by_severity = [
				TRIGGER_SEVERITY_DISASTER => 0,
				TRIGGER_SEVERITY_HIGH => 0,
				TRIGGER_SEVERITY_AVERAGE => 0,
				TRIGGER_SEVERITY_WARNING => 0,
				TRIGGER_SEVERITY_INFORMATION => 0,
				TRIGGER_SEVERITY_NOT_CLASSIFIED => 0
			];

			$problem_details = [];
			$max_severity = -1;

			foreach ($problems as $problem) {
				$problems_by_severity[$problem['severity']]++;
				if ($problem['severity'] > $max_severity) {
					$max_severity = $problem['severity'];
				}

				if (isset($triggers[$problem['objectid']])) {
					$trigger = $triggers[$problem['objectid']];
					$problem_details[] = [
						'eventid' => $problem['eventid'],
						'severity' => $problem['severity'],
						'name' => $problem['name'],
						'clock' => $problem['clock'],
						'host' => $trigger['hosts'][0]['name'] ?? '',
						'hostid' => $trigger['hosts'][0]['hostid'] ?? 0
					];
				}
			}

			// Apply regex transformation to group name
			$display_name = $this->transformGroupName($group['name']);

			$result_groups[] = [
				'groupid' => $group['groupid'],
				'name' => $display_name,
				'original_name' => $group['name'],
				'host_count' => count($hosts),
				'alert_count' => count($problems),
				'latitude' => $avg_lat,
				'longitude' => $avg_lng,
				'severity' => $max_severity,
				'problems_by_severity' => $problems_by_severity,
				'problem_details' => $problem_details
			];
		}

		return $result_groups;
	}

	/**
	 * Get initial map center point, zoom level and coordinates to center when clicking on navigate home button.
	 */
	private function getMapCenter(array $geomap_config): array {
		$geoloc_parser = new CGeomapCoordinatesParser();
		$home_coords = [];
		$center = [];

		$widgetid = $this->getInput('widgetid', 0);
		$user_default_view = CProfile::get('web.dashboard.widget.geomapplus.default_view', '', $widgetid);
		
		if ($user_default_view !== '' && $geoloc_parser->parse($user_default_view) == CParser::PARSE_SUCCESS) {
			$home_coords['default'] = true;
			$center = $geoloc_parser->result;
			$center['zoom'] = min($geomap_config['max_zoom'], $center['zoom']);
		}

		if (isset($this->fields_values['default_view'])
				&& $this->fields_values['default_view'] !== ''
				&& $geoloc_parser->parse($this->fields_values['default_view']) == CParser::PARSE_SUCCESS) {
			$initial_view = $geoloc_parser->result;

			if (isset($initial_view['zoom'])) {
				$initial_view['zoom'] = min($geomap_config['max_zoom'], $initial_view['zoom']);
			}
			else {
				$initial_view['zoom'] = ceil($geomap_config['max_zoom'] / 2);
			}

			$home_coords['initial'] = $initial_view;
			if (!$center) {
				$center = $home_coords['initial'];
			}
		}

		$defaults = [
			'latitude' => 0,
			'longitude' => 0,
			'zoom' => 1
		];

		return [
			'center' => $center ?: $defaults,
			'home_coords' => $home_coords
		];
	}

	private function getUserProfileFilter(int $widgetid): array {
		return [
			'severity' => CProfile::get('web.dashboard.widget.geomapplus.severity_filter', [], $widgetid)
		];
	}

	/**
	 * Get global map configuration.
	 */
	private static function getMapConfig(): array {
		if (CSettingsHelper::get(CSettingsHelper::GEOMAPS_TILE_PROVIDER) === '') {
			return [
				'tile_url' => CSettingsHelper::get(CSettingsHelper::GEOMAPS_TILE_URL),
				'max_zoom' => CSettingsHelper::get(CSettingsHelper::GEOMAPS_MAX_ZOOM),
				'attribution' => htmlspecialchars(CSettingsHelper::get(CSettingsHelper::GEOMAPS_ATTRIBUTION),
					ENT_NOQUOTES, 'UTF-8'
				)
			];
		}

		$tile_provider = getTileProviders()[CSettingsHelper::get(CSettingsHelper::GEOMAPS_TILE_PROVIDER)];

		return [
			'tile_url' => $tile_provider['geomaps_tile_url'],
			'max_zoom' => $tile_provider['geomaps_max_zoom'],
			'attribution' => $tile_provider['geomaps_attribution']
		];
	}

	/**
	 * Convert array of host groups to valid GeoJSON (RFC7946) object.
	 */
	private static function convertToRFC7946(array $groups): array {
		$geo_json = [];

		foreach ($groups as $group) {
			$geo_json[] = [
				'type' => 'Feature',
				'geometry' => [
					'type' => 'Point',
					'coordinates' => [
						$group['longitude'],
						$group['latitude'],
						0
					]
				],
				'properties' => [
					'groupid' => $group['groupid'],
					'name' => $group['name'],
					'original_name' => $group['original_name'],
					'host_count' => $group['host_count'],
					'alert_count' => $group['alert_count'],
					'severity' => $group['severity'],
					'problems_by_severity' => $group['problems_by_severity'],
					'problem_details' => $group['problem_details']
				]
			];
		}

		return $geo_json;
	}
}
