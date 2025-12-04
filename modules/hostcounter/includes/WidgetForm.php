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

/**
 * Host Counter widget form.
 */
class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		// Host group selection
		$this->addField(
			(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')))
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
		)
		// Host selection
		->addField(
			(new CWidgetFieldMultiSelectHost('hosts', _('Hosts')))
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		)
		
		// Count options
		->addField(
			(new CWidgetFieldCheckBox('count_problems', _('Contar problemas')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_items', _('Contar itens')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_triggers', _('Contar triggers')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_disabled', _('Contar hosts desativados')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('count_maintenance', _('Contar hosts em manutenção')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_suppressed', _('Mostrar problemas suprimidos')))
				->setDefault(0)
		)
		
		// Custom icon
		->addField(
			(new CWidgetFieldTextBox('custom_icon', _('Ícone personalizado')))
				->setDefault('')
		);

		return $this;
	}
}
