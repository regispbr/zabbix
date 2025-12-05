<?php declare(strict_types = 0);

namespace Modules\HostCounter\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm,
	Fields\CWidgetFieldCheckBox,
	Fields\CWidgetFieldMultiSelectGroup,
	Fields\CWidgetFieldMultiSelectHost,
	Fields\CWidgetFieldTextBox
};

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		$this->addField(
			(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')))
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
		)
		->addField(
			(new CWidgetFieldMultiSelectHost('hosts', _('Hosts')))
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_problems', _('Count problems')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_items', _('Count items')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_triggers', _('Count triggers')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_disabled', _('Count disabled hosts')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_maintenance', _('Count maintenance hosts')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems')))
				->setDefault(0)
		)
		->addField(
			(new CWidgetFieldTextBox('custom_icon', _('Custom icon filename')))
				->setDefault('')
		);

		return $this;
	}
}
