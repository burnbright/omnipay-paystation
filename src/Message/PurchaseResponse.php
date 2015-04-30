<?php

namespace Omnipay\Paystation\Message;

use DOMDocument;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Paystation Response
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{

    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;

        $responseDom = new DOMDocument;
        $responseDom->loadXML($data);
        $this->data = simplexml_import_dom($responseDom);

        if (!isset($this->data->PaystationTransactionID)) {
            throw new InvalidResponseException;
        }
    }

    public function isPending()
    {
        return false;
    }

    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return isset($this->data->DigitalOrder);
    }

    public function getTransactionReference()
    {
        return isset($this->data->PaystationTransactionID) ? (string)$this->data->PaystationTransactionID : null;
    }

    public function getMessage()
    {
        return isset($this->data->em) ? (string)$this->data->em : null;
    }

    public function getCode()
    {
        return isset($this->data->ec) ? (string)$this->data->ec : null;
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            return (string)$this->data->DigitalOrder;
        }
    }

    public function getRedirectData()
    {
        return null;
    }
}
