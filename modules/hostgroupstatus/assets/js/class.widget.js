/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

class WidgetHostGroupStatus extends CWidget {

	static ZBX_STYLE_CLASS = 'hostgroup-status-widget';

	onInitialize() {
		console.log('DEBUG [hostgroupstatus]: 1. onInitialize() - Widget iniciado.');
		this._vars = {};
		this._target.classList.remove('is-loading');
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

			if (available_height > 0) {
				this._body.style.height = Math.max(available_height, min_height) + 'px';
			}
			if (available_width > 0) {
				this._body.style.width = Math.max(available_width, min_width) + 'px';
			}
		}
	}

	onWidgetClick(event) {
		console.log('%cDEBUG [hostgroupstatus]: 4. onWidgetClick() - CLIQUE DETECTADO.', 'color: #00FF00; font-weight: bold;');
		event.preventDefault();
		event.stopPropagation();

		const widget_config = this._vars.widget_config || {}; 
		const host_data = this._vars.host_data || {};

		if (widget_config.enable_url_redirect && widget_config.redirect_url) {
			console.log('DEBUG [hostgroupstatus]: 4. onWidgetClick() - Redirecionamento customizado.');
			const target = widget_config.open_in_new_tab ? '_blank' : '_self';
			window.open(widget_config.redirect_url, target);
		} 
		else {
			console.log('DEBUG [hostgroupstatus]: 4. onWidgetClick() - Gerando URL padrão (host.view)...');
			
			if (!this._fields) {
				console.error('DEBUG [hostgroupstatus]: 4. onWidgetClick() - ERRO FATAL: this._fields é INDEFINIDO.');
				return;
			}

			const hostgroups = this._fields.hostgroups || [];
			const hosts = this._fields.hosts || [];
			const tags = this._fields.tags || [];
			const evaltype = this._fields.evaltype || 0;
			const show_suppressed = this._fields.show_suppressed || 0;
			const exclude_maintenance = this._fields.exclude_maintenance || 0;
			
			const url = new URL('zabbix.php', window.location.origin);
			url.searchParams.set('action', 'host.view');
			url.searchParams.set('filter_set', '1');

			if (hostgroups.length > 0) {
				hostgroups.forEach(groupid => url.searchParams.append('groupids[]', groupid));
			}
			if (hosts.length > 0) {
				hosts.forEach(hostid => url.searchParams.append('hostids[]', hostid));
			}

			// --- MUDANÇA: SEVERIDADE ---
			// Lê do novo array 'severities'
			const selected_severities = this._fields.severities || [];
			if (selected_severities.length > 0) {
				selected_severities.forEach(sev_id => {
					url.searchParams.append(`severities[${sev_id}]`, sev_id);
				});
			}
			// --- FIM DA MUDANÇA ---
			
			if (show_suppressed == 1) {
				url.searchParams.set('show_suppressed', '1');
			} else {
				url.searchParams.set('show_suppressed', '0');
			}
			
			if (exclude_maintenance == 1) {
				url.searchParams.set('maintenance_status', '0');
			} else {
				url.searchParams.set('maintenance_status', '1');
			}

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
		this._body.style.transform = 'scale(1.02)';
		this._body.style.transition = 'transform 0.2s ease';
		this._body.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
	}

	onMouseLeave(event) {
		this._body.style.transform = 'scale(1)';
		this._body.style.boxShadow = 'none';
	}

	// Esta função é usada pelo Zabbix para atualizar o widget
	getUpdateRequestData() {
		return {
			...super.getUpdateRequestData(),
			hostgroups: this._fields.hostgroups || [],
			hosts: this._fields.hosts || [],
			exclude_hosts: this._fields.exclude_hosts || [],
			
			// --- MUDANÇA: Envia o array de severities ---
			severities: this._fields.severities || [],
			// ------------------------------------------
			
			evaltype: this._fields.evaltype || 0,
			tags: this._fields.tags || [],
			show_acknowledged: this._fields.show_acknowledged || 0,
			show_suppressed: this._fields.show_suppressed || 0,
			exclude_maintenance: this._fields.exclude_maintenance || 0,

			count_mode: this._fields.count_mode || 1,
			widget_color: this._fields.widget_color || '4CAF50',
			show_group_name: this._fields.show_group_name || 1,
			group_name_text: this._fields.group_name_text || '',
			enable_url_redirect: this._fields.enable_url_redirect || 0,
			redirect_url: this._fields.redirect_url || '',
			open_in_new_tab: this._fields.open_in_new_tab || 1,
			font_size: this._fields.font_size || 14,
			font_family: this._fields.font_family || 'Arial, sans-serif',
			show_border: this._fields.show_border || 1,
			border_width: this._fields.border_width || 2,
			padding: this._fields.padding || 10
		};
	}

	setContents(response) {
		super.setContents(response);
		this._target.classList.remove('is-loading');

		if (response.fields_values) {
			this._fields = response.fields_values;
		}

		this.setContainerSize();

		if (response.host_data) {
			this._vars.host_data = response.host_data;
		}
		if (response.widget_config) {
			this._vars.widget_config = response.widget_config;
		}

		if (this._body) {
			this._body.removeEventListener('click', this.onWidgetClick.bind(this));
			this._body.removeEventListener('mouseenter', this.onMouseEnter.bind(this));
			this._body.removeEventListener('mouseleave', this.onMouseLeave.bind(this));
		}
		
		this._body = this._target.querySelector('.hostgroup-status-container');
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
