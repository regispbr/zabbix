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

class WidgetText extends CWidget {

	static ZBX_STYLE_CLASS = 'text-widget';

	onInitialize() {
		this._body = this._target.querySelector('.text-widget-container');
		this.setContainerSize();
		
		// Remove loader immediately after initialization
		this._target.classList.remove('is-loading');
	}

	onResize() {
		this.setContainerSize();
	}

	setContainerSize() {
        // Verifica se _body (nosso contêiner) e _content_body (o contêiner do Zabbix 7.0) existem
		if (this._body !== null && this._content_body) { 
			const padding = parseInt(this._fields.padding) || 10;

            
            const rect = this._content_body.getBoundingClientRect();
			const available_height = rect.height - (padding * 2);
			const available_width = rect.width - (padding * 2);
			
			// Apply dimensions directly to container
			if (available_height > 0) {
				this._body.style.height = available_height + 'px';
			}
			if (available_width > 0) {
				this._body.style.width = available_width + 'px';
			}
			
			// Reapply alignment settings
			this.applyAlignment();
		}
	}

	applyAlignment() {
		if (this._body !== null && this._fields.text_align !== undefined) {
			const alignments = ['left', 'center', 'right', 'justify'];
			const align = alignments[parseInt(this._fields.text_align)] || 'left';
			this._body.style.textAlign = align;
		}
	}

	onEdit() {
		// Called when widget enters edit mode
		this.setEditMode();
	}

	onEditEnd() {
		// Called when widget exits edit mode
		this.unsetEditMode();
	}

	setEditMode() {
		if (this._body !== null) {
			this._body.style.cursor = 'text';
			this._body.setAttribute('contenteditable', 'true');
			this._body.addEventListener('input', this.onTextChange.bind(this));
			this._body.addEventListener('blur', this.onTextBlur.bind(this));
		}
	}

	unsetEditMode() {
		if (this._body !== null) {
			this._body.style.cursor = 'default';
			this._body.setAttribute('contenteditable', 'false');
			this._body.removeEventListener('input', this.onTextChange.bind(this));
			this._body.removeEventListener('blur', this.onTextBlur.bind(this));
		}
	}

	onTextChange(event) {
		// Update the widget's text content in real-time
		const text_content = event.target.innerText || event.target.textContent || '';
		this._fields.text_content = text_content;
	}

	onTextBlur(event) {
		// Save changes when focus is lost
		const text_content = event.target.innerText || event.target.textContent || '';
		this._fields.text_content = text_content;
		this.save();
	}

	getUpdateRequestData() {
		return {
			...super.getUpdateRequestData(),
			text_content: this._fields.text_content || '',
			font_size: this._fields.font_size || 14,
			font_color: this._fields.font_color || '000000',
			background_color: this._fields.background_color || 'FFFFFF',
			font_family: this._fields.font_family || 'Arial, sans-serif',
			text_align: this._fields.text_align || 0,
			font_weight: this._fields.font_weight || 0,
			font_style: this._fields.font_style || 0,
			line_height: this._fields.line_height || 120,
			padding: this._fields.padding || 10,
			show_border: this._fields.show_border || 0,
			border_color: this._fields.border_color || 'CCCCCC',
			border_width: this._fields.border_width || 1
		};
	}

	setContents(response) {
		super.setContents(response);
		
		// Remove loader after content is set
		this._target.classList.remove('is-loading');
		
		// Update the display after content change
		this.setContainerSize();
		
		// Re-apply edit mode if currently editing
		if (this._is_edit_mode) {
			this.setEditMode();
		}
	}

	hasPadding() {
		return false;
	}
}
