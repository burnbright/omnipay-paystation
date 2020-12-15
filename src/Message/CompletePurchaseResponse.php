<?php

namespace Omnipay\Paystation\Message;

use DOMDocument;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Paystation\Message\CompletePurchaseRequest;

/**
 * Paystation Complete Purchase Response
 */
class CompletePurchaseResponse extends AbstractResponse
{
    use HasCardFields;

    public function __construct(CompletePurchaseRequest $request, $data)
    {
        $this->lookupField = 'LookupResponse';
        $this->request = $request;

        $responseDom = new DOMDocument;
        $responseDom->loadXML($data);
        $this->data = simplexml_import_dom($responseDom);

        if (!isset($this->data->LookupResponse)) {
            if (isset($this->data->LookupStatus->LookupMessage)) {
                throw new InvalidResponseException($this->data->LookupStatus->LookupMessage);
            } else {
                throw new InvalidResponseException;
            }
        }
    }

    public function isPending()
    {
        return false;
    }

    public function isSuccessful()
    {
        return $this->getCode() === "0";
    }

    public function getTransactionReference()
    {
        if (isset($this->data->LookupResponse) && isset($this->data->LookupResponse->PaystationTransactionID)) {
            return (string)$this->data->LookupResponse->PaystationTransactionID;
        }
    }

    public function getCode()
    {
        if (isset($this->data->LookupResponse->PaystationErrorCode)) {
            return (string)$this->data->LookupResponse->PaystationErrorCode;
        }
    }

    public function getMessage()
    {
        if (isset($this->data->LookupResponse->PaystationErrorMessage)) {
            return (string)$this->data->LookupResponse->PaystationErrorMessage;
        }
        if (isset($this->data->LookupStatus->LookupMessage)) {
            return (string)$this->data->LookupStatus->LookupMessage;
        }
    }
}
