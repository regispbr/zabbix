<?php declare(strict_types = 0);

namespace Modules\MenuWidget;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getDefaultName(): string {
		return _('Menu Widget');
	}

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Menu items' => _('Menu items'),
				'Add menu item' => _('Add menu item'),
				'No menu items defined' => _('No menu items defined')
			]
		];
	}
}
