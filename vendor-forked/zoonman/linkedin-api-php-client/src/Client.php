<?php
/**
 * linkedin-client
 * Client.php
 *
 * PHP Version 5
 *
 * @category Production
 * @package  Default
 * @author   Philipp Tkachev <philipp@zoonman.com>
 * @date     8/17/17 18:50
 * @license  http://www.zoonman.com/projects/linkedin-client/license.txt
 *           linkedin-client License
 * @version  GIT: 1.0
 * @link     http://www.zoonman.com/projects/linkedin-client/
 */

namespace LinkedIn;

use LinkedIn\Http\Method;

/**
 * Class Client
 *
 * @package LinkedIn
 */
class Client
{

    /**
     * Grant type
     */
    const OAUTH2_GRANT_TYPE = 'authorization_code';

    /**
     * Response type
     */
    const OAUTH2_RESPONSE_TYPE = 'code';

    /**
     * Client Id
     * @var string
     */
    protected $clientId;

    /**
     * Client Secret
     * @var string
     */
    protected $clientSecret;

    /**
     * @var \LinkedIn\AccessToken
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string The URI your users will be sent back to after
     *                            authorization.  This value must match one of
     *                            the defined OAuth 2.0 Redirect URLs in your
     *                            application configuration.
     */
    protected $redirectUrl;

    /**
     * Default authorization URL
     * string
     */
    const OAUTH2_API_ROOT = 'https://www.linkedin.com/oauth/v2/';

    /**
     * Default API root URL
     * string
     */
    const API_ROOT = 'https://api.linkedin.com/rest/';

    /**
     * API Root URL
     *
     * @var string
     */
    protected $apiRoot = self::API_ROOT;

    /**
     * OAuth API URL
     *
     * @var string
     */
    protected $oAuthApiRoot = self::OAUTH2_API_ROOT;

    /**
     * Use oauth2_access_token parameter instead of Authorization header
     *
     * @var bool
     */
    protected $useTokenParam = false;

    /**
     * @return bool
     */
    public function isUsingTokenParam()
    {
        return $this->useTokenParam;
    }

    /**
     * @param bool $useTokenParam
     *
     * @return Client
     */
    public function setUseTokenParam($useTokenParam)
    {
        $this->useTokenParam = $useTokenParam;
        return $this;
    }

    /**
     * List of default headers
     *
     * @var array
     */
    protected $apiHeaders = [
        'Content-Type' => 'application/json',
        'x-li-format' => 'json',
        'X-Restli-Protocol-Version' => '2.0.0',
        'LinkedIn-Version' => '202305',
    ];

    /**
     * Get list of headers
     *
     * @return array
     */
    public function getApiHeaders()
    {
        return $this->apiHeaders;
    }

    /**
     * Set list of default headers
     *
     * @param array $apiHeaders
     *
     * @return Client
     */
    public function setApiHeaders($apiHeaders)
    {
        $this->apiHeaders = $apiHeaders;
        return $this;
    }

    /**
     * Obtain API root URL
     *
     * @return string
     */
    public function getApiRoot()
    {
        return $this->apiRoot;
    }

    /**
     * Specify API root URL
     *
     * @param string $apiRoot
     *
     * @return Client
     */
    public function setApiRoot($apiRoot)
    {
        $this->apiRoot = $apiRoot;
        return $this;
    }

    /**
     * Get OAuth API root
     *
     * @return string
     */
    public function getOAuthApiRoot()
    {
        return $this->oAuthApiRoot;
    }

    /**
     * Set OAuth API root
     *
     * @param string $oAuthApiRoot
     *
     * @return Client
     */
    public function setOAuthApiRoot($oAuthApiRoot)
    {
        $this->oAuthApiRoot = $oAuthApiRoot;
        return $this;
    }

    /**
     * Client constructor.
     *
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct($clientId = '', $clientSecret = '')
    {
        !empty($clientId) && $this->setClientId($clientId);
        !empty($clientSecret) && $this->setClientSecret($clientSecret);
    }

    /**
     * Get ClientId
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set ClientId
     *
     * @param string $clientId
     *
     * @return Client
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * Get Client Secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Set Client Secret
     *
     * @param string $clientSecret
     *
     * @return Client
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * Retrieve Access Token from LinkedIn if we have code provided.
     * If code is not provided, return current Access Token.
     * If current access token is not set, will return null
     *
     * @param string $code
     *
     * @return \LinkedIn\AccessToken|null
     * @throws \LinkedIn\Exception
     */
    public function getAccessToken($code = '')
    {
        if (!empty($code)) {
            $url = $this->buildUrl('accessToken', []);
            try {
                $args = [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'grant_type' => self::OAUTH2_GRANT_TYPE,
                        self::OAUTH2_RESPONSE_TYPE => $code,
                        'redirect_uri' => $this->getRedirectUrl(),
                        'client_id' => $this->getClientId(),
                        'client_secret' => $this->getClientSecret(),
                    ]
                ];
                $response = wp_remote_post( $url, $args );
                if ( is_wp_error( $response ) ) {
                    throw new \Exception($response->get_error_message(), $response->get_error_code());
                }
            } catch (\Exception $exception) {
                throw Exception::fromRequestException($exception);
            }
            $this->setAccessToken(
                AccessToken::fromResponse($response)
            );
        }
        return $this->accessToken;
    }

    /**
     * Convert API response into Array
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    public static function responseToArray($response)
    {
        // Return body if it exists. Or else, attempt to get the header and return.
        // e.g. LinkedIn returns a new id in a response header.
        $body = wp_remote_retrieve_body( $response );
        if ($body) {
            $data = json_decode($body, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(
                    'json_decode error: ' . json_last_error_msg() . $data
                );
            }
            return $data;
        }
        $headers = wp_remote_retrieve_headers( $response );

        return $headers;
    }

    /**
     * Set AccessToken object
     *
     * @param AccessToken|string $accessToken
     *
     * @return Client
     */
    public function setAccessToken($accessToken)
    {
        if (is_string($accessToken)) {
            $accessToken = new AccessToken($accessToken);
        }
        if (is_object($accessToken) && $accessToken instanceof AccessToken) {
            $this->accessToken = $accessToken;
        } else {
            throw new \InvalidArgumentException('$accessToken must be instance of \LinkedIn\AccessToken class');
        }
        return $this;
    }

    /**
     * Retrieve current active scheme
     *
     * @return string
     */
    protected function getCurrentScheme()
    {
        $scheme = 'http';
        if (isset($_SERVER['HTTPS']) && "on" === $_SERVER["HTTPS"]) {
            $scheme = 'https';
        }
        return $scheme;
    }

    /**
     * Get current URL
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        return $this->getCurrentScheme() . '://' . $host . $path;
    }

    /**
     * Get unique state or specified state
     *
     * @return string
     */
    public function getState()
    {
        if (empty($this->state)) {
            $this->setState(
                rtrim(
                    base64_encode(uniqid('', true)),
                    '='
                )
            );
        }
        return $this->state;
    }

    /**
     * Set State
     *
     * @param string $state
     *
     * @return Client
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Retrieve URL which will be used to send User to LinkedIn
     * for authentication
     *
     * @param array $scope Permissions that your application requires
     *
     * @return string
     */
    public function getLoginUrl(
        array $scope = [Scope::READ_BASIC_PROFILE, Scope::READ_EMAIL_ADDRESS]
    ) {
        $params = [
            'response_type' => self::OAUTH2_RESPONSE_TYPE,
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUrl(),
            'state' => $this->getState(),
            'scope' => implode(' ', $scope),
        ];
        $uri = $this->buildUrl('authorization', $params);
        return $uri;
    }

    /**
     * @return string The URI your users will be sent back to after
     *                            authorization.  This value must match one of
     *                            the defined OAuth 2.0 Redirect URLs in your
     *                            application configuration.
     */
    public function getRedirectUrl()
    {
        if (empty($this->redirectUrl)) {
            $this->setRedirectUrl($this->getCurrentUrl());
        }
        return $this->redirectUrl;
    }

    /**
     * @param string $redirectUrl The URI your users will be sent back to after
     *                            authorization.  This value must match one of
     *                            the defined OAuth 2.0 Redirect URLs in your
     *                            application configuration.
     *
     * @return Client
     */
    public function setRedirectUrl($redirectUrl)
    {
        $redirectUrl = filter_var($redirectUrl, FILTER_VALIDATE_URL);
        if (false === $redirectUrl) {
            throw new \InvalidArgumentException('The argument is not an URL');
        }
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * @param string $endpoint
     * @param array  $params
     *
     * @return string
     */
    protected function buildUrl($endpoint, $params)
    {
        $url = $this->getOAuthApiRoot();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $authority = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $path .= trim($endpoint, '/');
        $fragment = '';
        $query = $this->buildQuery($params);

        $uri = '';
        if ($scheme != '') {
            $uri .= $scheme . ':';
        }
        if ($authority != ''|| $scheme === 'file') {
            $uri .= '//' . $authority;
        }
        $uri .= $path;

        if ($query != '') {
            $uri .= '?' . $query;
        }
        if ($fragment != '') {
            $uri .= '#' . $fragment;
        }
        return $uri;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    protected function buildQuery($params)
    {
        $query = http_build_query( $params, "", "&", PHP_QUERY_RFC3986 );
        return $query;
    }

    /**
     * Perform API call to LinkedIn
     *
     * @param string $endpoint
     * @param array  $params
     * @param string $method
     *
     * @return array
     * @throws \LinkedIn\Exception
     */
    public function api($endpoint, array $params = [], $method = Method::GET)
    {
        $headers = $this->getApiHeaders();
        $options = $this->prepareOptions($params, $method);
        Method::isMethodSupported($method);
        if ($this->isUsingTokenParam()) {
            $params['oauth2_access_token'] = $this->accessToken->getToken();
        } else {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken->getToken();
        }
        if (!empty($params) && Method::GET === $method) {
            $endpoint .= '?' . $this->buildQuery($params);
        }
        $url = $this->getApiRoot() . $endpoint;
        try {
            $response = wp_remote_request( $url, array_merge([
                'method' => $method,
                'headers' => $headers,
            ], $options) );
        } catch (\Exception $requestException) {
            throw Exception::fromRequestException($requestException);
        }

        return self::responseToArray($response);
    }

    /**
     * Make API call to LinkedIn using GET method
     *
     * @param string $endpoint
     * @param array  $params
     *
     * @return array
     * @throws \LinkedIn\Exception
     */
    public function get($endpoint, array $params = [])
    {
        return $this->api($endpoint, $params, Method::GET);
    }

    /**
     * Make API call to LinkedIn using POST method
     *
     * @param string $endpoint
     * @param array  $params
     *
     * @return array
     * @throws \LinkedIn\Exception
     */
    public function post($endpoint, array $params = [])
    {
        return $this->api($endpoint, $params, Method::POST);
    }

    /**
     * Make API call to LinkedIn using DELETE method
     *
     * @param string $endpoint
     * @param array  $params
     *
     * @return array
     * @throws \LinkedIn\Exception
     */
    public function delete($endpoint, array $params = [])
    {
        return $this->api($endpoint, $params, Method::DELETE);
    }

    /**
     * Upload Image Binary File (Using Assets API)
     *
     * @see https://docs.microsoft.com/ja-jp/linkedin/consumer/integrations/self-serve/share-on-linkedin#upload-image-binary-file
     * @param $uploadUrl
     * @param $path
     * @return array
     * @throws Exception
     */
    public function upload($uploadUrl, $path)
    {
        $headers = $this->getApiHeaders();
        $headers['Content-Type'] = 'application/binary';
    
        if ($this->isUsingTokenParam()) {
            //
        } else {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken->getToken();
        }
    
        $options = [
            'body' => file_get_contents($path), 
            'headers' => $headers,
            'data_format' => 'body',
        ];
    
        try {
            $response = wp_remote_post($uploadUrl, $options);
        } catch (\Exception $requestException) {
            throw Exception::fromRequestException($requestException);
        }
    
        return self::responseToArray($response);
    }

    /**
     * @param array $params
     * @param string $method
     * @return mixed
     */
    protected function prepareOptions(array $params, $method)
    {
        $options = [];
        if ($method === Method::POST) {
            $json = json_encode($params);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(
                    'json_encode error: ' . json_last_error_msg()
                );
            }
            $options['body'] = $json;
        }
        return $options;
    }
}
