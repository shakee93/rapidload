<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Api
{
    use RapidLoad_Utils;

    public static $apiUrl = 'https://api.rapidload.io/api/v1';

    public $apiKey = null;


    /**
     * RapidLoad_Api constructor.
     */
	public function __construct() {
		if ( defined( 'UUCSS_API_URL' ) ) {
			self::$apiUrl = UUCSS_API_URL;
		}

		$key          = isset( RapidLoad_Base::fetch_options()['uucss_api_key'] ) ? UnusedCSS_Admin::fetch_options()['uucss_api_key'] : null;
		$this->apiKey = $key;
	}

	static function get_key() {
		new self();

		return self::$apiUrl;
	}

	function get( $endpoint, $data = [] ) {

        $data = apply_filters('uucss/request', $data, 'get');

		$url = self::$apiUrl . '/' . $endpoint . '?' . http_build_query( $data );

		$response = wp_remote_get( $url, [
			'timeout' => 40,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->apiKey
			]
		] );

		return $this->handle_response( $response );
	}

	function post( $endpoint, $data = [] ) {

        $data = apply_filters('uucss/request', $data, 'post');

        //$data['service'] = true;

		$url = self::$apiUrl . '/' . $endpoint;

		$response = wp_remote_post( $url, [
			'timeout' => 120,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->apiKey
			],
			'body'    => $data
		] );

		return $this->handle_response( $response , $data);
	}


	/**
	 * @param $response array|WP_Error
	 *
	 * @return mixed|null
	 */
	public function handle_response( $response , $data = null) {

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {

			if ( $response['response']['code'] == 200 ) {
				$body = $response['body'];

				return json_decode( $body );
			}

			$this->log([
			    'log' => 'api request failed',
                'type' => 'store',
                'url' => isset($data) && isset($data['url']) ? $data['url'] : '',
                'request_body' => $data,
                'response_body' => $response['body']
            ]);

			return json_decode( $response['body'] );
		} else {

            $this->log([
                'log' => 'api request failed',
                'type' => 'store',
                'url' => isset($data) && isset($data['url']) ? $data['url'] : '',
                'request_body' => $data,
                'response_body' => $response->get_error_message()
            ]);

			return $response->get_error_message();
		}

	}

	public function is_error( $result ) {
		return ! isset( $result ) || isset( $result->errors ) || ( gettype( $result ) === 'string' && strpos( $result, 'cURL error' ) !== false );
	}

    public function extract_error( $result ) {
        if ( gettype( $result ) === 'string' ) {
            return [
                'code'    => 500,
                'message' => $result
            ];
        }

        if ( gettype( $result ) === 'object' && isset( $result->errors ) ) {

            return [
                'code'    => $result->errors[0]->code,
                'message' => $result->errors[0]->detail
            ];

        }

        return [
            'code'    => 500,
            'message' => 'Unknown Error Occurred'
        ];
    }
}