<?php declare(strict_types = 0);

namespace Modules\MenuWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use CSettingsHelper;

class WidgetView extends CControllerDashboardWidgetView {

	protected function init(): void {
		parent::init();
	}

	protected function doAction(): void {
		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'menu_orientation' => $this->fields_values['menu_orientation'],
			'menu_position' => $this->fields_values['menu_position'],
			'font_family' => $this->fields_values['font_family'],
			'font_size' => $this->fields_values['font_size'],
			'font_color' => $this->fields_values['font_color'],
			'bg_color' => $this->fields_values['bg_color'],
			'hover_color' => $this->fields_values['hover_color'],
			'menu_items' => $this->fields_values['menu_items'],
			'max_visible_items' => $this->fields_values['max_visible_items'],
			'collapsible' => $this->fields_values['collapsible'],
			'collapsed_by_default' => $this->fields_values['collapsed_by_default'],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}
}
