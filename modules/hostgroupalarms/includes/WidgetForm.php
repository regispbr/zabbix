<?php declare(strict_types = 0);

namespace Modules\HostGroupAlarms\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm,
	Fields\CWidgetFieldCheckBox,
	Fields\CWidgetFieldColor,
	Fields\CWidgetFieldIntegerBox,
	Fields\CWidgetFieldMultiSelectGroup,
	Fields\CWidgetFieldMultiSelectHost,
	Fields\CWidgetFieldRadioButtonList,
	Fields\CWidgetFieldSelect,
	Fields\CWidgetFieldTags, // <-- ADICIONADO
	Fields\CWidgetFieldTextBox,
	Fields\CWidgetFieldUrl
};

/**
 * Host Group Alarms widget form.
 */
class WidgetForm extends CWidgetForm {

	// Severity levels (matching Zabbix trigger severities)
	public const SEVERITY_NOT_CLASSIFIED = 0;
	public const SEVERITY_INFORMATION = 1;
	public const SEVERITY_WARNING = 2;
	public const SEVERITY_AVERAGE = 3;
	public const SEVERITY_HIGH = 4;
	public const SEVERITY_DISASTER = 5;

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

		// ----- NOVOS CAMPOS ADICIONADOS -----
		->addField(
			(new CWidgetFieldMultiSelectHost('exclude_hosts', _('Exclude Hosts')))
		)
		->addField(
			(new CWidgetFieldRadioButtonList('evaltype', _('Problem tags'), [
				TAG_EVAL_TYPE_AND_OR => _('And/Or'),
				TAG_EVAL_TYPE_OR => _('Or')
			]))
				->setDefault(TAG_EVAL_TYPE_AND_OR)
		)
		->addField(
			new CWidgetFieldTags('tags')
		)
		->addField(
			(new CWidgetFieldCheckBox('show_acknowledged', _('Show acknowledged problems')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems')))
				->setDefault(0)
		)
		
		// --- MUDANÇA AQUI: Adicionado filtro de Manutenção ---
		->addField(
			(new CWidgetFieldCheckBox('exclude_maintenance', _('Exclude hosts in maintenance')))
				->setDefault(0)
		)
		// --- FIM DA MUDANÇA ---

		// ----- FIM DOS NOVOS CAMPOS -----

		// Show group name
		->addField(
			(new CWidgetFieldCheckBox('show_group_name', _('Show group name')))
				->setDefault(1)
		)
		// Group name text
		->addField(
			(new CWidgetFieldTextBox('group_name_text', _('Custom group name')))
				->setDefault('')
		)
		// URL redirection settings
		->addField(
			(new CWidgetFieldCheckBox('enable_url_redirect', _('Enable URL redirect')))
				->setDefault(0)
		)
		->addField(
			(new CWidgetFieldTextBox('redirect_url', _('Redirect URL')))
				->setDefault('')
		)
		->addField(
			(new CWidgetFieldCheckBox('open_in_new_tab', _('Open in new tab')))
				->setDefault(1)
		)
		// Tooltip settings
		->addField(
			(new CWidgetFieldCheckBox('show_detailed_tooltip', _('Show detailed tooltip on hover')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldIntegerBox('tooltip_max_items', _('Maximum items in tooltip')))
				->setDefault(10)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		)
		// Severity filters
		->addField(
			(new CWidgetFieldCheckBox('show_not_classified', _('Show Not classified')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_information', _('Show Information')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_warning', _('Show Warning')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_average', _('Show Average')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_high', _('Show High')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_disaster', _('Show Disaster')))
				->setDefault(1)
		)
		// Font settings
		->addField(
			(new CWidgetFieldIntegerBox('font_size', _('Font Size (px)')))
				->setDefault(14)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		)
		->addField(
			(new CWidgetFieldTextBox('font_family', _('Font Family')))
				->setDefault('Arial, sans-serif')
		)
		// Border settings
		->addField(
			(new CWidgetFieldCheckBox('show_border', _('Show Border')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldIntegerBox('border_width', _('Border Width (px)')))
				->setDefault(2)
		)
		// Padding
		->addField(
			(new CWidgetFieldIntegerBox('padding', _('Padding (px)')))
				->setDefault(10)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		return $this;
	}
}
