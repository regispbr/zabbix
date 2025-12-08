<?php declare(strict_types = 0);
/*
** Host Group Alarms widget view.
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
	
	$alarm_text = $data['total_alarms'] . ' ' . ($data['total_alarms'] == 1 ? _('alarm') : _('alarms'));
	$content_items[] = (new CDiv($alarm_text))
		->addClass('hostgroup-alarms-count')
		->addStyle('font-size: ' . $data['font_size'] . 'px;');
		
	// --- ÍCONES NO CARD PRINCIPAL (Sua ideia) ---
	$has_suppressed = false;
	$has_acked = false;
	
	if (!empty($data['detailed_alarms'])) {
		foreach ($data['detailed_alarms'] as $alarm) {
			if ($alarm['suppressed'] == 1) $has_suppressed = true;
			if ($alarm['acknowledged'] == 1) $has_acked = true;
		}
	}
	
	$icons_div = (new CDiv())->addClass('hostgroup-alarms-icons')->addStyle('position: absolute; bottom: 5px; right: 5px; font-size: 14px;');
	
	if ($has_suppressed) {
		$icons_div->addItem((new CSpan())->addClass(ZBX_ICON_EYE_OFF)->setTitle(_('Contains suppressed problems'))->addStyle('margin-left: 5px; cursor: help;'));
	}
	if ($has_acked) {
		$icons_div->addItem((new CSpan('✔'))->setTitle(_('Contains acknowledged problems'))->addStyle('margin-left: 5px; cursor: help;'));
	}
	
	if ($has_suppressed || $has_acked) {
		$content_items[] = $icons_div;
	}
	// --------------------------------------------
}

// Tooltip
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
		
		// --- STATUS NO TOOLTIP (Com ícones) ---
		$tooltip_html .= '<div class="tooltip-status">';
		if ($alarm['suppressed']) {
			$tooltip_html .= '<span class="' . ZBX_ICON_EYE_OFF . '" title="' . _('Suppressed') . '" style="margin-right: 5px;"></span>';
		}
		if ($alarm['acknowledged']) {
			$tooltip_html .= '<span style="margin-right: 5px;">✔</span>';
		}
		$tooltip_html .= $ack_status . '</div>';
		// --------------------------------------
		
		if ($alarm['eventid']) {
			$tooltip_html .= '<div class="tooltip-actions">';
			$tooltip_html .= '<a href="tr_events.php?triggerid=' . $alarm['triggerid'] . '&eventid=' . $alarm['eventid'] . '" target="_blank">View Event</a>';
			if (!$alarm['acknowledged']) {
				// Usa o javascript nativo para popup de ack
				$tooltip_html .= ' | <a href="#" onclick="acknowledgePopUp({eventids: [\'' . $alarm['eventid'] . '\']}); return false;">Acknowledge</a>';
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
