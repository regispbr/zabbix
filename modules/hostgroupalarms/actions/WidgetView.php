<?php declare(strict_types = 0);

namespace Modules\HostGroupAlarms\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use CScreenProblem;
use CSettingsHelper; // <--- Importante
use CSeverityHelper; // <--- Adicionado para evitar erro de classe não encontrada
use Modules\HostGroupAlarms\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// 1. Coleta de Filtros
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$show_acknowledged = (int)($this->fields_values['show_acknowledged'] ?? 1);
		$show_suppressed = (int)($this->fields_values['show_suppressed'] ?? 0);
		$exclude_maintenance = (int)($this->fields_values['exclude_maintenance'] ?? 0);
		
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];

		// 2. Monta Severidades
		$severities = [];
		$map_severity = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => 'show_not_classified',
			WidgetForm::SEVERITY_INFORMATION => 'show_information',
			WidgetForm::SEVERITY_WARNING => 'show_warning',
			WidgetForm::SEVERITY_AVERAGE => 'show_average',
			WidgetForm::SEVERITY_HIGH => 'show_high',
			WidgetForm::SEVERITY_DISASTER => 'show_disaster'
		];

		foreach ($map_severity as $sev_code => $field_name) {
			if (!empty($this->fields_values[$field_name])) {
				$severities[] = $sev_code;
			}
		}

		$show_mode = TRIGGERS_OPTION_RECENT_PROBLEM; 
		$ack_status = ($show_acknowledged == 1) ? ZBX_ACK_STATUS_ALL : ZBX_ACK_STATUS_UNACK;

		// --- CORREÇÃO: Pega o limite de busca do Zabbix ---
		$search_limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT);
		// --------------------------------------------------

		// 3. CHAMA A ENGINE (Corrigido: Passando search_limit)
		$data = CScreenProblem::getData([
			'show' => $show_mode,
			'groupids' => $hostgroups,
			'hostids' => $hosts,
			'name' => '',
			'severities' => $severities,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'show_symptoms' => false,
			'show_suppressed' => ($show_suppressed == 1),
			'acknowledgement_status' => $ack_status,
			'show_opdata' => 0
		], $search_limit); // <--- Segundo argumento adicionado

		// 4. Processamento
		$alarm_counts = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
		$detailed_alarms = [];
		$total_alarms = 0;
		$highest_severity = -1;

		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerid = $problem['objectid'];
				
				if (!isset($data['triggers'][$triggerid])) continue;
				$trigger = $data['triggers'][$triggerid];
				
				$host = reset($trigger['hosts']);
				if (!$host) continue;

				if (in_array($host['hostid'], $exclude_hosts)) continue;

				if ($exclude_maintenance == 1 && $host['maintenance_status'] == 1) continue;

				$severity = (int)$problem['severity'];
				$alarm_counts[$severity]++;
				$total_alarms++;

				if ($severity > $highest_severity) {
					$highest_severity = $severity;
				}

				$detailed_alarms[] = [
					'eventid' => $problem['eventid'],
					'triggerid' => $triggerid,
					'description' => $problem['name'],
					'severity' => $severity,
					'severity_name' => CSeverityHelper::getName($severity),
					'host_name' => $host['name'],
					'clock' => $problem['clock'],
					'acknowledged' => ($problem['acknowledged'] == EVENT_ACKNOWLEDGED) ? 1 : 0,
					'suppressed' => ($problem['suppressed'] == ZBX_PROBLEM_SUPPRESSED) ? 1 : 0
				];
			}
		}

		usort($detailed_alarms, function($a, $b) {
			if ($a['severity'] === $b['severity']) {
				return $b['clock'] - $a['clock'];
			}
			return $b['severity'] - $a['severity'];
		});

		// 5. Renderização
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

		$response_data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'group_name' => $group_name,
			'show_group_name' => $this->fields_values['show_group_name'] ?? 1,
			'alarm_counts' => $alarm_counts,
			'total_alarms' => $total_alarms,
			'highest_severity' => $highest_severity,
			'detailed_alarms' => $detailed_alarms,
			'background_color' => $this->getSeverityColor($highest_severity),
			'text_color' => $this->getTextColor($highest_severity),
			'font_size' => $this->fields_values['font_size'] ?? 14,
			'font_family' => $this->fields_values['font_family'] ?? 'Arial, sans-serif',
			'show_border' => $this->fields_values['show_border'] ?? 1,
			'border_width' => $this->fields_values['border_width'] ?? 2,
			'border_color' => $this->getSeverityColor($highest_severity),
			'padding' => $this->fields_values['padding'] ?? 10,
			'enable_url_redirect' => $this->fields_values['enable_url_redirect'] ?? 0,
			'redirect_url' => $this->fields_values['redirect_url'] ?? '',
			'open_in_new_tab' => $this->fields_values['open_in_new_tab'] ?? 1,
			'show_detailed_tooltip' => $this->fields_values['show_detailed_tooltip'] ?? 1,
			'tooltip_max_items' => $this->fields_values['tooltip_max_items'] ?? 10,
			'fields_values' => $this->fields_values,
			'user' => ['debug_mode' => $this->getDebugMode()]
		];

		$this->setResponse(new CControllerResponseData($response_data));
	}

	private function getSeverityColor(int $severity): string {
		$colors = [-1=>'#66BB6A', 0=>'#97AAB3', 1=>'#7499FF', 2=>'#FFC859', 3=>'#FFA059', 4=>'#E97659', 5=>'#E45959'];
		return $colors[$severity] ?? $colors[-1];
	}

	private function getTextColor(int $severity): string {
		return in_array($severity, [-1, 0, 2, 3]) ? '#000000' : '#FFFFFF';
	}
}
