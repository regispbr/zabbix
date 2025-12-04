<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

namespace Modules\HostCounter;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getAssets(): array {
		return [
			// Arquivos para o Dashboard
			'js' => ['class.widget.js'],
			
			// A chave 'css' DEVE existir, mas pode ser vazia
			'css' => ['style.css'],
			
			// Arquivo para a tela de Edição
			'edit_js' => ['widget.edit.js']
		];
	}

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Host Counter' => _('Host Counter'),
				'No host group selected' => _('No host group selected'),
				'hosts' => _('hosts'),
				'host' => _('host'),
				'Count Problems' => _('Count Problems'),
				'Count Items' => _('Count Items'),
				'Count Triggers' => _('Count Triggers'),
				'Count Disabled Hosts' => _('Count Disabled Hosts'),
				'Count Maintenance Hosts' => _('Count Maintenance Hosts'),
				'Custom Icon' => _('Custom Icon'),
				'Total Hosts' => _('Total Hosts'),
				'Active Hosts' => _('Active Hosts'),
				'Disabled Hosts' => _('Disabled Hosts'),
				'Maintenance Hosts' => _('Maintenance Hosts'),
				'Problems' => _('Problems'),
				'Items' => _('Items'),
				'Triggers' => _('Triggers')
			]
		];
	}
}
