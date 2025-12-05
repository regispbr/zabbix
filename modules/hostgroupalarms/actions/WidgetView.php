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
		// Define constantes se necessário (normalmente já estão definidas pelo Zabbix)
		if (!defined('ZBX_PROBLEM_SUPPRESSED')) define('ZBX_PROBLEM_SUPPRESSED', 1);
		if (!defined('ZBX_ACK_STATUS_ALL')) define('ZBX_ACK_STATUS_ALL', 1);
		if (!defined('ZBX_ACK_STATUS_UNACK')) define('ZBX_ACK_STATUS_UNACK', 2);
		if (!defined('TRIGGERS_OPTION_RECENT_PROBLEM')) define('TRIGGERS_OPTION_RECENT_PROBLEM', 1);

		// 1. Coleta de Filtros do WidgetForm
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		// Converte checkboxes para inteiros
		$show_acknowledged = (int)($this->fields_values['show_acknowledged'] ?? 1);
		$show_suppressed = (int)($this->fields_values['show_suppressed'] ?? 0);
		$exclude_maintenance = (int)($this->fields_values['exclude_maintenance'] ?? 0);
		
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];

		// Mapeia os checkbox de severidade para um array de inteiros
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

		// Configurações para a Engine de Problemas
		$show_mode = TRIGGERS_OPTION_RECENT_PROBLEM; 
		// Lógica do filtro de Ack: Se marcado (1), mostra TUDO. Se desmarcado (0), mostra SÓ UNACK.
		$ack_status = ($show_acknowledged == 1) ? ZBX_ACK_STATUS_ALL : ZBX_ACK_STATUS_UNACK;
		$search_limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT);

		// 2. Chama Engine Nativa (CScreenProblem::getData)
		// Isso busca os dados brutos do banco de dados/cache
		$data = CScreenProblem::getData([
			'show' => $show_mode,
			'groupids' => $hostgroups,
			'hostids' => $hosts,
			'name' => '', // Filtro de nome de problema (vazio = todos)
			'severities' => $severities,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'show_symptoms' => false,
			'show_suppressed' => ($show_suppressed == 1),
			'acknowledgement_status' => $ack_status,
			'show_opdata' => 0
		], $search_limit);

		// 3. "Hidratação" dos Dados (O Segredo do Widget Nativo)
		// makeData processa os dados brutos, resolve nomes de hosts, macros, etc.
		// Isso garante que teremos acesso aos nomes corretos dos hosts.
		$data = CScreenProblem::makeData($data, [
			'show' => $show_mode,
			'details' => 0,
			'show_opdata' => 0
		]);

		// Se houver triggers, precisamos mapear os hosts para filtrar manutenção/exclusão
		// A makeData já popula $data['triggers_hosts'] com nomes formatados!
		// No entanto, para filtragem lógica (IDs), ainda precisamos olhar para $data['triggers']
		
		$alarm_counts = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
		$detailed_alarms = [];
		$total_alarms = 0;
		$highest_severity = -1;

		if (!empty($data['problems'])) {
			foreach ($data['problems'] as $problem) {
				$triggerid = $problem['objectid'] ?? 0;
				
				if (!isset($data['triggers'][$triggerid])) continue;
				$trigger = $data['triggers'][$triggerid];
				
				// Para filtragem lógica, precisamos dos dados brutos do host (ID e Manutenção)
				// makeData não altera a estrutura original de 'triggers', apenas adiciona 'triggers_hosts'
				$first_host_raw = null;
				if (!empty($trigger['hosts'])) {
					$first_host_raw = reset($trigger['hosts']);
				}

				if (!$first_host_raw) continue; // Trigger sem host (raro, mas possível)

				$host_id = $first_host_raw['hostid'];
				$host_maintenance_status = $first_host_raw['maintenance_status'];

				// --- FILTROS MANUAIS ---
				// 1. Exclude Hosts (por ID)
				if (in_array($host_id, $exclude_hosts)) continue;

				// 2. Maintenance
				// Se "Exclude" estiver marcado (1) E o host estiver em manutenção (1), ignoramos.
				if ($exclude_maintenance == 1 && $host_maintenance_status == 1) continue;

				// Se passou, contabiliza
				$severity = (int)($problem['severity'] ?? 0);
				$alarm_counts[$severity]++;
				$total_alarms++;

				if ($severity > $highest_severity) {
					$highest_severity = $severity;
				}

				// --- DADOS PARA O TOOLTIP ---
				// Agora usamos os dados processados pelo makeData para exibição correta
				
				// Nome do Host: Usamos o array 'triggers_hosts' gerado pelo makeData. 
				// Ele contém o nome formatado e seguro do host para este trigger.
				// Se for um array (vários hosts), pegamos o primeiro elemento que é o nome.
				$host_name_display = _('Unknown host');
				if (isset($data['triggers_hosts'][$triggerid])) {
					$host_entry = $data['triggers_hosts'][$triggerid];
					// O widget nativo retorna um array de CLink ou string. Precisamos da string.
					// Se for um array de objetos, tentamos pegar o nome do primeiro.
					// Simplificação: Pegamos o nome do host bruto se o makeData retornar objetos complexos de UI.
					$host_name_display = $first_host_raw['name']; 
				}

				// Ack Status: A API retorna '1' (string) ou 1 (int) para Ack.
				$p_ack = (int)($problem['acknowledged'] ?? 0);
				
				// Suppressed
				$p_sup = (int)($problem['suppressed'] ?? 0);

				$detailed_alarms[] = [
					'eventid' => $problem['eventid'],
					'triggerid' => $triggerid,
					'description' => $problem['name'], // Nome do problema já resolvido
					'severity' => $severity,
					'severity_name' => CSeverityHelper::getName($severity),
					'host_name' => $host_name_display,
					'clock' => $problem['clock'],
					'acknowledged' => $p_ack,
					'suppressed' => $p_sup
				];
			}
		}

		// Ordenação (Severidade Desc, depois Tempo Desc)
		usort($detailed_alarms, function($a, $b) {
			if ($a['severity'] === $b['severity']) {
				return $b['clock'] - $a['clock'];
			}
			return $b['severity'] - $a['severity'];
		});

		// 4. Nome do Grupo
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

		// Prepara resposta
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
