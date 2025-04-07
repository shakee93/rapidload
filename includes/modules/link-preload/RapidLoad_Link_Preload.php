<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Link_Preload
{
    use RapidLoad_Utils;

    public $options = [];

    public function __construct()
    {
        $this->options = RapidLoad_Base::get_merged_options();

        if(!isset($this->options['preload_internal_links']) || $this->options['preload_internal_links'] !== "1" ){
            return;
        };

        add_action('rapidload/job/handle', [$this, 'preload_links'], 20, 2);
    }


    public function preload_links($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_preload_links'] )){
            return false;
        }

        new RapidLoad_Link_Preload_Enqueue($job);

    }

}