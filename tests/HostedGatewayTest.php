<?php
namespace Omnipay\Paystation;

use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\ServerRequest;
use Omnipay\Tests\GatewayTestCase;
use Omnipay\Common\CreditCard;
use Omnipay\Common\Message\NotificationInterface;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

class HostedGatewayTest extends GatewayTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->gateway = new HostedGateway($this->getHttpClient(), $this->getHttpRequest());

        $this->gateway->setPaystationId('500600');
        $this->gateway->setGatewayId('FOOBAR');
    }

    public function testSetup()
    {
        $this->gateway->setHmacKey('abc');

        $this->assertEquals('Paystation', $this->gateway->getName());
        $this->assertEquals('500600', $this->gateway->getPaystationId());
        $this->assertEquals('FOOBAR', $this->gateway->getGatewayId());
        $this->assertEquals('abc', $this->gateway->getHmacKey());
        $this->assertNull($this->gateway->getTestMode());
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

        $this->gateway->setTestMode(true);

        $request = $this->gateway->purchase(array(
            'amount' => '1.00',
            'currency' => 'NZD',
            'card' => $this->getValidCard(),
            'merchantSession' => '',
            'returnUrl' => 'http://example.com',
            'notifyUrl' => 'http://example.net',
        ));
        $this->assertEquals('abc', $request->getHmacKey());
        $this->assertEquals('http://example.com', $request->getReturnUrl());
        $this->assertEquals('http://example.net', $request->getNotifyUrl());
        $data = $request->getData();
        // tests uniqid() merchantSession assignment
        $this->assertNotEquals('', $data['pstn_ms']);
        $this->assertEquals('T', $data['pstn_tm']);
        $this->assertEquals('http%3A%2F%2Fexample.com', $data['pstn_du']);
        $this->assertEquals('http%3A%2F%2Fexample.net', $data['pstn_dp']);

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

        $this->expectException('Omnipay\Common\Exception\InvalidRequestException');
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

        $this->expectException('Omnipay\Common\Exception\InvalidResponseException');
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

        $this->expectException('Omnipay\Common\Exception\InvalidResponseException');
        $this->expectExceptionMessage('Access denied for user [123456]');
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

        $this->expectException('Omnipay\Common\Exception\InvalidResponseException');
        // default message
        $this->expectExceptionMessage('Invalid response from payment gateway');
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

        $this->expectException('Omnipay\Common\Exception\InvalidRequestException');
        $response = $this->gateway->completePurchase()->send();
    }

    public function testAcceptNotificationSuccess()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationSuccess.txt');
        $gateway = new HostedGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertSame('0000743943-01', $notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_COMPLETED, $notification->getTransactionStatus());
        $this->assertNull($notification->getMessage());

        // ResponseInterface methods
        $response = $notification->send();
        $this->assertSame($notification, $response);
        $this->assertSame($notification, $response->getRequest());
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isCancelled());
        $this->assertSame('0', $response->getCode());

        $this->assertSame('MC', $notification->getCardType());
    }

    public function testAcceptNotificationFailure()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationFailure.txt');
        $gateway = new HostedGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertSame('0000743943-02', $notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
        $this->assertNull($notification->getMessage());

        // ResponseInterface methods
        $response = $notification->send();
        $this->assertFalse($response->isSuccessful());
        $this->assertSame('4', $response->getCode());
    }

    public function testAcceptNotificationPending()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationPending.txt');
        $gateway = new HostedGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertSame('0000743943-03', $notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_PENDING, $notification->getTransactionStatus());
        $this->assertNull($notification->getMessage());
    }

    public function testAcceptNotificationError()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationError.txt');
        $gateway = new HostedGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertSame('', $notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
        $this->assertNull($notification->getMessage());

        // ResponseInterface methods
        $response = $notification->send();
        $this->assertNull($response->getCode());
        
        $this->assertNull($notification->getCardType());
    }

    public function testAcceptNotificationMalfomed()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationMalformed.txt');
        $gateway = new HostedGateway($this->getHttpClient(), $httpRequest);
        $this->expectException('Omnipay\Common\Exception\InvalidRequestException');
        $notification = $gateway->acceptNotification();
    }

    /**
     * Parses a saved raw request file into a new HTTP request object
     *
     * Initial file parsing adapted from TestCase::getMockHttpResponse()
     *
     * @param string $path  The request file
     *
     * @return HttpRequest  The new request
     */
    protected function setMockHttpRequest($path)
    {
        $ref = new ReflectionObject($this);
        $dir = dirname($ref->getFileName());
        // if mock file doesn't exist, check parent directory
        if (file_exists($dir.'/Mock/'.$path)) {
            $raw = file_get_contents($dir.'/Mock/'.$path);
        } elseif (file_exists($dir.'/../Mock/'.$path)) {
            $raw = file_get_contents($dir.'/../Mock/'.$path);
        } else {
            throw new Exception("Cannot open '{$path}'");
        }

        $guzzleRequest = Message::parseRequest($raw);
        // PSR-bridge requires a ServerRequestInterface
        $guzzleServerRequest = new ServerRequest(
            $guzzleRequest->getMethod(),
            $guzzleRequest->getUri(),
            $guzzleRequest->getHeaders(),
            $guzzleRequest->getBody(),
            $guzzleRequest->getProtocolVersion(),
            $_SERVER
        );

        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createRequest($guzzleServerRequest);
    }
}
