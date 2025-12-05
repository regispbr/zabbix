<?php declare(strict_types = 0);

use Modules\TableModuleRME\Includes\WidgetForm;

?>

window.widget_tablemodulerme_form = new class {

	init({templateid}) {
		this._form = document.getElementById('widget-dialogue-form');
		this._list_columns = document.getElementById('list_columns');
		this._templateid = templateid;

		if (!this._form || !this._list_columns) {
			return; // Aborta se não encontrar o form para evitar erros
		}

		this._list_column_tmpl = new Template(this._list_columns.querySelector('template').innerHTML);

		this._updateForm();

		// Event Listeners
		this._list_columns.addEventListener('click', (e) => this._processColumnsAction(e));
		
		// Ordenação
		const orderingFields = this._form.querySelectorAll('[name="host_ordering_order_by"], [name="item_ordering_order_by"]');
		orderingFields.forEach(el => el.addEventListener('change', () => this._updateForm()));

		// Layouts (Radio buttons)
		const layoutInputs = this._form.querySelectorAll('input[name="layout"]');
		layoutInputs.forEach(input => input.addEventListener('change', () => this._updateForm()));

		// Multiselects (Compatibilidade jQuery)
		jQuery('#groupids_').on('change', () => this._updateForm());
		jQuery('#hostids_').on('change', () => this._updateForm());
		
		// Listener genérico
		this._form.addEventListener('form_fields.changed', () => this._updateForm());
	}

	_updateForm() {
		// Layout Constants
		const LAYOUT_VERTICAL = <?= WidgetForm::LAYOUT_VERTICAL ?>;
		const LAYOUT_COLUMN_PER = <?= WidgetForm::LAYOUT_COLUMN_PER ?>;
		
		// Detectar Layout Selecionado
		const selectedLayoutEl = this._form.querySelector('input[name="layout"]:checked');
		const selectedLayout = selectedLayoutEl ? parseInt(selectedLayoutEl.value) : <?= WidgetForm::LAYOUT_HORIZONTAL ?>;
		
		const isColumnPerPattern = (selectedLayout === LAYOUT_COLUMN_PER);
		const isVertical = (selectedLayout === LAYOUT_VERTICAL);

		// --- FUNÇÃO SEGURA DE VISIBILIDADE ---
		// Apenas esconde/mostra os containers. Não tenta desativar inputs internos de estruturas complexas.
		const setVisibility = (selector, isVisible) => {
			const fields = this._form.querySelectorAll(selector);
			fields.forEach(field => {
				field.style.display = isVisible ? '' : 'none';
				// Habilita/Desabilita apenas inputs simples e diretos para evitar erro em tabelas
				const simpleInputs = field.querySelectorAll('input:not([type="hidden"]), select, textarea');
				simpleInputs.forEach(input => {
					// Verificação de segurança antes de alterar propriedade
					if (input && input.style) { 
						input.disabled = !isVisible;
					}
				});
			});
		};

		// Aplica visibilidade baseada no layout
		setVisibility('.field_item_group_by', isColumnPerPattern);
		setVisibility('.field_grouping_delimiter', isColumnPerPattern);
		setVisibility('.field_aggregate_all_hosts', isColumnPerPattern);
		setVisibility('.field_show_grouping_only', isColumnPerPattern);

		// Lógica inversa para Broadcast HostID
		setVisibility('.field_no_broadcast_hostid', !isVertical);
		
		// --- Lógica de Ordenação (Multiselects) ---
		const ORDERBY_HOST = <?= WidgetForm::ORDERBY_HOST ?>;
		const ORDERBY_ITEM_VALUE = <?= WidgetForm::ORDERBY_ITEM_VALUE ?>;

		const itemOrderEl = this._form.querySelector('[name=item_ordering_order_by]:checked');
		const hostOrderEl = this._form.querySelector('[name=host_ordering_order_by]:checked');

		const orderByHost = itemOrderEl ? (parseInt(itemOrderEl.value) === ORDERBY_HOST) : false;
		const orderByItemValue = hostOrderEl ? (parseInt(hostOrderEl.value) === ORDERBY_ITEM_VALUE) : false;

		this._toggleMultiselect('host_ordering_item_', orderByItemValue);
		this._toggleMultiselect('item_ordering_host_', orderByHost);

		this._updateMultiselectUrls();
	}

	_toggleMultiselect(id, enable) {
		const el = document.getElementById(id);
		if (el) {
			const wrapper = el.closest('.form-field');
			if (wrapper) {
				if (enable) {
					wrapper.classList.remove('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(el).multiSelect('enable');
				} else {
					wrapper.classList.add('<?= ZBX_STYLE_DISPLAY_NONE ?>');
					jQuery(el).multiSelect('disable');
				}
			}
		}
	}

	_updateMultiselectUrls() {
		const hostSelect = document.getElementById('item_ordering_host_');
		const itemSelect = document.getElementById('host_ordering_item_');

		if (hostSelect && itemSelect) {
			const $hostSelect = jQuery(hostSelect);
			const $itemSelect = jQuery(itemSelect);
			
			// Verificação defensiva se o plugin multiselect existe
			if (!$hostSelect.data('multiSelect') || !$itemSelect.data('multiSelect')) return;

			const urlHost = new Curl($hostSelect.multiSelect('getOption', 'url'));
			const urlItem = new Curl($itemSelect.multiSelect('getOption', 'url'));
			
			const formFields = getFormFields(this._form);

			// Atualiza Group IDs
			if (formFields.groupids) {
				urlHost.setArgument('groupids', formFields.groupids);
				urlItem.setArgument('groupids', formFields.groupids);
			} else {
				urlHost.unsetArgument('groupids');
				urlItem.unsetArgument('groupids');
			}

			// Atualiza Host IDs
			if (formFields.hostids) {
				urlItem.setArgument('hostids', formFields.hostids);
			} else {
				urlItem.unsetArgument('hostids');
			}

			// Atualiza Items
			if (formFields.columns) {
				const items = [];
				// Garante que é iterável
				const columns = Array.isArray(formFields.columns) ? formFields.columns : Object.values(formFields.columns);
				
				columns.forEach(col => {
					if (col.items) {
						const colItems = Array.isArray(col.items) ? col.items : Object.values(col.items);
						items.push(...colItems);
					}
				});
				
				if (items.length > 0) {
					urlItem.setArgument('items', items);
				} else {
					urlItem.unsetArgument('items');
				}
			}

			$hostSelect.multiSelect('modify', {url: urlHost.getUrl()});
			$itemSelect.multiSelect('modify', {url: urlItem.getUrl()});
		}
	}

	_processColumnsAction(e) {
		const button = e.target.closest('button');
		if (!button) return;

		const action = button.getAttribute('name');
		const formFields = getFormFields(this._form);

		switch (action) {
			case 'add':
				this._openColumnPopup({}, (newRow) => {
					this._list_columns.querySelector('tbody').appendChild(newRow);
				});
				break;

			case 'edit':
				const row = button.closest('tr');
				const index = row.dataset.index;
				const data = formFields.columns && formFields.columns[index] ? formFields.columns[index] : {};
				
				this._openColumnPopup(data, (newRow) => {
					row.replaceWith(newRow);
				}, true);
				break;

			case 'remove':
				button.closest('tr').remove();
				this._form.dispatchEvent(new CustomEvent('form_fields.changed'));
				break;
		}
	}

	_openColumnPopup(data, callback, isEdit = false) {
		const formFields = getFormFields(this._form);
		const popupParams = {
			templateid: this._templateid,
			groupids: formFields.groupids,
			hostids: formFields.hostids,
			...data
		};
		if (isEdit) popupParams.edit = 1;

		const overlay = PopUp('widget.tablemodulerme.column.edit', popupParams, {
			dialogueid: 'tablemodulerme-column-edit-overlay',
			dialogue_class: 'modal-popup-generic'
		});

		overlay.$dialogue[0].addEventListener('dialogue.submit', (e) => {
			const tbody = this._list_columns.querySelector('tbody');
			// Calcula próximo índice com segurança
			let nextIndex = 0;
			const rows = tbody.querySelectorAll('tr[data-index]');
			if (rows.length > 0) {
				const lastIndex = parseInt(rows[rows.length - 1].dataset.index);
				nextIndex = isNaN(lastIndex) ? 0 : lastIndex + 1;
			}
			
			// Se for edição, mantém o índice original, senão usa o novo
			const finalIndex = isEdit ? (data.index ?? nextIndex) : nextIndex;
			
			callback(this._makeColumnRow(e.detail, finalIndex));
			this._form.dispatchEvent(new CustomEvent('form_fields.changed'));
		});
	}

	_makeColumnRow(data, index) {
		const itemsText = data.items ? (Array.isArray(data.items) ? data.items.join(', ') : Object.values(data.items).join(', ')) : '';
		
		const row = this._list_column_tmpl.evaluateToElement({
			...data,
			rowNum: index,
			items: itemsText
		});

		row.dataset.index = index;
		const columnDataContainer = row.querySelector('.js-column-data');
		
		// Helper recursivo para criar inputs hidden
		const createHiddenInput = (name, value) => {
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = value;
			columnDataContainer.appendChild(input);
		};

		const parseData = (prefix, obj) => {
			for (const [key, value] of Object.entries(obj)) {
				if (key === 'edit') continue;
				
				if (typeof value === 'object' && value !== null) {
					parseData(`${prefix}[${key}]`, value);
				} else {
					createHiddenInput(`${prefix}[${key}]`, value);
				}
			}
		};

		// Reconstrói a estrutura de dados nos inputs hidden
		parseData(`columns[${index}]`, data);

		return row;
	}
};
