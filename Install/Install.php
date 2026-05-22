<?php

namespace Apps\Tms\Packages\Billing\Invoices\Install;

use Apps\Tms\Packages\Billing\Invoices\Install\Schema\BillingInvoices;
use Apps\Tms\Packages\Billing\Invoices\Model\AppsTmsBillingInvoices;
use System\Base\BasePackage;
use System\Base\Providers\ModulesServiceProvider\DbInstaller;

class Install extends BasePackage
{
    protected $databases;

    protected $dbInstaller;

    public function init()
    {
        $this->databases =
            [
                'apps_tms_billing_invoices'  => [
                    'schema'        => new BillingInvoices,
                    'model'         => new AppsTmsBillingInvoices,
                    'configParams'  =>
                        [
                            'min_index_chars' => 6
                        ]
                ],
            ];

        $this->dbInstaller = new DbInstaller;

        return $this;
    }

    public function install()
    {
        $this->preInstall();

        $this->installDb();

        $this->postInstall();

        return true;
    }

    protected function preInstall()
    {
        return true;
    }

    public function installDb()
    {
        $this->dbInstaller->installDb($this->databases);

        return true;
    }

    public function postInstall()
    {
        //Do anything after installation.
        return true;
    }

    public function truncate()
    {
        $this->dbInstaller->truncate($this->databases);
    }

    public function uninstall($remove = false)
    {
        if ($remove) {
            //Check Relationship
            //Drop Table(s)
            $this->dbInstaller->uninstallDb($this->databases);
        }

        return true;
    }
}