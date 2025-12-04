<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
** ... (Licença) ...
**/

/**
 * Alarm widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\AlarmWidget\Includes\WidgetForm;

$form = new CWidgetFormView($data);

// Host groups field
if (array_key_exists('groupids', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
	);
}

// Hosts field
if (array_key_exists('hostids', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hostids'])
	);
}

// Exclude hosts field
if (array_key_exists('exclude_hostids', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['exclude_hostids'])
	);
}

// Severity field
if (array_key_exists('severities', $data['fields'])) {
	$form->addField(
		new CWidgetFieldSeveritiesView($data['fields']['severities'])
	);
}

// Exclude maintenance field
if (array_key_exists('exclude_maintenance', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['exclude_maintenance'])
	);
}

// Tags evaluation type
if (array_key_exists('evaltype', $data['fields'])) {
    $form->addField(
        new CWidgetFieldRadioButtonListView($data['fields']['evaltype'])
    );
}

// Tags
if (array_key_exists('tags', $data['fields'])) {
    $form->addField(
        new CWidgetFieldTagsView($data['fields']['tags'])
    );
}

// Problem status field
if (array_key_exists('problem_status', $data['fields'])) {
	$form->addField(
		new CWidgetFieldRadioButtonListView($data['fields']['problem_status'])
	);
}

// Show acknowledged field
if (array_key_exists('show_ack', $data['fields'])) {
	$form->addField(
		new CWidgetFieldRadioButtonListView($data['fields']['show_ack'])
	);
}

// --- MUDANÇA 1: RENDERIZA O FILTRO "SUPPRESSED" ---
if (array_key_exists('show_suppressed', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_suppressed'])
	);
}
// --- FIM DA MUDANÇA ---

// --- MUDANÇA 2: RENDERIZA O FILTRO "SORT BY" ---
if (array_key_exists('sort_by', $data['fields'])) {
	$form->addField(
		new CWidgetFieldRadioButtonListView($data['fields']['sort_by'])
	);
}
// --- FIM DA MUDANÇA ---


// Column visibility checkboxes
if (array_key_exists('show_column_host', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_host'])
	);
}

if (array_key_exists('show_column_severity', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_severity'])
	);
}

if (array_key_exists('show_column_status', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_status'])
	);
}

if (array_key_exists('show_column_problem', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_problem'])
	);
}

if (array_key_exists('show_column_operational_data', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_operational_data'])
	);
}

if (array_key_exists('show_column_ack', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_ack'])
	);
}

if (array_key_exists('show_column_age', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_age'])
	);
}

if (array_key_exists('show_column_time', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_column_time'])
	);
}

// Refresh interval field
if (array_key_exists('refresh_interval', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['refresh_interval'])
	);
}

// Show lines field
if (array_key_exists('show_lines', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['show_lines'])
	);
}

$form->show();
