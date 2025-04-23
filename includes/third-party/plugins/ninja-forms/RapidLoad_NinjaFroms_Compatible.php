<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_NinjaFroms_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'ninja-forms/ninja-forms.php';
        $this->catgeory = 'form-builder';
        $this->name = 'ninja-forms';

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/url/exclude', [$this, 'rapidload_handle']);
    }

    public function rapidload_handle($args)
    {

        if($this->str_contains("/nf_sub/", $args)){
            return false;
        }

        return $args;
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}