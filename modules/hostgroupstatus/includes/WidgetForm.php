<?php declare(strict_types = 0);

namespace Modules\HostGroupStatus\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm,
	Fields\CWidgetFieldCheckBox,
	Fields\CWidgetFieldColor,
	Fields\CWidgetFieldIntegerBox,
	Fields\CWidgetFieldMultiSelectGroup,
	Fields\CWidgetFieldMultiSelectHost,
	Fields\CWidgetFieldRadioButtonList,
	Fields\CWidgetFieldSeverities,
	Fields\CWidgetFieldTags,
	Fields\CWidgetFieldTextBox
};

class WidgetForm extends CWidgetForm {

	public const COUNT_MODE_WITH_ALARMS = 1;
	public const COUNT_MODE_WITHOUT_ALARMS = 2;
	public const COUNT_MODE_ALL = 3;
	
	// Constantes de Status
	private const PROBLEM_STATUS_ALL = 0;
	private const PROBLEM_STATUS_PROBLEM = 1;
	private const PROBLEM_STATUS_RESOLVED = 2;

	public function addFields(): self {
		$this->addField(
			(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')))
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
		);

		$this->addField(
			(new CWidgetFieldMultiSelectHost('hosts', _('Hosts')))
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		);
		
		$this->addField(
			(new CWidgetFieldMultiSelectHost('exclude_hosts', _('Exclude Hosts')))
		);

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
			(new CWidgetFieldRadioButtonList('evaltype', _('Problem tags'), [
				TAG_EVAL_TYPE_AND_OR => _('And/Or'),
				TAG_EVAL_TYPE_OR => _('Or')
			]))->setDefault(TAG_EVAL_TYPE_AND_OR)
		);

		$this->addField(new CWidgetFieldTags('tags'));

		// --- NOVO CAMPO: PROBLEM STATUS ---
		$this->addField(
			(new CWidgetFieldRadioButtonList('problem_status', _('Problem status'), [
				self::PROBLEM_STATUS_ALL => _('All'),
				self::PROBLEM_STATUS_PROBLEM => _('Problem'),
				self::PROBLEM_STATUS_RESOLVED => _('Resolved')
			]))->setDefault(self::PROBLEM_STATUS_PROBLEM)
		);
		// ----------------------------------

		$this->addField(
			(new CWidgetFieldCheckBox('show_acknowledged', _('Show acknowledged problems')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems')))
				->setDefault(0)
		);
		
		$this->addField(
			(new CWidgetFieldCheckBox('show_suppressed_only', _('Show ONLY suppressed')))
				->setDefault(0)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('exclude_maintenance', _('Exclude hosts in maintenance')))
				->setDefault(0)
		);

		$this->addField(
			(new CWidgetFieldRadioButtonList('count_mode', _('Count Mode'), [
				self::COUNT_MODE_WITH_ALARMS => _('Hosts with alarms'),
				self::COUNT_MODE_WITHOUT_ALARMS => _('Hosts without alarms'),
				self::COUNT_MODE_ALL => _('All hosts')
			]))
				->setDefault(self::COUNT_MODE_WITH_ALARMS)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		$this->addField(
			(new CWidgetFieldColor('widget_color', _('Widget Color')))
				->setDefault('4CAF50')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		$this->addField((new CWidgetFieldCheckBox('show_group_name', _('Show group name')))->setDefault(1));
		$this->addField((new CWidgetFieldTextBox('group_name_text', _('Custom group name')))->setDefault(''));
		$this->addField((new CWidgetFieldCheckBox('enable_url_redirect', _('Enable URL redirect')))->setDefault(0));
		$this->addField((new CWidgetFieldTextBox('redirect_url', _('Redirect URL')))->setDefault(''));
		$this->addField((new CWidgetFieldCheckBox('open_in_new_tab', _('Open in new tab')))->setDefault(1));
		$this->addField((new CWidgetFieldIntegerBox('font_size', _('Font Size (px)')))->setDefault(14)->setFlags(CWidgetField::FLAG_NOT_EMPTY));
		$this->addField((new CWidgetFieldTextBox('font_family', _('Font Family')))->setDefault('Arial, sans-serif'));
		$this->addField((new CWidgetFieldCheckBox('show_border', _('Show Border')))->setDefault(1));
		$this->addField((new CWidgetFieldIntegerBox('border_width', _('Border Width (px)')))->setDefault(2));
		$this->addField((new CWidgetFieldIntegerBox('padding', _('Padding (px)')))->setDefault(10)->setFlags(CWidgetField::FLAG_NOT_EMPTY));

		return $this;
	}
}
