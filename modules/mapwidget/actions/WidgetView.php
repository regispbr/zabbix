<?php declare(strict_types = 0);

namespace Modules\MapWidget\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;
use Modules\MapWidget\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// 1. Configuração do Mapa
		$map_config = [
			'map_id' => $this->fields_values['map_id'] ?? null,
			'map_key' => $this->fields_values['map_key'] ?? null,
			'zoom' => floatval($this->fields_values['zoom'] ?? 3),
			'center_lat' => floatval($this->fields_values['center_lat'] ?? -16),
			'center_lng' => floatval($this->fields_values['center_lng'] ?? -52),
			'bearing' => floatval($this->fields_values['bearing'] ?? 0),
			'pitch' => floatval($this->fields_values['pitch'] ?? 0)
		];

		// 2. Filtros do Zabbix
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];

		$severity_filters = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => $this->fields_values['show_not_classified'] ?? 1,
			WidgetForm::SEVERITY_INFORMATION => $this->fields_values['show_information'] ?? 1,
			WidgetForm::SEVERITY_WARNING => $this->fields_values['show_warning'] ?? 1,
			WidgetForm::SEVERITY_AVERAGE => $this->fields_values['show_average'] ?? 1,
			WidgetForm::SEVERITY_HIGH => $this->fields_values['show_high'] ?? 1,
			WidgetForm::SEVERITY_DISASTER => $this->fields_values['show_disaster'] ?? 1,
			-1 => 1 // OK
		];

		$problem_filters = [
			'show_acknowledged' => $this->fields_values['show_acknowledged'] ?? 1,
			'show_suppressed' => $this->fields_values['show_suppressed'] ?? 0
		];

		// --- MUDANÇA AQUI: Lê o novo campo ---
		$exclude_maintenance = (bool)($this->fields_values['exclude_maintenance'] ?? 0);
		// --- FIM DA MUDANÇA ---

		// 3. Busca e processa os dados
		
		// Lógica para os PINS e POPUPS DE PIN
		$zabbix_data = $this->getZabbixData(
			$hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters,
			$evaltype, $tags,
			$exclude_maintenance // <-- Passa o novo filtro
		);
		
		// Lógica para o MODAL "ALL PROBLEMS"
		$detailed_problems = $this->getDetailedProblemList(
			$hostgroups, $hosts, $exclude_hosts, $severity_filters, $problem_filters,
			$evaltype, $tags,
			$exclude_maintenance // <-- Passa o novo filtro
		);

		// 4. Prepara os dados para enviar ao frontend
		$data = $map_config + [
			'name' => $this->getInput('name', $this->widget->getName()),
			'zabbix_hosts' => $zabbix_data, // Para os pins
			'detailed_problems' => $detailed_problems, // Para o modal
			'severity_colors' => [
				-1 => $this->getSeverityColor(-1),
				WidgetForm::SEVERITY_NOT_CLASSIFIED => $this->getSeverityColor(WidgetForm::SEVERITY_NOT_CLASSIFIED),
				WidgetForm::SEVERITY_INFORMATION => $this->getSeverityColor(WidgetForm::SEVERITY_INFORMATION),
				WidgetForm::SEVERITY_WARNING => $this->getSeverityColor(WidgetForm::SEVERITY_WARNING),
				WidgetForm::SEVERITY_AVERAGE => $this->getSeverityColor(WidgetForm::SEVERITY_AVERAGE),
				WidgetForm::SEVERITY_HIGH => $this->getSeverityColor(WidgetForm::SEVERITY_HIGH),
				WidgetForm::SEVERITY_DISASTER => $this->getSeverityColor(WidgetForm::SEVERITY_DISASTER)
			],
			'severity_names' => [ // Nomes para o JS
				-1 => _('OK'),
				WidgetForm::SEVERITY_NOT_CLASSIFIED => _('Not classified'),
				WidgetForm::SEVERITY_INFORMATION => _('Information'),
				WidgetForm::SEVERITY_WARNING => _('Warning'),
				WidgetForm::SEVERITY_AVERAGE => _('Average'),
				WidgetForm::SEVERITY_HIGH => _('High'),
				WidgetForm::SEVERITY_DISASTER => _('Disaster')
			],
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	/**
	 * FUNÇÃO 1: (MODIFICADA)
	 * Busca dados para os PINS e agora também os eventids.
	 */
	private function getZabbixData(
		array $hostgroups, array $hosts, array $exclude_hosts, array $severity_filters, array $problem_filters,
		int $evaltype, array $tags,
		bool $exclude_maintenance // <-- Novo parâmetro
	): array {
		$locations_to_map = [];

		if (empty($hostgroups) && empty($hosts)) {
			return $locations_to_map;
		}

		// === PASSO 1: Obter Hosts ===
		$params = [
			'output' => ['hostid', 'name', 'maintenance_status'], // <-- Pede 'maintenance_status'
			'selectInventory' => ['location_lat', 'location_lon'],
			'selectHostGroups' => ['groupid', 'name'],
			'monitored_hosts' => true,
			'preservekeys' => true,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'filter' => [
				'inventory_mode' => [HOST_INVENTORY_MANUAL, HOST_INVENTORY_AUTOMATIC]
			]
		];

		if (!empty($hosts)) {
			$params['hostids'] = $hosts;
		} elseif (!empty($hostgroups)) {
			$params['groupids'] = $hostgroups;
		}

		if (!empty($exclude_hosts) && !empty($params['hostids'])) {
			$params['hostids'] = array_diff($params['hostids'], $exclude_hosts);
		}
		
		try {
			$hosts_from_api = \API::Host()->get($params);
		}
		catch (\Exception $e) {
			return $locations_to_map;
		}

		// --- MUDANÇA AQUI: Filtra por manutenção ---
		if ($exclude_maintenance) {
			$hosts_from_api = array_filter($hosts_from_api, function ($host) {
				return $host['maintenance_status'] == 0; // 0 = no maintenance
			});
		}
		// --- FIM DA MUDANÇA ---

		if (!empty($exclude_hosts) && !empty($params['groupids'])) {
			$hosts_from_api = array_filter($hosts_from_api, function ($hostid) use ($exclude_hosts) {
				return !in_array($hostid, $exclude_hosts);
			}, ARRAY_FILTER_USE_KEY);
		}

		if (empty($hosts_from_api)) {
			return $locations_to_map;
		}
		
		$user_selected_groupids = $hostgroups;

		// === PASSO 2 & 3: Obter Triggers e Problemas ===
		$hostids = array_keys($hosts_from_api);
		$triggers = []; $problems_api = [];
		try {
			$triggers = \API::Trigger()->get([
				'output' => [], 'selectHosts' => ['hostid'], 'hostids' => $hostids,
				'filter' => ['value' => TRIGGER_VALUE_TRUE],
				'monitored' => true, 'skipDependent' => true, 'preservekeys' => true
			]);
			if (!empty($triggers)) {
				$problems_api = \API::Problem()->get([
					'output' => ['objectid', 'severity', 'acknowledged', 'suppressed', 'eventid', 'name'],
					'objectids' => array_keys($triggers),
					'symptom' => false, 'preservekeys' => true
				]);
			}
		}
		catch (\Exception $e) { /* Não faz nada, continua sem problemas */ }

		// === PASSO 4: Agrupar problemas por Host ===
		$problems_by_host = [];
		foreach ($problems_api as $problem) {
			// Filtra aqui, ANTES de contar
			if ($problem['acknowledged'] == 1 && !$problem_filters['show_acknowledged']) continue;
			if ($problem['suppressed'] == 1 && !$problem_filters['show_suppressed']) continue;
			
			$trigger_id = $problem['objectid'];
			if (!isset($triggers[$trigger_id])) continue;
			
			foreach ($triggers[$trigger_id]['hosts'] as $trigger_host) {
				$hostid = $trigger_host['hostid'];
				if (!isset($hosts_from_api[$hostid])) continue;
				
				if (!array_key_exists($hostid, $problems_by_host)) {
					$problems_by_host[$hostid] = [
						'counts' => [
							5 => ['unacked' => 0, 'acked' => 0], 4 => ['unacked' => 0, 'acked' => 0],
							3 => ['unacked' => 0, 'acked' => 0], 2 => ['unacked' => 0, 'acked' => 0],
							1 => ['unacked' => 0, 'acked' => 0], 0 => ['unacked' => 0, 'acked' => 0]
						],
						'events' => []
					];
				}
				
				$sev = (int)$problem['severity'];
				$problem_key = $problem['acknowledged'] == 1 ? 'acked' : 'unacked';
				
				$problems_by_host[$hostid]['counts'][$sev][$problem_key]++;
				$problems_by_host[$hostid]['events'][$problem['eventid']] = [
					'name' => $problem['name'],
					'severity' => $sev,
					'acknowledged' => (int)$problem['acknowledged']
				];
			}
		}

		// === PASSO 5: Processar a lista final ===
		foreach ($hosts_from_api as $hostid => $host) {
			if (empty($host['inventory']['location_lat']) || empty($host['inventory']['location_lon'])
				|| !is_numeric($host['inventory']['location_lat']) || !is_numeric($host['inventory']['location_lon'])
				|| $host['inventory']['location_lat'] == 0 || $host['inventory']['location_lon'] == 0) {
				continue; 
			}

			$lat = (float)$host['inventory']['location_lat'];
			$lon = (float)$host['inventory']['location_lon'];
			
			$host_problems_counts = $problems_by_host[$hostid]['counts'] ?? [];
			$host_events = $problems_by_host[$hostid]['events'] ?? [];
			
			$highest_severity = -1;
			if (!empty($host_problems_counts)) {
				for ($s = 5; $s >= 0; $s--) {
					if (!empty($host_problems_counts[$s]['unacked'])) {
						$highest_severity = $s;
						break;
					}
				}
				if ($highest_severity == -1) {
					for ($s = 5; $s >= 0; $s--) {
						if (!empty($host_problems_counts[$s]['acked'])) {
							$highest_severity = $s;
							break;
						}
					}
				}
			}
			
			if (empty($severity_filters[$highest_severity])) {
				continue;
			}
			
			$best_group_name = '';
			$best_group_depth = -1;
			$best_group_name_length = 0;
			if (empty($host['hostgroups'])) {
				$best_group_name = $host['name'];
			} else {
				$best_group_name = $host['hostgroups'][0]['name'];
			}
			if (!empty($user_selected_groupids)) {
				foreach ($host['hostgroups'] as $host_group) {
					if (in_array($host_group['groupid'], $user_selected_groupids)) {
						$current_depth = substr_count($host_group['name'], '/');
						$current_length = strlen($host_group['name']);
						if ($current_depth > $best_group_depth) {
							$best_group_depth = $current_depth;
							$best_group_name_length = $current_length;
							$best_group_name = $host_group['name'];
						}
						elseif ($current_depth == $best_group_depth && $current_length > $best_group_name_length) {
							$best_group_name_length = $current_length;
							$best_group_name = $host_group['name'];
						}
					}
				}
			}
			
			$locations_to_map[] = [
				'hostid' => $hostid, 
				'name' => $host['name'], 
				'group_name' => $best_group_name,
				'lat' => $lat, 
				'lon' => $lon,
				'severity' => $highest_severity, 
				'problem_counts' => $host_problems_counts,
				'problem_events' => $host_events
			];
		}

		return $locations_to_map;
	}


	/**
	 * FUNÇÃO 2: (LÓGICA DO MODAL)
	 */
	private function getDetailedProblemList(
		array $hostgroups, array $hosts, array $exclude_hosts, array $severity_filters, array $problem_filters,
		int $evaltype, array $tags,
		bool $exclude_maintenance // <-- Novo parâmetro
	): array {
		$detailed_problems = [];
		
		// === PASSO 1: Obter os Hosts que correspondem aos filtros ===
		$host_params = [
			'output' => ['hostid', 'name', 'maintenance_status'], // <-- Pede 'maintenance_status'
			'selectInventory' => ['location_lat', 'location_lon'],
			'monitored_hosts' => true,
			'preservekeys' => true,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'filter' => [
				'inventory_mode' => [HOST_INVENTORY_MANUAL, HOST_INVENTORY_AUTOMATIC],
				'location_lat' => '',
				'location_lon' => ''
			]
		];
		if (!empty($hosts)) {
			$host_params['hostids'] = $hosts;
		} elseif (!empty($hostgroups)) {
			$host_params['groupids'] = $hostgroups;
		} else {
			return [];
		}
		if (!empty($exclude_hosts) && !empty($host_params['hostids'])) {
			$host_params['hostids'] = array_diff($host_params['hostids'], $exclude_hosts);
		}
		try {
			$hosts_from_api = \API::Host()->get($host_params);
		} catch (\Exception $e) {
			return [];
		}

		// --- MUDANÇA AQUI: Filtra por manutenção ---
		if ($exclude_maintenance) {
			$hosts_from_api = array_filter($hosts_from_api, function ($host) {
				return $host['maintenance_status'] == 0; // 0 = no maintenance
			});
		}
		// --- FIM DA MUDANÇA ---

		if (!empty($exclude_hosts) && !empty($host_params['groupids'])) {
			$hosts_from_api = array_filter($hosts_from_api, function ($hostid) use ($exclude_hosts) {
				return !in_array($hostid, $exclude_hosts);
			}, ARRAY_FILTER_USE_KEY);
		}
		
		$hosts_on_map = [];
		foreach ($hosts_from_api as $hostid => $host) {
			if (!empty($host['inventory']['location_lat']) && !empty($host['inventory']['location_lon'])
				&& is_numeric($host['inventory']['location_lat']) && is_numeric($host['inventory']['location_lon'])
				&& $host['inventory']['location_lat'] != 0 && $host['inventory']['location_lon'] != 0) {
				$hosts_on_map[$hostid] = $host['name'];
			}
		}
		
		if (empty($hosts_on_map)) {
			return [];
		}
		
		$filtered_host_ids = array_keys($hosts_on_map);
		$host_name_cache = $hosts_on_map;
		// === FIM DO PASSO 1 ===


		// === PASSO 2: Obter Triggers ATIVOS para esses hosts ===
		$severities_to_fetch = [];
		foreach ($severity_filters as $severity => $is_enabled) {
			if ($is_enabled && $severity >= 0) {
				$severities_to_fetch[] = $severity;
			}
		}
		if (empty($severities_to_fetch)) {
			return $detailed_problems;
		}

		try {
			$triggers = \API::Trigger()->get([
				'output' => ['triggerid'],
				'selectHosts' => ['hostid'],
				'hostids' => $filtered_host_ids,
				'filter' => ['value' => TRIGGER_VALUE_TRUE],
				'monitored' => true, 
				'skipDependent' => true,
				'preservekeys' => true
			]);
		} catch (\Exception $e) {
			return [];
		}

		if (empty($triggers)) {
			return [];
		}
		$active_trigger_ids = array_keys($triggers);


		// === PASSO 3: Obter Problemas para esses Triggers ATIVOS ===
		$options = [
			'output' => ['eventid', 'objectid', 'clock', 'name', 'severity', 'r_eventid', 'acknowledged', 'suppressed'],
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'sortfield' => 'eventid',
			'sortorder' => ZBX_SORT_DOWN,
			'severities' => $severities_to_fetch,
			'objectids' => $active_trigger_ids
		];

		if ($problem_filters['show_acknowledged'] == 0) {
			$options['acknowledged'] = false;
		}

		try {
			$problems = \API::Problem()->get($options);
		} catch (\Exception $e) {
			return $detailed_problems;
		}
		
		if (empty($problems)) {
			return $detailed_problems;
		}

		// === PASSO 4: Mapear Triggers para Hosts (Necessário) ===
		$trigger_to_host_map = [];
		foreach ($triggers as $trigger_id => $trigger) {
			if (!empty($trigger['hosts'])) {
				$trigger_to_host_map[$trigger_id] = $trigger['hosts'][0]['hostid'];
			}
		}

		// === PASSO 5: Processar a lista final ===
		foreach ($problems as $problem) {
			if ($problem_filters['show_suppressed'] == 0 && $problem['suppressed'] == 1) {
				continue;
			}
			
			$trigger_id = $problem['objectid'];
			$hostid = $trigger_to_host_map[$trigger_id] ?? null;
			
			if ($hostid === null || !isset($host_name_cache[$hostid])) {
				continue;
			}
			
			$detailed_problems[] = [
				'eventid' => $problem['eventid'],
				'hostname' => $host_name_cache[$hostid],
				'severity' => (int)$problem['severity'],
				'problem' => $problem['name'],
				'acknowledged' => (int)$problem['acknowledged'],
				'age' => $this->formatAge(time() - (int)$problem['clock'])
			];
		}

		return $detailed_problems;
	}


	// --- Função de Cor ---
	private function getSeverityColor(int $severity): string {
		$colors = [
			-1 => '#66BB6A', // OK (Verde)
			WidgetForm::SEVERITY_NOT_CLASSIFIED => '#97AAB3', // Gray
			WidgetForm::SEVERITY_INFORMATION => '#7499FF', // Blue
			WidgetForm::SEVERITY_WARNING => '#FFC859', // Yellow
			WidgetForm::SEVERITY_AVERAGE => '#FFA059', // Orange
			WidgetForm::SEVERITY_HIGH => '#E97659', // Light Red
			WidgetForm::SEVERITY_DISASTER => '#E45959' // Red
		];
		return $colors[$severity] ?? $colors[-1];
	}

	// --- Função "roubada" do AlarmWidget ---
	private function formatAge(int $seconds): string {
		if ($seconds < 60) return $seconds . 's';
		elseif ($seconds < 3600) return floor($seconds / 60) . 'm';
		elseif ($seconds < 86400) return floor($seconds / 3600) . 'h';
		elseif ($seconds < 2592000) return floor($seconds / 86400) . 'd';
		elseif ($seconds < 31536000) return floor($seconds / 2592000) . 'm';
		else return floor($seconds / 31536000) . 'y';
	}
}
