magento-oauth
=============

A service class for Magento OAuth using the [Lusitanian PHP OAuth](https://github.com/Lusitanian/PHPoAuthLib) library.

[![Total Downloads](https://poser.pugx.org/jonnyw/magento-oauth/downloads.png)](https://packagist.org/packages/jonnyw/magento-oauth) [![Latest Stable Version](https://poser.pugx.org/jonnyw/magento-oauth/v/stable.png)](https://packagist.org/packages/jonnyw/magento-oauth) [![Build Status](https://travis-ci.org/jonnnnyw/magento-oauth.png?branch=master)](https://travis-ci.org/jonnnnyw/magento-oauth) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/jonnnnyw/magento-oauth/badges/quality-score.png?s=0441e6221b048c5d83e2ab19dfe74456fabc0774)](https://scrutinizer-ci.com/g/jonnnnyw/magento-oauth/) [![Code Coverage](https://scrutinizer-ci.com/g/jonnnnyw/magento-oauth/badges/coverage.png?s=948ef83491cc08e6b57fc980a5232f85917b6e82)](https://scrutinizer-ci.com/g/jonnnnyw/magento-oauth/)

0.0 Table of Contents
---------------------

* Examples
* Changelog
* Troubleshooting


1.0 Examples
------------

A working example can be found in the examples/ directory of the repo.

You can create your own instance of the Magento service or use the service factory in the Lusitanian OAuth library, which ensures all dependencies are injected into the service:

```php
<?php

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\UriFactory;
use OAuth\ServiceFactory;

$applicationUrl     = 'http://magento.local';
$consumerKey        = 'd19e5e1ce0a8298a32fafc2d1d50227b';
$consumerSecret     = '7c230aba0da67e2ab462f88e6e83ee39';

$storage        = new Session();
$uriFactory     = new UriFactory();

$serviceFactory = new ServiceFactory();
$serviceFactory->registerService('magento', 'JonnyW\MagentoOAuth\OAuth1\Service\Magento');

$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
$currentUri->setQuery('');

$baseUri = $uriFactory->createFromAbsolute($applicationUrl);

$credentials = new Credentials(
    $consumerKey,
    $consumerSecret,
    $currentUri->getAbsoluteUri()
);

$magentoService = $serviceFactory->createService('magento', $credentials, $storage, array(), $baseUri);
```

By default the service class authorizes users in the admin scope. To authorize customers simply set the authorization endpoint on the Magento service after instantiating it:

```php
<?php

use JonnyW\MagentoOAuth\OAuth1\Service\Magento;

$magentoService->setAuthorizationEndpoint(Magento::AUTHORIZATION_ENDPOINT_CUSTOMER);
```

2.0 Changelog
------------


3.0 Troubleshooting
------------

If you receive a 'Server can not understand Accept HTTP header media type' error message when making API requests through the service then you may need to add an 'Accept' header to the request:

```php
$result = $magentoService->request('/api/rest/customers', 'GET', null, array('Accept' => '*/*'));
```