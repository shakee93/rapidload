<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_AMP_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'amp/amp.php';
        $this->catgeory = 'performance';
        $this->name = 'amp';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('uucss/enabled', [$this, 'handle']);
    }

    public function handle($args)
    {
        if(function_exists('amp_is_request') && amp_is_request()){
            return false;
        }
        return $args;
    }

    public function is_mu_plugin()
    {
        return false;
    }
}