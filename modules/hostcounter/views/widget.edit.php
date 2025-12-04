<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
*/

/**
 * Host Counter widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\HostCounter\Includes\WidgetForm;

$form = new CWidgetFormView($data);

// Host groups field
if (array_key_exists('hostgroups', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['hostgroups'])
	);
}

// Hosts field
if (array_key_exists('hosts', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hosts'])
	);
}

// Count problems field
if (array_key_exists('count_problems', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_problems'])
	);
}

// Count items field
if (array_key_exists('count_items', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_items'])
	);
}

// Count triggers field
if (array_key_exists('count_triggers', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_triggers'])
	);
}

// Count disabled hosts field
if (array_key_exists('count_disabled', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_disabled'])
	);
}

// Count maintenance hosts field
if (array_key_exists('count_maintenance', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_maintenance'])
	);
}

// Show suppressed problems field
if (array_key_exists('show_suppressed', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_suppressed'])
	);
}

// Custom icon field
if (array_key_exists('custom_icon', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['custom_icon'])
	);
}

$form->show();
