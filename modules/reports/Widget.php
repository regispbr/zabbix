<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
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

namespace Modules\Reports;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Reports Generator' => _('Reports Generator'),
				'Availability' => _('Availability'),
				'Performance' => _('Performance'),
				'Alerts' => _('Alerts'),
				'All Reports' => _('All Reports'),
				'Operational Format' => _('Operational Format'),
				'Executive Format' => _('Executive Format'),
				'Generate Report' => _('Generate Report'),
				'Export to PDF' => _('Export to PDF'),
				'No data available for selected filters' => _('No data available for selected filters'),
				'Loading report data...' => _('Loading report data...'),
				'Report generated successfully' => _('Report generated successfully')
			]
		];
	}
}