<?php

namespace Apps\Tms\Packages\Jobs\Invoices;

use Apps\Tms\Packages\Jobs\Invoices\Model\AppsTmsJobsInvoices;
use System\Base\BasePackage;

class JobsInvoices extends BasePackage
{
    protected $modelToUse = AppsTmsJobsInvoices::class;

    protected $packageName = 'invoices';

    public $invoices;

    public function init()
    {
        parent::init();

        return $this;
    }

    public function getNextInvoiceNumber($financialYear)
    {
        if ($this->config->databasetype === 'db') {
            $params =
                [
                    'conditions'    => 'financial_year = :financialYear:',
                    'bind'          =>
                        [
                            'financialYear'         => $financialYear
                        ]
                ];
        } else {
            $params = ['conditions' => ['financial_year', '=', $financialYear]];
        }

        $invoices = $this->getByParams($params);

        $invoiceNumbers = [];

        if ($invoices && count($invoices) > 0) {
            foreach ($invoices as $invoice) {
                if ($invoice['invoice_no'] > 10000) {//Taking into note that while importing we create own invoice numbers using timestamp.
                    continue;
                }
                array_push($invoiceNumbers, (int) $invoice['invoice_no']);
            }

            if (count($invoiceNumbers) > 0) {
                asort($invoiceNumbers);

                $nextInvoiceNumber = $this->helper->last($invoiceNumbers) + 1;

                $this->addResponse('Generated next invoice #', 0, ['nextInvoiceNumber' => $nextInvoiceNumber]);

                return $nextInvoiceNumber;
            }
        }

        return 1;
    }

    public function checkInvoice($data)
    {
        if ($this->config->databasetype === 'db') {
            $params =
                [
                    'conditions'    => 'financial_year = :financialYear: AND invoice_no = :invoiceNo:',
                    'bind'          =>
                        [
                            'financialYear'         => $data['financial_year'],
                            'invoiceNo'             => $data['invoice_no']
                        ]
                ];
        } else {
            $params = ['conditions' =>
                [
                    ['financial_year', '=', $data['financial_year']],
                    ['invoice_no', '=', (int) $data['invoice_no']]
                ]
            ];
        }

        $invoices = $this->getByParams($params);

        if ($invoices && count($invoices) > 0) {
            $this->addResponse('Invoice # ' . $data['invoice_no'] . ' already exists!', 1);

            return false;
        }

        $this->addResponse('Invoice # ' . $data['invoice_no'] . ' is valid');
    }

    public function signInvoice($data)
    {
        if ($this->config->databasetype === 'db') {
            $params =
                [
                    'conditions'    => 'id = :id: AND financial_year = :financialYear: AND invoice_no = :invoiceNo:',
                    'bind'          =>
                        [
                            'id'                    => (int) $data['lr_no'],
                            'financialYear'         => $data['financial_year'],
                            'invoiceNo'             => (int) $data['invoice_no']
                        ]
                ];
        } else {
            $params = ['conditions' =>
                [
                    ['id', '=', (int) $data['lr_no']],
                    ['financial_year', '=', $data['financial_year']],
                    ['invoice_no', '=', (int) $data['invoice_no']]
                ]
            ];
        }

        $invoices = $this->getByParams($params);

        if ($invoices && count($invoices) === 1) {
            $invoice = $invoices[0];

            if ($this->access->auth->check()) {
                $profile = $this->basepackages->profiles->getProfile($this->access->auth->account()['id']);

                $invoice['signed_id'] = $this->access->auth->account()['id'];
                $invoice['signed_by'] = $profile['contact']['full_name'];
                $invoice['signed_at'] = (\Carbon\Carbon::now())->format('d-m-Y H:i:s');

                $this->update($invoice);

                $this->addResponse('Invoice Signed', 0, ['invoice' => $invoice]);

                return true;
            }
        }

        $this->addResponse('Unable to sign invoice!', 1);

        return false;
    }
}