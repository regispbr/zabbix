<?php declare(strict_types = 0);

namespace Modules\CustomGeoMapWidget\Includes;

use Modules\CustomGeoMapWidget\Widget;
use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\CWidgetFieldTextBox;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectGroup;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectHost;
use Zabbix\Widgets\Fields\CWidgetFieldIntegerBox;
use Zabbix\Widgets\Fields\CWidgetFieldColor;
use Zabbix\Widgets\Fields\CWidgetFieldRadioButtonList;

class WidgetForm extends CWidgetForm {

    public function addFields(): self {
        $this->addField(
            (new CWidgetFieldTextBox('maptiler_key', _('MapTiler API Key')))
                ->setFlags(CWidgetField::FLAG_NOT_EMPTY)
        );
        
        $this->addField(
            new CWidgetFieldTextBox('style_url', _('MapTiler Style URL or ID'))
        );
        
        $this->addField(
            (new CWidgetFieldTextBox('initial_lat', _('Initial Latitude')))
                ->setDefault('-14.235')
        );
        
        $this->addField(
            (new CWidgetFieldTextBox('initial_lon', _('Initial Longitude')))
                ->setDefault('-51.925')
        );
        
        $this->addField(
            (new CWidgetFieldIntegerBox('initial_zoom', _('Initial Zoom')))
                ->setDefault(4)
                ->setFlags(CWidgetField::FLAG_NOT_EMPTY)
        );
        
        $this->addField(
            new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
        );
        
        $this->addField(
            new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
        );
        
        $this->addField(
            new CWidgetFieldTextBox('name_regex', _('Name Regex Pattern'))
        );
        
        $this->addField(
            new CWidgetFieldTextBox('name_replacement', _('Name Replacement'))
        );

        $severity_names = [
            0 => _('Not classified'),
            1 => _('Information'),
            2 => _('Warning'),
            3 => _('Average'),
            4 => _('High'),
            5 => _('Disaster')
        ];
        
        $default_colors = [
            0 => '97AAB3',
            1 => '7499FF',
            2 => 'FFC859',
            3 => 'FFA059',
            4 => 'E97659',
            5 => 'E45959'
        ];
        
        $default_sizes = [
            0 => 20,
            1 => 25,
            2 => 30,
            3 => 35,
            4 => 40,
            5 => 45
        ];

        for ($i = 0; $i <= 5; $i++) {
            $this->addField(
                (new CWidgetFieldColor('severity_color_'.$i, $severity_names[$i]))
                    ->setDefault($default_colors[$i])
            );
            
            $this->addField(
                (new CWidgetFieldIntegerBox('severity_size_'.$i, _('Size for severity ').$i))
                    ->setDefault($default_sizes[$i])
            );
        }

        $this->addField(
            (new CWidgetFieldRadioButtonList('show_maintenance', _('Show maintenance hosts'), [
                Widget::SHOW_MAINTENANCE_NO => _('No'),
                Widget::SHOW_MAINTENANCE_YES => _('Yes')
            ]))->setDefault(Widget::SHOW_MAINTENANCE_YES)
        );

        $this->addField(
            (new CWidgetFieldRadioButtonList('show_acknowledged', _('Show acknowledged problems'), [
                Widget::SHOW_ACKNOWLEDGED_NO => _('No'),
                Widget::SHOW_ACKNOWLEDGED_YES => _('Yes')
            ]))->setDefault(Widget::SHOW_ACKNOWLEDGED_YES)
        );

        $this->addField(
            (new CWidgetFieldIntegerBox('min_severity', _('Minimum severity')))
                ->setDefault(0)
        );
        
        $this->addField(
            (new CWidgetFieldTextBox('cluster_threshold', _('Cluster threshold (degrees)')))
                ->setDefault('0.001')
        );
        
        return $this;
    }
}
