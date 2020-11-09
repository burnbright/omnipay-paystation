# Omnipay: Paystation

**Paystation driver for the Omnipay PHP payment processing library**

http://www.paystation.co.nz

API Docs: https://docs.paystation.co.nz/

[![Build Status](https://travis-ci.org/burnbright/omnipay-paystation.png?branch=master)](https://travis-ci.org/burnbright/omnipay-paystation)
[![Latest Stable Version](https://poser.pugx.org/burnbright/omnipay-paystation/version.png)](https://packagist.org/packages/burnbright/omnipay-paystation)
[![Total Downloads](https://poser.pugx.org/burnbright/omnipay-paystation/d/total.png)](https://packagist.org/packages/burnbright/omnipay-paystation)

[Omnipay](https://github.com/omnipay/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.6+. This package implements Paystation support for Omnipay.

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

```json
{
    "require": {
        "burnbright/omnipay-paystation": "~3.0"
    }
}
```

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

## Basic Usage

The following gateways are provided by this package:

* Paystation_Hosted

For general omnipay usage instructions, please see the main [Omnipay](https://github.com/omnipay/omnipay)
repository.

**NOTE**: Paystation's back-end system supports both [IP whitelisting](https://en.wikipedia.org/wiki/Whitelisting) and [HMAC key](https://en.wikipedia.org/wiki/HMAC) usage. Be aware of which features are enabled on the account you're using.

Testing card details, and error cent values, are detailed here: http://www.paystation.co.nz/Paystation-Test-Site

If you want to use dynamic return urls, you must set a HMAC key. This can be obtained from paystation.

```
	$gateway->setHmacKey('1a2b3b3g3g3k3k23k23hj235h235');
```

## Merchant Session Uniqueness

The required merchant session identifier is generated using php's
[uniqid](http://php.net/manual/en/function.uniqid.php) function.
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

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/burnbright/omnipay-paystation/issues), or better yet, fork the library and submit a pull request.
