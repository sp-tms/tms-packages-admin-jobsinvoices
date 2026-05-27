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

    public function extractDigitalSignature($uuid)
    {
        try {
            $file = $this->basepackages->storages->getFileInfo($uuid);

            if (!$file || ($file && $file['type'] !== 'application/pdf')) {
                throw new \Exception('Incorrect file. Signature cannot be extracted.');
            }

            $storage = $this->basepackages->storages->getById($file['storages_id']);

            if (!$storage) {
                throw new \Exception('Cannot access local storage, contact administrator.');
            }

            $location = base_path($storage['permission'] . '/' . $storage['id'] . '/data/' . $file['uuid_location'] . $file['uuid']);

            $fileContent = file_get_contents($location);

            //Get Modified Date
            $regexp = '#<xmp:MetadataDate.*xmp:MetadataDate>#s';
            preg_match_all($regexp, $fileContent, $signedAtArr);

            $signedAt = '';
            if (isset($signedAtArr[0][0])) {
                $signedAtArr[0][0] = str_replace('<xmp:MetadataDate>', '', $signedAtArr[0][0]);
                $signedAt = str_replace('</xmp:MetadataDate>', '', $signedAtArr[0][0]);
            }

            if ($signedAt === '') {
                throw new \Exception('Not able to retrieve Signature information/Document not signed.');
            }

            // subexpressions are used to extract b and c
            $regexp = '#ByteRange\[\s*(\d+) (\d+) (\d+)#';
            $certArr = [];
            preg_match_all($regexp, $fileContent, $certArr);
            // $certArr[2][0] and $certArr[3][0] are b and c
            if (isset($certArr[2]) && isset($certArr[3]) && isset($certArr[2][0]) && isset($certArr[3][0])) {
                $start = $certArr[2][0];
                $end = $certArr[3][0];

                if ($stream = fopen($location, 'rb')) {
                    // because we need to exclude < and > from start and end
                    $signature = stream_get_contents($stream, $end - $start - 2, $start + 1);

                    fclose($stream);
                }
            }

            $seq = \Sop\ASN1\Type\Constructed\Sequence::fromDER(hex2bin($signature));
            $signed_data = $seq->getTagged(0)->asExplicit()->asSequence();
            $ecac = $signed_data->getTagged(0)->asImplicit(\Sop\ASN1\Element::TYPE_SET)->asSet();
            $ecoc = $ecac->at($ecac->count() - 1);
            $cert = \Sop\X509\Certificate\Certificate::fromASN1($ecoc->asSequence());

            $signedBy = str_replace('cn=', '', $cert->tbsCertificate()->subject()->all()[0]->toString());

            if ($signedBy && $signedAt) {
                $this->addResponse('Extracted successfully!', 0, ['fileName' => $file['org_file_name'], 'signedBy' => $signedBy, 'signedAt' => $signedAt]);
            }
        } catch (\throwable $e) {
            $this->addResponse($e->getMessage(), 1);

            return false;
        }
    }
}