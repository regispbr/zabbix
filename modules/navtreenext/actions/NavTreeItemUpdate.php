<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


namespace Modules\NavTreeNext\Actions;

use API,
	CController,
	CControllerResponseData;

use Modules\NavTreeNext\Widget;

class NavTreeItemUpdate extends CController {

	protected function init(): void {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$fields = [
			'name' => 'required|string|not_empty',
			'link_type' => 'required|in 0,1,2',
			'sysmapid' => 'db sysmaps.sysmapid',
			'dashboardid' => 'db dashboard.dashboardid',
			'url' => 'string',
			'add_submaps' => 'in 0,1',
			'depth' => 'ge 1|le '.Widget::MAX_DEPTH
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(
				new CControllerResponseData(['main_block' => json_encode([
					'error' => [
						'messages' => array_column(get_and_clear_messages(), 'message')
					]
				], JSON_THROW_ON_ERROR)])
			);
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
	}

	protected function doAction(): void {
		$link_type = $this->getInput('link_type', 0);
		$sysmapid = $this->getInput('sysmapid', 0);
		$dashboardid = $this->getInput('dashboardid', 0);
		$url = $this->getInput('url', '');
		$add_submaps = (int) $this->getInput('add_submaps', 0);
		$depth = (int) $this->getInput('depth', 1);

		// Validate map if link_type is map
		if ($link_type == 0 && $sysmapid != 0) {
			$sysmaps = API::Map()->get([
				'output' => [],
				'sysmapids' => $sysmapid
			]);

			if (!$sysmaps) {
				$sysmapid = 0;
			}
		}

		// Validate dashboard if link_type is dashboard
		if ($link_type == 1 && $dashboardid != 0) {
			$dashboards = API::Dashboard()->get([
				'output' => [],
				'dashboardids' => $dashboardid
			]);

			if (!$dashboards) {
				$dashboardid = 0;
			}
		}

		$all_sysmapids = [];
		$hierarchy = [];

		if ($link_type == 0 && $sysmapid != 0 && $add_submaps == 1) {
			// Recursively select submaps.
			$sysmapids = [];
			$sysmapids[$sysmapid] = true;

			do {
				if ($depth++ > Widget::MAX_DEPTH) {
					break;
				}

				$sysmaps = API::Map()->get([
					'output' => ['sysmapid'],
					'selectSelements' => ['elements', 'elementtype', 'permission'],
					'sysmapids' => array_keys($sysmapids),
					'preservekeys' => true
				]);

				$all_sysmapids += $sysmapids;
				$sysmapids = [];

				foreach ($sysmaps as $sysmap) {
					foreach ($sysmap['selements'] as $selement) {
						if ($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_MAP
								&& $selement['permission'] >= PERM_READ) {
							$element = $selement['elements'][0];
							$hierarchy[$sysmap['sysmapid']][] = $element['sysmapid'];

							if (!array_key_exists($element['sysmapid'], $all_sysmapids)) {
								$sysmapids[$element['sysmapid']] = true;
							}
						}
					}
				}
			}
			while ($sysmapids);
		}

		// Prepare output.
		$response = [
			'name' => $this->getInput('name'),
			'link_type' => $link_type,
			'sysmapid' => $sysmapid,
			'dashboardid' => $dashboardid,
			'url' => $url,
			'hierarchy' => $hierarchy,
			'submaps' => $all_sysmapids
				? API::Map()->get([
					'output' => ['sysmapid', 'name'],
					'sysmapids' => array_keys($all_sysmapids),
					'preservekeys' => true
				])
				: []
		];

		$this->setResponse(new CControllerResponseData(['main_block' => json_encode($response, JSON_THROW_ON_ERROR)]));
	}
}
