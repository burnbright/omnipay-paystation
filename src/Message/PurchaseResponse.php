<?php

namespace Omnipay\Paystation\Message;

use DOMDocument;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Paystation Response
 */
class PurchaseResponse extends AbstractResponse
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

	public function isSuccessful()
	{
		return isset($this->data->PaystationTransactionID) && !isset($this->data->ec);
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

	public function getRedirectUrl()
	{
		if ($this->isRedirect()) {
			return (string)$this->data->DigitalOrder;
		}
	}

}
