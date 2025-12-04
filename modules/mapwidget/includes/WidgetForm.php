<?php declare(strict_types = 0);

namespace Modules\MapWidget\Includes;

// Importamos os novos tipos de campos que vamos usar
use Zabbix\Widgets\{
		CWidgetField,
		CWidgetForm,
		Fields\CWidgetFieldCheckBox,
		Fields\CWidgetFieldIntegerBox,
		Fields\CWidgetFieldMultiSelectGroup,
		Fields\CWidgetFieldMultiSelectHost,
		Fields\CWidgetFieldRadioButtonList,
		Fields\CWidgetFieldTags,
		Fields\CWidgetFieldTextBox
};

/**
 * Map widget form.
 */
class WidgetForm extends CWidgetForm {

		public const SEVERITY_NOT_CLASSIFIED = 0;
		public const SEVERITY_INFORMATION = 1;
		public const SEVERITY_WARNING = 2;
		public const SEVERITY_AVERAGE = 3;
		public const SEVERITY_HIGH = 4;
		public const SEVERITY_DISASTER = 5;

		public function addFields(): self {
				$this->addField(
						(new CWidgetFieldTextBox('map_id', _('Map ID')))
								->setDefault('019a7f9a-5e75-73fc-9e12-49ecbdf5276b')
								->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
				)
				->addField(
						(new CWidgetFieldTextBox('map_key', _('Map Key')))
								->setDefault('EcU1lnbZ4xnZL4N9hK7w')
								->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
				)
				->addField(
						(new CWidgetFieldIntegerBox('zoom', _('Zoom Level')))
								->setDefault(3)
								->setFlags(CWidgetField::FLAG_NOT_EMPTY)
				)
				->addField(
						(new CWidgetFieldTextBox('center_lat', _('Center Latitude')))
								->setDefault('-16')
								->setFlags(CWidgetField::FLAG_NOT_EMPTY)
				)
				->addField(
						(new CWidgetFieldTextBox('center_lng', _('Center Longitude')))
								->setDefault('-52')
								->setFlags(CWidgetField::FLAG_NOT_EMPTY)
				)
				->addField(
						(new CWidgetFieldTextBox('bearing', _('Bearing (Rotation)')))
								->setDefault('0')
								->setFlags(CWidgetField::FLAG_NOT_EMPTY)
				)
				->addField(
						(new CWidgetFieldTextBox('pitch', _('Pitch (Inclination)')))
								->setDefault('0')
								->setFlags(CWidgetField::FLAG_NOT_EMPTY)
				)
				->addField(
						(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')))
				);

				$this->addField(
						(new CWidgetFieldMultiSelectGroup('hostgroups', _('Host groups')))
								->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
				)
				->addField(
						(new CWidgetFieldMultiSelectHost('hosts', _('Hosts')))
								->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
				)
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
						(new CWidgetFieldTextBox('label_regex', _('Label Regex (optional)')))
								->setDefault('([A-Z]{4})-.')
				)
				->addField(
						(new CWidgetFieldRadioButtonList('label_source', _('Pin Title'), [
								1 => _('Host Name'),		// Opção 1 (Padrão)
								2 => _('Host Group Name') // Opção 2
						]))
								->setDefault(1)
								->setFlags(CWidgetField::FLAG_NOT_EMPTY)
				);

				$this->addField(
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
				->addField(
						(new CWidgetFieldCheckBox('show_acknowledged', _('Show acknowledged problems')))
								->setDefault(1)
				)
				->addField(
						(new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems')))
								->setDefault(0)
				)
				// --- MUDANÇA AQUI: Adicionado filtro de manutenção ---
				->addField(
						(new CWidgetFieldCheckBox('exclude_maintenance', _('Exclude hosts in maintenance')))
								->setDefault(0)
				);
				// --- FIM DA MUDANÇA ---

				return $this;
		}
}
