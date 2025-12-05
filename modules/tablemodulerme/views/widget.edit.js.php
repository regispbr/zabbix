<?php declare(strict_types = 0);

use Modules\TableModuleRME\Includes\WidgetForm;

?>

window.widget_tablemodulerme_form = new class {

	/**
	 * @type {HTMLFormElement}
	 */
	#form;

	/**
	 * @type {string}
	 */
	#templateid;

	/**
	 * @type {HTMLElement}
	 */
	#list_columns;

	/**
	 * @type {Template}
	 */
	#list_column_tmpl;

	init({templateid}) {
		this.#form = document.getElementById('widget-dialogue-form');
		this.#list_columns = document.getElementById('list_columns');
		
		if (!this.#list_columns || !this.#form) {
			console.error('TableModuleRME: Form or Column list not found.');
			return;
		}

		this.#list_column_tmpl = new Template(this.#list_columns.querySelector('template').innerHTML);
		this.#templateid = templateid;

		this.#updateForm();

		this.#list_columns.addEventListener('click', (e) => this.#processColumnsAction(e));
		
		const ordering_fields = this.#form.querySelectorAll('[name="host_ordering_order_by"], [name="item_ordering_order_by"]');
		Array.from(ordering_fields).forEach(element => {
			element.addEventListener('change', () => this.#updateForm());
		});

		// Listeners para inputs de layout
		const layoutInputs = this.#form.querySelectorAll('input[name="layout"]');
		Array.from(layoutInputs).forEach(input => {
			input.addEventListener('change', () => this.#updateForm());
		});

		// Listeners jQuery para Multiselects
		const groupIdsEl = document.getElementById('groupids_');
		if (groupIdsEl) jQuery(groupIdsEl).on('change', () => this.#updateForm());

		const hostIdsEl = document.getElementById('hostids_');
		if (hostIdsEl) jQuery(hostIdsEl).on('change', () => this.#updateForm());
		
		this.#form.addEventListener('form_fields.changed', () => this.#updateForm());
	}

	#updateForm() {
		if (!this.#form) return;

		// Constantes locais para garantir compatibilidade
		const LAYOUT_THREE_COL = <?= WidgetForm::LAYOUT_THREE_COL ?>;
		const LAYOUT_COLUMN_PER = <?= WidgetForm::LAYOUT_COLUMN_PER ?>;
		const LAYOUT_VERTICAL = <?= WidgetForm::LAYOUT_VERTICAL ?>;
		
		const layout3Radio = this.#form.querySelector(`input[name="layout"][value="${LAYOUT_THREE_COL}"]`);
		const layoutColumnPerRadio = this.#form.querySelector(`input[name="layout"][value="${LAYOUT_COLUMN_PER}"]`);
		const layoutVerticalRadio = this.#form.querySelector(`input[name="layout"][value="${LAYOUT_VERTICAL}"]`);
		
		const column_per_pattern = layoutColumnPerRadio ? layoutColumnPerRadio.checked : false;
		const vertical_layout = layoutVerticalRadio ? layoutVerticalRadio.checked : false;

		// --- FUNÇÃO DE TOGGLE SEGURA ---
		const toggleFields = (selector, isEnabled) => {
			const fields = this.#form.querySelectorAll(selector);
			// Converte NodeList para Array para evitar erros de iteração
			Array.from(fields).forEach(field => {
				field.style.display = isEnabled ? '' : 'none';
				
				const inputs = field.querySelectorAll('input, select, textarea, button');
				Array.from(inputs).forEach(input => {
					// Verifica explicitamente se o input existe antes de alterar propriedade
					if (input) {
						input.disabled = !isEnabled;
						if (selector.includes('delimiter')) {
							input.setAttribute('data-no-trim', '1');
						}
					}
				});
			});
		};

		// Aplica lógica de campos
		toggleFields('.field_item_group_by', column_per_pattern);
		toggleFields('.field_grouping_delimiter', column_per_pattern);
		toggleFields('.field_aggregate_all_hosts', column_per_pattern);
		toggleFields('.field_show_grouping_only', column_per_pattern);

		// Lógica inversa para 'no_broadcast_hostid'
		const broadcastFields = this.#form.querySelectorAll('.field_no_broadcast_hostid');
		Array.from(broadcastFields).forEach(field => {
			field.style.display = vertical_layout ? 'none' : '';
			const inputs = field.querySelectorAll('input');
			Array.from(inputs).forEach(input => {
				if (input) input.disabled = vertical_layout;
			});
		});
		
		// Ordenação
		const ORDERBY_HOST = <?= WidgetForm::ORDERBY_HOST ?>;
		const ORDERBY_ITEM_VALUE = <?= WidgetForm::ORDERBY_ITEM_VALUE ?>;

		const itemOrderRadio = this.#form.querySelector('[name=item_ordering_order_by]:checked');
		const hostOrderRadio = this.#form.querySelector('[name=host_ordering_order_by]:checked');

		const orderByHost = itemOrderRadio ? (parseInt(itemOrderRadio.value) === ORDERBY_HOST) : false;
		const orderByItemValue = hostOrderRadio ? (parseInt(hostOrderRadio.value) === ORDERBY_ITEM_VALUE) : false;

		const itemSelect = document.getElementById('host_ordering_item_');
		if (itemSelect) {
			const wrapper = itemSelect.closest('.form-field');
			if (wrapper) {
				if (orderByItemValue) {
					wrapper.classList.remove('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(itemSelect).multiSelect('enable');
				} else {
					wrapper.classList.add('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(itemSelect).multiSelect('disable');
				}
			}
		}

		const hostSelect = document.getElementById('item_ordering_host_');
		if (hostSelect) {
			const wrapper = hostSelect.closest('.form-field');
			if (wrapper) {
				if (orderByHost) {
					wrapper.classList.remove('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(hostSelect).multiSelect('enable');
				} else {
					wrapper.classList.add('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(hostSelect).multiSelect('disable');
				}
			}
		}

		// Atualização de URL dos Multiselects
		if (hostSelect && itemSelect) {
			// Verifica se o multiselect já foi inicializado pelo Zabbix
			const $hostSelect = jQuery(hostSelect);
			const $itemSelect = jQuery(itemSelect);

			// Tenta pegar a opção url de forma segura
			let hostUrlData = $hostSelect.data('multiSelect');
			let itemUrlData = $itemSelect.data('multiSelect');

			if (hostUrlData && itemUrlData && hostUrlData.options && itemUrlData.options) {
				const urlHost = new Curl(hostUrlData.options.url);
				const urlItem = new Curl(itemUrlData.options.url);
				
				const formFields = getFormFields(this.#form);

				if (formFields.groupids !== undefined) {
					urlHost.setArgument('groupids', formFields.groupids);
					urlItem.setArgument('groupids', formFields.groupids);
				} else {
					urlHost.unsetArgument('groupids');
					urlItem.unsetArgument('groupids');
				}

				if (formFields.hostids !== undefined) {
					urlItem.setArgument('hostids', formFields.hostids);
				} else {
					urlItem.unsetArgument('hostids');
				}

				if (formFields.columns !== undefined && typeof formFields.columns === 'object') {
					const items = [];
					Object.values(formFields.columns).forEach((column) => {
						if (column.items && typeof column.items === 'object') {
							items.push(...Object.values(column.items));
						}
					});
					urlItem.setArgument('items', items);
				} else {
					urlItem.unsetArgument('items');
				}

				$hostSelect.multiSelect('modify', {url: urlHost.getUrl()});
				$itemSelect.multiSelect('modify', {url: urlItem.getUrl()});
			}
		}
	}

	#triggerUpdate() {
		this.#form.dispatchEvent(new CustomEvent('form_fields.changed', {detail: {}}));
	}

	#processColumnsAction(e) {
		const target = e.target;
		const button = target.closest('button');
		if (!button) return;

		const formFields = getFormFields(this.#form);
		const action = button.getAttribute('name');

		let columnPopup;

		switch (action) {
			case 'add':
				columnPopup = PopUp(
					'widget.tablemodulerme.column.edit',
					{
						templateid: this.#templateid,
						groupids: formFields.groupids,
						hostids: formFields.hostids
					},
					{
						dialogueid: 'tablemodulerme-column-edit-overlay',
						dialogue_class: 'modal-popup-generic'
					}
				).$dialogue[0];

				columnPopup.addEventListener('dialogue.submit', (e) => {
					const tbody = this.#list_columns.querySelector('tbody');
					const lastRow = tbody.querySelector('tr:last-child');
					
					let index = 0;
					if (lastRow && lastRow.dataset.index !== undefined) {
						index = parseInt(lastRow.dataset.index) + 1;
					}

					const newRow = this.#makeColumnRow(e.detail, index);
					
					if (lastRow) {
						lastRow.insertAdjacentElement('afterend', newRow);
					} else {
						tbody.appendChild(newRow);
					}
					
					this.#triggerUpdate();
				});
				break;

			case 'edit':
				const row = button.closest('tr');
				const columnIndex = row.dataset.index;
				const columnData = formFields.columns ? formFields.columns[columnIndex] : {};

				columnPopup = PopUp(
					'widget.tablemodulerme.column.edit',
					{
						...columnData,
						edit: 1,
						templateid: this.#templateid,
						groupids: formFields.groupids,
						hostids: formFields.hostids
					}, {
						dialogueid: 'tablemodulerme-column-edit-overlay',
						dialogue_class: 'modal-popup-generic'
					}
				).$dialogue[0];

				columnPopup.addEventListener('dialogue.submit', (e) => {
					const currentRow = this.#list_columns.querySelector(`tbody > tr[data-index="${columnIndex}"]`);
					currentRow.replaceWith(this.#makeColumnRow(e.detail, columnIndex));
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
		const columnData = row.querySelector('.js-column-data');
		
		const addVar = (name, value) => {
			const input = document.createElement('input');
			input.setAttribute('type', 'hidden');
			input.setAttribute('name', name);
			input.setAttribute('value', value);
			columnData.append(input);
		};

		for (const [key, value] of Object.entries(data)) {
			if (key === 'edit') continue;

			if (key === 'thresholds' && typeof value === 'object') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][thresholds][${k}][color]`, v.color);
					addVar(`columns[${index}][thresholds][${k}][threshold]`, v.threshold);
				}
				continue;
			}

			if (key === 'highlights' && typeof value === 'object') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][highlights][${k}][color]`, v.color);
					addVar(`columns[${index}][highlights][${k}][pattern]`, v.pattern);
				}
				continue;
			}

			if (key === 'items' && typeof value === 'object') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][items][${k}]`, v);
				}
				continue;
			}

			if (key === 'time_period' && typeof value === 'object') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][time_period][${k}]`, v);
				}
				continue;
			}

			if (key === 'sparkline' && typeof value === 'object') {
				for (const [k, v] of Object.entries(value)) {
					if (k === 'time_period' && typeof v === 'object') {
						for (const [sk, sv] of Object.entries(v)) {
							addVar(`columns[${index}][sparkline][time_period][${sk}]`, sv);
						}
					} else {
						addVar(`columns[${index}][sparkline][${k}]`, v);
					}
				}
				continue;
			}

			if (key === 'item_tags' && typeof value === 'object') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][item_tags][${k}][operator]`, v.operator);
					addVar(`columns[${index}][item_tags][${k}][tag]`, v.tag);
					addVar(`columns[${index}][item_tags][${k}][value]`, v.value);
				}
				continue;
			}

			addVar(`columns[${index}][${key}]`, value);
		}

		return row;
	}
};
