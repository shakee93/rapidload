<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_JavaScript
{

    use RapidLoad_Utils;

    public $base;
    public $file_system;
    public $options = [];

    public static $key_post_meta = 'rapidload_post_meta';

    public static $base_dir;

    public function __construct()
    {
        $this->options = RapidLoad_Base::get_merged_options();

        add_action('uucss/options/js', [$this, 'render_options']);

        if(!isset($this->options['uucss_enable_javascript']) || $this->options['uucss_enable_javascript'] !== "1"){
            return;
        }

        $this->file_system = new RapidLoad_FileSystem();

        if( ! $this->initFileSystem() ){
            return;
        }

        add_action('rapidload/job/handle', [$this, 'optimize_javascript'], 30, 2);

        //add_action( 'admin_bar_menu', [$this, 'add_admin_bar_items' ], 90 );

        add_filter('uucss/enqueue/js-minified-url', function ($js_file){
            return $this->get_cached_file($js_file, apply_filters('uucss/enqueue/cache-file-url/cdn', null));
        },10,1);

        //$this->enqueue_admin_scripts();

        add_action('rapidload/vanish', [ $this, 'vanish' ]);

        add_action('rapidload/vanish/js', [ $this, 'vanish' ]);

        add_filter('rapidload/js/excluded-files', function ($list){
            if(defined('RAPIDLOAD_PAGE_OPTIMIZER_ENABLED') && RAPIDLOAD_PAGE_OPTIMIZER_ENABLED){
                $list = $this->get_dynamic_exclusions($list);
            }
            return $list;
        }, 10);

        add_action('rapidload/admin-bar-actions', [$this, 'add_admin_clear_action']);
    }

    public function add_admin_clear_action($wp_admin_bar){
        $wp_admin_bar->add_node( array(
            'id'    => 'rapidload-clear-js-cache',
            'title' => '<span class="ab-label">' . __( 'Clear JS Optimizations', 'unusedcss' ) . '</span>',
            //'href'  => admin_url( 'admin.php?page=rapidload&action=rapidload_purge_all' ),
            'href'   => wp_nonce_url( add_query_arg( array(
                '_action' => 'rapidload_purge_all',
                'job_type' => 'js',
                'clear' => true,
            ) ), 'uucss_nonce', 'nonce' ),
            'meta'  => array( 'class' => 'rapidload-clear-all', 'title' => 'RapidLoad will clear cached JS files' ),
            'parent' => 'rapidload'
        ));
    }

    public function vanish() {

        if ( $this->file_system->rapidload_file_exists( self::$base_dir ) ){
            $this->file_system->delete( self::$base_dir, true );
        }

    }

    public function initFileSystem() {

        // Todo cache base setup
        /*$cache_base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR);

        $cache_base_option = RapidLoad_Base::get_option('rapidload_cache_base', null);

        if(!isset($cache_base_option)){

            $cache_base_option = $cache_base;
            RapidLoad_Base::update_option('rapidload_cache_base', $cache_base_option);
        }

        $this->base = RapidLoad_ThirdParty::plugin_exists('autoptimize') ? $cache_base_option . 'cpcss' : $cache_base . 'cpcss';*/

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'js';

        if ( ! $this->file_system ) {
            return false;
        }

        if ( ! $this->init_base_dir() ) {
            return false;
        }

        return true;
    }

    public function init_base_dir() {

        self::$base_dir = self::rapidload_util_get_wp_content_dir() . $this->base;

        if ( $this->file_system->rapidload_file_exists( self::$base_dir ) ) {
            return true;
        }

        // make dir if not exists
        $created = RapidLoad_Cache_Store::mkdir_p( self::$base_dir );

        if (!$created || ! $this->file_system->is_writable( self::$base_dir ) || ! $this->file_system->is_readable( self::$base_dir ) ) {
            return false;
        }

        return true;
    }

    public function enqueue_admin_scripts(){
        if(is_user_logged_in()){

            add_action('wp', function (){

                global $post;

                if(isset($post->ID)){

                    wp_enqueue_style( 'featherlight', UUCSS_PLUGIN_URL . 'assets/libs/popup/featherlight.css' );
                    wp_enqueue_style('rapidload-optimizer', UUCSS_PLUGIN_URL . 'includes/modules/javascript/assets/css/style.css', UUCSS_VERSION);

                    wp_enqueue_script( 'featherlight', UUCSS_PLUGIN_URL . 'assets/libs/popup/featherlight.js' , array( 'jquery' ) );
                    wp_register_script( 'rapidload-js-optimizer', UUCSS_PLUGIN_URL . 'includes/modules/javascript/assets/js/js-core.js', array(
                        'jquery',
                    ) , UUCSS_VERSION);

                    wp_localize_script( 'rapidload-js-optimizer', 'rapidload_js_optimizer', [
                        'post_id' => $post->ID,
                        'current_url' => $this->transform_url(get_permalink($post->ID)),
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'settings' => get_post_meta($post->ID, 'rapidload_js_settings')
                    ] );

                    wp_enqueue_script('rapidload-js-optimizer');
                }

            });

        }
    }

    public function add_admin_bar_items($wp_admin_bar){

        global $post;

        if(isset($post->ID)){

            $wp_admin_bar->add_menu(
                array(
                    'id'     => 'rapidload_psa',
                    'parent' => 'top-secondary',
                    'title'  => '<span class="ab-item">RapidLoad Optimizer</span>',
                    'meta'   => array( 'title' => 'RapidLoad Optimizer' ),
                )
            );

        }


    }

    public function render_options($args){
        $options = $args;
        include_once 'parts/options.html.php';

    }

    public function optimize_javascript($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_rapidload_js'] )){
            return false;
        }

        new Javascript_Enqueue($job);

    }

    public function get_cached_file( $file_url, $cdn = null ) {

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

    public static function get_third_party_scripts(){
        return[
            [
                'type' => "third_party",
                'name' => "Amazon Ads",
                'id' => "amazon_ads",
                'exclusions' => [
                    "amazon-adsystem.com"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Google AdSense",
                'id' => "google_adsense",
                'exclusions' => [
                    "adsbygoogle"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Google Analytics",
                'id' => "google_analytics",
                'exclusions' => [
                    "/google-analytics\.com\/analytics\.js/",
                    "/ga\( '/",
                    "/ga\('/"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Google Maps",
                'id' => "google_maps",
                'exclusions' => [
                    "maps.googleapis.com",
                    "maps.google.com"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Google Optimize",
                'id' => "google_optimize",
                'exclusions' => [
                    "a,s,y,n,c,h,i,d,e",
                    "/googleoptimize.com\/optimize.js/",
                    "async-hide"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Google Recaptcha",
                'id' => "google_recaptcha",
                'exclusions' => [
                    "recaptcha"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Google Tag Manager",
                'id' => "google_tag_manager",
                'exclusions' => [
                    "/gtag\/js/",
                    "/gtag\(/",
                    "/gtm.js/",
                    "async-hide"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Facebook",
                'id' => "facebook",
                'exclusions' => [
                    "connect.facebook.net/en_US/fbevents.js"
                ],
            ],
            [
                'type' => "third_party",
                'name' => "Hotjar",
                'id' => "hotjar",
                'exclusions' => [
                    "/static.hotjar.com/c/hotjar/"
                ],
            ],
        ];
    }

    public static function get_dynamic_exclusion_list(){
        $plugins = self::get_active_plugins();
        $exclusion_list = [];
        foreach ($plugins as $plugin){
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
            $plugin_name = $plugin_data['Name'];
            $path = str_replace(basename($plugin), "", $plugin);

            if (strpos($path, 'autoptimize-unusedcss') !== false || strpos($path, 'unusedcss') !== false) {
                continue;
            }

            $exclusion_list[] = [
                'type' => "plugins",
                'name' => $plugin_name,
                'id' => str_replace(" ","_", strtolower($plugin_name)),
                'exclusions' => [
                    "/plugins/" . $path,
                    "/jquery-?[0-9.](.*)(.min|.slim|.slim.min)?.js/",
                    "/jquery-migrate(.min)?.js/",
                ],
            ];
        }
        $exclusion_list[] = [
            'type' => "theme",
            'name' => self::get_active_theme(),
            'id' => str_replace(" ","_", strtolower(self::get_active_theme())),
            'exclusions' => [
                get_template_directory_uri(),
                "/jquery-?[0-9.](.*)(.min|.slim|.slim.min)?.js/",
                "/jquery-migrate(.min)?.js/",
            ],
        ];
        $third_party_scripts = self::get_third_party_scripts();
        return array_merge($third_party_scripts, $exclusion_list);
    }

    public static function get_active_theme() {
        $theme = wp_get_theme();
        $parent = $theme->get_template();
        if ( ! empty( $parent ) ) {
            return $parent;
        }
        return $theme->get( 'Name' );
    }

    public static function get_active_plugins() {
        $plugins = (array) get_option( 'active_plugins', [] );

        if ( ! is_multisite() ) {
            return $plugins;
        }

        return array_merge(
            $plugins,
            array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) )
        );
    }

    public function get_dynamic_exclusions($exclude = []){

        $exclusions = self::get_dynamic_exclusion_list();

        $dynamic_js_exclusion = isset($this->options['uucss_dynamic_js_exclusion_list']) && !empty($this->options['uucss_dynamic_js_exclusion_list']) ? explode("\n", $this->options['uucss_dynamic_js_exclusion_list']) : [];

        foreach ($dynamic_js_exclusion as $id){

            $key = array_search($id, array_column($exclusions, 'id'), true);

            if(isset($key) && is_numeric($key)){

                $exclude = array_merge($exclude, $exclusions[$key]['exclusions']);

            }
        }

        return array_unique($exclude);

    }
}