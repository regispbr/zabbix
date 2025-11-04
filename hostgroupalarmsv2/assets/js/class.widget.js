/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

class WidgetHostGroupAlarms extends CWidget {

	static ZBX_STYLE_CLASS = 'hostgroup-alarms-widget';

	onInitialize() {
		this._vars = {};
		this._body = this._target.querySelector('.hostgroup-alarms-container');
		this._tooltip = null;
		this.setContainerSize();
		// Remove loader immediately after initialization
		this._target.classList.remove('is-loading');

		// Add click handler for drill-down functionality
		if (this._body !== null) {
			this._body.addEventListener('click', this.onWidgetClick.bind(this));
			this._body.style.cursor = 'pointer';
			
			// Add hover handlers for tooltip
			this._body.addEventListener('mouseenter', this.onMouseEnter.bind(this));
			this._body.addEventListener('mouseleave', this.onMouseLeave.bind(this));
		}
	}

	onResize() {
		this.setContainerSize();
	}

	setContainerSize() {
		// Ensure the widget maintains a card-like aspect ratio
		if (this._body !== null && this._content_body) {
			const rect = this._content_body.getBoundingClientRect();
			const padding = parseInt(this._fields.padding) || 10;

			// Calculate available space
			const available_height = rect.height - (padding * 2);
			const available_width = rect.width - (padding * 2);

			// Set minimum dimensions for card-like appearance
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
		// Prevent default action and stop propagation
		event.preventDefault();
		event.stopPropagation();

		const widget_config = this._vars.widget_config || {};
		const alarm_data = this._vars.alarm_data || {};

		// Check if custom URL redirect is enabled
		if (widget_config.enable_url_redirect && widget_config.redirect_url) {
			const target = widget_config.open_in_new_tab ? '_blank' : '_self';
			window.open(widget_config.redirect_url, target);
		} else if (alarm_data.total_alarms > 0) {
			// Default behavior: Navigate to Problems page with filters
			const url = new URL('zabbix.php', window.location.origin);
			url.searchParams.set('action', 'problem.view');
			// Add group filter if available
			if (alarm_data.group_name) {
				url.searchParams.set('filter_groupids[]', alarm_data.group_name);
			}
			// Open in new tab/window
			window.open(url.toString(), '_blank');
		}
	}

	onMouseEnter(event) {
		const widget_config = this._vars.widget_config || {};
		const alarm_data = this._vars.alarm_data || {};
		
		// Add hover effect
		this._body.style.transform = 'scale(1.02)';
		this._body.style.transition = 'transform 0.2s ease';
		this._body.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';

		// Show detailed tooltip if enabled and there are alarms
		if (widget_config.show_detailed_tooltip && alarm_data.total_alarms > 0) {
			this.showDetailedTooltip(event);
		}
	}

	onMouseLeave(event) {
		// Remove hover effect
		this._body.style.transform = 'scale(1)';
		this._body.style.boxShadow = 'none';

		// Hide tooltip
		this.hideTooltip();
	}

	showDetailedTooltip(event) {
		const alarm_data = this._vars.alarm_data || {};
		const detailed_alarms = alarm_data.detailed_alarms || [];
		const max_items = this._fields.tooltip_max_items || 10;

		if (detailed_alarms.length === 0) {
			return;
		}

		// Create tooltip element
		this._tooltip = document.createElement('div');
		this._tooltip.className = 'hostgroup-alarms-tooltip';
		this._tooltip.innerHTML = this.buildTooltipContent(detailed_alarms, max_items);

		// Position tooltip
		document.body.appendChild(this._tooltip);
		this.positionTooltip(event);

		// Add event listeners to reposition tooltip on mouse move
		this._body.addEventListener('mousemove', this.onMouseMove.bind(this));
	}

	buildTooltipContent(alarms, max_items) {
		const displayed_alarms = alarms.slice(0, max_items);
		const severity_colors = {
			0: '#97AAB3', // Not classified
			1: '#7499FF', // Information
			2: '#FFC859', // Warning
			3: '#FFA059', // Average
			4: '#E97659', // High
			5: '#E45959'  // Disaster
		};

		let html = '<div class="tooltip-header">Alarm Details (' + alarms.length + ' total)</div>';

		displayed_alarms.forEach(alarm => {
			const time_formatted = new Date(alarm.clock * 1000).toLocaleString();
			const severity_color = severity_colors[alarm.severity] || '#97AAB3';
			const ack_status = alarm.acknowledged ? 'Acknowledged' : 'Not acknowledged';
			const ack_class = alarm.acknowledged ? 'acknowledged' : 'not-acknowledged';

			html += '<div class="tooltip-item" style="border-left: 4px solid ' + severity_color + ';">';
			html += '<div class="tooltip-time">' + time_formatted + '</div>';
			html += '<div class="tooltip-severity-host">';
			html += '<span class="tooltip-severity" style="color: ' + severity_color + ';">' + alarm.severity_name + '</span>';
			html += '<span class="tooltip-host"> - ' + this.escapeHtml(alarm.host_name) + '</span>';
			html += '</div>';
			html += '<div class="tooltip-description">' + this.escapeHtml(alarm.description) + '</div>';
			html += '<div class="tooltip-status ' + ack_class + '">' + ack_status + '</div>';
			
			if (alarm.eventid) {
				html += '<div class="tooltip-actions">';
				html += '<a href="tr_events.php?triggerid=' + alarm.triggerid + '&eventid=' + alarm.eventid + '" target="_blank">View Event</a>';
				if (!alarm.acknowledged) {
					html += ' | <a href="acknow.php?eventid=' + alarm.eventid + '" target="_blank">Acknowledge</a>';
				}
				html += '</div>';
			}
			
			html += '</div>';
		});

		if (alarms.length > max_items) {
			const remaining = alarms.length - max_items;
			html += '<div class="tooltip-more">... and ' + remaining + ' more alarms</div>';
		}

		return html;
	}

	positionTooltip(event) {
		if (!this._tooltip) {
			return;
		}

		const tooltip_rect = this._tooltip.getBoundingClientRect();
		const viewport_width = window.innerWidth;
		const viewport_height = window.innerHeight;

		let left = event.pageX + 10;
		let top = event.pageY + 10;

		// Adjust if tooltip would go off-screen
		if (left + tooltip_rect.width > viewport_width) {
			left = event.pageX - tooltip_rect.width - 10;
		}
		if (top + tooltip_rect.height > viewport_height) {
			top = event.pageY - tooltip_rect.height - 10;
		}

		this._tooltip.style.left = left + 'px';
		this._tooltip.style.top = top + 'px';
	}

	onMouseMove(event) {
		this.positionTooltip(event);
	}

	hideTooltip() {
		if (this._tooltip) {
			document.body.removeChild(this._tooltip);
			this._tooltip = null;
		}

		// Remove mouse move listener
		this._body.removeEventListener('mousemove', this.onMouseMove.bind(this));
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
			show_group_name: this._fields.show_group_name || 1,
			group_name_text: this._fields.group_name_text || '',
			enable_url_redirect: this._fields.enable_url_redirect || 0,
			redirect_url: this._fields.redirect_url || '',
			open_in_new_tab: this._fields.open_in_new_tab || 1,
			show_detailed_tooltip: this._fields.show_detailed_tooltip || 1,
			tooltip_max_items: this._fields.tooltip_max_items || 10,
			show_not_classified: this._fields.show_not_classified || 1,
			show_information: this._fields.show_information || 1,
			show_warning: this._fields.show_warning || 1,
			show_average: this._fields.show_average || 1,
			show_high: this._fields.show_high || 1,
			show_disaster: this._fields.show_disaster || 1,
			font_size: this._fields.font_size || 14,
			font_family: this._fields.font_family || 'Arial, sans-serif',
			show_border: this._fields.show_border || 1,
			border_width: this._fields.border_width || 2,
			padding: this._fields.padding || 10
		};
	}

	setContents(response) {
		super.setContents(response);

		// Remove loader after content is set
		this._target.classList.remove('is-loading');

		// Update the display after content change
		this.setContainerSize();

		// Update alarm data and widget config
		if (response.alarm_data) {
			this._vars.alarm_data = response.alarm_data;
		}
		if (response.widget_config) {
			this._vars.widget_config = response.widget_config;
		}

		// Re-initialize body element and event listeners
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

	// Auto-refresh every 30 seconds
	getRefreshInterval() {
		return 30;
	}
}