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
 * Host Group Alarms widget view.
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
		->addClass('hostgroup-alarms-title')
		->addStyle('font-weight: bold; margin-bottom: 8px; font-size: ' . ($data['font_size'] + 2) . 'px;');
}

// Alarm status
if ($data['total_alarms'] == 0) {
	$content_items[] = (new CDiv(_('OK')))
		->addClass('hostgroup-alarms-status')
		->addStyle('font-size: ' . ($data['font_size'] + 4) . 'px; font-weight: bold; margin-bottom: 4px;');
	$content_items[] = (new CDiv(_('No alarms')))
		->addClass('hostgroup-alarms-count')
		->addStyle('font-size: ' . ($data['font_size'] - 2) . 'px; opacity: 0.8;');
} else {
	// Show severity name
	$severity_names = [
		0 => _('Not classified'),
		1 => _('Information'),
		2 => _('Warning'),
		3 => _('Average'),
		4 => _('High'),
		5 => _('Disaster')
	];
	
	$severity_name = $severity_names[$data['highest_severity']] ?? _('Unknown');
	
	$content_items[] = (new CDiv($severity_name))
		->addClass('hostgroup-alarms-severity')
		->addStyle('font-size: ' . ($data['font_size'] + 2) . 'px; font-weight: bold; margin-bottom: 4px;');
	
	// Show alarm count
	$alarm_text = $data['total_alarms'] . ' ' . ($data['total_alarms'] == 1 ? _('alarm') : _('alarms'));
	$content_items[] = (new CDiv($alarm_text))
		->addClass('hostgroup-alarms-count')
		->addStyle('font-size: ' . $data['font_size'] . 'px;');
}

// Create tooltip content if enabled and there are alarms
$tooltip_content = '';
if ($data['show_detailed_tooltip'] && $data['total_alarms'] > 0 && !empty($data['detailed_alarms'])) {
	$tooltip_items = array_slice($data['detailed_alarms'], 0, $data['tooltip_max_items']);
	
	$tooltip_html = '<div class="hostgroup-alarms-tooltip">';
	$tooltip_html .= '<div class="tooltip-header">Alarm Details (' . $data['total_alarms'] . ' total)</div>';
	
	foreach ($tooltip_items as $alarm) {
		$severity_class = 'severity-' . $alarm['severity'];
		$time_formatted = date('Y-m-d H:i:s', $alarm['clock']);
		$ack_status = $alarm['acknowledged'] ? 'Acknowledged' : 'Not acknowledged';
		
		$tooltip_html .= '<div class="tooltip-item ' . $severity_class . '">';
		$tooltip_html .= '<div class="tooltip-time">' . $time_formatted . '</div>';
		$tooltip_html .= '<div class="tooltip-severity">' . $alarm['severity_name'] . '</div>';
		$tooltip_html .= '<div class="tooltip-host">' . htmlspecialchars($alarm['host_name']) . '</div>';
		$tooltip_html .= '<div class="tooltip-description">' . htmlspecialchars($alarm['description']) . '</div>';
		$tooltip_html .= '<div class="tooltip-status">' . $ack_status . '</div>';
		
		if ($alarm['eventid']) {
			$tooltip_html .= '<div class="tooltip-actions">';
			$tooltip_html .= '<a href="tr_events.php?triggerid=' . $alarm['triggerid'] . '&eventid=' . $alarm['eventid'] . '" target="_blank">View Event</a>';
			if (!$alarm['acknowledged']) {
				$tooltip_html .= ' | <a href="acknow.php?eventid=' . $alarm['eventid'] . '" target="_blank">Acknowledge</a>';
			}
			$tooltip_html .= '</div>';
		}
		
		$tooltip_html .= '</div>';
	}
	
	if (count($data['detailed_alarms']) > $data['tooltip_max_items']) {
		$remaining = count($data['detailed_alarms']) - $data['tooltip_max_items'];
		$tooltip_html .= '<div class="tooltip-more">... and ' . $remaining . ' more alarms</div>';
	}
	
	$tooltip_html .= '</div>';
	$tooltip_content = $tooltip_html;
}

$main_container = (new CDiv($content_items))
	->addClass('hostgroup-alarms-container')
	->addStyle($style_string);

// Add tooltip attribute if content exists
if (!empty($tooltip_content)) {
	$main_container->setAttribute('data-tooltip', $tooltip_content);
}

(new CWidgetView($data))
	->addItem($main_container)
	->setVar('alarm_data', [
		'total_alarms' => $data['total_alarms'],
		'highest_severity' => $data['highest_severity'],
		'group_name' => $data['group_name'],
		'detailed_alarms' => $data['detailed_alarms']
	])
	->setVar('widget_config', [
		'enable_url_redirect' => $data['enable_url_redirect'],
		'redirect_url' => $data['redirect_url'],
		'open_in_new_tab' => $data['open_in_new_tab'],
		'show_detailed_tooltip' => $data['show_detailed_tooltip']
	])
	->setVar('fields_values', $data['fields_values'])
	->show();