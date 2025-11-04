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
 * Host Group Alarms widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\HostGroupAlarms\Includes\WidgetForm;

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

// Tooltip settings
if (array_key_exists('show_detailed_tooltip', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_detailed_tooltip'])
	);
}

if (array_key_exists('tooltip_max_items', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['tooltip_max_items'])
	);
}

// Severity filter fields
if (array_key_exists('show_not_classified', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_not_classified'])
	);
}

if (array_key_exists('show_information', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_information'])
	);
}

if (array_key_exists('show_warning', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_warning'])
	);
}

if (array_key_exists('show_average', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_average'])
	);
}

if (array_key_exists('show_high', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_high'])
	);
}

if (array_key_exists('show_disaster', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_disaster'])
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