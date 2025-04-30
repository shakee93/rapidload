<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Admin_Bar {
    use RapidLoad_Utils;

    public function __construct()
    {

        add_action( 'wp_after_admin_bar_render', [$this,'rapidload_admin_bar_css'] );
        add_action('admin_bar_menu', [$this, 'add_rapidload_admin_bar_menu'], 100);

        $page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';

        if (
            (!is_admin() && is_user_logged_in() && defined('RAPIDLOAD_PAGE_OPTIMIZER_ENABLED')) ||
            (is_admin() && $page === 'rapidload')
        ) {
            add_action('init', function (){
                $this->load_optimizer_scripts();
            });

            add_action('wp_after_admin_bar_render', function () {
                echo '<div id="rapidload-page-optimizer"></div>';
            });
        }



    }

    public function load_optimizer_scripts()
    {
        $options = RapidLoad_Base::fetch_options();

        $page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';

        $tag = apply_filters('rapidload/titan/tag', 'latest');

        //$package = "https://unpkg.com/@rapidload/page-optimizer@{$tag}/dist";

        $optimizer_path = 'includes/admin/page-optimizer/dist';

        $debug_titan = apply_filters('rapidload/titan/debug', false);

        if ($debug_titan) {
            $optimizer_path .= '-debug';
        }


        $package = UUCSS_PLUGIN_URL . $optimizer_path;

        $asset_map_file = RAPIDLOAD_PLUGIN_DIR . '/' . $optimizer_path . '/asset-map.php';
        $indexJS = '/assets/index.js';
        $indexCSS = '/assets/index.css';

        if (file_exists($asset_map_file)) {
            include_once($asset_map_file);
            if (defined('RAPIDLOAD_ASSET_MAP')) {
                $indexJS = isset(RAPIDLOAD_ASSET_MAP['assets/index.js']) ? RAPIDLOAD_ASSET_MAP['assets/index.js'] : $indexJS;
                $indexCSS = isset(RAPIDLOAD_ASSET_MAP['assets/index.css']) ? RAPIDLOAD_ASSET_MAP['assets/index.css'] : $indexCSS;
            }
        }

        $indexJS = '/' . ltrim($indexJS, '/');
        $indexCSS = '/' . ltrim($indexCSS, '/');

        add_action('admin_head', function()use ($package ,  $indexCSS){
            echo '<link id="rapidload-page-optimizer-css" rel="preload" href="' . esc_url($package . $indexCSS) . '" as="style" type="text/css"/>';
        });

        wp_register_script( 'rapidload_page_optimizer', esc_url($package . $indexJS),[], RAPIDLOAD_VERSION, false);

        $current_url = isset($_SERVER['REQUEST_URI']) ? home_url(sanitize_url(wp_unslash($_SERVER['REQUEST_URI']))) : $this->get_current_url();

        if($this->is_admin_url($current_url)){
            $current_url = site_url();
            if(isset($_GET['optimize-url'])){
                $current_url = $this->transform_url(urldecode(sanitize_url(wp_unslash($_GET['optimize-url']))));
            }
        }

        $rapidload_license_data = get_option('rapidload_license_data', null);   

        if($rapidload_license_data){
            $rapidload_license_data = unserialize($rapidload_license_data);
        }

        $data = array(
            'titan_stylesheet_url' => $package .  $indexCSS,
            'load_optimizer' => true,
            'page_optimizer_package_base' => $package,
            'page_optimizer_base' => UUCSS_PLUGIN_URL .  'includes/admin/page-optimizer/dist',
            'plugin_url' => UUCSS_PLUGIN_URL,
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'admin_url' => admin_url(),
            'dashboard_url' => admin_url( 'admin.php?page=rapidload' ),
            'optimizer_url' => defined('RAPIDLOAD_OPTIMIZER_TEST_URL') ? RAPIDLOAD_OPTIMIZER_TEST_URL : $this->transform_url($current_url),
            'nonce' => self::create_nonce( 'uucss_nonce' ),
            'timezone' => get_option('timezone_string', 'UTC'),
            'rapidload_version' => RAPIDLOAD_VERSION,
            'actions' => [
                [
                    'tooltip' => 'Clear Site Cache',
                    'href' => wp_nonce_url( add_query_arg( array(
                        '_cache'  => 'rapidload-cache',
                        '_action' => 'clear',
                    ) ), 'rapidload_cache_clear_cache_nonce' ),
                    'icon' => 'clear_cache'
                ],
                [
                    'tooltip' => 'Clear Page Cache',
                    'href' => wp_nonce_url( add_query_arg( array(
                        '_cache'  => 'rapidload-cache',
                        '_action' => 'clearurl',
                        '_url' => $this->transform_url($this->get_current_url()),
                    ) ), 'rapidload_cache_clear_cache_nonce' ),
                    'icon' => 'clear_page_cache'
                ],
                [
                    'tooltip' => 'Clear CSS/JS/Font Optimizations',
                    'href' => wp_nonce_url( add_query_arg( array(
                        'action' => 'rapidload_purge_all',
                        'clear' => 'true'
                    ) ), 'uucss_nonce', '_nonce' ),
                    'icon' => 'clear_optimization'
                ]
            ],
            'api_root' => defined('UUCSS_API_URL') ? UUCSS_API_URL : 'https://api.rapidload.io/api/v1',
            'enable_entire_site' => RapidLoad_DB::get_optimization_count() < 2,
            'rest_url' => RapidLoadRestApi::rest_url(),
            'license_key' => RapidLoad_Base::get_license_key(),
            'test_mode' => boolval(isset($options['rapidload_test_mode']) && $options['rapidload_test_mode'] === "1"),
            'uucss_disable_error_tracking' => boolval(isset($options['uucss_disable_error_tracking']) && $options['uucss_disable_error_tracking'] === "1"),
            'rapidload_titan_gear' => get_option('rapidload_titan_gear', 'turboMax'),
            'rapidload_license_data' => $rapidload_license_data,
            'rapidload_privacy_policy_accepted' => get_option('rapidload_privacy_policy_accepted', false),
            'db_to_be_updated' => RapidLoad_DB::$current_version !== RapidLoad_DB::$db_version,
        );

        wp_localize_script( 'rapidload_page_optimizer', 'rapidload_optimizer', $data );

        wp_enqueue_script( 'rapidload_page_optimizer' );

        function add_module_script_type($tag, $handle) {

            if ($handle === 'rapidload_page_optimizer') {
                $tag = preg_replace("/type=['\"]text\/javascript['\"]/", "type='module'", $tag);

                if (!preg_match("/type=/", $tag)) {
                    $tag = preg_replace("/<script /", "<script type=\"module\" ", $tag);
                }
            }
            return $tag;
        }
        add_filter('script_loader_tag', 'add_module_script_type', 10, 2);

    }

    public function rapidload_admin_bar_css()
    {

        if ( is_admin_bar_showing() ) { ?>


            <style id="rapidload-admin-bar-css">

                #wp-admin-bar-rapidload .rl-node-wrapper {
                    display: flex;
                    gap: 6px;
                    z-index: 9999;
                }

                #wp-admin-bar-rapidload .rl-icon {
                    display: inline-flex;
                }

                #wp-admin-bar-rapidload .rl-icon img {
                    margin: 0 !important;
                }

                /*html.rapidload-optimizer-open,
                .rapidload-optimizer-open body,
                body.rapidload-optimizer-open {
                    overflow: hidden !important;
                }*/

                .rpo-loaded\:with-popup #wp-admin-bar-rapidload .ab-sub-wrapper {
                    display: none !important;
                }

                #wp-admin-bar-rapidload .ab-item {
                    padding: 0 8px 0 7px;
                }

                #rapidload-page-optimizer{
                    margin-left: -20px;
                    /* margin-top: 1px; */
                }

                /*.rl-page-optimizer-loaded #wp-admin-bar-rapidload *,*/
                /*.rl-page-optimizer-loaded #wpadminbar .ab-top-menu > li.hover > .ab-item, #wpadminbar.nojq .quicklinks .ab-top-menu > li > .ab-item:focus,*/
                /*.rl-page-optimizer-loaded #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item, #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {*/
                /*    color: #0c0c0c !important;*/

                /*}*/

            </style>

        <?php }
    }

    public function add_rapidload_admin_bar_menu($wp_admin_bar){

        if(apply_filters('rapidload/tool-bar-menu',true)){

            if ( current_user_can( 'manage_options' ) ) {

                do_action('rapidload/admin-bar-actions', $wp_admin_bar);

                $options = RapidLoad_Base::fetch_options();

                $wp_admin_bar->add_node(array(
                    'id'    => 'rapidload',
                    'title' => '<div id="rl-node-wrapper" class="'. ( isset($options['rapidload_test_mode']) && $options['rapidload_test_mode'] === "1" ? 'rl-node-wrapper rl-test-mode-on' : 'rl-node-wrapper') .'" >
                                    <span class="rl-icon">
                                        <img src="'. esc_url(UUCSS_PLUGIN_URL . '/assets/images/logo-icon-light.svg') .'" alt="" style="max-width: 100%">
                                    </span>
                                    <span class="rl-label">'.__( 'RapidLoad', 'unusedcss' ) . '</span>
                                    '. ( isset($options['rapidload_test_mode']) && $options['rapidload_test_mode'] === "1" ? ' <span class="rl-input-wrapper-test-mode"><span class="rl-input-test-mode">Test Mode</span></span>' : '' ) . '</div>',
                    'href'  => admin_url( 'admin.php?page=rapidload' ),
                    'meta'  => array( 'class' => '' ),
                ));

                $wp_admin_bar->add_node( array(
                    'id'    => 'rapidload-clear-cache',
                    'title' => '<span class="ab-label">' . __( 'Clear CSS/JS/Font Optimizations', 'unusedcss' ) . '</span>',
                    //'href'  => admin_url( 'admin.php?page=rapidload&action=rapidload_purge_all' ),
                    'href'   => wp_nonce_url( add_query_arg( array(
                        'action' => 'rapidload_purge_all',
                        'clear' => 'true',
                    ) ), 'uucss_nonce', 'nonce' ),
                    'meta'  => array( 'class' => 'rapidload-clear-all', 'title' => 'RapidLoad will clear all the cached files' ),
                    'parent' => 'rapidload'
                ));

            }

        }


    }

    function is_admin_url($url){
        $_url = wp_parse_url(untrailingslashit(admin_url()));
        if(isset($_url['path']) && $this->str_contains($url, $_url['path'])){
            return true;
        }
        return false;
    }
}