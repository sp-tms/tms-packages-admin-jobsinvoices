<?php

namespace Apps\Tms\Packages\Billing\Invoices;

use System\Base\BasePackage;

class BillingInvoices extends BasePackage
{
    //protected $modelToUse = ::class;

    protected $packageName = 'billinginvoices';

    public $billinginvoices;

    public function getBillingInvoicesById($id)
    {
        $billinginvoices = $this->getById($id);

        if ($billinginvoices) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }

    public function addBillingInvoices($data)
    {
        //
    }

    public function updateBillingInvoices($data)
    {
        $billinginvoices = $this->getById($id);

        if ($billinginvoices) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }

    public function removeBillingInvoices($data)
    {
        $billinginvoices = $this->getById($id);

        if ($billinginvoices) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }
}