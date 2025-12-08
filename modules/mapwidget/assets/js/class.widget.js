/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

class WidgetMap extends CWidget {

	static ZBX_STYLE_CLASS = 'map-widget';
	static MAPLIBRE_VERSION = 'v5.6.0'; 

	onInitialize() {
		this._map = null;
		this._map_container = null;
		this._maplibre_loaded = false;
		this._vars = {};
		this._location_markers = new Map();
		
		this._storage_key = 'mapwidget_filter_' + this.widget_id;
		const saved_filter_state = localStorage.getItem(this._storage_key);
		this._filter_only_problems = (saved_filter_state === 'true');
		
		this._maplibre_promise = this.loadMapLibre();
	}

	loadMapLibre() {
		return new Promise((resolve, reject) => {
			if (typeof maplibregl !== 'undefined') {
				this._maplibre_loaded = true;
				return resolve();
			}
			const link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = `https://unpkg.com/maplibre-gl@${WidgetMap.MAPLIBRE_VERSION}/dist/maplibre-gl.css`;
			document.head.appendChild(link);
			const script = document.createElement('script');
			script.src = `https://unpkg.com/maplibre-gl@${WidgetMap.MAPLIBRE_VERSION}/dist/maplibre-gl.js`;
			script.onload = () => {
				this._maplibre_loaded = true;
				resolve();
			};
			script.onerror = () => reject(new Error('Failed to load MapLibre GL JS'));
			document.head.appendChild(script);
		});
	}

	setContents(response) {
		this._vars.zabbix_hosts = response.zabbix_hosts; 
		this._vars.severity_colors = response.severity_colors;
		this._vars.severity_names = response.severity_names;
		this._vars.detailed_problems = response.detailed_problems; 
		
		if (response.fields_values) {
			this._fields = response.fields_values;
		}

		if (!this._map) {
			super.setContents(response);
			this.initializeMap();
		}
		else {
			console.log('Map refresh: Redrawing host pins...');
			this.drawHostPins();
			
			const problems_modal = this._target.querySelector('#map-problems-modal');
			if (problems_modal && problems_modal.classList.contains('visible')) {
				this._target.querySelector('.map-problems-modal-body').innerHTML = this.buildAllProblemsHtml();
			}
		}
	}

	async initializeMap() {
		if (this._map) return;
		try {
			await this._maplibre_promise;
			this._map_container = this._target.querySelector('.map-widget-container');
			if (!this._map_container) {
				this.showError('Map container element not found.');
				return;
			}
			if (!this._fields) {
				this.showError('Widget data missing.');
				return;
			}

			const map_id = this._fields.map_id;
			const map_key = this._fields.map_key;
			if (!map_id || !map_key) {
				this.showError('Map ID and Map Key must be configured.');
				return;
			}
			const mapUrl = `https://api.maptiler.com/maps/${map_id}/?key=${map_key}`;
			const zoom = parseFloat(this._fields.zoom) || 4;
			const centerLat = parseFloat(this._fields.center_lat) || -16;
			const centerLng = parseFloat(this._fields.center_lng) || -52;
			const bearing = parseFloat(this._fields.bearing) || 0;
			const pitch = parseFloat(this._fields.pitch) || 0;
			const styleUrl = this.convertToStyleUrl(mapUrl);
			
			this._map = new maplibregl.Map({
				container: this._map_container, 
				style: styleUrl,
				center: [centerLng, centerLat],
				zoom: zoom,
				bearing: bearing,
				pitch: pitch,
				attributionControl: true
			});
			this._map.addControl(new maplibregl.NavigationControl(), 'top-right');
			
			this._map.on('load', () => {
				this._target.classList.remove('is-loading');
				console.log('Map loaded.');
				this.drawHostPins();
				this.setupSearch(); 
			});
			
			this._map.on('error', (e) => {
				console.error('Map error:', e);
				this.showError('Map failed to load: ' + (e.error?.message || 'Unknown error'));
			});
		} catch (error) {
			console.error('Failed to initialize map:', error);
			this.showError('Failed to initialize map: ' + error.message);
		}
	}

	drawHostPins() {
		if (!this._map) return;

		this._location_markers.forEach(marker => marker.remove());
		this._location_markers.clear();

		const hosts = this._vars.zabbix_hosts || [];
		const colors = this._vars.severity_colors || {};
		
		const label_source = parseInt(this._fields.label_source, 10) || 1;
		const label_regex = this._fields.label_regex || '';

		const locations = {};
		hosts.forEach(host => {
			const key = `${host.lat},${host.lon}`;
			if (!locations[key]) locations[key] = [];
			locations[key].push(host);
		});

		Object.values(locations).forEach(host_group => {
			const host_count = host_group.length;
			const first_host = host_group[0];
			const { lat, lon } = first_host;
			const key = `${lat},${lon}`;

			const highest_severity = this.getHighestSeverityForGroup(host_group);
			
			if (this._filter_only_problems && highest_severity === -1) return;
			
			const color = colors[highest_severity] || '#97AAB3';

			let label_text = '';
			if (host_count > 1) {
				if (label_source === 2) {
					const first_group_name = first_host.group_name;
					let all_same_group = true;
					for (let i = 1; i < host_group.length; i++) {
						if (host_group[i].group_name !== first_group_name) {
							all_same_group = false;
							break;
						}
					}
					if (all_same_group) {
						label_text = this.applyRegex(first_group_name, label_regex);
					} else {
						label_text = `${host_count} Host Groups`;
					}
				} else {
					label_text = `${host_count} Hosts`;
				}
			} else {
				let source_string = (label_source === 2) ? first_host.group_name : first_host.name;
				if (!source_string) source_string = first_host.name;
				label_text = this.applyRegex(source_string, label_regex);
			}

			const marker_element = document.createElement('div');
			marker_element.className = 'map-marker-container';
			marker_element.innerHTML = `
				<div class="map-marker-label">${this.escapeHtml(label_text)}</div>
				<div class="map-marker-pin" style="background-color: ${color};"></div>
			`;

			const popup_html = this.buildPopupHtml(host_group); 
			const popup = new maplibregl.Popup({ 
				offset: 25,
				closeButton: true,
				closeOnClick: true,
				anchor: 'bottom'
			}).setHTML(popup_html);

			const marker = new maplibregl.Marker({
				element: marker_element,
				anchor: 'bottom'
			})
			.setLngLat([lon, lat])
			.setPopup(popup)
			.addTo(this._map);

			this._location_markers.set(key, marker);
		});
	}

	setupSearch() {
		const search_input = this._target.querySelector('input[name="map_search_input"]');
		const search_results = this._target.querySelector('.map-search-results');
		if (!search_input || !search_results) return;

		search_input.addEventListener('input', (e) => {
			const search_term = e.target.value.toLowerCase();
			search_results.innerHTML = ''; 
			if (search_term.length < 2) {
				search_results.classList.remove('visible');
				return;
			}
			const all_hosts = this._vars.zabbix_hosts || [];
			const matched_hosts = all_hosts.filter(host => 
				host.name.toLowerCase().includes(search_term)
			);
			if (matched_hosts.length === 0) {
				search_results.classList.remove('visible');
				return;
			}
			search_results.classList.add('visible');
			matched_hosts.slice(0, 10).forEach(host => {
				const item = document.createElement('div');
				item.className = 'map-search-item';
				item.textContent = host.name;
				item.addEventListener('click', () => {
					search_input.value = host.name;
					search_results.innerHTML = '';
					search_results.classList.remove('visible');
					const key = `${host.lat},${host.lon}`;
					const marker = this._location_markers.get(key);
					if (marker) {
						this._map.flyTo({
							center: [host.lon, host.lat],
							zoom: 14,
							speed: 1.5
						});
						setTimeout(() => {
							marker.togglePopup();
						}, 1000);
					}
				});
				search_results.appendChild(item);
			});
		});
		document.addEventListener('click', (e) => {
			if (!this._target.contains(e.target)) {
				search_results.classList.remove('visible');
			}
		});

		const show_problems_btn = this._target.querySelector('.map-show-problems-btn');
		const problems_modal = this._target.querySelector('#map-problems-modal');
		const problems_modal_close = this._target.querySelector('.map-problems-modal-close');
		const problems_modal_body = this._target.querySelector('.map-problems-modal-body');

		if (show_problems_btn && problems_modal && problems_modal_close && problems_modal_body) {
			show_problems_btn.addEventListener('click', () => {
				const html = this.buildAllProblemsHtml();
				problems_modal_body.innerHTML = html;
				problems_modal.classList.add('visible');
			});
			const closeModal = () => {
				problems_modal.classList.remove('visible');
			};
			problems_modal_close.addEventListener('click', closeModal);
			problems_modal.addEventListener('click', (e) => {
				if (e.target === problems_modal) {
					closeModal();
				}
			});
		}
		
		const filter_btn = this._target.querySelector('.map-filter-problems-btn');
		if (filter_btn) {
			filter_btn.textContent = this._filter_only_problems ? 'Show All Hosts' : 'Show Only Problems';
			filter_btn.classList.toggle('active', this._filter_only_problems);
			
			filter_btn.addEventListener('click', () => {
				this._filter_only_problems = !this._filter_only_problems;
				filter_btn.classList.toggle('active', this._filter_only_problems);
				filter_btn.textContent = this._filter_only_problems ? 'Show All Hosts' : 'Show Only Problems';
				localStorage.setItem(this._storage_key, this._filter_only_problems);
				this.drawHostPins();
			});
		}
	}
	
	buildUrlWithFilters() {
		const url = new URL('zabbix.php', window.location.origin);
		url.searchParams.set('action', 'problem.view');
		url.searchParams.set('filter_set', '1');

		const selected_severities = this._fields.severities || [];
		if (selected_severities.length > 0 && selected_severities.length < 6) {
			selected_severities.forEach(sev_id => {
				url.searchParams.append(`severities[${sev_id}]`, sev_id);
			});
		}

		if (this._fields.show_acknowledged == 0) url.searchParams.set('acknowledgement_status', '1'); 
		else url.searchParams.set('acknowledgement_status', '0'); 

		if (this._fields.show_suppressed == 1 || this._fields.show_suppressed_only == 1) {
			url.searchParams.set('show_suppressed', '1');
		} else {
			url.searchParams.set('show_suppressed', '0');
		}

		const tags = this._fields.tags || [];
		if (tags.length > 0) {
			tags.forEach((tag, index) => {
				url.searchParams.append(`tags[${index}][tag]`, tag.tag);
				url.searchParams.append(`tags[${index}][operator]`, tag.operator);
				url.searchParams.append(`tags[${index}][value]`, tag.value);
			});
			url.searchParams.set('evaltype', this._fields.evaltype);
		}

		return url;
	}

	buildPopupHtml(host_group) {
		const colors = this._vars.severity_colors || {};
		const host_count = host_group.length;
		let title = '';
		let view_all_html = '';
		
		if (host_count > 1) {
			title = `${host_count} Hosts`;
			const view_all_url = this.buildUrlWithFilters();
			host_group.forEach(h => view_all_url.searchParams.append('hostids[]', h.hostid));

			view_all_html = `
				<div class="map-popup-footer">
					<a href="${view_all_url.toString()}" target="_blank" class="view-button view-all">View All</a>
				</div>
			`;
		} else {
			title = host_group[0].name;
		}

		let hosts_html = '';
		host_group.forEach(host => {
			const host_url = this.buildUrlWithFilters();
			host_url.searchParams.append('hostids[]', host.hostid);

			hosts_html += `
				<div class="map-popup-host-item">
					<div class="map-popup-host-name">
						<span>${this.escapeHtml(host.name)}</span>
						<a href="${host_url.toString()}" target="_blank" class="view-button">View</a>
					</div>
			`;
			
			const problem_counts = host.problem_counts || {};
			const problem_events = host.problem_events || {};
			const event_ids = Object.keys(problem_events);
			
			const problem_severities = Object.keys(problem_counts)
				.filter(s => problem_counts[s].unacked > 0 || problem_counts[s].acked > 0)
				.sort((a, b) => b - a);

			if (problem_severities.length > 0) {
				hosts_html += '<ul class="map-popup-problem-list">';
				
				if (event_ids.length > 0 && event_ids.length <= 5) {
					event_ids.forEach(eventid => {
						const problem = problem_events[eventid];
						const color = colors[problem.severity] || '#97AAB3';
						
						hosts_html += `<li class="map-popup-problem-item" style="color: ${color};">
										 (${this.escapeHtml(problem.name)})`;
						
						if (problem.acknowledged) {
							hosts_html += ` <span class="map-popup-ack-icon">✔</span>`;
						}

						const button_text = problem.acknowledged ? 'Update' : '(Ack)';
						hosts_html += ` <a class="map-popup-ack-btn" 
											href="#" 
											onClick="acknowledgePopUp({eventids: ['${eventid}']}); event.stopPropagation(); return false;">
											 ${button_text}
										 </a>
									   </li>`;
					});
				}
				else {
					problem_severities.forEach(severity => {
						const unacked_count = problem_counts[severity].unacked || 0;
						const acked_count = problem_counts[severity].acked || 0;
						const color = colors[severity] || '#97AAB3';

						if (unacked_count > 0) {
							const text = (unacked_count > 1) ? `${unacked_count} problemas` : `${unacked_count} problema`;
							hosts_html += `<li class="map-popup-problem-item" style="color: ${color};">(${text})</li>`;
						}
						if (acked_count > 0) {
							const text = (acked_count > 1) ? `${acked_count} problemas` : `${acked_count} problema`;
							hosts_html += `<li class="map-popup-problem-item" style="color: ${color};">
											 (${text}) <span class="map-popup-ack-icon">✔</span>
										   </li>`;
						}
					});
				}
				hosts_html += '</ul>';
			} else {
				hosts_html += `<div class="host-status-ok" style="color: ${colors[-1]};">Status: OK</div>`;
			}
			hosts_html += '</div>';
		});

		return `
			<div class="map-popup-content">
				<div class="map-popup-header">
					<span>${this.escapeHtml(title)}</span>
				</div>
				${hosts_html}
				${view_all_html}
			</div>
		`;
	}

	buildAllProblemsHtml() {
		const problems = this._vars.detailed_problems || [];
		const colors = this._vars.severity_colors || {};
		const severities = this._vars.severity_names || {};

		if (problems.length === 0) {
			return `<div class="host-status-ok" style="color: ${colors[-1] || '#66BB6A'}; padding: 20px; text-align: center;">Nenhum problema encontrado.</div>`;
		}

		let html = `
			<table class="map-modal-table">
				<thead>
					<tr>
						<th>Host</th>
						<th>Severity</th>
						<th>Problem</th>
						<th>Age</th>
						<th>Ack</th>
					</tr>
				</thead>
				<tbody>
		`;

		problems.forEach(problem => {
			const sev_color = colors[problem.severity] || '#97AAB3';
			const sev_name = severities[problem.severity] || 'Unknown';
			const sev_text_class = (problem.severity == 2 || problem.severity == 3 || problem.severity == 0) ? 'sev-text-dark' : 'sev-text-light';
			const button_text = problem.acknowledged ? 'Update' : '(Ack)';
			
			const ack_button = `
				<a class="map-popup-ack-btn" 
				   href="#" 
				   style="margin-left: 5px; white-space: nowrap;"
				   onClick="acknowledgePopUp({eventids: ['${problem.eventid}']}); event.stopPropagation(); return false;">
				   ${button_text}
				</a>`;

			let ack_html = `<span style="white-space: nowrap;">`;
			if (problem.acknowledged) {
				ack_html += `<span class="map-popup-ack-icon">✔</span>`;
			}
			ack_html += ack_button;
			ack_html += `</span>`;

			html += `
				<tr>
					<td data-label="Host">${this.escapeHtml(problem.hostname)}</td>
					<td data-label="Severity">
						<span class="map-modal-severity-badge ${sev_text_class}" style="background-color: ${sev_color};">
							${sev_name}
						</span>
					</td>
					<td data-label="Problem">${this.escapeHtml(problem.problem)}</td>
					<td data-label="Age">${problem.age}</td>
					<td data-label="Ack">${ack_html}</td>
				</tr>
			`;
		});

		html += `</tbody></table>`;
		return html;
	}

	applyRegex(source_string, label_regex) {
		if (!source_string) return '';
		if (label_regex) {
			try {
				const regex = new RegExp(label_regex);
				const match = source_string.match(regex);
				if (match && match[1]) return match[1];
				else if (match && match[0]) return match[0];
				else return source_string; 
			} catch (e) {
				console.error('Invalid Regex in MapWidget config:', e.message);
				return source_string;
			}
		}
		return source_string;
	}

	getHighestSeverityForGroup(host_group) {
		let highest_severity = -1;
		host_group.forEach(host => {
			if (host.severity > highest_severity) {
				highest_severity = host.severity;
			}
		});
		return highest_severity;
	}

	escapeHtml(text) {
		if (typeof text !== 'string') return '';
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
	
	convertToStyleUrl(mapUrl) {
		try {
			const url = new URL(mapUrl);
			let pathname = url.pathname;
			if (pathname.endsWith('/')) pathname = pathname.slice(0, -1);
			if (!pathname.endsWith('style.json')) pathname += '/style.json';
			return url.origin + pathname + url.search;
		} catch (error) {
			console.error('Error parsing map URL:', error);
			const map_key = this._fields.map_key || 'EcU1lnbZ4xnZL4N9hK7w';
			return `https://api.maptiler.com/maps/basic-v2/style.json?key=${map_key}`;
		}
	}

	onResize() {
		if (this._map) {
			setTimeout(() => { this._map.resize(); }, 100);
		}
	}

	onDestroy() {
		this._location_markers.forEach(marker => marker.remove());
		this._location_markers.clear();
		if (this._map) {
			this._map.remove();
			this._map = null;
		}
	}

	showError(message) {
		this._target.classList.remove('is-loading');
		const container = this._map_container || this._target.querySelector('.map-widget-container') || this._target;
		if (container) {
			container.innerHTML = `<div style="padding:20px; text-align:center; color:#d9534f;"><strong>Map Error:</strong> ${message}</div>`;
		}
	}

	hasPadding() {
		return false;
	}

	getUpdateRequestData() {
		return {
			...super.getUpdateRequestData(),
			map_id: this._fields.map_id,
			map_key: this._fields.map_key,
			zoom: this._fields.zoom,
			center_lat: this._fields.center_lat,
			center_lng: this._fields.center_lng,
			bearing: this._fields.bearing,
			pitch: this._fields.pitch,
			hostgroups: this._fields.hostgroups || [],
			hosts: this._fields.hosts || [],
			exclude_hosts: this._fields.exclude_hosts || [],
			evaltype: this._fields.evaltype,
			tags: this._fields.tags || [],
			label_regex: this._fields.label_regex,
			label_source: this._fields.label_source,
			severities: this._fields.severities || [], // Array
			show_acknowledged: this._fields.show_acknowledged,
			show_suppressed: this._fields.show_suppressed,
			show_suppressed_only: this._fields.show_suppressed_only, // Novo
			exclude_maintenance: this._fields.exclude_maintenance
		};
	}

	getRefreshInterval() {
		return this._fields.refresh_interval || 30;
	}
}
