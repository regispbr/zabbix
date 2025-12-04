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
use Modules\AlarmWidget\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$groupids = $this->fields_values['groupids'] ?? [];
		$hostids = $this->fields_values['hostids'] ?? [];
		$exclude_hostids = $this->fields_values['exclude_hostids'] ?? [];
		$severities = $this->fields_values['severities'] ?? [];
		$exclude_maintenance = $this->fields_values['exclude_maintenance'] ?? 0;
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
        	$tags = $this->fields_values['tags'] ?? [];
		$problem_status = $this->fields_values['problem_status'] ?? WidgetForm::PROBLEM_STATUS_PROBLEM;
		$show_ack = $this->fields_values['show_ack'] ?? 0;
		$show_lines = $this->fields_values['show_lines'] ?? 25;
		$show_suppressed = $this->fields_values['show_suppressed'] ?? 0;
		$sort_by_int = (int)($this->fields_values['sort_by'] ?? WidgetForm::SORT_BY_TIME);

		// Build show_columns array
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
		
		// --- MUDANÇA: LÓGICA DE CLASSIFICAÇÃO (SORT) ---
		// Converte o INT do formulário para o TEXTO
		$sort_by_map = [
			WidgetForm::SORT_BY_TIME => 'eventid',
			WidgetForm::SORT_BY_SEVERITY => 'severity',
			WidgetForm::SORT_BY_HOST => 'host'
		];
		$sort_by = $sort_by_map[$sort_by_int] ?? 'eventid';

		// A API *SEMPRE* VAI USAR 'eventid' (Time) para evitar o crash.
		// A classificação por 'severity' e 'host' será feita no PHP (abaixo).
		// --- FIM DA MUDANÇA ---

		// Prepare options for API call
		$options = [
			'output' => ['eventid', 'objectid', 'clock', 'ns', 'name', 'severity', 'r_eventid', 'acknowledged', 'suppressed'],
			'selectAcknowledges' => ['acknowledgeid', 'clock', 'message', 'action', 'userid'],
			'selectTags' => ['tag', 'value'],
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'sortfield' => ['eventid'], // <-- CORRIGIDO: SEMPRE 'eventid'
			'sortorder' => [ZBX_SORT_DOWN],
			'limit' => $show_lines,
			'filter' => [] 
		];
		if (!empty($tags)) {
            	$options['evaltype'] = $evaltype;
            	$options['tags'] = $tags;
        	}
		if (!empty($groupids)) $options['groupids'] = $groupids;
		if (!empty($hostids)) $options['hostids'] = $hostids;
		if (!empty($severities)) $options['severities'] = $severities;

		if ($show_ack == 1) $options['acknowledged'] = false;
		elseif ($show_ack == 2) $options['acknowledged'] = true;

		if ($problem_status == WidgetForm::PROBLEM_STATUS_PROBLEM) $options['filter']['r_eventid'] = 0;
		elseif ($problem_status == WidgetForm::PROBLEM_STATUS_RESOLVED) $options['filter']['r_eventid'] = ['> 0'];

		if ($show_suppressed == 0) $options['suppressed'] = false;
		
		if (empty($options['filter'])) unset($options['filter']);

		$problems = API::Problem()->get($options);
		
		if (!is_array($problems)) $problems = [];

		$host_cache = [];
		if (!empty($problems)) {
			$trigger_ids = array_unique(array_column($problems, 'objectid'));
			
			if (!empty($trigger_ids)) {
				$triggers = API::Trigger()->get([
					'output' => ['triggerid'],
					'selectHosts' => ['hostid', 'name', 'maintenance_status'],
					'triggerids' => $trigger_ids,
					'preservekeys' => true
				]);
				
				if (is_array($triggers)) {
					foreach ($triggers as $trigger_id => $trigger) {
						if (!empty($trigger['hosts'])) $host_cache[$trigger_id] = $trigger['hosts'][0];
					}
				}
			}
		}

		if (!empty($exclude_hostids) && !empty($problems)) {
			$problems = array_filter($problems, function($problem) use ($host_cache, $exclude_hostids) {
				if (isset($host_cache[$problem['objectid']])) {
					return !in_array($host_cache[$problem['objectid']]['hostid'], $exclude_hostids);
				}
				return true;
			});
		}

		if ($exclude_maintenance && !empty($problems)) {
			$problems = array_filter($problems, function($problem) use ($host_cache) {
				if (isset($host_cache[$problem['objectid']])) {
					return $host_cache[$problem['objectid']]['maintenance_status'] != HOST_MAINTENANCE_STATUS_ON;
				}
				return true;
			});
		}

		if (!empty($problems)) {
			foreach ($problems as &$problem) {
				$age_seconds = time() - $problem['clock'];
				$problem['age_seconds'] = $age_seconds; // Adiciona para o sort
				$problem['age'] = $this->formatAge($age_seconds);
				$problem['time'] = date('d M Y H:i:s', $problem['clock']);
				$problem['status'] = ($problem['r_eventid'] != 0) ? 'RESOLVED' : 'PROBLEM';
				
				if (isset($host_cache[$problem['objectid']])) {
					$problem['hostname'] = $host_cache[$problem['objectid']]['name'];
					$problem['hostid'] = $host_cache[$problem['objectid']]['hostid'];
				} else {
					$problem['hostname'] = 'Unknown';
					$problem['hostid'] = 0;
				}
				
				$problem['ack_count'] = is_array($problem['acknowledges']) ? count($problem['acknowledges']) : 0;
				
				$problem['operational_data'] = '';
				if (!empty($problem['objectid'])) {
					$triggers = API::Trigger()->get([
						'output' => ['description', 'opdata'],
						'triggerids' => $problem['objectid'],
						'expandDescription' => true,
						'expandData' => true
					]);
					if (is_array($triggers) && !empty($triggers)) {
						$problem['operational_data'] = $triggers[0]['opdata'] ?? '';
					}
				}
			}
			unset($problem);
		}

		// --- MUDANÇA: CLASSIFICAÇÃO (SORT) FEITA NO PHP ---
		if (!empty($problems)) {
			if ($sort_by == 'host') {
				usort($problems, function($a, $b) {
					$host_cmp = strcmp($a['hostname'], $b['hostname']);
					if ($host_cmp !== 0) return $host_cmp; // 1. Host (ASC)
					if ($a['severity'] !== $b['severity']) return $b['severity'] - $a['severity']; // 2. Severity (DESC)
					return $b['eventid'] - $a['eventid']; // 3. Time (DESC)
				});
			}
			elseif ($sort_by == 'severity') {
				usort($problems, function($a, $b) {
					if ($a['severity'] !== $b['severity']) return $b['severity'] - $a['severity']; // 1. Severity (DESC)
					return $b['eventid'] - $a['eventid']; // 2. Time (DESC)
				});
			}
			// Se for 'eventid', já vem classificado pela API.
		}
		// --- FIM DA MUDANÇA ---

		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'problems' => array_values($problems),
			'show_columns' => $show_columns,
			'refresh_interval' => $this->fields_values['refresh_interval'] ?? 60,
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
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
