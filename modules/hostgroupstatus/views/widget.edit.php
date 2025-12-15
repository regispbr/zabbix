<?php declare(strict_types = 0);

use Modules\HostGroupStatus\Includes\WidgetForm;

$form = new CWidgetFormView($data);

$form
	->addField(new CWidgetFieldMultiSelectGroupView($data['fields']['hostgroups']))
	->addField(new CWidgetFieldMultiSelectHostView($data['fields']['hosts']))
	->addField(new CWidgetFieldMultiSelectHostView($data['fields']['exclude_hosts']))
	->addField(new CWidgetFieldSeveritiesView($data['fields']['severities']))
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['evaltype']))
	->addField(new CWidgetFieldTagsView($data['fields']['tags']))
	// --- NOVO ---
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['problem_status']))
	// ------------
	->addField(new CWidgetFieldCheckBoxView($data['fields']['show_acknowledged']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['show_suppressed']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['show_suppressed_only']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['exclude_maintenance']))
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['count_mode']))
	->addField(new CWidgetFieldColorView($data['fields']['widget_color']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['show_group_name']))
	->addField(new CWidgetFieldTextBoxView($data['fields']['group_name_text']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['enable_url_redirect']))
	->addField(new CWidgetFieldTextBoxView($data['fields']['redirect_url']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['open_in_new_tab']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['font_size']))
	->addField(new CWidgetFieldTextBoxView($data['fields']['font_family']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['show_border']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['border_width']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['padding']));

$form->show();
