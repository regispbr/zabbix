<?php declare(strict_types = 0);

namespace Modules\HostGroupAlarms\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\HostGroupAlarms\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

    protected function doAction(): void {
        // 1. Coletar Inputs
        $hostgroups = $this->fields_values['hostgroups'] ?? [];
        $hosts = $this->fields_values['hosts'] ?? [];
        $exclude_hosts = $this->fields_values['exclude_hosts'] ?? [];
        
        $show_acknowledged = (int)($this->fields_values['show_acknowledged'] ?? 1);
        $show_suppressed = (int)($this->fields_values['show_suppressed'] ?? 0);
        $exclude_maintenance = (int)($this->fields_values['exclude_maintenance'] ?? 0);
        
        $evaltype = $this->fields_values['evaltype'] ?? TAG_EVAL_TYPE_AND_OR;
        $tags = $this->fields_values['tags'] ?? [];

        $severity_filters = [
            WidgetForm::SEVERITY_NOT_CLASSIFIED => $this->fields_values['show_not_classified'] ?? 1,
            WidgetForm::SEVERITY_INFORMATION => $this->fields_values['show_information'] ?? 1,
            WidgetForm::SEVERITY_WARNING => $this->fields_values['show_warning'] ?? 1,
            WidgetForm::SEVERITY_AVERAGE => $this->fields_values['show_average'] ?? 1,
            WidgetForm::SEVERITY_HIGH => $this->fields_values['show_high'] ?? 1,
            WidgetForm::SEVERITY_DISASTER => $this->fields_values['show_disaster'] ?? 1
        ];

        // 2. Busca Dados (Agora usando a API correta e filtrada)
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

        // 3. Define nome do grupo
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

        // 4. Monta Resposta
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
            // Estilos
            'font_size' => $this->fields_values['font_size'] ?? 14,
            'font_family' => $this->fields_values['font_family'] ?? 'Arial, sans-serif',
            'show_border' => $this->fields_values['show_border'] ?? 1,
            'border_width' => $this->fields_values['border_width'] ?? 2,
            'border_color' => $this->getSeverityColor($alarm_data['highest_severity']),
            'padding' => $this->fields_values['padding'] ?? 10,
            // Configs Extras
            'enable_url_redirect' => $this->fields_values['enable_url_redirect'] ?? 0,
            'redirect_url' => $this->fields_values['redirect_url'] ?? '',
            'open_in_new_tab' => $this->fields_values['open_in_new_tab'] ?? 1,
            'show_detailed_tooltip' => $this->fields_values['show_detailed_tooltip'] ?? 1,
            'tooltip_max_items' => $this->fields_values['tooltip_max_items'] ?? 10,
            'fields_values' => $this->fields_values,
            
            // >>> DEBUG: Enviamos o log para o JS ler <<<
            'debug_log' => $alarm_data['debug_log'], 
            
            'user' => [
                'debug_mode' => $this->getDebugMode()
            ]
        ];

        $this->setResponse(new CControllerResponseData($data));
    }

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
        $debug_log = [];

        if (empty($hostgroups) && empty($hosts)) {
            return $this->buildEmptyResult($alarm_counts);
        }

        // Filtro de Hosts/Grupos
        $options = [
            'output' => ['eventid', 'objectid', 'severity', 'name', 'clock', 'acknowledged', 'suppressed'],
            'selectHosts' => ['hostid', 'name', 'maintenance_status'], // Trazemos o status aqui
            'source' => EVENT_SOURCE_TRIGGERS,
            'object' => EVENT_OBJECT_TRIGGER,
            'filter' => ['r_eventid' => 0], // <--- CORREÇÃO CRÍTICA: Apenas problemas ABERTOS
            'sortfield' => ['eventid'],
            'sortorder' => 'DESC',
            'preservekeys' => true
        ];

        if (!empty($hostgroups)) $options['groupids'] = $hostgroups;
        if (!empty($hosts)) $options['hostids'] = $hosts;
        
        // Filtro de Tags
        if (!empty($tags)) {
            $options['evaltype'] = $evaltype;
            $options['tags'] = $tags;
        }

        // 1. Filtro Suppressed (API)
        // Se show_suppressed = 0 (Desmarcado), API retorna apenas 'suppressed' => false
        // Se show_suppressed = 1 (Marcado), API retorna tudo (não definimos a chave)
        if ($show_suppressed == 0) {
            $options['suppressed'] = false;
        }

        // 2. Filtro Acknowledged (API)
        // Se show_acknowledged = 0 (Desmarcado), API retorna apenas 'acknowledged' => false
        if ($show_acknowledged == 0) {
            $options['acknowledged'] = false;
        }

        // Chamada API
        $problems = API::Problem()->get($options);

        // Processamento em PHP (para filtros que a API Problem não faz 100% ou para contagem)
        foreach ($problems as $problem) {
            $severity = (int)$problem['severity'];
            $host = $problem['hosts'][0] ?? null;

            $is_kept = true;
            $reason = 'OK';

            // Filtro Exclude Hosts (Manual)
            if ($host && in_array($host['hostid'], $exclude_hosts)) {
                $is_kept = false;
                $reason = 'Excluded Host';
            }

            // Filtro Manutenção
            // Se "Exclude Maintenance" = 1 e status = 1 (Em manutenção), remove.
            if ($is_kept && $exclude_maintenance == 1 && $host && $host['maintenance_status'] == 1) {
                $is_kept = false;
                $reason = 'In Maintenance';
            }

            // Filtro Severidade
            if ($is_kept && empty($severity_filters[$severity])) {
                $is_kept = false;
                $reason = 'Severity Disabled';
            }

            // DEBUG LOG
            $debug_log[] = [
                'Problem' => $problem['name'],
                'Host' => $host['name'] ?? '?',
                'Sever' => $severity,
                'Maint' => $host['maintenance_status'] ?? '?',
                'Suppr' => $problem['suppressed'],
                'Ack' => $problem['acknowledged'],
                'RESULT' => $is_kept ? 'KEPT' : 'DROPPED',
                'Reason' => $reason
            ];

            if ($is_kept) {
                $alarm_counts[$severity]++;
                
                $detailed_alarms[] = [
                    'triggerid' => $problem['objectid'],
                    'eventid' => $problem['eventid'],
                    'description' => $problem['name'],
                    'severity' => $severity,
                    'severity_name' => $this->getSeverityName($severity),
                    'host_name' => $host['name'] ?? 'Unknown',
                    'clock' => $problem['clock'],
                    'acknowledged' => (int)$problem['acknowledged'],
                    'suppressed' => (int)$problem['suppressed']
                ];
            }
        }

        // Calcula totais
        $total_alarms = array_sum($alarm_counts);
        
        // Calcula severidade mais alta
        $highest_severity = -1;
        for ($i = 5; $i >= 0; $i--) {
            if ($alarm_counts[$i] > 0) {
                $highest_severity = $i;
                break;
            }
        }

        return [
            'counts' => $alarm_counts,
            'total' => $total_alarms,
            'highest_severity' => $highest_severity,
            'detailed_alarms' => $detailed_alarms,
            'debug_log' => $debug_log
        ];
    }

    private function buildEmptyResult($alarm_counts): array {
        return ['counts' => $alarm_counts, 'total' => 0, 'highest_severity' => -1, 'detailed_alarms' => [], 'debug_log' => []];
    }

    private function getSeverityName(int $severity): string {
        $names = [0=>_('Not classified'), 1=>_('Information'), 2=>_('Warning'), 3=>_('Average'), 4=>_('High'), 5=>_('Disaster')];
        return $names[$severity] ?? _('Unknown');
    }

    private function getSeverityColor(int $severity): string {
        $colors = [-1=>'#66BB6A', 0=>'#97AAB3', 1=>'#7499FF', 2=>'#FFC859', 3=>'#FFA059', 4=>'#E97659', 5=>'#E45959'];
        return $colors[$severity] ?? $colors[-1];
    }

    private function getTextColor(int $severity): string {
        return in_array($severity, [-1, 0, 2, 3]) ? '#000000' : '#FFFFFF';
    }
}
