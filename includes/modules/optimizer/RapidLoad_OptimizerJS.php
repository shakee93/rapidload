<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_OptimizerJS
{

    static $filter_added = false;


    public function __construct()
    {

        add_filter('rapidload/js/delay-excluded', function ($value, $link, $job, $strategy, $options){
            if(!Javascript_Enqueue::is_js($link)){
                return $value;
            }
            $options = $strategy === "mobile" ? $job->rapidload_job_get_mobile_options(true) : $job->rapidload_job_get_desktop_options(true);
            if(isset($options['individual-file-actions']) && !empty($options['individual-file-actions']) && is_array($options['individual-file-actions'])){
                foreach ($options['individual-file-actions'] as $file_action){
                    if(isset($file_action->type) && $file_action->type === "js"){
                        if( isset($file_action->url) && !filter_var($file_action->url, FILTER_VALIDATE_URL) === false){
                            if(isset($file_action->action) && $file_action->action === "exclude" && isset($file_action->regex) && !empty($file_action->regex)){
                                if(preg_match($file_action->regex, $link->src)){
                                    $value = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            return $value;
        }, 10 , 5);

        add_action('rapidload/enqueue/optimize-js', function ($link, $job, $strategy, $options){

            $options = $strategy === "mobile" ? $job->rapidload_job_get_mobile_options(true) : $job->rapidload_job_get_desktop_options(true);

            if(isset($options['individual-file-actions']) && !empty($options['individual-file-actions']) && is_array($options['individual-file-actions'])){

                foreach ($options['individual-file-actions'] as $file_action){
                    if(isset($file_action->type) && $file_action->type === "js"){
                        if( isset($file_action->url) && !filter_var($file_action->url, FILTER_VALIDATE_URL) === false){
                            if(isset($file_action->action)){
                                switch ($file_action->action){
                                    case 'defer' : {
                                        if(Javascript_Enqueue::is_js($link)){
                                            if(isset($file_action->regex) && $file_action->regex){
                                                if(preg_match($file_action->regex, $link->src)){
                                                   $link->defer = true;
                                                    unset($link->async);
                                                }
                                            }
                                        }elseif (Javascript_Enqueue::is_inline_script($link)){
                                            if(isset($file_action->regex) && $file_action->regex){
                                                if(preg_match($file_action->regex, $link->innertext())){
                                                    $link->__set('outertext','<script ' . ( $link->id ? 'id="' . $link->id . '"' : '' ) .' type="text/javascript"> window.addEventListener("DOMContentLoaded", function() { ' . $link->innertext() . ' }); </script>');
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'delay' : {
                                        if(Javascript_Enqueue::is_js($link)){
                                            if(isset($file_action->regex) && $file_action->regex){
                                                if(preg_match($file_action->regex, $link->src)){
                                                    $data_attr = "data-rapidload-src";
                                                    $link->{$data_attr} = $link->src;
                                                    unset($link->src);
                                                    $this->add_delay_script();
                                                }
                                            }
                                        }elseif (Javascript_Enqueue::is_inline_script($link)){
                                            if(isset($file_action->regex) && $file_action->regex){
                                                if(preg_match($file_action->regex, $link->innertext())){
                                                    $link->__set('outertext',"<noscript data-rapidload-delayed>" . $link->innertext() . "</noscript>");
                                                    $this->add_delay_script();
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'remove' : {
                                        if(Javascript_Enqueue::is_js($link)){

                                            if(isset($file_action->regex) && $file_action->regex){
                                                if(preg_match($file_action->regex, $link->src)){

                                                    if($link->{'data-rapidload-removed'}){
                                                        return;
                                                    }
                                                    $link->{'data-rapidload-removed'} = true;
                                                    $link->__set('outertext',"<noscript data-rapidload-removed>" . $link->outertext() . "</noscript>");
                                                }
                                            }
                                        }elseif (Javascript_Enqueue::is_inline_script($link)){
                                            if(isset($file_action->regex) && $file_action->regex){
                                                if(preg_match($file_action->regex, $link->innertext())){
                                                    if($link->{'data-rapidload-removed'}){
                                                        return;
                                                    }
                                                    $link->{'data-rapidload-removed'} = true;
                                                    $link->__set('outertext',"<noscript data-rapidload-removed>" . $link->innertext() . "</noscript>");
                                                }
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

        }, 10 , 4);
    }

    public function add_delay_script(){
        if(!self::$filter_added){
            add_filter('rapidload/delay-script/enable', function (){
                return true;
            });
        }
    }

}