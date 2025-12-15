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

	// Constantes Locais (Igual ao AlarmWidget para consistência)
	private const PROBLEM_STATUS_ALL = 0;
	private const PROBLEM_STATUS_PROBLEM = 1;
	private const PROBLEM_STATUS_RESOLVED = 2;

	protected function doAction(): void {
		if (!defined('ZBX_PROBLEM_SUPPRESSED')) define('ZBX_PROBLEM_SUPPRESSED', 1);
		if (!defined('ZBX_ACK_STATUS_ALL')) define('ZBX_ACK_STATUS_ALL', 1);
		if (!defined('ZBX_ACK_STATUS_UNACK')) define('ZBX_ACK_STATUS_UNACK', 2);
		if (!defined('TRIGGERS_OPTION_RECENT_PROBLEM')) define('TRIGGERS_OPTION_RECENT_PROBLEM', 1);
		if (!defined('TRIGGERS_OPTION_IN_PROBLEM')) define('TRIGGERS_OPTION_IN_PROBLEM', 0);

		// 1. INPUTS
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$show_acknowledged = (int)($this->fields_values['show_acknowledged'] ?? 1);
		$exclude_maintenance = (int)($this->fields_values['exclude_maintenance'] ?? 0);
		
		$show_suppressed = (int)($this->fields_values['show_suppressed'] ?? 0);
		$show_suppressed_only = (int)($this->fields_values['show_suppressed_only'] ?? 0);
		$engine_show_suppressed = ($show_suppressed == 1 || $show_suppressed_only == 1);
		
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];
		$severities = $this->fields_values['severities'] ?? []; 

		// --- NOVO INPUT ---
		$problem_status_input = (int)($this->fields_values['problem_status'] ?? self::PROBLEM_STATUS_PROBLEM);
		// ------------------

		// Define o modo da engine baseado na escolha
		if ($problem_status_input == self::PROBLEM_STATUS_PROBLEM) {
			// Se quer só problemas, usa o modo padrão (mais leve)
			$show_mode = TRIGGERS_OPTION_IN_PROBLEM; // Ou RECENT_PROBLEM filtrado, mas IN_PROBLEM é mais direto
		} else {
			// Se quer All ou Resolved, PRECISA ser RECENT_PROBLEM para trazer o histórico
			$show_mode = TRIGGERS_OPTION_RECENT_PROBLEM;
		}

		$ack_status = ($show_acknowledged == 1) ? ZBX_ACK_STATUS_ALL : ZBX_ACK_STATUS_UNACK;
		$search_limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT);

		// 2. ENGINE CALL
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
			'show_opdata' => 2 // Traz dados completos
		], $search_limit);

		// 3. IDs
		$triggerIds = [];
		$eventIds = [];
		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerIds[] = $problem['objectid'];
				$eventIds[] = $problem['eventid'];
			}
		}

		// 4. STATUS REAL + RECUPERAÇÃO (Igual AlarmWidget)
		$problem_details_map = [];
		if (!empty($eventIds)) {
			// Se o modo exige histórico, pedimos 'recent'
			$api_recent = ($show_mode == TRIGGERS_OPTION_RECENT_PROBLEM);

			$db_problems = API::Problem()->get([
				'eventids' => $eventIds,
				'output' => ['eventid', 'r_eventid', 'r_clock', 'suppressed', 'acknowledged'],
				'recent' => $api_recent,
				'preservekeys' => true
			]);
			
			foreach ($db_problems as $eid => $prob) {
				$problem_details_map[$eid] = [
					'sup' => (int)$prob['suppressed'],
					'ack' => (int)$prob['acknowledged'],
					'r_eventid' => (int)$prob['r_eventid'],
					'r_clock' => (int)$prob['r_clock']
				];
			}
		}

		// 5. Host Info
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
				
				$details = $problem_details_map[$eventid] ?? [
					'sup' => 0, 'ack' => 0, 'r_eventid' => 0, 'r_clock' => 0
				];

				$p_sup = $details['sup'];
				$p_ack = $details['ack'];
				$r_eventid = $details['r_eventid'];
				$r_clock = $details['r_clock'];

				if ($show_suppressed_only == 1 && $p_sup == 0) continue;

				if (!isset($trigger_info_map[$triggerid])) continue;
				$info = $trigger_info_map[$triggerid];
				$host_info = $info['host'];

				if (in_array($host_info['id'], $exclude_hosts)) continue;
				if ($exclude_maintenance == 1 && $host_info['maintenance'] == 1) continue;

				// Status
				$is_resolved = ($r_eventid != 0) || ($r_clock != 0);

				// --- FILTRO FINAL DO USUÁRIO ---
				if ($problem_status_input == self::PROBLEM_STATUS_RESOLVED) {
					if (!$is_resolved) continue; // Quer resolvidos, mas este é ativo
				} elseif ($problem_status_input == self::PROBLEM_STATUS_PROBLEM) {
					if ($is_resolved) continue; // Quer ativos, mas este é resolvido
				}
				// -------------------------------

				$severity = (int)($problem['severity'] ?? 0);

				// Contabiliza (apenas ativos contam para a "saúde" visual do azulejo?)
				// Decisão: Vamos contar tudo o que está na lista para o número bater.
				// MAS, a COR do azulejo deve refletir apenas problemas ATIVOS, senão um resolvido deixa o azulejo vermelho.
				
				$alarm_counts[$severity]++;
				$total_alarms++;

				// Só atualiza a cor do azulejo se for um problema ATIVO
				if (!$is_resolved && $severity > $highest_severity) {
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
					'r_clock' => $r_clock, // Passa data de resolução para o JS se quiser usar
					'acknowledged' => $p_ack,
					'suppressed' => $p_sup,
					'is_resolved' => $is_resolved, // Flag para o JS
					'status_text' => $is_resolved ? _('RESOLVED') : _('PROBLEM')
				];
			}
		}

		usort($detailed_alarms, function($a, $b) {
			// Ativos primeiro
			if ($a['is_resolved'] !== $b['is_resolved']) {
				return $a['is_resolved'] ? 1 : -1; 
			}
			if ($a['severity'] === $b['severity']) {
				return $b['clock'] - $a['clock'];
			}
			return $b['severity'] - $a['severity'];
		});

		// 7. View Data
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
