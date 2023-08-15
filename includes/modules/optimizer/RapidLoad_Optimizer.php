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

    static $options;
    static $global_options;
    static $merged_options = [];
    static $job;
    static $strategy;
    static $revision_limit;

    public function __construct(){

        self::$revision_limit = apply_filters('rapidload/optimizer/revision-limit', 10);

        add_action('wp_ajax_fetch_page_speed', [$this, 'fetch_page_speed']);
        add_action('wp_ajax_nopriv_fetch_page_speed', [$this, 'fetch_page_speed']);

        add_action('wp_ajax_optimizer_update_settings', [$this,'optimizer_update_settings']);
        add_action('wp_ajax_nopriv_optimizer_update_settings', [$this,'optimizer_update_settings']);

        add_filter('rapidload/enqueue/preload/fonts', function ($urls, $job, $strategy){

            $options = $strategy === "mobile" ? $job->get_mobile_options() : $job->get_desktop_options();

            if(isset($options['individual-file-actions']) && isset($options['individual-file-actions']['font-display'])){

                foreach ($options['individual-file-actions']['font-display'] as $value){

                    if (isset($value->url) && isset($value->url->url) && filter_var($value->url->url, FILTER_VALIDATE_URL) !== false) {

                        $path_parts = pathinfo($value->url->url);

                        if(isset($path_parts['extension'])){
                            $file_extension = strtolower($path_parts['extension']);

                            if(in_array($file_extension, ['woff2', 'woff' , 'ttf'])){

                                if(isset($value->action) && $value->action == "preload"){
                                    $urls[] = $value->url->url;
                                }
                            }
                        }
                    }
                }
            }
            return $urls;
        }, 10, 3);

        add_action('rapidload/enqueue/optimize-js', function ($link, $job, $strategy){

            $options = $strategy == "mobile" ? $job->get_mobile_options() : $job->get_desktop_options();

            if(isset($options['individual-file-actions'])){

                foreach ($options['individual-file-actions'] as $option){

                    if(isset($option->url) && gettype($option->url) == "object" && isset($option->url->url) && isset($option->url->file_type) && $option->url->file_type == "js"){

                        if(isset($option->action) && gettype($option->action) == "object" && isset($option->action) && isset($option->action->value)){

                            if(isset($link->src)){
                                if($option->action->value == "delay"){
                                    if (preg_match('/.*googletagmanager\.com\/gtag\/.*/', $link->src)) {
                                        $link->{"data-rapidload-src"} = $link->src;
                                        unset($link->src);
                                    }
                                }
                            }else if(!empty($link->innertext())){
                                if($option->action->value == "delay"){
                                    if (preg_match('/.*googletagmanager\.com\/gtag\/.*/', $link->innertext())) {
                                        $link->__set('outertext',"<noscript data-rapidload-delayed>" . $link->innertext() . "</noscript>");
                                    }
                                }
                            }

                        }

                    }

                }

            }

        }, 10 , 1);
    }

    public static function   pre_optimizer_function(){
        if(isset($_REQUEST['url']) && !empty($_REQUEST['url']) && filter_var($_REQUEST['url'], FILTER_VALIDATE_URL) !== false){
            self::$job = new RapidLoad_Job([
                'url' => $_REQUEST['url']
            ]);
            if(!isset(self::$job->id)){
                self::$job->save();
            }
        }
        if(isset($_REQUEST['strategy']) && isset(self::$job)){
            self::$strategy = $_REQUEST['strategy'];
            self::$global_options = RapidLoad_Base::fetch_options();
            self::$options = self::$strategy == "desktop" ? self::$job->get_desktop_options() : self::$job->get_mobile_options();
            foreach (self::$options as $key => $value){
                if(!isset(self::$merged_options[$key])){
                    self::$merged_options[$key] = $value;
                }
            }
            foreach (self::$global_options as $key => $value){
                if(!isset(self::$merged_options[$key])){
                    self::$merged_options[$key] = $value;
                }
            }
        }
    }

    public static function post_optimizer_function($data){

        if(!isset(self::$strategy) || !isset(self::$job) || !isset(self::$options)){
            return;
        }

        foreach (self::$options as $key => $option){

            if(!isset(self::$global_options[$key])){
                continue;
            }

            if($key == "individual-file-actions"){

                foreach (self::$options['individual-file-actions'] as $action_key => $actions){

                    if(isset($actions) && isset($actions->action) && isset($actions->action->value) && $actions->action->value == "none"){
                        unset(self::$options['individual-file-actions'][$action_key]);
                    }

                }

            }

            $option_type = gettype(self::$global_options[$key]);

            if(isset(self::$global_options[$key])){
                if($option_type == "string" && self::$global_options[$key] == $option){
                    unset(self::$options[$key]);
                }
                else if (($option_type == "object" || $option_type == "array") && json_encode($option) == json_encode(self::$global_options[$key])){
                    unset(self::$options[$key]);
                }
            }

        }

        if(self::$strategy == "desktop"){
            self::$job->set_desktop_options(self::$options);
        }else{
            self::$job->set_mobile_options(self::$options);
        }

        $hash = self::$job->get_last_optimization_revision_hash(self::$strategy);
        $new_hash = hash('md5', serialize($data));

        if($hash == $new_hash){
            return;
        }

        $revision_count = self::$job->get_revision_count(self::$strategy);

        if($revision_count > (self::$revision_limit - 1)){
            self::$job->delete_old_revision(self::$strategy, self::$revision_limit);
        }

        $optimization = new RapidLoad_Job_Optimization(self::$job, self::$strategy);
        $optimization->set_data($data);
        $optimization->save();

        self::$job->save(!self::$job->exist());

        if(isset(self::$options['uucss_enable_cache'])){
            self::$global_options['uucss_enable_cache'] = self::$options['uucss_enable_cache'];
            RapidLoad_Base::update_option('autoptimize_uucss_settings',self::$global_options);
        }
    }

    public function fetch_page_speed(){

        self::pre_optimizer_function();

        if(!isset(self::$job) || !isset(self::$strategy)){
            wp_send_json_error();
        }

        $new = isset($_REQUEST['new']) && $_REQUEST['new'] === 'true';

        $result = self::$job->get_last_optimization_revision(self::$strategy);

        if(!$result || $new){

            $api = new RapidLoad_Api();

            $url = "https://www.submeter.com/"; // isset($_REQUEST['url']) ? $_REQUEST['url'] : site_url();

            $result = $api->post('page-speed', [
                'url' => $url,
                'mobile' => self::$strategy
            ]);

            if(is_wp_error($result)){
                wp_send_json_error($result);
            }

            if(!isset($result->audits)){
                wp_send_json_error([]);
            }

            foreach ($result->audits as $audit){

                if(isset($audit->settings)){
                    foreach ($audit->settings as $settings){
                        foreach ($settings->inputs as $input){
                            if(isset(self::$merged_options[$input->key])){
                                if($input->key == "uucss_load_js_method"){
                                    $input->value = self::$merged_options[$input->key] == "defer";
                                }else{
                                    $input->value = self::$merged_options[$input->key];
                                }

                            }
                            if($input->key == "uucss_enable_uucss"){
                                $data = new RapidLoad_Job_Data(self::$job, 'uucss');
                                if($data->exist()){
                                    $data->save();
                                }
                                $input->{'value_data'} = $data->status;
                            }
                            if($input->key == "uucss_enable_cpcss"){
                                $data = new RapidLoad_Job_Data(self::$job, 'cpcss');
                                if($data->exist()){
                                    $data->save();
                                }
                                $input->{'value_data'} = $data->status;
                            }
                        }
                    }
                }

                if(isset($audit->files) && isset($audit->files->items) && !empty($audit->files->items)){
                    foreach ($audit->files->items as $item){

                        if(isset($item->url) && isset($item->url->url) && in_array($audit->id,['bootup-time','unused-javascript','render-blocking-resources','offscreen-images',
                                'unused-css-rules','legacy-javascript','font-display'])){

                            if(!isset(self::$merged_options['individual-file-actions'])){
                                self::$merged_options['individual-file-actions'] = [];
                            }

                            if(!isset(self::$merged_options['individual-file-actions'][$audit->id])){
                                self::$merged_options['individual-file-actions'][$audit->id][] = [];
                            }

                            if(isset(self::$merged_options['individual-file-actions'][$audit->id]) && is_array(self::$merged_options['individual-file-actions'][$audit->id]) && !empty(self::$merged_options['individual-file-actions'][$audit->id])){

                                $key = array_search($item->url->url, array_column(self::$merged_options['individual-file-actions'][$audit->id], 'url'));

                                if(isset($key) && is_numeric($key)){

                                    $item->action = self::$merged_options['individual-file-actions'][$audit->id][$key]['action'];

                                }else{

                                    self::$merged_options['individual-file-actions'][$audit->id][] = [
                                        'url' => $item->url->url,
                                        'action' => $item->action,
                                        'url_object' => $item->url
                                    ];

                                }

                            }
                        }
                    }
                }

            }
        }

        if(!isset($result->audits)){
            wp_send_json_error([]);
        }

        self::post_optimizer_function($result);

        wp_send_json_success([
            'page_speed' => $result,
            'revisions' => self::$job->get_optimization_revisions(self::$strategy, self::$revision_limit),
            'individual-file-actions' => isset(self::$merged_options['individual-file-actions']) ? self::$merged_options['individual-file-actions'] : []
        ]);


    }

    public function optimizer_update_settings(){

//        self::verify_nonce();

        $data = json_decode(file_get_contents('php://input'));

        if(!isset($data) || !isset($data->data)){
            wp_send_json_error('Missing required data to save the settings!');
        }

        self::pre_optimizer_function();

        if(!isset(self::$options)){
            wp_send_json_error('Missing options data to save the settings!');
        }

        $result = $data->data;

        if(isset($result->audits) && is_array($result->audits)){

            foreach ($result->audits as $audit){

                if(isset($audit->settings)){
                    foreach ($audit->settings as $settings){
                        foreach ($settings->inputs as $input){
                            switch($input->control_type ){

                                case 'checkbox' :{
                                    if(isset($input->value) && isset($input->key) && $input->value){
                                        if($input->key == "uucss_load_js_method"){
                                            self::$options[$input->key] = "defer";
                                        }else{
                                            self::$options[$input->key] = "1";
                                        }
                                    }else if(isset(self::$options[$input->key])){
                                        unset(self::$options[$input->key]);
                                    }
                                    break;
                                }
                                case 'dropdown' :
                                case 'text' :
                                case 'options' :
                                case 'textarea' :
                                case 'number' :{
                                    if(isset($input->value) && isset($input->key)){
                                        self::$options[$input->key] = $input->value;
                                    }else if(isset($new_options[$input->key])){
                                        unset(self::$options[$input->key]);
                                    }
                                    break;
                                }
                            }

                            if($input->key == "uucss_enable_uucss" || $input->key == "uucss_enable_cpcss"){
                                if(isset($input->{'value_data'})){
                                    unset($input->{'value_data'});
                                }
                            }

                        }
                    }
                }

                if(isset($audit->files) && isset($audit->files->items) && !empty($audit->files->items)){
                    foreach ($audit->files->items as $item){

                        if(isset($item->url) && isset($item->url->url) && in_array($audit->id,['bootup-time','unused-javascript','render-blocking-resources','offscreen-images',
                                'unused-css-rules','legacy-javascript','font-display'])){

                            if(!isset(self::$options['individual-file-actions'][$audit->id])){
                                self::$options['individual-file-actions'][$audit->id] = [];
                            }

                            $key = array_search($item->url, array_column(self::$options['individual-file-actions'][$audit->id], 'url'));

                            if(isset($key) && is_numeric($key)){

                                self::$options['individual-file-actions'][$audit->id][$key]['action'] = $item->action;

                            }else{
                                self::$options['individual-file-actions'][$audit->id][] = [
                                    'url' => $item->url->url,
                                    'action' => $item->action,
                                    'url_object' => $item->url
                                ];
                            }

                        }
                    }
                }

            }

        }

        if(isset(self::$options['uucss_lazy_load_images']) && self::$options['uucss_lazy_load_images'] || isset(self::$options['uucss_support_next_gen_formats']) && self::$options['uucss_support_next_gen_formats']){
            self::$options['uucss_enable_image_delivery'] = "1";
        }else{
            unset(self::$options['uucss_enable_image_delivery']);
        }

        if(isset(self::$options['uucss_self_host_google_fonts']) && self::$options['uucss_self_host_google_fonts'] == "1"){
            self::$options['uucss_enable_font_optimization'] = "1";
        }else{
            unset(self::$options['uucss_self_host_google_fonts']);
        }

        if(isset(self::$options['uucss_minify']) && self::$options['uucss_minify'] ||
            isset(self::$options['uucss_enable_cpcss']) && self::$options['uucss_enable_cpcss'] ||
            isset(self::$options['uucss_enable_uucss']) && self::$options['uucss_enable_uucss'] ){
            self::$options['uucss_enable_css'] = "1";
        }else{
            unset(self::$options['uucss_enable_css']);
        }

        if(isset(self::$options['minify_js']) && self::$options['minify_js'] || isset(self::$options['uucss_load_js_method']) && (self::$options['uucss_load_js_method'] == "defer" || self::$options['uucss_load_js_method'] == "1")){
            self::$options['uucss_enable_javascript'] = "1";
        }else{
            unset(self::$options['uucss_enable_javascript']);
        }

        foreach (self::$options as $key => $value){
            if(isset(self::$global_options[$key]) && gettype($value) == "string" && self::$global_options[$key] == $value){
                unset(self::$options[$key]);
            }
        }

        error_log(json_encode(self::$options, JSON_PRETTY_PRINT));

        RapidLoad_Cache::setup_cache(isset(self::$options['uucss_enable_cache']) && self::$options['uucss_enable_cache'] ? "1" : "");

        $this->associate_domain(false);

        self::post_optimizer_function($result);

        wp_send_json_success('optimization updated successfully');

    }

    // old ajax actions here

    public function optimizer_enable_cache(){

        if(!isset($_REQUEST['status'])){
            wp_send_json_success('param missing');
        }

        $status = isset($_REQUEST['status']) && $_REQUEST['status'] == "on" ? "1" : "";

        RapidLoad_Cache::setup_cache($status);

        wp_send_json_success(true);

    }

    public function optimizer_serve_next_gen_images(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_error('status param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_image_delivery'] = "1";
            self::$options['uucss_support_next_gen_formats'] = "1";
            self::$options['uucss_image_optimize_level'] = "lossless";
        }else if(isset(self::$options['uucss_support_next_gen_formats'])){
            unset(self::$options['uucss_enable_image_delivery']);
            unset(self::$options['uucss_support_next_gen_formats']);
            unset(self::$options['uucss_image_optimize_level']);
        }

        $this->associate_domain(false);

        self::post_optimizer_function();

        wp_send_json_success(true);
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

    public function optimizer_self_host_google_font(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_error('status param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_font_optimization'] = "1";
            self::$options['uucss_self_host_google_fonts'] = "1";
        }else if(isset(self::$options['uucss_self_host_google_fonts'])){
            unset(self::$options['uucss_enable_font_optimization']);
            unset(self::$options['uucss_self_host_google_fonts']);
        }

        self::post_optimizer_function();

        wp_send_json_success(true);
    }

    public function optimizer_set_image_width_and_height(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_error('status param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_image_delivery'] = "1";
            self::$options['uucss_set_width_and_height'] = "1";
        }else if(isset(self::$options['uucss_set_width_and_height'])){
            unset(self::$options['uucss_enable_image_delivery']);
            unset(self::$options['uucss_set_width_and_height']);
        }

        self::post_optimizer_function();

        wp_send_json_success(true);
    }

    public function optimizer_set_unminified_css(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_success('param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_css'] = "1";
            self::$options['uucss_minify'] = "1";
        }else if(isset(self::$options['uucss_minify'])){
            unset(self::$options['uucss_enable_css']);
            unset(self::$options['uucss_minify']);
        }

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    public function optimizer_set_unminified_javascript(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_success('param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_javascript'] = "1";
            self::$options['minify_js'] = "1";
        }else if(isset(self::$options['minify_js'])){
            unset(self::$options['uucss_enable_javascript']);
            unset(self::$options['minify_js']);
        }

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    public function optimizer_set_unused_css_rules(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_success('param missing');
        }

        self::$options['uucss_enable_uucss'] =  isset($_REQUEST['status']) && $_REQUEST['status'] == "on" ? "1" : null;

        if(self::$options['uucss_enable_uucss'] == "1"){
            self::$options['uucss_enable_css'] = "1";
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_css'] = "1";
            self::$options['uucss_enable_uucss'] = "1";
        }else if(isset(self::$options['uucss_enable_uucss'])){
            unset(self::$options['uucss_enable_css']);
            unset(self::$options['uucss_enable_uucss']);
        }

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    public function optimizer_render_blocking_resources(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_success('param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_css'] = "1";
            self::$options['uucss_enable_cpcss'] = "1";
        }else if(isset(self::$options['uucss_enable_cpcss'])){
            unset(self::$options['uucss_enable_css']);
            unset(self::$options['uucss_enable_cpcss']);
        }

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    public function optimizer_offscreen_images(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_success('param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_image_delivery'] = "1";
            self::$options['uucss_lazy_load_images'] = "1";
            self::$options['uucss_lazy_load_iframes'] = "1";
            self::$options['uucss_exclude_above_the_fold_image_count'] = "5";
        }else if(isset(self::$options['uucss_exclude_above_the_fold_image_count'])){
            unset(self::$options['uucss_enable_image_delivery']);
            unset(self::$options['uucss_lazy_load_images']);
            unset(self::$options['uucss_lazy_load_iframes']);
            unset(self::$options['uucss_exclude_above_the_fold_image_count']);
        }

        $this->associate_domain(false);

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    public function optimizer_offscreen_images_exclude_above_the_fold(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['count'])){
            wp_send_json_success('param missing');
        }

        self::$options['uucss_exclude_above_the_fold_image_count'] = $_REQUEST['count'];

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    public function optimizer_defer_javascript(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['status'])){
            wp_send_json_success('param missing');
        }

        if($_REQUEST['status'] == "on"){
            self::$options['uucss_enable_javascript'] = "1";
            self::$options['uucss_load_js_method'] = "defer";
            self::$options['defer_inline_js'] = "1";
        }else if(isset(self::$options['uucss_load_js_method'])){
            unset(self::$options['uucss_enable_javascript']);
            unset(self::$options['uucss_load_js_method']);
            unset(self::$options['defer_inline_js']);
        }

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    public function optimizer_load_javascript_file_on_user_interaction(){

        if(!isset(self::$job) || !isset(self::$options) || !isset(self::$strategy)){
            wp_send_json_error('optimizer failed');
        }

        if(!isset($_REQUEST['pattern']) || !isset($_REQUEST['url']) || !isset($_REQUEST['action'])){
            wp_send_json_success('param missing');
        }

        self::$options['rapidload_load_scripts_on_user_interaction'] = isset(self::$options['rapidload_load_scripts_on_user_interaction']) ? unserialize(self::$options['rapidload_load_scripts_on_user_interaction']) : [];

        self::$options['rapidload_load_scripts_on_user_interaction'][] = [
            'url' => $_REQUEST['url'],
            'pattern' => $_REQUEST['pattern'],
            'action' => $_REQUEST['action']
        ];

        self::$options['rapidload_load_scripts_on_user_interaction'] = serialize(self::$options['rapidload_load_scripts_on_user_interaction']);

        self::post_optimizer_function();

        wp_send_json_success(true);

    }

    // old ajax actions ends here

}