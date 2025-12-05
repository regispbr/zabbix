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
			'js' => ['class.widget.js'],
			'css' => ['style.css'],
			'edit_js' => ['widget.edit.js']
		];
	}

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Host Counter' => _('Host Counter'),
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
