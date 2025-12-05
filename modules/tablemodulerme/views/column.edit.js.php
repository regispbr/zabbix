<?php

use Modules\TableModuleRME\Includes\CWidgetFieldColumnsList;

?>

window.tablemodulerme_column_edit_form = new class {

	/**
	 * @type {Overlay}
	 */
	#overlay;

	/**
	 * @type {HTMLElement}
	 */
	#dialogue;

	/**
	 * @type {HTMLFormElement}
	 */
	#form;

	/**
	 * @type {HTMLTableElement}
	 */
	#thresholds_table;

	/**
	 * @type {HTMLTableElement}
	 */
	#highlights_table;

	init({form_id, thresholds, highlights, colors}) {
		this.aggregation_error = false;
		this.#overlay = overlays_stack.getById('tablemodulerme-column-edit-overlay');
		this.#dialogue = this.#overlay.$dialogue[0];
		this.#form = document.getElementById(form_id);

		this.#thresholds_table = document.getElementById('thresholds_table');
		this.#highlights_table = document.getElementById('highlights_table');

		this.#form
			.querySelectorAll(
				'[name="display_value_as"], [name="aggregate_function"], [name="column_agg_method"], [name="display"], [name="history"], [name="url_display_mode"]'
			)
			.forEach(element => {
				element.addEventListener('change', () => this.#updateForm());
			});

		this.#form.addEventListener('change', ({target}) => {
			if (target.matches('[type="text"]')) {
				target.value = target.value.trim();
			}
		});

		colorPalette.setThemeColors(colors);
		
		const parentForm = document.getElementsByClassName('modal-widget-configuration').item(0);
		this.tgt = this.#findRecurse(parentForm, 'field_item_group_by');
		this.all_hosts_aggregated = this.#findRecurse(parentForm, 'field_aggregate_all_hosts');

		// Initialize thresholds table.
		$(this.#thresholds_table)
			.dynamicRows({
				rows: thresholds,
				template: '#thresholds-row-tmpl',
				allow_empty: true,
				dataCallback: (row_data) => {
					if (!('color' in row_data)) {
						// CORREÇÃO: Removido ZBX_STYLE_COLOR_PICKER e usado seletor genérico de inputs de cor
						const color_inputs = this.#form.querySelectorAll('input[name$="[color]"]');
						const used_colors = [];

						for (const input of color_inputs) {
							if (input.value !== '') {
								used_colors.push(input.value);
							}
						}

						row_data.color = colorPalette.getNextColor(used_colors);
					}
				}
			})
			.on('afteradd.dynamicRows', () => this.#updateForm())
			.on('afterremove.dynamicRows', () => this.#updateForm())

		// Initialize highlights table.
		$(this.#highlights_table)
			.dynamicRows({
				rows: highlights,
				template: '#highlights-row-tmpl',
				allow_empty: true,
				dataCallback: (row_data) => {
					if (!('color' in row_data)) {
						// CORREÇÃO: Mesma correção para highlights
						const color_inputs = this.#form.querySelectorAll('input[name$="[color]"]');
						const used_colors = [];

						for (const input of color_inputs) {
							if (input.value !== '') {
								used_colors.push(input.value);
							}
						}

						row_data.color = colorPalette.getNextColor(used_colors);
					}
				}
			})
			.on('afteradd.dynamicRows', () => this.#updateForm())
			.on('afterremove.dynamicRows', () => this.#updateForm());

		// Initialize Advanced configuration collapsible.
		const collapsible = this.#form.querySelector(`fieldset.collapsible`);
		if (collapsible) {
			new CFormFieldsetCollapsible(collapsible);
		}

		// Field trimming.
		this.#form.querySelectorAll('[name="min"], [name="max"]').forEach(element => {
			element.addEventListener('change', (e) => e.target.value = e.target.value.trim(), {capture: true});
		});

		// Initialize form elements accessibility.
		this.#updateForm();

		this.#form.style.display = '';
		this.#overlay.recoverFocus();

		this.#form.addEventListener('submit', (e) => {
			e.preventDefault();
			this.submit();
		});
	}
	
	#findRecurse(parent, classToSearch) {
		if (!parent) return null;
		
		if (parent.classList && parent.classList.contains(classToSearch)) {
			return parent;
		}
		
		for (let i = 0; i < parent.children.length; i++) {
			const child = parent.children[i];
			const result = this.#findRecurse(child, classToSearch);
			if (result) {
				return result;
			}
		}
		
		return null;
	}

	#updateForm() {
		const display_value_as = this.#form.querySelector('[name=display_value_as]:checked').value;
		const display = this.#form.querySelector('[name=display]:checked').value;
		const url_display_mode = this.#form.querySelector('[name=url_display_mode]:checked').value;

		// Column title	
		const titleFields = this.#form.querySelectorAll('.js-column-title');
		for (let i = 0; i < titleFields.length; i++) {
			const element = titleFields[i];
			element.style.display = (this.tgt && this.tgt.style.display == 'none') ? 'none' : '';
		}

		// Broadcast in grouped cell
		const broadcastFields = this.#form.querySelectorAll('.js-broadcast-in-group-cell');
		for (let i = 0; i < broadcastFields.length; i++) {
			const element = broadcastFields[i];
			element.style.display = (this.tgt && this.tgt.style.display == 'none') ? 'none' : '';
		}

		// Display.
		const display_show = display_value_as == 1;
		const displayRows = this.#form.querySelectorAll('.js-display-row');
		for (let i = 0; i < displayRows.length; i++) {
			const element = displayRows[i];
			element.style.display = display_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !display_show;
			}
		}

		// Sparkline.
		const sparkline_show = display_value_as == 1 && display == 6;

		const sparklineRows = this.#form.querySelectorAll('.js-sparkline-row');
		for (let i = 0; i < sparklineRows.length; i++) {
			const element = sparklineRows[i];
			element.style.display = sparkline_show ? '' : 'none';
			
			const inputs = element.querySelectorAll('input, select, textarea, button');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !sparkline_show;
			}
		}

		// Min/Max.
		const min_max_show = display_value_as == 1 && ['2', '3'].includes(display);
		
		const minMaxRows = this.#form.querySelectorAll('.js-min-max-row');
		for (let i = 0; i < minMaxRows.length; i++) {
			const element = minMaxRows[i];
			element.style.display = min_max_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !min_max_show;
			}
		}

		// Highlights.
		const highlights_show = display_value_as == 2;
		const highlightRows = this.#form.querySelectorAll('.js-highlights-row');
		for (let i = 0; i < highlightRows.length; i++) {
			const element = highlightRows[i];
			element.style.display = highlights_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !highlights_show;
			}
		}

		// URL display options.
		const display_url = display_value_as == 100;
		const url_override_show = display_url && url_display_mode == 2;

		const urlDisplayModeRows = this.#form.querySelectorAll('.js-url-display-mode');
		for (let i = 0; i < urlDisplayModeRows.length; i++) {
			const element = urlDisplayModeRows[i];
			element.style.display = display_url ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !display_url;
			}
		}

		const urlOverrideRows = this.#form.querySelectorAll('.js-url-display-override');
		for (let i = 0; i < urlOverrideRows.length; i++) {
			const element = urlOverrideRows[i];
			element.style.display = url_override_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !url_override_show;
			}
		}

		const urlCustomRows = this.#form.querySelectorAll('.js-url-custom-override');
		for (let i = 0; i < urlCustomRows.length; i++) {
			const element = urlCustomRows[i];
			element.style.display = url_override_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !url_override_show;
			}
		}

		const urlOpenInRows = this.#form.querySelectorAll('.js-url-open-in');
		for (let i = 0; i < urlOpenInRows.length; i++) {
			const element = urlOpenInRows[i];
			element.style.display = display_url ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !display_url;
			}
		}

		// Thresholds.
		const thresholds_show = display_value_as == 1;
		const thresholdRows = this.#form.querySelectorAll('.js-thresholds-row');
		for (let i = 0; i < thresholdRows.length; i++) {
			const element = thresholdRows[i];
			element.style.display = thresholds_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !thresholds_show;
			}
		}

		// Decimal places.
		const decimals_show = display_value_as == 1;
		const decimalRows = this.#form.querySelectorAll('.js-decimals-row');
		for (let i = 0; i < decimalRows.length; i++) {
			const element = decimalRows[i];
			element.style.display = decimals_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !decimals_show;
			}
		}

		// Column aggregation.		
		const colAggRows = this.#form.querySelectorAll('.js-column-agg-row');
		for (let i = 0; i < colAggRows.length; i++) {
			const element = colAggRows[i];
			element.style.display = (this.tgt && this.tgt.style.display == 'none') ? 'none' : '';
		}

		// Aggregation function.
		const aggregation_function_select = this.#form.querySelector('z-select[name=aggregate_function]');
		if (aggregation_function_select) {
			[1, 2, 3, 5].forEach(option => {
				const opt = aggregation_function_select.getOptionByValue(option);
				if (opt) {
					opt.disabled = display_value_as != 1;
					opt.hidden = display_value_as != 1;
				}

				if (aggregation_function_select.value == option && display_value_as != 1) {
					aggregation_function_select.value = 0;
				}
			});
		}

		// Time period.
		const aggFuncInput = document.getElementById('aggregate_function');
		const time_period_show = aggFuncInput && parseInt(aggFuncInput.value) != 0;
		
		const timePeriodRows = this.#form.querySelectorAll('.js-time-period');
		for (let i = 0; i < timePeriodRows.length; i++) {
			const element = timePeriodRows[i];
			element.style.display = time_period_show ? '' : 'none';
			const inputs = element.querySelectorAll('input, select, textarea, button');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !time_period_show;
			}
		}

		// History data.
		const history_show = display_value_as == 1;
		const historyRows = this.#form.querySelectorAll('.js-history-row');
		for (let i = 0; i < historyRows.length; i++) {
			const element = historyRows[i];
			element.style.display = history_show ? '' : 'none';
			const inputs = element.querySelectorAll('input');
			for (let j = 0; j < inputs.length; j++) {
				inputs[j].disabled = !history_show;
			}
		}

		// Override footer.
		const footerRows = this.#form.querySelectorAll('.js-override-footer');
		for (let i = 0; i < footerRows.length; i++) {
			const element = footerRows[i];
			element.style.display = (this.tgt && this.tgt.style.display == 'none') ? 'none' : '';
		}

		// Option for including itemids encoding in cell for broadcasting
		const column_pattern_selection = this.#form.querySelector('button[id=column_patterns_aggregation]');
		const includeItemidsRows = this.#form.querySelectorAll('.js-include-itemids');
		
		for (let i = 0; i < includeItemidsRows.length; i++) {
			const element = includeItemidsRows[i];
			if ((column_pattern_selection && column_pattern_selection.innerText === 'not used') || (this.tgt && this.tgt.style.display == 'none')) {
				element.style.display = 'none';
				const inputs = element.querySelectorAll('input');
				for (let j = 0; j < inputs.length; j++) {
					inputs[j].disabled = true;
				}
			}
			else {
				element.style.display = '';
				const inputs = element.querySelectorAll('input');
				for (let j = 0; j < inputs.length; j++) {
					inputs[j].disabled = false;
				}
			}
		}
	}

	submit() {
		if (this.all_hosts_aggregated && this.all_hosts_aggregated.nextElementSibling) {
			const checkbox = this.all_hosts_aggregated.nextElementSibling.querySelector('[id="aggregate_all_hosts"]');
			const colAggMethod = document.getElementById('column_agg_method'); // Pode ser select normal ou z-select
			
			// Ajuste para pegar valor de input hidden se for select customizado
			const colAggVal = colAggMethod ? colAggMethod.value : '0';

			if (checkbox && checkbox.checked && colAggVal === '0') {
				if (!this.aggregation_error) {
					const title = 'Form configuration error';
					const messages = ['A \'Column patterns aggregation\' (under Advanced configuration) is required when using \'Aggregate all hosts\' from the main form'];
					const message_box = makeMessageBox('bad', messages, title)[0];
					this.#form.parentNode.insertBefore(message_box, this.#form);
					this.aggregation_error = true;
				}

				this.#overlay.unsetLoading();
				return;
			}
		}

		const curl = new Curl(this.#form.getAttribute('action'));
		const fields = getFormFields(this.#form);

		this.#overlay.setLoading();

		this.#post(curl.getUrl(), fields);
	}

	#post(url, fields) {
		fetch(url, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
			body: urlEncodeData(fields)
		})
			.then(response => response.json())
			.then(response => {
				if ('error' in response) {
					throw {error: response.error};
				}

				overlayDialogueDestroy(this.#overlay.dialogueid);

				this.#dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
			})
			.catch((exception) => {
				const children = this.#form.parentNode.children;
				for (let i = 0; i < children.length; i++) {
					const element = children[i];
					if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
						element.parentNode.removeChild(element);
					}
				}

				let title, messages;

				if (typeof exception === 'object' && 'error' in exception) {
					title = exception.error.title;
					messages = exception.error.messages;
				}
				else {
					messages = ["Unexpected server error."];
				}

				const message_box = makeMessageBox('bad', messages, title)[0];

				this.#form.parentNode.insertBefore(message_box, this.#form);
			})
			.finally(() => {
				this.#overlay.unsetLoading();
			});
	}
};
