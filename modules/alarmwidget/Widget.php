<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
** ... (Licença) ...
**/

namespace Modules\AlarmWidget;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getAssets(): array {
		return [
			// --- MUDANÇA AQUI: Caminhos corrigidos ---
			'js' => ['class.widget.js'],
			'css' => ['style.css'],
			'edit_js' => ['widget.edit.js']
			// --- FIM DA MUDANÇA ---
		];
	}

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Alarm Widget' => _('Alarm Widget'),
				'Host' => _('Host'),
				'Severity' => _('Severity'),
				'Status' => _('Status'),
				'Problem' => _('Problem'),
				'Operational data' => _('Operational data'),
				'Ack' => _('Ack'),
				'Age' => _('Age'),
				'Time' => _('Time'),
				'No problems found' => _('No problems found'),
				'Loading...' => _('Loading...'),
				'Disaster' => _('Disaster'),
				'High' => _('High'),
				'Average' => _('Average'),
				'Warning' => _('Warning'),
				'Information' => _('Information'),
				'Not classified' => _('Not classified')
			]
		];
	}
}
