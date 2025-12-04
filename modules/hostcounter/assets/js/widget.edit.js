/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
*/

jQuery(function($) {
	'use strict';

	// Initialize widget edit form
	$(document).ready(function() {
		initializeHostCounterForm();
	});

	function initializeHostCounterForm() {
		const form = $('form[name="widget_dialogue_form"]');
		if (form.length === 0) {
			return;
		}

		const formFields = [
			'hostgroups',
			'hosts',
			'count_problems',
			'count_items',
			'count_triggers',
			'count_disabled',
			'count_maintenance',
			'show_suppressed',
			'custom_icon'
		];

		formFields.forEach(function(fieldName) {
			const field = form.find(`[name="${fieldName}"], [name="${fieldName}[]"]`);
			if (field.length > 0) {
				field.on('change input', function() {
					updatePreview();
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
	}

	function addPreviewContainer() {
		const form = $('form[name="widget_dialogue_form"]');
		let previewContainer = form.find('.hostcounter-preview');
		
		if (previewContainer.length === 0) {
			previewContainer = $('<div class="hostcounter-preview">')
				.css({
					'margin-top': '15px', 'padding': '10px', 'border': '1px solid #ccc',
					'background-color': '#f9f9f9', 'border-radius': '4px'
				});
			
			const previewLabel = $('<label>').text('Preview:').css({
				'font-weight': 'bold', 'display': 'block', 'margin-bottom': '5px'
			});
			
			const previewContent = $('<div class="preview-content">')
				.css({
					'min-height': '120px', 'min-width': '160px', 'max-width': '300px',
					'border': '1px solid #ddd', 'background-color': '#fff', 'border-radius': '8px',
					'display': 'flex', 'flex-wrap': 'wrap', 'justify-content': 'space-around',
					'align-items': 'center', 'padding': '10px', 'gap': '10px',
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
		const countProblems = form.find('[name="count_problems"]').is(':checked');
		const countItems = form.find('[name="count_items"]').is(':checked');
		const countTriggers = form.find('[name="count_triggers"]').is(':checked');
		const countDisabled = form.find('[name="count_disabled"]').is(':checked');
		const countMaintenance = form.find('[name="count_maintenance"]').is(':checked');
		const customIcon = form.find('[name="custom_icon"]').val();

		// Simulate counter data
		const counters = [
			{ label: 'Total Hosts', value: Math.floor(Math.random() * 50) + 10, class: 'counter-total' },
			{ label: 'Active Hosts', value: Math.floor(Math.random() * 40) + 5, class: 'counter-active' }
		];

		if (countDisabled) {
			counters.push({ label: 'Disabled', value: Math.floor(Math.random() * 5), class: 'counter-disabled' });
		}

		if (countMaintenance) {
			counters.push({ label: 'Maintenance', value: Math.floor(Math.random() * 3), class: 'counter-maintenance' });
		}

		if (countProblems) {
			counters.push({ label: 'Problems', value: Math.floor(Math.random() * 15), class: 'counter-problems' });
		}

		if (countItems) {
			counters.push({ label: 'Items', value: Math.floor(Math.random() * 200) + 50, class: 'counter-items' });
		}

		if (countTriggers) {
			counters.push({ label: 'Triggers', value: Math.floor(Math.random() * 100) + 20, class: 'counter-triggers' });
		}

		let content = '';
		
		// Add icon if specified
		if (customIcon) {
			content += '<div style="position: absolute; top: 5px; right: 5px; font-size: 16px;">📊</div>';
		}

		// Add counters
		counters.forEach(function(counter) {
			const borderColor = getBorderColor(counter.class);
			content += '<div style="display: inline-block; margin: 2px; padding: 8px; border: 1px solid #ddd; ' +
					   'border-left: 4px solid ' + borderColor + '; border-radius: 4px; min-width: 60px; ' +
					   'background: #f9f9f9; text-align: center;">' +
					   '<div style="font-size: 16px; font-weight: bold;">' + counter.value + '</div>' +
					   '<div style="font-size: 10px; margin-top: 2px;">' + counter.label + '</div>' +
					   '</div>';
		});

		previewContent.html(content);
	}

	function getBorderColor(className) {
		const colors = {
			'counter-total': '#337ab7',
			'counter-active': '#5cb85c',
			'counter-disabled': '#777',
			'counter-maintenance': '#f0ad4e',
			'counter-problems': '#d9534f',
			'counter-items': '#5bc0de',
			'counter-triggers': '#9b59b6'
		};
		return colors[className] || '#337ab7';
	}
});
