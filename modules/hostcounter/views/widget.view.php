<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

/**
 * Host Counter widget view.
 *
 * @var CView $this
 * @var array $data
 */

// Custom icon display
$icon_html = '';
if (!empty($data['custom_icon']) && file_exists(__DIR__ . '/../assets/' . $data['custom_icon'])) {
	$icon_path = 'modules/hostcounter/assets/' . $data['custom_icon'];
	$icon_html = (new CImg($icon_path))
		->addClass('hostcounter-icon')
		->addStyle('max-width: 48px; max-height: 48px; position: absolute; top: 5px; right: 5px; z-index: 10;');
}

// Build counters display
$counters = [];
$counts = $data['counts'] ?? [];

// Always show total hosts
$counters[] = [
	'label' => _('Total de Hosts'),
	'value' => $counts['total_hosts'] ?? 0,
	'class' => 'counter-total'
];

$counters[] = [
	'label' => _('Hosts Ativos'),
	'value' => $counts['active_hosts'] ?? 0,
	'class' => 'counter-active'
];

if ($data['count_disabled']) {
	$counters[] = [
		'label' => _('Hosts Desativados'),
		'value' => $counts['disabled_hosts'] ?? 0,
		'class' => 'counter-disabled'
	];
}

if ($data['count_maintenance']) {
	$counters[] = [
		'label' => _('Hosts em Manutenção'),
		'value' => $counts['maintenance_hosts'] ?? 0,
		'class' => 'counter-maintenance'
	];
}

if ($data['count_problems']) {
	$counters[] = [
		'label' => _('Problemas'),
		'value' => $counts['total_problems'] ?? 0,
		'class' => 'counter-problems'
	];
}

if ($data['count_items']) {
	$counters[] = [
		'label' => _('Itens'),
		'value' => $counts['total_items'] ?? 0,
		'class' => 'counter-items'
	];
}

if ($data['count_triggers']) {
	$counters[] = [
		'label' => _('Triggers'),
		'value' => $counts['total_triggers'] ?? 0,
		'class' => 'counter-triggers'
	];
}

// Build widget content
$widget_content = new CDiv();

if ($icon_html) {
	$widget_content->addItem($icon_html);
}

// Counter grid
$counter_grid = new CDiv();
$counter_grid->addClass('hostcounter-grid');

foreach ($counters as $counter) {
	$counter_item = new CDiv([
		new CDiv($counter['value'])
			->addClass('counter-value')
			->addStyle('font-size: 24px; font-weight: bold; text-align: center;'),
		new CDiv($counter['label'])
			->addClass('counter-label')
			->addStyle('font-size: 12px; text-align: center; margin-top: 5px;')
	]);
	$counter_item->addClass('counter-item ' . $counter['class']);
	$counter_item->addStyle('
		display: inline-block;
		margin: 5px;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 4px;
		min-width: 80px;
		background: #f9f9f9;
		transition: all 0.3s ease;
	');
	
	$counter_grid->addItem($counter_item);
}

$widget_content->addItem($counter_grid);
$widget_content->addClass('hostcounter-container');

(new CWidgetView($data))
	->addItem($widget_content)
	->setVar('counts', $counts)
	->setVar('fields_values', $data['fields_values'])
	->show();
