<?php declare(strict_types = 0);

$data = $data + [
    'name' => 'Custom Geo Map',
    'hosts' => [],
    'maptiler_key' => '',
    'style_url' => '',
    'severity_colors' => [],
    'severity_sizes' => [],
    'user' => ['debug_mode' => GROUP_DEBUG_MODE_DISABLED]
];

$output = [
    'name' => $data['name'],
    'body' => '',
    'messages' => [],
    'info' => '',
    'debug' => null
];

$widget_data = [
    'hosts' => $data['hosts'],
    'maptiler_key' => $data['maptiler_key'],
    'style_url' => $data['style_url'],
    'severity_colors' => $data['severity_colors'],
    'severity_sizes' => $data['severity_sizes']
];

$output['data'] = $widget_data;

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
    $output['debug'] = [
        'host_count' => count($data['hosts']),
        'has_maptiler_key' => !empty($data['maptiler_key']),
        'has_style_url' => !empty($data['style_url']),
        'data_received' => array_keys($data)
    ];
}

echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
