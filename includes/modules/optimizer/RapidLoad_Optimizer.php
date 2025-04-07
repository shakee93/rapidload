<?php

class RapidLoad_Optimizer
{

    use RapidLoad_Utils;

    static $metrics = ['render-blocking-resources','uses-responsive-images','offscreen-images','unminified-css','unminified-javascript','unused-css-rules',
        'unused-javascript','uses-optimized-images','modern-image-formats','uses-text-compression','uses-rel-preconnect','server-response-time',
        'redirects','uses-rel-preload','efficient-animated-content','duplicated-javascript','legacy-javascript','preload-lcp-image',
        'total-byte-weight','uses-long-cache-ttl','dom-size','user-timings','bootup-time','mainthread-work-breakdown','font-display',
        'third-party-summary','third-party-facades','lcp-lazy-loaded','layout-shift-elements','uses-passive-event-listeners','no-document-write',
        'long-tasks','non-composited-animations','unsized-images','viewport','no-unload-listeners',
        'critical-request-chains',
        'resource-summary',
        'largest-contentful-paint-element'
    ];

    static $global_options;
    static $revision_limit = 10;

    static $global;
    static $options;
    static $previous_options;
    static $merged_options = [];
    static $job;
    static $strategy;

    public function __construct(){

        self::$global_options = RapidLoad_Base::fetch_options();

        $this->registerAjaxActions();

        if (!$this->isOptimizerEnabled()) {
            return;
        }

        $this->initializeOptimizers();

        self::$revision_limit = apply_filters('rapidload/optimizer/revision-limit', 10);

        add_filter('uucss/enqueue/content/update', [$this, 'update_content'], 90);

    }

    public function registerAjaxActions()
    {
        $actions = [
            'fetch_page_speed' => 'handle_ajax_fetch_page_speed',
            'latest_page_speed' => 'latest_page_speed', 
            'preload_page' => 'preload_page',
            'rapidload_css_job_status' => 'rapidload_css_job_status',
            'fetch_titan_settings' => 'fetch_titan_settings',
            'update_titan_settings' => 'update_titan_settings',
            'get_rapidload_cdn_usage' => 'get_rapidload_cdn_usage',
            'get_rapidload_image_usage' => 'get_rapidload_image_usage',
            'get_cache_file_size' => 'get_cache_file_size',
            'update_titan_performance_gear' => 'update_titan_performance_gear',
            'rapidload_titan_home_page_performance' => 'rapidload_titan_home_page_performance',
            'rapidload_get_active_plugins' => 'rapidload_get_active_plugins',
            'rapidload_diagnose_data' => 'rapidload_diagnose_data',
            'rapidload_server_info' => 'rapidload_server_info',
            'rapidload_privacy_policy_acceptance' => 'rapidload_privacy_policy_acceptance',
        ];

        foreach ($actions as $action => $method) {
            add_action("wp_ajax_$action", [$this, $method]);

            if (defined('RAPIDLOAD_DEV_MODE')) {
                add_action("wp_ajax_nopriv_$action", [$this, $method]);
            }
        }
    }

    public function rapidload_privacy_policy_acceptance(){
        self::verify_nonce();
        $accepted = isset($_REQUEST['accepted']) && boolval(sanitize_text_field(wp_unslash($_REQUEST['accepted']))) ? true : false;
        update_option('rapidload_privacy_policy_accepted', $accepted);
        wp_send_json_success('success');
    }

    public function rapidload_server_info(){

        self::verify_nonce();

        // check if crawler is working

        $response = [
            'constants' => []
        ];

        $api = new RapidLoad_Api();

        $result = $api->post('crawler-check',[
            'url' => site_url()
        ]);

        if(is_wp_error($result)){
            $response['crawler_check'] = $result->get_error_message();
        }else{
            $response['crawler_check'] = $result;
        }

        // check if there are any conflict plugins

        $cnflict_plugins = apply_filters('uucss/third-party/plugins',[]);

        $plugins = get_plugins();

        $conflict_plugin_names = [];

        foreach($cnflict_plugins as $cnflict_plugin){

            if(isset($cnflict_plugin['has_conflict']) && $cnflict_plugin['has_conflict'] && isset($plugins[$cnflict_plugin['path']])){
                $conflict_plugin_names[] = $plugins[$cnflict_plugin['path']]['Name'];
            }
        }

        if(!empty($conflict_plugin_names)){
            $response['conflict_plugins'] = $conflict_plugin_names;
        }

        // check if there are any cron issues

        $cron_status = $this->get_cron_spawn();

        if(is_wp_error($cron_status)){
            $response['cron_status'] = $cron_status->get_error_message();
        }

        $code = wp_remote_retrieve_response_code( $cron_status );
        $message = wp_remote_retrieve_response_message( $cron_status );
        
        // Update cron status with actual code
        $response['cron_status'] = [
            'code' => $code,
            'message' => $message,
        ];

        $response['constants']['DISABLE_WP_CRON'] = defined('DISABLE_WP_CRON') ? DISABLE_WP_CRON : false;

        $response['constants']['ALTERNATE_WP_CRON'] = defined('ALTERNATE_WP_CRON') ? ALTERNATE_WP_CRON : false;

        $response['constants']['DONOTCACHEPAGE'] = defined('DONOTCACHEPAGE') ? DONOTCACHEPAGE : false;

        $response['constants']['WP_CACHE'] = defined('WP_CACHE') ? WP_CACHE : false;

        $response['constants']['WP_CRON_LOCK_TIMEOUT'] = defined('WP_CRON_LOCK_TIMEOUT') ? WP_CRON_LOCK_TIMEOUT : false;

        $response['server'] = array(
            'server_software' => sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ),
            'php_version' => PHP_VERSION,
        );

        // return all the data

        wp_send_json_success($response);
    }

    public function rapidload_diagnose_data(){

        self::verify_nonce();

        $url = isset($_REQUEST['url']) ? sanitize_url(wp_unslash($_REQUEST['url'])) : $this->transform_url(site_url());
        $strategy = isset($_REQUEST['strategy']) ? sanitize_text_field(wp_unslash($_REQUEST['strategy'])) : 'desktop';

        $body = file_get_contents('php://input');

        $data = ($body) ? json_decode($body) : null;

        $job = new RapidLoad_Job([
            'url' => $url
        ]);

        if(!isset($job->id)){
            wp_send_json_error('job not found');
        }

        if($data){
            $diagnose_data = $job->get_diagnose_data();
            if($strategy == 'desktop'){
                $diagnose_data['desktop'] = $data;
                $job->set_diagnose_data($diagnose_data);
            }else{
                $diagnose_data['mobile'] = $data;
                $job->set_diagnose_data($diagnose_data);
            }
            $job->save();
        }

        wp_send_json_success([
            'diagnose_data' => $job->get_diagnose_data(),
            'strategy' => $strategy
        ]);
    }

    public function rapidload_get_active_plugins(){
        wp_send_json_success(RapidLoad_Utils::get_active_plugins());
    }

    public function rapidload_titan_home_page_performance(){

        $performance_data = RapidLoad_Job::get_first_and_last_optimization_score($this->transform_url(site_url()), 'desktop');

        wp_send_json_success($performance_data);
    }

    public function get_cache_file_size() {

        $cache_folders = [
            'page_cache_dir' => [
                'path' => RapidLoad_Cache_Store::get_cache_dir(site_url()),
                'label' => 'Page Cache',
                'action' => [
                    'href' => wp_nonce_url( add_query_arg( array(
                        '_cache'  => 'rapidload-cache',
                        '_action' => 'clear',
                    ) ,admin_url('admin.php') ), 'rapidload_cache_clear_cache_nonce' ),
                    'label' => 'Clear Page Cache'
                ]
            ],
            'js_cache_dir' => [
                'path' => JavaScript::$base_dir,
                'label' => 'JavaScript Cache',
                'action' => [
                    'href' => wp_nonce_url( add_query_arg( array(
                        'action' => 'rapidload_purge_all',
                        'job_type' => 'js',
                        'clear' => true,
                    ) , admin_url( 'admin-ajax.php' )), 'uucss_nonce', 'nonce' ),
                    'label' => 'Clear JavaScript Cache'
                ]
            ],
            'font_cache_dir' => [
                'path' => RapidLoad_Font::$base_dir,
                'label' => 'Font Cache',
                'action' => [
                    'href' => wp_nonce_url( add_query_arg( array(
                        'action' => 'rapidload_purge_all',
                        'job_type' => 'fonts',
                        'clear' => true,
                    ) , admin_url('admin-ajax.php')), 'uucss_nonce', 'nonce' ),
                    'label' => 'Clear Font Cache'
                ]
            ]
        ];

        $css_folders = [
            'cpcss_cache_dir' => [
                'path' => CriticalCSS::$base_dir,
                'label' => 'Critical CSS Cache'
            ],
            'uucss_cache_dir' => [
                'path' => UnusedCSS::$base_dir,
                'label' => 'Unused CSS Cache'
            ],
            'minified_css_cache_dir' => [
                'path' => MinifyCSS::$base_dir,
                'label' => 'Minified CSS Cache'
            ]
        ];

        $folder_sizes = [];
        $file_system = RapidLoad_Base::get_log_instance();

        foreach ($cache_folders as $key => $folder) {
            if (isset($folder['path']) && file_exists($folder['path'])) {
                $folder_sizes[] = [
                    'key' => $key,
                    'label' => $folder['label'],
                    'size' => [
                        'folder_name' => $folder['path'],
                        'size' => $file_system->get_folder_size($folder['path'])
                    ],
                    'action' => $folder['action']
                ];
            } else {
                $folder_sizes[] = [
                    'key' => $key,
                    'label' => $folder['label'], 
                    'size' => [
                        'folder_name' => $folder['path'],
                        'size' => '0 KB'
                    ],
                    'action' => $folder['action']
                ];
            }
        }

        $css_total_size = 0;
        foreach ($css_folders as $folder) {
            if (file_exists($folder['path'])) {
                $css_total_size += $file_system->get_folder_size_in_bytes($folder['path']);
            }
        }

        $folder_sizes[] = [
            'key' => 'css_file_size',
            'label' => 'CSS Cache',
            'size' => [
                'folder_name' => 'CSS Cache Folders (Combined)',
                'size' => $file_system->format_size_units($css_total_size)
            ],
            'action' => [
                'href' => wp_nonce_url( add_query_arg( array(
                    'action' => 'rapidload_purge_all',
                    'job_type' => 'css',
                    'clear' => true,
                ) , admin_url('admin-ajax.php')), 'uucss_nonce', 'nonce' ),
                'label' => 'Clear CSS Cache'
            ]
        ];

        wp_send_json_success((array)$folder_sizes);
    }

    public function get_rapidload_cdn_usage(){

        if(!isset(self::$global_options['uucss_cdn_zone_id'])){
            wp_send_json_error('zone id not found');
        }

        $api = new RapidLoad_Api();

        $result = $api->post('get_cdn_usage',[
            'zone_id' => self::$global_options['uucss_cdn_zone_id']
        ]);

        if(is_wp_error($result)){
            wp_send_json_error($api->extract_error($result));
        }

        if(!isset($result->requested_usage)){
            wp_send_json_error("Requested Usage not found");
        }

        wp_send_json_success($result->requested_usage);
    }

    public function get_rapidload_image_usage(){

        $api = new RapidLoad_Api();

        $result = $api->post('get_image_usage',[
            'url' => site_url()
        ]);

        if(is_wp_error($result)){
            wp_send_json_error($api->extract_error($result));
        }

        if(!isset($result->requested_usage)){
            wp_send_json_error("Requested Usage not found");
        }

        wp_send_json_success($result->requested_usage);
    }

    public function isOptimizerEnabled()
    {
        return RapidLoad_DB::$current_version >= 1.6;
    }

    public function initializeOptimizers()
    {
        if (!defined('RAPIDLOAD_PAGE_OPTIMIZER_ENABLED')) {
            define('RAPIDLOAD_PAGE_OPTIMIZER_ENABLED', true);
        }

        new OptimizerFont();
        new OptimizerJS();
        new OptimizerImage();
        new OptimizerStyle();
    }

    public function update_content($state){

        self::debug_log('doing titan');

        if(isset($state['dom']) && isset($_REQUEST['rapidload_preview'])){

            $head = $state['dom']->find('head', 0);

            $front_end_data = apply_filters('rapidload/optimizer/frontend/data',[
                'server' => $this->get_server_type()
            ]);

            $script = '<script id="rapidload-optimizer-status-script"> window.rapidload_preview_stats = ' . json_encode($front_end_data) . ';' . '</script>';
            $first_child = $head->last_child();
            $first_child->__set('outertext', $script . $first_child->outertext);

            $body = $state['dom']->find('body', 0);

            $frontend_data_content = '<div id="rapidload-preview-container"></div>';

            $last_child = $body->last_child();

            $last_child->__set('outertext', $last_child->outertext . $frontend_data_content);

        }

        return $state;
    }

    public function get_server_type() {
        // Check if the SERVER_SOFTWARE key exists
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            // Get the server software information
            $server_software = sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']));

            // Check if the server is Apache
            if (stripos($server_software, 'Apache') !== false) {
                return ['server_type' => 'Apache', 'full_info' => $server_software];
            }
            // Check if the server is Nginx
            elseif (stripos($server_software, 'nginx') !== false) {
                return ['server_type' => 'Nginx', 'full_info' => $server_software];
            }
            // Server type is unknown or another server
            else {
                return ['server_type' => 'Unknown', 'full_info' => $server_software];
            }
        } else {
            // Server software information is not available
            return ['server_type' => 'Unknown', 'full_info' => 'Not available'];
        }
    }

    public function rapidload_css_job_status(){

        self::verify_nonce();

        $url = isset($_REQUEST['url']) ? sanitize_url(wp_unslash($_REQUEST['url'])) : site_url();
        $types = isset($_REQUEST['types']) ? explode(",", sanitize_text_field(wp_unslash($_REQUEST['types']))) : [];

        $url = $this->transform_url($url);

        $job = new RapidLoad_Job([
            'url' => $url
        ]);

        if(!isset($job->id)){
            wp_send_json_error('url not found');
        }

        $response = [];

        foreach ($types as $type){
            switch ($type){
                case 'uucss' :{
                    $job_data_uucss = new RapidLoad_Job_Data($job,'uucss');
                    $response[$type] = [
                        'status' => isset($job_data_uucss->id) ? $job_data_uucss->status : 'queued',
                        'error' => isset($job_data_uucss->id) && isset($job_data_uucss->error) ? unserialize($job_data_uucss->error) : null,
                        'meta' => [
                            'warnings' => isset($job_data_uucss->id) && isset($job_data_uucss->warnings) ? unserialize($job_data_uucss->warnings) : null,
                            'stats' => isset($job_data_uucss->id) && isset($job_data_uucss->stats) ? unserialize($job_data_uucss->stats) : null
                        ]
                    ];
                    break;
                }
                case 'cpcss' :{
                    $job_data_cpcss = new RapidLoad_Job_Data($job,'cpcss');
                    $cpcss_data = $job_data_cpcss->get_cpcss_data();
                    $response[$type] = [
                        'status' => isset($job_data_cpcss->id) ? $job_data_cpcss->status : 'queued',
                        'error' => isset($job_data_cpcss->id) && isset($job_data_cpcss->error) ? unserialize($job_data_cpcss->error) : null,
                        'meta' => [
                            'warnings' => isset($job_data_cpcss->id) && isset($job_data_cpcss->warnings) ? unserialize($job_data_cpcss->warnings) : null,
                            'stats' => isset($job_data_cpcss->id) && isset($job_data_cpcss->stats) ? unserialize($job_data_cpcss->stats) : null,
                            'desktop' => isset($job_data_cpcss->id) && isset($cpcss_data['desktop']) && !empty($cpcss_data['desktop']) ? $cpcss_data['desktop'] : null,
                            'mobile' => isset($job_data_cpcss->id) && isset($cpcss_data['mobile']) && !empty($cpcss_data['mobile']) ? $cpcss_data['mobile'] : null,
                        ], 
                    ];
                    break;
                }
                case 'cache' :{

                    $cache_file = RapidLoad_Cache_Store::get_cache_file($url);
                    $cache_file_exist = @file_exists($cache_file);

                    $status = RapidLoad_Cache_Store::get_page_cache_errors();

                    $response[$type] = [
                        'status' => $cache_file_exist ? 'Hit' : ($status == 'Hit' ? $status : 'failed'),
                        'error' => [
                            'code' => $cache_file_exist ? null : 422,
                            'message' => $cache_file_exist ? 'Hit' : $status,
                        ],
                        'meta' => [
                            'file' => $cache_file,
                            'size' => $cache_file_exist ? $this->formatSize(@filesize($cache_file)) : null,
                        ]
                    ];
                    break;
                }
                case 'cache_policy':{

                    $response[$type] = RapidLoad_htaccess::has_rapidload_rules();
                }
            }
        }

        wp_send_json_success($response);

    }

    public function latest_page_speed(){

        self::verify_nonce();

        $job = new RapidLoad_Job([
            'url' => $this->transform_url(site_url())
        ]);
        if(!isset($job->id)){
            wp_send_json_error();
        }

        $last_metrics = $job->get_last_optimization_revision('desktop');

        if(!$last_metrics){
            $last_metrics = $job->get_last_optimization_revision('mobile');
        }

        wp_send_json_success($last_metrics);

    }

    public function update_titan_performance_gear(){

        self::verify_nonce();

        if(!isset($_REQUEST['titan_gear']) || empty($_REQUEST['titan_gear'])){
            wp_send_json_error('titan gear required');
        }

        if(!isset($_REQUEST['url']) || empty($_REQUEST['url'])){
            wp_send_json_error('url required');
        }

        $url = sanitize_url(wp_unslash($_REQUEST['url']));
        $titan_gear = sanitize_text_field(wp_unslash($_REQUEST['titan_gear']));

        if(!$this->is_valid_url($url)){
           wp_send_json_error('url not valid');
        }

        $strategy = isset($_REQUEST['strategy']) ? sanitize_text_field(wp_unslash($_REQUEST['strategy'])) : 'mobile';

        $global = isset($_REQUEST['global']) && boolval($_REQUEST['global']) || rtrim(strtolower($url),"/") == rtrim(strtolower(site_url()), "/");

        $this->pre_optimizer_function($url, $strategy, $global);

        $starter = ['uucss_enable_uucss', 'uucss_minify', 'minify_js', 'uucss_enable_cache', 'uucss_self_host_google_fonts'];

        $accelerate = array_merge($starter, ['uucss_enable_cdn', 'uucss_support_next_gen_formats', 'uucss_lazy_load_iframes', 'uucss_lazy_load_images', 'uucss_exclude_above_the_fold_images', 'uucss_set_width_and_height', 'uucss_load_js_method']);

        $turboMax = array_merge($accelerate, ['delay_javascript', 'uucss_enable_cpcss', 'uucss_support_next_gen_formats']);

        $setOptions = function($enabledOptions) {
            foreach (self::$options as $key => $value) {
                if (in_array($key, $enabledOptions)) {
                    self::$options[$key] = "1";
                } else {
                    unset(self::$options[$key]);
                }
            }
        };

        switch ($titan_gear) {
            case 'starter':
                $setOptions($starter);
                break;
            case 'accelerate':
                $setOptions($accelerate);
                break;
            case 'turboMax':
                $setOptions($turboMax);
                break;
        }

        self::post_optimizer_function(null);

        wp_send_json_success();
    }

    public function fetch_titan_settings(){

        self::verify_nonce();

        if(!isset($_REQUEST['url']) || empty($_REQUEST['url'])){
            wp_send_json_error('url required');
        }

        $url = sanitize_url(wp_unslash($_REQUEST['url']));

        if(!$this->is_valid_url($url)){
           wp_send_json_error('url not valid');
        }

        $strategy = isset($_REQUEST['strategy']) ? sanitize_text_field(wp_unslash($_REQUEST['strategy'])) : 'mobile';

        $this->pre_optimizer_function($url, $strategy, null, false);

        if(isset(self::$merged_options['uucss_api_key'])){
            unset(self::$merged_options['uucss_api_key']);
        }

        wp_send_json_success([
            'general' => [
                'performance_gear' => get_option('rapidload_titan_gear', false),
                'test_mode' => self::$global_options['rapidload_test_mode']
            ],
            'performance' => $this->transform_options_to_settings($url, self::$merged_options),
            'actions' => [
                [
                    'control_type' => 'button',
                    'category' => 'general',
                    'control_label' => 'Flush Cache',
                    'control_icon' => 'clear_page_cache',
                    'control_description' => 'Clear Page Cache',
                    'action' => wp_nonce_url( add_query_arg( array(
                        '_cache'  => 'rapidload-cache',
                        '_action' => 'clear',
                    ) ), 'rapidload_cache_clear_cache_nonce' )
                ]
            ]
        ]);
    }

    public function update_titan_settings()
    {

        self::verify_nonce();

        if(!isset($_REQUEST['url']) || empty($_REQUEST['url'])){
            wp_send_json_error('url required');
        }

        $url = sanitize_url(wp_unslash($_REQUEST['url']));

        if(!$this->is_valid_url($url)){
           wp_send_json_error('url not valid');
        }

        $strategy = isset($_REQUEST['strategy']) ? sanitize_text_field(wp_unslash($_REQUEST['strategy'])) : 'mobile';

        self::$global = isset($_REQUEST['global']) && (boolval($_REQUEST['global']) || rtrim(site_url(), "/") == rtrim($url, "/"));

        $this->pre_optimizer_function($url, $strategy, self::$global);

        if(isset(self::$merged_options['uucss_api_key'])){
            unset(self::$merged_options['uucss_api_key']);
        }

        $raw_body = file_get_contents('php://input');
        $body = json_decode(sanitize_text_field($raw_body));

        if(!isset($body) || !isset($body->settings) || !isset($body->settings->performance)){
            wp_send_json_error('Missing required data to save the settings!');
        }

        $this->optimizer_update_settings($body->settings->performance,$body->settings->general);

        wp_send_json_success('optimization updated successfully');

    }

    public function  pre_optimizer_function($url, $strategy, $global, $can_be_saved = false){

        $url = $this->transform_url(urldecode($url));

        self::$job = new RapidLoad_Job([
            'url' => $url
        ]);

        if(!isset(self::$job->id) && $can_be_saved){
            self::$job->save();
        }

        self::$global_options = RapidLoad_Base::fetch_options();

        self::$strategy = $strategy;

        self::$global = $global;

        self::$options = self::$strategy == "desktop" ? isset(self::$job->id) ? self::$job->get_desktop_options() : self::$job->get_mobile_options() : self::$global_options;

        self::$previous_options = self::$options;

        foreach (self::$global_options as $key => $value){
            self::$merged_options[$key] = $value;
        }

        foreach (self::$options as $key => $value){
            self::$merged_options[$key] = $value;
        }
    }

    public static function post_optimizer_function($data){

        foreach (self::$options as $key => $option){

            if(isset(self::$options[$key]) && (self::$options[$key] != "" && self::$options[$key] && !empty(self::$options[$key]))){
                switch ($key){
                    case 'uucss_enable_uucss':
                    case 'uucss_inline_css':
                    case 'uucss_enable_cpcss':
                    case 'uucss_minify':
                        if(self::$global){
                            RapidLoad_Base::update_option('rapidload_module_css',"1");
                        }
                        self::$options['uucss_enable_css'] = "1";
                        if($key == "uucss_enable_uucss"){
                            do_action('rapidload/uucss/job/handle', self::$job, [
                                'immediate' => true,
                                'titan' => true,
                            ]);
                        }
                        if($key == "uucss_enable_cpcss"){
                            do_action('rapidload/cpcss/job/handle', self::$job, [
                                'immediate' => true,
                                'titan' => true,
                                'options' => [
                                    'strategy' => self::$strategy
                                ]
                            ], [
                                'titan' => true,
                                'options' => [
                                    'strategy' => self::$strategy == "desktop" ? "mobile" : "desktop"
                                ]
                            ]);
                        }
                        break;
                    case 'uucss_self_host_google_fonts':
                        self::$options['uucss_enable_font_optimization'] = "1";
                        if(self::$global){
                            RapidLoad_Base::update_option('rapidload_module_font',"1");
                        }
                        break;
                    case 'defer_inline_js':
                    case 'delay_javascript':
                    case 'minify_js':
                    case 'uucss_load_js_method':
                        self::$options['uucss_enable_javascript'] = "1";
                        if(self::$global){
                            RapidLoad_Base::update_option('rapidload_module_js',"1");
                        }
                        break;
                    case 'uucss_support_next_gen_formats':
                    case 'uucss_set_width_and_height':
                    case 'uucss_lazy_load_images':
                    case 'uucss_lazy_load_iframes':
                    case 'uucss_exclude_above_the_fold_images':
                        self::$options['uucss_enable_image_delivery'] = "1";
                        if(self::$global){
                            RapidLoad_Base::update_option('rapidload_module_image',"1");
                        }
                        break;
                }

            }

            $option_type = gettype(self::$options[$key]);

            if(isset(self::$global_options[$key])){
                if($option_type == "string" && self::$global_options[$key] == $option){
                    unset(self::$options[$key]);
                }
                else if (($option_type == "object" || $option_type == "array") && json_encode($option) == json_encode(self::$global_options[$key])){
                    unset(self::$options[$key]);
                }
            }

        }

        $preload_images = [];

        if (isset($data) && isset($data->audits) && is_array($data->audits)) {

            $lcp_audit = array_filter($data->audits, function($audit) {
                return $audit->id === 'prioritize-lcp-image';
            });

            if (!empty($lcp_audit)) {
                $lcp_audit = reset($lcp_audit);

                if (isset($lcp_audit->files) && isset($lcp_audit->files->debugData) && !empty($lcp_audit->files->debugData->initiatorPath)) {
                    foreach ($lcp_audit->files->debugData->initiatorPath as $path) {
                        if (isset($path->url) && preg_match('/\.(jpg|jpeg|jpg|png|gif)$/i', $path->url)) {
                            $preload_images[] = $path->url;
                        }
                    }
                }
            }
        }

        if(!empty($preload_images)){
            self::$options['uucss_preload_lcp_image'] = implode("\n",$preload_images);
        }

        if(isset(self::$options['rapidload_test_mode']) && self::$options['rapidload_test_mode'] == "1"){
            unset(self::$options['rapidload_test_mode']);
        }

        if(self::$strategy == "desktop"){
            self::$job->set_desktop_options(self::$options);
        }else{
            self::$job->set_mobile_options(self::$options);
        }

        self::$job->save(!self::$job->exist());

        $options = [
            //css
            'uucss_enable_css',
            'uucss_minify',
            'uucss_minify_excluded_files',
            'uucss_enable_cpcss',
            'uucss_additional_css',
            'uucss_enable_uucss',
            'uucss_safelist',
            'remove_cpcss_on_user_interaction',
            'rapidload_enable_cpcss_file_chunk',
            'rapidload_cpcss_file_character_length',
            'uucss_excluded_files',
            'enable_uucss_on_cpcss',
            //js
            'uucss_enable_javascript',
            'minify_js',
            'uucss_exclude_files_from_minify_js',
            'uucss_load_js_method',
            'uucss_excluded_js_files_from_defer',
            'delay_javascript',
            'uucss_exclude_files_from_delay_js',
            'delay_javascript_callback',
            'uucss_excluded_js_files',
            'uucss_dynamic_js_exclusion_list',
            'uucss_load_scripts_on_user_interaction',
            //image
            'uucss_enable_image_delivery',
            'uucss_support_next_gen_formats',
            'uucss_adaptive_image_delivery',
            'uucss_image_optimize_level',
            'uucss_generate_blurry_place_holder',
            'uucss_exclude_images_from_modern_images',
            'uucss_lazy_load_images',
            'uucss_exclude_above_the_fold_images',
            'uucss_exclude_above_the_fold_image_count',
            'uucss_lazy_load_iframes',
            'uucss_exclude_images_from_lazy_load',
            'uucss_set_width_and_height',
            'uucss_exclude_images_from_set_width_and_height',
            'uucss_exclude_iframes_from_lazy_load',
            //font
            'uucss_enable_font_optimization',
            'uucss_self_host_google_fonts',
            'uucss_preload_font_urls',
            //cache
            'uucss_disable_wp_emoji'

        ];

        if(self::$global){
            foreach ($options as $key){
                if(isset(self::$options[$key])){
                    self::$global_options[$key] = self::$options[$key];
                }
            }
            RapidLoad_Base::update_option('autoptimize_uucss_settings',self::$global_options);
        }


    }

    function formatSize($bytes) {
        $sizes = array("Bytes", "KB", "MB", "GB", "TB");
        if ($bytes == 0) return '0 Byte';
        $i = intval(floor(log($bytes, 1024)));
        return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    }

    public function handle_ajax_fetch_page_speed(){

        self::verify_nonce();

        if(!isset($_REQUEST['url']) || empty($_REQUEST['url'])){
            wp_send_json_error('url required');
        }

        $url = sanitize_url(wp_unslash($_REQUEST['url']));

        if(!$this->is_valid_url($url)){
           wp_send_json_error('url not valid');
        }

        $strategy = isset($_REQUEST['strategy']) ? sanitize_text_field(wp_unslash($_REQUEST['strategy'])) : 'mobile';
        $global = isset($_REQUEST['global']) && boolval($_REQUEST['global']);

        $this->pre_optimizer_function($url, $strategy, $global, true);

        $body = file_get_contents('php://input');

        $result = ($body) ? json_decode($body) : null;

        if ($result && isset($result->page_speed)) {
            $result = $result->page_speed;
        }

        if(!$result){
            $result = self::$job->get_last_optimization_revision(self::$strategy);
        }

        $response = $this->fetch_page_speed($url, $result);

        if(isset($response['success']) && $response['success']){
            wp_send_json_success($response);
        }else{
            wp_send_json_error($response);
        }

    }

    public function fetch_page_speed($url, $result){

        if(!$result || !isset($result->audits)){

            return [
                'success' => false,
                'reload' => true
            ];

        }

        $result->job_id = isset(self::$job) ? self::$job->id : null;

        $hash = self::$job->get_last_optimization_revision_hash(self::$strategy);
        $new_hash = hash('md5', json_encode($result));
        $revision_count = self::$job->get_revision_count(self::$strategy);

        if(($hash != $new_hash) || $revision_count == 0){

            if($revision_count > (self::$revision_limit - 1)){
                self::$job->delete_old_revision(self::$strategy, self::$revision_limit);
            }

            $optimization = new RapidLoad_Job_Optimization(self::$job, self::$strategy);
            $optimization->set_data($result);
            $optimization->save();
        }

        return[
            'url' => $url,
            'success' => true,
            'job_id' => isset(self::$job) ? self::$job->id : null,
            'page_speed' => $result,
            'revisions' => self::$job->get_optimization_revisions(self::$strategy, self::$revision_limit),
            'options' => self::$options,
            'merged_options' => self::$merged_options,
            'global_options' => self::$global_options,
        ];

    }

    public function get_google_audits(){
        return ['render-blocking-resources', 'uses-responsive-images', 'offscreen-images', 'unminified-css', 'unminified-javascript', 'unused-css-rules',
            'unused-javascript', 'uses-optimized-images', 'modern-image-formats', 'uses-text-compression', 'uses-rel-preconnect', 'server-response-time',
            'redirects', 'uses-rel-preload', 'efficient-animated-content', 'duplicated-javascript', 'legacy-javascript', 'preload-lcp-image',
            'total-byte-weight', 'uses-long-cache-ttl', 'dom-size', 'user-timings', 'bootup-time', 'mainthread-work-breakdown', 'font-display',
            'third-party-summary', 'third-party-facades', 'lcp-lazy-loaded', 'layout-shift-elements', 'uses-passive-event-listeners', 'no-document-write',
            'long-tasks', 'non-composited-animations', 'unsized-images', 'viewport', 'no-unload-listeners',
            'critical-request-chains','resource-summary','largest-contentful-paint-element' , 'prioritize-lcp-image'
        ];
    }

    function get_settings_with_inputs($url , $keys, $settings, $options = array()) {
        $input_map = array(
            // CPCSS settings starts here
            'uucss_enable_cpcss' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Enable Critical CSS',
                'control_values' => array('1', '0'),
                'default' => '0',
                'main_input' => true
            ),
            'uucss_additional_css' => array(
                'control_type' => 'textarea',
                'control_label' => 'Above-the-fold CSS',
                'control_description' => 'Include any CSS content you need to load above the fold.',
                'default' => ''
            ),
            'remove_cpcss_on_user_interaction' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Remove Critical CSS on User Interaction',
                'control_description' => 'Remove Critical CSS when users engage',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'rapidload_cpcss_file_character_length' => array(
                'control_type' => 'input',
                'control_props' => [
                    'type' => 'number'
                ],
                'control_label' => 'CSS Chunck File Size',
                'control_description' => 'Splits large critical CSS files into smaller chunks to improve speed',
                'default' => 0,
                'control_visibility' => [
                    [
                        'key' => 'rapidload_enable_cpcss_file_chunk',
                        'value' => true,
                    ]
                ]
            ),
            'rapidload_enable_cpcss_file_chunk' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Enable CSS File Chunck',
                'control_description' => 'Enable Splits large critical CSS files',
                'control_values' => array('1', '0'),
                'default' => '0',
            ),
            'enable_uucss_on_cpcss' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Enable Remove Unused CSS with Critical CSS',
                'control_description' => 'Enable Remove Unused CSS with Critical CSS',
                'control_values' => array('1', '0'),
                'default' => '0',
                'global' => true,
            ),
            'uucss_ignore_inlined_styles' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Include inline styles',
                'control_description' => 'Essential inline CSS for optimizing above-the-fold content',
                'control_values' => array('1', '0'),
                'default' => '0',
                'global' => true,
            ),
            'uucss_preload_font_urls' => array(
                'control_type' => 'textarea',
                'control_label' => 'Preload Fonts',
                'control_description' => 'Preload critical font files to avoid FOUT and signal browsers to download fonts earlier.',
                'default' => ''
            ),
            'cpcss_purge_url' => array(
                'control_type' => 'button',
                'control_label' => 'Regenerate Critical CSS',
                'action' => 'action=cpcss_purge_url&url=' . $url . '&nonce=' . self::create_nonce( 'uucss_nonce' ),
                'description' => ''
            ),
            // UUCSS settings starts here
            'uucss_enable_uucss' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Remove Unused CSS',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_excluded_files' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude CSS from Remove Unused CSS',
                'control_description' => 'These CSS files will be excluded from Remove Unused CSS optimization.',
                'default' => ''
            ),
            'uucss_safelist' => array(
                'control_type' => 'textarea',
                'control_label' => 'Force Include selectors',
                'control_description' => 'These selectors will be forcefully included into optimization.',
                'default' => ''
            ),
            'uucss_misc_options' => array(
                'control_type' => 'accordion',
                'inputs' => [
                    array(
                        'control_type' => 'checkbox',
                        'control_label' => 'CSS Variables',
                        'control_description' => 'Remove unused CSS variables.',
                        'control_values' => array('1', '0'),
                        'key' => 'uucss_variables',
                        'default' => '0'
                    ),
                    array(
                        'control_type' => 'checkbox',
                        'control_accordion_name' => 'uucss-misc-options',
                        'control_label' => 'CSS Animation keyframes',
                        'control_description' => 'Remove unused keyframe animations.',
                        'control_values' => array('1', '0'),
                        'key' => 'uucss_keyframes',
                        'default' => '0'
                    ),
                    array(
                        'control_type' => 'checkbox',
                        'control_accordion_name' => 'uucss-misc-options',
                        'control_label' => 'CSS @font-face rules',
                        'control_description' => 'Remove unused @font-face rules.',
                        'control_values' => array('1', '0'),
                        'key' => 'uucss_fontface',
                        'default' => '0'
                    ),
                    array(
                        'control_type' => 'checkbox',
                        'control_accordion_name' => 'uucss-misc-options',
                        'control_label' => 'Inline CSS',
                        'control_description' => 'Optimize inline CSS.',
                        'control_values' => array('1', '0'),
                        'key' => 'uucss_include_inline_css',
                        'default' => '0'
                    ),
                    array(
                        'control_type' => 'checkbox',
                        'control_accordion_name' => 'uucss-misc-options',
                        'control_label' => 'Cache Busting',
                        'control_description' => 'Enable RapidLoad crawler to view pages with a random query string.',
                        'control_values' => array('1', '0'),
                        'key' => 'uucss_cache_busting_v2',
                        'default' => '0'
                    ),
                ],
            ),
            'rapidload_purge_all' => array(
                'control_type' => 'button',
                'control_label' => 'Regenerate Unused CSS',
                'action' => 'action=rapidload_purge_all&job_type=url&clear=false&immediate=true&url=' . $url . '&nonce=' . self::create_nonce( 'uucss_nonce' ),
                'description' => ''
            ),

            // Minify CSS settings starts here
            'uucss_minify' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Minify CSS',
                'control_description' => 'Remove unnecessary spaces, lines and comments from CSS files.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_minify_excluded_files' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude CSS from Minify',
                'control_description' => 'These CSS files will be excluded from being minified.',
                'default' => ''
            ),

            // JS settings here
            'rapidload_js_delay_method' => array(
                'control_type' => 'radio',
                'control_label' => 'Delay Method',
                'control_description' => 'Delay Method',
                'control_values' => array('All Files', 'Selected Files'),
                'control_values_description' => array(
                    [
                        'value' => 'All Files',
                        'description' => 'Every JavaScript file will be delayed.'
                    ],
                    [
                        'value' => 'Selected Files',
                        'description' => 'The files listed below will be delayed.'
                    ]),
                'default' => 'All Files'
            ),
            'uucss_load_js_method' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Defer Javascript',
                'control_description' => 'Render-blocking JS on website can be resolved with defer javaScript.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_excluded_js_files_from_defer' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Javascript from Deferring',
                'control_description' => 'These JS files will be excluded from deferring.',
                'default' => ''
            ),
            'minify_js' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Minify Javascript',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_exclude_files_from_minify_js' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Javascript from Minify',
                'control_description' => 'These JS files will be excluded from being minified.',
                'default' => ''
            ),
            'delay_javascript' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Delay Javascript',
                'control_description' => 'Loading JS files on user interaction',
                'control_values' => array('1', '0'),
                'default' => '0',
                'control_visibility' => [
                    [
                        'key' => 'rapidload_js_delay_method',
                        'value' => 'All Files',
                    ]
                ]
            ),
            'uucss_exclude_files_from_delay_js' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Javascript from Delaying',
                'control_description' => 'These JS files will be excluded from delaying.',
                'default' => '',
                'control_visibility' => [
                    [
                        'key' => 'rapidload_js_delay_method',
                        'value' => 'All Files',
                    ]
                ]
            ),
            /*'uucss_exclude_files_from_delay_js' => array(
                'control_type' => 'tab',
                'control_label' => 'Exclude Javascript from Delaying',
                'control_description' => 'These JS files will be excluded from delaying.',
                'default' => '',
                'control_visibility' => [
                    [
                        'key' => 'rapidload_js_delay_method',
                        'value' => 'All Files',
                    ]
                ]
            ),*/
            'delay_javascript_callback' => array(
                'control_type' => 'textarea',
                'control_label' => 'Callback Script',
                'control_description' => 'These scripts will be executed on DOMContentLoaded',
                'default' => '',
                'control_visibility' => [
                    [
                        'key' => 'rapidload_js_delay_method',
                        'value' => 'All Files',
                    ]
                ]
            ),
            'uucss_load_scripts_on_user_interaction' => array(
                'control_type' => 'textarea',
                'control_label' => 'Delaying only selected Javascript',
                'control_description' => 'Add JavaScript files to forcefully delay.',
                'default' => '',
                'control_visibility' => [
                    [
                        'key' => 'rapidload_js_delay_method',
                        'value' => 'Selected Files',
                    ]
                ]
            ),

            //Image settings starts here
            'uucss_support_next_gen_formats' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Serve next-gen Images',
                'control_description' => 'Serve the images in next-gen image formats to all the browsers that support them.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_image_optimize_level' => array(
                'control_type' => 'options',
                'control_label' => 'Image Optimize Level',
                'control_description' => 'Choose the image compression level.',
                'control_values' => array('lossy', 'glossy', 'lossless'),
                'default' => 'lossless'
            ),
            'uucss_generate_blurry_place_holder' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Low Quality Image placeholders (LQIP)',
                'control_description' => 'Generate low quality blurry SVG image placeholders.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_adaptive_image_delivery' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Enable adaptive Image delivery',
                'control_description' => 'Resize your images based on device screen size to reduce download times.',
                'control_values' => array('1', '0'),
                'default' => '0',
                'main_input' => false
            ),
            'uucss_exclude_images_from_modern_images' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Images from modern image urls',
                'control_description' => 'These images will be excluded from being converted to modern formats.',
                'default' => ''
            ),
            'uucss_lazy_load_images' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Image Lazy Load',
                'control_description' => 'Lazy load images.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_exclude_images_from_lazy_load' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Images from Lazy Load',
                'control_description' => 'These images will be excluded from lazy-loading.',
                'default' => ''
            ),
            'uucss_exclude_iframes_from_lazy_load' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Iframes from Lazy Load',
                'control_description' => 'These iframes will be excluded from lazy-loading.',
                'default' => ''
            ),
            'uucss_lazy_load_iframes' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Iframes Lazy Load',
                'control_description' => 'Lazy load all iframes in your website.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_exclude_above_the_fold_images' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Exclude LCP image from Lazy Load',
                'control_description' => 'Choose the image count to exclude from above-the-fold',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_exclude_above_the_fold_image_count' => array(
                'control_type' => 'number-range',
                'control_label' => 'Exclude Above-the-fold Images from Lazy Load',
                'control_description' => 'Choose the image count to exclude from above-the-fold',
                'control_values' => array('1','2', '3','4', '5'),
                'default' => '5'
            ),
            'uucss_set_width_and_height' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Add Width and Height Attributes',
                'control_description' => 'Include width and height attributes for these images.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_exclude_images_from_set_width_and_height' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Images from being set width and height',
                'control_description' => 'These images will be excluded from inserting a width and height.',
                'default' => ''
            ),
            'preload_lcp_images' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Preload LCP image',
                'control_description' => 'Lazy load all iframes in your website.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_preload_lcp_image' => array(
                'control_type' => 'textarea',
                'control_label' => 'Preload LCP Image List',
                'control_description' => 'These images will be preloaded.',
                'default' => ''
            ),

            // Fonts settings starts here
            'uucss_self_host_google_fonts' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Self Host Google Fonts',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),

            // CDN settings starts here
            'uucss_enable_cdn' => array(
                'control_type' => 'checkbox',
                'control_label' => 'RapidLoad CDN',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_cdn_url' => array(
                'control_type' => 'input',
                'readonly' => true,
                'placeholder' => 'Your CDN url is not populated yet.',
                'control_label' => 'CDN Endpoint',
                'control_description' => 'Your CDN endpoint to store and serve all your resources across the CDN network',
                'actions' => array(
                    array(
                        'key' => 'copy_cdn_url',
                        'control_type' => 'button',
                        'control_label' => 'Copy CDN URL',
                        'control_icon' => 'clipboard',
                        'control_description' => 'Copy to clipboard',
                        'action' => 'clipboard',
                    ),
                    array(
                        'key' => 'validate_cdn_url',
                        'control_type' => 'button',
                        'control_label' => 'Validate CDN URL',
                        'control_icon' => 'check-circle',
                        'control_description' => 'Check if the CDN url is working',
                        'action' => 'action=validate_cdn&dashboard_cdn_validator&nonce=' . self::create_nonce( 'uucss_nonce' ),
                        // this state will be updated in the frontend after response using data.${provided_key}
                        'action_response_mutates' => ['uucss_cdn_url'],
                    ),
                    array(
                        'key' => 'clear_cdn_cache',
                        'control_type' => 'button',
                        'control_label' => 'Clear CDN Cache',
                        'control_icon' => 'rotate-cw',
                        'control_description' => 'Clear resources caches across the CDN network',
                        'action' => 'action=purge_rapidload_cdn&nonce=' . self::create_nonce( 'uucss_nonce' ),
                    ),
                )
            ),

            //Cache settings starts here
            'uucss_enable_cache' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Page Cache',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'cache_expiry_time' => array(
                'control_type' => 'number-range',
                'control_label' => 'Cache Expiration',
                'control_description' => 'Cached pages expire.',
                'control_values' => array('0', '2', '6', '12', '24'),
                'control_values_suffix' => 'h',
                'default' => '0'
            ),
            'mobile_cache' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Mobile Cache',
                'control_description' => 'Create a cached version for mobile devices.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'excluded_page_paths' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Pages',
                'control_description' => 'These pages will exclude from cache.',
                'default' => ''
            ),
            'update_htaccess_file' => array(
                'control_type' => 'button',
                'control_label' => 'Setup Policies',
                'action' => 'update_htaccess_file',
                'default' => ''
            ),
            'preload_internal_links' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Preload Links',
                'control_description' => 'Intelligent preload and pre-render pages to faster page load times.',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_disable_wp_emoji' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Disable WP-Emojis',
                'control_description' => 'Prevents WordPress from displaying emojis, smiley faces and other icons',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),

            //Other settings starts here
            'uucss_inline_css' => array(
                'control_type' => 'checkbox',
                'control_label' => 'Inline Small CSS Files',
                'control_values' => array('1', '0'),
                'default' => '0'
            ),
            'uucss_excluded_js_files' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Javascript',
                'control_description' => 'These JS files will be excluded from all optimizations.',
                'default' => '',
                'control_visibility' => [
                    [
                        'key' => 'rapidload_js_delay_method',
                        'value' => 'All Files',
                    ]
                ]
            ),
            'uucss_exclude_images' => array(
                'control_type' => 'textarea',
                'control_label' => 'Exclude Images from Lazy Load',
                'control_description' => 'These images will be excluded from lazy-loading.',
                'default' => ''
            ),

        );

        $inputs = array();

        $rapidload_cache_args = RapidLoad_Cache::get_settings();

        foreach ($keys as $key) {
            if (isset($input_map[$key])) {
                $input = $input_map[$key];
                $input['key'] = $key;
                if($input['key'] == "uucss_exclude_files_from_delay_js"){
                    $input['control_values'] = JavaScript::get_dynamic_exclusion_list();
                    $input['value'] = "";
                    if(isset($options['uucss_dynamic_js_exclusion_list']) && !empty($options['uucss_dynamic_js_exclusion_list'])){
                        $input['value'] = $options['uucss_dynamic_js_exclusion_list'];
                    }
                    if(isset($options['uucss_exclude_files_from_delay_js']) && !empty($options['uucss_exclude_files_from_delay_js'])){
                        $input['value'] = (!empty($input['value'])) ? trim($input['value']) . "," . $options['uucss_exclude_files_from_delay_js'] : $options['uucss_exclude_files_from_delay_js'];
                    }
                }else if($input['key'] == "uucss_load_js_method" && isset($options[$input['key']])){
                    $input['value'] = $options[$input['key']] == "defer" || $options[$input['key']] == "1";
                }else if($input['key'] == "uucss_enable_uucss"){
                    $data = new RapidLoad_Job_Data(self::$job, 'uucss');
                    $settings['status'] =  isset($options[$input['key']]) && $options[$input['key']] == "1" ? [
                        'status' => isset($data->id) ? $data->status : 'queued',
                        'error' =>  isset($data->id) ? $data->get_error() : null,
                        'meta' => [
                            'stats' =>  isset($data->id) ? $data->get_stats() : null,
                            'warnings' =>  isset($data->id) ? $data->get_warnings() : null,
                        ]
                    ] : null;
                    $input['value'] = isset($options[$input['key']]) ? $options[$input['key']] : ( isset($input['default']) ? $input['default'] : null) ;
                }else if($input['key'] == "update_htaccess_file"){
                    $settings['status'] = RapidLoad_htaccess::has_rapidload_rules();
                }else if($input['key'] == "uucss_enable_cpcss"){
                    $data = new RapidLoad_Job_Data(self::$job, 'cpcss');
                    $cpcss_data = $data->get_cpcss_data();
                    $settings['status'] = isset($options[$input['key']]) && $options[$input['key']] == "1" ? [
                        'status' => isset($data->id) ? $data->status : 'queued',
                        'error' => isset($data->id) ? $data->get_error() : null,
                        'meta' => [
                            'desktop' => isset($data->id) && isset($cpcss_data['desktop']) && !empty($cpcss_data['desktop']) ? $cpcss_data['desktop'] : null,
                            'mobile' => isset($data->id) && isset($cpcss_data['mobile']) && !empty($cpcss_data['mobile']) ? $cpcss_data['mobile'] : null,
                        ]
                    ] : null;
                    $input['value'] = isset($options[$input['key']]) ? $options[$input['key']] : ( isset($input['default']) ? $input['default'] : null) ;
                }else if($input['key'] == "uucss_enable_cache"){
                    $cache_file = RapidLoad_Cache_Store::get_cache_file($url);
                    $cache_file_exist = @file_exists($cache_file);
                    $settings['status'] = isset($options[$input['key']]) && $options[$input['key']] == "1" ? [
                        'status' => $cache_file_exist ? 'Hit' : 'processing',
                        'error' => [
                            'code' => $cache_file_exist ? null : 422,
                            'message' => $cache_file_exist ? null : 'Cache file not found',
                        ],
                        'meta' => [
                            'file' => $cache_file,
                            'size' => $cache_file_exist ? $this->formatSize(@filesize($cache_file)) : null,
                            'url' => $url,
                        ]
                    ] : null;
                    $input['value'] = isset($options[$input['key']]) ? $options[$input['key']] : ( isset($input['default']) ? $input['default'] : null) ;
                }else if($input['key'] == "uucss_safelist"){
                    $rulesArray = [];
                    if(isset($options[$input['key']])){
                        $dataArray = json_decode($options[$input['key']], true);
                        foreach ($dataArray as $item) {
                            if (isset($item['rule'])) {
                                $rulesArray[] = $item['rule'];
                            }
                        }
                    }
                    $input['value'] = implode("\n",$rulesArray);
                }else if($input['key'] == "cache_expires" || $input['key'] == "cache_expiry_time" || $input['key'] == "mobile_cache"){
                    $input['value'] = isset($rapidload_cache_args[$input['key']]) ? (string)$rapidload_cache_args[$input['key']] : null;
                }else if($input['key'] == "excluded_page_paths") {
                    $input['value'] = isset($rapidload_cache_args[$input['key']]) ? implode("\n", $this->transformRegexToPaths($rapidload_cache_args[$input['key']])) : '';
                }else if($input['key'] == "rapidload_js_delay_method"){
                    $input['value'] = isset($options['uucss_load_scripts_on_user_interaction']) && !empty($options['uucss_load_scripts_on_user_interaction']) ? 'Selected Files' : 'All Files' ;
                }else if($input['key'] == "uucss_misc_options"){

                    foreach ($input['inputs'] as $internal_key => $internal_input){

                        $input['inputs'][$internal_key]['value'] = isset($options[$input['inputs'][$internal_key]['key']]) && $options[$input['inputs'][$internal_key]['key']] == "1" ? "1" : "0" ;

                    }

                    $input['value'] = isset($options['uucss_load_scripts_on_user_interaction']) && !empty($options['uucss_load_scripts_on_user_interaction']) ? 'Selected Files' : 'All Files' ;
                }else{
                    $input['value'] = isset($options[$input['key']]) ? $options[$input['key']] : ( isset($input['default']) ? $input['default'] : null) ;
                }
                $inputs[] = $input;
            }
        }

        $settings['inputs'] = $inputs;

        return $settings;
    }

    public function transform_options_to_settings($url, $options) {
        $audits = $this->get_google_audits();
        $settings = [];

        $settings_map = [
            ['keys' => ['modern-image-formats', 'uses-optimized-images', 'uses-responsive-images'], 'name' => 'Serve next-gen Images (AVIF, WEBP)', 'description' => 'Serve the images in next-gen image formats to all the browsers that support them.', 'category' => 'image', 'inputs' => ['uucss_support_next_gen_formats', 'uucss_image_optimize_level', 'uucss_generate_blurry_place_holder','uucss_adaptive_image_delivery', 'uucss_exclude_images_from_modern_images']],
            ['keys' => ['font-display', 'enable-font'], 'name' => 'Self Host Google Fonts', 'description' => 'Self host all your Google fonts and load fonts faster. Turn on CDN to serve these fonts faster through RapidLoad CDN.', 'category' => 'font', 'inputs' => ['uucss_self_host_google_fonts', 'uucss_preload_font_urls']],
            ['keys' => ['unsized-images'], 'name' => 'Minify CSS', 'description' => 'Remove unnecessary spaces, lines and comments from CSS files.', 'category' => 'css', 'inputs' => ['uucss_minify', 'uucss_minify_excluded_files']],
            ['keys' => ['unminified-javascript'], 'name' => 'Minify Javascript', 'description' => 'Remove unnecessary spaces, lines and comments from JS files.', 'category' => 'javascript', 'inputs' => ['minify_js', 'uucss_exclude_files_from_minify_js']],
            ['keys' => ['unused-css-rules'], 'name' => 'Remove Unused CSS', 'description' => 'Remove unused CSS for each page and reduce page size.', 'category' => 'css', 'inputs' => ['uucss_enable_uucss', 'uucss_excluded_files','uucss_safelist','uucss_misc_options','rapidload_purge_all']],
            ['keys' => ['render-blocking-resources'], 'name' => 'Critical CSS', 'description' => 'Extract and prioritize above-the-fold CSS.', 'category' => 'css', 'inputs' => ['uucss_enable_cpcss', 'remove_cpcss_on_user_interaction', 'uucss_ignore_inlined_styles', 'enable_uucss_on_cpcss', 'uucss_additional_css', 'cpcss_purge_url']],
            ['keys' => ['render-blocking-resources'], 'name' => 'Defer Javascript', 'description' => 'Render-blocking JS on website can be resolved with defer JavaScript.', 'category' => 'javascript', 'inputs' => ['uucss_load_js_method', 'uucss_excluded_js_files_from_defer']],
            ['keys' => ['offscreen-images'], 'name' => 'Lazy Load Images', 'description' => 'Delay loading of images until needed.', 'category' => 'image', 'inputs' => ['uucss_lazy_load_images', 'uucss_exclude_images_from_lazy_load']],
            ['keys' => ['lcp-lazy-loaded'], 'name' => 'Exclude Above-the-fold Images from Lazy Load', 'description' => 'Improve your LCP images.', 'category' => 'image', 'inputs' => ['uucss_exclude_above_the_fold_images', 'uucss_exclude_above_the_fold_image_count']],
            ['keys' => ['server-response-time'], 'name' => 'Disable WP-Emojis', 'description' => 'Prevents WordPress from displaying emojis, smiley faces and other icons', 'category' => 'cache', 'inputs' => ['uucss_disable_wp_emoji']],
            ['keys' => ['server-response-time'], 'name' => 'Preload Links', 'description' => 'Intelligent preload and pre-render pages to faster page load times.', 'category' => 'cache', 'inputs' => ['preload_internal_links']],
            ['keys' => ['bootup-time', 'unused-javascript'], 'name' => 'Delay Javascript', 'description' => 'Loading JS files on user interaction', 'category' => 'javascript', 'inputs' => ['delay_javascript', 'rapidload_js_delay_method', 'uucss_exclude_files_from_delay_js', 'uucss_excluded_js_files', 'uucss_load_scripts_on_user_interaction']],
            ['keys' => ['server-response-time'], 'name' => 'Page Cache', 'description' => 'Optimize and cache static HTML pages to provide a snappier page experience.', 'category' => 'cache', 'inputs' => ['uucss_enable_cache','cache_expires','cache_expiry_time','mobile_cache','excluded_page_paths']],
            ['keys' => ['third-party-facades'], 'name' => 'Lazy Load Iframes', 'description' => 'Delay loading of iframes until needed.', 'category' => 'image', 'inputs' => ['uucss_lazy_load_iframes', 'uucss_exclude_iframes_from_lazy_load']],
            ['keys' => ['uses-long-cache-ttl'], 'name' => 'RapidLoad CDN', 'description' => 'Load resource files faster by using 112 edge locations with only 27ms latency.', 'category' => 'cdn', 'inputs' => ['uucss_enable_cdn','uucss_cdn_url', 'validate_cdn_url', 'clear_cdn_cache']],
            ['keys' => ['uses-long-cache-ttl'], 'name' => 'Cache Policy', 'description' => 'Set up cache-control header to increase the browser cache expiration', 'category' => 'cache', 'inputs' => ['update_htaccess_file',]],
            ['keys' => ['unsized-images'], 'name' => 'Add Width and Height Attributes', 'description' => 'Include width and height attributes for these images.', 'category' => 'image', 'inputs' => ['uucss_set_width_and_height','uucss_exclude_images_from_set_width_and_height']],
            ['keys' => ['preload-lcp-image'], 'name' => 'Preload LCP Image', 'description' => 'Preload LCP image to improve page load time.', 'category' => 'image', 'inputs' => ['preload_lcp_images', 'uucss_preload_lcp_image']]
        ];

        foreach ($audits as $audit) {
            foreach ($settings_map as $setting) {

                if(isset($settings[$setting['name']])){
                    continue;
                }
                if (in_array($audit, $setting['keys'])) {

                    $_setting = [
                        'name' => $setting['name'],
                        'description' => $setting['description'],
                        'category' => $setting['category'],
                    ];

                    $_setting = $this->get_settings_with_inputs($url, $setting['inputs'], $_setting, $options);

                    $settings[$setting['name']] = $_setting;
                }
            }
        }

        return array_values($settings);
    }

    public function optimizer_update_settings($result, $general_settings = null){

        $rapidload_cache_args = RapidLoad_Cache::get_settings();

        foreach ($result as $settings){
            foreach ($settings->inputs as $input){
                switch($input->control_type ){

                    case 'checkbox' :{
                        if(isset($input->value) && isset($input->key) && ($input->value || $input->value == "1")){
                            if($input->key == "uucss_load_js_method"){
                                self::$options[$input->key] = "defer";
                            }else if($input->key == "cache_expires" || $input->key == "mobile_cache"){
                                $rapidload_cache_args[$input->key] = $input->value ? 1 : 0;
                            }else{
                                self::$options[$input->key] = "1";
                            }
                        }else if(isset($input->key)){
                            if($input->key == "cache_expires" || $input->key == "mobile_cache"){
                                $rapidload_cache_args[$input->key] = "0";
                            }else{
                                self::$options[$input->key] = "";
                            }
                        }
                        break;
                    }
                    case 'dropdown' :
                    case 'text' :
                    case 'options' :
                    case 'textarea' :
                    case 'number-range' :
                    case 'input' :
                    case 'number' :{
                        if(isset($input->value) && isset($input->key)){
                            if($input->key == "uucss_safelist"){
                                $rulesArray = explode("\n",sanitize_textarea_field($input->value));
                                $transformedRulesArray = [];
                                foreach ($rulesArray as $rule) {
                                    $transformedRulesArray[] = [
                                        'type' => 'greedy',
                                        'rule' => $rule
                                    ];
                                }
                                self::$options[$input->key] = json_encode($transformedRulesArray);
                            }else if($input->key == "cache_expiry_time"){
                                $rapidload_cache_args['cache_expiry_time'] = (float)$input->value;
                            }else if($input->key == "excluded_page_paths"){
                                if(!empty($input->value)){
                                    $paths = explode("\n",sanitize_textarea_field($input->value));
                                    $rapidload_cache_args['excluded_page_paths'] = $this->transformPathsToRegex($paths);
                                }else{
                                    $rapidload_cache_args['excluded_page_paths'] = "";
                                }
                            }else{
                                if($input->key != "uucss_cdn_url"){
                                    self::$options[$input->key] = sanitize_textarea_field($input->value);
                                }
                            }
                        }else if(isset($new_options[$input->key])){
                            unset(self::$options[$input->key]);
                        }
                        break;
                    }
                    case 'tab' : {
                        if(isset($input->key) && $input->key == "uucss_exclude_files_from_delay_js"){
                            if(is_array($input->value)){
                                self::$options['uucss_dynamic_js_exclusion_list'] = implode("\n",$input->value);
                            }
                        }
                        break;
                    }
                    case 'accordion' : {
                        foreach ($input->inputs as $accordion_key => $accordion_input){
                            self::$options[$input->inputs[$accordion_key]->key] =
                                isset($accordion_input->value) &&
                                ($accordion_input->value ||
                                    $accordion_input->value == "1") ? "1" : "0";
                        }
                        break;
                    }
                }

                if(isset($input->key) && ($input->key == "uucss_enable_uucss" || $input->key == "uucss_enable_cpcss")){
                    if(isset($input->{'value_data'})){
                        unset($input->{'value_data'});
                    }
                }

            }
        }

        RapidLoad_Cache::update_settings($rapidload_cache_args);

        if((isset(self::$options['uucss_lazy_load_images']) && self::$options['uucss_lazy_load_images'] == "1") || (isset(self::$options['uucss_support_next_gen_formats']) && self::$options['uucss_support_next_gen_formats'] == "1" ) || (isset(self::$options['uucss_lazy_load_iframes']) && self::$options['uucss_lazy_load_iframes'] == "1") ){
            self::$options['uucss_enable_image_delivery'] = "1";
            if(isset(self::$options['uucss_lazy_load_iframes']) && self::$options['uucss_lazy_load_iframes'] == "1" ){
                self::$options['uucss_lazy_load_images'] == "1";
            }
        }else{
            unset(self::$options['uucss_enable_image_delivery']);
            RapidLoad_Base::update_option('rapidload_module_image',"0");
        }

        if(isset(self::$options['uucss_self_host_google_fonts']) && self::$options['uucss_self_host_google_fonts'] == "1"){
            self::$options['uucss_enable_font_optimization'] = "1";
        }else{
            unset(self::$options['uucss_enable_font_optimization']);
        }

        if(isset(self::$options['uucss_minify']) && self::$options['uucss_minify'] ||
            isset(self::$options['uucss_enable_cpcss']) && self::$options['uucss_enable_cpcss'] ||
            isset(self::$options['uucss_enable_uucss']) && self::$options['uucss_enable_uucss'] ){
            self::$options['uucss_enable_css'] = "1";
        }else{
            unset(self::$options['uucss_enable_css']);
        }

        if(isset(self::$options['minify_js']) && self::$options['minify_js'] ||
            isset(self::$options['delay_javascript']) && self::$options['delay_javascript'] == "1" ||
            isset(self::$options['uucss_load_js_method']) && (self::$options['uucss_load_js_method'] == "defer" || self::$options['uucss_load_js_method'] == "1")){
            self::$options['uucss_enable_javascript'] = "1";
        }else{
            unset(self::$options['uucss_enable_javascript']);
        }

        $cache_enabled = isset(self::$options['uucss_enable_cache']) && self::$options['uucss_enable_cache'] ? "1" : "";
        $cache_enabled_prev_status = isset(self::$global_options['uucss_enable_cache']) && self::$global_options['uucss_enable_cache'] ? "1" : "";

        if($cache_enabled != $cache_enabled_prev_status){
            RapidLoad_Base::update_option('rapidload_module_cache',$cache_enabled);
            RapidLoad_Cache::setup_cache($cache_enabled);
        }

        $cdn_enabled = isset(self::$options['uucss_enable_cdn']) && self::$options['uucss_enable_cdn'] == "1";
        $cdn_enabled_prev_status = isset(self::$global_options['uucss_enable_cdn']) && self::$global_options['uucss_enable_cdn'] == "1";

        if($cdn_enabled != $cdn_enabled_prev_status){
            do_action('rapidload/validate-cdn', !$cdn_enabled);
            $refresh_cdn_settings = RapidLoad_Base::fetch_options(false);
            if(isset($refresh_cdn_settings['uucss_cdn_zone_id']) && isset($refresh_cdn_settings['uucss_cdn_dns_id']) && isset($refresh_cdn_settings['uucss_cdn_url'])){
                self::$global_options['uucss_cdn_zone_id'] = $refresh_cdn_settings['uucss_cdn_zone_id'];
                self::$global_options['uucss_cdn_dns_id'] = $refresh_cdn_settings['uucss_cdn_dns_id'];
                self::$global_options['uucss_cdn_url'] = $refresh_cdn_settings['uucss_cdn_url'];
            }
        }

        if($general_settings){
            if(isset($general_settings->performance_gear)){
                update_option('rapidload_titan_gear', $general_settings->performance_gear);
            }
        }

        $this->associate_domain(false);

        if(isset(self::$options['rapidload_enable_cpcss_file_chunk']) && isset(self::$options['rapidload_cpcss_file_character_length'])){
            self::$global_options['rapidload_enable_cpcss_file_chunk'] = self::$options['rapidload_enable_cpcss_file_chunk'];
            self::$global_options['rapidload_cpcss_file_character_length'] = self::$options['rapidload_cpcss_file_character_length'];
            RapidLoad_Base::update_option('autoptimize_uucss_settings',self::$global_options);
        }

        if(isset(self::$options['uucss_enable_cache'])){
            self::$global_options['uucss_enable_cache'] = self::$options['uucss_enable_cache'];
            RapidLoad_Base::update_option('rapidload_module_cache', self::$options['uucss_enable_cache']);
            RapidLoad_Base::update_option('autoptimize_uucss_settings',self::$global_options);
        }

        if(isset(self::$options['enable_uucss_on_cpcss'])){
            self::$global_options['enable_uucss_on_cpcss'] = self::$options['enable_uucss_on_cpcss'];
            RapidLoad_Base::update_option('autoptimize_uucss_settings',self::$global_options);
        }

        if(isset(self::$options['uucss_ignore_inlined_styles'])){
            self::$global_options['uucss_ignore_inlined_styles'] = self::$options['uucss_ignore_inlined_styles'];
            RapidLoad_Base::update_option('autoptimize_uucss_settings',self::$global_options);
        }

        error_log(json_encode(self::$options, JSON_PRETTY_PRINT));

        self::post_optimizer_function($result);

    }

    public function associate_domain($revoke){
        $api = new RapidLoad_Api();
        if(!$revoke){
            $api->post('spai-associate-host',[
                'url' => trailingslashit(site_url()),
                'action' => 'add-domain'
            ]);
        }else{
            $api->post('spai-associate-host',[
                'url' => trailingslashit(site_url()),
                'action' => 'revoke-domain'
            ]);
        }

    }

    public function preload_page(){

        self::verify_nonce();

        if(!isset($_REQUEST['url'])){
            wp_send_json_error('url required');
        }

        $url = sanitize_url(wp_unslash($_REQUEST['url']));

        if(self::$global_options['rapidload_test_mode'] && self::$global_options['rapidload_test_mode'] == "1"){
            $url = add_query_arg('rapidload_preview', 'true', $url);
        }

        $agent = isset($_REQUEST['user_agent']) ? sanitize_text_field(wp_unslash($_REQUEST['user_agent'])) : null;

        $response = wp_remote_get( $url, array( 'timeout' => 30, 'headers' => [
            'User-Agent' => $agent
        ] ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_headers = wp_remote_retrieve_headers($response);

        $result = array(
            'success' => true,
            'data' => "Ping to {$url} successful.",
            'response_code' => $response_code,
            'headers' => $response_headers->getAll()
        );

        wp_send_json_success($result);
    }
    
    
}