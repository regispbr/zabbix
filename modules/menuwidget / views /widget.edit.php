<?php declare(strict_types = 0);

/**
 * Menu widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectItem;

$form = (new CWidgetFormView($data));

// Menu Orientation
$form->addField(
	new CWidgetFieldRadioButtonListView($data['fields']['menu_orientation'])
);

// Menu Position (for vertical orientation)
$form->addField(
	new CWidgetFieldRadioButtonListView($data['fields']['menu_position'])
);

// Font Family
$form->addField(
	new CWidgetFieldTextBoxView($data['fields']['font_family'])
);

// Font Size
$form->addField(
	new CWidgetFieldIntegerBoxView($data['fields']['font_size'])
);

// Font Color
$form->addField(
	new CWidgetFieldColorView($data['fields']['font_color'])
);

// Background Color
$form->addField(
	new CWidgetFieldColorView($data['fields']['bg_color'])
);

// Hover Color
$form->addField(
	new CWidgetFieldColorView($data['fields']['hover_color'])
);

// Max Visible Items
$form->addField(
	new CWidgetFieldIntegerBoxView($data['fields']['max_visible_items'])
);

// Collapsible
$form->addField(
	new CWidgetFieldCheckBoxView($data['fields']['collapsible'])
);

// Collapsed by Default
$form->addField(
	new CWidgetFieldCheckBoxView($data['fields']['collapsed_by_default'])
);

// Menu Items
$field_menu_items = $data['fields']['menu_items'];

$form->addItem(
	new CTag('script', true,
		'const ZBX_STYLE_LIST_ACCORDION_ITEM_OPENED = "'.ZBX_STYLE_LIST_ACCORDION_ITEM_OPENED.'";'.
		'const ZBX_STYLE_LIST_ACCORDION_ITEM_CLOSED = "'.ZBX_STYLE_LIST_ACCORDION_ITEM_CLOSED.'";'
	)
);

$form->addFieldset(
	(new CWidgetFieldsetCollapsibleView(_('Menu Items')))
		->addItem(
			(new CDiv(
				(new CTable())
					->setId('menu_items_table')
					->addClass('menu-items-table')
					->setHeader([
						_('Label'),
						_('URL'),
						_('Image'),
						_('Actions')
					])
					->addItem(
						(new CTag('tbody', true))
					)
			))
		)
		->addItem(
			(new CSimpleButton(_('Add menu item')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->setId('menu_item_add')
		)
);

$form->includeJsFile('widget.edit.js.php');

$form->addJavaScript('widget_menuwidget_form.init('.json_encode([
	'menu_items' => $field_menu_items->getValue()
], JSON_THROW_ON_ERROR).');');

$form->show();
