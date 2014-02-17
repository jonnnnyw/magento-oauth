magento-oauth
=============

A service class for Magento OAuth using the Lusitanian PHP OAuth library.


0.0 Table of Contents
---------------------

* Examples
* Changelog


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


4.0 Troubleshooting
------------

If you are using V1.0.0 then the examples above won't work for you. It is reccommend that you upgrade to the latest version.

Look at the response class (JonnyW\PhantomJs\Response) to see what data you have access to.

An explanation of the errors that are thrown by the client:

### CommandFailedException

The command sent to the PhantomJS executable failed. This should be very rare and is probably my fault if this happens (sorry).

### InvalidUrlException

The URL you are providing is an invalid format. It is very loose verification.

### InvalidMethodException

The request method you are providing is invalid.

### NoPhantomJsException

The PhantomJS executable cannot be found or it is not executable. Check the path and permissions.

### NotWriteableException

The screen capture location you provided or your /tmp folder are not writeable. The /tmp folder is used to temporarily write the scripts that PhantomJS executes. They are deleted after execution or on failure.