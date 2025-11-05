<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


namespace Widgets\GeomapPlus\Includes;

use Zabbix\Widgets\CWidgetForm;

use Zabbix\Widgets\Fields\{
	CWidgetFieldColor,
	CWidgetFieldLatLng,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldMultiSelectOverrideHost,
	CWidgetFieldRadioButtonList,
	CWidgetFieldTags,
	CWidgetFieldTextBox
};

/**
 * GeomapPlus widget form.
 */
class WidgetForm extends CWidgetForm {

	public const MARKER_SHAPE_CIRCLE = 0;
	public const MARKER_SHAPE_RECTANGLE = 1;
	public const MARKER_SHAPE_PIN = 2;
	public const MARKER_SHAPE_CLOUD = 3;

	public function addFields(): self {
		return $this
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
			)
			->addField($this->isTemplateDashboard()
				? null
				: (new CWidgetFieldRadioButtonList('evaltype', _('Tags'), [
					TAG_EVAL_TYPE_AND_OR => _('And/Or'),
					TAG_EVAL_TYPE_OR => _('Or')
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldTags('tags')
			)
			->addField(
				new CWidgetFieldLatLng('default_view', _('Initial view'))
			)
			->addField(
				(new CWidgetFieldTextBox('group_name_pattern', _('Group name pattern (regex)')))
					->setPlaceholder('/^(.+?) - (.+)$/i')
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldTextBox('group_name_replacement', _('Group name replacement')))
					->setPlaceholder('$2')
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldRadioButtonList('marker_shape', _('Marker Shape'), [
					self::MARKER_SHAPE_CIRCLE => _('Circle'),
					self::MARKER_SHAPE_RECTANGLE => _('Rectangle'),
					self::MARKER_SHAPE_PIN => _('Pin'),
					self::MARKER_SHAPE_CLOUD => _('Cloud')
				]))->setDefault(self::MARKER_SHAPE_CIRCLE)
			)
			->addField(
				(new CWidgetFieldColor('color_no_problems', _('Color: No Problems')))->setDefault('00AA00')
			)
			->addField(
				(new CWidgetFieldColor('color_information', _('Color: Information')))->setDefault('7499FF')
			)
			->addField(
				(new CWidgetFieldColor('color_warning', _('Color: Warning')))->setDefault('FFC859')
			)
			->addField(
				(new CWidgetFieldColor('color_average', _('Color: Average')))->setDefault('FFA059')
			)
			->addField(
				(new CWidgetFieldColor('color_high', _('Color: High')))->setDefault('E97659')
			)
			->addField(
				(new CWidgetFieldColor('color_disaster', _('Color: Disaster')))->setDefault('E45959')
			)
			->addField(
				new CWidgetFieldMultiSelectOverrideHost()
			);
	}
}