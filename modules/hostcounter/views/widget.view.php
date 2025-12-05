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

$icon_html = '';
if (!empty($data['custom_icon'])) {
	$icon_html = (new CDiv('📊'))
		->addClass('hostcounter-icon')
		->addStyle('position: absolute; top: 5px; right: 5px; font-size: 24px; z-index: 10;');
}

$counters = [];
$counts = $data['counts'] ?? [];

$counters[] = [
	'label' => _('Total Hosts'),
	'value' => $counts['total_hosts'] ?? 0,
	'class' => 'counter-total'
];

$counters[] = [
	'label' => _('Active Hosts'),
	'value' => $counts['active_hosts'] ?? 0,
	'class' => 'counter-active'
];

if ($data['count_disabled']) {
	$counters[] = [
		'label' => _('Disabled Hosts'),
		'value' => $counts['disabled_hosts'] ?? 0,
		'class' => 'counter-disabled'
	];
}

if ($data['count_maintenance']) {
	$counters[] = [
		'label' => _('Maintenance Hosts'),
		'value' => $counts['maintenance_hosts'] ?? 0,
		'class' => 'counter-maintenance'
	];
}

if ($data['count_problems']) {
	$counters[] = [
		'label' => _('Problems'),
		'value' => $counts['total_problems'] ?? 0,
		'class' => 'counter-problems'
	];
}

if ($data['count_items']) {
	$counters[] = [
		'label' => _('Items'),
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

$widget_content = new CDiv();

if ($icon_html) {
	$widget_content->addItem($icon_html);
}

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
