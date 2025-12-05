<?php declare(strict_types = 0);

use Modules\HostGroupStatus\Includes\WidgetForm;

/**
 * Host Group Status widget form view.
 *
 * @var CView $this
 * @var array $data
 */

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

// Exclude Hosts field
if (array_key_exists('exclude_hosts', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['exclude_hosts'])
	);
}

// --- MUDANÇA: Novo campo de Severidade Compacto ---
if (array_key_exists('severities', $data['fields'])) {
	$form->addField(
		new CWidgetFieldSeveritiesView($data['fields']['severities'])
	);
}
// --- FIM DA MUDANÇA ---

// Problem tags (evaltype) field
if (array_key_exists('evaltype', $data['fields'])) {
	$form->addField(
		new CWidgetFieldRadioButtonListView($data['fields']['evaltype'])
	);
}

// Problem tags (tags) field
if (array_key_exists('tags', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTagsView($data['fields']['tags'])
	);
}

// Show acknowledged field
if (array_key_exists('show_acknowledged', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_acknowledged'])
	);
}

// Show suppressed field
if (array_key_exists('show_suppressed', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_suppressed'])
	);
}

// Exclude maintenance
if (array_key_exists('exclude_maintenance', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['exclude_maintenance'])
	);
}

// Count mode field
if (array_key_exists('count_mode', $data['fields'])) {
	$form->addField(
		new CWidgetFieldRadioButtonListView($data['fields']['count_mode'])
	);
}

// Widget color field
if (array_key_exists('widget_color', $data['fields'])) {
	$form->addField(
		new CWidgetFieldColorView($data['fields']['widget_color'])
	);
}

// Show group name field
if (array_key_exists('show_group_name', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_group_name'])
	);
}

// Custom group name field
if (array_key_exists('group_name_text', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['group_name_text'])
	);
}

// URL redirect settings
if (array_key_exists('enable_url_redirect', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['enable_url_redirect'])
	);
}

if (array_key_exists('redirect_url', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['redirect_url'])
	);
}

if (array_key_exists('open_in_new_tab', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['open_in_new_tab'])
	);
}

// Font size field
if (array_key_exists('font_size', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['font_size'])
	);
}

// Font family field
if (array_key_exists('font_family', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['font_family'])
	);
}

// Show border field
if (array_key_exists('show_border', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_border'])
	);
}

// Border width field
if (array_key_exists('border_width', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['border_width'])
	);
}

// Padding field
if (array_key_exists('padding', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['padding'])
	);
}

$form->show();
