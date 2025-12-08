/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

class WidgetHostGroupAlarms extends CWidget {

	static ZBX_STYLE_CLASS = 'hostgroup-alarms-widget';

	onInitialize() {
		this._vars = {};
		this._body = this._target.querySelector('.hostgroup-alarms-container');
		this._tooltip = null;
		this._hide_timer = null;
		this.setContainerSize();
		
		this._target.classList.remove('is-loading');

		if (this._body !== null) {
			this._body.addEventListener('click', this.onWidgetClick.bind(this));
			this._body.style.cursor = 'pointer';
			this._body.addEventListener('mouseenter', this.onMouseEnter.bind(this));
			this._body.addEventListener('mouseleave', this.onMouseLeave.bind(this));
		}
		
		// Injeta estilos da tabela se não existirem
		this.injectTableStyles();
	}

	injectTableStyles() {
		if (document.getElementById('hostgroup-alarms-table-styles')) return;
		
		const style = document.createElement('style');
		style.id = 'hostgroup-alarms-table-styles';
		style.innerHTML = `
			.hostgroup-alarms-tooltip {
				background: #2b2b2b;
				border: 1px solid #383838;
				box-shadow: 0 4px 20px rgba(0,0,0,0.5);
				z-index: 1000;
				position: absolute;
				max-width: 600px;
				padding: 0;
				border-radius: 2px;
			}
			.hostgroup-alarms-table {
				width: 100%;
				border-collapse: collapse;
				font-size: 12px;
				color: #f2f2f2;
			}
			.hostgroup-alarms-table th {
				text-align: left;
				padding: 6px 10px;
				background: #383838;
				color: #acacac;
				font-weight: bold;
				border-bottom: 1px solid #4f4f4f;
			}
			.hostgroup-alarms-table td {
				padding: 6px 10px;
				border-bottom: 1px solid #383838;
				vertical-align: top;
			}
			.hostgroup-alarms-table tr:last-child td {
				border-bottom: none;
			}
			.hga-severity {
				padding: 2px 6px;
				border-radius: 2px;
				color: #fff;
				font-weight: bold;
				font-size: 11px;
				display: inline-block;
				min-width: 60px;
				text-align: center;
			}
			.hga-severity.sev-0 { background: #97AAB3; color: #000; }
			.hga-severity.sev-1 { background: #7499FF; color: #000; }
			.hga-severity.sev-2 { background: #FFC859; color: #000; }
			.hga-severity.sev-3 { background: #FFA059; color: #000; }
			.hga-severity.sev-4 { background: #E97659; color: #fff; }
			.hga-severity.sev-5 { background: #E45959; color: #fff; }
			
			.hga-ack-btn {
				color: #7499FF;
				text-decoration: none;
				margin-left: 5px;
			}
			.hga-ack-btn:hover {
				text-decoration: underline;
			}
			.hostgroup-alarms-footer {
				background: #383838;
				padding: 8px 10px;
				text-align: right;
				font-style: italic;
				color: #acacac;
				border-top: 1px solid #4f4f4f;
			}
		`;
		document.head.appendChild(style);
	}

	onResize() {
		this.setContainerSize();
	}

	setContainerSize() {
		if (this._body !== null && this._content_body) {
			const rect = this._content_body.getBoundingClientRect();
			const padding = (this._fields && this._fields.padding) ? parseInt(this._fields.padding, 10) : 10;
			const available_height = rect.height - (padding * 2);
			const available_width = rect.width - (padding * 2);
			const min_height = 120;
			const min_width = 160;

			if (available_height > 0) this._body.style.height = Math.max(available_height, min_height) + 'px';
			if (available_width > 0) this._body.style.width = Math.max(available_width, min_width) + 'px';
		}
	}

	onWidgetClick(event) {
		event.preventDefault();
		event.stopPropagation();

		const widget_config = this._vars.widget_config || {}; 
		const alarm_data = this._vars.alarm_data || {};

		if (widget_config.enable_url_redirect && widget_config.redirect_url) {
			const target = widget_config.open_in_new_tab ? '_blank' : '_self';
			window.open(widget_config.redirect_url, target);
		} 
		else if (alarm_data.total_alarms > 0) {
			const hostgroups = this._fields.hostgroups || [];
			const hosts = this._fields.hosts || [];
			const tags = this._fields.tags || [];
			const evaltype = this._fields.evaltype || 0;
			const show_acknowledged = this._fields.show_acknowledged || 0;
			const show_suppressed = this._fields.show_suppressed || 0;
			const show_suppressed_only = this._fields.show_suppressed_only || 0;
			const exclude_maintenance = this._fields.exclude_maintenance || 0;
			
			const url = new URL('zabbix.php', window.location.origin);
			url.searchParams.set('action', 'problem.view');
			url.searchParams.set('filter_set', '1');

			if (hostgroups.length > 0) hostgroups.forEach(gid => url.searchParams.append('groupids[]', gid));
			if (hosts.length > 0) hosts.forEach(hid => url.searchParams.append('hostids[]', hid));

			const selected_severities = this._fields.severities || [];
			if (selected_severities.length > 0) {
				selected_severities.forEach(sev_id => url.searchParams.append(`severities[${sev_id}]`, sev_id));
			}

			if (show_acknowledged == 0) url.searchParams.set('acknowledgement_status', '1');
			else url.searchParams.set('acknowledgement_status', '0');
			
			if (show_suppressed == 1 || show_suppressed_only == 1) {
				url.searchParams.set('show_suppressed', '1');
			} else {
				url.searchParams.set('show_suppressed', '0');
			}
			
			if (exclude_maintenance == 1) url.searchParams.set('maintenance_status', '0');
			else url.searchParams.set('maintenance_status', '1');

			if (tags.length > 0) {
				tags.forEach((tag, index) => {
					url.searchParams.append(`tags[${index}][tag]`, tag.tag);
					url.searchParams.append(`tags[${index}][operator]`, tag.operator);
					url.searchParams.append(`tags[${index}][value]`, tag.value);
				});
				url.searchParams.set('evaltype', evaltype);
			}

			window.open(url.toString(), '_blank');
		}
	}

	onMouseEnter(event) {
		if (this._hide_timer) {
			clearTimeout(this._hide_timer);
			this._hide_timer = null;
		}
		
		this._body.style.transform = 'scale(1.02)';
		this._body.style.transition = 'transform 0.2s ease';
		this._body.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';

		const widget_config = this._vars.widget_config || {};
		const alarm_data = this._vars.alarm_data || {};

		if (widget_config.show_detailed_tooltip && alarm_data.total_alarms > 0) {
			this.showDetailedTooltip();
		}
	}

	onMouseLeave(event) {
		this._body.style.transform = 'scale(1)';
		this._body.style.boxShadow = 'none';
		if (this._hide_timer) clearTimeout(this._hide_timer);
		this._hide_timer = setTimeout(this.hideTooltip.bind(this), 400);
	}

	showDetailedTooltip() {
		const alarm_data = this._vars.alarm_data || {};
		const detailed_alarms = alarm_data.detailed_alarms || [];
		const max_items = this._fields.tooltip_max_items || 10;

		if (detailed_alarms.length === 0) return;
		if (this._tooltip) return;

		this._tooltip = document.createElement('div');
		this._tooltip.className = 'hostgroup-alarms-tooltip';
		if (document.body.classList.contains('theme-dark')) this._tooltip.classList.add('dark-mode-tooltip');
		
		this._tooltip.innerHTML = this.buildTooltipContent(detailed_alarms, max_items);

		this._tooltip.addEventListener('mouseenter', this.onTooltipEnter.bind(this));
		this._tooltip.addEventListener('mouseleave', this.onTooltipLeave.bind(this));

		document.body.appendChild(this._tooltip);
		this.positionTooltip();
	}

	onTooltipEnter() {
		if (this._hide_timer) {
			clearTimeout(this._hide_timer);
			this._hide_timer = null;
		}
	}

	onTooltipLeave() {
		if (this._hide_timer) clearTimeout(this._hide_timer);
		this.hideTooltip();
	}

	// --- AQUI ESTÁ A MUDANÇA PRINCIPAL (TABELA) ---
	buildTooltipContent(alarms, max_items) {
		const displayed_alarms = alarms.slice(0, max_items);
		
		let html = `
			<table class="hostgroup-alarms-table">
				<thead>
					<tr>
						<th>Host</th>
						<th>Severity</th>
						<th>Problem</th>
						<th>Ack</th>
					</tr>
				</thead>
				<tbody>
		`;

		displayed_alarms.forEach(alarm => {
			const sev_class = 'sev-' + alarm.severity;
			const is_ack = (alarm.acknowledged == 1);
			const is_sup = (alarm.suppressed == 1);
			
			// Ícones
			let ack_html = '';
			if (is_sup) ack_html += '<span class="icon-eye-off" title="Suppressed" style="margin-right: 5px;"></span>';
			if (is_ack) ack_html += '<span style="margin-right: 5px;">✔</span>';
			
			// Botão Update/Ack
			if (alarm.eventid) {
				const btn_text = is_ack ? 'Update' : 'Ack';
				ack_html += `<a href="javascript:void(0)" class="hga-ack-btn" onclick="acknowledgePopUp({eventids: ['${alarm.eventid}']}); return false;">${btn_text}</a>`;
			}

			html += `
				<tr>
					<td>${this.escapeHtml(alarm.host_name)}</td>
					<td><span class="hga-severity ${sev_class}">${alarm.severity_name}</span></td>
					<td>${this.escapeHtml(alarm.description)}</td>
					<td>${ack_html}</td>
				</tr>
			`;
		});

		html += `</tbody></table>`;

		if (alarms.length > max_items) {
			const remaining = alarms.length - max_items;
			html += `<div class="hostgroup-alarms-footer">... and ${remaining} more alarms</div>`;
		}

		return html;
	}

	positionTooltip() {
		if (!this._tooltip || !this._body) return;

		const widget_rect = this._body.getBoundingClientRect();
		const tooltip_rect = this._tooltip.getBoundingClientRect();
		const viewport_width = window.innerWidth;
		const viewport_height = window.innerHeight;

		// Tenta centralizar embaixo primeiro
		let left = widget_rect.left + (widget_rect.width / 2) - (tooltip_rect.width / 2);
		let top = widget_rect.bottom + 10 + window.scrollY;

		// Ajuste horizontal (se sair da tela)
		if (left < 10) left = 10;
		if (left + tooltip_rect.width > viewport_width - 10) {
			left = viewport_width - tooltip_rect.width - 10;
		}

		// Ajuste vertical (se não couber embaixo, joga pra cima)
		if (top + tooltip_rect.height > viewport_height + window.scrollY - 10) {
			top = widget_rect.top + window.scrollY - tooltip_rect.height - 10;
		}

		this._tooltip.style.left = left + 'px';
		this._tooltip.style.top = top + 'px';
	}

	hideTooltip() {
		if (this._hide_timer) {
			clearTimeout(this._hide_timer);
			this._hide_timer = null;
		}
		if (this._tooltip) {
			if (this._tooltip.parentNode === document.body) {
				document.body.removeChild(this._tooltip);
			}
			this._tooltip = null;
		}
	}

	escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	getUpdateRequestData() {
		return {
			...super.getUpdateRequestData(),
			hostgroups: this._fields.hostgroups || [],
			hosts: this._fields.hosts || [],
			exclude_hosts: this._fields.exclude_hosts || [],
			severities: this._fields.severities || [],
			evaltype: this._fields.evaltype || 0,
			tags: this._fields.tags || [],
			show_acknowledged: this._fields.show_acknowledged || 0,
			show_suppressed: this._fields.show_suppressed || 0,
			show_suppressed_only: this._fields.show_suppressed_only || 0,
			exclude_maintenance: this._fields.exclude_maintenance || 0,
			show_group_name: this._fields.show_group_name || 1,
			group_name_text: this._fields.group_name_text || '',
			enable_url_redirect: this._fields.enable_url_redirect || 0,
			redirect_url: this._fields.redirect_url || '',
			open_in_new_tab: this._fields.open_in_new_tab || 1,
			show_detailed_tooltip: this._fields.show_detailed_tooltip || 1,
			tooltip_max_items: this._fields.tooltip_max_items || 10,
			font_size: this._fields.font_size || 14,
			font_family: this._fields.font_family || 'Arial, sans-serif',
			show_border: this._fields.show_border || 1,
			border_width: this._fields.border_width || 2,
			padding: this._fields.padding || 10
		};
	}

	setContents(response) {
		if (this._tooltip) this.hideTooltip();
		super.setContents(response);
		this._target.classList.remove('is-loading');

		if (response.fields_values) this._fields = response.fields_values;
		this.setContainerSize();

		if (response.alarm_data) this._vars.alarm_data = response.alarm_data;
		if (response.widget_config) this._vars.widget_config = response.widget_config;

		if (this._body) {
			this._body.removeEventListener('click', this.onWidgetClick.bind(this));
			this._body.removeEventListener('mouseenter', this.onMouseEnter.bind(this));
			this._body.removeEventListener('mouseleave', this.onMouseLeave.bind(this));
		}
		
		this._body = this._target.querySelector('.hostgroup-alarms-container');
		if (this._body !== null) {
			this._body.addEventListener('click', this.onWidgetClick.bind(this));
			this._body.addEventListener('mouseenter', this.onMouseEnter.bind(this));
			this._body.addEventListener('mouseleave', this.onMouseLeave.bind(this));
			this._body.style.cursor = 'pointer';
		}
	}

	hasPadding() {
		return false;
	}

	getRefreshInterval() {
		return (this._fields && this._fields.refresh_interval) ? this._fields.refresh_interval : 30;
	}
}
