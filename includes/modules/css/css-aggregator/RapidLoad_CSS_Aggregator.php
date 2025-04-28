<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_CSS_Aggregator')){
    return;
}

class RapidLoad_CSS_Aggregator
{

    use RapidLoad_Utils;

    public $base;
    public $file_system;
    public $options = [];

    public static $base_dir;

    public function __construct()
    {
        $this->options = RapidLoad_Base::rapidload_get_merged_options();

        if(!isset($this->options['rapidload_aggregate_css']) || $this->options['rapidload_aggregate_css'] !== "1" ){
            return;
        }

        $this->file_system = new RapidLoad_FileSystem();

        if( ! $this->rapidload_initFileSystem() ){
            return;
        }

        add_filter('uucss/enqueue/aggregated-css-url', function ($file){
            return $this->rapidload_get_cached_file($file, apply_filters('uucss/enqueue/cache-file-url/cdn', null));
        },10,1);

        add_action('rapidload/job/handle', [$this, 'rapidload_aggregate_css'], 40, 2);
    }

    public function rapidload_initFileSystem() {

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'aggregated';

        if ( ! $this->file_system ) {
            return false;
        }

        if ( ! $this->rapidload_init_base_dir() ) {
            return false;
        }

        return true;
    }

    public function rapidload_init_base_dir() {

        self::$base_dir = self::rapidload_util_get_wp_content_dir() . $this->base;

        if ( $this->file_system->rapidload_file_exists( self::$base_dir ) ) {
            return true;
        }

        // make dir if not exists
        $created = RapidLoad_Cache_Store::mkdir_p( self::$base_dir );

        if (!$created || ! $this->file_system->rapidload_file_is_writable( self::$base_dir ) || ! $this->file_system->rapidload_file_is_readable( self::$base_dir ) ) {
            return false;
        }

        return true;
    }

    public function rapidload_aggregate_css($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_agg_css'] )){
            return false;
        }

        new RapidLoad_LinkPreload_Enqueue($job);

    }

    public function rapidload_get_cached_file( $file_url, $cdn = null ) {

        if ( ! $cdn || empty( $cdn ) ) {
            $cdn = self::rapidload_util_get_wp_content_url();
        } else {

            $url_parts = wp_parse_url( self::rapidload_util_get_wp_content_url() );

            $cdn = rtrim( $cdn, '/' ) . (isset($url_parts['path']) ? rtrim( $url_parts['path'], '/' ) : '/wp-content');

        }

        return implode( '/', [
            $cdn,
            trim($this->base, "/"),
            $file_url
        ] );
    }
}