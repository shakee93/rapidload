<?php

defined( 'ABSPATH' ) or die();

use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

class RapidLoad_Sitemap
{
    use RapidLoad_Utils;

    private $sitemap_error;

    function __construct(){

    }

    function process_site_map($sitemap_url, array $urls = [] ){

        $args = apply_filters(
            'rapidload/sitemap/request',
            [
                'timeout'    => 40,
                'user-agent' => 'RapidLoad/Sitemaps',
                'sslverify'  => apply_filters( 'https_local_ssl_verify', false ),
            ]
        );

        $sitemap = wp_remote_get( esc_url_raw( $sitemap_url ), $args );

        if ( is_wp_error( $sitemap ) ) {
            return $urls;
        }

        $response_code = wp_remote_retrieve_response_code( $sitemap );

        if ( 200 !== $response_code ) {

            return $urls;
        }

        $xml_data = wp_remote_retrieve_body( $sitemap );

        if ( empty( $xml_data ) ) {

            return $urls;
        }

        if ( ! function_exists( 'simplexml_load_string' ) ) {

            return $urls;
        }

        $xml = simplexml_load_string( $xml_data );

        if(!$xml){

            return $urls;
        }

        $url_count = count( $xml->url );
        $sitemap_children = count( $xml->sitemap );

        if ( $url_count > 0 ) {

            for ( $i = 0; $i < $url_count; $i++ ) {

                $url = (string) $xml->url[ $i ]->loc;

                if ( ! $url ) {
                    continue;
                }

                array_push($urls, $this->transform_url($url));
            }

            return $urls;
        }

        if ( ! $sitemap_children ) {
            return $urls;
        }

        for ( $i = 0; $i < $sitemap_children; $i++ ) {
            $sub_sitemap_url = (string) $xml->sitemap[ $i ]->loc;
            $urls = $this->process_site_map( $sub_sitemap_url, $urls );
        }

        return $urls;

    }

}