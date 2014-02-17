<?php

/*
 * This file is part of the Magento OAuth package
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\UriFactory;
use OAuth\ServiceFactory;
use JonnyW\MagentoOAuth\OAuth1\Service\Magento;

/**
 * Autoload
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Consumer credentials
 */
$applicationUrl     = 'http://magento.local';
$consumerKey        = 'd19e5e1ce0a8298a32fafc2d1d50227b';
$consumerSecret     = '7c230aba0da67e2ab462f88e6e83ee39';

/**
 * Setup service
 */
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
$magentoService->setAuthorizationEndpoint(Magento::AUTHORIZATION_ENDPOINT_ADMIN);

/**
 * OAuth logic
 */

// +++++++++++++++++++++++++ //
// AUTHENTICATION CANCELLED  //
// +++++++++++++++++++++++++ //
if(isset($_GET['rejected'])) {
     echo '<p>OAuth authentication was cancelled.</p>';
}

// +++++++++++++++++++++++++ //
// AUTHENTICATE WITH MAGENTO //
// +++++++++++++++++++++++++ //
elseif(isset($_GET['authenticate'])) {

    // extra request needed for oauth1 to request a request token :-)
    $token     = $magentoService->requestRequestToken();
    $url     = $magentoService->getAuthorizationUri(array('oauth_token' => $token->getRequestToken()));
    
    header('Location: ' . $url);
} 

// +++++++++++++++++++++++++++++++++++++ //
// GET ACCESS TOKEN AFTER AUTHENTICATION //
// +++++++++++++++++++++++++++++++++++++ //
elseif(!empty($_GET['oauth_token'])) {

    $token = $storage->retrieveAccessToken('Magento');

    // This was a callback request from twitter, get the token
    $magentoService->requestAccessToken(
        $_GET['oauth_token'],
        $_GET['oauth_verifier'],
        $token->getRequestTokenSecret()
    );

    // Send a request now that we have access token
    $result = $magentoService->request('/api/rest/customers', 'GET', null, array('Accept' => '*/*'));

    echo 'result: <pre>' . print_r(json_decode($result), true) . '</pre>';
} 

// +++++++ //
// DEFAULT //
// +++++++ //
else {

    $url = $currentUri->getRelativeUri() . '?authenticate=true';
    
    echo '<a href="' . $url . '" title="Login with Twitter">Login with Twitter!</a>';
}