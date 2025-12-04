/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

class WidgetHostCounter extends CWidget {

	static ZBX_STYLE_CLASS = 'hostcounter-widget';

	onInitialize() {
		console.log('DEBUG [hostcounter]: Widget initialized.');
		this._vars = {};
		this._target.classList.remove('is-loading');
	}

	onResize() {
		this.setContainerSize();
	}

	setContainerSize() {
		if (this._body !== null && this._content_body) {
			const rect = this._content_body.getBoundingClientRect();
			const padding = 10;

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

	getUpdateRequestData() {
		console.log('DEBUG [hostcounter]: Getting update request data...');
		
		const fields = this.fields_values || this._fields || {};
		
		return {
			...super.getUpdateRequestData(),
			hostgroups: fields.hostgroups || [],
			hosts: fields.hosts || [],
			count_problems: fields.count_problems || 1,
			count_items: fields.count_items || 1,
			count_triggers: fields.count_triggers || 1,
			count_disabled: fields.count_disabled || 1,
			count_maintenance: fields.count_maintenance || 1,
			show_suppressed: fields.show_suppressed || 0,
			custom_icon: fields.custom_icon || ''
		};
	}

	setContents(response) {
		console.log('DEBUG [hostcounter]: Setting contents...');
		
		// Draw HTML
		super.setContents(response);
		this._target.classList.remove('is-loading');

		// Populate data
		if (response.fields_values) {
			console.log('DEBUG [hostcounter]: Populating fields with:', response.fields_values);
			this._fields = response.fields_values;
		}

		// Set container size
		this.setContainerSize();

		if (response.counts) {
			this._vars.counts = response.counts;
		}

		// Find the container
		this._body = this._target.querySelector('.hostcounter-container');
		if (this._body !== null) {
			console.log('DEBUG [hostcounter]: Container found successfully.');
		} else {
			console.error('DEBUG [hostcounter]: Container not found.');
		}
	}

	hasPadding() {
		return false;
	}

	getRefreshInterval() {
		return 30; // 30 seconds
	}
}
