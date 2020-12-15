<?php

namespace Omnipay\Paystation\Message;

/**
 * Provide additional information to look up card fields on a response object
 */
trait HasCardFields
{
    /** @var string|null  The property of $this->data to look up response fields by, or null if directly on $this->data */
    protected $lookupField;

    abstract public function isSuccessful();

    public function getCardNumber()
    {
        return $this->getResponseField('CardNo');
    }

    public function getCardExpiryYear()
    {
        $expiry = $this->convertExpiryDate($this->getResponseField('CardExpiry'));
        return $expiry[0];
    }

    public function getCardExpiryMonth()
    {
        $expiry = $this->convertExpiryDate($this->getResponseField('CardExpiry'));
        return $expiry[1];
    }

    public function getCardholderName()
    {
        return $this->getResponseField('CardholderName');
    }

    public function getCardType()
    {
        $type = $this->getResponseField('CardType');
        if ($type === null) {
            $type = $this->getResponseField('Cardtype');
        }
        return $type;
    }

    protected function getResponseField($key)
    {
        if (!$this->isSuccessful()) {
            return null;
        }
        $lookup = $this->lookupField === null ? $this->data : $this->data->{$this->lookupField};
        return isset($lookup->$key) ? (string) $lookup->$key : null;
    }

    protected function convertExpiryDate($yymm)
    {
        if (preg_match('/([0-9]{2})([0-9]{2})/', $yymm, $match)) {
            return array((int) $match[1], (int) $match[2]);
        } else {
            return array(null, null);
        }
    }
}
