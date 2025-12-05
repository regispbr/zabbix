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
		// Busca o formulário de forma segura
		this.#form = document.getElementById('widget-dialogue-form');
		this.#list_columns = document.getElementById('list_columns');
		
		if (!this.#list_columns) {
			console.error('TableModuleRME: List columns container not found.');
			return;
		}

		this.#list_column_tmpl = new Template(this.#list_columns.querySelector('template').innerHTML);
		this.#templateid = templateid;

		// Inicializa o estado do formulário
		this.#updateForm();

		// Listeners de eventos
		this.#list_columns.addEventListener('click', (e) => this.#processColumnsAction(e));
		
		// Listener para campos de ordenação
		const ordering_fields = this.#form.querySelectorAll('[name="host_ordering_order_by"], [name="item_ordering_order_by"]');
		if (ordering_fields.length > 0) {
			for (let i = 0; i < ordering_fields.length; i++) {
				ordering_fields[i].addEventListener('change', () => this.#updateForm());
			}
		}

		// Listeners para Multiselects e Radio buttons
		// Usamos jQuery para compatibilidade com componentes legados do Zabbix se necessário, 
		// mas preferimos vanilla JS para eventos de mudança simples
		const groupIdsEl = document.getElementById('groupids_');
		if (groupIdsEl) jQuery(groupIdsEl).on('change', () => this.#updateForm());

		const hostIdsEl = document.getElementById('hostids_');
		if (hostIdsEl) jQuery(hostIdsEl).on('change', () => this.#updateForm());

		// Monitorar mudanças de layout (Inputs que começam com layout_)
		const layoutInputs = this.#form.querySelectorAll('input[name="layout"]');
		for (let i = 0; i < layoutInputs.length; i++) {
			layoutInputs[i].addEventListener('change', () => this.#updateForm());
		}
		
		this.#form.addEventListener('form_fields.changed', () => this.#updateForm());
	}

	/**
	 * Updates widget column configuration form field visibility, enable/disable state and available options.
	 */
	#updateForm() {
		if (!this.#form) return;

		// Verifica Layouts
		// Layout 3 = Column per pattern
		// Layout 1 = Vertical
		const layout3Radio = this.#form.querySelector('input[name="layout"][value="<?= WidgetForm::LAYOUT_THREE_COL ?>"]'); // ou valor especifico do column per pattern
		// Como os valores são constantes, vamos pegar pelo ID se possível ou pelo value
		// No seu form original: value 51 = LAYOUT_COLUMN_PER
		const layoutColumnPerPattern = this.#form.querySelector('input[name="layout"][value="<?= WidgetForm::LAYOUT_COLUMN_PER ?>"]');
		const layoutVertical = this.#form.querySelector('input[name="layout"][value="<?= WidgetForm::LAYOUT_VERTICAL ?>"]');
		
		const isColumnPerPattern = layoutColumnPerPattern ? layoutColumnPerPattern.checked : false;
		const isVertical = layoutVertical ? layoutVertical.checked : false;

		// Função auxiliar SEGURA para alternar campos
		const toggleFields = (selector, isEnabled) => {
			const fields = this.#form.querySelectorAll(selector);
			if (fields.length > 0) {
				for (let i = 0; i < fields.length; i++) {
					const field = fields[i];
					field.style.display = isEnabled ? '' : 'none';
					
					const inputs = field.querySelectorAll('input, select, textarea, button');
					if (inputs.length > 0) {
						for (let j = 0; j < inputs.length; j++) {
							const input = inputs[j];
							// VERIFICAÇÃO CRÍTICA: Só altera se o input existir
							if (input) {
								input.disabled = !isEnabled;
								// Tratamento especial para delimiter
								if (selector.includes('delimiter')) {
									input.setAttribute('data-no-trim', '1');
								}
							}
						}
					}
				}
			}
		};

		// Aplica a lógica de toggle
		toggleFields('.field_item_group_by', isColumnPerPattern);
		toggleFields('.field_grouping_delimiter', isColumnPerPattern);
		toggleFields('.field_aggregate_all_hosts', isColumnPerPattern);
		toggleFields('.field_show_grouping_only', isColumnPerPattern);

		// Lógica inversa para broadcast hostid (esconder se for Vertical)
		const broadcastFields = this.#form.querySelectorAll('.field_no_broadcast_hostid');
		if (broadcastFields.length > 0) {
			for (let i = 0; i < broadcastFields.length; i++) {
				const field = broadcastFields[i];
				field.style.display = isVertical ? 'none' : '';
				const inputs = field.querySelectorAll('input');
				if (inputs.length > 0) {
					for (let j = 0; j < inputs.length; j++) {
						if (inputs[j]) inputs[j].disabled = isVertical;
					}
				}
			}
		}
		
		// Lógica de Ordenação
		const itemOrderRadio = this.#form.querySelector('[name=item_ordering_order_by]:checked');
		const hostOrderRadio = this.#form.querySelector('[name=host_ordering_order_by]:checked');

		const orderByHost = itemOrderRadio ? (parseInt(itemOrderRadio.value) === <?= WidgetForm::ORDERBY_HOST ?>) : false;
		const orderByItemValue = hostOrderRadio ? (parseInt(hostOrderRadio.value) === <?= WidgetForm::ORDERBY_ITEM_VALUE ?>) : false;

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

		// Atualiza URLs dos Multiselects (Filtros dinâmicos)
		if (hostSelect && itemSelect) {
			// Verifica se os métodos getOption do multiselect estão disponíveis
			const hostUrlOption = jQuery(hostSelect).multiSelect('getOption', 'url');
			const itemUrlOption = jQuery(itemSelect).multiSelect('getOption', 'url');

			if (hostUrlOption && itemUrlOption) {
				const urlHost = new Curl(hostUrlOption);
				const urlItem = new Curl(itemUrlOption);
				
				const formFields = getFormFields(this.#form);

				// Groups
				if (formFields.groupids !== undefined) {
					urlHost.setArgument('groupids', formFields.groupids);
					urlItem.setArgument('groupids', formFields.groupids);
				} else {
					urlHost.unsetArgument('groupids');
					urlItem.unsetArgument('groupids');
				}

				// Hosts
				if (formFields.hostids !== undefined) {
					urlItem.setArgument('hostids', formFields.hostids);
				} else {
					urlItem.unsetArgument('hostids');
				}

				// Items (Columns)
				if (formFields.columns !== undefined) {
					const items = [];
					if (typeof formFields.columns === 'object' && formFields.columns !== null) {
						Object.values(formFields.columns).forEach((column) => {
							if (column.items && typeof column.items === 'object') {
								items.push(...Object.values(column.items));
							}
						});
					}
					urlItem.setArgument('items', items);
				} else {
					urlItem.unsetArgument('items');
				}

				jQuery(hostSelect).multiSelect('modify', {url: urlHost.getUrl()});
				jQuery(itemSelect).multiSelect('modify', {url: urlItem.getUrl()});
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
		
		// Função helper para criar inputs hidden
		const addVar = (name, value) => {
			const input = document.createElement('input');
			input.setAttribute('type', 'hidden');
			input.setAttribute('name', name);
			input.setAttribute('value', value);
			columnData.append(input);
		};

		for (const [key, value] of Object.entries(data)) {
			if (key === 'edit') continue;

			if (key === 'thresholds') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][thresholds][${k}][color]`, v.color);
					addVar(`columns[${index}][thresholds][${k}][threshold]`, v.threshold);
				}
				continue;
			}

			if (key === 'highlights') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][highlights][${k}][color]`, v.color);
					addVar(`columns[${index}][highlights][${k}][pattern]`, v.pattern);
				}
				continue;
			}

			if (key === 'items') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][items][${k}]`, v);
				}
				continue;
			}

			if (key === 'time_period') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][time_period][${k}]`, v);
				}
				continue;
			}

			if (key === 'sparkline') {
				for (const [k, v] of Object.entries(value)) {
					if (k === 'time_period') {
						for (const [sk, sv] of Object.entries(v)) {
							addVar(`columns[${index}][sparkline][time_period][${sk}]`, sv);
						}
					} else {
						addVar(`columns[${index}][sparkline][${k}]`, v);
					}
				}
				continue;
			}

			if (key === 'item_tags') {
				for (const [k, v] of Object.entries(value)) {
					addVar(`columns[${index}][item_tags][${k}][operator]`, v.operator);
					addVar(`columns[${index}][item_tags][${k}][tag]`, v.tag);
					addVar(`columns[${index}][item_tags][${k}][value]`, v.value);
				}
				continue;
			}

			// Default para valores simples
			addVar(`columns[${index}][${key}]`, value);
		}

		return row;
	}
};
