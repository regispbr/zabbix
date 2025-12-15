<?php declare(strict_types = 0);

namespace Modules\HostGroupStatus\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\HostGroupStatus\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	// Constantes locais
	private const PROBLEM_STATUS_ALL = 0;
	private const PROBLEM_STATUS_PROBLEM = 1;
	private const PROBLEM_STATUS_RESOLVED = 2;

	protected function doAction(): void {
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$count_mode = $this->fields_values['count_mode'] ?? WidgetForm::COUNT_MODE_WITH_ALARMS;
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		// Filtros de Problema
		$problem_filters = [
			'evaltype' => $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR,
			'tags' => $this->fields_values['tags'] ?? [],
			'show_acknowledged' => $this->fields_values['show_acknowledged'] ?? 1,
			'show_suppressed' => $this->fields_values['show_suppressed'] ?? 0,
			'show_suppressed_only' => $this->fields_values['show_suppressed_only'] ?? 0,
			'exclude_maintenance' => $this->fields_values['exclude_maintenance'] ?? 0,
			'problem_status' => $this->fields_values['problem_status'] ?? self::PROBLEM_STATUS_PROBLEM // NOVO
		];

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
		
		// === PASSO 1: Obter Hosts do Grupo ===
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
		
		$all_hosts = API::Host()->get($host_params);
		
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

		// === PASSO 2: Obter Hosts com Problemas (LÓGICA NOVA) ===
		$hosts_with_alarms_map = [];
		
		if (!empty($severity_ids)) {
			// Prepara opções da API Problem
			// Aqui é onde garantimos que o filtro de TAGS funcione
			$problem_options = [
				'output' => ['objectid', 'suppressed', 'acknowledged', 'r_eventid', 'r_clock'], // Pedimos dados de recuperação
				'source' => EVENT_SOURCE_TRIGGERS,
				'object' => EVENT_OBJECT_TRIGGER,
				'severities' => $severity_ids, // Severidade vai direto aqui
				'evaltype' => $problem_filters['evaltype'],
				'tags' => $problem_filters['tags'] // Tags vão direto aqui
			];

			// Define se buscamos histórico (recent = true) ou apenas ativos (recent = false)
			// Se o filtro for "All" ou "Resolved", precisamos do histórico (recent=true)
			$want_history = ($problem_filters['problem_status'] != self::PROBLEM_STATUS_PROBLEM);
			$problem_options['recent'] = $want_history;

			// Lógica de Supressão
			if ($problem_filters['show_suppressed_only'] == 1) {
				$problem_options['suppressed'] = true;
			} elseif ($problem_filters['show_suppressed'] == 0) {
				$problem_options['suppressed'] = false;
			}
			// Se show_suppressed == 1, não filtramos (traz tudo)

			// BUSCA OS PROBLEMAS
			$problems = API::Problem()->get($problem_options);

			if (!empty($problems)) {
				// Coleta Trigger IDs para descobrir os Hosts
				$triggerids = array_column($problems, 'objectid');
				
				// Busca Triggers para mapear Trigger -> Host
				// Filtramos por hostids do grupo para otimizar e garantir pertinência
				$triggers = API::Trigger()->get([
					'output' => ['triggerid'],
					'selectHosts' => ['hostid'],
					'triggerids' => $triggerids,
					'hostids' => $all_host_ids, // Crucial: Só triggers dos hosts selecionados
					'monitored' => true,
					'preservekeys' => true
				]);

				foreach ($problems as $problem) {
					$triggerid = $problem['objectid'];
					
					// Filtros de Ack (feitos no PHP pois a API tem limitações combinatórias as vezes)
					$p_ack = (int)$problem['acknowledged'];
					if ($p_ack == 1 && !$problem_filters['show_acknowledged']) continue;

					// Filtro de Status (Problem vs Resolved)
					$r_eventid = (int)$problem['r_eventid'];
					$r_clock = (int)$problem['r_clock'];
					$is_resolved = ($r_eventid != 0 || $r_clock != 0);

					if ($problem_filters['problem_status'] == self::PROBLEM_STATUS_RESOLVED) {
						if (!$is_resolved) continue;
					} elseif ($problem_filters['problem_status'] == self::PROBLEM_STATUS_PROBLEM) {
						if ($is_resolved) continue;
					}

					// Se passou nos filtros, marca o host
					if (isset($triggers[$triggerid]['hosts'])) {
						foreach ($triggers[$triggerid]['hosts'] as $host) {
							// Garantia dupla que o host pertence ao grupo filtrado
							if (isset($all_hosts[$host['hostid']])) {
								$hosts_with_alarms_map[$host['hostid']] = true;
							}
						}
					}
				}
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
