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


class CWidgetGeoMapPlus extends CWidget {

	static ZBX_STYLE_HINTBOX = 'geomapplus-hintbox';

	static SEVERITY_NO_PROBLEMS = -1;
	static SEVERITY_NOT_CLASSIFIED = 0;
	static SEVERITY_INFORMATION = 1;
	static SEVERITY_WARNING = 2;
	static SEVERITY_AVERAGE = 3;
	static SEVERITY_HIGH = 4;
	static SEVERITY_DISASTER = 5;

	static MARKER_SHAPE_CIRCLE = 0;
	static MARKER_SHAPE_RECTANGLE = 1;
	static MARKER_SHAPE_PIN = 2;
	static MARKER_SHAPE_CLOUD = 3;

	/**
	 * GeomapPlus's data from response.
	 *
	 * @type {Object|null}
	 */
	#geomapplus = null;

	/**
	 * ID of selected group
	 *
	 * @type {string|null}
	 */
	#selected_groupid = null;

	onInitialize() {
		this._map = null;
		this._icons = {};
		this._selected_icons = {};
		this._mouseover_icons = {};
		this._initial_load = true;
		this._home_coords = {};
		this._marker_shape = CWidgetGeoMapPlus.MARKER_SHAPE_CIRCLE;
		this._colors = {};
		this._marker_labels = {};
	}

	promiseReady() {
		if (this._map === null){
			return super.promiseReady();
		}

		return new Promise(resolve => {
			this._map.whenReady(() => {
				super.promiseReady()
					.then(() => setTimeout(resolve, 300));
			});
		});
	}

	getUpdateRequestData() {
		return {
			...super.getUpdateRequestData(),
			initial_load: this._initial_load ? 1 : 0,
			unique_id: this._unique_id
		};
	}

	setContents(response) {
		if (this._initial_load) {
			super.setContents(response);
		}

		if (response.geomapplus === undefined) {
			this._initial_load = false;
			return;
		}

		this.#geomapplus = response.geomapplus;

		if (this.#geomapplus.config !== undefined) {
			this._marker_shape = this.#geomapplus.config.marker_shape;
			this._colors = this.#geomapplus.config.colors;
			this._initMap(this.#geomapplus.config);
		}

		// Filter groups to show only those with alarms
		const groupsWithAlarms = this.#geomapplus.hostgroups.filter(group => 
			group.properties.alert_count > 0
		);

		this._addMarkers(groupsWithAlarms);

		if (!this.hasEverUpdated() && this.isReferred()) {
			this.#selected_groupid = this.#getDefaultSelectable();

			if (this.#selected_groupid !== null) {
				this.#updateHintboxes();
				this.#updateMarkers();
			}
		}
		else if (this.#selected_groupid !== null) {
			this.#updateHintboxes();
			this.#updateMarkers();
		}

		this._initial_load = false;
	}

	#getDefaultSelectable() {
		return this.#geomapplus.hostgroups.length > 0
			? this.#getClosestGroup(this.#geomapplus.config, this.#geomapplus.hostgroups).properties.groupid
			: null;
	}

	_addMarkers(groups) {
		this._markers.clearLayers();
		this._clusters.clearLayers();
		
		// Clear existing labels
		Object.values(this._marker_labels).forEach(label => {
			if (this._map.hasLayer(label)) {
				this._map.removeLayer(label);
			}
		});
		this._marker_labels = {};

		this._markers.addData(groups);
		this._clusters.addLayer(this._markers);
		
		// Add labels for each marker
		this._markers.eachLayer((marker) => {
			const groupName = marker.feature.properties.name;
			const groupId = marker.feature.properties.groupid;
			
			const label = L.marker(marker.getLatLng(), {
				icon: L.divIcon({
					className: 'geomapplus-marker-label',
					html: `<div style="
						background: rgba(0, 0, 0, 0.75);
						color: white;
						padding: 4px 8px;
						border-radius: 4px;
						font-size: 12px;
						font-weight: bold;
						white-space: nowrap;
						box-shadow: 0 2px 4px rgba(0,0,0,0.3);
						pointer-events: none;
					">${groupName}</div>`,
					iconSize: null,
					iconAnchor: [0, -15]
				}),
				interactive: false,
				zIndexOffset: -1000
			});
			
			this._marker_labels[groupId] = label;
			
			// Show labels based on zoom level
			if (this._map.getZoom() >= 10) {
				label.addTo(this._map);
			}
		});
	}

	_initMap(config) {
		const latLng = new L.latLng([config.center.latitude, config.center.longitude]);

		this._home_coords = config.home_coords;

		// Initialize map and load tile layer.
		this._map = L.map(this._unique_id).setView(latLng, config.center.zoom);
		L.tileLayer(config.tile_url, {
			tap: false,
			minZoom: 0,
			maxZoom: parseInt(config.max_zoom, 10),
			minNativeZoom: 1,
			maxNativeZoom: parseInt(config.max_zoom, 10),
			attribution: config.attribution
		}).addTo(this._map);

		this.initMarkerIcons();

		// Create cluster layer.
		this._clusters = this._createClusterLayer();
		this._map.addLayer(this._clusters);

		// Create markers layer.
		this._markers = L.geoJSON([], {
			onEachFeature: (feature, marker) => {
				marker.on('mouseover', () => {
					if (feature.properties.groupid !== this.#selected_groupid) {
						marker.setIcon(this._mouseover_icons[feature.properties.severity]);
					}
				});
				marker.on('mouseout', () => {
					if (feature.properties.groupid !== this.#selected_groupid) {
						marker.setIcon(this._icons[feature.properties.severity]);
					}
				});
			},
			pointToLayer: function (feature, ll) {
				return L.marker(ll, {
					icon: this._icons[feature.properties.severity]
				});
			}.bind(this)
		});

		this._map.setDefaultView(latLng, config.center.zoom);

		// Navigate home btn.
		this._map.navigateHomeControl = L.control.navigateHomeBtn({position: 'topleft'}).addTo(this._map);
		if (Object.keys(this._home_coords).length > 0) {
			const home_btn_title = ('default' in this._home_coords)
				? t('Navigate to default view')
				: t('Navigate to initial view');

			this._map.navigateHomeControl.setTitle(home_btn_title);
			this._map.navigateHomeControl.show();
		}

		// Workaround to prevent dashboard jumping to make map completely visible.
		this._map.getContainer().focus = () => {};

		// Add event listeners.
		this._map.getContainer().addEventListener('click', (e) => {
			if (e.target.classList.contains('leaflet-container')) {
				this.removeHintBoxes();
			}
		}, false);

		this._map.getContainer().addEventListener('cluster.click', (e) => {
			const cluster = e.detail;
			const node = cluster.originalEvent.srcElement.classList.contains('marker-cluster')
				? cluster.originalEvent.srcElement
				: cluster.originalEvent.srcElement.closest('.marker-cluster');

			if ('hintBoxItem' in node) {
				return;
			}

			const hintbox = document.createElement('div');
			hintbox.classList.add(CWidgetGeoMapPlus.ZBX_STYLE_HINTBOX);
			hintbox.style.maxHeight = `${node.getBoundingClientRect().top - 27}px`;
			hintbox.append(this.makePopupContent(cluster.layer.getAllChildMarkers().map(o => o.feature)));

			node.hintBoxItem = hintBox.createBox(cluster.originalEvent, node, hintbox, '', true);

			// Adjust hintbox size in case if scrollbar is necessary.
			hintBox.positionElement(cluster.originalEvent, node, node.hintBoxItem);

			// Center hintbox relative to node.
			node.hintBoxItem.position({
				my: 'center bottom',
				at: 'center top',
				of: node,
				collision: 'fit'
			});

			Overlay.prototype.recoverFocus.call({'$dialogue': node.hintBoxItem});
			Overlay.prototype.containFocus.call({'$dialogue': node.hintBoxItem});
		});

		this._markers.on('click keypress', (e) => {
			this.#selected_groupid = e.layer.feature.properties.groupid;

			this.#updateHintboxes();
			this.#updateMarkers();

			const node = e.originalEvent.srcElement;

			if ('hintBoxItem' in node) {
				return;
			}

			if (e.type === 'keypress') {
				if (e.originalEvent.key !== ' ' && e.originalEvent.key !== 'Enter') {
					return;
				}

				e.originalEvent.preventDefault();
			}

			const hintbox = document.createElement('div');
			hintbox.classList.add(CWidgetGeoMapPlus.ZBX_STYLE_HINTBOX);
			hintbox.style.maxHeight = `${node.getBoundingClientRect().top - 27}px`;
			hintbox.append(this.makePopupContent([e.layer.feature]));

			node.hintBoxItem = hintBox.createBox(e.originalEvent, node, hintbox, '', true);
			e.layer.hintBoxItem = node.hintBoxItem;

			// Adjust hintbox size in case if scrollbar is necessary.
			hintBox.positionElement(e.originalEvent, node, node.hintBoxItem);

			// Center hintbox relative to node.
			node.hintBoxItem.position({
				my: 'center bottom',
				at: 'center top',
				of: node,
				collision: 'fit'
			});

			Overlay.prototype.recoverFocus.call({'$dialogue': node.hintBoxItem});
			Overlay.prototype.containFocus.call({'$dialogue': node.hintBoxItem});
		});

		this._map.getContainer().addEventListener('cluster.dblclick', (e) => {
			e.detail.layer.zoomToBounds({padding: [20, 20]});
		});

		this._map.getContainer().addEventListener('contextmenu', (e) => {
			if (e.target.classList.contains('leaflet-container')) {
				const $obj = $(e.target);
				const menu = [{
					label: t('Actions'),
					items: [{
						label: t('Set this view as default'),
						clickCallback: this.updateDefaultView.bind(this),
						disabled: !this._widgetid
					}, {
						label: t('Reset to initial view'),
						clickCallback: this.unsetDefaultView.bind(this),
						disabled: !('default' in this._home_coords)
					}]
				}];

				$obj.menuPopup(menu, e, {
					position: {
						of: $obj,
						my: 'left top',
						at: 'left+'+e.layerX+' top+'+e.layerY,
						collision: 'fit'
					}
				});
			}

			e.preventDefault();
		});

		// Close opened hintboxes when moving/zooming/resizing widget.
		this._map
			.on('zoomstart movestart resize', () => this.removeHintBoxes())
			.on('zoomend', () => {
				this.#updateMarkers();
				this._updateLabelsVisibility();
			})
			.on('unload', () => {
				this._markers.clearLayers();
				this._clusters.clearLayers();
				Object.values(this._marker_labels).forEach(label => {
					if (this._map.hasLayer(label)) {
						this._map.removeLayer(label);
					}
				});
				this._marker_labels = {};

				this._initial_load = true;
			});
	}

	_updateLabelsVisibility() {
		const zoom = this._map.getZoom();
		const showLabels = zoom >= 10;
		
		Object.values(this._marker_labels).forEach(label => {
			if (showLabels && !this._map.hasLayer(label)) {
				label.addTo(this._map);
			} else if (!showLabels && this._map.hasLayer(label)) {
				this._map.removeLayer(label);
			}
		});
	}

	onClearContents() {
		if (this._map !== null) {
			this._map.remove();
			this._map = null;
		}
	}

	/**
	 * Get the closest group to the map center defined in the config.
	 *
	 * @param {Object}        config
	 * @param {Array<Object>} groups
	 *
	 * @returns {Object}
	 */
	#getClosestGroup(config, groups) {
		const center_point = L.latLng(config.center.latitude, config.center.longitude);

		return groups.reduce((closest, current) => {
			const current_point = L.latLng(current.geometry.coordinates[1], current.geometry.coordinates[0]);
			const closest_point = L.latLng(closest.geometry.coordinates[1], closest.geometry.coordinates[0]);

			return current_point.distanceTo(center_point) < closest_point.distanceTo(center_point)
				? current
				: closest;
		});
	}

	/**
	 * Function to update selected row in hintboxes.
	 */
	#updateHintboxes() {
		this._map._container.querySelectorAll('.marker-cluster').forEach((cluster) => {
			if (cluster.hintBoxItem !== undefined) {
				cluster.hintBoxItem[0].querySelectorAll('[data-groupid]').forEach((row) => {
					row.classList.toggle(ZBX_STYLE_ROW_SELECTED, row.dataset.groupid === this.#selected_groupid);
				});
			}
		});

		this._markers.eachLayer((marker) => {
			if (marker.hintBoxItem !== undefined) {
				marker.hintBoxItem[0].querySelectorAll('[data-groupid]').forEach((row) => {
					row.classList.toggle(ZBX_STYLE_ROW_SELECTED, row.dataset.groupid === this.#selected_groupid);
				});
			}
		});
	}

	/**
	 * Function to update style for selected marker or cluster.
	 */
	#updateMarkers() {
		this._markers.eachLayer((marker) => {
			const {groupid, severity} = marker.feature.properties;

			marker.setIcon(groupid === this.#selected_groupid ? this._selected_icons[severity] : this._icons[severity]);
		});

		this._map.eachLayer((layer) => {
			if (layer.getAllChildMarkers !== undefined) {
				const selected = layer.getAllChildMarkers().some(
					p => p.feature.properties.groupid == this.#selected_groupid
				);

				layer._icon.classList.toggle('selected', selected);
			}
		});
	}

	/**
	 * Function to create cluster layer.
	 *
	 * @returns {L.MarkerClusterGroup}
	 */
	_createClusterLayer() {
		const clusters = L.markerClusterGroup({
			showCoverageOnHover: false,
			zoomToBoundsOnClick: false,
			removeOutsideVisibleBounds: true,
			spiderfyOnMaxZoom: false,
			iconCreateFunction: (cluster) => {
				const max_severity = Math.max(...cluster.getAllChildMarkers().map(p => p.feature.properties.severity));
				const color = this.getSeverityColor(max_severity);

				return new L.DivIcon({
					html: `
						<div class="cluster-outer-shape">
							<div style="background-color: ${color};">
								<span>${cluster.getChildCount()}</span>
							</div>
						</div>`,
					className: 'marker-cluster',
					iconSize: new L.Point(50, 50)
				});
			}
		});

		// Transform 'clusterclick' event as 'cluster.click' and 'cluster.dblclick' events.
		clusters.on('clusterclick clusterkeypress', (e) => {
			if (e.type === 'clusterkeypress') {
				if (e.originalEvent.key !== ' ' && e.originalEvent.key !== 'Enter') {
					return;
				}

				e.originalEvent.preventDefault();
			}

			if ('event_click' in clusters) {
				clearTimeout(clusters.event_click);
				delete clusters.event_click;
				this._map.getContainer().dispatchEvent(
					new CustomEvent('cluster.dblclick', {detail: e})
				);
			}
			else {
				clusters.event_click = setTimeout(() => {
					delete clusters.event_click;
					this._map.getContainer().dispatchEvent(
						new CustomEvent('cluster.click', {detail: e})
					);
				}, 300);
			}
		});

		return clusters;
	}

	/**
	 * Save default view.
	 */
	updateDefaultView() {
		const ll = this._map.getCenter();
		const zoom = this._map.getZoom();
		const view = `${ll.lat},${ll.lng},${zoom}`;

		updateUserProfile('web.dashboard.widget.geomapplus.default_view', view, [this._widgetid], PROFILE_TYPE_STR);
		this._map.setDefaultView(ll, zoom);
		this._home_coords['default'] = true;
		this._map.navigateHomeControl.show();
		this._map.navigateHomeControl.setTitle(t('Navigate to default view'));
	}

	/**
	 * Unset default view.
	 */
	unsetDefaultView() {
		updateUserProfile('web.dashboard.widget.geomapplus.default_view', '', [this._widgetid], PROFILE_TYPE_STR)
			.always(() => {
				delete this._home_coords.default;
			});

		if ('initial' in this._home_coords) {
			const latLng = new L.latLng([this._home_coords.initial.latitude, this._home_coords.initial.longitude]);
			this._map.setDefaultView(latLng, this._home_coords.initial.zoom);
			this._map.navigateHomeControl.setTitle(t('Navigate to initial view'));
			this._map.setView(latLng, this._home_coords.initial.zoom);
		}
		else {
			this._map.navigateHomeControl.hide();
		}
	}

	/**
	 * Function to delete all opened hintboxes.
	 */
	removeHintBoxes() {
		const markers = this._map._container.parentNode.querySelectorAll('.marker-cluster, .leaflet-marker-icon');
		[...markers].forEach((m) => {
			if ('hintboxid' in m) {
				hintBox.deleteHint(m);
			}
		});
	}

	/**
	 * Create host group popup content - styled like hostgroupalarms widget.
	 *
	 * @param {array} groups
	 *
	 * @returns {DocumentFragment}
	 */
	makePopupContent(groups) {
		const container = document.createElement('div');
		container.style.cssText = 'min-width: 300px; max-width: 400px;';

		groups.forEach(group => {
			const {name, host_count, alert_count, severity} = group.properties;
			
			// Get severity info
			const severityColor = this.getSeverityColor(severity);
			const severityName = this.getSeverityName(severity);
			const textColor = this.getTextColor(severity);
			
			// Create card container
			const card = document.createElement('div');
			card.setAttribute('data-groupid', group.properties.groupid);
			card.style.cssText = `
				background-color: ${severityColor};
				color: ${textColor};
				padding: 20px;
				border-radius: 8px;
				text-align: center;
				margin-bottom: 10px;
				box-shadow: 0 2px 4px rgba(0,0,0,0.2);
			`;
			
			// Group name
			const titleDiv = document.createElement('div');
			titleDiv.style.cssText = 'font-size: 16px; font-weight: bold; margin-bottom: 12px;';
			titleDiv.textContent = name;
			card.appendChild(titleDiv);
			
			// Severity name
			const severityDiv = document.createElement('div');
			severityDiv.style.cssText = 'font-size: 18px; font-weight: bold; margin-bottom: 8px;';
			severityDiv.textContent = severityName;
			card.appendChild(severityDiv);
			
			// Alarm count
			const countDiv = document.createElement('div');
			countDiv.style.cssText = 'font-size: 14px; margin-bottom: 8px;';
			countDiv.textContent = `${alert_count} ${alert_count === 1 ? 'alarm' : 'alarms'}`;
			card.appendChild(countDiv);
			
			// Host count
			const hostDiv = document.createElement('div');
			hostDiv.style.cssText = 'font-size: 12px; opacity: 0.9;';
			hostDiv.textContent = `${host_count} ${host_count === 1 ? 'host' : 'hosts'}`;
			card.appendChild(hostDiv);
			
			container.appendChild(card);
		});

		const dom = document.createElement('template');
		dom.content.appendChild(container);

		return dom.content;
	}

	/**
	 * Get severity color based on configuration.
	 */
	getSeverityColor(severity) {
		switch(severity) {
			case CWidgetGeoMapPlus.SEVERITY_DISASTER:
				return this._colors.disaster;
			case CWidgetGeoMapPlus.SEVERITY_HIGH:
				return this._colors.high;
			case CWidgetGeoMapPlus.SEVERITY_AVERAGE:
				return this._colors.average;
			case CWidgetGeoMapPlus.SEVERITY_WARNING:
				return this._colors.warning;
			case CWidgetGeoMapPlus.SEVERITY_INFORMATION:
				return this._colors.information;
			case CWidgetGeoMapPlus.SEVERITY_NOT_CLASSIFIED:
				return this._colors.information;
			default:
				return this._colors.no_problems;
		}
	}

	/**
	 * Get severity name.
	 */
	getSeverityName(severity) {
		const names = {
			[CWidgetGeoMapPlus.SEVERITY_DISASTER]: 'Disaster',
			[CWidgetGeoMapPlus.SEVERITY_HIGH]: 'High',
			[CWidgetGeoMapPlus.SEVERITY_AVERAGE]: 'Average',
			[CWidgetGeoMapPlus.SEVERITY_WARNING]: 'Warning',
			[CWidgetGeoMapPlus.SEVERITY_INFORMATION]: 'Information',
			[CWidgetGeoMapPlus.SEVERITY_NOT_CLASSIFIED]: 'Not classified'
		};
		return names[severity] || 'Unknown';
	}

	/**
	 * Get text color based on severity (white for dark backgrounds, black for light).
	 */
	getTextColor(severity) {
		const darkBackgrounds = [
			CWidgetGeoMapPlus.SEVERITY_INFORMATION,
			CWidgetGeoMapPlus.SEVERITY_HIGH,
			CWidgetGeoMapPlus.SEVERITY_DISASTER
		];
		return darkBackgrounds.includes(severity) ? '#FFFFFF' : '#000000';
	}

	/**
	 * Function creates marker icons based on shape and severity.
	 */
	initMarkerIcons() {
		const styles = getComputedStyle(this._contents);
		const hover_fill = styles.getPropertyValue('--hover-fill') || '#FFFFFF';
		const selected_fill = styles.getPropertyValue('--selected-fill') || '#FFFFFF';
		const selected_stroke = styles.getPropertyValue('--selected-stroke') || '#000000';

		for (let i = CWidgetGeoMapPlus.SEVERITY_NO_PROBLEMS; i <= CWidgetGeoMapPlus.SEVERITY_DISASTER; i++) {
			const color = this.getSeverityColor(i);

			const tmpl = this.createMarkerSVG(color, this._marker_shape, false, false);
			const selected_tmpl = this.createMarkerSVG(color, this._marker_shape, true, false);
			const mouseover_tmpl = this.createMarkerSVG(color, this._marker_shape, false, true);

			this._icons[i] = L.icon({
				iconUrl: 'data:image/svg+xml;base64,' + btoa(tmpl),
				iconSize: [46, 61],
				iconAnchor: [22, 44]
			});

			this._selected_icons[i] = L.icon({
				iconUrl: 'data:image/svg+xml;base64,' + btoa(selected_tmpl),
				iconSize: [46, 61],
				iconAnchor: [22, 44]
			});

			this._mouseover_icons[i] = L.icon({
				iconUrl: 'data:image/svg+xml;base64,' + btoa(mouseover_tmpl),
				iconSize: [46, 61],
				iconAnchor: [22, 44]
			});
		}
	}

	/**
	 * Create marker SVG based on shape type.
	 */
	createMarkerSVG(color, shape, selected, hover) {
		const styles = getComputedStyle(this._contents);
		const hover_fill = styles.getPropertyValue('--hover-fill') || '#FFFFFF';
		const selected_fill = styles.getPropertyValue('--selected-fill') || '#FFFFFF';
		const selected_stroke = styles.getPropertyValue('--selected-stroke') || '#000000';

		let shapePath = '';
		
		switch(shape) {
			case CWidgetGeoMapPlus.MARKER_SHAPE_RECTANGLE:
				shapePath = `<rect x="6" y="6" width="12" height="12" fill="${color}" stroke="${selected ? selected_stroke : color}" stroke-width="${selected ? 2 : 1}"/>`;
				break;
			case CWidgetGeoMapPlus.MARKER_SHAPE_PIN:
				shapePath = `<path fill="${color}" fill-rule="evenodd" clip-rule="evenodd" d="M12 24C12.972 24 18 15.7794 18 12.3C18 8.82061 15.3137 6 12 6C8.68629 6 6 8.82061 6 12.3C6 15.7794 11.028 24 12 24ZM12.0001 15.0755C13.4203 15.0755 14.5716 13.8565 14.5716 12.3528C14.5716 10.8491 13.4203 9.63011 12.0001 9.63011C10.58 9.63011 9.42871 10.8491 9.42871 12.3528C9.42871 13.8565 10.58 15.0755 12.0001 15.0755Z"/>`;
				break;
			case CWidgetGeoMapPlus.MARKER_SHAPE_CLOUD:
				shapePath = `<path fill="${color}" d="M18 14c1.1 0 2-.9 2-2s-.9-2-2-2c-.18 0-.36.03-.53.08C17.16 9.09 16.16 8 15 8c-.55 0-1.05.22-1.41.59-.36-.37-.86-.59-1.41-.59-1.16 0-2.16 1.09-2.47 2.08C9.36 10.03 9.18 10 9 10c-1.1 0-2 .9-2 2s.9 2 2 2h9z"/>`;
				break;
			case CWidgetGeoMapPlus.MARKER_SHAPE_CIRCLE:
			default:
				shapePath = `<circle cx="12" cy="12" r="6" fill="${color}" stroke="${selected ? selected_stroke : color}" stroke-width="${selected ? 2 : 1}"/>`;
				break;
		}

		let svg = `<svg width="24" height="32" viewBox="0 0 24 32" fill="none" xmlns="http://www.w3.org/2000/svg">`;
		
		if (selected) {
			svg += `<path fill="${selected_fill}" fill-rule="evenodd" clip-rule="evenodd" d="M12 30C13.62 30 22 17.2124 22 11.8C22 6.38761 17.5228 2 12 2C6.47715 2 2 6.38761 2 11.8C2 17.2124 10.38 30 12 30ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16Z"/>`;
			svg += `<path fill="${selected_stroke}" d="M21.5 11.8C21.5 13.0504 21.009 14.7888 20.2033 16.7359C19.4038 18.6682 18.3176 20.7523 17.1775 22.6746C16.0371 24.5971 14.8501 26.3455 13.8535 27.6075C13.3541 28.24 12.9113 28.7391 12.5528 29.0752C12.3729 29.2439 12.2256 29.3607 12.1123 29.4323C11.9844 29.5131 11.9563 29.5 12 29.5V30.5C12.2462 30.5 12.4734 30.387 12.6463 30.2778C12.8337 30.1595 13.0325 29.9963 13.2368 29.8047C13.6467 29.4203 14.1244 28.8781 14.6384 28.2272C15.6687 26.9225 16.8804 25.1356 18.0376 23.1847C19.1949 21.2335 20.3049 19.1059 21.1273 17.1183C21.9436 15.1455 22.5 13.2558 22.5 11.8H21.5ZM12 2.5C17.2563 2.5 21.5 6.67323 21.5 11.8H22.5C22.5 6.10199 17.7894 1.5 12 1.5V2.5ZM2.5 11.8C2.5 6.67323 6.74372 2.5 12 2.5V1.5C6.21058 1.5 1.5 6.10199 1.5 11.8H2.5ZM12 29.5C12.0437 29.5 12.0156 29.5131 11.8877 29.4323C11.7744 29.3607 11.6271 29.2439 11.4472 29.0752C11.0887 28.7391 10.6459 28.24 10.1465 27.6075C9.14988 26.3455 7.96285 24.5971 6.82253 22.6746C5.68238 20.7523 4.59618 18.6682 3.7967 16.7359C2.99104 14.7888 2.5 13.0504 2.5 11.8H1.5C1.5 13.2558 2.05645 15.1455 2.87267 17.1183C3.69505 19.1059 4.80509 21.2335 5.96244 23.1847C7.11961 25.1356 8.33133 26.9225 9.36163 28.2272C9.87559 28.8781 10.3533 29.4203 10.7632 29.8047C10.9675 29.9963 11.1663 30.1595 11.3537 30.2778C11.5266 30.387 11.7538 30.5 12 30.5V29.5Z"/>`;
		}
		
		if (hover) {
			svg += `<path fill="${hover_fill}" fill-rule="evenodd" clip-rule="evenodd" d="M12 30C13.62 30 22 17.2124 22 11.8C22 6.38761 17.5228 2 12 2C6.47715 2 2 6.38761 2 11.8C2 17.2124 10.38 30 12 30ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16Z"/>`;
		}
		
		svg += shapePath;
		svg += `</svg>`;

		return svg;
	}

	onEdit() {
		if (this._map !== null) {
			this.removeHintBoxes();
		}
	}

	hasPadding() {
		return false;
	}
}

// Global function for acknowledging events
function acknowledgeEvent(eventid) {
	// This would typically call Zabbix API to acknowledge the event
	console.log('Acknowledging event:', eventid);
	// In a real implementation, this would make an AJAX call to acknowledge the event
	alert('Event acknowledgement functionality would be implemented here');
}