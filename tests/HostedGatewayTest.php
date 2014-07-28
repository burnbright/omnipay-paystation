<?php
namespace Omnipay\Paystation;

use Omnipay\GatewayTestCase;
use Omnipay\Common\CreditCard;

class HostedGatewayTest extends GatewayTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new HostedGateway($this->getHttpClient(), $this->getHttpRequest());
    }

    //see phpunit output
	
	
}