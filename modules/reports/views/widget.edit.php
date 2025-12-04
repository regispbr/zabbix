<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/

/**
 * Reports widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\Reports\Includes\WidgetForm;

$form = new CWidgetFormView($data);

// Report Configuration
$form->addFieldsetHeader(_('Report Configuration'));

if (array_key_exists('report_title', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['report_title'])
	);
}

if (array_key_exists('report_types', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxListView($data['fields']['report_types'])
	);
}

if (array_key_exists('display_format', $data['fields'])) {
	$form->addField(
		new CWidgetFieldRadioButtonListView($data['fields']['display_format'])
	);
}

// Time Period
$form->addFieldsetHeader(_('Time Period'));

if (array_key_exists('date_from', $data['fields'])) {
	$form->addField(
		new CWidgetFieldDatePickerView($data['fields']['date_from'])
	);
}

if (array_key_exists('date_to', $data['fields'])) {
	$form->addField(
		new CWidgetFieldDatePickerView($data['fields']['date_to'])
	);
}

// Filters
$form->addFieldsetHeader(_('Filters'));

if (array_key_exists('host_groups', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['host_groups'])
	);
}

if (array_key_exists('hosts', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hosts'])
	);
}

if (array_key_exists('items', $data['fields'])) {
	$form->addField(
		new CWidgetFieldMultiSelectItemView($data['fields']['items'])
	);
}

if (array_key_exists('tags', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTagsView($data['fields']['tags'])
	);
}

if (array_key_exists('alert_severities', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxListView($data['fields']['alert_severities'])
	);
}

// Chart Configuration
$form->addFieldsetHeader(_('Chart Configuration'));

if (array_key_exists('show_charts', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_charts'])
	);
}

if (array_key_exists('chart_type', $data['fields'])) {
	$form->addField(
		new CWidgetFieldSelectView($data['fields']['chart_type'])
	);
}

// Advanced Options
$form->addFieldsetHeader(_('Advanced Options'));

if (array_key_exists('jrxml_template', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['jrxml_template'])
	);
}

if (array_key_exists('show_page_numbers', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_page_numbers'])
	);
}

if (array_key_exists('show_header', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_header'])
	);
}

if (array_key_exists('show_footer', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_footer'])
	);
}

$form->show();