<?php declare(strict_types = 0);

namespace Modules\HostGroupAlarms\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use CScreenProblem;
use CSettingsHelper;
use CSeverityHelper;
use Modules\HostGroupAlarms\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// Constantes
		if (!defined('ZBX_PROBLEM_SUPPRESSED')) define('ZBX_PROBLEM_SUPPRESSED', 1);
		if (!defined('ZBX_ACK_STATUS_ALL')) define('ZBX_ACK_STATUS_ALL', 1);
		if (!defined('ZBX_ACK_STATUS_UNACK')) define('ZBX_ACK_STATUS_UNACK', 2);
		if (!defined('TRIGGERS_OPTION_RECENT_PROBLEM')) define('TRIGGERS_OPTION_RECENT_PROBLEM', 1);

		// 1. Coleta de Filtros
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$show_acknowledged = (int)($this->fields_values['show_acknowledged'] ?? 1);
		$exclude_maintenance = (int)($this->fields_values['exclude_maintenance'] ?? 0);
		
		// --- LÓGICA DE SUPRESSED ---
		$show_suppressed = (int)($this->fields_values['show_suppressed'] ?? 0);
		$show_suppressed_only = (int)($this->fields_values['show_suppressed_only'] ?? 0);
		
		$engine_show_suppressed = ($show_suppressed == 1 || $show_suppressed_only == 1);
		// ---------------------------
		
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];
		$severities = $this->fields_values['severities'] ?? []; 

		$show_mode = TRIGGERS_OPTION_RECENT_PROBLEM; 
		$ack_status = ($show_acknowledged == 1) ? ZBX_ACK_STATUS_ALL : ZBX_ACK_STATUS_UNACK;
		$search_limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT);

		// 2. Engine Nativa
		$data = CScreenProblem::getData([
			'show' => $show_mode,
			'groupids' => $hostgroups,
			'hostids' => $hosts,
			'name' => '',
			'severities' => $severities,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'show_symptoms' => false,
			'show_suppressed' => $engine_show_suppressed,
			'acknowledgement_status' => $ack_status,
			'show_opdata' => 0
		], $search_limit);

		// 3. Coleta de IDs
		$triggerIds = [];
		$eventIds = [];
		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerIds[] = $problem['objectid'];
				$eventIds[] = $problem['eventid'];
			}
		}

		// 4. Hidratação 1: Status Real do Evento (Problem API)
		$event_status_map = [];
		if (!empty($eventIds)) {
			$db_problems = API::Problem()->get([
				'eventids' => $eventIds,
				'output' => ['eventid', 'suppressed', 'acknowledged'],
				'preservekeys' => true
			]);
			
			foreach ($db_problems as $eid => $prob) {
				$event_status_map[$eid] = [
					'sup' => (int)$prob['suppressed'],
					'ack' => (int)$prob['acknowledged']
				];
			}
		}

		// 5. Hidratação 2: Dados do Host (Trigger API)
		$trigger_info_map = [];
		if (!empty($triggerIds)) {
			$db_triggers = API::Trigger()->get([
				'triggerids' => $triggerIds,
				'output' => ['triggerid'],
				'selectHosts' => ['hostid', 'name', 'maintenance_status'],
				'preservekeys' => true
			]);

			foreach ($db_triggers as $tid => $trig) {
				$host_data = ['id' => 0, 'name' => _('Unknown host'), 'maintenance' => 0];
				if (!empty($trig['hosts'])) {
					$first_host = reset($trig['hosts']);
					$host_data = [
						'id' => $first_host['hostid'],
						'name' => $first_host['name'],
						'maintenance' => (int)$first_host['maintenance_status']
					];
				}
				$trigger_info_map[$tid] = ['host' => $host_data];
			}
		}

		// 6. Processamento Final
		$alarm_counts = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
		$detailed_alarms = [];
		$total_alarms = 0;
		$highest_severity = -1;

		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$eventid = $problem['eventid'];
				$triggerid = $problem['objectid'] ?? 0;
				
				// Status Seguro
				$status_info = $event_status_map[$eventid] ?? ['sup' => 0, 'ack' => 0];
				$p_sup = $status_info['sup'];
				$p_ack = $status_info['ack'];

				// --- FILTRO ONLY SUPPRESSED ---
				if ($show_suppressed_only == 1 && $p_sup == 0) {
					continue;
				}
				// ------------------------------

				if (!isset($trigger_info_map[$triggerid])) continue;
				$info = $trigger_info_map[$triggerid];
				$host_info = $info['host'];

				// Filtros Manuais
				if (in_array($host_info['id'], $exclude_hosts)) continue;
				if ($exclude_maintenance == 1 && $host_info['maintenance'] == 1) continue;

				// Contabiliza
				$severity = (int)($problem['severity'] ?? 0);
				$alarm_counts[$severity]++;
				$total_alarms++;

				if ($severity > $highest_severity) {
					$highest_severity = $severity;
				}

				$detailed_alarms[] = [
					'eventid' => $eventid,
					'triggerid' => $triggerid,
					'description' => $problem['name'],
					'severity' => $severity,
					'severity_name' => CSeverityHelper::getName($severity),
					'host_name' => $host_info['name'],
					'clock' => $problem['clock'],
					'acknowledged' => $p_ack,
					'suppressed' => $p_sup // Passa para a View
				];
			}
		}

		usort($detailed_alarms, function($a, $b) {
			if ($a['severity'] === $b['severity']) {
				return $b['clock'] - $a['clock'];
			}
			return $b['severity'] - $a['severity'];
		});

		// 7. Nome do Grupo
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
