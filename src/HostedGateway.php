<?php

namespace Omnipay\Paystation;

use Omnipay\Common\AbstractGateway;

class HostedGateway extends AbstractGateway
{
    
    public function getName()
    {
        return 'Paystation';
    }

    public function getDefaultParameters()
    {
        return array(
            'paystationId' => '',
            'gatewayId' => ''
        );
    }

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

    public function getHmacKey()
    {
        return $this->getParameter('hmacKey');
    }

    public function setHmacKey($value)
    {
        return $this->setParameter('hmacKey', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Paystation\Message\PurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Paystation\Message\CompletePurchaseRequest', $parameters);
    }
}
