<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_MinifyCSS_Enqueue
{
    use RapidLoad_Utils;

    private $job = null;

    private $dom;
    private $inject;
    private $options;
    private $file_system;
    private $settings;
    private $strategy;

    private $frontend_data = [];

    public function __construct($job)
    {
        $this->job = $job;
        $this->file_system = new RapidLoad_FileSystem();

        add_filter('uucss/enqueue/content/update', [$this, 'update_content'], 40);
    }

    public function update_content($state){

        self::debug_log('doing minify css');

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

        foreach ( $links as $link ) {


            $this->minify_css($link);

            do_action('rapidload/enqueue/after-minify-css', $link, $this->job, $this->strategy);

        }

        $styles = $this->dom->find( 'style' );

        foreach ($styles as $style){

            $this->minify_inline_css($style);

        }

        add_filter('rapidload/optimizer/frontend/data', function ($data){
            return array_merge($data,$this->frontend_data);
        });

        return [
            'dom' => $this->dom,
            'inject' => $this->inject,
            'options' => $this->options,
            'strategy' => $this->strategy
        ];
    }

    public function minify_css($link){

        $_frontend_data = [];

        if(!self::is_css($link) || $this->is_file_excluded($link->href)){
            return;
        }

        if(!$this->rapidload_util_str_contains($link->href, ".css")){
            return;
        }

        $_frontend_data['href'] =  $link->href;

        $file_path = self::get_file_path_from_url($link->href);

        if(!$file_path){
            return;
        }

        if(!is_file($file_path)){
            return;
        }

        $version = substr(hash_file('md5', $file_path), 0, 12);

        $filename = basename(preg_replace('/\?.*/', '', $file_path));

        if(!$filename){
            return;
        }

        if($this->rapidload_util_str_contains($filename, ".min.css")){
            return;
        }else if($this->rapidload_util_str_contains($filename, ".css")){
            $filename = str_replace(".css","-{$version}.min.css", $filename);
        }

        $minified_file = MinifyCSS::$base_dir . '/' . $filename;
        $minified_url = apply_filters('uucss/enqueue/css-minified-url', $filename);

        $file_exist = $this->file_system->rapidload_file_exists($minified_file);

        if(!$file_exist){
            $minifier = new \MatthiasMullie\Minify\CSS($file_path);
            $minifier->minify($minified_file);

        }

        $link->href = $minified_url;
        $link->{'data-rpd-minify'} = true;

        $_frontend_data['new_href'] = $link->href;

        if(!empty($_frontend_data)){
            $this->frontend_data['minify_css'][] = $_frontend_data;
        }
    }

    public function minify_inline_css($style){

        $minifier = new \MatthiasMullie\Minify\CSS();
        $minifier->add($style->innertext);
        $style->__set('innertext',$minifier->minify());
    }

    private static function is_css( $el ) {
        return $el->rel === 'stylesheet' || ($el->rel === 'preload' && $el->as === 'style');
    }

    private function is_file_excluded($file) {

        $files = isset( $this->options['uucss_minify_excluded_files'] ) && !empty($this->options['uucss_minify_excluded_files']) ? explode( "\n", $this->options['uucss_minify_excluded_files'] ) : [];

        foreach ( $files as $excluded_file ) {

            if($this->rapidload_util_str_contains( trim($excluded_file), '*' ) && self::is_path_glob_matched($file, trim($excluded_file))){
                return true;
            }else if ( $this->rapidload_util_str_contains( $file, trim($excluded_file) ) ) {
                return true;
            }

        }

        return false;
    }
}