<?php declare(strict_types = 0);

namespace Modules\MenuWidget\Includes;

use Zabbix\Widgets\CWidgetFieldView;

class CWidgetFieldMenuItemsView extends CWidgetFieldView {

	public function __construct(CWidgetFieldMenuItems $field) {
		$this->field = $field;
	}

	public function getView(): CDiv {
		return new CDiv();
	}

	public function getJavaScript(): string {
		return 'return ' . json_encode($this->field->getValue()) . ';';
	}
}
