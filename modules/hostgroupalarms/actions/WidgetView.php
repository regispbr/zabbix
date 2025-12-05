<?php declare(strict_types = 0);

namespace Modules\HostGroupAlarms\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;
use Modules\HostGroupAlarms\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
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

		$severity_filters = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => $this->fields_values['show_not_classified'] ?? 1,
			WidgetForm::SEVERITY_INFORMATION => $this->fields_values['show_information'] ?? 1,
			WidgetForm::SEVERITY_WARNING => $this->fields_values['show_warning'] ?? 1,
			WidgetForm::SEVERITY_AVERAGE => $this->fields_values['show_average'] ?? 1,
			WidgetForm::SEVERITY_HIGH => $this->fields_values['show_high'] ?? 1,
			WidgetForm::SEVERITY_DISASTER => $this->fields_values['show_disaster'] ?? 1
		];

		$alarm_data = $this->getAlarmData($hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters);

		// Configuração do nome do grupo (visual)
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
			
			// DEBUG LOG para o JS
			'debug_log' => $alarm_data['debug_log'],
			
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getAlarmData(array $hostgroups, array $hosts, array $exclude_hosts, array $severity_filters, array $problem_filters): array {
		
        // DEBUG: Arquivo de log
        $log_file = '/tmp/widget_debug.txt';
        $log_data = "--- EXECUCAO " . date('H:i:s') . " ---\n";

		$alarm_counts = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => 0,
			WidgetForm::SEVERITY_INFORMATION => 0,
			WidgetForm::SEVERITY_WARNING => 0,
			WidgetForm::SEVERITY_AVERAGE => 0,
			WidgetForm::SEVERITY_HIGH => 0,
			WidgetForm::SEVERITY_DISASTER => 0
		];

		$detailed_alarms = [];
		$debug_log = [];

		if (empty($hostgroups) && empty($hosts)) {
			return ['counts' => $alarm_counts, 'total' => 0, 'highest_severity' => -1, 'detailed_alarms' => [], 'debug_log' => []];
		}

		$host_filter = [];
		if (!empty($hosts)) {
			$host_filter['hostids'] = $hosts;
		} elseif (!empty($hostgroups)) {
			$host_filter['groupids'] = $hostgroups;
		}

		if (!empty($exclude_hosts) && !empty($host_filter['hostids'])) {
			$host_filter['hostids'] = array_diff($host_filter['hostids'], $exclude_hosts);
		}

		$params = [
			'output' => ['triggerid', 'priority', 'description', 'lastchange', 'value'],
			'selectHosts' => ['hostid', 'name', 'maintenance_status'],
			'selectLastEvent' => ['eventid', 'clock', 'acknowledged', 'suppressed'],
			'filter' => ['value' => TRIGGER_VALUE_TRUE],
			'monitored' => true,
			'skipDependent' => true,
			'preservekeys' => true,
			'sortfield' => ['priority', 'lastchange'],
			'sortorder' => ['DESC', 'DESC'],
			'evaltype' => $problem_filters['evaltype'],
			'tags' => $problem_filters['tags']
		] + $host_filter;

		// API Call
		$triggers = \API::Trigger()->get($params);

        // --- CORREÇÃO DO ERRO BOOL ---
        if (!is_array($triggers)) {
            $log_data .= "ERRO CRITICO: API::Trigger()->get retornou FALSE/NULL!\n";
            $log_data .= "Params enviados: " . json_encode($params) . "\n";
            $triggers = []; // Força array vazio para não quebrar o foreach
            
            $debug_log[] = [
                'ERROR' => 'API Failed',
                'Reason' => 'Trigger.get returned false'
            ];
        } else {
            $log_data .= "API OK. Triggers encontradas: " . count($triggers) . "\n";
        }
        // ------------------------------

		// Exclusão manual de hosts
		if (!empty($exclude_hosts) && !empty($host_filter['groupids'])) {
			$triggers = array_filter($triggers, function ($trigger) use ($exclude_hosts) {
                // Segurança extra caso hosts não venha
                if (!isset($trigger['hosts']) || !is_array($trigger['hosts'])) return true;
				foreach ($trigger['hosts'] as $host) {
					if (in_array($host['hostid'], $exclude_hosts)) {
						return false;
					}
				}
				return true;
			});
		}

		// Filtro de Manutenção
		if ($problem_filters['exclude_maintenance'] == 1) {
			$triggers = array_filter($triggers, function ($trigger) {
				return isset($trigger['hosts'][0]) && $trigger['hosts'][0]['maintenance_status'] == 0; 
			});
		}

		$total_alarms = 0;
		$highest_severity = -1;

        // O erro acontecia aqui (foreach em booleano)
		foreach ($triggers as $trigger) {
			$severity = (int)$trigger['priority'];

			$acknowledged = 0;
			$suppressed = 0;
			
			if (!empty($trigger['lastEvent'])) {
				$acknowledged = (int)($trigger['lastEvent']['acknowledged'] ?? 0);
				$suppressed = (int)($trigger['lastEvent']['suppressed'] ?? 0);
			}

			// --- LÓGICA DE FILTRO ---
			$is_kept = true;
			$reason = 'OK';

			// 1. Filtro ACK
			if ($problem_filters['show_acknowledged'] == 0 && $acknowledged == 1) {
				$is_kept = false;
				$reason = 'Filtrado por ACK';
			}

			// 2. Filtro Suppressed
			if ($is_kept && $problem_filters['show_suppressed'] == 0 && $suppressed == 1) {
				$is_kept = false;
				$reason = 'Filtrado por SUPPRESSED';
			}
			
			// 3. Filtro Severidade
			if ($is_kept && empty($severity_filters[$severity])) {
				$is_kept = false;
				$reason = 'Filtrado por SEVERIDADE';
			}

			// --- DEBUG LOG ---
            if ($is_kept) {
                // Só logo no arquivo se for mantido ou se houver dúvida, para não explodir o disco
                 $log_data .= "KEEP: {$trigger['description']} | Ack:$acknowledged | Sup:$suppressed\n";
            }
            
			$debug_log[] = [
				'Trigger' => $trigger['description'],
				'Host' => $trigger['hosts'][0]['name'] ?? 'Unknown',
				'Severity' => $severity,
				'Is_Ack' => $acknowledged,
				'Is_Suppressed' => $suppressed,
				'RESULT' => $is_kept ? 'KEPT' : 'DROPPED',
				'Reason' => $reason
			];

			if ($is_kept) {
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

        // Tenta salvar o arquivo de log
        @file_put_contents($log_file, $log_data, FILE_APPEND);

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
			'detailed_alarms' => $detailed_alarms,
			'debug_log' => $debug_log
		];
	}

	private function getSeverityName(int $severity): string {
		$severity_names = [0=>_('Not classified'), 1=>_('Information'), 2=>_('Warning'), 3=>_('Average'), 4=>_('High'), 5=>_('Disaster')];
		return $severity_names[$severity] ?? _('Unknown');
	}

	private function getSeverityColor(int $severity): string {
		$colors = [-1=>'#66BB6A', 0=>'#97AAB3', 1=>'#7499FF', 2=>'#FFC859', 3=>'#FFA059', 4=>'#E97659', 5=>'#E45959'];
		return $colors[$severity] ?? $colors[-1];
	}

	private function getTextColor(int $severity): string {
		$light = [-1, WidgetForm::SEVERITY_NOT_CLASSIFIED, WidgetForm::SEVERITY_WARNING, WidgetForm::SEVERITY_AVERAGE];
		return in_array($severity, $light) ? '#000000' : '#FFFFFF';
	}
}
