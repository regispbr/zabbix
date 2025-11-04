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

jQuery(function($) {
	'use strict';

	// Initialize widget edit form
	$(document).ready(function() {
		initializeHostGroupAlarmsForm();
	});

	function initializeHostGroupAlarmsForm() {
		const form = $('form[name="widget_dialogue_form"]');
		
		if (form.length === 0) {
			return;
		}

		// Add event listeners for form changes
		const formFields = [
			'hostgroups',
			'hosts', 
			'show_group_name',
			'group_name_text',
			'enable_url_redirect',
			'redirect_url',
			'open_in_new_tab',
			'show_detailed_tooltip',
			'tooltip_max_items',
			'show_not_classified',
			'show_information',
			'show_warning',
			'show_average',
			'show_high',
			'show_disaster',
			'font_size',
			'font_family',
			'show_border',
			'border_width',
			'padding'
		];

		formFields.forEach(function(fieldName) {
			const field = form.find('[name="' + fieldName + '"]');
			if (field.length > 0) {
				field.on('change input', function() {
					updatePreview();
					toggleConditionalFields();
				});
			}
		});

		// Special handling for multiselect fields
		form.on('change', '.multiselect', function() {
			updatePreview();
		});

		// Add preview container
		addPreviewContainer();
		updatePreview();
		
		// Initialize conditional field visibility
		toggleConditionalFields();
	}

	function toggleConditionalFields() {
		const form = $('form[name="widget_dialogue_form"]');
		
		// Show/hide group name text field
		const showGroupNameField = form.find('[name="show_group_name"]');
		const groupNameTextField = form.find('[name="group_name_text"]').closest('.form-field');
		
		if (showGroupNameField.is(':checked')) {
			groupNameTextField.show();
		} else {
			groupNameTextField.hide();
		}

		// Show/hide URL redirect fields
		const enableUrlRedirectField = form.find('[name="enable_url_redirect"]');
		const redirectUrlField = form.find('[name="redirect_url"]').closest('.form-field');
		const openInNewTabField = form.find('[name="open_in_new_tab"]').closest('.form-field');
		
		if (enableUrlRedirectField.is(':checked')) {
			redirectUrlField.show();
			openInNewTabField.show();
		} else {
			redirectUrlField.hide();
			openInNewTabField.hide();
		}

		// Show/hide tooltip max items field
		const showDetailedTooltipField = form.find('[name="show_detailed_tooltip"]');
		const tooltipMaxItemsField = form.find('[name="tooltip_max_items"]').closest('.form-field');
		
		if (showDetailedTooltipField.is(':checked')) {
			tooltipMaxItemsField.show();
		} else {
			tooltipMaxItemsField.hide();
		}
	}

	function addPreviewContainer() {
		const form = $('form[name="widget_dialogue_form"]');
		let previewContainer = form.find('.hostgroup-alarms-preview');
		
		if (previewContainer.length === 0) {
			previewContainer = $('<div class="hostgroup-alarms-preview">')
				.css({
					'margin-top': '15px',
					'padding': '10px',
					'border': '1px solid #ccc',
					'background-color': '#f9f9f9',
					'border-radius': '4px'
				});
			
			const previewLabel = $('<label>').text('Preview:').css({
				'font-weight': 'bold',
				'display': 'block',
				'margin-bottom': '5px'
			});
			
			const previewContent = $('<div class="preview-content">')
				.css({
					'min-height': '120px',
					'min-width': '160px',
					'max-width': '200px',
					'border': '1px solid #ddd',
					'background-color': '#fff',
					'border-radius': '8px',
					'display': 'flex',
					'flex-direction': 'column',
					'justify-content': 'center',
					'align-items': 'center',
					'text-align': 'center',
					'position': 'relative',
					'margin': '0 auto'
				});
			
			previewContainer.append(previewLabel).append(previewContent);
			form.append(previewContainer);
		}
	}

	function updatePreview() {
		const form = $('form[name="widget_dialogue_form"]');
		const previewContent = form.find('.preview-content');
		
		if (previewContent.length === 0) {
			return;
		}

		// Get form values
		const showGroupName = form.find('[name="show_group_name"]').is(':checked');
		const groupNameText = form.find('[name="group_name_text"]').val() || 'Sample Group';
		const fontSize = parseInt(form.find('[name="font_size"]').val()) || 14;
		const fontFamily = form.find('[name="font_family"]').val() || 'Arial, sans-serif';
		const showBorder = form.find('[name="show_border"]').is(':checked');
		const borderWidth = parseInt(form.find('[name="border_width"]').val()) || 2;
		const padding = parseInt(form.find('[name="padding"]').val()) || 10;
		const enableUrlRedirect = form.find('[name="enable_url_redirect"]').is(':checked');
		const redirectUrl = form.find('[name="redirect_url"]').val();
		const showDetailedTooltip = form.find('[name="show_detailed_tooltip"]').is(':checked');

		// Get severity filters
		const severityFilters = {
			not_classified: form.find('[name="show_not_classified"]').is(':checked'),
			information: form.find('[name="show_information"]').is(':checked'),
			warning: form.find('[name="show_warning"]').is(':checked'),
			average: form.find('[name="show_average"]').is(':checked'),
			high: form.find('[name="show_high"]').is(':checked'),
			disaster: form.find('[name="show_disaster"]').is(':checked')
		};

		// Simulate alarm data based on enabled severities
		let simulatedSeverity = getHighestEnabledSeverity(severityFilters);
		let simulatedCount = simulatedSeverity >= 0 ? Math.floor(Math.random() * 10) + 1 : 0;
		
		// Get colors
		const colors = getSeverityColors(simulatedSeverity);
		
		// Build styles
		const styles = {
			'font-size': fontSize + 'px',
			'font-family': fontFamily,
			'color': colors.text,
			'background-color': colors.background,
			'padding': padding + 'px',
			'min-height': '120px',
			'min-width': '160px',
			'max-width': '200px',
			'display': 'flex',
			'flex-direction': 'column',
			'justify-content': 'center',
			'align-items': 'center',
			'text-align': 'center',
			'box-sizing': 'border-box',
			'position': 'relative'
		};

		if (showBorder) {
			styles['border'] = borderWidth + 'px solid ' + colors.background;
			styles['border-radius'] = '8px';
		} else {
			styles['border'] = 'none';
			styles['border-radius'] = '8px';
		}

		// Build content
		let content = '';
		
		if (showGroupName) {
			content += '<div style="font-weight: bold; margin-bottom: 8px; font-size: ' + (fontSize + 2) + 'px;">' + 
					   groupNameText + '</div>';
		}
		
		if (simulatedCount === 0) {
			content += '<div style="font-size: ' + (fontSize + 4) + 'px; font-weight: bold; margin-bottom: 4px;">OK</div>';
			content += '<div style="font-size: ' + (fontSize - 2) + 'px; opacity: 0.8;">No alarms</div>';
		} else {
			const severityNames = ['Not classified', 'Information', 'Warning', 'Average', 'High', 'Disaster'];
			const severityName = severityNames[simulatedSeverity] || 'Unknown';
			
			content += '<div style="font-size: ' + (fontSize + 2) + 'px; font-weight: bold; margin-bottom: 4px;">' + 
					   severityName + '</div>';
			content += '<div style="font-size: ' + fontSize + 'px;">' + 
					   simulatedCount + ' ' + (simulatedCount === 1 ? 'alarm' : 'alarms') + '</div>';
		}

		// Add configuration indicators
		let indicators = [];
		if (enableUrlRedirect && redirectUrl) {
			indicators.push('🔗 Custom URL');
		}
		if (showDetailedTooltip) {
			indicators.push('💬 Tooltip');
		}

		if (indicators.length > 0) {
			content += '<div style="font-size: 10px; margin-top: 8px; opacity: 0.7;">' + 
					   indicators.join(' | ') + '</div>';
		}

		// Apply styles and content
		previewContent.css(styles).html(content);
	}

	function getHighestEnabledSeverity(filters) {
		const severities = [
			{ level: 5, key: 'disaster' },
			{ level: 4, key: 'high' },
			{ level: 3, key: 'average' },
			{ level: 2, key: 'warning' },
			{ level: 1, key: 'information' },
			{ level: 0, key: 'not_classified' }
		];

		for (let severity of severities) {
			if (filters[severity.key]) {
				return severity.level;
			}
		}
		
		return -1; // No alarms
	}

	function getSeverityColors(severity) {
		const colorMap = {
			'-1': { background: '#00FF00', text: '#000000' }, // No alarms - green
			'0': { background: '#97AAB3', text: '#000000' },  // Not classified
			'1': { background: '#7499FF', text: '#FFFFFF' },  // Information
			'2': { background: '#FFC859', text: '#000000' },  // Warning
			'3': { background: '#FFA059', text: '#000000' },  // Average
			'4': { background: '#E97659', text: '#FFFFFF' },  // High
			'5': { background: '#E45959', text: '#FFFFFF' }   // Disaster
		};

		return colorMap[severity.toString()] || colorMap['-1'];
	}
});