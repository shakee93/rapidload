<?php

class OptimizerFont
{

    public function __construct()
    {
        add_filter('rapidload/enqueue/preload/fonts', function ($urls, $job, $strategy){

            $options = $strategy === "mobile" ? $job->get_mobile_options(true) : $job->get_desktop_options(true);

            if(isset($options['individual-file-actions']) && !empty($options['individual-file-actions']) && is_array($options['individual-file-actions'])){
                foreach ($options['individual-file-actions'] as $file_action){
                    if(isset($file_action->type) && $file_action->type === "font"){
                        if( isset($file_action->url) && !filter_var($file_action->url, FILTER_VALIDATE_URL) === false){
                            if(isset($file_action->action) && $file_action->action === "preload"){
                                $urls[] = $file_action->url;
                            }
                        }
                    }
                }
            }

            return $urls;
        }, 10, 3);
    }

}