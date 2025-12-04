<?php declare(strict_types = 0);

namespace Modules\AlarmWidget\Includes;

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
	CWidgetFieldSeverities,
	CWidgetFieldTags
};

/**
 * Alarm widget form.
 */
class WidgetForm extends CWidgetForm {

	// Severity constants (matching Zabbix 7.0)
	public const SEVERITY_NOT_CLASSIFIED = 0;
	public const SEVERITY_INFORMATION = 1;
	public const SEVERITY_WARNING = 2;
	public const SEVERITY_AVERAGE = 3;
	public const SEVERITY_HIGH = 4;
	public const SEVERITY_DISASTER = 5;

	// Problem status
	public const PROBLEM_STATUS_ALL = 0;
	public const PROBLEM_STATUS_PROBLEM = 1;
	public const PROBLEM_STATUS_RESOLVED = 2;

	// --- MUDANÇA: ADICIONADO CONSTANTES DE SORT ---
	public const SORT_BY_TIME = 0;
	public const SORT_BY_SEVERITY = 1;
	public const SORT_BY_HOST = 2;
	// --- FIM DA MUDANÇA ---

	public function addFields(): self {
		// Host groups filter
		$this->addField(
			new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
		);

		// Hosts filter (include)
		$this->addField(
			new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
		);

		// Exclude hosts filter
		$this->addField(
			new CWidgetFieldMultiSelectHost('exclude_hostids', _('Exclude hosts'))
		);

		// Severity filter
		$this->addField(
			(new CWidgetFieldSeverities('severities', _('Severity')))
				->setDefault([
					self::SEVERITY_NOT_CLASSIFIED,
					self::SEVERITY_INFORMATION,
					self::SEVERITY_WARNING,
					self::SEVERITY_AVERAGE,
					self::SEVERITY_HIGH,
					self::SEVERITY_DISASTER
				])
		);

		// Exclude hosts in maintenance
		$this->addField(
			(new CWidgetFieldCheckBox('exclude_maintenance', _('Exclude hosts in maintenance')))
				->setDefault(0)
		);

		$this->addField(
            		(new CWidgetFieldRadioButtonList('evaltype', _('Problem Tags'), [
                		TAG_EVAL_TYPE_AND_OR => _('And/Or'),
                		TAG_EVAL_TYPE_OR => _('Or')
            ]))
                ->setDefault(TAG_EVAL_TYPE_AND_OR)
        	);

        	// Tags
        	$this->addField(
            		(new CWidgetFieldTags('tags', _('.')))->setDefault([])
        	);

		// Problem status filter
		$this->addField(
			(new CWidgetFieldRadioButtonList('problem_status', _('Problem status'), [
				self::PROBLEM_STATUS_ALL => _('All'),
				self::PROBLEM_STATUS_PROBLEM => _('Problem'),
				self::PROBLEM_STATUS_RESOLVED => _('Resolved')
			]))
				->setDefault(self::PROBLEM_STATUS_PROBLEM)
		);

		// Show acknowledged
		$this->addField(
			(new CWidgetFieldRadioButtonList('show_ack', _('Show acknowledged'), [
				0 => _('All'),
				1 => _('Unacknowledged'),
				2 => _('Acknowledged')
			]))
				->setDefault(0)
		);

		// Filtro "SUPPRESSED"
		$this->addField(
			(new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems')))
				->setDefault(0)
		);

		// --- MUDANÇA: FILTRO "SORT BY" COM CHAVES NUMÉRICAS ---
		$this->addField(
			(new CWidgetFieldRadioButtonList('sort_by', _('Sort by'), [
				self::SORT_BY_TIME => _('Time'),
				self::SORT_BY_SEVERITY => _('Severity'),
				self::SORT_BY_HOST => _('Host')
			]))
				->setDefault(self::SORT_BY_TIME) // Default é 0
		);
		// --- FIM DA MUDANÇA ---

		// Column visibility - using individual CheckBox fields
		$this->addField(
			(new CWidgetFieldCheckBox('show_column_host', _('Show column: Host')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_column_severity', _('Show column: Severity')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_column_status', _('Show column: Status')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_column_problem', _('Show column: Problem')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_column_operational_data', _('Show column: Operational data')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_column_ack', _('Show column: Ack')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_column_age', _('Show column: Age')))
				->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldCheckBox('show_column_time', _('Show column: Time')))
				->setDefault(1)
		);

		// Refresh interval
		/*$this->addField(
			(new CWidgetFieldIntegerBox('refresh_interval', _('Refresh interval (seconds)')))
				->setDefault(60)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);*/

		// Show lines
		$this->addField(
			(new CWidgetFieldIntegerBox('show_lines', _('Show lines')))
				->setDefault(25)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		);

		return $this;
	}
}
