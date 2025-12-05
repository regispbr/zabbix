/*
** Zabbix Host Counter Widget JavaScript
*/

class WidgetHostCounter extends CWidget {

	static ZBX_STYLE_CLASS = 'hostcounter-widget';

	onInitialize() {
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
		super.setContents(response);
		this._target.classList.remove('is-loading');

		if (response.fields_values) {
			this._fields = response.fields_values;
		}

		this.setContainerSize();

		if (response.counts) {
			this._vars.counts = response.counts;
		}

		this._body = this._target.querySelector('.hostcounter-container');
	}

	hasPadding() {
		return false;
	}

	getRefreshInterval() {
		return 30;
	}
}
