<?php declare(strict_types = 0);

namespace Modules\HostGroupStatus\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldColor,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldRadioButtonList,
	CWidgetFieldSeverities, // <-- Importante
	CWidgetFieldTags, 
	CWidgetFieldTextBox
};

class WidgetForm extends CWidgetForm {

	// Count modes
	public const COUNT_MODE_WITH_ALARMS = 1;
	public const COUNT_MODE_WITHOUT_ALARMS = 2;
	public const COUNT_MODE_ALL = 3;
	
	public function addFields(): self {
		// Host group selection
		$this->addField(
			(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')))
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
		);

		// Host selection
		$this->addField(
			(new CWidgetFieldMultiSelectHost('hosts', _('Hosts')))
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		);
		
		// Exclude Hosts
		$this->addField(
			(new CWidgetFieldMultiSelectHost('exclude_hosts', _('Exclude Hosts')))
		);

		// --- MUDANÇA: SEVERIDADE ---
		// Substitui os 6 checkboxes individuais por um único campo de Severities
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
		// --- FIM DA MUDANÇA ---

		// Problem tags
		$this->addField(
			(new CWidgetFieldRadioButtonList('evaltype', _('Problem tags'), [
				TAG_EVAL_TYPE_AND_OR => _('And/Or'),
				TAG_EVAL_TYPE_OR => _('Or')
			]))
				->setDefault(TAG_EVAL_TYPE_AND_OR)
		);

		$this->addField(
			new CWidgetFieldTags('tags')
		);

		// Filters
		$this->addField(
			(new CWidgetFieldCheckBox('show_acknowledged', _('Show acknowledged problems')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems')))
				->setDefault(0)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('exclude_maintenance', _('Exclude hosts in maintenance')))
				->setDefault(0)
		);

		// Count mode
		$this->addField(
			(new CWidgetFieldRadioButtonList('count_mode', _('Count Mode'), [
				self::COUNT_MODE_WITH_ALARMS => _('Hosts with alarms'),
				self::COUNT_MODE_WITHOUT_ALARMS => _('Hosts without alarms'),
				self::COUNT_MODE_ALL => _('All hosts')
			]))
				->setDefault(self::COUNT_MODE_WITH_ALARMS)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		// Widget color
		$this->addField(
			(new CWidgetFieldColor('widget_color', _('Widget Color')))
				->setDefault('4CAF50')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		// Show group name
		$this->addField(
			(new CWidgetFieldCheckBox('show_group_name', _('Show group name')))
				->setDefault(1)
		);

		// Group name text
		$this->addField(
			(new CWidgetFieldTextBox('group_name_text', _('Custom group name')))
				->setDefault('')
		);

		// URL redirection settings
		$this->addField(
			(new CWidgetFieldCheckBox('enable_url_redirect', _('Enable URL redirect')))
				->setDefault(0)
		);

		$this->addField(
			(new CWidgetFieldTextBox('redirect_url', _('Redirect URL')))
				->setDefault('')
		);

		$this->addField(
			(new CWidgetFieldCheckBox('open_in_new_tab', _('Open in new tab')))
				->setDefault(1)
		);

		// Font settings
		$this->addField(
			(new CWidgetFieldIntegerBox('font_size', _('Font Size (px)')))
				->setDefault(14)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		$this->addField(
			(new CWidgetFieldTextBox('font_family', _('Font Family')))
				->setDefault('Arial, sans-serif')
		);

		// Border settings
		$this->addField(
			(new CWidgetFieldCheckBox('show_border', _('Show Border')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldIntegerBox('border_width', _('Border Width (px)')))
				->setDefault(2)
		);

		// Padding
		$this->addField(
			(new CWidgetFieldIntegerBox('padding', _('Padding (px)')))
				->setDefault(10)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		return $this;
	}
}
