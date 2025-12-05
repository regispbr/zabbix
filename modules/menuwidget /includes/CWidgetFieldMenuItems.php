<?php declare(strict_types = 0);

namespace Modules\MenuWidget\Includes;

use Zabbix\Widgets\CWidgetField;

class CWidgetFieldMenuItems extends CWidgetField {

	public const DEFAULT_VIEW = \Modules\MenuWidget\Includes\CWidgetFieldMenuItemsView::class;

	public function __construct(string $name, string $label = null) {
		parent::__construct($name, $label);

		$this->setDefault([]);
	}

	public function setValue($value): self {
		$this->value = (array) $value;

		return $this;
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		if (!is_array($this->value)) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', $this->label, _('an array is expected'));
			return $errors;
		}

		foreach ($this->value as $index => $item) {
			if (!is_array($item)) {
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', $this->label . '[' . $index . ']', _('an array is expected'));
				continue;
			}

			if (!array_key_exists('label', $item) || !is_string($item['label']) || $item['label'] === '') {
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', $this->label . '[' . $index . '][label]', _('cannot be empty'));
			}

			if (array_key_exists('url', $item) && !is_string($item['url'])) {
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', $this->label . '[' . $index . '][url]', _('a character string is expected'));
			}

			if (array_key_exists('image', $item) && !is_string($item['image'])) {
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', $this->label . '[' . $index . '][image]', _('a character string is expected'));
			}
		}

		return $errors;
	}

	public function toApi(array &$widget_fields = []): void {
		$value = $this->getValue();

		foreach ($value as $index => $item) {
			$widget_fields[] = [
				'type' => ZBX_WIDGET_FIELD_TYPE_STR,
				'name' => $this->name . '.label.' . $index,
				'value' => $item['label']
			];

			if (array_key_exists('url', $item)) {
				$widget_fields[] = [
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name . '.url.' . $index,
					'value' => $item['url']
				];
			}

			if (array_key_exists('image', $item)) {
				$widget_fields[] = [
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name . '.image.' . $index,
					'value' => $item['image']
				];
			}
		}
	}
}
