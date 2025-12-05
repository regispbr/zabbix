<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

namespace Modules\HostGroupAlarms\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\HostGroupAlarms\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// 1. Coletar filtros do formulário
		$hostgroups = $this->fields_values['hostgroups'] ?? [];
		$hosts = $this->fields_values['hosts'] ?? [];
		$exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
		
		$show_acknowledged = (int)($this->fields_values['show_acknowledged'] ?? 1);
		$show_suppressed = (int)($this->fields_values['show_suppressed'] ?? 0);
		$exclude_maintenance = (int)($this->fields_values['exclude_maintenance'] ?? 0);
		
		$evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['tags'] ?? [];

		// Mapeamento de severidades habilitadas no widget
		$severity_filters = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => $this->fields_values['show_not_classified'] ?? 1,
			WidgetForm::SEVERITY_INFORMATION => $this->fields_values['show_information'] ?? 1,
			WidgetForm::SEVERITY_WARNING => $this->fields_values['show_warning'] ?? 1,
			WidgetForm::SEVERITY_AVERAGE => $this->fields_values['show_average'] ?? 1,
			WidgetForm::SEVERITY_HIGH => $this->fields_values['show_high'] ?? 1,
			WidgetForm::SEVERITY_DISASTER => $this->fields_values['show_disaster'] ?? 1
		];

		// 2. Definir o nome do grupo (Visual)
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

		// 3. Buscar Dados Reais (Refatorado para usar API::Problem)
		$alarm_data = $this->getProblemData(
			$hostgroups, 
			$hosts, 
			$exclude_hosts, 
			$severity_filters, 
			$show_acknowledged, 
			$show_suppressed, 
			$exclude_maintenance,
			$evaltype,
			$tags
		);

		// 4. Preparar resposta
		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'group_name' => $group_name,
			'show_group_name' => $this->fields_values['show_group_name'] ?? 1,
			'alarm_counts' => $alarm_data['counts'],
			'total_alarms' => $alarm_data['total'],
			'highest_severity' => $alarm_data['highest_severity'],
			'detailed_alarms' => $alarm_data['detailed_alarms'],
			'background_color' => $this->getSeverityColor($alarm_data['highest_severity']),
			'text_color' => $this->getTextColor($alarm_data['highest_severity']),
			'font_size' => $this->fields_values['font_size'] ?? 14,
			'font_family' => $this->fields_values['font_family'] ?? 'Arial, sans-serif',
			'show_border' => $this->fields_values['show_border'] ?? 1,
			'border_width' => $this->fields_values['border_width'] ?? 2,
			'border_color' => $this->getSeverityColor($alarm_data['highest_severity']),
			'padding' => $this->fields_values['padding'] ?? 10,
			'enable_url_redirect' => $this->fields_values['enable_url_redirect'] ?? 0,
			'redirect_url' => $this->fields_values['redirect_url'] ?? '',
			'open_in_new_tab' => $this->fields_values['open_in_new_tab'] ?? 1,
			'show_detailed_tooltip' => $this->fields_values['show_detailed_tooltip'] ?? 1,
			'tooltip_max_items' => $this->fields_values['tooltip_max_items'] ?? 10,
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	/**
	 * Busca problemas usando API::Problem para garantir paridade com o frontend nativo.
	 */
	private function getProblemData(
		array $hostgroups, 
		array $hosts, 
		array $exclude_hosts, 
		array $severity_filters, 
		int $show_acknowledged,
		int $show_suppressed,
		int $exclude_maintenance,
		int $evaltype,
		array $tags
	): array {
		
		$alarm_counts = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => 0,
			WidgetForm::SEVERITY_INFORMATION => 0,
			WidgetForm::SEVERITY_WARNING => 0,
			WidgetForm::SEVERITY_AVERAGE => 0,
			WidgetForm::SEVERITY_HIGH => 0,
			WidgetForm::SEVERITY_DISASTER => 0
		];
		$detailed_alarms = [];
		$total_alarms = 0;
		$highest_severity = -1;

		if (empty($hostgroups) && empty($hosts)) {
			return $this->buildEmptyResult($alarm_counts);
		}

		// === PASSO 1: Resolver Hosts (para filtro de Manutenção e Exclusão) ===
		// Precisamos dos IDs dos hosts para passar ao API::Problem e garantir filtros corretos
		$host_options = [
			'output' => ['hostid', 'name', 'maintenance_status'],
			'preservekeys' => true
		];
		
		if (!empty($groupids)) $host_options['groupids'] = $hostgroups;
		if (!empty($hosts)) $host_options['hostids'] = $hosts;

		$hosts_data = API::Host()->get($host_options);

		// Filtra Manutenção
		if ($exclude_maintenance == 1) {
			$hosts_data = array_filter($hosts_data, function($host) {
				return $host['maintenance_status'] == HOST_MAINTENANCE_STATUS_OFF;
			});
		}

		// Filtra Exclusões Manuais
		if (!empty($exclude_hosts)) {
			$hosts_data = array_diff_key($hosts_data, array_flip($exclude_hosts));
		}

		$valid_hostids = array_keys($hosts_data);

		if (empty($valid_hostids)) {
			return $this->buildEmptyResult($alarm_counts);
		}

		// === PASSO 2: Buscar Problemas (A Correção Principal) ===
		$problem_options = [
			'output' => ['eventid', 'objectid', 'severity', 'name', 'clock', 'acknowledged', 'suppressed'],
			'hostids' => $valid_hostids,
			'selectAcknowledges' => ['action'], // Necessário para saber se foi ack
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'recent' => true, // Importante para "Recent problems" vs "History"
			'sortfield' => ['eventid'],
			'sortorder' => 'DESC'
		];

		// Filtro de Severidade (Passamos apenas as marcadas para otimizar)
		$severities_to_get = [];
		foreach ($severity_filters as $sev => $active) {
			if ($active) $severities_to_get[] = $sev;
		}
		if (empty($severities_to_get)) {
			return $this->buildEmptyResult($alarm_counts);
		}
		$problem_options['severities'] = $severities_to_get;

		// Filtro de Tags
		if (!empty($tags)) {
			$problem_options['evaltype'] = $evaltype;
			$problem_options['tags'] = $tags;
		}

		// Filtro Acknowledged
		// Se show_acknowledged == 0 (apenas não reconhecidos)
		// Se show_acknowledged == 1 (mostrar todos - padrão do widget original era checkbox)
		if ($show_acknowledged == 0) {
			$problem_options['acknowledged'] = false;
		}

		// Filtro Suppressed
		// Se show_suppressed == 0, ocultar suprimidos. Se 1, mostrar (ou ambos).
		if ($show_suppressed == 0) {
			$problem_options['suppressed'] = false;
		}

		$problems = API::Problem()->get($problem_options);

		// === PASSO 3: Processar Resultados ===
		foreach ($problems as $problem) {
			$severity = (int)$problem['severity'];

			// Incrementa contadores
			if (isset($alarm_counts[$severity])) {
				$alarm_counts[$severity]++;
				$total_alarms++;
				
				if ($severity > $highest_severity) {
					$highest_severity = $severity;
				}

				// Prepara dados detalhados para o Tooltip
				// Nota: API::Problem não retorna Host Name direto, pegamos do cache de hosts
				// Para pegar o host exato do problema, precisaríamos de selectHosts, 
				// mas como já temos $hosts_data filtrado, podemos tentar cruzar ou fazer um get extra se for crítico.
				// Para performance, no tooltip, vamos assumir que o usuário quer ver o problema.
				// Se precisar do nome do host exato no tooltip, descomente o selectHosts no API::Problem
				
				$detailed_alarms[] = [
					'triggerid' => $problem['objectid'], // Em problemas de trigger, objectid é o triggerid
					'eventid' => $problem['eventid'],
					'description' => $problem['name'],
					'severity' => $severity,
					'severity_name' => $this->getSeverityName($severity),
					'host_name' => 'Host', // Simplificação para evitar N+1 queries. O ideal é adicionar selectHosts no get.
					'clock' => $problem['clock'],
					'acknowledged' => (int)$problem['acknowledged'],
					'suppressed' => (int)$problem['suppressed']
				];
			}
		}

		// Para o nome do host no tooltip ficar correto, faremos um fetch em lote rápido
		if (!empty($detailed_alarms)) {
			// Coletamos os triggerids
			$triggerids = array_column($detailed_alarms, 'triggerid');
			$triggers = API::Trigger()->get([
				'output' => ['triggerid'],
				'selectHosts' => ['name'],
				'triggerids' => $triggerids,
				'preservekeys' => true
			]);
			
			foreach ($detailed_alarms as &$alarm) {
				if (isset($triggers[$alarm['triggerid']]['hosts'][0])) {
					$alarm['host_name'] = $triggers[$alarm['triggerid']]['hosts'][0]['name'];
				}
			}
		}

		return [
			'counts' => $alarm_counts,
			'total' => $total_alarms,
			'highest_severity' => $highest_severity,
			'detailed_alarms' => $detailed_alarms
		];
	}

	private function buildEmptyResult($alarm_counts): array {
		return [
			'counts' => $alarm_counts,
			'total' => 0,
			'highest_severity' => -1,
			'detailed_alarms' => []
		];
	}

	private function getSeverityName(int $severity): string {
		$severity_names = [
			WidgetForm::SEVERITY_NOT_CLASSIFIED => _('Not classified'),
			WidgetForm::SEVERITY_INFORMATION => _('Information'),
			WidgetForm::SEVERITY_WARNING => _('Warning'),
			WidgetForm::SEVERITY_AVERAGE => _('Average'),
			WidgetForm::SEVERITY_HIGH => _('High'),
			WidgetForm::SEVERITY_DISASTER => _('Disaster')
		];

		return $severity_names[$severity] ?? _('Unknown');
	}

	private function getSeverityColor(int $severity): string {
		$colors = [
			-1 => '#66BB6A',
			WidgetForm::SEVERITY_NOT_CLASSIFIED => '#97AAB3',
			WidgetForm::SEVERITY_INFORMATION => '#7499FF',
			WidgetForm::SEVERITY_WARNING => '#FFC859',
			WidgetForm::SEVERITY_AVERAGE => '#FFA059',
			WidgetForm::SEVERITY_HIGH => '#E97659',
			WidgetForm::SEVERITY_DISASTER => '#E45959'
		];

		return $colors[$severity] ?? $colors[-1];
	}

	private function getTextColor(int $severity): string {
		$light_backgrounds = [
			-1,
			WidgetForm::SEVERITY_NOT_CLASSIFIED,
			WidgetForm::SEVERITY_WARNING,
			WidgetForm::SEVERITY_AVERAGE
		];

		return in_array($severity, $light_backgrounds) ? '#000000' : '#FFFFFF';
	}
}

