<?php
namespace Omnipay\Paystation;

use Omnipay\Tests\GatewayTestCase;
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

    public function testSetup() {
        $this->gateway->setHmacKey('abc');

        $this->assertEquals('Paystation', $this->gateway->getName());
        $this->assertEquals('500600', $this->gateway->getPaystationId());
        $this->assertEquals('FOOBAR', $this->gateway->getGatewayId());
        $this->assertEquals('abc', $this->gateway->getHmacKey());
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

        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('023523354-01', $response->getTransactionReference());
        $this->assertEquals(null, $response->getMessage());
        $this->assertEquals(null, $response->getCode());

        $this->assertEquals('GET', $response->getRedirectMethod());
        $this->assertEquals(
            "https://payments.paystation.co.nz/hosted/?hk=uxFYtGKLzlC2aRfFbrfGaefFlDkr14GdoJPw43QetY",
            $response->getRedirectURL()
        );
        $this->assertEquals(null, $response->getRedirectData());
    }

    public function testPurchaseFailure()
    {
        $this->setMockHttpResponse('PurchaseRequestFailure.txt');

        $response = $this->gateway->purchase(array(
            'amount' => '0.00',
            'currency' => 'NZD',
            'card' => $this->getValidCard(),
            'merchantSession' => '12345678'
        ))->send();
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals(
            'The amount specified is too high or low and exceeds the limits set by this merchant',
            $response->getMessage()
        );
        $this->assertEquals('10', $response->getCode());
        $this->assertEquals(null, $response->getTransactionReference());
        $this->assertEquals(null, $response->getRedirectURL());
    }

    public function testPurchaseBadHmac()
    {
        $this->gateway->setHmacKey('abc');

        $this->setMockHttpResponse('PurchaseRequestBadHmac.txt');

        $request = $this->gateway->purchase(array(
            'amount' => '1.00',
            'currency' => 'NZD',
            'card' => $this->getValidCard(),
            'merchantSession' => '',              // tests uniqid() merchantSession assignment
            'returnUrl' => 'http://example.com'   // tests $data['pstn_du'] assignment
        ));
        $this->assertEquals('abc', $request->getHmacKey());
        $this->assertEquals('http://example.com', $request->getReturnUrl());

        $response = $request->send();
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('HMAC validation failed', $response->getMessage());
        $this->assertEquals('160', $response->getCode());
    }
    
    public function testPurchaseNegative()
    {
        $this->setMockHttpResponse('PurchaseRequestFailure.txt');

        $this->setExpectedException('Omnipay\Common\Exception\InvalidRequestException');
        $response = $this->gateway->purchase(array(
            'amount' => '-12345.00',
            'currency' => 'NZD',
            'card' => $this->getValidCard(),
            'merchantSession' => '12345678'
        ))->send();
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('10', $response->getCode());
    }

    public function testPurchaseInvalid()
    {
        $this->setMockHttpResponse('PurchaseResponseInvalid.txt');

        $this->setExpectedException('Omnipay\Common\Exception\InvalidResponseException');
        $response = $this->gateway->purchase(array(
            'amount' => '12345.00',
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
        $this->assertFalse($response->isPending());
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals("00", $response->getCode());
        $this->assertEquals("Transaction successful", $response->getMessage());
        $this->assertEquals("1212123241-01", $response->getTransactionReference());
        $this->assertEquals('512345XXXXXXX346', $response->getCardNumber());
        $this->assertEquals('17', $response->getCardExpiryYear());
        $this->assertEquals('05', $response->getCardExpiryMonth());
        $this->assertEquals('TIM TOOLMAN', $response->getCardholderName());
        $this->assertEquals('MC', $response->getCardType());
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
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals("4", $response->getCode());
        $this->assertEquals("Expired Card", $response->getMessage());
        $this->assertEquals("0040852604-01", $response->getTransactionReference());
    }
    
    public function testCompletePurchaseInvalid()
    {
        $this->getHttpRequest()
            ->query->replace(
                array(
                    'ti' => '9999999999-99',
                    'ec' => '0',
                    'am' => '1000'
                )
            );
        $this->setMockHttpResponse('CompletePurchaseRequestInvalid.txt');

        $this->setExpectedException(
            'Omnipay\Common\Exception\InvalidResponseException',
            'The transaction could not be found on the payment servers.'
        );
        $response = $this->gateway->completePurchase()->send();
    }

    public function testCompletePurchaseMalformed()
    {
        $this->getHttpRequest()
            ->query->replace(
                array(
                    'ti' => '9999999999-99',
                    'ec' => '0',
                    'am' => '1000'
                )
            );
        $this->setMockHttpResponse('CompletePurchaseRequestMalformed.txt');

        $response = $this->gateway->completePurchase()->send();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(null, $response->getCode());
        $this->assertEquals('Successful', $response->getMessage());
        $this->assertEquals(null, $response->getTransactionReference());
    }

    public function testCompletePurchaseVeryMalformed()
    {
        $this->getHttpRequest()
            ->query->replace(
                array(
                    'ti' => '9999999999-99',
                    'ec' => '0',
                    'am' => '1000'
                )
            );
        $this->setMockHttpResponse('CompletePurchaseRequestVeryMalformed.txt');

        $response = $this->gateway->completePurchase()->send();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(null, $response->getCode());
        $this->assertEquals(null, $response->getMessage());
        $this->assertEquals(null, $response->getTransactionReference());
        $this->assertEquals(null, $response->getCardExpiryYear());
    }

    public function testCompletePurchaseCompletelyInvalid()
    {
        $this->getHttpRequest()
            ->query->replace(
                array(
                    'ti' => '9999999999-99',
                    'ec' => '0',
                    'am' => '1000'
                )
            );
        $this->setMockHttpResponse('PurchaseRequestInvalid.txt');

        $this->setExpectedException(
            'Omnipay\Common\Exception\InvalidResponseException',
            'Invalid response from payment gateway' // default message
        );
        $response = $this->gateway->completePurchase()->send();
    }

    public function testCompletePurchaseMissingTransactionReference()
    {
        $this->getHttpRequest()
            ->query->replace(
                array(
                    'ti' => '',
                    'ec' => '0',
                    'am' => '1000'
                )
            );
        $this->setMockHttpResponse('CompletePurchaseRequestInvalid.txt');

        $this->setExpectedException('Omnipay\Common\Exception\InvalidRequestException');
        $response = $this->gateway->completePurchase()->send();
    }
}
