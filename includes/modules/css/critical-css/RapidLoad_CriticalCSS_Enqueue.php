<?php

defined( 'ABSPATH' ) or die();
class RapidLoad_CriticalCSS_Enqueue
{
    use RapidLoad_Utils;

    private $file_system;

    private $dom;
    private $inject;
    private $options;

    private $job_data;
    private $data;
    private $warnings;
    private $is_mobile;
    private $strategy;

    private $frontend_data = [];

    public function __construct($job_data)
    {
        $this->file_system = new RapidLoad_FileSystem();

        $this->job_data = $job_data;

        $this->data = $job_data->rapidload_job_data_get_cpcss_data();

        $this->warnings = $this->job_data->get_warnings();

        add_filter('uucss/enqueue/content/update', [$this, 'update_content'], 20);
    }

    function update_content($state){

        self::debug_log('doing critical css');

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

        if(!isset($this->job_data->id) || $this->job_data->status !== 'success'){
            //$this->inject->rapidload = false;
            //$this->inject->successfully_injected = false;
            return [
                'dom' => $this->dom,
                'inject' => $this->inject,
                'options' => $this->options,
                'strategy' => $this->strategy
            ];
        }

        $this->is_mobile = $this->rapidload_util_is_mobile();

        $data = isset($this->data['desktop']) ? $this->data['desktop'] : null;

        if($this->is_mobile){
            $data = isset($this->data['mobile']) ? $this->data['mobile'] : null;
        }

        if(!isset($data)){
            return [
                'dom' => $this->dom,
                'inject' => $this->inject,
                'options' => $this->options,
                'strategy' => $this->strategy
            ];
        }

        $data = CriticalCSS::extract_file_data($data);

        $file_exist = $this->file_system->rapidload_file_exists(CriticalCSS::$base_dir . '/' . $data['file_name']);

        if(!$file_exist &&
            ($this->job_data->attempts <=2 || (time() - strtotime($this->job_data->created_at)) > 86400)) {
            self::log([
                'log' =>  'requeue-> critical css file not found and attempts less than two or last generated days more than one',
                'url' => $this->job_data->job->url,
            ]);
            $this->job_data->requeue();
            $this->job_data->save();
            //$this->inject->successfully_injected = false;
            return [
                'dom' => $this->dom,
                'inject' => $this->inject,
                'options' => $this->options,
                'strategy' => $this->strategy
            ];
        }

        if($file_exist && $this->dom && $this->inject){

            $this->enqueue_cpcss($data['file_name'], $data['file_count']);

            return [
                'dom' => $this->dom,
                'inject' => $this->inject,
                'options' => $this->options,
                'strategy' => $this->strategy
            ];
        }

        return $state;

    }

    function update_noscript(){

        foreach ( $this->dom->find( 'link' ) as $key => $sheet ) {

            $parent = $sheet->parent();

            if(isset($parent) && $parent->tag === 'noscript' || !self::is_css($sheet)){
                continue;
            }

            if($sheet->tag !== 'link'){
                continue;
            }

            if(is_numeric(strpos($sheet->outertext,'<style'))){
                continue;
            }

            if(isset($sheet->href) && apply_filters('rapidload/cpcss/noscript/disable', false, $sheet->href)){
                continue;
            }

            $data_norapidload = "data-norapidload";

            if(isset($sheet->$data_norapidload)){
                continue;
            }

            if(isset($sheet->id) && $this->rapidload_util_str_contains($sheet->id, 'rapidload-google-font')){
                continue;
            }

            if(isset($sheet->href) && $this->rapidload_util_str_contains($sheet->href, 'fonts.googleapis.com')){
                continue;
            }

            if(!isset($sheet->{'data-href'}) && isset($sheet->{'href'})){
                $sheet->{'data-href'} = $sheet->{'href'};
            }

            if(!$this->is_mobile && apply_filters('rapidload/cpcss/set-preload-css', true)){
                if(defined('RAPIDLOAD_CPCSS_ENABLED') && RAPIDLOAD_CPCSS_ENABLED){
                    if(isset($sheet->href)){
                        unset($sheet->href);
                    }
                }else{
                    $sheet->onload = "this.onload=null;this.rel='stylesheet'";
                    $sheet->rel = "preload";
                    $sheet->as = "style";
                }
            }else{
                if(!apply_filters('rapidload/frontend/do-not-load/original-css', false) && isset($sheet->{'data-href'}) && !apply_filters('rapidload/cpcss/disable/unset-href-mobile',false)){
                    unset($sheet->href);
                }
            }

        }
    }

    function enqueue_cpcss($cpcss_file, $file_count = 1){

        $critical_css_with_tag = '';
        $mode = $this->is_mobile ? 'mobile' : 'desktop';

        for ($i = 1; $i <= $file_count; $i++) {
            $file_name = ($i === 1) ? $cpcss_file : str_replace(".css","-" . $i . ".css", $cpcss_file);
            $index = ($i === 1) ? "" : "-" . $i;
            if($this->file_system->rapidload_file_exists(CriticalCSS::$base_dir . '/' . $file_name)){
                $part = $this->file_system->rapidload_file_get_contents(CriticalCSS::$base_dir . '/' . $file_name );
                $part = apply_filters('rapidload/cpcss/minify', $part, $mode);
                if(!empty($part)){
                    $critical_css_with_tag .= '<style id="rapidload-critical-css' . $index .'" data-mode="'. $mode .'">' . $part . '</style>';
                }
            }
        }

        if(isset($this->options['uucss_additional_css']) && !empty($this->options['uucss_additional_css'])){

            $additional_css = apply_filters('rapidload/cpcss/minify', stripslashes($this->options['uucss_additional_css']), $mode);
            $critical_css_with_tag .= '<style id="rapidload-critical-css-additional" data-mode="'. $mode .'">' . $additional_css . '</style>';

        }

        $_frontend_data['data-mode'] = $mode;
        $_frontend_data['cpcss-file'] = CriticalCSS::$base_dir . '/' . $cpcss_file;

        if(isset($this->dom->find( 'title' )[0])){

            $title_content = $this->dom->find( 'title' )[0]->outertext;

            $this->dom->find( 'title' )[0]->__set('outertext', $title_content . $critical_css_with_tag);

            $this->update_noscript();

            if(!defined('RAPIDLOAD_UUCSS_ENABLED')){

                $body = $this->dom->find('body', 0);

                $content = file_get_contents(RAPIDLOAD_PLUGIN_DIR . '/assets/js/rapidload.cpcss.frontend.min.js');

                if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true || defined('RAPIDLOAD_DEV_MODE') && RAPIDLOAD_DEV_MODE === true) {
                    $filePath = RAPIDLOAD_PLUGIN_DIR . '/assets/js/rapidload.cpcss.frontend.js';

                    if (file_exists($filePath)) {
                        $content = file_get_contents($filePath);
                    }
                }

                $node = $this->dom->createElement('script', "" . $content . "");

                $node->setAttribute('id', 'rapidload-cpcss-frontend-js');
                $node->setAttribute('type', 'text/javascript');
                $node->setAttribute('norapidload',true);
                $body->appendChild($node);
                $_frontend_data['cpcss_styles_added'] = true;
            }

        }

        $this->job_data->mark_as_successful_hit();
        $this->job_data->save();

        if(isset($this->options['remove_cpcss_on_user_interaction']) && $this->options['remove_cpcss_on_user_interaction'] === "1"){

            $body = $this->dom->find('body', 0);
            $node = $this->dom->createElement('script',
                "['mousemove', 'touchstart', 'keydown','scroll'].forEach(function (event) { var listener = function () { setTimeout(function (){ let element = document.getElementById('rapidload-critical-css'); if(element){ element.remove();} }, 5000); removeEventListener(event, listener) }; addEventListener(event, listener);});");

            $node->setAttribute('type', 'text/javascript');
            $body->appendChild($node);

        }

        if(!empty($_frontend_data)){
            $this->frontend_data['cpcss'] = $_frontend_data;
        }

        add_filter('rapidload/optimizer/frontend/data', function ($data){
            return array_merge($data,$this->frontend_data);
        });

        add_filter('uucss/enqueue/content/cpcss/handled', function (){
            return true;
        });

    }

    private static function is_css( $el ) {
        return $el->rel === 'stylesheet' || ($el->rel === 'preload' && $el->as === 'style');
    }
}