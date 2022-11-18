<?php

class MinifyCSS
{
    use RapidLoad_Utils;

    public $base;
    public $file_system;
    public $options = [];

    public static $base_dir;

    public function __construct()
    {
        $this->options = RapidLoad_Base::fetch_options();

        if(!isset($this->options['uucss_enable_css']) || !isset($this->options['uucss_minify']) || $this->options['uucss_enable_css'] == "" || $this->options['uucss_minify'] = "" ){
            return;
        }

        $this->file_system = new RapidLoad_FileSystem();

        if( ! $this->initFileSystem() ){
            return;
        }

        add_filter('uucss/enqueue/css-minified-url', function ($js_file){
            return $this->get_cached_file($js_file, apply_filters('uucss/enqueue/cache-file-url/cdn', null));
        },10,1);

        add_action('rapidload/job/handle', [$this, 'minify_css'], 40, 2);
    }

    public function minify_css($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_mincss'] )){
            return false;
        }

        new MinifyCSS_Enqueue($job);
    }

    public function initFileSystem() {

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'min-css';

        if ( ! $this->file_system ) {
            return false;
        }

        if ( ! $this->init_base_dir() ) {
            return false;
        }

        return true;
    }

    public function init_base_dir() {

        self::$base_dir = WP_CONTENT_DIR . $this->base;

        if ( $this->file_system->exists( self::$base_dir ) ) {
            return true;
        }

        // make dir if not exists
        $created = $this->file_system->mkdir( self::$base_dir );

        if (!$created || ! $this->file_system->is_writable( self::$base_dir ) || ! $this->file_system->is_readable( self::$base_dir ) ) {
            return false;
        }

        return true;
    }

    public function get_cached_file( $file_url, $cdn = null ) {

        if ( ! $cdn || empty( $cdn ) ) {
            $cdn = content_url();
        } else {

            $url_parts = parse_url( content_url() );

            $cdn = rtrim( $cdn, '/' ) . (isset($url_parts['path']) ? rtrim( $url_parts['path'], '/' ) : '/wp-content');

        }

        return implode( '/', [
            $cdn,
            trim($this->base, "/"),
            $file_url
        ] );
    }
}