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
		initializeTextWidgetForm();
	});

	function initializeTextWidgetForm() {
		const form = $('form[name="widget_dialogue_form"]');
		
		if (form.length === 0) {
			return;
		}

		// Add event listeners for real-time preview updates
		const previewFields = [
			'font_size',
			'font_color',
			'background_color',
			'font_family',
			'text_align',
			'font_weight',
			'font_style',
			'line_height',
			'padding',
			'show_border',
			'border_color',
			'border_width'
		];

		previewFields.forEach(function(fieldName) {
			const field = form.find('[name="' + fieldName + '"]');
			if (field.length > 0) {
				field.on('change input', function() {
					updatePreview();
				});
			}
		});

		// Special handling for text content
		const textContentField = form.find('[name="text_content"]');
		if (textContentField.length > 0) {
			textContentField.on('input', function() {
				updatePreview();
			});
		}

		// Add preview container if it doesn't exist
		addPreviewContainer();
		updatePreview();
	}

	function addPreviewContainer() {
		const form = $('form[name="widget_dialogue_form"]');
		let previewContainer = form.find('.text-widget-preview');
		
		if (previewContainer.length === 0) {
			previewContainer = $('<div class="text-widget-preview">')
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
					'min-height': '50px',
					'border': '1px solid #ddd',
					'background-color': '#fff'
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
		const textContent = form.find('[name="text_content"]').val() || 'Enter your text here...';
		const fontSize = form.find('[name="font_size"]').val() || '14';
		const fontColor = '#' + (form.find('[name="font_color"]').val() || '000000');
		const backgroundColor = '#' + (form.find('[name="background_color"]').val() || 'FFFFFF');
		const fontFamily = form.find('[name="font_family"]').val() || 'Arial, sans-serif';
		const textAlign = getSelectValue(form.find('[name="text_align"]'), ['left', 'center', 'right', 'justify']);
		const fontWeight = getSelectValue(form.find('[name="font_weight"]'), ['normal', 'bold']);
		const fontStyle = getSelectValue(form.find('[name="font_style"]'), ['normal', 'italic']);
		const lineHeight = form.find('[name="line_height"]').val() || '120';
		const padding = form.find('[name="padding"]').val() || '10';
		const showBorder = form.find('[name="show_border"]').is(':checked');
		const borderColor = '#' + (form.find('[name="border_color"]').val() || 'CCCCCC');
		const borderWidth = form.find('[name="border_width"]').val() || '1';

		// Build styles
		const styles = {
			'font-size': fontSize + 'px',
			'color': fontColor,
			'background-color': backgroundColor,
			'font-family': fontFamily,
			'text-align': textAlign,
			'font-weight': fontWeight,
			'font-style': fontStyle,
			'line-height': lineHeight + '%',
			'padding': padding + 'px',
			'box-sizing': 'border-box',
			'overflow-wrap': 'break-word',
			'word-wrap': 'break-word',
			'min-height': '50px'
		};

		if (showBorder) {
			styles['border'] = borderWidth + 'px solid ' + borderColor;
		} else {
			styles['border'] = 'none';
		}

		// Apply styles and content
		previewContent.css(styles).html(textContent.replace(/\n/g, '<br>'));
	}

	function getSelectValue(selectElement, options) {
		if (selectElement.length === 0) {
			return options[0];
		}
		
		const selectedIndex = parseInt(selectElement.val()) || 0;
		return options[selectedIndex] || options[0];
	}
});
