<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
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

namespace Modules\Reports\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\Reports\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$report_types = $this->fields_values['report_types'] ?? [WidgetForm::REPORT_TYPE_AVAILABILITY];
		$host_groups = $this->fields_values['host_groups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$items = $this->fields_values['items'] ?? [];
		$tags = $this->fields_values['tags'] ?? [];
		$alert_severities = $this->fields_values['alert_severities'] ?? [];
		$date_from = $this->fields_values['date_from'] ?? 'now-7d';
		$date_to = $this->fields_values['date_to'] ?? 'now';

		// Initialize data arrays
		$availability_data = [];
		$performance_data = [];
		$alerts_data = [];

		// Process Availability Report
		if (in_array(WidgetForm::REPORT_TYPE_AVAILABILITY, $report_types)) {
			$availability_data = $this->getAvailabilityData($hosts, $host_groups, $date_from, $date_to);
		}

		// Process Performance Report
		if (in_array(WidgetForm::REPORT_TYPE_PERFORMANCE, $report_types)) {
			$performance_data = $this->getPerformanceData($hosts, $host_groups, $items, $date_from, $date_to);
		}

		// Process Alerts Report
		if (in_array(WidgetForm::REPORT_TYPE_ALERTS, $report_types)) {
			$alerts_data = $this->getAlertsData($hosts, $host_groups, $alert_severities, $tags, $date_from, $date_to);
		}

		// Prepare chart data if enabled
		$chart_data = [];
		if ($this->fields_values['show_charts'] ?? 1) {
			$chart_data = $this->prepareChartData(
				$availability_data,
				$performance_data,
				$alerts_data,
				$this->fields_values['chart_type'] ?? WidgetForm::CHART_TYPE_LINE
			);
		}

		// Prepare widget data
		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'report_title' => $this->fields_values['report_title'] ?? 'Infrastructure Report',
			'report_types' => $report_types,
			'display_format' => $this->fields_values['display_format'] ?? WidgetForm::FORMAT_OPERATIONAL,
			'date_from' => $date_from,
			'date_to' => $date_to,
			'availability_data' => $availability_data,
			'performance_data' => $performance_data,
			'alerts_data' => $alerts_data,
			'chart_data' => $chart_data,
			'show_charts' => $this->fields_values['show_charts'] ?? 1,
			'chart_type' => $this->fields_values['chart_type'] ?? WidgetForm::CHART_TYPE_LINE,
			'show_page_numbers' => $this->fields_values['show_page_numbers'] ?? 1,
			'show_header' => $this->fields_values['show_header'] ?? 1,
			'show_footer' => $this->fields_values['show_footer'] ?? 1,
			'jrxml_template' => $this->fields_values['jrxml_template'] ?? '',
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	/**
	 * Get availability data for hosts
	 */
	private function getAvailabilityData(array $hosts, array $host_groups, string $date_from, string $date_to): array {
		$availability_data = [];

		// Get host IDs from groups if specified
		$host_ids = [];
		if (!empty($host_groups)) {
			$group_hosts = API::Host()->get([
				'output' => ['hostid', 'name', 'status'],
				'groupids' => $host_groups,
				'monitored_hosts' => true
			]);
			foreach ($group_hosts as $host) {
				$host_ids[] = $host['hostid'];
			}
		}

		// Add directly specified hosts
		if (!empty($hosts)) {
			$host_ids = array_merge($host_ids, $hosts);
		}

		// Remove duplicates
		$host_ids = array_unique($host_ids);

		if (empty($host_ids)) {
			return $availability_data;
		}

		// Get host details with availability
		$hosts_data = API::Host()->get([
			'output' => ['hostid', 'name', 'status', 'available', 'error'],
			'hostids' => $host_ids,
			'selectInterfaces' => ['type', 'available', 'error']
		]);

		foreach ($hosts_data as $host) {
			$availability_percentage = $this->calculateAvailability($host['hostid'], $date_from, $date_to);
			
			$availability_data[] = [
				'hostid' => $host['hostid'],
				'hostname' => $host['name'],
				'status' => $host['status'],
				'availability' => $availability_percentage,
				'available' => $host['available'],
				'error' => $host['error'] ?? ''
			];
		}

		return $availability_data;
	}

	/**
	 * Get performance data for items
	 */
	private function getPerformanceData(array $hosts, array $host_groups, array $items, string $date_from, string $date_to): array {
		$performance_data = [];

		// Get host IDs
		$host_ids = $this->getHostIds($hosts, $host_groups);
		
		if (empty($host_ids)) {
			return $performance_data;
		}

		// Get items data
		$item_params = [
			'output' => ['itemid', 'name', 'key_', 'value_type', 'units', 'lastvalue'],
			'hostids' => $host_ids,
			'monitored' => true,
			'selectHosts' => ['name']
		];

		if (!empty($items)) {
			$item_params['itemids'] = $items;
		}

		$items_data = API::Item()->get($item_params);

		foreach ($items_data as $item) {
			$history = $this->getItemHistory($item['itemid'], $date_from, $date_to);
			
			$performance_data[] = [
				'itemid' => $item['itemid'],
				'hostname' => $item['hosts'][0]['name'] ?? '',
				'item_name' => $item['name'],
				'key' => $item['key_'],
				'units' => $item['units'],
				'last_value' => $item['lastvalue'],
				'history' => $history,
				'statistics' => $this->calculateStatistics($history)
			];
		}

		return $performance_data;
	}

	/**
	 * Get alerts data with severity filtering
	 */
	private function getAlertsData(array $hosts, array $host_groups, array $severities, array $tags, string $date_from, string $date_to): array {
		$alerts_data = [];

		// Get host IDs
		$host_ids = $this->getHostIds($hosts, $host_groups);

		// Convert date strings to timestamps
		$time_from = strtotime($date_from);
		$time_to = strtotime($date_to);

		// Get problems (alerts)
		$problem_params = [
			'output' => ['eventid', 'objectid', 'name', 'severity', 'clock', 'r_clock', 'acknowledged'],
			'time_from' => $time_from,
			'time_till' => $time_to,
			'recent' => false,
			'selectHosts' => ['name'],
			'selectTags' => ['tag', 'value'],
			'sortfield' => ['clock'],
			'sortorder' => 'DESC'
		];

		if (!empty($host_ids)) {
			$problem_params['hostids'] = $host_ids;
		}

		if (!empty($severities)) {
			$problem_params['severities'] = $severities;
		}

		if (!empty($tags)) {
			$problem_params['tags'] = $tags;
		}

		$problems = API::Problem()->get($problem_params);

		foreach ($problems as $problem) {
			$duration = $problem['r_clock'] > 0 
				? $problem['r_clock'] - $problem['clock'] 
				: time() - $problem['clock'];

			$alerts_data[] = [
				'eventid' => $problem['eventid'],
				'hostname' => $problem['hosts'][0]['name'] ?? 'Unknown',
				'problem_name' => $problem['name'],
				'severity' => $problem['severity'],
				'severity_name' => $this->getSeverityName($problem['severity']),
				'start_time' => $problem['clock'],
				'end_time' => $problem['r_clock'],
				'duration' => $duration,
				'acknowledged' => $problem['acknowledged'],
				'tags' => $problem['tags']
			];
		}

		return $alerts_data;
	}

	/**
	 * Prepare chart data based on report type
	 */
	private function prepareChartData(array $availability_data, array $performance_data, array $alerts_data, int $chart_type): array {
		$chart_data = [];

		// Availability chart
		if (!empty($availability_data)) {
			$chart_data['availability'] = [
				'labels' => array_column($availability_data, 'hostname'),
				'values' => array_column($availability_data, 'availability'),
				'type' => $this->getChartTypeName($chart_type)
			];
		}

		// Performance chart
		if (!empty($performance_data)) {
			$chart_data['performance'] = [];
			foreach ($performance_data as $item) {
				if (!empty($item['history'])) {
					$chart_data['performance'][] = [
						'label' => $item['hostname'] . ' - ' . $item['item_name'],
						'data' => $item['history'],
						'type' => $this->getChartTypeName($chart_type)
					];
				}
			}
		}

		// Alerts chart - severity distribution
		if (!empty($alerts_data)) {
			$severity_counts = array_count_values(array_column($alerts_data, 'severity'));
			$chart_data['alerts'] = [
				'labels' => array_map([$this, 'getSeverityName'], array_keys($severity_counts)),
				'values' => array_values($severity_counts),
				'type' => 'pie'
			];
		}

		return $chart_data;
	}

	/**
	 * Helper functions
	 */
	private function getHostIds(array $hosts, array $host_groups): array {
		$host_ids = [];

		if (!empty($host_groups)) {
			$group_hosts = API::Host()->get([
				'output' => ['hostid'],
				'groupids' => $host_groups
			]);
			$host_ids = array_column($group_hosts, 'hostid');
		}

		if (!empty($hosts)) {
			$host_ids = array_merge($host_ids, $hosts);
		}

		return array_unique($host_ids);
	}

	private function calculateAvailability(string $hostid, string $date_from, string $date_to): float {
		// Simplified availability calculation
		// In production, this should query actual uptime/downtime data
		$time_from = strtotime($date_from);
		$time_to = strtotime($date_to);
		$total_time = $time_to - $time_from;

		// Get problems for this host in the time period
		$problems = API::Problem()->get([
			'output' => ['clock', 'r_clock'],
			'hostids' => [$hostid],
			'time_from' => $time_from,
			'time_till' => $time_to
		]);

		$downtime = 0;
		foreach ($problems as $problem) {
			$end_time = $problem['r_clock'] > 0 ? $problem['r_clock'] : $time_to;
			$downtime += ($end_time - $problem['clock']);
		}

		$uptime = $total_time - $downtime;
		return ($uptime / $total_time) * 100;
	}

	private function getItemHistory(string $itemid, string $date_from, string $date_to): array {
		$time_from = strtotime($date_from);
		$time_to = strtotime($date_to);

		$history = API::History()->get([
			'output' => ['clock', 'value'],
			'itemids' => [$itemid],
			'time_from' => $time_from,
			'time_till' => $time_to,
			'sortfield' => 'clock',
			'sortorder' => 'ASC',
			'limit' => 1000
		]);

		return $history;
	}

	private function calculateStatistics(array $history): array {
		if (empty($history)) {
			return ['min' => 0, 'max' => 0, 'avg' => 0];
		}

		$values = array_column($history, 'value');
		return [
			'min' => min($values),
			'max' => max($values),
			'avg' => array_sum($values) / count($values)
		];
	}

	private function getSeverityName(int $severity): string {
		$severity_names = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => 'Not classified',
			WidgetForm::SEVERITY_INFORMATION => 'Information',
			WidgetForm::SEVERITY_WARNING => 'Warning',
			WidgetForm::SEVERITY_AVERAGE => 'Average',
			WidgetForm::SEVERITY_HIGH => 'High',
			WidgetForm::SEVERITY_DISASTER => 'Disaster'
		];

		return $severity_names[$severity] ?? 'Unknown';
	}

	private function getChartTypeName(int $chart_type): string {
		$chart_types = [
			WidgetForm::CHART_TYPE_LINE => 'line',
			WidgetForm::CHART_TYPE_AREA => 'area',
			WidgetForm::CHART_TYPE_BAR => 'bar',
			WidgetForm::CHART_TYPE_PIE => 'pie',
			WidgetForm::CHART_TYPE_GAUGE => 'gauge'
		];

		return $chart_types[$chart_type] ?? 'line';
	}
}