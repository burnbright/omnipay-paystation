<?php

namespace Omnipay\Paystation\Message;

use DOMDocument;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Accept an incoming notification (a webhook)
 *
 * @see https://docs.paystation.co.nz/#post-response
 */
class WebhookNotification extends AbstractRequest implements NotificationInterface, ResponseInterface
{
    /**
     * The data contained in the response.
     *
     * @var mixed
     */
    protected $data;

    /**
     * @inheritDoc
     */
    public function __construct(ClientInterface $httpClient, HttpRequest $httpRequest)
    {
        parent::__construct($httpClient, $httpRequest);
        // fetch POST stream directly
        $responseDom = new DOMDocument();
        $parsed = $responseDom->loadXML($httpRequest->getContent(), LIBXML_NOWARNING | LIBXML_NOERROR);
        if ($parsed === false) {
            $error = libxml_get_last_error();
            throw new InvalidRequestException($error->message);
        }
        $this->data = simplexml_import_dom($responseDom);
    }

    /**
     * ResponseInterface implemented so that we can return self here for any legacy support that uses send()
     */
    public function sendData($data)
    {
        return $this;
    }

    /**
     * Get the authorisation code if available.
     *
     * @return null|string
     */
    public function getTransactionReference()
    {
        return isset($this->data->TransactionID) ? (string)$this->data->TransactionID : null;
    }

    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {@link NotificationInterface::STATUS_COMPLETED},
     * {@link NotificationInterface::STATUS_PENDING}, or {@link NotificationInterface::STATUS_FAILED}.
     */
    public function getTransactionStatus()
    {
        if (!isset($this->data->PaystationErrorCode)) {
            return NotificationInterface::STATUS_FAILED;
        }
        if ((string)$this->data->PaystationErrorCode == '0') {
            return NotificationInterface::STATUS_COMPLETED;
        }
        if (isset($this->data->Authentication->auth_Status)
            && (string)$this->data->Authentication->auth_Status == 'P'
        ) {
            return NotificationInterface::STATUS_PENDING;
        }

        // last resort, assume failure
        return NotificationInterface::STATUS_FAILED;
    }

    /**
     * Get the merchant response message if available.
     *
     * Note: POST response doesn't include a <PaystationErrorMessage> node
     *
     * @return null|string
     */
    public function getMessage()
    {
        return null;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the original request which generated this response
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this;
    }

    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->getTransactionStatus() == NotificationInterface::STATUS_COMPLETED;
    }

    /**
     * Does the response require a redirect?
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return false;
    }

    /**
     * Is the transaction cancelled by the user?
     *
     * @return boolean
     */
    public function isCancelled()
    {
        return isset($this->data->Authentication->auth_Status)
            && (string)$this->data->Authentication->auth_Status == 'C';
    }

    /**
     * Response code
     *
     * @return null|string A response code from the payment gateway
     */
    public function getCode()
    {
        return isset($this->data->PaystationErrorCode) ? (string)$this->data->PaystationErrorCode : null;
    }

    /**
     * Get the card type if available.
     *
     * @return null|string
     */
    public function getCardType()
    {
        return isset($this->data->Cardtype) ? (string)$this->data->Cardtype : null;
    }
}
