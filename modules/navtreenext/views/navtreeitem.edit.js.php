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
?>


window.navtreeitem_edit_popup = new class {

	init() {
		const $sysmap = jQuery('#sysmapid');
		const $dashboard = jQuery('#dashboardid');
		const name_input = document.getElementById('name');
		const link_type_radios = document.querySelectorAll('input[name="link_type"]');
		const map_field = document.getElementById('map-field');
		const dashboard_field = document.getElementById('dashboard-field');
		const url_field = document.getElementById('url-field');
		const add_submaps_field = document.getElementById('add-submaps-field');

		// Auto-fill name when map is selected
		$sysmap.on('change', () => {
			if (name_input.value === '') {
				const sysmaps = $sysmap.multiSelect('getData');
				name_input.value = sysmaps.length ? sysmaps[0]['name'] : '';
			}
		});

		// Auto-fill name when dashboard is selected
		$dashboard.on('change', () => {
			if (name_input.value === '') {
				const dashboards = $dashboard.multiSelect('getData');
				name_input.value = dashboards.length ? dashboards[0]['name'] : '';
			}
		});

		// Handle link type change
		link_type_radios.forEach(radio => {
			radio.addEventListener('change', (e) => {
				const link_type = parseInt(e.target.value);

				// Show/hide appropriate fields
				map_field.style.display = link_type === 0 ? '' : 'none';
				dashboard_field.style.display = link_type === 1 ? '' : 'none';
				url_field.style.display = link_type === 2 ? '' : 'none';
				
				if (add_submaps_field) {
					add_submaps_field.style.display = link_type === 0 ? '' : 'none';
				}
			});
		});
	}
};