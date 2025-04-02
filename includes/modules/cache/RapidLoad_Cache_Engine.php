<?php

class RapidLoad_Cache_Engine
{

    public static $started = false;
    public static $request_headers;
    public static $settings;
    public static $to_be_cached = false;

    public function __construct()
    {
        if ( self::$started ) {
            global $wp_rewrite;
            $wp_rewrite->init();
        }

        self::$request_headers = self::get_request_headers();

        if ( self::is_index() ) {
            self::$settings = RapidLoad_Cache_Store::get_settings( ! self::$started );
        } elseif ( class_exists( 'RapidLoad_Cache' ) ) {
            self::$settings = RapidLoad_Cache::get_settings( ! self::$started );
        }

        self::$started = ( ! empty( self::$settings ) ) ? true : false;

    }

    private static function get_request_headers() {

        if ( ! empty( self::$request_headers ) ) {
            return self::$request_headers;
        }

        $request_headers = function_exists( 'apache_request_headers' ) ? apache_request_headers() : array();

        $request_headers = array(
            'Accept'             => isset( $request_headers['Accept'] ) ? self::sanitize_server_input( $request_headers['Accept'] ) : ( isset( $_SERVER['HTTP_ACCEPT'] ) ? self::sanitize_server_input( $_SERVER['HTTP_ACCEPT'] ) : '' ),
            'Accept-Encoding'    => isset( $request_headers['Accept-Encoding'] ) ? self::sanitize_server_input( $request_headers['Accept-Encoding'] ) : ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? self::sanitize_server_input( $_SERVER['HTTP_ACCEPT_ENCODING'] ) : '' ),
            'Host'               => isset( $request_headers['Host'] ) ? self::sanitize_server_input( $request_headers['Host'] ) : ( isset( $_SERVER['HTTP_HOST'] ) ? self::sanitize_server_input( $_SERVER[ 'HTTP_HOST' ] ) : '' ),
            'If-Modified-Since'  => isset( $request_headers['If-Modified-Since'] ) ? self::sanitize_server_input( $request_headers['If-Modified-Since'] ) : ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? self::sanitize_server_input( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) : '' ),
            'User-Agent'         => isset( $request_headers['User-Agent'] ) ? self::sanitize_server_input( $request_headers['User-Agent'] ) : ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? self::sanitize_server_input( $_SERVER['HTTP_USER_AGENT'] ) : '' ),
            'X-Forwarded-Proto'  => isset( $request_headers['X-Forwarded-Proto'] ) ? self::sanitize_server_input( $request_headers['X-Forwarded-Proto'] ) : ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? self::sanitize_server_input( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) : '' ),
            'X-Forwarded-Scheme' => isset( $request_headers['X-Forwarded-Scheme'] ) ? self::sanitize_server_input( $request_headers['X-Forwarded-Scheme'] ) : ( isset( $_SERVER['HTTP_X_FORWARDED_SCHEME'] ) ? self::sanitize_server_input( $_SERVER['HTTP_X_FORWARDED_SCHEME'] ) : '' ),
        );

        return $request_headers;
    }

    public static function start( $force = false ) {

        if ( $force || self::should_start() ) {
            new self();
        }

        return self::$started;
    }

    public static function should_start() {

        $valid_engine_running = ( self::$started && ( ! is_multisite() || ! ms_is_switched() ) );
        $early_ajax_request   = ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! class_exists( 'RapidLoad_Cache' ) );
        $rest_request         = ( defined( 'REST_REQUEST' ) && REST_REQUEST );
        $xmlrpc_request       = ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST );
        $bad_request_uri      = ( str_replace( array( '.ico', '.txt', '.xml', '.xsl' ), '', $_SERVER['REQUEST_URI'] ) !== $_SERVER['REQUEST_URI'] );

        if ( $valid_engine_running || $early_ajax_request || $rest_request || $xmlrpc_request || $bad_request_uri || wp_doing_cron() ) { // wp_doing_ajax() removed
            return false;
        }

        return true;
    }

    private static function is_wrong_permalink_structure() {

        if ( self::$settings['use_trailing_slashes'] ) {
            if ( preg_match( '/\/[^\.\/\?]+(\?.*)?$/', sanitize_url( $_SERVER['REQUEST_URI'] ) ) ) {
                return true;
            }
        } elseif ( preg_match( '/\/[^\.\/\?]+\/(\?.*)?$/', sanitize_url( $_SERVER['REQUEST_URI'] ) ) ) {
            return true;
        }

        return false;
    }

    private static function is_excluded() {

        $bad_request_method = ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'GET' );
        $bad_response_code  = ( http_response_code() !== 200 );
        $donotcachepage     = ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE );

        if ( $bad_request_method || $bad_response_code || $donotcachepage || self::is_wrong_permalink_structure() ) {
            return true;
        }

        // Post ID exclusions.
        if ( ! empty( self::$settings['excluded_post_ids'] ) && function_exists( 'is_singular' ) && is_singular() ) {
            $post_id = get_queried_object_id();
            $excluded_post_ids = array_map( 'absint', (array) explode( ',', self::$settings['excluded_post_ids'] ) );

            if ( in_array( $post_id, $excluded_post_ids, true ) ) {
                return true;
            }
        }

        // Page path exclusions.
        if ( ! empty( self::$settings['excluded_page_paths'] ) ) {
            $page_path = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

            if ( preg_match( self::$settings['excluded_page_paths'], $page_path ) ) {
                return true;
            }
        }

        // Query string exclusions.
        if ( ! empty( $_GET ) ) {
            if ( ! empty( self::$settings['excluded_query_strings'] ) ) {
                $query_string_regex = self::$settings['excluded_query_strings'];
            } else {
                $query_string_regex = '/^(?!(fbclid|ref|mc_(cid|eid)|utm_(source|medium|campaign|term|content|expid)|gclid|fb_(action_ids|action_types|source)|age-verified|usqp|cn-reloaded|_ga|_ke)).+$/';
            }

            $query_string = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );

            if ( preg_match( "/^rapidload_preview$/", $query_string ) ) {
                return false;
            }

            if ( preg_match( $query_string_regex, $query_string ) ) {
                return true;
            }
        }

        // Cookie exclusions.
        if ( ! empty( $_COOKIE ) ) {
            if ( ! empty( self::$settings['excluded_cookies'] ) ) {
                $cookies_regex = self::$settings['excluded_cookies'];
            } else {
                $cookies_regex = '/^(wp-postpass|wordpress_logged_in|comment_author)_/';
            }

            foreach ( $_COOKIE as $key => $value ) {
                if ( preg_match( $cookies_regex, $key ) ) {
                    return true;
                }
            }
        }

        // When the output buffering is ending.
        if ( class_exists( 'WP' ) ) {
            if ( is_admin() || is_feed() || is_trackback() || is_robots() || is_preview() || post_password_required() || self::exclude_search() ) {
                return true;
            }
        }

        return false;
    }

    private static function exclude_search() {

        $exclude_search = apply_filters( 'rapidload_cache_exclude_search', is_search() );

        return $exclude_search;
    }

    public static function bypass_cache() {

        $bypass_cache = apply_filters( 'rapidload_bypass_cache', self::is_excluded() );

        return $bypass_cache;
    }

    public static function deliver_cache() {
        header( 'X-Cache-Handler: rapidload-cache-engine' );

        $cache_file = RapidLoad_Cache_Store::get_cache_file();

        if ( RapidLoad_Cache_Store::cache_exists( $cache_file ) && ! RapidLoad_Cache_Store::cache_expired( $cache_file ) && ! self::bypass_cache() ) {
            header('X-Optimized-By: RapidLoad v' . UUCSS_VERSION);
            header( 'X-Cache-Status: HIT' );

            if ( strtotime( self::$request_headers['If-Modified-Since'] >= filemtime( $cache_file ) ) ) {
                header( self::sanitize_server_input( $_SERVER['SERVER_PROTOCOL'] ) . ' 304 Not Modified', true, 304 );
                exit; // Deliver empty body.
            }

            switch ( substr( $cache_file, -2, 2 ) ) {
                case 'br':
                    header( 'Content-Encoding: br' );
                    break;
                case 'gz':
                    header( 'Content-Encoding: gzip' );
                    break;
            }

            if(function_exists('fopen') && function_exists('fread')){
                $filePointer = fopen($cache_file, 'r');
                if ($filePointer) {

                    while (!feof($filePointer)) {
                        echo fread($filePointer, 8192);
                        flush();
                    }

                    fclose($filePointer);
                    exit;
                }

            }

            readfile( $cache_file );

            exit;
        }


        header( 'X-Cache-Status: MISS' );

        self::$to_be_cached = true;

        return false;
    }

    private static function is_index() {

        if ( ! defined( 'RAPIDLOAD_INDEX_FILE' ) ) {
            return false;
        }

        if ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
            $script_name_length = strlen( $_SERVER['SCRIPT_NAME'] );

            if ( substr( RAPIDLOAD_INDEX_FILE, -$script_name_length, $script_name_length ) === $_SERVER['SCRIPT_NAME'] ) {
                return true;
            }
        }

        return false;
    }

    public static function sanitize_server_input($str, $strict = true) {

        if ( is_object( $str ) || is_array( $str ) ) {
            return '';
        }

        $str = (string) $str;
        if ( 0 === strlen( $str ) ) {
            return '';
        }

        $filtered = preg_replace( '/[\r\n\t ]+/', ' ', $str );
        $filtered = trim( $filtered );

        if ( $strict ) {
            $found = false;
            while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
                $filtered = str_replace( $match[0], '', $filtered );
                $found    = true;
            }

            if ( $found ) {
                $filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
            }
        }

        return $filtered;
    }

    public static function is_cacheable( $contents ) {

        if ( ! is_string( $contents ) ) {
            return false;
        }

        $has_html_tag       = ( stripos( $contents, '<html' ) !== false );
        $has_html5_doctype  = preg_match( '/^<!DOCTYPE.+html\s*>/i', ltrim( $contents ) );
        $has_xsl_stylesheet = ( stripos( $contents, '<xsl:stylesheet' ) !== false || stripos( $contents, '<?xml-stylesheet' ) !== false );

        if ( $has_html_tag && $has_html5_doctype && ! $has_xsl_stylesheet ) {
            return true;
        }

        return false;
    }
}