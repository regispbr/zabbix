<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
** ... (Licença) ...
**/

/**
 * Alarm widget view.
 *
 * @var CView $this
 * @var array $data
 */

// Build table headers
$headers = [];
$column_labels = [
	'host' => _('Host'),
	'severity' => _('Severity'),
	'status' => _('Status'),
	'problem' => _('Problem'),
	'operational_data' => _('Operational data'),
	'ack' => _('Ack'),
	'age' => _('Age'),
	'time' => _('Time')
];

// O 'sort_by' (0, 1, 2) vem do fields_values
$sort_by_int = (int)($data['fields_values']['sort_by'] ?? 0);
$sort_by_map = [ 0 => 'time', 1 => 'severity', 2 => 'host' ];
$default_sort_column = $sort_by_map[$sort_by_int] ?? 'time';

foreach ($data['show_columns'] as $column) {
	if (isset($column_labels[$column])) {
		$header = (new CColHeader($column_labels[$column]))
			->addClass('alarm-header-' . $column)
			->setAttribute('data-column', $column);
		
		// Adiciona a classe de sort padrão
		if ($column == $default_sort_column) {
			$header->addClass($column == 'host' ? 'sort-asc' : 'sort-desc');
		}
		
		$headers[] = $header;
	}
}

// Create table
$table = (new CTableInfo())->setHeader($headers);

// Severity mappings
$severity_classes = [
	0 => 'alarm-severity-not-classified',
	1 => 'alarm-severity-information',
	2 => 'alarm-severity-warning',
	3 => 'alarm-severity-average',
	4 => 'alarm-severity-high',
	5 => 'alarm-severity-disaster'
];

$severity_names = [
	0 => _('Not classified'),
	1 => _('Information'),
	2 => _('Warning'),
	3 => _('Average'),
	4 => _('High'),
	5 => _('Disaster')
];

if (empty($data['problems'])) {
	$table->setNoDataMessage(_('No problems found'));
} else {
	foreach ($data['problems'] as $problem) {
		$row = [];
		
		foreach ($data['show_columns'] as $column) {
			switch ($column) {
				case 'host':
					$row[] = (new CCol($problem['hostname']))
						->setAttribute('data-sort-value', $problem['hostname']);
					break;
					
				case 'severity':
					$severity = $problem['severity'];
					$severity_class = $severity_classes[$severity] ?? 'alarm-severity-not-classified';
					$severity_name = $severity_names[$severity] ?? _('Not classified');
					$row[] = (new CCol($severity_name))
						->addClass($severity_class)
						->addClass('alarm-severity')
						->setAttribute('data-sort-value', $severity);
					break;
					
				case 'status':
					$status_class = ($problem['status'] == 'PROBLEM') ? 'alarm-status-problem' : 'alarm-status-resolved';
					$row[] = (new CCol($problem['status']))
						->addClass($status_class)
						->setAttribute('data-sort-value', $problem['status']);
					break;
					
				case 'problem':
					$row[] = new CCol($problem['name']);
					break;
					
				case 'operational_data':
					$row[] = new CCol($problem['operational_data']);
					break;
					
				case 'ack':
					$ack_col = new CCol();
					$ack_col->addClass('alarm-ack-cell');
					
					// --- MUDANÇA: Ícone de Suprimido (Olho Cortado) ---
					if (isset($problem['suppressed']) && $problem['suppressed'] == 1) {
						$sup_icon = (new CSpan())
							->addClass(ZBX_ICON_EYE_OFF) // Classe nativa do Zabbix
							->addClass('alarm-ack-icon') // Para manter o estilo
							->setTitle(_('Suppressed'));
						$ack_col->addItem($sup_icon);
						
						// Adiciona um pequeno espaço se houver Ack também
						if ($problem['ack_count'] > 0) {
							$ack_col->addItem(' '); 
						}
					}
					// --------------------------------------------------

					if ($problem['ack_count'] > 0) {
						// Adiciona o ícone de "check"
						$ack_icon = (new CSpan('✔'))
							->addClass('alarm-ack-icon')
							->setTitle(_('Acknowledged'));
						$ack_col->addItem($ack_icon);
					}

					// Adiciona o botão de Acknowledge/Update
					$button_text = ($problem['ack_count'] > 0) ? _('Update') : _('Ack');
					$ack_button = (new CLink($button_text))
						->addClass('alarm-ack-btn')
						->onClick('acknowledgePopUp({eventids: [\''.$problem['eventid'].'\']}); event.stopPropagation(); return false;');
					
					$ack_col->addItem($ack_button);
					$row[] = $ack_col;
					break;
					
				case 'age':
					$row[] = (new CCol($problem['age']))
						->setAttribute('data-sort-value', $problem['age_seconds']);
					break;
					
				case 'time':
					$row[] = (new CCol($problem['time']))
						->setAttribute('data-sort-value', $problem['clock']);
					break;
			}
		}
		
		$table->addRow($row);
	}
}

(new CWidgetView($data))
	->addItem($table)
	->setVar('problems', $data['problems'])
	->setVar('show_columns', $data['show_columns'])
	->setVar('refresh_interval', $data['refresh_interval'])
	->setVar('fields_values', $data['fields_values'])
	->show();
