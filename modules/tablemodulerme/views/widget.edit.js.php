<?php declare(strict_types = 0);

use Modules\TableModuleRME\Includes\WidgetForm;

?>

window.widget_tablemodulerme_form = new class {

	/**
	 * Widget form.
	 *
	 * @type {HTMLFormElement}
	 */
	#form;

	/**
	 * Template id.
	 *
	 * @type {string}
	 */
	#templateid;

	/**
	 * Column list container.
	 *
	 * @type {HTMLElement}
	 */
	#list_columns;

	/**
	 * Column list entry template.
	 *
	 * @type {Template}
	 */
	#list_column_tmpl;

	init({templateid}) {
		// No Zabbix 7, pegamos o form pelo nome ou ID padrão da modal
		this.#form = document.getElementById('widget-dialogue-form');
		this.#list_columns = document.getElementById('list_columns');
		this.#list_column_tmpl = new Template(this.#list_columns.querySelector('template').innerHTML);
		this.#templateid = templateid;

		// Initialize form elements accessibility.
		this.#updateForm();

		this.#list_columns.addEventListener('click', (e) => this.#processColumnsAction(e));
		
		const ordering_fields = this.#form.querySelectorAll('[name="host_ordering_order_by"], [name="item_ordering_order_by"]');
		if (ordering_fields.length > 0) {
			ordering_fields.forEach(element => {
				element.addEventListener('change', () => this.#updateForm());
			});
		}

		// Use jQuery for older components if necessary, but try native where possible
		jQuery(document.getElementById('groupids_')).on('change', () => this.#updateForm());
		jQuery(document.getElementById('hostids_')).on('change', () => this.#updateForm());
		jQuery('[id^=layout_]').on('change', () => this.#updateForm());
		
		// O evento customizado form_fields.changed pode não ser disparado automaticamente no 7.0 da mesma forma
		// Mas mantemos o listener caso seu módulo dispare manualmente
		this.#form.addEventListener('form_fields.changed', () => this.#updateForm());
	}

	/**
	 * Updates widget column configuration form field visibility, enable/disable state and available options.
	 */
	#updateForm() {
		const layout_3 = this.#form.querySelector('#layout_3');
		const layout_1 = this.#form.querySelector('#layout_1');
		
		const column_per_pattern = layout_3 ? layout_3.checked : false;
		const vertical_layout = layout_1 ? layout_1.checked : false;

		// Helper function to safely toggle fields
		const toggleFields = (selector, isEnabled) => {
			const fields = this.#form.querySelectorAll(selector);
			if (fields.length > 0) {
				fields.forEach(field => {
					// Toggle visibility
					field.style.display = isEnabled ? '' : 'none';
					
					// Toggle inputs
					const inputs = field.querySelectorAll('input, select, textarea, button');
					inputs.forEach(input => {
						input.disabled = !isEnabled;
						if (selector.includes('delimiter')) {
							input.setAttribute('data-no-trim', '1');
						}
					});
				});
			}
		};

		// Apply toggle logic safely
		toggleFields('.field_item_group_by', column_per_pattern);
		toggleFields('.field_grouping_delimiter', column_per_pattern);
		toggleFields('.field_aggregate_all_hosts', column_per_pattern);
		toggleFields('.field_show_grouping_only', column_per_pattern);

		// Special case for 'no_broadcast_hostid' (inverse logic)
		const broadcastFields = this.#form.querySelectorAll('.field_no_broadcast_hostid');
		if (broadcastFields.length > 0) {
			broadcastFields.forEach(field => {
				field.style.display = vertical_layout ? 'none' : '';
				const inputs = field.querySelectorAll('input');
				inputs.forEach(input => input.disabled = vertical_layout);
			});
		}
		
		// Ordering Logic
		const item_ordering_radio = this.#form.querySelector('[name=item_ordering_order_by]:checked');
		const host_ordering_radio = this.#form.querySelector('[name=host_ordering_order_by]:checked');

		// Defina as constantes manualmente se o PHP não as injetou no JS global
		const ORDERBY_HOST = 1; 
		const ORDERBY_ITEM_VALUE = 3;

		const order_by_host = item_ordering_radio ? (parseInt(item_ordering_radio.value) === ORDERBY_HOST) : false;
		const order_by_item_value = host_ordering_radio ? (parseInt(host_ordering_radio.value) === ORDERBY_ITEM_VALUE) : false;

		const item_select = document.getElementById('host_ordering_item_');
		if (item_select) {
			const fieldContainer = item_select.closest('.form-field');
			if (fieldContainer) {
				if (order_by_item_value) {
					fieldContainer.classList.remove('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(item_select).multiSelect('enable');
				} else {
					fieldContainer.classList.add('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(item_select).multiSelect('disable');
				}
			}
		}

		const host_select = document.getElementById('item_ordering_host_');
		if (host_select) {
			const fieldContainer = host_select.closest('.form-field');
			if (fieldContainer) {
				if (order_by_host) {
					fieldContainer.classList.remove('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(host_select).multiSelect('enable');
				} else {
					fieldContainer.classList.add('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(host_select).multiSelect('disable');
				}
			}
		}

		// Limit multi select suggestions
		if (host_select && item_select) {
			const url_host = new Curl(jQuery(host_select).multiSelect('getOption', 'url'));
			const url_item = new Curl(jQuery(item_select).multiSelect('getOption', 'url'));
			
			const form_fields = getFormFields(this.#form);

			if (form_fields.groupids !== undefined) {
				url_host.setArgument('groupids', form_fields.groupids);
				url_item.setArgument('groupids', form_fields.groupids);
			} else {
				url_host.unsetArgument('groupids');
				url_item.unsetArgument('groupids');
			}

			if (form_fields.hostids !== undefined) {
				url_item.setArgument('hostids', form_fields.hostids);
			} else {
				url_item.unsetArgument('hostids');
			}

			if (form_fields.columns !== undefined) {
				const items = [];
				// Verifica se columns é objeto ou array antes de iterar
				if (typeof form_fields.columns === 'object' && form_fields.columns !== null) {
					Object.values(form_fields.columns).forEach((column) => {
						if (column.items && typeof column.items === 'object') {
							items.push(...Object.values(column.items));
						}
					});
				}
				url_item.setArgument('items', items);
			} else {
				url_item.unsetArgument('items');
			}

			jQuery(host_select).multiSelect('modify', {url: url_host.getUrl()});
			jQuery(item_select).multiSelect('modify', {url: url_item.getUrl()});
		}
	}

	#triggerUpdate() {
		this.#form.dispatchEvent(new CustomEvent('form_fields.changed', {detail: {}}));
	}

	#processColumnsAction(e) {
		const target = e.target;
		// Verifica se o clique foi em um botão dentro da tabela
		const button = target.closest('button');
		if (!button) return;

		const form_fields = getFormFields(this.#form);
		const action = button.getAttribute('name');

		let column_popup;

		switch (action) {
			case 'add':
				column_popup = PopUp(
					'widget.tablemodulerme.column.edit',
					{
						templateid: this.#templateid,
						groupids: form_fields.groupids,
						hostids: form_fields.hostids
					},
					{
						dialogueid: 'tablemodulerme-column-edit-overlay',
						dialogue_class: 'modal-popup-generic'
					}
				).$dialogue[0];

				column_popup.addEventListener('dialogue.submit', (e) => {
					const last_row = this.#list_columns.querySelector(`tbody > tr:last-child`);
					// Correção para index: se não houver linhas, começa do 0. Se houver, pega o ultimo + 1
					let index = 0;
					if (last_row) {
						// Verifica se é uma linha de dados ou placeholder
						if (last_row.dataset.index !== undefined) {
							index = parseInt(last_row.dataset.index) + 1;
						}
					}

					// Se a tabela estiver vazia (sem linhas TR), append no tbody
					const tbody = this.#list_columns.querySelector('tbody');
					if (!last_row) {
						tbody.appendChild(this.#makeColumnRow(e.detail, index));
					} else {
						last_row.insertAdjacentElement('afterend', this.#makeColumnRow(e.detail, index));
					}
					
					this.#triggerUpdate();
				});

				break;

			case 'edit':
				const row = button.closest('tr');
				const column_index = row.dataset.index;
				
				// Garante que columns[column_index] existe
				const column_data = form_fields.columns ? form_fields.columns[column_index] : {};

				column_popup = PopUp(
					'widget.tablemodulerme.column.edit',
					{
						...column_data,
						edit: 1,
						templateid: this.#templateid,
						groupids: form_fields.groupids,
						hostids: form_fields.hostids
					}, {
						dialogueid: 'tablemodulerme-column-edit-overlay',
						dialogue_class: 'modal-popup-generic'
					}
					).$dialogue[0];

				column_popup.addEventListener('dialogue.submit', (e) => {
					const row = this.#list_columns.querySelector(`tbody > tr[data-index="${column_index}"]`);
					row.replaceWith(this.#makeColumnRow(e.detail, column_index));
					this.#triggerUpdate();
				});

				break;

			case 'remove':
				button.closest('tr').remove();
				this.#triggerUpdate();
				break;
		}
	}

	#makeColumnRow(data, index) {
		const row = this.#list_column_tmpl.evaluateToElement({
			...data,
			rowNum: index,
			items: data.items ? Object.values(data.items).join(', ') : ''
		});

		row.dataset.index = index;
		const column_data = row.querySelector('.js-column-data');
		
		for (const [data_key, data_value] of Object.entries(data)) {
			switch (data_key) {
				case 'edit':
					continue;

				case 'thresholds':
					for (const [key, value] of Object.entries(data.thresholds)) {
						column_data.append(this.#makeVar(`columns[${index}][thresholds][${key}][color]`, value.color));
						column_data.append(this.#makeVar(
							`columns[${index}][thresholds][${key}][threshold]`,
							value.threshold
						));
					}
					break;

				case 'highlights':
					for (const [key, value] of Object.entries(data.highlights)) {
						column_data.append(this.#makeVar(`columns[${index}][highlights][${key}][color]`, value.color));
						column_data.append(this.#makeVar(
							`columns[${index}][highlights][${key}][pattern]`,
							value.pattern
						));
					}
					break;

				case 'items':
					for (const [key, value] of Object.entries(data.items)) {
						column_data.append(this.#makeVar(`columns[${index}][items][${key}]`, value));
					}
					break;

				case 'time_period':
					for (const [key, value] of Object.entries(data.time_period)) {
						column_data.append(this.#makeVar(`columns[${index}][time_period][${key}]`, value));
					}
					break;

				case 'sparkline':
					for (const [key, value] of Object.entries(data_value)) {
						if (key === 'time_period') {
							for (const [k, v] of Object.entries(value)) {
								column_data.append(this.#makeVar(`columns[${index}][sparkline][time_period][${k}]`, v));
							}
						}
						else {
							column_data.append(this.#makeVar(`columns[${index}][sparkline][${key}]`, value));
						}
					}
					break;

				case 'item_tags':
					for (const [key, {operator, tag, value}] of Object.entries(data.item_tags)) {
						column_data.append(this.#makeVar(`columns[${index}][item_tags][${key}][operator]`, operator));
						column_data.append(this.#makeVar(`columns[${index}][item_tags][${key}][tag]`, tag));
						column_data.append(this.#makeVar(`columns[${index}][item_tags][${key}][value]`, value));
					}
					break;

				default:
					column_data.append(this.#makeVar(`columns[${index}][${data_key}]`, data_value));
					break;
			}
		}

		return row;
	}

	#makeVar(name, value) {
		const input = document.createElement('input');
		input.setAttribute('type', 'hidden');
		input.setAttribute('name', name);
		input.setAttribute('value', value);
		return input;
	}
};
