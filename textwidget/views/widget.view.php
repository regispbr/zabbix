<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

/**
 * Text widget view.
 *
 * @var CView $this
 * @var array $data
 */

// Build inline styles
$styles = [
	'font-size: ' . $data['font_size'] . 'px',
	'color: ' . $data['font_color'],
	'background-color: ' . $data['background_color'],
	'font-family: ' . $data['font_family'],
	'text-align: ' . $data['text_align'],
	'font-weight: ' . $data['font_weight'],
	'font-style: ' . $data['font_style'],
	'line-height: ' . $data['line_height'] . '%',
	'padding: ' . $data['padding'] . 'px',
	'width: 100%',
	'height: 100%',
	'box-sizing: border-box',
	'overflow-wrap: break-word',
	'word-wrap: break-word'
];

// Add border styles if enabled
if ($data['show_border']) {
	$styles[] = 'border: ' . $data['border_width'] . 'px solid ' . $data['border_color'];
}

$style_string = implode('; ', $styles);

// Prepare text content
$text_content = $data['text_content'];
if (empty($text_content)) {
	$text_content = _('No text configured');
	$style_string .= '; opacity: 0.5; font-style: italic;';
}

// Convert newlines to HTML breaks
$text_content = nl2br(htmlspecialchars($text_content));

(new CWidgetView($data))
	->addItem(
		(new CDiv())
			->addClass('text-widget-container')
			->addStyle($style_string)
			->addItem($text_content)
	)
	->setVar('text_content', $data['text_content'])
	->setVar('fields_values', $data['fields_values'])
	->show();
