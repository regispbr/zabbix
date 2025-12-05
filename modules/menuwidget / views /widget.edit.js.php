<?php declare(strict_types = 0);
?>

window.widget_menuwidget_form = new class {
	init(widget_data) {
		this.menu_items = widget_data.menu_items || [];
		this.renderMenuItems();
		this.attachEventListeners();
	}

	renderMenuItems() {
		const tbody = document.querySelector('#menu_items_table tbody');
		tbody.innerHTML = '';

		this.menu_items.forEach((item, index) => {
			const row = this.createMenuItemRow(item, index);
			tbody.appendChild(row);
		});
	}

	createMenuItemRow(item, index) {
		const row = document.createElement('tr');
		row.dataset.index = index;

		// Label cell
		const labelCell = document.createElement('td');
		const labelInput = document.createElement('input');
		labelInput.type = 'text';
		labelInput.className = 'form-control';
		labelInput.value = item.label || '';
		labelInput.name = `menu_items[${index}][label]`;
		labelInput.placeholder = 'Menu Label';
		labelCell.appendChild(labelInput);

		// URL cell
		const urlCell = document.createElement('td');
		const urlInput = document.createElement('input');
		urlInput.type = 'text';
		urlInput.className = 'form-control';
		urlInput.value = item.url || '';
		urlInput.name = `menu_items[${index}][url]`;
		urlInput.placeholder = 'https://example.com';
		urlCell.appendChild(urlInput);

		// Image cell
		const imageCell = document.createElement('td');
		const imageSelect = document.createElement('select');
		imageSelect.className = 'form-control';
		imageSelect.name = `menu_items[${index}][image]`;
		
		const emptyOption = document.createElement('option');
		emptyOption.value = '';
		emptyOption.textContent = '-- Sem imagem --';
		imageSelect.appendChild(emptyOption);

		// Add Zabbix image options (you can customize this list)
		const zabbixImages = [
			'/images/ZabbixImages.jpg',
			'icon_info.png',
			'icon_error.png',
			'icon_ok.png',
			'icon_maintenance.png'
		];

		zabbixImages.forEach(img => {
			const option = document.createElement('option');
			option.value = `images/${img}`;
			option.textContent = img;
			if (item.image === `images/${img}`) {
				option.selected = true;
			}
			imageSelect.appendChild(option);
		});

		imageCell.appendChild(imageSelect);

		// Actions cell
		const actionsCell = document.createElement('td');
		const deleteBtn = document.createElement('button');
		deleteBtn.type = 'button';
		deleteBtn.className = 'btn-link';
		deleteBtn.textContent = 'Remover';
		deleteBtn.onclick = () => this.removeMenuItem(index);
		actionsCell.appendChild(deleteBtn);

		row.appendChild(labelCell);
		row.appendChild(urlCell);
		row.appendChild(imageCell);
		row.appendChild(actionsCell);

		return row;
	}

	attachEventListeners() {
		const addButton = document.getElementById('menu_item_add');
		if (addButton) {
			addButton.onclick = () => this.addMenuItem();
		}

		// Update menu_items array when inputs change
		document.querySelector('#menu_items_table').addEventListener('input', (e) => {
			this.updateMenuItemsFromDOM();
		});
	}

	addMenuItem() {
		this.menu_items.push({
			label: '',
			url: '',
			image: ''
		});
		this.renderMenuItems();
	}

	removeMenuItem(index) {
		this.menu_items.splice(index, 1);
		this.renderMenuItems();
	}

	updateMenuItemsFromDOM() {
		const rows = document.querySelectorAll('#menu_items_table tbody tr');
		this.menu_items = [];

		rows.forEach((row, index) => {
			const label = row.querySelector(`input[name="menu_items[${index}][label]"]`).value;
			const url = row.querySelector(`input[name="menu_items[${index}][url]"]`).value;
			const image = row.querySelector(`select[name="menu_items[${index}][image]"]`).value;

			this.menu_items.push({
				label: label,
				url: url,
				image: image
			});
		});
	}
};
