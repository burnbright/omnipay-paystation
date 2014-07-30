# Omnipay: Paystation

http://www.paystation.co.nz

## Author

Jeremy Shipman (jeremy@burnbright.net)

## About

Don't forget about the testing card details, and error cent values, as detailed here: http://www.paystation.co.nz/Paystation-Test-Site

If you want to use dynamic return urls, you must set a HMAC key. This can be obtained from paystation.

```
	$gateway->setHmacKey('1a2b3b3g3g3k3k23k23hj235h235');
```

## Merchant Session Uniqueness

The required merchant session identifier is generated using php's
[uniqueid](http://php.net/manual/en/function.uniqid.php) function.
This may not be enough uniqueness if your system architecture has
multiple hosts. You can override this by setting the `merchantSession`
omnipay parameter:

```php
	$response = $gateway->purchase(array(
		'amount' => '123.00',
		'currency' => 'NZD',
		'card' => array(...),
		'merchantSession' => uniqueid($hostidentifier) //here
	))->send();

```

## TODO

Implement further paystation features:

 * server POST back handling
 * dynamic return urls
 * on-site gateways
 * authorize/capture
 * tokenisation

 * extra parameter configuration
 	* select currencies
 	* card type
 	* customer details
 	* order details
