<?php declare(strict_types = 0);

namespace Modules\MenuWidget\Includes;

use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldColor,
	CWidgetFieldIntegerBox,
	CWidgetFieldRadioButtonList,
	CWidgetFieldTextBox
};
use Modules\MenuWidget\Includes\CWidgetFieldMenuItems;

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldRadioButtonList('menu_orientation', _('Menu Orientation'), [
					'horizontal' => _('Horizontal'),
					'vertical' => _('Vertical')
				]))
					->setDefault('vertical')
			)
			->addField(
				(new CWidgetFieldRadioButtonList('menu_position', _('Menu Position'), [
					'left' => _('Left'),
					'top' => _('Top')
				]))
					->setDefault('left')
			)
			->addField(
				(new CWidgetFieldTextBox('font_family', _('Font Family')))
					->setDefault('Arial, sans-serif')
			)
			->addField(
				(new CWidgetFieldIntegerBox('font_size', _('Font Size (px)')))
					->setDefault(14)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldColor('font_color', _('Font Color')))
					->setDefault('333333')
			)
			->addField(
				(new CWidgetFieldColor('bg_color', _('Background Color')))
					->setDefault('F5F5F5')
			)
			->addField(
				(new CWidgetFieldColor('hover_color', _('Hover Color')))
					->setDefault('E0E0E0')
			)
			->addField(
				(new CWidgetFieldIntegerBox('max_visible_items', _('Max Visible Items')))
					->setDefault(5)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				new CWidgetFieldCheckBox('collapsible', _('Collapsible Menu'))
			)
			->addField(
				new CWidgetFieldCheckBox('collapsed_by_default', _('Collapsed by Default'))
			)
			->addField(
				new CWidgetFieldMenuItems('menu_items', _('Menu Items'))
			);
	}
}
