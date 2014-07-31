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

        $this->gateway->setPaystationId('500600');
        $this->gateway->setGatewayId('FOOBAR');
    }

    public function testPurchaseSuccess()
    {
        $this->setMockHttpResponse('PurchaseRequestSuccess.txt');

        $response = $this->gateway->purchase(array(
            'amount' => '10.00',
            'currency' => 'NZD',
            'card' => $this->getValidCard(),
            'merchantSession' => '12345678'
        ))->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('023523354-01', $response->getTransactionReference());

        $this->assertEquals(
            "https://payments.paystation.co.nz/hosted/?hk=uxFYtGKLzlC2aRfFbrfGaefFlDkr14GdoJPw43QetY",
            $response->getRedirectURL()
        );
    }

    public function testPurchaseFailure()
    {
        $this->setMockHttpResponse('PurchaseRequestFailure.txt');

        $response = $this->gateway->purchase(array(
            'amount' => '-12345.00',
            'currency' => 'NZD',
            'card' => $this->getValidCard(),
            'merchantSession' => '12345678'
        ))->send();
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('10', $response->getCode());
    }

    public function testPurchaseInvalid()
    {
        $this->setMockHttpResponse('InvalidResponse.txt');

        $this->setExpectedException('Omnipay\Common\Exception\InvalidResponseException');
        $response = $this->gateway->purchase(array(
            'amount' => '-12345.00',
            'currency' => 'NZD',
            'card' => $this->getValidCard(),
            'merchantSession' => '12345678'
        ))->send();
    }
    
    public function testCompletePurchaseSuccess()
    {
        $this->getHttpRequest()
            ->query->replace(
                array(
                    'ti' => '1212123241-01',
                    'ec' => '0',
                    'am' => '1000'
                )
            );
        $this->setMockHttpResponse('CompletePurchaseRequestSuccess.txt');
        $response = $this->gateway->completePurchase()->send();

        //reponse assertions
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals("00", $response->getCode());
        $this->assertEquals("Transaction successful", $response->getMessage());
        $this->assertEquals("1212123241-01", $response->getTransactionReference());
    }

    public function testCompletePurchaseFailure()
    {
        $this->getHttpRequest()
            ->query->replace(
                array(
                    'ti' => '0040852604-01',
                    'ec' => '4',
                    'em' => 'Expired Card',
                    'ms' => '53d839c4e0d89',
                    'am' => '1054'
                )
            );
        $this->setMockHttpResponse('CompletePurchaseRequestFailure.txt');
        $response = $this->gateway->completePurchase()->send();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals("4", $response->getCode());
        $this->assertEquals("Expired Card", $response->getMessage());
        $this->assertEquals("0040852604-01", $response->getTransactionReference());
    }
    
}
