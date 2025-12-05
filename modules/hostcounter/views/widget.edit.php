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

$form = new CWidgetFormView($data);

if (array_key_exists('hostgroups', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['hostgroups'])
	);
}

if (array_key_exists('hosts', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hosts'])
	);
}

if (array_key_exists('count_problems', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_problems'])
	);
}

if (array_key_exists('count_items', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_items'])
	);
}

if (array_key_exists('count_triggers', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_triggers'])
	);
}

if (array_key_exists('count_disabled', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_disabled'])
	);
}

if (array_key_exists('count_maintenance', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['count_maintenance'])
	);
}

if (array_key_exists('show_suppressed', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_suppressed'])
	);
}

if (array_key_exists('custom_icon', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['custom_icon'])
	);
}

$form->show();
