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
 * Host Group Status widget view.
 *
 * @var CView $this
 * @var array $data
 */

// Build inline styles
$styles = [
	'font-size: ' . $data['font_size'] . 'px',
	'color: ' . $data['text_color'],
	'background-color: ' . $data['background_color'],
	'font-family: ' . $data['font_family'],
	'text-align: center',
	'padding: ' . $data['padding'] . 'px',
	'width: 100%',
	'height: 100%',
	'box-sizing: border-box',
	'display: flex',
	'flex-direction: column',
	'justify-content: center',
	'align-items: center',
	'position: relative',
	'min-height: 120px'
];

// Add border styles if enabled
if ($data['show_border']) {
	$styles[] = 'border: ' . $data['border_width'] . 'px solid ' . $data['border_color'];
	$styles[] = 'border-radius: 8px';
}

$style_string = implode('; ', $styles);

// Prepare content
$content_items = [];

// Group name
if ($data['show_group_name'] && !empty($data['group_name'])) {
	$content_items[] = (new CDiv($data['group_name']))
		->addClass('hostgroup-status-title')
		->addStyle('font-weight: bold; margin-bottom: 8px; font-size: ' . ($data['font_size'] + 2) . 'px;');
}

// Host count
$host_text = $data['host_count'] . ' ' . ($data['host_count'] == 1 ? _(' ') : _(' '));
$content_items[] = (new CDiv($host_text))
	->addClass('hostgroup-status-count')
	->addStyle('font-size: ' . ($data['font_size'] + 6) . 'px; font-weight: bold; margin-bottom: 4px;');

// Count mode label
if (!empty($data['count_mode_label'])) {
	$content_items[] = (new CDiv($data['count_mode_label']))
		->addClass('hostgroup-status-mode')
		->addStyle('font-size: ' . ($data['font_size'] - 2) . 'px; opacity: 0.9;');
}

$main_container = (new CDiv($content_items))
	->addClass('hostgroup-status-container')
	->addStyle($style_string);

(new CWidgetView($data))
	->addItem($main_container)
	->setVar('host_data', [
		'host_count' => $data['host_count'],
		'count_mode' => $data['count_mode'],
		'group_name' => $data['group_name']
	])
	->setVar('widget_config', [
		'enable_url_redirect' => $data['enable_url_redirect'],
		'redirect_url' => $data['redirect_url'],
		'open_in_new_tab' => $data['open_in_new_tab']
	])
	->setVar('fields_values', $data['fields_values'])
	->show();
