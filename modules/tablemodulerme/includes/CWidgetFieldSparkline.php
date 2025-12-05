<?php declare(strict_types = 0);

namespace Modules\TableModuleRME\Includes;

use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\Fields\CWidgetFieldTimePeriod;

class CWidgetFieldSparkline extends CWidgetField {

	public const DEFAULT_VIEW = null;

	public const DATA_SOURCE_AUTO = 0;
	public const DATA_SOURCE_HISTORY = 1;
	public const DATA_SOURCE_TRENDS = 2;

	public function __construct(string $name, ?string $label = null) {
		parent::__construct($name, $label);

		$this->setDefault([]);
	}

	public function toApi(array &$widget_fields = []): void {
		$value = $this->getValue();

		if ($value === null) {
			return;
		}

		// Width
		if (array_key_exists('width', $value)) {
			$widget_fields[] = [
				'type' => ZBX_WIDGET_FIELD_TYPE_INT32,
				'name' => $this->name.'.width',
				'value' => $value['width']
			];
		}

		// Fill
		if (array_key_exists('fill', $value)) {
			$widget_fields[] = [
				'type' => ZBX_WIDGET_FIELD_TYPE_INT32,
				'name' => $this->name.'.fill',
				'value' => $value['fill']
			];
		}

		// Color
		if (array_key_exists('color', $value)) {
			$widget_fields[] = [
				'type' => ZBX_WIDGET_FIELD_TYPE_STR,
				'name' => $this->name.'.color',
				'value' => $value['color']
			];
		}

		// History
		if (array_key_exists('history', $value)) {
			$widget_fields[] = [
				'type' => ZBX_WIDGET_FIELD_TYPE_INT32,
				'name' => $this->name.'.history',
				'value' => $value['history']
			];
		}

		// Time Period
		if (array_key_exists('time_period', $value)) {
			$time_period_field = (new CWidgetFieldTimePeriod($this->name.'.time_period', null))
				->setDefault($this->default['time_period'] ?? [])
				->setValue($value['time_period']);
			
			$time_period_field->toApi($widget_fields);
		}
	}
}
