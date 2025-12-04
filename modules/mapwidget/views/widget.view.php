<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
** ... (GNU license header) ...
*/

/**
 * Map widget view.
 *
 * @var CView $this
 * @var array $data
 */

// Criamos o contêiner principal do widget
$widget_view = (new CWidgetView($data));

// 1. Adicionamos o contêiner do mapa
$widget_view->addItem(
	(new CDiv())
		->addClass('map-widget-container') 
		->addStyle('width: 100%; height: 100%;')
);

// 2. Adicionamos o container da UI (Pesquisa e Botões)
$search_container = (new CDiv())
	->addClass('map-search-container')
	// Item 1: A caixa de input
	->addItem(
		(new CInput('text', 'map_search_input'))
			->setAttribute('placeholder', 'Pesquisar host...')
	)
	// Item 2: Os resultados da pesquisa
	->addItem(
		(new CDiv())->addClass('map-search-results')
	)
	// Item 3: O botão "Show Problems"
	->addItem(
		(new CButton('show_problems_btn', _('Show Problems')))
			->addClass('map-show-problems-btn')
	)
	// Item 4: O botão "Toggle Problems"
	->addItem(
		(new CButton('filter_problems_btn', _('Show Only Problems')))
			->addClass('map-filter-problems-btn')
	);
	
$widget_view->addItem($search_container);

// 3. ADICIONAMOS O MODAL (POPUP) ESCONDIDO
$modal_content = (new CDiv())
	->addClass('map-problems-modal-content')
	// Botão de fechar
	->addItem(
		(new CButton('map_problems_modal_close', '×'))
			->addClass('map-problems-modal-close')
	)
	// Cabeçalho
	->addItem(
		(new CDiv(_('All Problems')))
			->addClass('map-problems-modal-header')
	)
	// Corpo
	->addItem(
		(new CDiv())
			->addClass('map-problems-modal-body')
	);
$widget_view->addItem(
	(new CDiv($modal_content))
		->setId('map-problems-modal')
		->addClass('map-problems-modal-overlay')
);

// Passa todas as variáveis para o JavaScript
$widget_view
	->setVar('map_id', $data['map_id'])
	->setVar('map_key', $data['map_key'])
	->setVar('zoom', $data['zoom'])
	->setVar('center_lat', $data['center_lat'])
	->setVar('center_lng', $data['center_lng'])
	->setVar('bearing', $data['bearing'])
	->setVar('pitch', $data['pitch'])
	->setVar('zabbix_hosts', $data['zabbix_hosts']) // Para os pins
	->setVar('severity_colors', $data['severity_colors'])
	->setVar('severity_names', $data['severity_names']) // Adicionado
	->setVar('detailed_problems', $data['detailed_problems']) // Para o modal
	
	// --- MUDANÇA AQUI: ESTA LINHA CORRIGE O CRASH ---
	->setVar('fields_values', $data['fields_values'])
	// --- FIM DA MUDANÇA ---
	
	->show();
