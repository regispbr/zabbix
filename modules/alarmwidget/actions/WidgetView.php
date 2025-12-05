<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
** ... (Licença) ...
**/

namespace Modules\AlarmWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use CScreenProblem;
use CSettingsHelper;
use Modules\AlarmWidget\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// Constantes de fallback
		if (!defined('ZBX_PROBLEM_SUPPRESSED')) define('ZBX_PROBLEM_SUPPRESSED', 1);
		if (!defined('ZBX_ACK_STATUS_ALL')) define('ZBX_ACK_STATUS_ALL', 1);
		if (!defined('ZBX_ACK_STATUS_UNACK')) define('ZBX_ACK_STATUS_UNACK', 2);
		if (!defined('TRIGGERS_OPTION_RECENT_PROBLEM')) define('TRIGGERS_OPTION_RECENT_PROBLEM', 1);
		if (!defined('TRIGGERS_OPTION_IN_PROBLEM')) define('TRIGGERS_OPTION_IN_PROBLEM', 2); // Caso use status 'Problem'

		// 1. Coleta de Filtros
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
		$sort_by_int = (int)($this->fields_values['sort_by'] ?? WidgetForm::SORT_BY_TIME);

		// Configuração de Colunas (Visual)
		$show_columns = [];
		if (!empty($this->fields_values['show_column_host'])) $show_columns[] = 'host';
		if (!empty($this->fields_values['show_column_severity'])) $show_columns[] = 'severity';
		if (!empty($this->fields_values['show_column_status'])) $show_columns[] = 'status';
		if (!empty($this->fields_values['show_column_problem'])) $show_columns[] = 'problem';
		if (!empty($this->fields_values['show_column_operational_data'])) $show_columns[] = 'operational_data';
		if (!empty($this->fields_values['show_column_ack'])) $show_columns[] = 'ack';
		if (!empty($this->fields_values['show_column_age'])) $show_columns[] = 'age';
		if (!empty($this->fields_values['show_column_time'])) $show_columns[] = 'time';
		if (empty($show_columns)) {
			$show_columns = ['host', 'severity', 'status', 'problem', 'operational_data', 'ack', 'age', 'time'];
		}

		// 2. Mapeamento de Filtros para Engine Nativa (CScreenProblem)
		
		// Status do Problema (Recente vs Histórico vs Apenas Problema)
		// O widget nativo usa TRIGGERS_OPTION_RECENT_PROBLEM por padrão
		$show_mode = TRIGGERS_OPTION_RECENT_PROBLEM; 
		if ($problem_status_input == WidgetForm::PROBLEM_STATUS_RESOLVED) {
			// Se quer resolvidos, normalmente é histórico, mas a engine nativa trata diferente.
			// Vamos manter Recent Problem e filtrar depois se necessário, ou usar 'All' (History).
			// Para simplificar e bater com o nativo, vamos usar Recent Problem.
		}

		// Status de Ack
		$ack_status = ZBX_ACK_STATUS_ALL;
		if ($show_ack == 1) $ack_status = ZBX_ACK_STATUS_UNACK;
		elseif ($show_ack == 2) $ack_status = ZBX_ACK_STATUS_ACK; // Se tiver essa constante customizada

		$search_limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT);

		// 3. Chamada à Engine Nativa (Encontra os problemas)
		$data = CScreenProblem::getData([
			'show' => $show_mode,
			'groupids' => $groupids,
			'hostids' => $hostids,
			'name' => '', 
			'severities' => $severities,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'show_symptoms' => false,
			'show_suppressed' => ($show_suppressed == 1),
			'acknowledgement_status' => $ack_status,
			'show_opdata' => 0 // Buscaremos opdata depois se precisar
		], $search_limit);

		// 4. Hidratação Robusta (API de Trigger)
		$triggerIds = [];
		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerIds[] = $problem['objectid'];
			}
		}

		$trigger_info_map = [];
		if (!empty($triggerIds)) {
			$db_triggers = API::Trigger()->get([
				'triggerids' => $triggerIds,
				'output' => ['triggerid', 'opdata'], // Inclui opdata
				'selectHosts' => ['hostid', 'name', 'maintenance_status'],
				'selectLastEvent' => ['acknowledged', 'suppressed'],
				'preservekeys' => true,
				'expandData' => true // Expande macros no opdata
			]);

			foreach ($db_triggers as $tid => $trig) {
				// Host
				$host_data = ['id' => 0, 'name' => _('Unknown host'), 'maintenance' => 0];
				if (!empty($trig['hosts'])) {
					$first_host = reset($trig['hosts']);
					$host_data = [
						'id' => $first_host['hostid'],
						'name' => $first_host['name'],
						'maintenance' => (int)$first_host['maintenance_status']
					];
				}

				// Ack/Sup
				$ack_status_trigger = 0;
				if (!empty($trig['lastEvent'])) {
					$ack_status_trigger = (int)($trig['lastEvent']['acknowledged'] ?? 0);
				}

				// Opdata
				$opdata = $trig['opdata'] ?? '';

				$trigger_info_map[$tid] = [
					'host' => $host_data,
					'ack' => $ack_status_trigger,
					'opdata' => $opdata
				];
			}
		}

		// 5. Construção do Array Final de Problemas
		$problems_final = [];

		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerid = $problem['objectid'] ?? 0;
				
				if (!isset($trigger_info_map[$triggerid])) continue;

				$info = $trigger_info_map[$triggerid];
				$host_info = $info['host'];

				// --- FILTROS MANUAIS ---
				// 1. Exclude Hosts
				if (in_array($host_info['id'], $exclude_hostids)) continue;

				// 2. Maintenance
				if ($exclude_maintenance == 1 && $host_info['maintenance'] == 1) continue;

				// Monta o objeto do problema
				$age_seconds = time() - $problem['clock'];
				
				$problems_final[] = [
					'eventid' => $problem['eventid'],
					'objectid' => $triggerid,
					'name' => $problem['name'],
					'severity' => (int)$problem['severity'],
					'status' => ($problem['r_eventid'] != 0) ? 'RESOLVED' : 'PROBLEM',
					'clock' => $problem['clock'],
					'time' => date('d M Y H:i:s', $problem['clock']),
					'age' => $this->formatAge($age_seconds),
					'age_seconds' => $age_seconds,
					
					'hostname' => $host_info['name'],
					'hostid' => $host_info['id'],
					
					'ack_count' => $info['ack'], // 1 ou 0
					'operational_data' => $info['opdata']
				];
			}
		}

		// 6. Ordenação (Sort)
		// Mapeia o INT do form para string
		$sort_by_map = [
			WidgetForm::SORT_BY_TIME => 'clock',
			WidgetForm::SORT_BY_SEVERITY => 'severity',
			WidgetForm::SORT_BY_HOST => 'hostname'
		];
		$sort_key = $sort_by_map[$sort_by_int] ?? 'clock';

		usort($problems_final, function($a, $b) use ($sort_key) {
			if ($sort_key == 'hostname') {
				// Host ASC
				$cmp = strcmp($a['hostname'], $b['hostname']);
				if ($cmp !== 0) return $cmp;
				// Desempate: Severity DESC -> Time DESC
				if ($a['severity'] !== $b['severity']) return $b['severity'] - $a['severity'];
				return $b['clock'] - $a['clock'];
			} 
			elseif ($sort_key == 'severity') {
				// Severity DESC
				if ($a['severity'] !== $b['severity']) return $b['severity'] - $a['severity'];
				// Desempate: Time DESC
				return $b['clock'] - $a['clock'];
			} 
			else { // Time (clock)
				// Time DESC
				return $b['clock'] - $a['clock'];
			}
		});

		// Aplica limite de linhas
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
