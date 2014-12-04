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
            throw new InvalidResponseException;
        }
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
    public function getCardNumber()      { return $this->get_response_field('CardNo'); }
    public function getCardExpiryYear()  { return $this->convert_expiry_date($this->get_response_field('CardExpiry'))[0]; }
    public function getCardExpiryMonth() { return $this->convert_expiry_date($this->get_response_field('CardExpiry'))[1]; }
    public function getCardholderName()  { return $this->get_response_field('CardholderName'); }
    public function getCardType()        { return $this->get_response_field('CardType'); }
    
    private function get_response_field($key) {
        return $this->isSuccessful() && isset($this->data->LookupResponse->$key)
            ? (string) $this->data->LookupResponse->$key
            : null;
    }
    
    private function convert_expiry_date($yymm) {
        if (preg_match('/([0-9]{2})([0-9]{2})/', $yymm, $match))
            return [(int) $match[1], (int) $match[2]];
        else
            return [null, null];
    }
}
