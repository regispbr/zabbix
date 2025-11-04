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

namespace Modules\HostGroupAlarms;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Host Group Alarms' => _('Host Group Alarms'),
				'No host group selected' => _('No host group selected'),
				'No alarms' => _('No alarms'),
				'OK' => _('OK'),
				'Information' => _('Information'),
				'Warning' => _('Warning'),
				'Average' => _('Average'),
				'High' => _('High'),
				'Disaster' => _('Disaster'),
				'alarms' => _('alarms'),
				'alarm' => _('alarm'),
				'Not classified' => _('Not classified'),
				'Show group name' => _('Show group name'),
				'Custom group name' => _('Custom group name'),
				'Enable URL redirect' => _('Enable URL redirect'),
				'Redirect URL' => _('Redirect URL'),
				'Open in new tab' => _('Open in new tab'),
				'Show detailed tooltip on hover' => _('Show detailed tooltip on hover'),
				'Maximum items in tooltip' => _('Maximum items in tooltip'),
				'Show Not classified' => _('Show Not classified'),
				'Show Information' => _('Show Information'),
				'Show Warning' => _('Show Warning'),
				'Show Average' => _('Show Average'),
				'Show High' => _('Show High'),
				'Show Disaster' => _('Show Disaster'),
				'Font Size (px)' => _('Font Size (px)'),
				'Font Family' => _('Font Family'),
				'Show Border' => _('Show Border'),
				'Border Width (px)' => _('Border Width (px)'),
				'Padding (px)' => _('Padding (px)'),
				'Acknowledged' => _('Acknowledged'),
				'Not acknowledged' => _('Not acknowledged'),
				'View Event' => _('View Event'),
				'Acknowledge' => _('Acknowledge'),
				'Alarm Details' => _('Alarm Details'),
				'total' => _('total'),
				'and' => _('and'),
				'more alarms' => _('more alarms')
			]
		];
	}
}