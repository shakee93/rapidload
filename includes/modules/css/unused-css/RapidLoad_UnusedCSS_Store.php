<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_UnusedCSS_Store')){
    return;
}

class RapidLoad_UnusedCSS_Store
{
    use RapidLoad_Utils;

    public $job_data;
    public $args;
    public $options;

    public $purged_css;
    public $result;

    /**
     * @var WP_Filesystem_Direct
     */
    public $file_system;

    public function __construct($job_data, $args){

        $this->job_data = $job_data;
        $this->args = $args;
        $this->options = RapidLoad_Base::rapidload_get_merged_options();
        $this->file_system = new RapidLoad_FileSystem();

    }

    public function rapidload_purge_css() {

        $uucss_api = new RapidLoad_Api();

        if(isset($this->args['immediate']) || isset($this->args['titan'])){

            if(isset($this->args['titan']) && ($this->job_data->status === 'waiting' || $this->job_data->status === 'processing' || $this->job_data->status === 'success')){
                return;
            }

            $api_options = ( isset( $this->args['options'] ) ) ? $this->args['options'] : [];

            if(empty($api_options)){

                $uucss = RapidLoad_Base::rapidload_get()->rapidload_modules()->rapidload_get_module_instance('unused-css');

                if($uucss){
                    $api_options = $uucss->rapidload_api_options(url_to_postid($this->job_data->job->url));
                }

            }

            $uucss_config = apply_filters('uucss/purge/config', $api_options);

            $result = $uucss_api->rapidload_api_post( 'purger',
                array_merge( $uucss_config,
                    [
                        'url' => $this->job_data->job->url,
                        'service' => true
                    ]
                ) );

            if ( ! isset( $result ) || isset( $result->errors ) || ( gettype( $result ) === 'string' && strpos( $result, 'cURL error' ) !== false ) ) {

                $this->job_data->rapidload_job_data_mark_as_failed($uucss_api->rapidload_api_extract_error( $result ));
                $this->job_data->rapidload_job_data_save();

                return;
            }

            $this->result       = $result;
            $this->purged_css = $result->data;

            $this->rapidload_cache_files($this->purged_css);
            $this->rapidload_uucss_cached($this->job_data->job->url);

        }else{

            $api_options = ( isset( $this->args['options'] ) ) ? $this->args['options'] : [];

            if(empty($api_options)){

                $uucss = RapidLoad_Base::rapidload_get()->rapidload_modules()->rapidload_get_module_instance('unused-css');

                if($uucss){
                    $api_options = $uucss->rapidload_api_options(url_to_postid($this->job_data->job->url));
                }

            }

            $uucss_config = apply_filters('uucss/purge/config', $api_options);

            $result = $uucss_api->rapidload_api_post( 's/unusedcss',
                array_merge( $uucss_config,
                    [
                        'url' => $this->job_data->job->url,
                        'priority' => isset($this->args['priority']),
                        'wp_nonce' => wp_create_nonce('uucss_job_hook'),
                        'hook_end_point' => trailingslashit(get_site_url()),
                        'immediate' => true
                    ]
                ) );

            if($uucss_api->rapidload_api_is_error($result)){

                $this->job_data->rapidload_job_data_mark_as_failed($uucss_api->rapidload_api_extract_error( $result ));
                $this->job_data->rapidload_job_data_save();

                self::rapidload_util_log( [
                    'log' => 'fetched data stored status failed',
                    'url' => $this->job_data->job->url,
                    'type' => 'uucss-cron'
                ] );

                return;
            }

            if(isset($result->id)){

                $this->job_data->queue_job_id = $result->id;
                $this->job_data->status = 'waiting';
                $this->job_data->rapidload_job_data_save();

            }else if($result->completed){

                $this->result       = $result;
                $this->purged_css = $result->data;

                $this->rapidload_cache_files($this->purged_css);
                $this->rapidload_uucss_cached($this->job_data->job->url);

            }

        }


    }

    public function rapidload_hashed_file_name( $file, $content ) {

        $hash_made_from            = $this->options;
        $hash_made_from['content'] = $content;

        return $this->rapidload_util_file_name( $file, $hash_made_from );
    }

    public function rapidload_append_cache_file_dir( $file, $content ) {
        return RapidLoad_UnusedCSS::$base_dir . '/' . $this->rapidload_hashed_file_name( $file, $content );
    }

    function rapidload_cache_files($purged_files, $result = false){

        if(!isset($purged_files) || !is_array($purged_files)){
            $this->job_data->rapidload_job_data_mark_as_failed('Unknown error occurred');
            $this->job_data->rapidload_job_data_save();
            return;
        }

        if($result){
            $this->result = $result;
        }

        $warnings = isset($this->result) && isset($this->result->meta) ? $this->result->meta->warnings : null;
        $stats = isset($this->result) && isset($this->result->meta) ? $this->result->meta->stats : null;

        if(isset($this->result->meta->stats) && isset($this->result->meta->stats->using) && in_array('rapidload', $this->result->meta->stats->using)){

            $warnings[] = [
                "message" => "Clear your page cache"
            ];
        }

        $files              = [];

        foreach ( $purged_files as $file ) {

            if ( $this->rapidload_util_is_uucss_file( $file->file ) ) {
                continue;
            }

            if ( $this->rapidload_util_is_file_excluded( $this->options, $file->file ) ) {
                continue;
            }

            if(!$this->rapidload_util_str_contains($file->file,'//inline-style@'))
            {

                $file->css = apply_filters('rapidload/cache_file_creating/css', $file->css);

                $file->css = $this->rapidload_handleFontFace($file->css);

                $file_location = $this->rapidload_append_cache_file_dir( $file->file, $file->css );

                $files[] = [
                    'original' => $file->file,
                    'uucss'    => $this->rapidload_hashed_file_name( $file->file, $file->css ),
                    'original_relative' => self::rapidload_util_get_relative_url($file->file)
                ];

                $css = '';

                if ( $this->rapidload_util_endsWith($file->file, '.php')) {
                    $css = '<?php header("Content-type: text/css; charset=utf-8"); ?>';
                }

                $css .=  $file->css;

                $this->file_system->rapidload_file_put_contents( $file_location, $css );

                do_action( 'uucss/cache_file_created', $file_location, $file->css );

            }else{

                $files[] = [
                    'original' => $file->file,
                    'uucss'    => $file->css,
                ];
            }

        }

        if($this->job_data){

            $this->job_data->rapidload_job_data_mark_as_success($files, $stats, $warnings, 'success', 'uucss');
            $this->job_data->rapidload_job_data_save();
            $this->rapidload_uucss_cached($this->job_data->job->url);

        }

    }

    public function rapidload_uucss_cached($url){
        do_action( 'uucss/cached', [
            'url' => $url
        ]);
    }

    function rapidload_update_css(){

        if(!$this->job_data->queue_job_id || $this->job_data->status === "success" || $this->job_data->status === "failed"){
            return;
        }

        $uucss_api = new RapidLoad_Api();

        $result = $uucss_api->rapidload_api_get( 's/unusedcss/' . $this->job_data->queue_job_id);

        if ( ! isset( $result ) || isset( $result->errors ) || ( gettype( $result ) === 'string' && strpos( $result, 'cURL error' ) !== false ) ) {

            $error = $uucss_api->rapidload_api_extract_error( $result );

            if(isset($error['message']) && ($error['message'] === 'Job processing failed in queue' || $error['message'] === 'Error')){

                self::rapidload_util_log([
                    'log' =>  'requeue-> uucss job processing failed in queue',
                    'url' => $this->job_data->job->url,
                ]);
                $this->job_data->rapidload_job_data_requeue();
                $this->job_data->rapidload_job_data_save();

                return;
            }

            $this->job_data->rapidload_job_data_mark_as_failed($error);
            $this->job_data->rapidload_job_data_save();

            do_action( 'uucss/cache_cleared', [
                'url' => $this->job_data->job->url
            ]);

            return;
        }

        if(isset($result->state) && $result->state === 'failed'){

            $this->job_data->rapidload_job_data_mark_as_failed('Unknown error occurred');
            $this->job_data->rapidload_job_data_save();

            do_action( 'uucss/cache_cleared', [
                'url' => $this->job_data->job->url
            ]);

            return;
        }

        if(isset($result->state)){

            if($result->state === 'waiting' || $result->state === 'delayed' || $result->state === 'created' || $result->state === 'stalling'){
                $this->job_data->status = 'waiting';
                $this->job_data->rapidload_job_data_save();
            }else if($result->state === 'active'){
                $this->job_data->status = 'processing';
                $this->job_data->rapidload_job_data_save();
            }

        }

        if(isset($result->completed) && $result->completed){

            $this->rapidload_cache_files($result->data, $result);

        }
    }

    function rapidload_handleFontFace($content){

        if(isset($this->options['uucss_enable_font_optimization']) && $this->options['uucss_enable_font_optimization'] === "1"){
            return RapidLoad_Font::rapidload_add_display_swap($content);
        }
        return  $content;

    }
}