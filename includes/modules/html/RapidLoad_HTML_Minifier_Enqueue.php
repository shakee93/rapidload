<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_HTML_Minifier_Enqueue
{
    use RapidLoad_Utils;

    private $job = null;

    private $dom;
    private $inject;
    private $options;
    private $file_system;

    public function __construct($job)
    {
        $this->job = $job;

        add_filter('uucss/enqueue/content/update', [$this, 'update_content'], 99);
    }

    public function update_content($state){

        self::debug_log('doing HTML minify');

        if(isset($state['dom'])){
            $this->dom = $state['dom'];
        }

        if(isset($state['inject'])){
            $this->inject = $state['inject'];
        }

        if(isset($state['options'])){
            $this->options = $state['options'];
        }

        $minifier = new RapidLoad_WP_HTML_Compression($this->dom->__toString());

        $state['dom'] = $minifier->__toString();

        return $state;
    }

}