<?php

namespace Apps\Tms\Packages\Jobs\Invoices\Model;

use System\Base\BaseModel;

class AppsTmsJobsInvoices extends BaseModel
{
    public $id;

    public $invoice_no;

    public $financial_year;

    public $invoice_date;

    public $due_date;

    public $po_number;

    public $material_invoice_no;

    public $signed_uuid;

    public $signed_document;

    public $signed_by;

    public $signed_at;

    public $invoice_dev_notes;

    public $invoice_notes;
}