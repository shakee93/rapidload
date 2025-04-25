<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_Api')){
    return;
}

/**
 * Class RapidLoad_Api
 */
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

		$key          = isset( RapidLoad_Base::rapidload_fetch_options()['uucss_api_key'] ) ? RapidLoad_Base::rapidload_fetch_options()['uucss_api_key'] : null;
		$this->apiKey = defined( 'UUCSS_API_KEY' ) ? UUCSS_API_KEY : $key;
	}

	static function rapidload_api_get_key() {
		new self();

		return self::$apiUrl;
	}

	function rapidload_api_get( $endpoint, $data = [] ) {

        $data = apply_filters('uucss/request', $data, 'get');

		$url = self::$apiUrl . '/' . $endpoint . '?' . http_build_query( $data );

		$response = wp_remote_get( $url, [
			'timeout' => 360,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->apiKey
			]
		] );

		return $this->rapidload_api_handle_response( $response );
	}

	function rapidload_api_post( $endpoint, $data = [] ) {

        $data = apply_filters('uucss/request', $data, 'post');

        //$data['service'] = true;

		$url = self::$apiUrl . '/' . $endpoint;

		$response = wp_remote_post( $url, [
			'timeout' => 360,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->apiKey
			],
			'body'    => $data
		] );

		return $this->rapidload_api_handle_response( $response , $data);
	}


	/**
	 * @param $response array|WP_Error
	 *
	 * @return mixed|null
	 */
	public function rapidload_api_handle_response( $response , $data = null) {

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {

			if ( $response['response']['code'] === 200 ) {
				$body = $response['body'];

				return json_decode( $body );
			}

			return json_decode( $response['body'] );
		} else {

			return $response->get_error_message();
		}

	}

	public function rapidload_api_is_error( $result ) {
		return ! isset( $result ) || isset( $result->errors ) || ( gettype( $result ) === 'string' && strpos( $result, 'cURL error' ) !== false );
	}

    public function rapidload_api_extract_error( $result ) {

        if(!isset($result)){
            return [
                'code'    => 524,
                'message' => 'Request timed out'
            ];
        }

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