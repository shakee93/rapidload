<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_CSS_Aggregator_Enqueue')){
    return;
}

class RapidLoad_CSS_Aggregator_Enqueue
{
    use RapidLoad_Utils;

    private $job = null;

    private $dom;
    private $inject;
    private $options;
    private $file_system;
    private $settings;
    private $strategy;

    private $aggregated_css = "";

    public function __construct($job)
    {
        $this->job = $job;
        $this->file_system = new RapidLoad_FileSystem();

        add_filter('uucss/enqueue/content/update', [$this, 'rapidload_update_content'], 30);
    }

    public function rapidload_update_content($state){

        self::rapidload_util_debug_log('doing aggregate css');

        if(isset($state['dom'])){
            $this->dom = $state['dom'];
        }

        if(isset($state['inject'])){
            $this->inject = $state['inject'];
        }

        if(isset($state['options'])){
            $this->options = $state['options'];
        }

        if(isset($state['strategy'])){
            $this->strategy = $state['strategy'];
        }

        $links = $this->dom->find( 'link' );

        foreach ($links as $link){

            $this->rapidload_aggregate_css($link);

        }

        $version = substr(hash('md5', base64_encode($this->aggregated_css)), 0, 12);

        $aggregated_file = RapidLoad_CSS_Aggregator::$base_dir . '/rapidload-aggreated-css-' . $version . '.css';
        $aggregated_file_url = apply_filters('uucss/enqueue/aggregated-css-url', 'rapidload-aggreated-css-' . $version . '.css');

        $this->file_system->rapidload_file_put_contents($aggregated_file, $this->aggregated_css);

        $body = $this->dom->find('head', 0);
        $node = $this->dom->createElement('link', "");

        $node->setAttribute('id', 'rapidload-aggregated-css');
        $node->setAttribute('href', $aggregated_file_url);

        $node->setAttribute('media', 'all');

        if(apply_filters('uucss/enqueue/content/cpcss/handled', false)){
            $node->setAttribute('rel', 'preload');
            $node->setAttribute('as', 'style');
            $node->setAttribute('onload', "this.onload=null;this.rel='stylesheet'");
        }else{
            $node->setAttribute('rel', 'stylesheet');
        }

        $body->appendChild($node);

        return [
            'dom' => $this->dom,
            'inject' => $this->inject,
            'options' => $this->options,
            'strategy' => $this->strategy
        ];
    }

    public function rapidload_aggregate_css($link){

        if(!self::rapidload_is_css($link) || $this->rapidload_util_is_file_excluded($this->options, $link->href)){
            return;
        }

        if(!$this->rapidload_util_str_contains($link->href, ".css")){
            return;
        }

        $file_path = self::rapidload_util_get_file_path_from_url($link->href);

        if(!$file_path){
            return;
        }

        if(!is_file($file_path)){
            return;
        }

        $this->aggregated_css .= file_get_contents($file_path) . "\n";
        $link->remove();


    }

    private static function rapidload_is_css( $el ) {
        return $el->rel === 'stylesheet' || ($el->rel === 'preload' && $el->as === 'style');
    }
}