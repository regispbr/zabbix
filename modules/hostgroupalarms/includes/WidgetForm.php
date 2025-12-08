<?php declare(strict_types = 0);

namespace Modules\HostGroupAlarms\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm,
	Fields\CWidgetFieldCheckBox,
	Fields\CWidgetFieldIntegerBox,
	Fields\CWidgetFieldMultiSelectGroup,
	Fields\CWidgetFieldMultiSelectHost,
	Fields\CWidgetFieldRadioButtonList,
	Fields\CWidgetFieldSeverities,
	Fields\CWidgetFieldTags,
	Fields\CWidgetFieldTextBox,
	Fields\CWidgetFieldColor
};

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		$this->addField(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')));
		$this->addField(new CWidgetFieldMultiSelectHost('hosts', _('Hosts')));
		$this->addField(new CWidgetFieldMultiSelectHost('exclude_hosts', _('Exclude hosts')));

		$this->addField(
			(new CWidgetFieldSeverities('severities', _('Severity')))
				->setDefault([
					TRIGGER_SEVERITY_NOT_CLASSIFIED,
					TRIGGER_SEVERITY_INFORMATION,
					TRIGGER_SEVERITY_WARNING,
					TRIGGER_SEVERITY_AVERAGE,
					TRIGGER_SEVERITY_HIGH,
					TRIGGER_SEVERITY_DISASTER
				])
		);

		$this->addField(
			(new CWidgetFieldRadioButtonList('evaltype', _('Problem Tags'), [
				TAG_EVAL_TYPE_AND_OR => _('And/Or'),
				TAG_EVAL_TYPE_OR => _('Or')
			]))->setDefault(TAG_EVAL_TYPE_AND_OR)
		);

		$this->addField(new CWidgetFieldTags('tags', _('.'))->setDefault([]));

		$this->addField(
			(new CWidgetFieldCheckBox('show_acknowledged', _('Show acknowledged')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems')))
				->setDefault(0)
		);
		
		// --- NOVO CAMPO ---
		$this->addField(
			(new CWidgetFieldCheckBox('show_suppressed_only', _('Show ONLY suppressed')))
				->setDefault(0)
		);
		// ------------------

		$this->addField(
			(new CWidgetFieldCheckBox('exclude_maintenance', _('Exclude hosts in maintenance')))
				->setDefault(0)
		);

		$this->addField((new CWidgetFieldCheckBox('show_group_name', _('Show group name')))->setDefault(1));
		$this->addField(new CWidgetFieldTextBox('group_name_text', _('Custom group name')));
		$this->addField((new CWidgetFieldCheckBox('enable_url_redirect', _('Enable URL redirect')))->setDefault(0));
		$this->addField(new CWidgetFieldTextBox('redirect_url', _('Redirect URL')));
		$this->addField((new CWidgetFieldCheckBox('open_in_new_tab', _('Open in new tab')))->setDefault(1));
		$this->addField((new CWidgetFieldCheckBox('show_detailed_tooltip', _('Show detailed tooltip')))->setDefault(1));
		$this->addField((new CWidgetFieldIntegerBox('tooltip_max_items', _('Tooltip max items')))->setDefault(10));
		$this->addField((new CWidgetFieldIntegerBox('font_size', _('Font Size (px)')))->setDefault(14));
		$this->addField((new CWidgetFieldTextBox('font_family', _('Font Family')))->setDefault('Arial, sans-serif'));
		$this->addField((new CWidgetFieldCheckBox('show_border', _('Show Border')))->setDefault(1));
		$this->addField((new CWidgetFieldIntegerBox('border_width', _('Border Width (px)')))->setDefault(2));
		$this->addField((new CWidgetFieldIntegerBox('padding', _('Padding (px)')))->setDefault(10));

		return $this;
	}
}
