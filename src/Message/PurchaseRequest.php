<?php

namespace Omnipay\Paystation\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Paystation Purchase Request
 *
 * Documentation:
 * @link http://www.paystation.co.nz/cms_show_download.php?id=41
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

	protected function getBaseData()
	{
		$data = array();
		$data['paystation'] = '_empty';
		$data['pstn_pi'] = $this->getPaystationId();
		$data['pstn_gi'] = $this->getGatewayId();
		$merchantSession = $this->getMerchantSession();
		if(!$merchantSession){
			$merchantSession = uniqid();
		}
		$data['pstn_ms'] = $merchantSession;

		return $data;
	}

	public function getData()
	{
		$this->validate('amount', 'card', 'paystationId', 'gatewayId');
		//required
		$data = $this->getBaseData();
		$data['pstn_am'] = $this->getAmountInteger();
		//optional
		$data['pstn_cu'] = $this->getCurrency();
		$data['pstn_tm'] = $this->getTestMode() ? 'T' : null;
		$data['pstn_mc'] = $this->getCustomerDetails();

		return $data;
	}

	protected function getCustomerDetails()
	{
		$card = $this->getCard();
		return substr(implode(array_filter(array(
			$card->getName(),
			$card->getCompany(),
			$card->getAddress1(),
			$card->getAddress2(),
			$card->getCity(),
			$card->getState(),
			$card->getCountry(),
			$card->getPhone(),
			$card->getEmail(),

		)),","), 0, 255);
	}

	public function send()
	{
		$httpResponse = $this->httpClient->post($this->endpoint, null, $this->getData())->send();
		return $this->response = new PurchaseResponse($this, $httpResponse->getBody());
	}

}
