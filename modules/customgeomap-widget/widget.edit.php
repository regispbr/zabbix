<?php declare(strict_types = 0);

/**
 * Custom Geo Map widget form view.
 *
 * @var CView $this
 * @var array $data
 */

(new CWidgetFormView($data))
    ->addField(
        new CWidgetFieldTextBoxView($data['fields']['maptiler_key'])
    )
    ->addField(
        new CWidgetFieldTextBoxView($data['fields']['style_url'])
    )
    ->addField(
        new CWidgetFieldTextBoxView($data['fields']['initial_lat'])
    )
    ->addField(
        new CWidgetFieldTextBoxView($data['fields']['initial_lon'])
    )
    ->addField(
        new CWidgetFieldIntegerBoxView($data['fields']['initial_zoom'])
    )
    ->addField(
        new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
    )
    ->addField(
        new CWidgetFieldMultiSelectHostView($data['fields']['hostids'])
    )
    ->addField(
        new CWidgetFieldTextBoxView($data['fields']['name_regex'])
    )
    ->addField(
        new CWidgetFieldTextBoxView($data['fields']['name_replacement'])
    )
    ->addFieldset(
        (new CWidgetFormFieldsetCollapsibleView(_('Severity colors and sizes')))
            ->addField(
                new CWidgetFieldColorView($data['fields']['severity_color_0'])
            )
            ->addField(
                new CWidgetFieldIntegerBoxView($data['fields']['severity_size_0'])
            )
            ->addField(
                new CWidgetFieldColorView($data['fields']['severity_color_1'])
            )
            ->addField(
                new CWidgetFieldIntegerBoxView($data['fields']['severity_size_1'])
            )
            ->addField(
                new CWidgetFieldColorView($data['fields']['severity_color_2'])
            )
            ->addField(
                new CWidgetFieldIntegerBoxView($data['fields']['severity_size_2'])
            )
            ->addField(
                new CWidgetFieldColorView($data['fields']['severity_color_3'])
            )
            ->addField(
                new CWidgetFieldIntegerBoxView($data['fields']['severity_size_3'])
            )
            ->addField(
                new CWidgetFieldColorView($data['fields']['severity_color_4'])
            )
            ->addField(
                new CWidgetFieldIntegerBoxView($data['fields']['severity_size_4'])
            )
            ->addField(
                new CWidgetFieldColorView($data['fields']['severity_color_5'])
            )
            ->addField(
                new CWidgetFieldIntegerBoxView($data['fields']['severity_size_5'])
            )
    )
    ->addFieldset(
        (new CWidgetFormFieldsetCollapsibleView(_('Advanced options')))
            ->addField(
                new CWidgetFieldRadioButtonListView($data['fields']['show_maintenance'])
            )
            ->addField(
                new CWidgetFieldRadioButtonListView($data['fields']['show_acknowledged'])
            )
            ->addField(
                new CWidgetFieldIntegerBoxView($data['fields']['min_severity'])
            )
            ->addField(
                new CWidgetFieldTextBoxView($data['fields']['cluster_threshold'])
            )
    )
    ->includeJsFile('widget.edit.js.php')
    ->show();
