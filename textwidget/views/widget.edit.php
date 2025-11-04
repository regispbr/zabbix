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
 * Text widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\TextWidget\Includes\WidgetForm;

$form = new CWidgetFormView($data);

// Text content field
if (array_key_exists('text_content', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextAreaView($data['fields']['text_content'])
	);
}

// Font size field
if (array_key_exists('font_size', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['font_size'])
	);
}

// Font color field
if (array_key_exists('font_color', $data['fields'])) {
	$form->addField(
		new CWidgetFieldColorView($data['fields']['font_color'])
	);
}

// Background color field
if (array_key_exists('background_color', $data['fields'])) {
	$form->addField(
		new CWidgetFieldColorView($data['fields']['background_color'])
	);
}

// Font family field
if (array_key_exists('font_family', $data['fields'])) {
	$form->addField(
		new CWidgetFieldTextBoxView($data['fields']['font_family'])
	);
}

// Text alignment field
if (array_key_exists('text_align', $data['fields'])) {
	$form->addField(
		new CWidgetFieldSelectView($data['fields']['text_align'])
	);
}

// Font weight field
if (array_key_exists('font_weight', $data['fields'])) {
	$form->addField(
		new CWidgetFieldSelectView($data['fields']['font_weight'])
	);
}

// Font style field
if (array_key_exists('font_style', $data['fields'])) {
	$form->addField(
		new CWidgetFieldSelectView($data['fields']['font_style'])
	);
}

// Line height field
if (array_key_exists('line_height', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['line_height'])
	);
}

// Padding field
if (array_key_exists('padding', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['padding'])
	);
}

// Show border field
if (array_key_exists('show_border', $data['fields'])) {
	$form->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_border'])
	);
}

// Border color field
if (array_key_exists('border_color', $data['fields'])) {
	$form->addField(
		new CWidgetFieldColorView($data['fields']['border_color'])
	);
}

// Border width field
if (array_key_exists('border_width', $data['fields'])) {
	$form->addField(
		new CWidgetFieldIntegerBoxView($data['fields']['border_width'])
	);
}

$form->show();
