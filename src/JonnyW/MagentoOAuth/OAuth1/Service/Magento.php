<?php

namespace JonnyW\MagentoOAuth\OAuth1\Service;

use OAuth\OAuth1\Service\AbstractService;
use OAuth\OAuth1\Signature\SignatureInterface;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\OAuth1\Token\TokenInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Exception\Exception;

class Magento extends AbstractService
{
    const REQUEST_TOKEN_ENDPOINT             = '/oauth/initiate';
    const ACCESS_TOKEN_ENDPOINT              = '/oauth/token';
    const AUTHORIZATION_ENDPOINT_CUSTOMER    = '/oauth/authorize';
    const AUTHORIZATION_ENDPOINT_ADMIN       = '/admin/oAuth_authorize';

    /**
     * Authorization endpoint
     *
     * @var string
     */
    protected $authorizationEndpoint;

    /**
     * Internal constructor
     *
     * @param  CredentialsInterface  $credentials
     * @param  ClientInterface       $httpClient
     * @param  TokenStorageInterface $storage
     * @param  SignatureInterface    $signature
     * @param  UriInterface          $baseApiUri
     * @return void
     */
    public function __construct(CredentialsInterface $credentials, ClientInterface $httpClient, TokenStorageInterface $storage, SignatureInterface $signature, UriInterface $baseApiUri = null)
    {
        parent::__construct($credentials, $httpClient, $storage, $signature, $baseApiUri);

        $this->setAuthorizationEndpoint(self::AUTHORIZATION_ENDPOINT_ADMIN);

        if (null === $baseApiUri) {
            throw new Exception('Base URI must be set.');
        }
    }

    /**
     * Get request token endpoint
     *
     * @return UriInterface
     */
    public function getRequestTokenEndpoint()
    {
        $uri = clone $this->baseApiUri;
        $uri->setPath(self::REQUEST_TOKEN_ENDPOINT);

        return $uri;
    }

    /**
     * Set authorization endpoint
     *
     * @param  string $endpoint
     * @return void
     */
    public function setAuthorizationEndpoint($endpoint)
    {
        $validEndpoints = array(
            self::AUTHORIZATION_ENDPOINT_CUSTOMER,
            self::AUTHORIZATION_ENDPOINT_ADMIN
        );

        if (!in_array($endpoint, $validEndpoints)) {
            throw new Exception('Authorization endpoint is invalid.');
        }

        $this->authorizationEndpoint = $endpoint;
    }

    /**
     * Get authorize token endpoint
     *
     * @return UriInterface
     */
    public function getAuthorizationEndpoint()
    {
        $uri = clone $this->baseApiUri;
        $uri->setPath($this->authorizationEndpoint);

        return $uri;
    }

    /**
     * Get access token endpoint
     *
     * @return UriInterface
     */
    public function getAccessTokenEndpoint()
    {
        $uri = clone $this->baseApiUri;
        $uri->setPath(self::ACCESS_TOKEN_ENDPOINT);

        return $uri;
    }

    /**
     * Parse request token response
     *
     * @param  string         $responseBody
     * @return StdOAuth1Token
     */
    protected function parseRequestTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        $this->validateTokenResponse($data);

        if (!isset($data['oauth_callback_confirmed']) || $data['oauth_callback_confirmed'] !== 'true') {
            throw new TokenResponseException('Error in retrieving token.');
        }

        return $this->parseAccessTokenResponse($responseBody);
    }

    /**
     * Parse access token response
     *
     * @param  string         $responseBody
     * @return StdOAuth1Token
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        $this->validateTokenResponse($data);

        $token = new StdOAuth1Token();

        $token->setRequestToken($data['oauth_token']);
        $token->setRequestTokenSecret($data['oauth_token_secret']);
        $token->setAccessToken($data['oauth_token']);
        $token->setAccessTokenSecret($data['oauth_token_secret']);

        $token->setEndOfLife(StdOAuth1Token::EOL_NEVER_EXPIRES);

        unset($data['oauth_token'], $data['oauth_token_secret']);

        $token->setExtraParams($data);

        return $token;
    }

    /**
     * Validate token response
     *
     * @param  array|null $data
     * @return void
     */
    protected function validateTokenResponse($data)
    {
        if (is_null($data) || !is_array($data)) {
            throw new TokenResponseException('Response body is in an invalid format');
        }

        if (isset($data['error'])) {
            throw new TokenResponseException('Error creating token: "' . $data['error'] . '"');
        }

        if (!isset($data['oauth_token'])) {
            throw new TokenResponseException('Error creating token: \'oauth_token\' was not found in response body');
        }

        if (!isset($data['oauth_token_secret'])) {
            throw new TokenResponseException('Error creating token: \'oauth_token_secret\' was not found in response body');
        }
    }

    /**
     * Builds the authorization header for an
     * authenticated API user
     *
     *  - Adds oauth_verifier to auth header for Magento
     *
     * @param string         $method
     * @param UriInterface   $uri
     * @param TokenInterface $token
     * @param array          $bodyParams
     *
     * @return string
     */
    protected function buildAuthorizationHeaderForAPIRequest($method, UriInterface $uri, TokenInterface $token, $bodyParams = null)
    {
        $this->signature->setTokenSecret($token->getAccessTokenSecret());

        $parameters = $this->getBasicAuthorizationHeaderInfo();

        if (isset($parameters['oauth_callback'])) {
            unset($parameters['oauth_callback']);
        }

        if (isset($bodyParams['oauth_verifier'])) {
            $parameters['oauth_verifier'] = $bodyParams['oauth_verifier'];
        }

        $parameters     = array_merge($parameters, array('oauth_token' => $token->getAccessToken()));
        $mergedParams   = array_merge($parameters, is_array($bodyParams) ? $bodyParams : array());

        $parameters['oauth_signature'] = $this->signature->getSignature($uri, $mergedParams, $method);

        $authorizationHeader = array();

        foreach ($parameters as $key => $value) {
            $authorizationHeader[] = sprintf('%1$s="%2$s"', rawurlencode($key), rawurlencode($value));
        }

        return sprintf('OAuth %1$s', implode(', ', $authorizationHeader));
    }
}
