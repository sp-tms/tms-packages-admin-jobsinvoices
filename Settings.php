<?php

namespace Apps\Tms\Packages\Jobs\Invoices;

use System\Base\BasePackage;

class Settings extends BasePackage
{
    public function afterUpdate($packageClass, $package, $data)
    {
        return true;
    }
}