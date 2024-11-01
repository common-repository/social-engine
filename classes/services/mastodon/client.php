<?php
class MastodonClient {

    /**
     * Website
     *
     * @var string
     */
    protected $website;

    /**
     * Access Token
     *
     * @var string
     */
    protected $access_token;

    /**
     * Constructor.
     *
     * @param string $website
     * @param string $access_token
     */
    public function __construct( $website, $access_token )
    {
        $this->website = trailingslashit( $website );
        $this->access_token = $access_token;
    }

    /**
     * Send get request to Mastodon.
     *
     * @param string $path
     * @param array $params
     *
     * @return array|null
     * @throws \Exception
     */
    public function get( $path, $params = [] )
    {
        $url = $this->buildUrl( $path, $params );
        try {
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->access_token}"
                ],
            ];
            $response = wp_remote_get( $url, $args );
            if ( is_wp_error( $response ) ) {
                throw new \Exception( $response->get_error_message(), $this->getErrorCode( $response ) );
            }
            $response_code = wp_remote_retrieve_response_code( $response );
            if ( !$response_code || $response_code >= 400 ) {
                $body = $this->getBodyFromResponse( $response );
                throw new \Exception( $body['error'], $this->getErrorCode( $response ) );
            }
        } catch ( \Exception $exception ) {
            throw $exception;
        }
        return array_merge(
            ['response_code' => $response_code],
            $this->getBodyFromResponse( $response ),
        );
    }

    /**
     * Send post request to Mastodon.
     *
     * @param string $path
     * @param array $body
     *
     * @return array|null
     * @throws \Exception
     */
    public function post( $path, $body = [] )
    {
        $url = $this->buildUrl( $path, [] );
        try {
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->access_token}",
                ],
                'body' => json_encode( $body ),
            ];
            $response = wp_remote_post( $url, $args );
            if ( is_wp_error( $response ) ) {
                throw new \Exception( $response->get_error_message(), $this->getErrorCode( $response ) );
            }
            $response_code = wp_remote_retrieve_response_code( $response );
            if ( !$response_code || $response_code >= 400 ) {
                $body = $this->getBodyFromResponse( $response );
                throw new \Exception( $body['error'], $this->getErrorCode( $response ) );
            }
        } catch ( \Exception $exception ) {
            throw $exception;
        }
        return array_merge(
            ['response_code' => $response_code],
            $this->getBodyFromResponse( $response ),
        );
    }

    /**
     * Send post request with multipart/form-data to Mastodon.
     *
     * @param string $path
     * @param array $body
     *
     * @return array|null
     * @throws Exception
     */
    public function upload( $path, $file_path, $body = [] )
    {
        $url = $this->buildUrl( $path, [] );
        $boundary = wp_generate_password( 24 );
        try {
            $args = [
                'headers' => [
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                    'Authorization' => "Bearer {$this->access_token}",
                ],
                'body' => $this->generatePayload( $boundary, $file_path, $body ),
            ];
            $response = wp_remote_post( $url, $args );
            if ( is_wp_error( $response ) ) {
                throw new \Exception( $response->get_error_message(), $this->getErrorCode( $response ) );
            }
            $response_code = wp_remote_retrieve_response_code( $response );
            if ( !$response_code || $response_code >= 400 ) {
                $body = $this->getBodyFromResponse( $response );
                throw new \Exception( $body['error'], $this->getErrorCode( $response ) );
            }
        } catch ( Exception $exception ) {
            throw $exception;
        }

        $body = $this->getBodyFromResponse( $response );
        if ($response_code === 202) {
            while (true) {
                sleep(2);
                $process_result = $this->get( 'api/v1/media/' . $body['id'] );
                if ( $process_result['response_code'] === 200 ) {
                    break;
                }
            }
        }

        return array_merge(
            ['response_code' => $response_code],
            $body,
        );
    }

    /**
     * Generate payload for multipart/form-data.
     * @param string $boundary
     * @param string $file_path
     * @param array $body
     * @return string
     */
    protected function generatePayload( $boundary, $file_path, $body )
    {
        $payload = '';
        foreach ( $body as $name => $value ) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $name .
                '"' . "\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }

        if ( $file_path ) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . 'file' .
                '"; filename="' . basename( $file_path ) . '"' . "\r\n";
            $payload .= 'Content-Type: image/jpeg' . "\r\n";
            $payload .= "\r\n";
            $payload .= file_get_contents( $file_path );
            $payload .= "\r\n";
        }
        $payload .= '--' . $boundary . '--';
        return $payload;
    }

    /**
     * @param string $endpoint
     * @param array  $params
     *
     * @return string
     */
    protected function buildUrl( $endpoint, $params )
    {
        $url = $this->website;
        $scheme = parse_url( $url, PHP_URL_SCHEME );
        $authority = parse_url( $url, PHP_URL_HOST );
        $path = parse_url( $url, PHP_URL_PATH );
        $path .= trim( $endpoint, '/' );
        $fragment = '';
        $query = $this->buildQuery( $params );

        $uri = '';
        if ( $scheme != '' ) {
            $uri .= $scheme . ':';
        }
        if ( $authority != ''|| $scheme === 'file' ) {
            $uri .= '//' . $authority;
        }
        $uri .= $path;

        if ( $query != '' ) {
            $uri .= '?' . $query;
        }
        if ( $fragment != '' ) {
            $uri .= '#' . $fragment;
        }
        return $uri;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    protected function buildQuery( $params )
    {
        return http_build_query( $params, "", "&", PHP_QUERY_RFC3986 );
    }

    /**
     * Retrieve body from response
     *
     * @param array $response
     *
     * @return array|null
     */
    protected function getBodyFromResponse( $response )
    {
        $body = wp_remote_retrieve_body( $response );
        if ( $body ) {
            $data = json_decode( $body, true );
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(
                    'json_decode error: ' . json_last_error_msg() . $data
                );
            }
            return $data;
        }
        return null;
    }

    /**
     * Get error code from response.
     * Return 500 if the response does not have the code.
     * @param mixed $response
     * @return int
     */
    protected function getErrorCode( $response ) {
        $code = wp_remote_retrieve_response_code( $response );
        if ( !empty( $code ) ) {
            return $code;
        }
        return 500;
    }
}