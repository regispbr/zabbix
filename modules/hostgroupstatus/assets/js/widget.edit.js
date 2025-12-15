/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
** ... (Licença) ...
**/

jQuery(function($) {
	'use strict';

	// Initialize widget edit form
	$(document).ready(function() {
		initializeHostGroupStatusForm();
	});

	function initializeHostGroupStatusForm() {
		const form = $('form[name="widget_dialogue_form"]');
		if (form.length === 0) {
			return;
		}

		// ----- LISTA ATUALIZADA -----
		const formFields = [
			'hostgroups',
			'hosts', 
			'exclude_hosts',
			'evaltype',
			'tags',
			'show_acknowledged',
			'show_suppressed',
			'exclude_maintenance', // <-- MUDANÇA AQUI: Adicionado
			'count_mode',
			'problem_status',
			'widget_color',
			'show_group_name',
			'group_name_text',
			'enable_url_redirect',
			'redirect_url',
			'open_in_new_tab',
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
		// --------------------------

		formFields.forEach(function(fieldName) {
			// Procura por nome (para radio/select) ou 'name' terminando com [] (para multiselect)
			const field = form.find(`[name="${fieldName}"], [name="${fieldName}[]"]`);
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

		form.on('change', 'input[name^="tags["]', function() {
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
		
		const showGroupNameField = form.find('[name="show_group_name"]');
		const groupNameTextField = form.find('[name="group_name_text"]').closest('.form-field');
		
		if (showGroupNameField.is(':checked')) {
			groupNameTextField.show();
		} else {
			groupNameTextField.hide();
		}

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
	}

	function addPreviewContainer() {
		const form = $('form[name="widget_dialogue_form"]');
		let previewContainer = form.find('.hostgroup-status-preview');
		
		if (previewContainer.length === 0) {
			previewContainer = $('<div class="hostgroup-status-preview">')
				.css({
					'margin-top': '15px', 'padding': '10px', 'border': '1px solid #ccc',
					'background-color': '#f9f9f9', 'border-radius': '4px'
				});
			
			const previewLabel = $('<label>').text('Preview:').css({
				'font-weight': 'bold', 'display': 'block', 'margin-bottom': '5px'
			});
			
			const previewContent = $('<div class="preview-content">')
				.css({
					'min-height': '120px', 'min-width': '160px', 'max-width': '200px',
					'border': '1px solid #ddd', 'background-color': '#fff', 'border-radius': '8px',
					'display': 'flex', 'flex-direction': 'column', 'justify-content': 'center',
					'align-items': 'center', 'text-align': 'center', 'position': 'relative',
					'margin': '0 auto'
				});
			
			previewContainer.append(previewLabel).append(previewContent);
			form.append(previewContainer);
		}
	}

	function updatePreview() {
		const form = $('form[name="widget_dialogue_form"]');
		const previewContent = form.find('.preview-content');
		if (previewContent.length === 0) return;

		// Get form values
		const showGroupName = form.find('[name="show_group_name"]').is(':checked');
		const groupNameText = form.find('[name="group_name_text"]').val() || 'Sample Group';
		const fontSize = parseInt(form.find('[name="font_size"]').val()) || 14;
		const fontFamily = form.find('[name="font_family"]').val() || 'Arial, sans-serif';
		const showBorder = form.find('[name="show_border"]').is(':checked');
		const borderWidth = parseInt(form.find('[name="border_width"]').val()) || 2;
		const padding = parseInt(form.find('[name="padding"]').val()) || 10;
		const widgetColor = form.find('[name="widget_color"]').val() || '4CAF50';
		const countMode = parseInt(form.find('[name="count_mode"]:checked').val()) || 1;
		const enableUrlRedirect = form.find('[name="enable_url_redirect"]').is(':checked');
		const redirectUrl = form.find('[name="redirect_url"]').val();

		const backgroundColor = widgetColor.indexOf('#') === 0 ? widgetColor : '#' + widgetColor;
		const textColor = getContrastColor(backgroundColor);
		const simulatedCount = Math.floor(Math.random() * 20) + 1;
		
		const countModeLabels = {
			1: 'Hosts with alarms', 2: 'Hosts without alarms', 3: 'All hosts'
		};
		const countModeLabel = countModeLabels[countMode] || '';
		
		const styles = {
			'font-size': fontSize + 'px', 'font-family': fontFamily,
			'color': textColor, 'background-color': backgroundColor,
			'padding': padding + 'px', 'min-height': '120px',
			'min-width': '160px', 'max-width': '200px',
			'display': 'flex', 'flex-direction': 'column',
			'justify-content': 'center', 'align-items': 'center',
			'text-align': 'center', 'box-sizing': 'border-box',
			'position': 'relative'
		};

		if (showBorder) {
			styles['border'] = borderWidth + 'px solid ' + backgroundColor;
			styles['border-radius'] = '8px';
		} else {
			styles['border'] = 'none';
			styles['border-radius'] = '8px';
		}

		let content = '';
		if (showGroupName) {
			content += '<div style="font-weight: bold; margin-bottom: 8px; font-size: ' + (fontSize + 2) + 'px;">' + 
						 groupNameText + '</div>';
		}
		const hostText = simulatedCount + ' ' + (simulatedCount === 1 ? 'host' : 'hosts');
		content += '<div style="font-size: ' + (fontSize + 6) + 'px; font-weight: bold; margin-bottom: 4px;">' + 
					 hostText + '</div>';
		content += '<div style="font-size: ' + (fontSize - 2) + 'px; opacity: 0.9;">' + 
					 countModeLabel + '</div>';

		let indicators = [];
		if (enableUrlRedirect && redirectUrl) {
			indicators.push('🔗 Custom URL');
		}

		if (indicators.length > 0) {
			content += '<div style="font-size: 10px; margin-top: 8px; opacity: 0.7;">' + 
						 indicators.join(' | ') + '</div>';
		}
		previewContent.css(styles).html(content);
	}

	function getContrastColor(hexColor) {
		hexColor = hexColor.replace('#', '');
		const r = parseInt(hexColor.substr(0, 2), 16);
		const g = parseInt(hexColor.substr(2, 2), 16);
		const b = parseInt(hexColor.substr(4, 2), 16);
		const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
		return luminance > 0.5 ? '#000000' : '#FFFFFF';
	}
});
