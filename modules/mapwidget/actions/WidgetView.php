<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
** ... (Licença) ...
**/

namespace Modules\MapWidget\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;
use Modules\MapWidget\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// 1. Configuração do Mapa
		$map_config = [
			'map_id' => $this->fields_values['map_id'] ?? null,
			'map_key' => $this->fields_values['map_key'] ?? null,
			'zoom' => floatval($this->fields_values['zoom'] ?? 3),
			'center_lat' => floatval($this->fields_values['center_lat'] ?? -16),
			'center_lng' => floatval($this->fields_values['center_lng'] ?? -52),
			'bearing' => floatval($this->fields_values['bearing'] ?? 0),
			'pitch' => floatval($this->fields_values['pitch'] ?? 0)
		];

		// 2. Filtros
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];

		$selected_severities = $this->fields_values['severities'] ?? [];
		$severity_filters = [ -1 => 1 ];
		for ($i = 0; $i <= 5; $i++) {
			$severity_filters[$i] = in_array($i, $selected_severities) ? 1 : 0;
		}

		$problem_filters = [
			'show_acknowledged' => $this->fields_values['show_acknowledged'] ?? 1,
			'show_suppressed' => $this->fields_values['show_suppressed'] ?? 0,
			'show_suppressed_only' => $this->fields_values['show_suppressed_only'] ?? 0
		];

		$exclude_maintenance = (bool)($this->fields_values['exclude_maintenance'] ?? 0);

		// 3. Busca de Dados
		$zabbix_data = $this->getZabbixData(
			$hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters,
			$evaltype, $tags,
			$exclude_maintenance
		);
		
		$detailed_problems = $this->getDetailedProblemList(
			$hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters,
			$evaltype, $tags,
			$exclude_maintenance
		);

		// 4. Response
		$data = $map_config + [
			'name' => $this->getInput('name', $this->widget->getName()),
			'zabbix_hosts' => $zabbix_data,
			'detailed_problems' => $detailed_problems,
			'severity_colors' => [
				-1 => $this->getSeverityColor(-1),
				0 => $this->getSeverityColor(0),
				1 => $this->getSeverityColor(1),
				2 => $this->getSeverityColor(2),
				3 => $this->getSeverityColor(3),
				4 => $this->getSeverityColor(4),
				5 => $this->getSeverityColor(5)
			],
			'severity_names' => [
				-1 => _('OK'),
				0 => _('Not classified'),
				1 => _('Information'),
				2 => _('Warning'),
				3 => _('Average'),
				4 => _('High'),
				5 => _('Disaster')
			],
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getZabbixData($hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters, $evaltype, $tags, $exclude_maintenance): array {
		$locations_to_map = [];
		if (empty($hostgroups) && empty($hosts)) return $locations_to_map;

		$params = [
			'output' => ['hostid', 'name', 'maintenance_status'],
			'selectInventory' => ['location_lat', 'location_lon'],
			'selectHostGroups' => ['groupid', 'name'],
			'monitored_hosts' => true,
			'preservekeys' => true,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'filter' => ['inventory_mode' => [HOST_INVENTORY_MANUAL, HOST_INVENTORY_AUTOMATIC]]
		];

		if (!empty($hosts)) $params['hostids'] = $hosts;
		elseif (!empty($hostgroups)) $params['groupids'] = getSubGroups($hostgroups);

		if (!empty($exclude_hosts) && !empty($params['hostids'])) {
			$params['hostids'] = array_diff($params['hostids'], $exclude_hosts);
		}
		
		try { $hosts_from_api = \API::Host()->get($params); } catch (\Exception $e) { return $locations_to_map; }

		if ($exclude_maintenance) {
			$hosts_from_api = array_filter($hosts_from_api, fn($h) => $h['maintenance_status'] == 0);
		}

		if (!empty($exclude_hosts) && !empty($params['groupids'])) {
			$hosts_from_api = array_filter($hosts_from_api, fn($k) => !in_array($k, $exclude_hosts), ARRAY_FILTER_USE_KEY);
		}

		if (empty($hosts_from_api)) return $locations_to_map;
		
		$hostids = array_keys($hosts_from_api);
		$triggers = []; $problems_api = [];
		
		try {
			$triggers = \API::Trigger()->get([
				'output' => [], 'selectHosts' => ['hostid'], 'hostids' => $hostids,
				'filter' => ['value' => TRIGGER_VALUE_TRUE],
				'monitored' => true, 'skipDependent' => true, 'preservekeys' => true
			]);
			
			if (!empty($triggers)) {
				$prob_options = [
					'output' => ['objectid', 'severity', 'acknowledged', 'suppressed', 'eventid', 'name'],
					'objectids' => array_keys($triggers),
					'symptom' => false, 
					'preservekeys' => true
				];
				
				if ($problem_filters['show_suppressed_only'] == 1) {
					$prob_options['suppressed'] = true;
				} elseif ($problem_filters['show_suppressed'] == 1) {
					$p1 = \API::Problem()->get($prob_options + ['suppressed' => false]);
					$p2 = \API::Problem()->get($prob_options + ['suppressed' => true]);
					$problems_api = $p1 + $p2;
				} else {
					$prob_options['suppressed'] = false;
					$problems_api = \API::Problem()->get($prob_options);
				}
				
				if (!isset($problems_api) || (empty($problems_api) && $problem_filters['show_suppressed'] != 1)) {
					if (!isset($problems_api)) $problems_api = \API::Problem()->get($prob_options);
				}
			}
		} catch (\Exception $e) {}

		$problems_by_host = [];
		foreach ($problems_api as $problem) {
			$p_ack = (int)$problem['acknowledged'];
			$p_sup = (int)$problem['suppressed'];

			if ($p_ack == 1 && !$problem_filters['show_acknowledged']) continue;
			if ($problem_filters['show_suppressed_only'] == 1 && $p_sup == 0) continue;
			if ($problem_filters['show_suppressed'] == 0 && $p_sup == 1) continue;
			
			$trigger_id = $problem['objectid'];
			if (!isset($triggers[$trigger_id])) continue;
			
			foreach ($triggers[$trigger_id]['hosts'] as $trigger_host) {
				$hostid = $trigger_host['hostid'];
				if (!isset($hosts_from_api[$hostid])) continue;
				
				if (!array_key_exists($hostid, $problems_by_host)) {
					$problems_by_host[$hostid] = ['counts' => array_fill(0, 6, ['unacked'=>0, 'acked'=>0]), 'events' => []];
				}
				
				$sev = (int)$problem['severity'];
				$key = $p_ack == 1 ? 'acked' : 'unacked';
				
				$problems_by_host[$hostid]['counts'][$sev][$key]++;
				$problems_by_host[$hostid]['events'][$problem['eventid']] = [
					'name' => $problem['name'],
					'severity' => $sev,
					'acknowledged' => $p_ack,
					'suppressed' => $p_sup // <-- ADICIONADO PARA O FRONTEND
				];
			}
		}

		foreach ($hosts_from_api as $hostid => $host) {
			if (empty($host['inventory']['location_lat']) || empty($host['inventory']['location_lon'])
				|| !is_numeric($host['inventory']['location_lat']) || !is_numeric($host['inventory']['location_lon'])
				|| $host['inventory']['location_lat'] == 0 || $host['inventory']['location_lon'] == 0) continue;

			$lat = (float)$host['inventory']['location_lat'];
			$lon = (float)$host['inventory']['location_lon'];
			
			$host_problems_counts = $problems_by_host[$hostid]['counts'] ?? [];
			$host_events = $problems_by_host[$hostid]['events'] ?? [];
			
			$highest_severity = -1;
			if (!empty($host_problems_counts)) {
				for ($s = 5; $s >= 0; $s--) {
					if (!empty($host_problems_counts[$s]['unacked'])) { $highest_severity = $s; break; }
				}
				if ($highest_severity == -1) {
					for ($s = 5; $s >= 0; $s--) {
						if (!empty($host_problems_counts[$s]['acked'])) { $highest_severity = $s; break; }
					}
				}
			}
			
			if (empty($severity_filters[$highest_severity])) continue;
			
			$best_group_name = empty($host['hostgroups']) ? $host['name'] : $host['hostgroups'][0]['name'];
			
			$locations_to_map[] = [
				'hostid' => $hostid, 'name' => $host['name'], 'group_name' => $best_group_name,
				'lat' => $lat, 'lon' => $lon, 'severity' => $highest_severity,
				'problem_counts' => $host_problems_counts, 'problem_events' => $host_events
			];
		}
		return $locations_to_map;
	}

	private function getDetailedProblemList($hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters, $evaltype, $tags, $exclude_maintenance): array {
		$detailed_problems = [];
		$host_params = [
			'output' => ['hostid', 'name', 'maintenance_status'],
			'selectInventory' => ['location_lat', 'location_lon'],
			'monitored_hosts' => true, 'preservekeys' => true,
			'evaltype' => $evaltype, 'tags' => $tags,
			'filter' => ['inventory_mode' => [HOST_INVENTORY_MANUAL, HOST_INVENTORY_AUTOMATIC], 'location_lat' => '', 'location_lon' => '']
		];
		
		if (!empty($hosts)) $host_params['hostids'] = $hosts;
		elseif (!empty($hostgroups)) $host_params['groupids'] = getSubGroups($hostgroups);
		else return [];

		if (!empty($exclude_hosts) && !empty($host_params['hostids'])) $host_params['hostids'] = array_diff($host_params['hostids'], $exclude_hosts);
		
		try { $hosts_from_api = \API::Host()->get($host_params); } catch (\Exception $e) { return []; }

		if ($exclude_maintenance) $hosts_from_api = array_filter($hosts_from_api, fn($h) => $h['maintenance_status'] == 0);
		if (!empty($exclude_hosts) && !empty($host_params['groupids'])) $hosts_from_api = array_filter($hosts_from_api, fn($k) => !in_array($k, $exclude_hosts), ARRAY_FILTER_USE_KEY);
		
		$hosts_on_map = [];
		foreach ($hosts_from_api as $hostid => $host) {
			if (!empty($host['inventory']['location_lat']) && !empty($host['inventory']['location_lon'])
				&& is_numeric($host['inventory']['location_lat']) && is_numeric($host['inventory']['location_lon'])
				&& $host['inventory']['location_lat'] != 0 && $host['inventory']['location_lon'] != 0) {
				$hosts_on_map[$hostid] = $host['name'];
			}
		}
		if (empty($hosts_on_map)) return [];
		
		$severities_to_fetch = [];
		foreach ($severity_filters as $severity => $is_enabled) {
			if ($is_enabled && $severity >= 0) $severities_to_fetch[] = $severity;
		}
		if (empty($severities_to_fetch)) return $detailed_problems;

		try {
			$triggers = \API::Trigger()->get([
				'output' => ['triggerid'], 'selectHosts' => ['hostid'], 'hostids' => array_keys($hosts_on_map),
				'filter' => ['value' => TRIGGER_VALUE_TRUE], 'monitored' => true, 'skipDependent' => true, 'preservekeys' => true
			]);
		} catch (\Exception $e) { return []; }
		if (empty($triggers)) return [];

		$options = [
			'output' => ['eventid', 'objectid', 'clock', 'name', 'severity', 'r_eventid', 'acknowledged', 'suppressed'],
			'source' => EVENT_SOURCE_TRIGGERS, 'object' => EVENT_OBJECT_TRIGGER,
			'sortfield' => 'eventid', 'sortorder' => ZBX_SORT_DOWN,
			'severities' => $severities_to_fetch, 'objectids' => array_keys($triggers)
		];
		if ($problem_filters['show_acknowledged'] == 0) $options['acknowledged'] = false;

		if ($problem_filters['show_suppressed_only'] == 1) {
			$options['suppressed'] = true;
		} elseif ($problem_filters['show_suppressed'] == 1) {
			$p1 = \API::Problem()->get($options + ['suppressed' => false]);
			$p2 = \API::Problem()->get($options + ['suppressed' => true]);
			$problems = $p1 + $p2;
		} else {
			$options['suppressed'] = false;
			$problems = \API::Problem()->get($options);
		}
		
		if (!isset($problems) && empty($problems) && ($problem_filters['show_suppressed'] != 1 || $problem_filters['show_suppressed_only'] == 1)) {
			try { $problems = \API::Problem()->get($options); } catch (\Exception $e) { return $detailed_problems; }
		}
		if (empty($problems)) return $detailed_problems;

		$trigger_to_host_map = [];
		foreach ($triggers as $tid => $trig) {
			if (!empty($trig['hosts'])) $trigger_to_host_map[$tid] = $trig['hosts'][0]['hostid'];
		}

		foreach ($problems as $problem) {
			$p_sup = (int)$problem['suppressed'];
			
			if ($problem_filters['show_suppressed_only'] == 1 && $p_sup == 0) continue;
			if ($problem_filters['show_suppressed'] == 0 && $p_sup == 1) continue;
			
			$hostid = $trigger_to_host_map[$problem['objectid']] ?? null;
			if ($hostid === null || !isset($hosts_on_map[$hostid])) continue;
			
			$detailed_problems[] = [
				'eventid' => $problem['eventid'],
				'hostname' => $hosts_on_map[$hostid],
				'severity' => (int)$problem['severity'],
				'problem' => $problem['name'],
				'acknowledged' => (int)$problem['acknowledged'],
				'suppressed' => $p_sup, // <-- ADICIONADO PARA O FRONTEND
				'age' => $this->formatAge(time() - (int)$problem['clock'])
			];
		}
		return $detailed_problems;
	}

	private function getSeverityColor(int $severity): string {
		$colors = [-1 => '#66BB6A', 0 => '#97AAB3', 1 => '#7499FF', 2 => '#FFC859', 3 => '#FFA059', 4 => '#E97659', 5 => '#E45959'];
		return $colors[$severity] ?? $colors[-1];
	}

	private function formatAge(int $seconds): string {
		if ($seconds < 60) return $seconds . 's';
		elseif ($seconds < 3600) return floor($seconds / 60) . 'm';
		elseif ($seconds < 86400) return floor($seconds / 3600) . 'h';
		elseif ($seconds < 2592000) return floor($seconds / 86400) . 'd';
		elseif ($seconds < 31536000) return floor($seconds / 2592000) . 'm';
		else return floor($seconds / 31536000) . 'y';
	}
}
