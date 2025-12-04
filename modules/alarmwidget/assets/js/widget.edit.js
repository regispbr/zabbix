jQuery(function($) {
	'use strict';

	// Initialize widget edit form
	$(document).ready(function() {
		initializeAlarmWidgetForm();
	});

	function initializeAlarmWidgetForm() {
		const form = $('form[name="widget_dialogue_form"]');
		
		if (form.length === 0) {
			return;
		}

		// --- MUDANÇA: Lista de campos atualizada ---
		// (Incluindo os novos campos e corrigindo os 'show_column_')
		const formFields = [
			'groupids',
			'hostids',
			'exclude_hostids',
			'severities',
			'exclude_maintenance',
			'problem_status',
			'show_ack',
			'show_suppressed', // <-- ADICIONADO
			'sort_by', // <-- ADICIONADO
			'show_column_host',
			'show_column_severity',
			'show_column_status',
			'show_column_problem',
			'show_column_operational_data',
			'show_column_ack',
			'show_column_age',
			'show_column_time',
			'refresh_interval',
			'show_lines'
		];
		// --- FIM DA MUDANÇA ---

		formFields.forEach(function(fieldName) {
			// Procura por nome (para radio/select) ou 'name' terminando com [] (para multiselect)
			const field = form.find(`[name="${fieldName}"], [name="${fieldName}[]"]`);
			if (field.length > 0) {
				field.on('change', function() {
					validateForm();
				});
			}
		});
		
		// Validate form on load
		validateForm();
	}

	function validateForm() {
		const form = $('form[name="widget_dialogue_form"]');
		
		// Validate refresh interval
		const refreshInterval = parseInt(form.find('[name="refresh_interval"]').val());
		if (isNaN(refreshInterval) || refreshInterval < 10) {
			form.find('[name="refresh_interval"]').val(10);
		}

		// Validate show lines
		const showLines = parseInt(form.find('[name="show_lines"]').val());
		if (isNaN(showLines) || showLines < 1) {
			form.find('[name="show_lines"]').val(1);
		} else if (showLines > 100) {
			form.find('[name="show_lines"]').val(100);
		}

		// --- MUDANÇA: Lógica de validação das colunas ---
		// (Atualizado para os checkboxes individuais)
		const columnCheckboxes = form.find(
			'input[name="show_column_host"],' +
			'input[name="show_column_severity"],' +
			'input[name="show_column_status"],' +
			'input[name="show_column_problem"],' +
			'input[name="show_column_operational_data"],' +
			'input[name="show_column_ack"],' +
			'input[name="show_column_age"],' +
			'input[name="show_column_time"]'
		);

		const checkedCount = columnCheckboxes.filter(':checked').length;
		
		if (checkedCount === 0 && columnCheckboxes.length > 0) {
			// Se nenhum estiver marcado, força o 'problem' a ficar marcado
			form.find('input[name="show_column_problem"]').prop('checked', true);
		}
		// --- FIM DA MUDANÇA ---
	}

	// Add help text for fields
	function addFieldHelp() {
		const form = $('form[name="widget_dialogue_form"]');
		
		// Add help for refresh interval
		const refreshField = form.find('[name="refresh_interval"]').closest('.form-field');
		if (refreshField.length > 0 && refreshField.find('.field-help').length === 0) {
			refreshField.append(
				$('<div class="field-help">')
					.text('Minimum refresh interval is 10 seconds')
			);
		}

		// Add help for show lines
		const linesField = form.find('[name="show_lines"]').closest('.form-field');
		if (linesField.length > 0 && linesField.find('.field-help').length === 0) {
			linesField.append(
				$('<div class="field-help">')
					.text('Maximum 100 lines can be displayed')
			);
		}
	}

	// Call addFieldHelp after a short delay to ensure DOM is ready
	setTimeout(addFieldHelp, 100);
});
