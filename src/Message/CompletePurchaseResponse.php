<?php

namespace Omnipay\Paystation\Message;

use DOMDocument;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Paystation Complete Purchase Response
 */
class CompletePurchaseResponse extends AbstractResponse
{

    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;

        $responseDom = new DOMDocument;
        $responseDom->loadXML($data);
        $this->data = simplexml_import_dom($responseDom);

        if (!isset($this->data->LookupResponse)) {
            if (isset($this->data->response_error)) {
                throw new InvalidResponseException($this->data->response_error);
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

    // additional information fields
    public function getCardNumber()
    {
        return $this->getResponseField('CardNo');
    }

    public function getCardExpiryYear()
    {
        $expiry = $this->convertExpiryDate($this->getResponseField('CardExpiry'));
        return $expiry[0];
    }

    public function getCardExpiryMonth()
    {
        $expiry = $this->convertExpiryDate($this->getResponseField('CardExpiry'));
        return $expiry[1];
    }

    public function getCardholderName()
    {
        return $this->getResponseField('CardholderName');
    }

    public function getCardType()
    {
        return $this->getResponseField('CardType');
    }

    private function getResponseField($key)
    {
        return $this->isSuccessful() && isset($this->data->LookupResponse->$key)
            ? (string) $this->data->LookupResponse->$key
            : null;
    }

    private function convertExpiryDate($yymm)
    {
        if (preg_match('/([0-9]{2})([0-9]{2})/', $yymm, $match)) {
            return array((int) $match[1], (int) $match[2]);
        } else {
            return array(null, null);
        }
    }
}
