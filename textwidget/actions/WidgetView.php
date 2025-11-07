<?php declare(strict_types = 0);
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

namespace Modules\TextWidget\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;
use Modules\TextWidget\Includes\WidgetForm;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		// Get text alignment mapping
		$text_align_map = [
			WidgetForm::TEXT_ALIGN_LEFT => 'left',
			WidgetForm::TEXT_ALIGN_CENTER => 'center',
			WidgetForm::TEXT_ALIGN_RIGHT => 'right',
			WidgetForm::TEXT_ALIGN_JUSTIFY => 'justify'
		];

		// Get font weight mapping
		$font_weight_map = [
			WidgetForm::FONT_WEIGHT_NORMAL => 'normal',
			WidgetForm::FONT_WEIGHT_BOLD => 'bold'
		];

		// Get font style mapping
		$font_style_map = [
			WidgetForm::FONT_STYLE_NORMAL => 'normal',
			WidgetForm::FONT_STYLE_ITALIC => 'italic'
		];

		// Prepare widget data
		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'text_content' => $this->fields_values['text_content'] ?? '',
			'font_size' => $this->fields_values['font_size'] ?? 14,
			'font_color' => '#' . ($this->fields_values['font_color'] ?? '000000'),
			'background_color' => '#' . ($this->fields_values['background_color'] ?? 'FFFFFF'),
			'font_family' => $this->fields_values['font_family'] ?? 'Arial, sans-serif',
			'text_align' => $text_align_map[$this->fields_values['text_align'] ?? WidgetForm::TEXT_ALIGN_LEFT],
			'font_weight' => $font_weight_map[$this->fields_values['font_weight'] ?? WidgetForm::FONT_WEIGHT_NORMAL],
			'font_style' => $font_style_map[$this->fields_values['font_style'] ?? WidgetForm::FONT_STYLE_NORMAL],
			'line_height' => $this->fields_values['line_height'] ?? 120,
			'padding' => $this->fields_values['padding'] ?? 10,
			'show_border' => $this->fields_values['show_border'] ?? 0,
			'border_color' => '#' . ($this->fields_values['border_color'] ?? 'CCCCCC'),
			'border_width' => $this->fields_values['border_width'] ?? 1,
			'fields_values' => $this->fields_values,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}
}
