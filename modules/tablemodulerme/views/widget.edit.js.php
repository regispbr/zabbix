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
		
		// Segurança contra null caso o campo não exista em algum contexto
		const column_per_pattern = layout_3 ? layout_3.checked : false;
		const vertical_layout = layout_1 ? layout_1.checked : false;

		for (const grouping_field of this.#form.querySelectorAll('.field_item_group_by')) {
			grouping_field.style.display = !column_per_pattern ? 'none' : '';
			for (const input of grouping_field.querySelectorAll('input')) {
				input.disabled = !column_per_pattern;
			}
		}

		for (const grouping_delimiter of this.#form.querySelectorAll('.field_grouping_delimiter')) {
			grouping_delimiter.style.display = !column_per_pattern ? 'none' : '';
			for (const input of grouping_delimiter.querySelectorAll('input')) {
				input.disabled = !column_per_pattern;
				input.setAttribute('data-no-trim', '1');
			}
		}

		for (const aggregate_all_field of this.#form.querySelectorAll('.field_aggregate_all_hosts')) {
			aggregate_all_field.style.display = !column_per_pattern ? 'none' : '';
			for (const input of aggregate_all_field.querySelectorAll('input')) {
				input.disabled = !column_per_pattern;
			}
		}
		
		for (const show_grouping_only_field of this.#form.querySelectorAll('.field_show_grouping_only')) {
			show_grouping_only_field.style.display = !column_per_pattern ? 'none' : '';
			for (const input of show_grouping_only_field.querySelectorAll('input')) {
				input.disabled = !column_per_pattern;
			}
		}

		for (const bc_hostid_field of this.#form.querySelectorAll('.field_no_broadcast_hostid')) {
			bc_hostid_field.style.display = vertical_layout ? 'none' : '';
			for (const input of bc_hostid_field.querySelectorAll('input')) {
				input.disabled = vertical_layout;
			}
		}
		
		const item_ordering_radio = this.#form.querySelector('[name=item_ordering_order_by]:checked');
		const host_ordering_radio = this.#form.querySelector('[name=host_ordering_order_by]:checked');

		const order_by_host = item_ordering_radio ? (item_ordering_radio.value == <?= WidgetForm::ORDERBY_HOST ?>) : false;
		const order_by_item_value = host_ordering_radio ? (host_ordering_radio.value == <?= WidgetForm::ORDERBY_ITEM_VALUE ?>) : false;

		const item_select = document.getElementById('host_ordering_item_');
		if (item_select) {
			if (order_by_item_value) {
				item_select.closest('.form-field').classList.remove('<?= ZBX_STYLE_DISPLAY_NONE ?>');
				jQuery(item_select).multiSelect('enable');
			}
			else {
				item_select.closest('.form-field').classList.add('<?= ZBX_STYLE_DISPLAY_NONE ?>');
				jQuery(item_select).multiSelect('disable');
			}
		}

		const host_select = document.getElementById('item_ordering_host_');
		if (host_select) {
			if (order_by_host) {
				host_select.closest('.form-field').classList.remove('<?= ZBX_STYLE_DISPLAY_NONE ?>');
				jQuery(host_select).multiSelect('enable');
			}
			else {
				host_select.closest('.form-field').classList.add('<?= ZBX_STYLE_DISPLAY_NONE ?>');
				jQuery(host_select).multiSelect('disable');
			}
		}

		// Limit multi select suggestions to selected hosts and groups.
		if (host_select && item_select) {
			const url_host = new Curl(jQuery(host_select).multiSelect('getOption', 'url'));
			const url_item = new Curl(jQuery(item_select).multiSelect('getOption', 'url'));
			
			// getFormFields é global no Zabbix
			const form_fields = getFormFields(this.#form);

			if (form_fields.groupids !== undefined) {
				url_host.setArgument('groupids', form_fields.groupids);
				url_item.setArgument('groupids', form_fields.groupids);
			}
			else {
				url_host.unsetArgument('groupids');
				url_item.unsetArgument('groupids');
			}

			if (form_fields.hostids !== undefined) {
				url_item.setArgument('hostids', form_fields.hostids);
			}
			else {
				url_item.unsetArgument('hostids');
			}

			// Nota: Se 'columns' for complexo, pode precisar de ajuste, mas mantive a lógica original
			if (form_fields.columns !== undefined) {
				const items = [];
				Object.values(form_fields.columns).forEach((column) => {
					if (column.items) items.push(...Object.values(column.items));
				});
				url_item.setArgument('items', items);
			}
			else {
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
