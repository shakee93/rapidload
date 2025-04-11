<?php

defined( 'ABSPATH' ) or die();

class Autoptimize_Beta_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'autoptimize-beta/autoptimize.php';
        $this->catgeory = 'optimize';
        $this->name = 'autoptimize-beta';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function init_hooks(){

        /*add_filter('uucss/cache-base-dir', function ($value){
            return trailingslashit(defined('AUTOPTIMIZE_CACHE_CHILD_DIR') ? AUTOPTIMIZE_CACHE_CHILD_DIR : '/cache/autoptimize/');
        });*/

        add_action('uucss/options/before_render_form', [$this, 'render_option_page_ao_admin_tabs']);

        add_filter( 'autoptimize_filter_settingsscreen_tabs', [$this, 'handle'], 20, 1 );

        add_filter('uucss/notifications', [$this, 'addNotifications'], 10, 1);

        /*add_action( 'admin_bar_menu', function () {

            wp_enqueue_script( 'wp-util' );

            global $wp_admin_bar;

            $wp_admin_bar->add_node( array(
                'id'     => 'autoptimize-uucss',
                'title'  => $this->get_node_text(),
                'parent' => 'autoptimize',
                'tag'    => 'div'
            ) );

        }, 1 );*/

        if(apply_filters('uucss/autoptimize/clear-on-purge', false)){

            add_action( 'autoptimize_action_cachepurged', function (){
                do_action('uucss/clear');
            });

        }

        add_filter('uucss/tool-bar-menu', function (){
            return false;
        });

        add_filter('cpcss/other-plugins', function ($args){
            $ao_render_blocking = autoptimizeOptionWrapper::get_option( 'autoptimize_css_defer' );
            if(isset($ao_render_blocking) && $ao_render_blocking === "on"){
                $args[] = 'Autoptimize';
            }
            return $args;
        });

        /*add_filter('rapidload/cpcss/minify', function ($css){

            if(class_exists('autoptimizeCSSmin')){
                $css = autoptimizeCSSmin::minify($css);
            }

            if(class_exists('autoptimizeImages') && autoptimizeImages::instance()->should_lazyload()){
                $css = str_replace(".lazyload{display:none}","", $css);
            }

            return $css;
        });*/

        add_filter('uucss/enqueue/cache-file-url/cdn', function ($default){
            return autoptimizeOptionWrapper::get_option( 'autoptimize_cdn_url' );
        }, 10 , 1);

        //add_filter('rapidload/tool-bar-menu','__return_false');
    }

    public function addNotifications($notifications) {

        if(!class_exists('autoptimizeOptionWrapper')){
            return $notifications;
        }

        if (!(bool) autoptimizeOptionWrapper::get_option( 'autoptimize_cache_nogzip' )) {
            $notifications[] = [
                "title" => "Incompatible Autoptimize option enabled",
                "message" => "It is recommended to enable <strong>'Save aggregated script/css as static files?'</strong> in Autoptimize to RapidLoad to work properly.",
                "type" => "error"
            ];
        }

        if(autoptimizeOptionWrapper::get_option( 'autoptimize_css_inline' ) === 'on'){
            $notifications[] = [
                "title" => "Incompatible Autoptimize option enabled",
                "message" => "It is recommended to disable <strong>'inline all css?'</strong> in Autoptimize to RapidLoad to work properly.",
                "type" => "warning"
            ];
        }

        return $notifications;
    }

    public function get_node_text() {
        ob_start();

        include('parts/admin-node.html.php');

        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    public function handle($args) {
        if (function_exists('autoptimize')) {
    
            $args = array_merge($args, array(
                'rapidload' => sprintf(
                    '<span class="uucss-tab-title"><img src="%s" width="15" alt="RapidLoad.io logo"><span>%s</span></span>',
                    esc_url(UUCSS_PLUGIN_URL . '/assets/images/logo-icon.svg'),
                    __("RapidLoad", "unusedcss")
                )
            ));
    
            return $args;
        }
        return $args;
    }

    public function render_option_page_ao_admin_tabs(){

        if(class_exists('autoptimizeConfig')){

            echo autoptimizeConfig::ao_admin_tabs();

        }

    }

    public function is_mu_plugin()
    {
        return false;
    }
}