<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_MinifyCSS')){
    return;
}

class RapidLoad_MinifyCSS
{
    use RapidLoad_Utils;

    public $base;
    public $file_system;
    public $options = [];

    public static $base_dir;

    public function __construct()
    {
        $this->options = RapidLoad_Base::rapidload_get_merged_options();

        if(!isset($this->options['uucss_enable_css']) || !isset($this->options['uucss_minify']) || $this->options['uucss_enable_css'] !== "1" || $this->options['uucss_minify'] !== "1" ){
            return;
        }

        if(defined('SCRIPT_DEBUG') && boolval(SCRIPT_DEBUG) === true){
            return;
        }

        $this->file_system = new RapidLoad_FileSystem();

        if( ! $this->rapidload_initFileSystem() ){
            return;
        }

        add_filter('uucss/enqueue/css-minified-url', function ($js_file){
            return $this->rapidload_get_cached_file($js_file, apply_filters('uucss/enqueue/cache-file-url/cdn', null));
        },10,1);

        add_action('rapidload/job/handle', [$this, 'rapidload_minify_css'], 40, 2);

        add_action('rapidload/vanish', [ $this, 'rapidload_vanish' ]);

        add_action('rapidload/vanish/css', [ $this, 'rapidload_vanish' ]);

        add_action('cron_rapidload_minify_css_storage_clean', [$this, 'rapidload_clean_minify_file_storage']);

        /*if (!wp_next_scheduled('cron_rapidload_minify_css_storage_clean')) {
            wp_schedule_event(current_time('timestamp'), 'daily', 'cron_rapidload_minify_css_storage_clean');
        }*/
        add_action('rapidload/cdn/validated', [$this, 'rapidload_update_cdn_url_in_cached_files']);

        add_filter('rapidload/cpcss/minify', function ($css){
            $minifier = new \MatthiasMullie\Minify\CSS();
            $minifier->add($css);
            return $minifier->minify();
        });
    }

    public function rapidload_update_cdn_url_in_cached_files($args) {
        RapidLoad_CDN::rapidload_update_cdn_url_in_cached_files(self::$base_dir, $args);
    }

    public function rapidload_clean_minify_file_storage() {
        $directory_path = RapidLoad_MinifyCSS::$base_dir;
        $days_to_keep = 7;
        $current_time = time();
        $files = scandir($directory_path);
        foreach ($files as $file) {
            $file_path = $directory_path . '/' . $file;
            if (is_file($file_path) && $this->rapidload_util_str_contains($file_path, 'rpd-inline-style-') && (filemtime($file_path) < ($current_time - ($days_to_keep * 86400)))) {
                wp_delete_file($file_path);
            }
        }
    }

    public function rapidload_vanish() {

        if ( $this->file_system->rapidload_file_exists( self::$base_dir ) ){
            $this->file_system->rapidload_file_delete( self::$base_dir, true );
        }

    }

    public function rapidload_minify_css($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_rapidload_mincss'] )){
            return false;
        }

        new RapidLoad_MinifyCSS_Enqueue($job);
    }

    public function rapidload_initFileSystem() {

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'min-css';

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