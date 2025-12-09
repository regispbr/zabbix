<?php declare(strict_types = 0);

namespace Modules\AlarmWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use CScreenProblem;
use CSettingsHelper;
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
		// show_opdata = 0 pois faremos o processamento manual completo
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
			'show_opdata' => 0 
		], $search_limit);

		$triggerIds = [];
		$eventIds = [];
		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerIds[] = $problem['objectid'];
				$eventIds[] = $problem['eventid'];
			}
		}

		// 4. STATUS REAL
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

		// 5. RESOLUÇÃO MANUAL DE OPDATA (LÓGICA RELACIONAL)
		$trigger_info_map = [];
		
		if (!empty($triggerIds)) {
			// A) Buscar Triggers e suas funções
			$db_triggers = API::Trigger()->get([
				'triggerids' => $triggerIds,
				'output' => ['triggerid', 'opdata', 'expression', 'description'],
				'selectFunctions' => ['function', 'parameter', 'itemid'],
				'selectHosts' => ['hostid', 'name', 'maintenance_status'],
				'preservekeys' => true
			]);

			// B) Coleta Item IDs das funções
			$itemIds = [];
			foreach ($db_triggers as $trig) {
				if (!empty($trig['functions'])) {
					foreach ($trig['functions'] as $func) {
						$itemIds[] = $func['itemid'];
					}
				}
			}

			// C) Busca Itens + ValueMapID
			$db_items = [];
			$valuemapIds = [];
			if (!empty($itemIds)) {
				$db_items = API::Item()->get([
					'itemids' => $itemIds,
					'output' => ['itemid', 'name', 'lastvalue', 'units', 'value_type', 'valuemapid'],
					'preservekeys' => true
				]);
				
				foreach ($db_items as $item) {
					if ($item['valuemapid'] != 0) {
						$valuemapIds[$item['valuemapid']] = true;
					}
				}
			}

			// D) Busca ValueMaps Completos (O Segredo do "Down (1)")
			$db_valuemaps = [];
			if (!empty($valuemapIds)) {
				try {
					$valuemaps_raw = API::ValueMap()->get([
						'valuemapids' => array_keys($valuemapIds),
						'output' => ['valuemapid', 'name'],
						'selectMappings' => ['value', 'newvalue'], // Traz os mapeamentos reais
						'preservekeys' => true
					]);
					
					foreach ($valuemaps_raw as $id => $vmap) {
						$db_valuemaps[$id] = $vmap['mappings'] ?? [];
					}
				} catch (\Exception $e) {
					$db_valuemaps = [];
				}
			}

			// E) Montagem Inteligente
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

				$resolved_opdata = $trig['opdata'];
				
				// --- CENÁRIO 1: OpData Configurado na Trigger ---
				if (!empty($resolved_opdata) && !empty($trig['functions'])) {
					$resolved_opdata = preg_replace_callback('/\{ITEM\.(?:LAST)?VALUE(\d*)\}/', 
						function($matches) use ($trig, $db_items, $db_valuemaps) {
							$index = (int)($matches[1] === '' ? 1 : $matches[1]);
							$func_index = $index - 1; 
							
							if (isset($trig['functions'][$func_index])) {
								$itemid = $trig['functions'][$func_index]['itemid'];
								if (isset($db_items[$itemid])) {
									$item = $db_items[$itemid];
									
									// Injeta o dicionário de mapeamento no item para a função formatar
									if ($item['valuemapid'] != 0 && isset($db_valuemaps[$item['valuemapid']])) {
										$item['valuemap'] = $db_valuemaps[$item['valuemapid']];
									} else {
										$item['valuemap'] = [];
									}

									// Função nativa do Zabbix que gera "Down (1)" ou "100 GB"
									return formatHistoryValue($item['lastvalue'], $item);
								}
							}
							return $matches[0];
						}, 
						$resolved_opdata
					);
				}
				// --- CENÁRIO 2: OpData Vazio (Fallback para lista de itens) ---
				else if (empty($resolved_opdata) && !empty($trig['functions'])) {
					$opdata_parts = [];
					foreach ($trig['functions'] as $func) {
						$itemid = $func['itemid'];
						if (isset($db_items[$itemid])) {
							$item = $db_items[$itemid];
							
							// Injeta o dicionário de mapeamento
							if ($item['valuemapid'] != 0 && isset($db_valuemaps[$item['valuemapid']])) {
								$item['valuemap'] = $db_valuemaps[$item['valuemapid']];
							} else {
								$item['valuemap'] = [];
							}
							
							$formatted_val = formatHistoryValue($item['lastvalue'], $item);
							// Se a trigger só tem um item, mostra só o valor (ex: "150ms")
							// Se tem vários, mostra "Nome: Valor" para não confundir
							if (count($trig['functions']) == 1) {
								$opdata_parts[] = $formatted_val;
							} else {
								$opdata_parts[] = $item['name'] . ': ' . $formatted_val;
							}
						}
					}
					// Remove duplicatas (ex: se trigger usa avg e last do mesmo item) e junta
					if (!empty($opdata_parts)) {
						$opdata_parts = array_unique($opdata_parts);
						$resolved_opdata = implode(', ', $opdata_parts);
					}
				}

				$trigger_info_map[$tid] = [
					'host' => $host_data,
					'opdata' => $resolved_opdata
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

				$opdata_final = $info['opdata']; 

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

		// 7. ORDENAÇÃO
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
