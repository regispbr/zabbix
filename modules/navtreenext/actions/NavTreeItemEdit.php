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

class NavTreeItemEdit extends CController {

	protected function init(): void {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$fields = [
			'name' => 'required|string',
			'link_type' => 'required|in 0,1,2',
			'sysmapid' => 'db sysmaps.sysmapid',
			'dashboardid' => 'db dashboard.dashboardid',
			'url' => 'string',
			'depth' => 'required|ge 1|le '.Widget::MAX_DEPTH
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

		$sysmap = [];
		$dashboard = [];

		if ($link_type == 0 && $sysmapid != 0) {
			$sysmaps = API::Map()->get([
				'output' => ['sysmapid', 'name'],
				'sysmapids' => [$sysmapid]
			]);

			if ($sysmaps) {
				$sysmap = $sysmaps[0];
				$sysmap = [
					'id' => $sysmap['sysmapid'],
					'name' => $sysmap['name']
				];
			}
			else {
				$sysmap = [
					'id' => $sysmapid,
					'name' => _('Inaccessible map'),
					'inaccessible' => true
				];
			}
		}
		elseif ($link_type == 1 && $dashboardid != 0) {
			$dashboards = API::Dashboard()->get([
				'output' => ['dashboardid', 'name'],
				'dashboardids' => [$dashboardid]
			]);

			if ($dashboards) {
				$dashboard = $dashboards[0];
				$dashboard = [
					'id' => $dashboard['dashboardid'],
					'name' => $dashboard['name']
				];
			}
			else {
				$dashboard = [
					'id' => $dashboardid,
					'name' => _('Inaccessible dashboard'),
					'inaccessible' => true
				];
			}
		}

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name'),
			'link_type' => $link_type,
			'sysmap' => $sysmap,
			'dashboard' => $dashboard,
			'url' => $url,
			'depth' => $this->getInput('depth'),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
