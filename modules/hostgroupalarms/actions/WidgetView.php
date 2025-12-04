<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

namespace Modules\HostGroupAlarms\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;
use Modules\HostGroupAlarms\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// Get selected host groups
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		// CRITICAL: Convert checkbox values to integers for proper comparison
		$show_acknowledged = (int)($this->fields_values['show_acknowledged'] ?? 1);
		$show_suppressed = (int)($this->fields_values['show_suppressed'] ?? 0);
		$exclude_maintenance = (int)($this->fields_values['exclude_maintenance'] ?? 0);
		
		$problem_filters = [
			'evaltype' => $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR,
			'tags' => $this->fields_values['tags'] ?? [],
			'show_acknowledged' => $show_acknowledged,
			'show_suppressed' => $show_suppressed,
			'exclude_maintenance' => $exclude_maintenance
		];

		// Severity filters
		$severity_filters = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => $this->fields_values['show_not_classified'] ?? 1,
			WidgetForm::SEVERITY_INFORMATION => $this->fields_values['show_information'] ?? 1,
			WidgetForm::SEVERITY_WARNING => $this->fields_values['show_warning'] ?? 1,
			WidgetForm::SEVERITY_AVERAGE => $this->fields_values['show_average'] ?? 1,
			WidgetForm::SEVERITY_HIGH => $this->fields_values['show_high'] ?? 1,
			WidgetForm::SEVERITY_DISASTER => $this->fields_values['show_disaster'] ?? 1
		];

		// Get alarm counts, highest severity, and detailed alarm data
		$alarm_data = $this->getAlarmData($hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters);

		// Get group name
		$group_name = '';
		if ($this->fields_values['show_group_name'] ?? 1) {
			if (!empty($this->fields_values['group_name_text'])) {
				$group_name = $this->fields_values['group_name_text'];
			} elseif (!empty($hostgroups)) {
				// Get first group name
				$group_names = \API::HostGroup()->get([
					'output' => ['name'],
					'groupids' => array_slice($hostgroups, 0, 1)
				]);
				$group_name = !empty($group_names) ? $group_names[0]['name'] : '';
			}
		}

		// Prepare widget data
		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'group_name' => $group_name,
			'show_group_name' => $this->fields_values['show_group_name'] ?? 1,
			'alarm_counts' => $alarm_data['counts'],
			'total_alarms' => $alarm_data['total'],
			'highest_severity' => $alarm_data['highest_severity'],
			'detailed_alarms' => $alarm_data['detailed_alarms'],
			'background_color' => $this->getSeverityColor($alarm_data['highest_severity']),
			'text_color' => $this->getTextColor($alarm_data['highest_severity']),
			'font_size' => $this->fields_values['font_size'] ?? 14,
			'font_family' => $this->fields_values['font_family'] ?? 'Arial, sans-serif',
			'show_border' => $this->fields_values['show_border'] ?? 1,
			'border_width' => $this->fields_values['border_width'] ?? 2,
			'border_color' => $this->getSeverityColor($alarm_data['highest_severity']),
			'padding' => $this->fields_values['padding'] ?? 10,
			'enable_url_redirect' => $this->fields_values['enable_url_redirect'] ?? 0,
			'redirect_url' => $this->fields_values['redirect_url'] ?? '',
			'open_in_new_tab' => $this->fields_values['open_in_new_tab'] ?? 1,
			'show_detailed_tooltip' => $this->fields_values['show_detailed_tooltip'] ?? 1,
			'tooltip_max_items' => $this->fields_values['tooltip_max_items'] ?? 10,
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getAlarmData(array $hostgroups, array $hosts, array $exclude_hosts, array $severity_filters, array $problem_filters): array {
		$alarm_counts = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => 0,
			WidgetForm::SEVERITY_INFORMATION => 0,
			WidgetForm::SEVERITY_WARNING => 0,
			WidgetForm::SEVERITY_AVERAGE => 0,
			WidgetForm::SEVERITY_HIGH => 0,
			WidgetForm::SEVERITY_DISASTER => 0
		];

		$detailed_alarms = [];

		if (empty($hostgroups) && empty($hosts)) {
			return [
				'counts' => $alarm_counts,
				'total' => 0,
				'highest_severity' => -1,
				'detailed_alarms' => $detailed_alarms
			];
		}

		// Build host filter
		$host_filter = [];
		if (!empty($hosts)) {
			$host_filter['hostids'] = $hosts;
		} elseif (!empty($hostgroups)) {
			$host_filter['groupids'] = $hostgroups;
		}

		if (!empty($exclude_hosts) && !empty($host_filter['hostids'])) {
			$host_filter['hostids'] = array_diff($host_filter['hostids'], $exclude_hosts);
		}

		// Get triggers with problems
		$params = [
			'output' => ['triggerid', 'priority', 'description', 'lastchange'],
			'selectHosts' => ['hostid', 'name', 'maintenance_status'],
			'selectLastEvent' => ['eventid', 'clock', 'acknowledged', 'r_eventid', 'suppressed'],
			'filter' => ['value' => TRIGGER_VALUE_TRUE],
			'monitored' => true,
			'skipDependent' => true,
			'preservekeys' => true,
			'sortfield' => ['priority', 'lastchange'],
			'sortorder' => ['DESC', 'DESC'],
			'evaltype' => $problem_filters['evaltype'],
			'tags' => $problem_filters['tags']
		] + $host_filter;

		// Add maintenance filter if needed
		if ($problem_filters['exclude_maintenance'] == 1) {
			$params['maintenance'] = false;
		}

		$triggers = \API::Trigger()->get($params);

		// Filter out excluded hosts when using groupids
		if (!empty($exclude_hosts) && !empty($host_filter['groupids'])) {
			$triggers = array_filter($triggers, function ($trigger) use ($exclude_hosts) {
				foreach ($trigger['hosts'] as $host) {
					if (in_array($host['hostid'], $exclude_hosts)) {
						return false;
					}
				}
				return true;
			});
		}

		$total_alarms = 0;
		$highest_severity = -1;

		foreach ($triggers as $trigger) {
			$severity = (int)$trigger['priority'];

			// Get acknowledged and suppressed status from lastEvent
			$acknowledged = 0;
			$suppressed = 0;
			
			if (!empty($trigger['lastEvent'])) {
				$acknowledged = (int)($trigger['lastEvent']['acknowledged'] ?? 0);
				$suppressed = (int)($trigger['lastEvent']['suppressed'] ?? 0);
			}

			// CRITICAL FIX: Apply acknowledged filter
			// If show_acknowledged = 0 (unchecked), hide acknowledged problems
			if ($problem_filters['show_acknowledged'] == 0 && $acknowledged == 1) {
				continue;
			}

			// CRITICAL FIX: Apply suppressed filter  
			// If show_suppressed = 0 (unchecked), hide suppressed problems
			if ($problem_filters['show_suppressed'] == 0 && $suppressed == 1) {
				continue;
			}

			// Check if this severity should be counted
			if (!empty($severity_filters[$severity])) {
				$alarm_counts[$severity]++;
				$total_alarms++;
				
				if ($severity > $highest_severity) {
					$highest_severity = $severity;
				}

				$detailed_alarms[] = [
					'triggerid' => $trigger['triggerid'],
					'description' => $trigger['description'],
					'severity' => $severity,
					'severity_name' => $this->getSeverityName($severity),
					'host_name' => !empty($trigger['hosts']) ? $trigger['hosts'][0]['name'] : 'Unknown',
					'lastchange' => $trigger['lastchange'],
					'clock' => !empty($trigger['lastEvent']) ? $trigger['lastEvent']['clock'] : $trigger['lastchange'],
					'acknowledged' => $acknowledged,
					'suppressed' => $suppressed,
					'eventid' => !empty($trigger['lastEvent']) ? $trigger['lastEvent']['eventid'] : null
				];
			}
		}

		// Sort detailed alarms
		usort($detailed_alarms, function($a, $b) {
			if ($a['severity'] === $b['severity']) {
				return $b['clock'] - $a['clock'];
			}
			return $b['severity'] - $a['severity'];
		});

		return [
			'counts' => $alarm_counts,
			'total' => $total_alarms,
			'highest_severity' => $highest_severity,
			'detailed_alarms' => $detailed_alarms
		];
	}

	private function getSeverityName(int $severity): string {
		$severity_names = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => _('Not classified'),
			WidgetForm::SEVERITY_INFORMATION => _('Information'),
			WidgetForm::SEVERITY_WARNING => _('Warning'),
			WidgetForm::SEVERITY_AVERAGE => _('Average'),
			WidgetForm::SEVERITY_HIGH => _('High'),
			WidgetForm::SEVERITY_DISASTER => _('Disaster')
		];

		return $severity_names[$severity] ?? _('Unknown');
	}

	private function getSeverityColor(int $severity): string {
		$colors = [
			-1 => '#66BB6A',
			WidgetForm::SEVERITY_NOT_CLASSIFIED => '#97AAB3',
			WidgetForm::SEVERITY_INFORMATION => '#7499FF',
			WidgetForm::SEVERITY_WARNING => '#FFC859',
			WidgetForm::SEVERITY_AVERAGE => '#FFA059',
			WidgetForm::SEVERITY_HIGH => '#E97659',
			WidgetForm::SEVERITY_DISASTER => '#E45959'
		];

		return $colors[$severity] ?? $colors[-1];
	}

	private function getTextColor(int $severity): string {
		$light_backgrounds = [
			-1,
			WidgetForm::SEVERITY_NOT_CLASSIFIED,
			WidgetForm::SEVERITY_WARNING,
			WidgetForm::SEVERITY_AVERAGE
		];

		return in_array($severity, $light_backgrounds) ? '#000000' : '#FFFFFF';
	}
}
