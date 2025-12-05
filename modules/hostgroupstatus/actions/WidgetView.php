<?php declare(strict_types = 0);

namespace Modules\HostGroupStatus\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;
use Modules\HostGroupStatus\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$count_mode = $this->fields_values['count_mode'] ?? WidgetForm::COUNT_MODE_WITH_ALARMS;

		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$problem_filters = [
			'evaltype' => $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR,
			'tags' => $this->fields_values['tags'] ?? [],
			'show_acknowledged' => $this->fields_values['show_acknowledged'] ?? 1,
			'show_suppressed' => $this->fields_values['show_suppressed'] ?? 0,
			'exclude_maintenance' => $this->fields_values['exclude_maintenance'] ?? 0
		];

		// --- MUDANÇA: SEVERIDADE ---
		$selected_severities = $this->fields_values['severities'] ?? [];
		$severity_filters = [];
		// Converte o array de IDs [2, 4] para mapa [0=>0, 2=>1, 4=>1, ...]
		for ($i = 0; $i <= 5; $i++) {
			$severity_filters[$i] = in_array($i, $selected_severities) ? 1 : 0;
		}
		// --- FIM DA MUDANÇA ---

		// Passa o novo filtro para a função
		$host_data = $this->getHostStatusData(
			$hostgroups, $hosts, $exclude_hosts, $count_mode, 
			$severity_filters, $problem_filters
		);

		$group_name = '';
		if ($this->fields_values['show_group_name'] ?? 1) {
			if (!empty($this->fields_values['group_name_text'])) {
				$group_name = $this->fields_values['group_name_text'];
			} elseif (!empty($hostgroups)) {
				$group_names = \API::HostGroup()->get([
					'output' => ['name'],
					'groupids' => array_slice($hostgroups, 0, 1)
				]);
				$group_name = !empty($group_names) ? $group_names[0]['name'] : '';
			}
		}

		$widget_color = $this->fields_values['widget_color'] ?? '4CAF50';
		if (strpos($widget_color, '#') !== 0) {
			$widget_color = '#' . $widget_color;
		}

		$text_color = $this->getContrastColor($widget_color);

		$count_mode_labels = [
			WidgetForm::COUNT_MODE_WITH_ALARMS => _(' '),
			WidgetForm::COUNT_MODE_WITHOUT_ALARMS => _(' '),
			WidgetForm::COUNT_MODE_ALL => _(' ')
		];
		$count_mode_label = $count_mode_labels[$count_mode] ?? '';

		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'group_name' => $group_name,
			'show_group_name' => $this->fields_values['show_group_name'] ?? 1,
			'host_count' => $host_data['count'],
			'count_mode' => $count_mode,
			'count_mode_label' => $count_mode_label,
			'background_color' => $widget_color,
			'text_color' => $text_color,
			'font_size' => $this->fields_values['font_size'] ?? 14,
			'font_family' => $this->fields_values['font_family'] ?? 'Arial, sans-serif',
			'show_border' => $this->fields_values['show_border'] ?? 1,
			'border_width' => $this->fields_values['border_width'] ?? 2,
			'border_color' => $widget_color,
			'padding' => $this->fields_values['padding'] ?? 10,
			'enable_url_redirect' => $this->fields_values['enable_url_redirect'] ?? 0,
			'redirect_url' => $this->fields_values['redirect_url'] ?? '',
			'open_in_new_tab' => $this->fields_values['open_in_new_tab'] ?? 1,
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getHostStatusData(
		array $hostgroups, array $hosts, array $exclude_hosts, int $count_mode,
		array $severity_filters, array $problem_filters
	): array {
		if (empty($hostgroups) && empty($hosts)) {
			return ['count' => 0];
		}

		// === PASSO 1: Obter todos os Hosts ===
		$host_params = [
			'output' => ['hostid', 'name'],
			'monitored_hosts' => true,
			'preservekeys' => true,
			'evaltype' => $problem_filters['evaltype'],
			'tags' => $problem_filters['tags']
		];

		if ($problem_filters['exclude_maintenance'] == 1) {
			$host_params['maintenance_status'] = false;
		}

		if (!empty($hosts)) {
			$host_params['hostids'] = $hosts;
		} elseif (!empty($hostgroups)) {
			$host_params['groupids'] = $hostgroups;
		}

		if (!empty($exclude_hosts) && !empty($host_params['hostids'])) {
			$host_params['hostids'] = array_diff($host_params['hostids'], $exclude_hosts);
		}
		
		try {
			$all_hosts = \API::Host()->get($host_params);
		} catch (\Exception $e) {
			return ['count' => 0];
		}
		
		if (!empty($exclude_hosts) && !empty($host_params['groupids'])) {
			$all_hosts = array_filter($all_hosts, function ($hostid) use ($exclude_hosts) {
				return !in_array($hostid, $exclude_hosts);
			}, ARRAY_FILTER_USE_KEY);
		}

		if (empty($all_hosts)) {
			return ['count' => 0];
		}

		$all_host_ids = array_keys($all_hosts);
		$total_host_count = count($all_host_ids);

		if ($count_mode === WidgetForm::COUNT_MODE_ALL) {
			return ['count' => $total_host_count];
		}

		// === PASSO 2: Obter hosts com alarmes ===
		$trigger_severities = [];
		foreach ($severity_filters as $severity => $is_enabled) {
			if ($is_enabled) {
				$trigger_severities[] = $severity;
			}
		}

		$hosts_with_alarms_map = [];
		if (!empty($trigger_severities)) {
			try {
				$trigger_params = [
					'output' => ['triggerid'],
					'selectHosts' => ['hostid'],
					'selectLastEvent' => ['acknowledged', 'suppressed'],
					'hostids' => $all_host_ids, 
					'filter' => [
						'value' => TRIGGER_VALUE_TRUE,
						'priority' => $trigger_severities
					],
					'monitored' => true,
					'skipDependent' => true
				];

				if ($problem_filters['exclude_maintenance'] == 1) {
					$trigger_params['maintenance_status'] = false;
				}
				
				$triggers = \API::Trigger()->get($trigger_params);

				foreach ($triggers as $trigger) {
					$acknowledged = $trigger['lastEvent']['acknowledged'] ?? 0;
					$suppressed = $trigger['lastEvent']['suppressed'] ?? 0;

					if ($acknowledged == 1 && !$problem_filters['show_acknowledged']) {
						continue;
					}
					if ($suppressed == 1 && !$problem_filters['show_suppressed']) {
						continue;
					}

					if (!empty($trigger['hosts'])) {
						foreach ($trigger['hosts'] as $host) {
							if (isset($all_hosts[$host['hostid']])) {
								$hosts_with_alarms_map[$host['hostid']] = true;
							}
						}
					}
				}
			} catch (\Exception $e) {}
		}
		
		$hosts_with_alarms_count = count($hosts_with_alarms_map);

		// === PASSO 3: Calcular ===
		$count = 0;
		switch ($count_mode) {
			case WidgetForm::COUNT_MODE_WITH_ALARMS:
				$count = $hosts_with_alarms_count;
				break;
			case WidgetForm::COUNT_MODE_WITHOUT_ALARMS:
				$count = $total_host_count - $hosts_with_alarms_count;
				break;
		}

		return ['count' => $count];
	}

	private function getContrastColor(string $hex_color): string {
		$hex_color = ltrim($hex_color, '#');
		$r = hexdec(substr($hex_color, 0, 2));
		$g = hexdec(substr($hex_color, 2, 2));
		$b = hexdec(substr($hex_color, 4, 2));
		$luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
		return $luminance > 0.5 ? '#000000' : '#FFFFFF';
	}
}
