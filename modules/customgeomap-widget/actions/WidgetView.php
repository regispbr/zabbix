<?php declare(strict_types = 0);

namespace Modules\CustomGeoMapWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

    protected function doAction(): void {
        $groupids = $this->fields_values['groupids'] ?? null;
        $hostids = $this->fields_values['hostids'] ?? null;
        $min_severity = $this->fields_values['min_severity'] ?? 0;
        $show_maintenance = $this->fields_values['show_maintenance'] ?? 0;
        $show_acknowledged = $this->fields_values['show_acknowledged'] ?? 1;

        // Host groups filter
        $hostgroups_filter = [
            'output' => ['groupid', 'name'],
            'selectHosts' => ['hostid', 'name', 'maintenance_status'],
            'with_hosts' => true,
            'preservekeys' => true
        ];

        if ($groupids) {
            $hostgroups_filter['groupids'] = $groupids;
        }

        $hostgroups = API::HostGroup()->get($hostgroups_filter);

        $result_hosts = [];

        foreach ($hostgroups as $groupid => $group) {
            if (empty($group['hosts'])) continue;
            
            foreach ($group['hosts'] as $host) {
                if ($hostids && !in_array($host['hostid'], $hostids)) {
                    continue;
                }

                $host_full = API::Host()->get([
                    'output' => ['hostid', 'name', 'maintenance_status'],
                    'hostids' => $host['hostid'],
                    'selectInventory' => ['location_lat', 'location_lon', 'location_latitude', 'location_longitude', 'latitude', 'longitude'],
                    'preservekeys' => false
                ]);

                $host_info = reset($host_full);
                if (!$host_info || empty($host_info['inventory'])) {
                    continue;
                }

                // Try multiple possible field names for latitude/longitude
                $lat = $host_info['inventory']['location_lat'] 
                    ?? $host_info['inventory']['location_latitude'] 
                    ?? $host_info['inventory']['latitude'] 
                    ?? null;
                
                $lon = $host_info['inventory']['location_lon'] 
                    ?? $host_info['inventory']['location_longitude'] 
                    ?? $host_info['inventory']['longitude'] 
                    ?? null;

                if (!$lat || !$lon) continue;

                $problems = API::Problem()->get([
                    'output' => ['eventid', 'severity', 'acknowledged'],
                    'hostids' => $host['hostid'],
                    'recent' => true,
                    'suppressed' => false
                ]);

                $problem_count = 0;
                $unacknowledged_count = 0;
                $max_severity = 0;

                foreach ($problems as $problem) {
                    if ($problem['severity'] >= $min_severity) {
                        $problem_count++;
                        if (isset($problem['acknowledged']) && $problem['acknowledged'] == 0) {
                            $unacknowledged_count++;
                        }
                        if ($problem['severity'] > $max_severity) {
                            $max_severity = $problem['severity'];
                        }
                    }
                }

                $result_hosts[] = [
                    'groupid' => $groupid,
                    'groupname' => $group['name'],
                    'hostid' => $host['hostid'],
                    'hostname' => $host['name'],
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'max_severity' => $max_severity,
                    'maintenance_status' => $host['maintenance_status'],
                    'problem_count' => $problem_count,
                    'unacknowledged_count' => $unacknowledged_count
                ];
            }
        }

        $severity_colors = [];
        $severity_sizes = [];
        for ($i = 0; $i <= 5; $i++) {
            $severity_colors[$i] = '#'.($this->fields_values['severity_color_'.$i] ?? '97AAB3');
            $severity_sizes[$i] = intval($this->fields_values['severity_size_'.$i] ?? 20);
        }

        $this->setResponse(new CControllerResponseData([
            'name' => $this->getInput('name', $this->widget->getDefaultName()),
            'hosts' => $result_hosts,
            'maptiler_key' => $this->fields_values['maptiler_key'] ?? '',
            'style_url' => $this->fields_values['style_url'] ?? '',
            'severity_colors' => $severity_colors,
            'severity_sizes' => $severity_sizes,
            'user' => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }
}
