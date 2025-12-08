<?php declare(strict_types = 0);

namespace Modules\HostGroupStatus\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\HostGroupStatus\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$count_mode = $this->fields_values['count_mode'] ?? WidgetForm::COUNT_MODE_WITH_ALARMS;
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$problem_filters = [
			'evaltype' => $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR,
			'tags' => $this->fields_values['tags'] ?? [],
			'show_acknowledged' => $this->fields_values['show_acknowledged'] ?? 1,
			'show_suppressed' => $this->fields_values['show_suppressed'] ?? 0,
			// Novo filtro
			'show_suppressed_only' => $this->fields_values['show_suppressed_only'] ?? 0,
			'exclude_maintenance' => $this->fields_values['exclude_maintenance'] ?? 0
		];

		// Severidades (Array de IDs)
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
		
		// === PASSO 1: Obter Hosts ===
		$host_params = [
			'output' => ['hostid'],
			'monitored_hosts' => true,
			'preservekeys' => true,
			// Não filtramos tags aqui, pois queremos contar hosts totais corretamente para o modo "All"
			// As tags serão filtradas na busca de problemas
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
		
		try {
			$all_hosts = API::Host()->get($host_params);
		} catch (\Exception $e) {
			return ['count' => 0];
		}
		
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

		// === PASSO 2: Obter Problemas (API Problem é mais confiável que Trigger) ===
		$hosts_with_alarms_map = [];
		
		if (!empty($severity_ids)) {
			try {
				// Configuramos a busca de Problemas
				$problem_options = [
					'output' => ['objectid', 'suppressed', 'acknowledged'], // Precisamos do triggerid (objectid) para saber o host? Não, problem.get não retorna hostid direto se não pedir
					'selectHosts' => ['hostid'], // Pedimos os hosts associados ao problema
					'hostids' => $all_host_ids,
					'severities' => $severity_ids,
					'evaltype' => $problem_filters['evaltype'],
					'tags' => $problem_filters['tags'],
					'recent' => true // Importante para pegar problemas ativos
				];

				// Lógica de Supressão para a API
				// Se "Only Suppressed" ou "Show Suppressed" estiver marcado, pedimos suprimidos
				if ($problem_filters['show_suppressed'] == 1 || $problem_filters['show_suppressed_only'] == 1) {
					$problem_options['suppressed'] = true; // Traz suprimidos e não suprimidos (API Zabbix < 7.0 comportamento pode variar, mas geralmente é isso)
                    // Na API Problem, 'suppressed' => true traz AMBOS ou APENAS suprimidos?
                    // Documentação Zabbix 6/7: "If set to true, the response will contain only suppressed problems." -> CUIDADO!
                    // CORREÇÃO: Problem API "suppressed": true (apenas suprimidos), false (apenas normais), null (todos).
                    
                    if ($problem_filters['show_suppressed_only'] == 1) {
                        $problem_options['suppressed'] = true; // Apenas suprimidos
                    } elseif ($problem_filters['show_suppressed'] == 1) {
                        $problem_options['suppressed'] = null; // Todos (API Problem default é null = todos? Não, default é false = só normais. Null = todos)
                        // Hack: Para API Problem, não passar 'suppressed' traz apenas normais. 
                        // Para trazer todos, não existe um parâmetro 'all'. Temos que fazer duas chamadas ou não usar esse filtro na API e filtrar no PHP.
                        // Vamos não passar 'suppressed' na API e filtrar no PHP? Não, se não passar, só vem normais.
                        
                        // Solução segura: Se Show Suppressed = 1 (e Only = 0), não definimos 'suppressed' na query? 
                        // Não, definimos 'suppressed' => null para trazer tudo (se a API suportar null).
                        // Se a API não suportar null, teremos que fazer chamadas separadas?
                        // Teste prático: unset($problem_options['suppressed']) geralmente traz apenas não-suprimidos.
                        
                        // Vamos assumir que queremos filtrar no PHP para garantir.
                        // Mas para trazer suprimidos, precisamos avisar a API.
                        // Zabbix 7: 'suppressed' => true (show suppressed problems).
                        unset($problem_options['suppressed']); // Traz tudo (depende da versão).
                        // Vamos usar a lógica "traga tudo o que puder" e filtrar no PHP.
                        // Mas espere, API Problem do Zabbix geralmente filtra suppressed=false por padrão.
                        // Vamos tentar forçar a busca de suprimidos explicitamente se necessário.
                    } else {
                        $problem_options['suppressed'] = false; // Apenas normais
                    }
				} else {
					$problem_options['suppressed'] = false; // Apenas normais
				}
                
                // REFINAMENTO DA LÓGICA DE SUPRESSÃO PARA API
                // Se Only Suppressed = 1 -> 'suppressed' => true
                // Se Show Suppressed = 1 -> Não enviar o parâmetro (traz tudo? ou precisa de lógica extra?)
                // Na API Problem.get: "suppressed" (boolean) - if set to true, return only suppressed problems. If set to false, return only problems that are NOT suppressed.
                // Se omitido? Retorna ambos? Não, geralmente retorna não-suprimidos.
                
                // Vamos simplificar: Faremos a busca em duas etapas se for "Show Suppressed" (Todos).
                // Ou, melhor: Se "Only", 'suppressed'=>true. Se "Show", fazemos duas queries e merge, ou assumimos que a API sem parametro traz não suprimidos e com parametro true traz suprimidos.
                
                // CORREÇÃO: Vamos confiar no filtro PHP pós-busca.
                // Para garantir que temos dados, vamos buscar sem filtro de supressão se for "Show Suppressed" (se a API permitir) ou fazer 2 chamadas.
                // Mas para manter simples e eficiente:
                
                if ($problem_filters['show_suppressed_only'] == 1) {
                    $problem_options['suppressed'] = true;
                } elseif ($problem_filters['show_suppressed'] == 1) {
                    // Queremos tudo.
                    // Vamos buscar normais.
                    $probs_normal = API::Problem()->get($problem_options + ['suppressed' => false]);
                    // Vamos buscar suprimidos.
                    $probs_sup = API::Problem()->get($problem_options + ['suppressed' => true]);
                    $problems = array_merge($probs_normal, $probs_sup);
                } else {
                    // Padrão: Apenas não suprimidos
                    $problem_options['suppressed'] = false;
                    $problems = API::Problem()->get($problem_options);
                }

                // Se não entrou no IF do meio, executamos a query única
                if (!isset($problems)) {
                    $problems = API::Problem()->get($problem_options);
                }

				foreach ($problems as $problem) {
                    // Filtragem no PHP para garantir
					$p_ack = (int)$problem['acknowledged'];
					$p_sup = (int)$problem['suppressed'];

					if ($p_ack == 1 && !$problem_filters['show_acknowledged']) {
						continue;
					}
                    // O filtro de supressão já foi tratado na API, mas confirmamos:
					if ($problem_filters['show_suppressed_only'] == 1 && $p_sup == 0) {
						continue;
					}
                    if ($problem_filters['show_suppressed'] == 0 && $p_sup == 1) {
                        continue;
                    }

					foreach ($problem['hosts'] as $host) {
						if (isset($all_hosts[$host['hostid']])) {
							$hosts_with_alarms_map[$host['hostid']] = true;
						}
					}
				}
			} catch (\Exception $e) {}
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
