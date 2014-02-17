<?php

namespace JonnyW\MagentoOAuth\OAuth1\Service;

use OAuth\OAuth1\Service\AbstractService;
use OAuth\OAuth1\Signature\SignatureInterface;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Exception\Exception;

class Magento extends AbstractService
{
	/**
	 * Internal constructor
	 *
	 * @param OAuth\Common\Consumer\CredentialsInterface $credentials
	 * @param OAuth\Common\Http\Client\ClientInterface $httpClient
	 * @param OAuth\Common\Storage\TokenStorageInterface $storage
	 * @param OAuth\OAuth1\Signature\SignatureInterface $signature
	 * @param OAuth\Common\Http\Uri\UriInterface $baseApiUri
	 * @return void
	 */
    public function __construct(CredentialsInterface $credentials, ClientInterface $httpClient, TokenStorageInterface $storage, SignatureInterface $signature, UriInterface $baseApiUri = null) 
    {    
        parent::__construct($credentials, $httpClient, $storage, $signature, $baseApiUri);

        if(null === $baseApiUri) {
            throw new Exception('Base URI must be set.');
        }
    }

    /**
     * Get request token endpoint
     * 
     * @return OAuth\Common\Http\Uri\Uri
     */
    public function getRequestTokenEndpoint()
    {
    	$uri = clone $this->baseApiUri;
    	$uri->setPath('oauth/initiate');
    	
    	return $uri;
    }

    /**
     * Get authorize token endpoint
     *
     * @return OAuth\Common\Http\Uri\Uri
     */
    public function getAuthorizationEndpoint()
    {
    	$uri = clone $this->baseApiUri;
    	$uri->setPath('oauth/authorize');
    	
    	return $uri;
    }

    /**
     * Get access token endpoint
     * 
     * @return OAuth\Common\Http\Uri\Uri
     */
    public function getAccessTokenEndpoint()
    {
    	$uri = clone $this->baseApiUri;
    	$uri->setPath('oauth/token');
    	
    	return $uri;
    }

    /**
     * Parse request token response
     * 
     * @param array $responseBody
     * @return OAuth\OAuth1\Token\StdOAuth1Token
     */
    protected function parseRequestTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        }

        return $this->parseAccessTokenResponse($responseBody);
    }

    /**
     * Parse access token response
     * 
     * @param array $responseBody
     * @return OAuth\OAuth1\Token\StdOAuth1Token
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        if(null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        }
        elseif(isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

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
}
