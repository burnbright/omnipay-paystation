<?php

namespace Omnipay\Paystation\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Paystation Complete Purchase Request
 *
 * uses quicklookup service, as described here:
 * @link http://www.paystation.co.nz/cms_show_download.php?id=38
 */
class CompletePurchaseRequest extends PurchaseRequest
{

    protected $endpoint = "https://payments.paystation.co.nz/lookup/";

    public function getData()
    {
        $query = $this->httpRequest->query;
        $ti = $query->get('ti'); //transaction reference
        $ec = $query->get('ec'); //error code
        $em = $query->get('em'); //error message
        $ms = $query->get('ms'); //merchant session
        $am = $query->get('am'); //amount
        $futurepaytoken = $query->get('futurepaytoken');
        if (!$ti) {
            throw new InvalidRequestException('Transaction reference is missing');
        }
        $data = array();
        $data['pi'] = $this->getPaystationId();
        $data['ti'] = $ti;
        
        return $data;
    }

    public function sendData($data)
    {
        $postdata = http_build_query($data);
        $httpRequest = $this->httpClient->post($this->getEndPoint($postdata), null, $data);
        $httpResponse = $httpRequest->send();

        return $this->response = new CompletePurchaseResponse($this, $httpResponse->getBody());
    }
}
