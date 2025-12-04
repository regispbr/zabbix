<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

namespace Modules\HostCounter\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		
		$count_problems = $this->fields_values['count_problems'] ?? 1;
		$count_items = $this->fields_values['count_items'] ?? 1;
		$count_triggers = $this->fields_values['count_triggers'] ?? 1;
		$count_disabled = $this->fields_values['count_disabled'] ?? 1;
		$count_maintenance = $this->fields_values['count_maintenance'] ?? 1;
		$show_suppressed = $this->fields_values['show_suppressed'] ?? 0;
		$custom_icon = $this->fields_values['custom_icon'] ?? '';

		// Get host counts
		$counts = $this->getHostCounts($hostgroups, $hosts, [
			'count_problems' => $count_problems,
			'count_items' => $count_items,
			'count_triggers' => $count_triggers,
			'count_disabled' => $count_disabled,
			'count_maintenance' => $count_maintenance,
			'show_suppressed' => $show_suppressed
		]);

		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'counts' => $counts,
			'custom_icon' => $custom_icon,
			'count_problems' => $count_problems,
			'count_items' => $count_items,
			'count_triggers' => $count_triggers,
			'count_disabled' => $count_disabled,
			'count_maintenance' => $count_maintenance,
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getHostCounts(array $hostgroups, array $hosts, array $filters): array {
		if (empty($hostgroups) && empty($hosts)) {
			return [
				'total_hosts' => 0,
				'active_hosts' => 0,
				'disabled_hosts' => 0,
				'maintenance_hosts' => 0,
				'total_problems' => 0,
				'total_items' => 0,
				'total_triggers' => 0
			];
		}

		// Base host query
		$host_params = [
			'output' => ['hostid', 'name', 'status', 'maintenance_status'],
			'preservekeys' => true
		];

		if (!empty($hosts)) {
			$host_params['hostids'] = $hosts;
		} elseif (!empty($hostgroups)) {
			$host_params['groupids'] = $hostgroups;
		}

		try {
			$all_hosts = API::Host()->get($host_params);
		} catch (Exception $e) {
			return [
				'total_hosts' => 0,
				'active_hosts' => 0,
				'disabled_hosts' => 0,
				'maintenance_hosts' => 0,
				'total_problems' => 0,
				'total_items' => 0,
				'total_triggers' => 0
			];
		}

		$counts = [
			'total_hosts' => count($all_hosts),
			'active_hosts' => 0,
			'disabled_hosts' => 0,
			'maintenance_hosts' => 0,
			'total_problems' => 0,
			'total_items' => 0,
			'total_triggers' => 0
		];

		$hostids = array_keys($all_hosts);

		// Count host statuses
		foreach ($all_hosts as $host) {
			if ($host['status'] == HOST_STATUS_MONITORED) {
				$counts['active_hosts']++;
			} else {
				$counts['disabled_hosts']++;
			}

			if ($host['maintenance_status'] == HOST_MAINTENANCE_STATUS_ON) {
				$counts['maintenance_hosts']++;
			}
		}

		// Count problems if requested
		if ($filters['count_problems'] && $hostids) {
			try {
				$problem_params = [
					'output' => ['eventid'],
					'hostids' => $hostids,
					'countOutput' => true
				];

				if (!$filters['show_suppressed']) {
					$problem_params['suppressed'] = false;
				}

				$counts['total_problems'] = API::Problem()->get($problem_params);
			} catch (Exception $e) {
				$counts['total_problems'] = 0;
			}
		}

		// Count items if requested
		if ($filters['count_items'] && $hostids) {
			try {
				$counts['total_items'] = API::Item()->get([
					'output' => ['itemid'],
					'hostids' => $hostids,
					'countOutput' => true,
					'webitems' => true
				]);
			} catch (Exception $e) {
				$counts['total_items'] = 0;
			}
		}

		// Count triggers if requested
		if ($filters['count_triggers'] && $hostids) {
			try {
				$counts['total_triggers'] = API::Trigger()->get([
					'output' => ['triggerid'],
					'hostids' => $hostids,
					'countOutput' => true,
					'monitored' => true
				]);
			} catch (Exception $e) {
				$counts['total_triggers'] = 0;
			}
		}

		return $counts;
	}
}
