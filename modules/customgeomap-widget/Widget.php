<?php declare(strict_types = 0);

namespace Modules\CustomGeoMapWidget;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

    public const SHOW_MAINTENANCE_YES = 1;
    public const SHOW_MAINTENANCE_NO = 0;

    public const SHOW_ACKNOWLEDGED_YES = 1;
    public const SHOW_ACKNOWLEDGED_NO = 0;

    public function getDefaultName(): string {
        return _('Custom Geo Map');
    }

    public function getDefaultRefreshRate(): int {
        return 60;
    }


    public function getTranslationStrings(): array {
        return [
            'class.widget.js' => ['assets/js/class.widget.js']
        ];
    }
}
