<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_HTML_Minifier')){
    return;
}

class RapidLoad_HTML_Minifier
{
    use RapidLoad_Utils;

    public $options = [];

    public function __construct()
    {
        $this->options = RapidLoad_Base::rapidload_get_merged_options();

        if(!isset($this->options['rapidload_minify_html']) || $this->options['rapidload_minify_html'] !== "1"){
            return;
        }

        add_action('rapidload/job/handle', [$this, 'rapidload_minify_html'], 90, 2);

    }

    public function rapidload_minify_html($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_rapidload_html_minify'] )){
            return false;
        }

        new RapidLoad_HTML_Minifier_Enqueue($job);

    }
}