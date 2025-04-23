<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_HideMyWP_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'hide-my-wp/index.php';
        $this->catgeory = 'security';
        $this->name = 'hide-my-wp';

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/enqueue/new/link', [$this, 'rapidload_handle']);
    }

    public function rapidload_handle($args)
    {

        if(!class_exists('HMW_Classes_Tools')){
            return $args;
        }

        $wp_content_dir_path =  explode("/", WP_CONTENT_DIR);

        $hidden_path = HMW_Classes_Tools::getOption( 'hmw_wp-content_url' );

        if(!isset($hidden_path) || empty($hidden_path)){
            return $args;
        }

        return str_replace("/" . $wp_content_dir_path[count($wp_content_dir_path)-1] . " /", "/" . $hidden_path . "/", $args);
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}