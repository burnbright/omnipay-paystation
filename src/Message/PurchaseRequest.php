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

	public function getHmacKey()
	{
		return $this->getParameter('hmacKey');
	}

	public function setHmacKey($value)
	{
		return $this->setParameter('hmacKey', $value);
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
		$this->validate('amount', 'paystationId', 'gatewayId');
		//required
		$data = $this->getBaseData();
		$data['pstn_am'] = $this->getAmountInteger();
		//optional
		$data['pstn_cu'] = $this->getCurrency();
		$data['pstn_tm'] = $this->getTestMode() ? 'T' : null;
		$data['pstn_mc'] = $this->getCustomerDetails();
		if($this->getHmacKey() && $this->getReturnUrl()){
			$data['pstn_du'] = urlencode($this->getReturnUrl());
		}

		return $data;
	}

	public function send()
	{
		$postdata = http_build_query($this->getData());
		$httpRequest = $this->httpClient->post($this->getEndPoint($postdata), null, $postdata);
		$httpResponse = $httpRequest->send();

		return $this->response = new PurchaseResponse($this, $httpResponse->getBody());
	}

	/**
	 * Package up, and limit the customer details.
	 * @return string customer details
	 */
	protected function getCustomerDetails()
	{
		$card = $this->getCard();
		return substr(implode(array_filter(array(
			$card->getName(),
			$card->getCompany(),
			$card->getEmail(),
			$card->getPhone(),
			$card->getAddress1(),
			$card->getAddress2(),
			$card->getCity(),
			$card->getState(),
			$card->getCountry()
		)),","), 0, 255);
	}

	/**
	 * Get the endpoint for this request.
	 * Will include hmac data in GET query, if necessary.
	 * @return string endpoint url
	 */
	protected function getEndPoint($postdata){
		$url = $this->endpoint;
		if($this->getHmacKey()){
			$qd = array();
			$timestamp = time();
			$qd['pstn_HMACTimestamp'] = $timestamp;
			$qd['pstn_HMAC'] = $this->getHmac($timestamp, $postdata);
			$url .= '?'.http_build_query($qd);
		}

		return $url;
	}

	/**
	 * Generate the hmac hash to be passed in endpoint url
	 *
	 * Code modified from
	 * @link http://www.paystation.co.nz/cms_show_download.php?id=69
	 * @return string hmac
	 */
	protected function getHmac($timestamp, $postdata){
		$authenticationKey = $this->getHmacKey();
		$hmacWebserviceName = 'paystation'; //webservice identification. 
		$hmacBody = pack('a*',$timestamp).pack('a*',$hmacWebserviceName).pack('a*',$postdata);
		$hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);

		return $hmacHash;
	}

}
