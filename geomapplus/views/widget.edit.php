<?php declare(strict_types = 1);
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


/**
 * GeomapPlus widget form view.
 *
 * @var CView $this
 * @var array $data
 */

$groupids = array_key_exists('groupids', $data['fields'])
	? new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
	: null;

(new CWidgetFormView($data))
	->addField($groupids)
	->addField(array_key_exists('hostids', $data['fields'])
		? (new CWidgetFieldMultiSelectHostView($data['fields']['hostids']))
			->setFilterPreselect([
				'id' => $groupids !== null ? $groupids->getId() : '',
				'accept' => CMultiSelect::FILTER_PRESELECT_ACCEPT_ID,
				'submit_as' => 'groupid'
			])
		: null
	)
	->addField(array_key_exists('evaltype', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['evaltype'])
		: null
	)
	->addField(array_key_exists('tags', $data['fields'])
		? new CWidgetFieldTagsView($data['fields']['tags'])
		: null
	)
	->addField(
		(new CWidgetFieldLatLngView($data['fields']['default_view']))
			->setPlaceholder('40.6892494,-74.0466891')
	)
	->addField(
		(new CWidgetFieldTextBoxView($data['fields']['group_name_pattern']))
			->setPlaceholder('/^(.+?) - (.+)$/i')
	)
	->addField(
		(new CWidgetFieldTextBoxView($data['fields']['group_name_replacement']))
			->setPlaceholder('$2')
	)
	->addField(
		new CWidgetFieldRadioButtonListView($data['fields']['marker_shape'])
	)
	->addField(
		new CWidgetFieldColorView($data['fields']['color_no_problems'])
	)
	->addField(
		new CWidgetFieldColorView($data['fields']['color_information'])
	)
	->addField(
		new CWidgetFieldColorView($data['fields']['color_warning'])
	)
	->addField(
		new CWidgetFieldColorView($data['fields']['color_average'])
	)
	->addField(
		new CWidgetFieldColorView($data['fields']['color_high'])
	)
	->addField(
		new CWidgetFieldColorView($data['fields']['color_disaster'])
	)
	->show();
