<?php declare(strict_types = 0);

namespace Modules\HostGroupStatus\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
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
			'show_suppressed_only' => $this->fields_values['show_suppressed_only'] ?? 0,
			'exclude_maintenance' => $this->fields_values['exclude_maintenance'] ?? 0
		];

		// Severidades (Array de IDs)
		$severity_ids = $this->fields_values['severities'] ?? [];

		$host_data = $this->getHostStatusData(
			$hostgroups, $hosts, $exclude_hosts, $count_mode, 
			$severity_ids, $problem_filters
		);

		$group_name = '';
		if ($this->fields_values['show_group_name'] ?? 1) {
			if (!empty($this->fields_values['group_name_text'])) {
				$group_name = $this->fields_values['group_name_text'];
			} elseif (!empty($hostgroups)) {
				$group_names = API::HostGroup()->get([
					'output' => ['name'],
					'groupids' => array_slice($hostgroups, 0, 1)
				]);
				$group_name = !empty($group_names) ? $group_names[0]['name'] : '';
			}
		}

		$widget_color = $this->fields_values['widget_color'] ?? '4CAF50';
		if (strpos($widget_color, '#') !== 0) $widget_color = '#' . $widget_color;
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
			'user' => ['debug_mode' => $this->getDebugMode()]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getHostStatusData(
		array $hostgroups, array $hosts, array $exclude_hosts, int $count_mode,
		array $severity_ids, array $problem_filters
	): array {
		
		// === PASSO 1: Obter Hosts ===
		$host_params = [
			'output' => ['hostid'],
			'monitored_hosts' => true,
			'preservekeys' => true,
		];

		if ($problem_filters['exclude_maintenance'] == 1) {
			$host_params['filter']['maintenance_status'] = HOST_MAINTENANCE_STATUS_OFF;
		}

		if (!empty($hosts)) {
			$host_params['hostids'] = $hosts;
		} elseif (!empty($hostgroups)) {
			$host_params['groupids'] = getSubGroups($hostgroups);
		} else {
			return ['count' => 0];
		}

		if (!empty($exclude_hosts) && !empty($host_params['hostids'])) {
			$host_params['hostids'] = array_diff($host_params['hostids'], $exclude_hosts);
		}
		
		try {
			$all_hosts = API::Host()->get($host_params);
		} catch (\Exception $e) {
			return ['count' => 0];
		}
		
		if (!empty($exclude_hosts)) {
			$all_hosts = array_diff_key($all_hosts, array_flip($exclude_hosts));
		}

		if (empty($all_hosts)) return ['count' => 0];

		$total_host_count = count($all_hosts);
		$all_host_ids = array_keys($all_hosts);

		// Se modo "All Hosts", retornamos direto
		if ($count_mode === WidgetForm::COUNT_MODE_ALL) {
			return ['count' => $total_host_count];
		}

		// === PASSO 2: Obter Hosts com Problemas (Corrigido) ===
		$hosts_with_alarms_map = [];
		
		if (!empty($severity_ids)) {
			try {
				// 2.1 Buscar Triggers (para saber os Hosts)
				$triggers = API::Trigger()->get([
					'output' => ['triggerid'],
					'selectHosts' => ['hostid'], // AQUI pode usar selectHosts
					'hostids' => $all_host_ids,
					'filter' => [
						'value' => TRIGGER_VALUE_TRUE, // Apenas triggers em estado de problema
						'priority' => $severity_ids,
						'status' => TRIGGER_STATUS_ENABLED
					],
					'monitored' => true,
					'skipDependent' => true,
					'preservekeys' => true
				]);

				if (!empty($triggers)) {
					$triggerids = array_keys($triggers);

					// 2.2 Buscar Problemas (para saber status Supressed/Ack)
					// Não usamos selectHosts aqui, pois dá erro.
					$problem_options = [
						'output' => ['objectid', 'suppressed', 'acknowledged'],
						'objectids' => $triggerids,
						'source' => EVENT_SOURCE_TRIGGERS,
						'object' => EVENT_OBJECT_TRIGGER,
						'recent' => true,
						'evaltype' => $problem_filters['evaltype'],
						'tags' => $problem_filters['tags']
					];

					// Lógica de Supressão na API
					if ($problem_filters['show_suppressed_only'] == 1) {
						$problem_options['suppressed'] = true;
					} elseif ($problem_filters['show_suppressed'] == 1) {
						// Trazer tudo (duas chamadas para garantir)
						$p1 = API::Problem()->get($problem_options + ['suppressed' => false]);
						$p2 = API::Problem()->get($problem_options + ['suppressed' => true]);
						$problems = array_merge($p1, $p2);
					} else {
						// Padrão: Apenas não suprimidos
						$problem_options['suppressed'] = false;
						$problems = API::Problem()->get($problem_options);
					}

					// Se não foi a estratégia de merge, executa agora
					if (!isset($problems)) {
						$problems = API::Problem()->get($problem_options);
					}

					// 2.3 Cruzamento
					foreach ($problems as $problem) {
						$triggerid = $problem['objectid'];
						$p_ack = (int)$problem['acknowledged'];
						$p_sup = (int)$problem['suppressed'];

						// Filtros PHP de segurança
						if ($p_ack == 1 && !$problem_filters['show_acknowledged']) continue;
						
						if ($problem_filters['show_suppressed_only'] == 1 && $p_sup == 0) continue;
						if ($problem_filters['show_suppressed'] == 0 && $p_sup == 1) continue;

						// Mapear para o Host usando a Trigger
						if (isset($triggers[$triggerid]['hosts'])) {
							foreach ($triggers[$triggerid]['hosts'] as $host) {
								if (isset($all_hosts[$host['hostid']])) {
									$hosts_with_alarms_map[$host['hostid']] = true;
								}
							}
						}
					}
				}
			} catch (\Exception $e) {
				// error_log("HostGroupStatus Error: " . $e->getMessage());
			}
		}
		
		$hosts_with_alarms_count = count($hosts_with_alarms_map);

		// === PASSO 3: Retorno ===
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
