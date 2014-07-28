<?php

namespace Omnipay\Paystation\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Paystation Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{

	protected $endpoint = 'https://www.paystation.co.nz/direct/paystation.dll';

	public function getPaystationId()
	{
		return $this->getParameter('paystationId');
	}

	public function setPaystationId($value)
	{
		return $this->setParameter('paystationId', $value);
	}

	public function getGatewayId()
	{
		return $this->getParameter('gatewayId');
	}

	public function setGatewayId($value)
	{
		return $this->setParameter('gatewayId', $value);
	}

	public function getMerchantSession()
	{
		return $this->getParameter('merchantSession');
	}

	public function setMerchantSession($value)
	{
		return $this->setParameter('merchantSession', $value);
	}

	public function getTestMode()
	{
		return $this->getParameter('testMode');
	}

	public function setTestMode($value)
	{
		return $this->setParameter('testMode', $value);
	}

	/*
	public function get()
	{
		return $this->getParameter('');
	}

	public function set($value)
	{
		return $this->setParameter('', $value);
	}
	*/

	protected function getBaseData()
    {
        $data = array();
        $data['paystation'] = '_empty';
        $data['pstn_pi'] = $this->getPaystationId();
        $data['pstn_gi'] = $this->getGatewayId();
        $data['pstn_ms'] = $this->getMerchantSession();

        return $data;
    }

	public function getData()
    {
        $this->validate('amount', 'card');
        $this->getCard()->validate();

        //required
        $data = $this->getBaseData();
        $data['pstn_af'] = 'dollars.cents';
        $data['pstn_am'] = $this->getAmount();

 		//optional
        //$data['pstn_cu'] = $this->getCurrency();
        $data['pstn_tm'] = $this->getTestMode();
        //$data['pstn_mr'] = $this->getMerchantReference();
        //$data['pstn_ct'] = $this->getCardType();
        //$data['pstn_mc'] = $this->getCustomerDetails();
        //$data['pstn_mo'] = $this->getOrderDetails();

        return $data;
    }

    public function send()
    {
        $httpResponse = $this->httpClient->post($this->endpoint, null, $this->getData())->send();

        return $this->response = new Response($this, $httpResponse->getBody());
    }

}