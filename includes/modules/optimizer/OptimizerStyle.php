<?php

class OptimizerStyle
{
    public function __construct(){

        add_action('rapidload/enqueue/after-optimize-css', function ($sheet, $job_data, $strategy){

            $options = $strategy === "mobile" ? $job_data->job->get_mobile_options(true) : $job_data->job->get_desktop_options(true);

            if(isset($options['individual-file-actions']) && !empty($options['individual-file-actions']) && is_array($options['individual-file-actions'])){
                foreach ($options['individual-file-actions'] as $file_action){
                    if(isset($file_action->type) && $file_action->type === "css"){
                        if( isset($file_action->url) && !filter_var($file_action->url, FILTER_VALIDATE_URL) === false){
                            if(isset($file_action->action) && $file_action->action === "remove"){
                                if(isset($file_action->regex) && $file_action->regex && preg_match($file_action->regex, $sheet->href)){
                                    unset($sheet->rel);
                                    unset($sheet->href);
                                }

                            }
                        }
                    }
                }
            }

        }, 10 , 3);

        add_action('rapidload/enqueue/after-minify-css', function ($sheet, $job, $strategy){

            $options = $strategy === "mobile" ? $job->get_mobile_options(true) : $job->get_desktop_options(true);

            if(isset($options['individual-file-actions']) && !empty($options['individual-file-actions']) && is_array($options['individual-file-actions'])){
                foreach ($options['individual-file-actions'] as $file_action){
                    if(isset($file_action->type) && $file_action->type === "css"){
                        if( isset($file_action->url) && !filter_var($file_action->url, FILTER_VALIDATE_URL) === false){
                            if(isset($file_action->action) && $file_action->action === "remove"){
                                if(isset($file_action->regex) && $file_action->regex && preg_match($file_action->regex, $sheet->href)){
                                    unset($sheet->rel);
                                    unset($sheet->href);
                                }

                            }
                        }
                    }
                }
            }

        }, 10 , 3);

    }
}