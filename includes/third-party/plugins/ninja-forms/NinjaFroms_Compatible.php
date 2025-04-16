<?php

defined( 'ABSPATH' ) or die();

class NinjaFroms_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'ninja-forms/ninja-forms.php';
        $this->catgeory = 'form-builder';
        $this->name = 'ninja-forms';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('uucss/url/exclude', [$this, 'handle']);
    }

    public function handle($args)
    {

        if($this->str_contains("/nf_sub/", $args)){
            return false;
        }

        return $args;
    }

    public function is_mu_plugin()
    {
        return false;
    }
}