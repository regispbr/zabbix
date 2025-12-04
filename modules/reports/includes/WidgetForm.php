<?php declare(strict_types = 0);

namespace Modules\Reports\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm,
	Fields\CWidgetFieldCheckBox,
	Fields\CWidgetFieldCheckBoxList,
	Fields\CWidgetFieldColor,
	Fields\CWidgetFieldDatePicker,
	Fields\CWidgetFieldIntegerBox,
	Fields\CWidgetFieldMultiSelectGroup,
	Fields\CWidgetFieldMultiSelectHost,
	Fields\CWidgetFieldMultiSelectItem,
	Fields\CWidgetFieldRadioButtonList,
	Fields\CWidgetFieldSelect,
	Fields\CWidgetFieldTags,
	Fields\CWidgetFieldTextArea,
	Fields\CWidgetFieldTextBox
};

/**
 * Reports widget form.
 */
class WidgetForm extends CWidgetForm {

	// Report types
	public const REPORT_TYPE_AVAILABILITY = 0;
	public const REPORT_TYPE_PERFORMANCE = 1;
	public const REPORT_TYPE_ALERTS = 2;
	public const REPORT_TYPE_ALL = 3;

	// Alert severity levels
	public const SEVERITY_NOT_CLASSIFIED = 0;
	public const SEVERITY_INFORMATION = 1;
	public const SEVERITY_WARNING = 2;
	public const SEVERITY_AVERAGE = 3;
	public const SEVERITY_HIGH = 4;
	public const SEVERITY_DISASTER = 5;

	// Chart types
	public const CHART_TYPE_LINE = 0;
	public const CHART_TYPE_AREA = 1;
	public const CHART_TYPE_BAR = 2;
	public const CHART_TYPE_PIE = 3;
	public const CHART_TYPE_GAUGE = 4;

	// Display formats
	public const FORMAT_OPERATIONAL = 0;
	public const FORMAT_EXECUTIVE = 1;

	public function addFields(): self {
		// Report Configuration Section
		$this->addField(
			(new CWidgetFieldTextBox('report_title', _('Report Title')))
				->setDefault('Infrastructure Report')
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		)
		->addField(
			(new CWidgetFieldCheckBoxList('report_types', _('Report Types'), [
				self::REPORT_TYPE_AVAILABILITY => _('Availability'),
				self::REPORT_TYPE_PERFORMANCE => _('Performance'),
				self::REPORT_TYPE_ALERTS => _('Alerts')
			]))
				->setDefault([self::REPORT_TYPE_AVAILABILITY])
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		)
		->addField(
			(new CWidgetFieldRadioButtonList('display_format', _('Display Format'), [
				self::FORMAT_OPERATIONAL => _('Operational'),
				self::FORMAT_EXECUTIVE => _('Executive')
			]))
				->setDefault(self::FORMAT_OPERATIONAL)
		);

		// Time Period Section
		$this->addField(
			(new CWidgetFieldDatePicker('date_from', _('From')))
				->setDefault('now-7d')
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		)
		->addField(
			(new CWidgetFieldDatePicker('date_to', _('To')))
				->setDefault('now')
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		);

		// Filter Section - Host Groups
		$this->addField(
			new CWidgetFieldMultiSelectGroup('host_groups', _('Host Groups'))
		)
		->addField(
			new CWidgetFieldMultiSelectHost('hosts', _('Hosts'))
		)
		->addField(
			new CWidgetFieldTags('tags', _('Tags'))
		);

		// Alert Severity Filter
		$this->addField(
			(new CWidgetFieldCheckBoxList('alert_severities', _('Alert Severities'), [
				self::SEVERITY_NOT_CLASSIFIED => _('Not classified'),
				self::SEVERITY_INFORMATION => _('Information'),
				self::SEVERITY_WARNING => _('Warning'),
				self::SEVERITY_AVERAGE => _('Average'),
				self::SEVERITY_HIGH => _('High'),
				self::SEVERITY_DISASTER => _('Disaster')
			]))
				->setDefault([
					self::SEVERITY_WARNING,
					self::SEVERITY_AVERAGE,
					self::SEVERITY_HIGH,
					self::SEVERITY_DISASTER
				])
		);

		// Items Selection
		$this->addField(
			new CWidgetFieldMultiSelectItem('items', _('Items'))
		);

		// Chart Configuration
		$this->addField(
			(new CWidgetFieldCheckBox('show_charts', _('Include Charts')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldSelect('chart_type', _('Chart Type'), [
				self::CHART_TYPE_LINE => _('Line'),
				self::CHART_TYPE_AREA => _('Area'),
				self::CHART_TYPE_BAR => _('Bar'),
				self::CHART_TYPE_PIE => _('Pie'),
				self::CHART_TYPE_GAUGE => _('Gauge')
			]))
				->setDefault(self::CHART_TYPE_LINE)
		);

		// JRXML Template Upload
		$this->addField(
			(new CWidgetFieldTextBox('jrxml_template', _('JRXML Template Path')))
				->setDefault('')
				->setPlaceholder('/path/to/template.jrxml')
		);

		// PDF Options
		$this->addField(
			(new CWidgetFieldCheckBox('show_page_numbers', _('Show Page Numbers')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_header', _('Show Header')))
				->setDefault(1)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_footer', _('Show Footer')))
				->setDefault(1)
		);

		return $this;
	}
}