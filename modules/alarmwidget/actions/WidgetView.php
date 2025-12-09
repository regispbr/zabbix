<?php declare(strict_types = 0);

namespace Modules\AlarmWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use CScreenProblem;
use CSettingsHelper;
use CMacrosResolverHelper;
use Modules\AlarmWidget\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// --- CONFIGURAÇÕES ---
		if (!defined('ZBX_PROBLEM_SUPPRESSED')) define('ZBX_PROBLEM_SUPPRESSED', 1);
		if (!defined('ZBX_ACK_STATUS_ALL')) define('ZBX_ACK_STATUS_ALL', 1);
		if (!defined('ZBX_ACK_STATUS_UNACK')) define('ZBX_ACK_STATUS_UNACK', 2);
		if (!defined('TRIGGERS_OPTION_RECENT_PROBLEM')) define('TRIGGERS_OPTION_RECENT_PROBLEM', 1);
		if (!defined('TRIGGERS_OPTION_ALL')) define('TRIGGERS_OPTION_ALL', 2);

		// 1. INPUTS
		$groupids = $this->fields_values['groupids'] ?? [];
		$hostids = $this->fields_values['hostids'] ?? [];
		$exclude_hostids = $this->fields_values['exclude_hostids'] ?? [];
		$severities = $this->fields_values['severities'] ?? [];
		$exclude_maintenance = $this->fields_values['exclude_maintenance'] ?? 0;
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];
		$problem_status_input = $this->fields_values['problem_status'] ?? WidgetForm::PROBLEM_STATUS_PROBLEM;
		$show_ack = $this->fields_values['show_ack'] ?? 0;
		$show_lines = $this->fields_values['show_lines'] ?? 25;
		$show_suppressed = $this->fields_values['show_suppressed'] ?? 0;
		$show_suppressed_only = $this->fields_values['show_suppressed_only'] ?? 0;
		$engine_show_suppressed = ($show_suppressed == 1 || $show_suppressed_only == 1);
		$sort_by_int = (int)($this->fields_values['sort_by'] ?? WidgetForm::SORT_BY_TIME);
		$show_columns = ['host', 'severity', 'status', 'problem', 'operational_data', 'ack', 'age', 'time'];

		// 2. FILTROS
		$show_mode = TRIGGERS_OPTION_RECENT_PROBLEM; 
		$ack_status = ZBX_ACK_STATUS_ALL;
		if ($show_ack == 1) $ack_status = ZBX_ACK_STATUS_UNACK;
		elseif ($show_ack == 2) $ack_status = ZBX_ACK_STATUS_ACK;
		$search_limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT);

		// 3. ENGINE CALL
		// show_opdata => 2 para tentar forçar a engine a trazer algo
		$data = CScreenProblem::getData([
			'show' => $show_mode,
			'groupids' => $groupids,
			'hostids' => $hostids,
			'name' => '', 
			'severities' => $severities,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'show_symptoms' => false,
			'show_suppressed' => $engine_show_suppressed,
			'acknowledgement_status' => $ack_status,
			'show_opdata' => 2 
		], $search_limit);

		$triggerIds = [];
		$eventIds = [];
		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerIds[] = $problem['objectid'];
				$eventIds[] = $problem['eventid'];
			}
		}

		// 4. STATUS REAL (Problem API)
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

		// 5. TRIGGER + OPDATA RESOLVER
		$trigger_info_map = [];
		$resolved_opdata = [];

		if (!empty($triggerIds)) {
			// Busca COMPLETA da trigger
			$db_triggers = API::Trigger()->get([
				'triggerids' => $triggerIds,
				'output' => API_OUTPUT_EXTEND, // Pega TUDO
				'selectHosts' => ['hostid', 'name', 'maintenance_status'],
				'selectFunctions' => 'extend', // Necessário para macros
				'preservekeys' => true
			]);

			// === PREPARAÇÃO MANUAL DO ARRAY PARA O RESOLVER ===
			// Não confiamos na estrutura que veio do banco. Recriamos.
			$clean_triggers = [];
			
			foreach ($data['problems'] as $p) {
				$tid = $p['objectid'];
				
				if (!isset($db_triggers[$tid])) {
					error_log("DEBUG ALARM: Trigger ID $tid não encontrada no DB_TRIGGERS.");
					continue;
				}
				
				$raw_trig = $db_triggers[$tid];
				
				// Se já processamos essa trigger, pula
				if (isset($clean_triggers[$tid])) continue;

				// FORÇA AS CHAVES E TIPOS CORRETOS
				// O helper do Zabbix espera um array onde as chaves sejam: triggerid, expression, etc.
				$clean_triggers[$tid] = [
					'triggerid' => (string)$tid, // Força string e garante a chave
					'expression' => isset($raw_trig['expression']) ? (string)$raw_trig['expression'] : '',
					'recovery_expression' => isset($raw_trig['recovery_expression']) ? (string)$raw_trig['recovery_expression'] : '',
					'opdata' => isset($raw_trig['opdata']) ? (string)$raw_trig['opdata'] : '',
					'priority' => $raw_trig['priority'] ?? '0',
					'value' => $raw_trig['value'] ?? '0'
					// Adicione outros campos se necessário, mas estes são os que OpData costuma usar
				];
				
				// Copia funções se existirem (necessário para macros de item)
				if (isset($raw_trig['functions'])) {
					$clean_triggers[$tid]['functions'] = $raw_trig['functions'];
				}
			}

			// Prepara Eventos
			$problems_for_macros = [];
			foreach ($data['problems'] as $p) {
				if (isset($clean_triggers[$p['objectid']])) {
					$problems_for_macros[$p['eventid']] = $p;
				}
			}

			// --- DEBUG LOGGING ---
			if (!empty($clean_triggers)) {
				$first_key = array_key_first($clean_triggers);
				$first = $clean_triggers[$first_key];
				error_log("DEBUG ALARM CHECK: Enviando " . count($clean_triggers) . " triggers para resolver.");
				error_log("DEBUG ALARM CHECK: Exemplo Trigger [$first_key]: Keys=" . implode(',', array_keys($first)));
				error_log("DEBUG ALARM CHECK: Exemplo Trigger ID Value: '" . $first['triggerid'] . "'");
				error_log("DEBUG ALARM CHECK: Exemplo Expression Type: " . gettype($first['expression']));
			} else {
				error_log("DEBUG ALARM CHECK: Nenhuma trigger limpa para resolver.");
			}
			// ---------------------

			try {
				if (!empty($clean_triggers) && !empty($problems_for_macros)) {
					// Chama o helper
					$resolved_opdata = CMacrosResolverHelper::resolveTriggerOpdata(
						$clean_triggers,
						[
							'events' => $problems_for_macros,
							'html' => false
						]
					);
					error_log("DEBUG ALARM SUCCESS: OpData resolvido com sucesso.");
				}
			} catch (\Throwable $e) {
				error_log("DEBUG ALARM CRASH: Erro Fatal no CMacrosResolverHelper: " . $e->getMessage());
				error_log("DEBUG ALARM CRASH TRACE: " . $e->getTraceAsString());
				$resolved_opdata = [];
			}

			// Mapa de Hosts para exibição (usando db_triggers original que tem os hosts)
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
				$trigger_info_map[$tid] = [
					'host' => $host_data,
					'raw_opdata' => $trig['opdata'] ?? ''
				];
			}
		}

		// 6. CONSTRUÇÃO FINAL
		$problems_final = [];

		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$eventid = $problem['eventid'];
				$triggerid = $problem['objectid'] ?? 0;

				$status_info = $event_status_map[$eventid] ?? ['sup' => 0, 'ack' => 0];
				$p_sup = $status_info['sup'];
				$p_ack = $status_info['ack'];

				if ($show_suppressed_only == 1 && $p_sup == 0) continue; 

				if (!isset($trigger_info_map[$triggerid])) continue;
				$info = $trigger_info_map[$triggerid];
				$host_info = $info['host'];

				if (in_array($host_info['id'], $exclude_hostids)) continue;
				if ($exclude_maintenance == 1 && $host_info['maintenance'] == 1) continue;

				$r_eventid = $problem['r_eventid'] ?? 0;
				$clock = $problem['clock'] ?? time();
				$severity = (int)($problem['severity'] ?? 0);
				$name = $problem['name'] ?? _('Unknown problem');

				if ($problem_status_input == WidgetForm::PROBLEM_STATUS_RESOLVED) {
					if ($r_eventid == 0) continue; 
				} elseif ($problem_status_input == WidgetForm::PROBLEM_STATUS_PROBLEM) {
					if ($r_eventid != 0) continue; 
				}

				// OpData Final: Prioridade para resolvido, fallback para raw, fallback para string vazia
				$opdata_final = '';
				if (isset($resolved_opdata[$eventid])) {
					$opdata_final = $resolved_opdata[$eventid];
				} else {
					$opdata_final = $info['raw_opdata'];
				}

				$age_seconds = time() - $clock;
				
				$problems_final[] = [
					'eventid' => $eventid,
					'objectid' => $triggerid,
					'name' => $name,
					'severity' => $severity,
					'status' => ($r_eventid != 0) ? 'RESOLVED' : 'PROBLEM',
					'clock' => $clock,
					'time' => date('d M Y H:i:s', $clock),
					'age' => $this->formatAge($age_seconds),
					'age_seconds' => $age_seconds,
					'hostname' => $host_info['name'],
					'hostid' => $host_info['id'],
					'ack_count' => $p_ack,
					'suppressed' => $p_sup, 
					'operational_data' => $opdata_final
				];
			}
		}

		$sort_by_map = [
			WidgetForm::SORT_BY_TIME => 'clock',
			WidgetForm::SORT_BY_SEVERITY => 'severity',
			WidgetForm::SORT_BY_HOST => 'hostname'
		];
		$sort_key = $sort_by_map[$sort_by_int] ?? 'clock';

		usort($problems_final, function($a, $b) use ($sort_key) {
			if ($sort_key == 'hostname') {
				$cmp = strcmp($a['hostname'], $b['hostname']);
				if ($cmp !== 0) return $cmp;
				if ($a['severity'] !== $b['severity']) return $b['severity'] - $a['severity'];
				return $b['clock'] - $a['clock'];
			} 
			elseif ($sort_key == 'severity') {
				if ($a['severity'] !== $b['severity']) return $b['severity'] - $a['severity'];
				return $b['clock'] - $a['clock'];
			} 
			else { 
				return $b['clock'] - $a['clock'];
			}
		});

		$problems_final = array_slice($problems_final, 0, $show_lines);

		$response_data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'problems' => $problems_final,
			'show_columns' => $show_columns,
			'refresh_interval' => $this->fields_values['refresh_interval'] ?? 60,
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($response_data));
	}

	private function formatAge(int $seconds): string {
		if ($seconds < 60) return $seconds . 's';
		elseif ($seconds < 3600) return floor($seconds / 60) . 'm';
		elseif ($seconds < 86400) return floor($seconds / 3600) . 'h';
		elseif ($seconds < 2592000) return floor($seconds / 86400) . 'd';
		elseif ($seconds < 31536000) return floor($seconds / 2592000) . ' months';
		else return floor($seconds / 31536000) . ' years';
	}
}
