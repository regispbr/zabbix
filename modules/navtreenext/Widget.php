<?php declare(strict_types = 1);

namespace Modules\NavTreeNext;

use Zabbix\Core\CWidget;

class Widget extends CWidget {
	
	// Max depth of navigation tree.
	public const MAX_DEPTH = 10;
	
	// Link types
	public const LINK_TYPE_MAP = 0;
	public const LINK_TYPE_DASHBOARD = 1;
	public const LINK_TYPE_URL = 2;
	
	public function getDefaultName(): string {
		return _('Navigation tree next');
	}
	
	public function getFields(): array {
		return [
			new \Zabbix\Widgets\Fields\CWidgetFieldNavTree('navtree', _('Navigation tree')),
			new \Zabbix\Widgets\Fields\CWidgetFieldCheckBox('show_unavailable', _('Show unavailable maps'))
		];
	}
	
	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Add' => _('Add'),
				'Add child element' => _('Add child element'),
				'Add multiple maps' => _('Add multiple maps'),
				'Apply' => _('Apply'),
				'Cancel' => _('Cancel'),
				'Edit' => _('Edit'),
				'Edit tree element' => _('Edit tree element'),
				'Remove' => _('Remove'),
				'root' => _('root')
			]
		];
	}
}
