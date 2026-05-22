<?php

namespace Apps\Tms\Packages\Billing\Invoices\Model;

use System\Base\BaseModel;

class AppsTmsBillingInvoices extends BaseModel
{
    public $id;

    public $organisation_id;

    public $company_id;

    public $date;

    public $due_date;

    public $invoice_no;

    public $financial_year;

    public $lr_no;

    public $lr_date;

    public $po_number;

    public $material_invoice_no;

    public $vehicle_id;
}