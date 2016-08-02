<?php

error_reporting(E_ALL | E_STRICT);
// Set the default timezone
date_default_timezone_set("Pacific/Auckland");

// include the composer autoloader
$autoloader = require __DIR__.'/../vendor/autoload.php';

// autoload abstract TestCase classes in test directory
$autoloader->add('Omnipay', __DIR__.'/../vendor/omnipay/omnipay/tests');

