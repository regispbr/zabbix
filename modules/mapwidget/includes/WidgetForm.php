<?php declare(strict_types = 0);

namespace Modules\MapWidget\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldRadioButtonList,
	CWidgetFieldSeverities, // <-- Importante
	CWidgetFieldTags,
	CWidgetFieldTextBox
};

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		// Map Config
		$this->addField(
			(new CWidgetFieldTextBox('map_id', _('Map ID')))
				->setDefault('019a7f9a-5e75-73fc-9e12-49ecbdf5276b')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
		);

		$this->addField(
			(new CWidgetFieldTextBox('map_key', _('Map Key')))
				->setDefault('EcU1lnbZ4xnZL4N9hK7w')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
		);

		$this->addField(
			(new CWidgetFieldIntegerBox('zoom', _('Zoom Level')))
				->setDefault(3)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		$this->addField(
			(new CWidgetFieldTextBox('center_lat', _('Center Latitude')))
				->setDefault('-16')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		$this->addField(
			(new CWidgetFieldTextBox('center_lng', _('Center Longitude')))
				->setDefault('-52')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		$this->addField(
			(new CWidgetFieldTextBox('bearing', _('Bearing (Rotation)')))
				->setDefault('0')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		$this->addField(
			(new CWidgetFieldTextBox('pitch', _('Pitch (Inclination)')))
				->setDefault('0')
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		// Filters
		$this->addField(
			(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')))
		);

		$this->addField(
			(new CWidgetFieldMultiSelectHost('hosts', _('Hosts')))
		);

		$this->addField(
			(new CWidgetFieldMultiSelectHost('exclude_hosts', _('Exclude Hosts')))
		);

		$this->addField(
			(new CWidgetFieldRadioButtonList('evaltype', _('Problem tags'), [
				TAG_EVAL_TYPE_AND_OR => _('And/Or'),
				TAG_EVAL_TYPE_OR => _('Or')
			]))->setDefault(TAG_EVAL_TYPE_AND_OR)
		);

		$this->addField(
			new CWidgetFieldTags('tags')
		);

		// Labels
		$this->addField(
			(new CWidgetFieldTextBox('label_regex', _('Label Regex (optional)')))
				->setDefault('([A-Z]{4})-.')
		);

		$this->addField(
			(new CWidgetFieldRadioButtonList('label_source', _('Pin Title'), [
				1 => _('Host Name'),
				2 => _('Host Group Name')
			]))
				->setDefault(1)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		// --- MUDANÇA: SEVERIDADE ---
		// Substitui os checkboxes individuais por um campo Severities
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

		return $this;
	}
}
