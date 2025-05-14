<?php

defined( 'ABSPATH' ) or die();
class RapidLoad_CriticalCSS_Store
{
    use RapidLoad_Utils;

    public $job_data;
    public $args;
    public $options;

    public $purged_css;
    public $purged_mobile_css;
    public $result;

    /**
     * @var WP_Filesystem_Direct
     */
    public $file_system;

    public function __construct($job_data, $args){

        $this->job_data = $job_data;
        $this->args = $args;
        $this->options = RapidLoad_Base::get_merged_options();
        $this->file_system = new RapidLoad_FileSystem();

    }

    function purge_css(){

        $uucss_api = new RapidLoad_Api();

        if(isset($this->args['immediate'])){

            $discontinue = false;

            if(!isset($this->args['titan']) && ($this->job_data->status === 'waiting' || $this->job_data->status === 'processing' || $this->job_data->status === 'success')){
                return;
            }

            if(isset($this->args['titan']) && isset($this->args['options']) && isset($this->args['options']['strategy'])){

                $strategy = $this->args['options']['strategy'];
                $cpcss_data = $this->job_data->get_cpcss_data();

                if(isset($cpcss_data[$strategy]) && !empty($cpcss_data[$strategy]) && ($this->job_data->status === 'success' || $this->job_data->status === 'processing')){
                    $discontinue = true;
                }

            }

            if($discontinue){
                return;
            }

            $cpcss_config = apply_filters('cpcss/purge/config', ( isset( $this->args['options'] ) ) ? $this->args['options'] : []);

            $result = $uucss_api->post( 'purger/cpcss',
                array_merge( $cpcss_config,
                    [
                        'url' => $this->job_data->job->url,
                        'service' => true,
                        'mobile_device' => true,
                        "cacheBusting"          => isset($this->options['uucss_cache_busting_v2']) ? apply_filters('uucss/cache/bust',[]) : [],
                        "ignoreInlinedStyles" => isset($this->options['uucss_ignore_inlined_styles']) ? !($this->options['uucss_ignore_inlined_styles'] === "1") : true
                    ]
                ) );

            if ( ! isset( $result ) || isset( $result->errors ) || ( gettype( $result ) === 'string' && strpos( $result, 'cURL error' ) !== false ) ) {

                $this->job_data->mark_as_failed($uucss_api->extract_error( $result ));
                $this->job_data->save();

                return;
            }

            $this->result       = $result;
            $this->purged_css = $result->data;
            $this->purged_mobile_css = $result->data_mobile;

            $this->cache_file($this->purged_css, $this->purged_mobile_css);
            $this->cpcss_cached($this->job_data->job->url);

        }else{

            $discontinue = false;

            if(isset($this->args['titan']) && isset($this->args['options']) && isset($this->args['options']['strategy'])){

                $strategy = $this->args['options']['strategy'];
                $cpcss_data = $this->job_data->get_cpcss_data();

                if(isset($cpcss_data[$strategy]) && !empty($cpcss_data[$strategy]) && ($this->job_data->status === 'success' || $this->job_data->status === 'processing')){
                    $discontinue = true;
                }

            }

            if($discontinue){
                return;
            }

            $cpcss_config = apply_filters('cpcss/purge/config', ( isset( $this->args['options'] ) ) ? $this->args['options'] : []);

            $result = $uucss_api->post( 's/criticalcss',
                array_merge( $cpcss_config,
                    [
                        'url' => $this->job_data->job->url,
                        'priority' => isset($this->args['priority']),
                        'mobile_device' => true,
                        'wp_nonce' => wp_create_nonce('uucss_job_hook'),
                        'hook_end_point' => trailingslashit(get_site_url()),
                        'immediate' => true,
                        "cacheBusting"          => isset($this->options['uucss_cache_busting_v2']) ? apply_filters('uucss/cache/bust',[]) : [],
                        "ignoreInlinedStyles" => isset($this->options['uucss_ignore_inlined_styles']) ? !($this->options['uucss_ignore_inlined_styles'] === "1") : true
                    ]

                ) );

            if($uucss_api->is_error($result)){

                $this->job_data->mark_as_failed($uucss_api->extract_error( $result ));
                $this->job_data->save();

                $this->log( [
                    'log' => 'fetched data stored status failed',
                    'url' => $this->job_data->job->url,
                    'type' => 'uucss-cron'
                ] );

                return;
            }

            if(isset($result->id)){

                $this->job_data->queue_job_id = $result->id;
                $this->job_data->status = 'waiting';
                $this->job_data->save();

            }else if($result->completed){

                $this->result       = $result;
                $this->purged_css = $result->data;
                $this->purged_mobile_css = $result->data_mobile;

                $this->cache_file($this->purged_css, $this->purged_mobile_css);
                $this->cpcss_cached($this->job_data->job->url);

            }

        }
    }

    function cache_file($purged_css, $purged_mobile = false, $result = false) {
        if ($result) {
            $this->result = $result;
        }

        $warnings = isset($this->result->meta->warnings) ? $this->result->meta->warnings : array();

        if (isset($this->result->meta->stats->using) && in_array('rapidload', $this->result->meta->stats->using)) {
            $warnings[] = array("message" => "Clear your page cache");
        }

        $data = $this->job_data->get_cpcss_data();
        $file_chunk_enabled = isset($this->options['rapidload_enable_cpcss_file_chunk']) && $this->options['rapidload_enable_cpcss_file_chunk'] === "1";
        $file_character_length = $file_chunk_enabled && isset($this->options['rapidload_cpcss_file_character_length']) ? $this->options['rapidload_cpcss_file_character_length'] : 0;
        $file_character_length = apply_filters('rapidload/cpcss/file-character-length', $file_character_length);

        if (!empty($purged_css)) {
            $purged_css = apply_filters('rapidload/cache_file_creating/css', $purged_css);
            $data['desktop'] = $this->handle_css_parts($purged_css, '', $file_character_length);
        }

        if (!empty($purged_mobile)) {
            $purged_mobile = apply_filters('rapidload/cache_file_creating/css', $purged_mobile);
            $data['mobile'] = $this->handle_css_parts($purged_mobile, '-mobile', $file_character_length);
        }

        if ($this->job_data) {
            $this->job_data->mark_as_success($data, null, $warnings, 'success', 'cpcss');
            $this->job_data->save();
            $this->cpcss_cached($this->job_data->job->url);
        }
    }

    function handle_css_parts($css, $suffix, $file_character_length) {

        if($file_character_length === "0" || $file_character_length === 0){
            $file_name = 'cpcss-' . $this->encode($css) . $suffix . '.css';
            if (!$this->file_system->exists(RapidLoad_CriticalCSS::$base_dir . '/' . $file_name)) {
                $this->file_system->put_contents(RapidLoad_CriticalCSS::$base_dir . '/' . $file_name, $css);
            }
            return $file_name;
        }

        $parts = RapidLoad_CriticalCSS::breakCSSIntoParts($css, $file_character_length);
        $file_count = count($parts);
        $file_suffix = $file_count > 1 ? "[$file_count]" : "";

        $file_name = 'cpcss-' . $this->encode($css) . $suffix . '.css';
        $final_file_name = str_replace('.css', $file_suffix . '.css', $file_name);

        foreach ($parts as $index => $part) {
            $part_file_name = $index === 0 ? $file_name : str_replace('.css', '-' . ($index + 1) . '.css', $file_name);
            if (!$this->file_system->exists(RapidLoad_CriticalCSS::$base_dir . '/' . $part_file_name)) {
                $this->file_system->put_contents(RapidLoad_CriticalCSS::$base_dir . '/' . $part_file_name, $part);
            }
        }

        return $final_file_name;
    }


    public function cpcss_cached($url){
        do_action( 'uucss/cached', [
            'url' => $url
        ]);
    }

    function update_css(){

        if(!$this->job_data->queue_job_id || $this->job_data->status === "success" || $this->job_data->status === "failed"){
            return;
        }

        $uucss_api = new RapidLoad_Api();

        $result = $uucss_api->get( 's/criticalcss/' . $this->job_data->queue_job_id);

        if ( ! isset( $result ) || isset( $result->errors ) || ( gettype( $result ) === 'string' && strpos( $result, 'cURL error' ) !== false ) ) {

            $error = $uucss_api->extract_error( $result );

            if(isset($error['message']) && ($error['message'] === 'Job processing failed in queue' || $error['message'] === 'Error')){

                self::log([
                    'log' =>  'requeue-> cpcss job processing failed in queue',
                    'url' => $this->job_data->job->url,
                ]);
                $this->job_data->requeue();
                $this->job_data->save();

                return;
            }

            $this->job_data->mark_as_failed($error);
            $this->job_data->save();

            do_action( 'uucss/cache_cleared', [
                'url' => $this->job_data->job->url
            ]);

            return;
        }

        if(isset($result->state) && $result->state === 'failed'){

            $this->job_data->mark_as_failed('Unknown error occurred');
            $this->job_data->save();

            do_action( 'uucss/cache_cleared', [
                'url' => $this->job_data->job->url
            ]);

            return;
        }

        if(isset($result->state)){

            if($result->state === 'waiting' || $result->state === 'delayed' || $result->state === 'created' || $result->state === 'stalling'){
                $this->job_data->status = 'waiting';
                $this->job_data->save();
            }else if($result->state === 'active'){
                $this->job_data->status = 'processing';
                $this->job_data->save();
            }

        }

        if(isset($result->completed) && $result->completed){

            if(!isset($result->data) && !isset($result->data_mobile)){
                error_log('Critical css content is empty');
                $this->job_data->mark_as_failed([
                    'code' => 500,
                    'message' => 'Critical css content is empty'
                ]);
                $this->job_data->save();

                do_action( 'uucss/cache_cleared', [
                    'url' => $this->job_data->job->url
                ]);

                return;
            }
            $this->cache_file($result->data, $result->data_mobile, $result);

        }
    }
}