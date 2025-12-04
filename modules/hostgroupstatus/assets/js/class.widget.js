/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
** ... (Licença) ...
**
** VERSÃO CORRIGIDA (Bug do EventListener)
**/

class WidgetHostGroupStatus extends CWidget {

	static ZBX_STYLE_CLASS = 'hostgroup-status-widget';

	onInitialize() {
		// --- MUDANÇA: Lógica de clique REMOVIDA daqui ---
		console.log('DEBUG [hostgroupstatus]: 1. onInitialize() - Widget iniciado.');
		this._vars = {};
		// this._body e os listeners serão definidos no setContents()
		
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

	// --- FUNÇÃO onWidgetClick (Lógica está correta) ---
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
			console.log('DEBUG [hostgroupstatus]: 4. onWidgetClick() - Lendo filtros de this._fields:', this._fields);

			const hostgroups = this._fields.hostgroups || [];
			const hosts = this._fields.hosts || [];
			const tags = this._fields.tags || [];
			const evaltype = this._fields.evaltype || 0;
			const show_suppressed = this._fields.show_suppressed || 0;
			const exclude_maintenance = this._fields.exclude_maintenance || 0;
			
			console.log(`DEBUG [hostgroupstatus]: 4. onWidgetClick() - Valor lido de exclude_maintenance: ${exclude_maintenance}`);
			
			const url = new URL('zabbix.php', window.location.origin);
			url.searchParams.set('action', 'host.view');
			url.searchParams.set('filter_set', '1');

			if (hostgroups.length > 0) {
				hostgroups.forEach(groupid => url.searchParams.append('groupids[]', groupid));
			}
			if (hosts.length > 0) {
				hosts.forEach(hostid => url.searchParams.append('hostids[]', hostid));
			}

			const severities_map = {
				'show_not_classified': 0, 'show_information': 1, 'show_warning': 2,
				'show_average': 3, 'show_high': 4, 'show_disaster': 5
			};
			let all_severities_checked = true;
			for (const key of Object.keys(severities_map)) {
				if (!this._fields[key]) {
					all_severities_checked = false;
					break;
				}
			}
			if (!all_severities_checked) {
				console.log('DEBUG [hostgroupstatus]: 4. onWidgetClick() - Aplicando filtros de severidade.');
				for (const [key, severity_id] of Object.entries(severities_map)) {
					if (this._fields[key]) { 
						url.searchParams.append(`severities[${severity_id}]`, severity_id);
					}
				}
			}
			
			if (show_suppressed == 1) {
				url.searchParams.set('show_suppressed', '1');
			} else {
				url.searchParams.set('show_suppressed', '0');
			}
			
			if (exclude_maintenance == 1) {
				console.log('DEBUG [hostgroupstatus]: 4. onWidgetClick() - LÓGICA: Exclude é 1. Definindo maintenance_status=0 (Desmarcado)');
				url.searchParams.set('maintenance_status', '0');
			} else {
				console.log('DEBUG [hostgroupstatus]: 4. onWidgetClick() - LÓGICA: Exclude é 0. Definindo maintenance_status=1 (Marcado)');
				url.searchParams.set('maintenance_status', '1');
			}

			if (tags.length > 0) {
				console.log('DEBUG [hostgroupstatus]: 4. onWidgetClick() - Aplicando filtros de tags.');
				tags.forEach((tag, index) => {
					url.searchParams.append(`tags[${index}][tag]`, tag.tag);
					url.searchParams.append(`tags[${index}][operator]`, tag.operator);
					url.searchParams.append(`tags[${index}][value]`, tag.value);
				});
				url.searchParams.set('evaltype', evaltype);
			}

			console.log(`DEBUG [hostgroupstatus]: 4. onWidgetClick() - URL FINAL: ${url.toString()}`);
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

	getUpdateRequestData() {
		console.log('DEBUG [hostgroupstatus]: 3. getUpdateRequestData() - Coletando dados para refresh...');
		
		const fields = this.fields_values || this._fields || {};
		
		console.log('DEBUG [hostgroupstatus]: 3. getUpdateRequestData() - Valor de exclude_maintenance enviado ao backend:', fields.exclude_maintenance);
		
		return {
			...super.getUpdateRequestData(),
			hostgroups: fields.hostgroups || [],
			hosts: fields.hosts || [],
			
			exclude_hosts: fields.exclude_hosts || [],
			evaltype: fields.evaltype || 0,
			tags: fields.tags || [],
			show_acknowledged: fields.show_acknowledged || 0,
			show_suppressed: fields.show_suppressed || 0,
			show_not_classified: fields.show_not_classified || 1,
			show_information: fields.show_information || 1,
			show_warning: fields.show_warning || 1,
			show_average: fields.show_average || 1,
			show_high: fields.show_high || 1,
			show_disaster: fields.show_disaster || 1,
			
			exclude_maintenance: fields.exclude_maintenance || 0,

			count_mode: fields.count_mode || 1,
			widget_color: fields.widget_color || '4CAF50',
			show_group_name: fields.show_group_name || 1,
			group_name_text: fields.group_name_text || '',
			enable_url_redirect: fields.enable_url_redirect || 0,
			redirect_url: fields.redirect_url || '',
			open_in_new_tab: fields.open_in_new_tab || 1,
			font_size: fields.font_size || 14,
			font_family: fields.font_family || 'Arial, sans-serif',
			show_border: fields.show_border || 1,
			border_width: fields.border_width || 2,
			padding: fields.padding || 10
		};
	}

	// --- FUNÇÃO setContents (COM A CORREÇÃO) ---
	setContents(response) {
		console.log('DEBUG [hostgroupstatus]: 2. setContents() - Recebendo dados do backend.');
		
		// 1. O HTML é desenhado AQUI
		super.setContents(response);
		this._target.classList.remove('is-loading');

		// 2. Os dados são populados AQUI
		if (response.fields_values) {
			console.log('DEBUG [hostgroupstatus]: 2. setContents() - Populando this._fields com:', response.fields_values);
			this._fields = response.fields_values;
		} else {
			console.error('DEBUG [hostgroupstatus]: 2. setContents() - ERRO: Backend não enviou "fields_values". O clique vai falhar.');
		}

		// 3. O resize é chamado AQUI
		this.setContainerSize();

		if (response.host_data) {
			this._vars.host_data = response.host_data;
		}
		if (response.widget_config) {
			this._vars.widget_config = response.widget_config;
		}

		// --- MUDANÇA: Lógica de clique MOVIDA para cá ---
		// 4. Os listeners são anexados AGORA (pois o HTML já existe)
		if (this._body) {
			// Remove listeners antigos para evitar duplicação
			this._body.removeEventListener('click', this.onWidgetClick.bind(this));
			this._body.removeEventListener('mouseenter', this.onMouseEnter.bind(this));
			this._body.removeEventListener('mouseleave', this.onMouseLeave.bind(this));
		}

	this._body = this._target.querySelector('.hostgroup-status-container');
		if (this._body !== null) {
			console.log('DEBUG [hostgroupstatus]: 2. setContents() - Sucesso! Anexando listener de CLIQUE.');
			this._body.addEventListener('click', this.onWidgetClick.bind(this));
			this._body.addEventListener('mouseenter', this.onMouseEnter.bind(this));
			this._body.addEventListener('mouseleave', this.onMouseLeave.bind(this));
			this._body.style.cursor = 'pointer';
		} else {
			// Se este erro aparecer, o problema é no seu arquivo .view.php
			console.error('DEBUG [hostgroupstatus]: 2. setContents() - ERRO GRAVE: .hostgroup-status-container não encontrado mesmo após setContents().');
		}
		// --- FIM DA MUDANÇA ---
	}

	hasPadding() {
		return false;
	}

	getRefreshInterval() {
		const interval = (this._fields && this._fields.refresh_interval) ? this._fields.refresh_interval : 30;
		return interval;
	}
}
