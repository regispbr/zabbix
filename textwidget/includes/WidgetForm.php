<?php declare(strict_types = 0);

namespace Modules\TextWidget\Includes;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm,
	Fields\CWidgetFieldCheckBox,
	Fields\CWidgetFieldColor,
	Fields\CWidgetFieldIntegerBox,
	Fields\CWidgetFieldRadioButtonList,
	Fields\CWidgetFieldSelect,
	Fields\CWidgetFieldTextArea,
	Fields\CWidgetFieldTextBox
};

/**
 * Text widget form.
 */
class WidgetForm extends CWidgetForm {

	// Text alignment options
	public const TEXT_ALIGN_LEFT = 0;
	public const TEXT_ALIGN_CENTER = 1;
	public const TEXT_ALIGN_RIGHT = 2;
	public const TEXT_ALIGN_JUSTIFY = 3;

	// Font weight options
	public const FONT_WEIGHT_NORMAL = 0;
	public const FONT_WEIGHT_BOLD = 1;

	// Font style options
	public const FONT_STYLE_NORMAL = 0;
	public const FONT_STYLE_ITALIC = 1;

	public function addFields(): self {
		$this->addField(
			(new CWidgetFieldTextArea('text_content', _('Text Content')))
				->setDefault('')
				->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
		)
		->addField(
			(new CWidgetFieldIntegerBox('font_size', _('Font Size (px)')))
				->setDefault(14)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		)
		->addField(
			(new CWidgetFieldColor('font_color', _('Font Color')))
				->setDefault('000000')
		)
		->addField(
			(new CWidgetFieldColor('background_color', _('Background Color')))
				->setDefault('FFFFFF')
		)
		->addField(
			(new CWidgetFieldTextBox('font_family', _('Font Family')))
				->setDefault('Arial, sans-serif')
		)
		->addField(
			(new CWidgetFieldSelect('text_align', _('Text Alignment'), [
				self::TEXT_ALIGN_LEFT => _('Left'),
				self::TEXT_ALIGN_CENTER => _('Center'),
				self::TEXT_ALIGN_RIGHT => _('Right'),
				self::TEXT_ALIGN_JUSTIFY => _('Justify')
			]))
				->setDefault(self::TEXT_ALIGN_LEFT)
		)
		->addField(
			(new CWidgetFieldSelect('font_weight', _('Font Weight'), [
				self::FONT_WEIGHT_NORMAL => _('Normal'),
				self::FONT_WEIGHT_BOLD => _('Bold')
			]))
				->setDefault(self::FONT_WEIGHT_NORMAL)
		)
		->addField(
			(new CWidgetFieldSelect('font_style', _('Font Style'), [
				self::FONT_STYLE_NORMAL => _('Normal'),
				self::FONT_STYLE_ITALIC => _('Italic')
			]))
				->setDefault(self::FONT_STYLE_NORMAL)
		)
		->addField(
			(new CWidgetFieldIntegerBox('line_height', _('Line Height (%)')))
				->setDefault(120)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		)
		->addField(
			(new CWidgetFieldIntegerBox('padding', _('Padding (px)')))
				->setDefault(10)
				->setFlags(CWidgetField::FLAG_NOT_EMPTY)
		)
		->addField(
			(new CWidgetFieldCheckBox('show_border', _('Show Border')))
				->setDefault(0)
		)
		->addField(
			(new CWidgetFieldColor('border_color', _('Border Color')))
				->setDefault('CCCCCC')
		)
		->addField(
			(new CWidgetFieldIntegerBox('border_width', _('Border Width (px)')))
				->setDefault(1)
		);

		return $this;
	}
}
