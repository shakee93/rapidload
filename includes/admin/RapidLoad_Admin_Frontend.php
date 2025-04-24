<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Admin_Frontend
{

    use RapidLoad_Utils;

    public $options;

    public function __construct()
    {
        $this->options = RapidLoad_Base::rapidload_fetch_options();

        add_action('admin_menu', [$this, 'rapidload_menu_item']);

        new RapidLoad_Admin_Bar();

        add_action('init', function (){

            if($this->is_rapidload_legacy_page()){

                $this->rapidload_load_legacy_scripts();

            }

            if ($this->rapidload_is_rapidload_page()) {

                $this->rapidload_load_scripts();

                // TODO: temporary should be removed so it supports all the browsers
                add_filter('script_loader_tag', function ($tag, $handle) {

                    if ( 'rapidload_admin_frontend' !== $handle )
                        return $tag;

                    return str_replace( ' src', ' type="module" src', $tag );

                }, 10, 2);

            }

            if ($this->rapidload_is_rapidload_on_board()) {

                $this->rapidload_load_on_board_scripts();

                // TODO: temporary should be removed so it supports all the browsers
                add_filter('script_loader_tag', function ($tag, $handle) {

                    if ( 'rapidload_admin_on_board' !== $handle )
                        return $tag;

                    return str_replace( ' src', ' type="module" src', $tag );

                }, 10, 2);

            }

        });

        $this->rapidload_load_legacy_ajax();

        if(is_admin()){

            add_action('admin_menu', [$this, 'rapidload_add_developer_settings_page']);
            add_action('admin_menu', [$this, 'rapidload_add_page_optimizer_page']);
            add_action('admin_menu', [$this, 'rapidload_remove_legacey_dashboard_menu'], 999);

        }

        add_action( "rapidload_run_gpsi_test_for_all", [$this, 'rapidload_run_gpsi_test_for_all']);

        $this->rapidload_purge_all_process_request();
    }

    public function rapidload_add_developer_settings_page() {

        global $submenu;

        add_submenu_page( 'options-general.php', 'RapidLoad', 'RapidLoad', 'manage_options', 'uucss_legacy', function () {
            wp_enqueue_script( 'post' );

            ?>
            <div class="uucss-wrap">
                <?php
                do_action('uucss/options/before_render_form');
                ?>
                <div>
                    <?php $this->rapidload_render_developer_settings_page() ?>
                </div>
            </div>

            <?php
        });

        register_setting('autoptimize_uucss_settings', 'autoptimize_uucss_settings', function($value) {
            return is_array($value) ? $value : [];
        });

        $key = null;

        if(!isset($submenu['options-general.php'])){
            return;
        }

        $key = array_search(["RapidLoad","manage_options","uucss_legacy","RapidLoad"], $submenu['options-general.php']);

        if(isset($submenu['options-general.php'][$key])){
            unset($submenu['options-general.php'][$key]);
        }

    }

    public function rapidload_render_developer_settings_page(){
        $options = RapidLoad_Base::rapidload_fetch_options();

        include('views/developer-settings-page.html.php');
    }

    public function rapidload_load_legacy_ajax(){

        if(is_admin()){

            add_action("wp_ajax_uucss_run_gpsi_status_check_for_all", [ $this, 'rapidload_run_gpsi_status_check_for_all' ] );
            add_action("wp_ajax_get_all_rules", [$this, 'rapidload_get_all_rules']);
            add_action("wp_ajax_upload_rules", [$this, 'rapidload_upload_rules']);
            add_action("wp_ajax_rapidload_purge_all", [$this, 'rapidload_purge_all']);
            add_action("wp_ajax_uucss_test_url", [ $this, 'uucss_test_url' ] );
            add_action("wp_ajax_uucss_data", [ $this, 'uucss_data' ] );
            add_action("wp_ajax_rapidload_notifications", [$this, 'rapidload_notifications']);
            add_action("wp_ajax_uucss_update_rule", [ $this, 'uucss_update_rule' ] );
            add_action("wp_ajax_mark_faqs_read", [$this, 'mark_faqs_read']);
            add_action("wp_ajax_mark_notice_read", [$this, 'mark_notice_read']);
            add_action("wp_ajax_suggest_whitelist_packs", [ $this, 'suggest_whitelist_packs' ] );
            add_action("wp_ajax_update_htaccess_file", [$this, "wp_ajax_update_htaccess_file"]);
            add_action("wp_ajax_nopriv_update_htaccess_file", [$this, "wp_ajax_update_htaccess_file"]);
        }

    }

    public function wp_ajax_update_htaccess_file(){

        self::rapidload_util_verify_nonce();

        RapidLoad_htaccess::update_htaccess(isset($_REQUEST['remove']));

        wp_send_json_success(true);

    }

    public function suggest_whitelist_packs(){
        self::rapidload_util_verify_nonce();
        RapidLoad_Base::suggest_whitelist_packs();
    }

    public function mark_notice_read(){

        self::rapidload_util_verify_nonce();

        $notice_id = isset($_REQUEST['notice_id']) ? sanitize_text_field(wp_unslash($_REQUEST['notice_id'])) : false;

        if($notice_id){
            RapidLoad_Base::update_option('uucss_notice_' . $notice_id . '_read', true);
        }

        wp_send_json_success(true);
    }

    public function mark_faqs_read(){

        self::rapidload_util_verify_nonce();

        RapidLoad_Base::update_option('rapidload_faqs_read', true);
        wp_send_json_success(true);
    }

    public function uucss_update_rule(){

        self::rapidload_util_verify_nonce();

        if( !isset($_REQUEST['rule']) || empty($_REQUEST['rule']) ||
            !isset($_REQUEST['url']) || empty($_REQUEST['url'])
        ){
            wp_send_json_error('Required fields missing');
        }

        $rule = sanitize_text_field( wp_unslash($_REQUEST['rule']) );
        $url = sanitize_url( wp_unslash($_REQUEST['url']) );
        $regex = isset($_REQUEST['regex']) ? sanitize_text_field( wp_unslash($_REQUEST['regex']) ) : '/';

        $url = $this->rapidload_util_transform_url($url);

        global $uucss;

        if(!$this->is_url_allowed($url)){
            wp_send_json_error('URL not allowed');
        }

        if(!self::is_url_glob_matched($url, $regex)){
            wp_send_json_error('Invalid regex for the url');
        }

        if(isset($_REQUEST['old_rule']) && isset($_REQUEST['old_regex'])){

            $old_rule = sanitize_text_field( wp_unslash($_REQUEST['old_rule']) );
            $old_regex = sanitize_text_field( wp_unslash($_REQUEST['old_regex']) );

            $rule_exist = new RapidLoad_Job([
                'rule' => $old_rule,
                'regex' => $old_regex
            ]);

            $new_rule = new RapidLoad_Job([
                'url' => $url
            ]);

            if(isset($new_rule->id) && $new_rule->rule === "is_url"){
               $new_rule->delete();
            }

            if(isset($rule_exist->id)){

                $rule_exist->url = $url;
                $rule_exist->rule = $rule;
                $rule_exist->regex = $regex;
                $rule_exist->save(true);

                if(isset($_REQUEST['old_url']) && sanitize_text_field(wp_unslash($_REQUEST['old_url'])) !== $url ||
                    sanitize_text_field(wp_unslash($_REQUEST['old_rule'])) !== $rule || sanitize_text_field(wp_unslash($_REQUEST['old_regex'])) !== $regex){
                    if(isset($_REQUEST['requeue']) && sanitize_text_field(wp_unslash($_REQUEST['requeue'])) === "1"){
                        RapidLoad_DB::requeueJob($rule_exist->id);
                    }
                }

                wp_send_json_success('Rule updated successfully');
            }

        }

        $new_rule = new RapidLoad_Job([
            'url' => $url
        ]);

        if(isset($new_rule->id) && $new_rule->rule === "is_url"){
            $new_rule->delete();
        }

        $rule = new RapidLoad_Job([
            'rule' => $rule,
            'regex' => $regex
        ]);

        if(isset($rule->id)){
            wp_send_json_error('Rule already exist');
        }

        $rule->url = $url;
        $rule->save(true);

        wp_send_json_success('Rule updated successfully');
    }

    function rapidload_notifications(){

        self::rapidload_util_verify_nonce();

        wp_send_json_success([
            'faqs' => $this->get_faqs(),
            'notifications' => $this->get_public_notices()
        ]);

    }

    public function get_public_notices(){

        $api = new RapidLoad_Api();

        $result = $api->rapidload_api_get('notification');

        $data = !$api->is_error($result) && isset($result->data) ? $result->data : [];

        $data = array_filter($data, function ($notice){
            $notice_read = RapidLoad_Base::get_option('uucss_notice_' . $notice->id . '_read');
            return empty($notice_read);
        });

        $keys = array_keys($data);

        if(empty($keys)){
            return $data;
        }

        $notices = [];

        foreach ($data as $key => $notice){
            array_push($notices, $notice);
        }

        return $notices;
    }

    public function get_faqs(){

        $rapidload_faqs_read = RapidLoad_Base::get_option('rapidload_faqs_read');

        if(!empty($rapidload_faqs_read)){
            return [];
        }

        $api = new RapidLoad_Api();

        $result = $api->rapidload_api_get('faqs');

        $default = [
            [
                "title" => "I enabled RapidLoad and now my site is broken. What do I do?",
                "message" => "If you are encountering layout or styling issues on a RapidLoad optimized page, try enabling the \"Load Original CSS Files\" option or <a href='https://rapidload.zendesk.com/hc/en-us/articles/360063292673-Sitewide-Safelists-Blocklists'>adding safelist rules</a> for affected elements in the plugin Advanced Settings. Always remember to requeue affected pages after making plugin changes. Need more help? Head over to the RapidLoad docs for more information or to submit a Support request: <a href='https://rapidload.zendesk.com/hc/en-us'>https://rapidload.zendesk.com/hc/en-us</a>",
            ],
            [
                "title" => "Why am I still seeing the \"Removed unused CSS\" flag in Google Page Speed Insights?",
                "message" => "It's possible that the RapidLoad optimized version of the page is not yet being served. Try clearing your page cache and running the GPSI test again.",
            ],
            [
                "title" => "Will this plugin work with other caching plugins?",
                "message" => "RapidLoad works with all major caching plugins. If you are using a little known caching plugin and are experiencing issues with RapidLoad, please submit your issue and caching plugin name to our support team and we will review.",
            ],
            [
                "title" => "Do I need to run this every time I make a change?",
                "message" => "No! RapidLoad works in the background, so any new stylesheets that are added will be analyzed and optimized on the fly. Just set it and forget it!",
            ],
            [
                "title" => "Do you offer support if I need it?",
                "message" => "Yes, our team is standing by to assist you! Submit a support ticket any time from the Support tab in the plugin and we'll be happy to help.",
            ]
        ];

        return !$api->is_error($result) && isset($result->data) ? $result->data : $default;
    }


    public function uucss_test_url(){

        self::rapidload_util_verify_nonce();

        global $uucss;

        if(!isset($_REQUEST['url'])){
            wp_send_json_error('url required');
        }

        $url = sanitize_url(wp_unslash($_REQUEST['url']));
        $type = isset($_REQUEST['type']) ? sanitize_text_field(wp_unslash($_REQUEST['type'])) : 'path';

        if($type === 'rule'){

            if(!isset($_REQUEST['rule']) || !isset($_REQUEST['regex'])){
                wp_send_json_error('rule and regex required');
            }

        }

        $uucss_api = new RapidLoad_Api();

        $link = $type === 'path' ? new RapidLoad_Job(['url' => $url]) : new RapidLoad_Job(['rule' => sanitize_text_field(wp_unslash($_REQUEST['rule'])), 'regex' => sanitize_text_field(wp_unslash($_REQUEST['regex']))]);

        $result = $this->get_gpsi_test_result(new RapidLoad_Job_Data($link, 'uucss'));

        if ( $uucss_api->is_error( $result ) ) {
            if(isset($result->errors) && isset($result->errors[0])){
                wp_send_json_error($result->errors[0]->detail);
            }else{
                wp_send_json_error($result);
            }
        }

        wp_send_json_success($result);
    }

    public function uucss_data(){

        self::rapidload_util_verify_nonce();

        $type = isset($_REQUEST['type']) ? sanitize_text_field(wp_unslash($_REQUEST['type'])) : 'path';

        $start = isset($_REQUEST['start']) ? absint($_REQUEST['start']) : 0;
        $length = isset($_REQUEST['length']) ? absint($_REQUEST['length']) : 5;

        $length = 50;

        $draw = isset($_REQUEST['draw']) ? absint($_REQUEST['draw']) : 1;

        $status_filter = isset($_REQUEST['columns']) &&
        isset($_REQUEST['columns'][0]) &&
        isset($_REQUEST['columns'][0]['search']) &&
        isset($_REQUEST['columns'][0]['search']['value']) ?
            sanitize_text_field(wp_unslash($_REQUEST['columns'][0]['search']['value'])) : false;

        $url_filter = isset($_REQUEST['columns']) &&
        isset($_REQUEST['columns'][1]) &&
        isset($_REQUEST['columns'][1]['search']) &&
        isset($_REQUEST['columns'][1]['search']['value']) ?
            sanitize_text_field(wp_unslash($_REQUEST['columns'][1]['search']['value'])) : false;

        $url_regex = isset($_REQUEST['columns']) &&
        isset($_REQUEST['columns'][1]) &&
        isset($_REQUEST['columns'][1]['search']) &&
        isset($_REQUEST['columns'][1]['search']['regex']) ?
            sanitize_text_field(wp_unslash($_REQUEST['columns'][1]['search']['regex'])) : false;

        $filters = [];

        if($status_filter){

            if($status_filter === 'warning'){

                $filters[] = " warnings IS NOT NULL ";

            }else{

                $filters[] = " status = '". $status_filter . "' AND warnings IS NULL ";
            }

        }else{

            $filters[] = " status !== 'rule-based' ";

        }

        if($url_regex === 'true' && $url_filter){

            $filters[] = " url = '". $url_filter . "' ";

        }

        if($url_regex === 'false' && $url_filter){

            $filters[] = " url LIKE '%%". $url_filter . "%%' ";

        }

        $where_clause = '';

        foreach ($filters as $key => $filter){

            if($key === 0){

                $where_clause = " WHERE ";
                $where_clause .= $filter;
            }else{

                $where_clause .= " AND ";
                $where_clause .= $filter;
            }

        }

        $data  = RapidLoad_DB::get_merged_data($type === "rule", $start, $length);

        wp_send_json([
            'data' => $data,
            "draw" => (int)$draw,
            "recordsTotal" => RapidLoad_DB::get_total_job_count($type === "path" ? " where rule = 'is_url' and regex = '/'" : " where rule !== 'is_url'"),
            "recordsFiltered" => RapidLoad_DB::get_total_job_count($where_clause),
            "success" => true
        ]);

    }

    public function rapidload_run_gpsi_test_for_all(){

        $links = UnusedCSS_DB::get_data_for_gpsi_test();

        if(!empty($links)){

            foreach ($links as $link){

                if($link->hits > 0){
                    continue;
                }

                $this->get_gpsi_test_result(RapidLoad_Job_Data::find_or_fail($link->id, 'uucss'));

            }

        }

    }

    public function rapidload_run_gpsi_status_check_for_all(){

        self::rapidload_util_verify_nonce();

        $spawned = wp_schedule_single_event( time() + 5, 'rapidload_run_gpsi_test_for_all');

        wp_send_json_success([
            'spawned' => $spawned
        ]);
    }

    public function get_gpsi_test_result($job_data){

        if(!$job_data){
            return null;
        }

        $uucss_api = new RapidLoad_Api();

        $cached_files = [];
        $original_files = [];

        $files = $job_data->get_files();

        if(isset($files) && !empty($files)){

            $cached_files = array_filter($files, function ($file){
                return !$this->rapidload_util_str_contains($file['original'], '//inline-style@');
            });

            $original_files = array_filter($files, function ($file){
                return !$this->rapidload_util_str_contains($file['original'], '//inline-style@');
            });
        }

        do_action( 'uucss/cached', [
            'url' => $job_data->job->url
        ]);

        return $uucss_api->rapidload_api_post( 'test/wordpress',
            [
                'url' => urldecode($job_data->job->url),
                'files' => !empty($cached_files) ? array_values(array_column($cached_files, 'uucss')) : [],
                'aoFiles' => !empty($original_files) ? array_values(array_column($original_files, 'original')) : []
            ]);

    }

    public function rapidload_purge_all_process_request(){

        if ( empty( $_GET['_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ), 'uucss_nonce' ) || !current_user_can('manage_options')) {
            return;
        }

        $job_type = isset($_GET['_job_type']) ? sanitize_text_field(wp_unslash($_GET['_job_type'])) : 'all';
        $url = isset($_GET['_url']) ? sanitize_url(wp_unslash($_GET['_url'])) : false;
        $clear = isset($_GET['_clear']) && boolval($_GET['_clear'] === 'true') ? true : false;

        if($clear){
            if ($url){
                RapidLoad_DB::clear_job_data($job_type, [
                    'url' => $url
                ]);
                RapidLoad_DB::clear_jobs($job_type, [
                    'url' => $url
                ]);
            }else{
                //RapidLoad_DB::clear_job_data($job_type);
                RapidLoad_DB::clear_jobs($job_type);
                do_action('rapidload/vanish');
            }

            wp_safe_redirect( remove_query_arg( array( '_job_type', '_url', '_clear', '_nonce' ) ) );
        }
    }

    public function rapidload_purge_all(){

        self::rapidload_util_verify_nonce();

        $job_type = isset($_REQUEST['job_type']) ? sanitize_text_field(wp_unslash($_REQUEST['job_type'])    ) : 'all';
        $url = isset($_REQUEST['url']) ? sanitize_url(wp_unslash($_REQUEST['url'])) : false;
        $rule = isset($_REQUEST['rule']) ? sanitize_text_field(wp_unslash($_REQUEST['rule'])) : false;
        $regex = isset($_REQUEST['regex']) ? sanitize_text_field(wp_unslash($_REQUEST['regex'])) : false;
        $clear = isset($_REQUEST['clear']) && boolval($_REQUEST['clear'] === 'true' || $_REQUEST['clear'] === '1') ? true : false;
        $url_list = isset($_REQUEST['url_list']) ? $_REQUEST['url_list'] : [];
        $immediate = isset($_REQUEST['immediate']) ? boolval($_REQUEST['immediate']) : false;

        if($clear){

            if(!empty($url_list)){
                self::log([
                   'log' => 'all jobs cleared'
                ]);
                RapidLoad_DB::clear_job_data($job_type, [], $url_list);
                RapidLoad_DB::clear_jobs($job_type, [], $url_list);

            }else{

                if($rule && $regex){
                    RapidLoad_DB::clear_job_data($job_type, [
                        'rule' => $rule,
                        'regex' => $regex
                    ]);
                    RapidLoad_DB::clear_jobs($job_type, [
                        'rule' => $rule,
                        'regex' => $regex
                    ]);
                }elseif ($job_type === "css"){
                    do_action('rapidload/vanish/css');
                }elseif ($job_type === "js"){
                    do_action('rapidload/vanish/js');
                }elseif ($job_type === "fonts"){
                    do_action('rapidload/vanish/font');
                }elseif ($url){
                    RapidLoad_DB::clear_job_data($job_type, [
                        'url' => $url
                    ]);
                    RapidLoad_DB::clear_jobs($job_type, [
                        'url' => $url
                    ]);
                }else{
                    //RapidLoad_DB::clear_job_data($job_type);
                    RapidLoad_DB::clear_jobs($job_type);
                    do_action('rapidload/vanish');
                }

            }

        }
        else{

            $url = $this->rapidload_util_transform_url($url);

            switch ($job_type){

                case 'requeue_all_rule': {
                    if (!empty($url_list)) {
                        RapidLoad_DB::updateRuleJobDataStatusWhere("queued", $url_list);
                    } else {
                        RapidLoad_DB::updateRuleJobDataStatusWhere("queued", []);
                    }
                    break;
                }
                case 'requeue_all_rule_processing': {
                    RapidLoad_DB::updateRuleJobDataStatusWhere("queued", [], "processing");
                    break;
                }
                case 'requeue_all_rule_warnings': {
                    RapidLoad_DB::updateRuleJobDataStatusWhere("queued", [], "success");
                    break;
                }
                case 'requeue_all_rule_failed': {
                    RapidLoad_DB::updateRuleJobDataStatusWhere("queued", [], "failed");
                    break;
                }
                case 'requeue_all_url': {
                    if (!empty($url_list)) {
                        RapidLoad_DB::updateUrlJobDataStatusWhere("queued", $url_list);
                    } else {
                        RapidLoad_DB::updateUrlJobDataStatusWhere("queued", []);
                    }
                    break;
                }
                case 'requeue_all_url_processing':{
                    RapidLoad_DB::updateUrlJobDataStatusWhere("queued", [], "processing");
                    break;
                }
                case 'requeue_all_url_warnings':{
                    RapidLoad_DB::updateUrlJobDataStatusWhere("queued", [], "success");
                    break;
                }
                case 'requeue_all_url_failed':{
                    RapidLoad_DB::updateUrlJobDataStatusWhere("queued", [], "failed");
                    break;
                }
                case 'url':{

                    if($url){

                        if($this->is_url_allowed($url)){

                            $job = new RapidLoad_Job(['url' => $url]);
                            $job->save(true);

                            $args = [
                                'requeue' => true
                            ];

                            if($immediate){
                                $args['immediate'] = true;
                            }

                            do_action('rapidload/job/purge', $job, $args);

                        }
                    }
                    break;
                }
                case 'rule':{

                    if($url && $rule && $regex){

                        $this->update_rule((object)[
                            'url' => $url,
                            'rule' => $rule,
                            'regex' => $regex,
                            'immediate' => $immediate,
                            'requeue' => true
                        ]);
                    }
                    break;
                }
                case 'site_map':{

                    if($url){

                        $spawned = $this->schedule_cron('add_sitemap_to_jobs',[
                            'url' => $url
                        ]);
                    }
                    break;
                }
                default:{

                    if($url){

                        $job = new RapidLoad_Job(['url' => $url]);
                        $job->save(true);

                        $args = [
                            'requeue' => true
                        ];

                        if($immediate){
                            $args['immediate'] = true;
                        }

                        do_action('rapidload/job/purge', $job, $args);

                    }

                    $posts = new WP_Query(array(
                        'post_type'=> $job_type,
                        'posts_per_page' => -1
                    ));

                    if($posts && $posts->have_posts()){

                        while ($posts->have_posts()){

                            $posts->the_post();

                            $url = $this->rapidload_util_transform_url(get_the_permalink(get_the_ID()));

                            if($this->is_url_allowed($url)){

                                $job = new RapidLoad_Job(['url' => $url]);
                                $job->save(true);
                            }
                        }
                    }

                    wp_reset_postdata();

                    break;
                }
            }
        }
        wp_send_json_success('Successfully purged');
    }

    public function rapidload_upload_rules(){

        self::rapidload_util_verify_nonce();

        if(!isset($_REQUEST['rules'])){
            wp_send_json_error('rules required');
        }

        $rules = json_decode(sanitize_text_field(wp_unslash($_REQUEST['rules'])));

        if(!$rules){
            wp_send_json_error('rules required');
        }

        foreach ($rules as $rule){

            $rule_job = new RapidLoad_Job([
                'url' => $rule->url,
                'rule' => $rule->rule,
                'regex' => $rule->regex
            ]);
            $rule_job->save(true);
        }

        wp_send_json_success('success');
    }

    public function rapidload_get_all_rules(){

        self::rapidload_util_verify_nonce();

        wp_send_json_success(RapidLoad_Job::all_rules());

    }

    public function rapidload_add_page_optimizer_page() {

        global $submenu;

        add_submenu_page( 'options-general.php', 'RapidLoad Optimizer', 'RapidLoad Optimizer', 'manage_options', 'rapidload-page-optimizer', function () {
            wp_enqueue_script( 'post' );

            ?>
            <div id="rapidload-page-optimizer">

            </div>

            <?php
        });

        $key = null;

        if(!isset($submenu['options-general.php'])){
            return;
        }

        $key = array_search(["RapidLoad Optimizer","manage_options","rapidload-page-optimizer","RapidLoad Optimizer"], $submenu['options-general.php']);

        if(isset($submenu['options-general.php'][$key])){
            unset($submenu['options-general.php'][$key]);
        }

    }

    public function rapidload_is_rapidload_page()
    {
        return isset($_GET['page']) && $_GET['page'] === 'rapidload-legacy-dashboard';
    }

    public function rapidload_is_rapidload_on_board()
    {
        return isset($_GET['page']) && $_GET['page'] === 'rapidload-on-board';
    }

    public function is_rapidload_page_optimizer()
    {
        return isset($_GET['page']) && $_GET['page'] === 'rapidload-page-optimizer';
    }

    public function is_rapidload_legacy_page()
    {
        return isset($_GET['page']) && $_GET['page'] === 'uucss_legacy';
    }

    public function rapidload_load_scripts()
    {

        wp_enqueue_style( 'rapidload_admin_frontend', UUCSS_PLUGIN_URL .  'includes/admin/frontend/dist/assets/index.css',[],UUCSS_VERSION);

        wp_register_script( 'rapidload_admin_frontend', UUCSS_PLUGIN_URL .  'includes/admin/frontend/dist/assets/index.min.js',[], UUCSS_VERSION);

        $data = array(
            'frontend_base' => UUCSS_PLUGIN_URL .  'includes/admin/frontend/dist'
        );

        wp_localize_script( 'rapidload_admin_frontend', 'rapidload_admin', $data );

        wp_enqueue_script( 'rapidload_admin_frontend' );



    }

    public function rapidload_load_optimizer_scripts()
    {
        wp_enqueue_style( 'rapidload_page_optimizer', UUCSS_PLUGIN_URL .  'includes/admin/page-optimizer/dist/assets/index.css',[],UUCSS_VERSION);

        wp_register_script( 'rapidload_page_optimizer', UUCSS_PLUGIN_URL .  'includes/admin/page-optimizer/dist/assets/index.js',[], UUCSS_VERSION);

        $data = array(
            'page_optimizer_base' => UUCSS_PLUGIN_URL .  'includes/admin/page-optimizer/dist',
            'plugin_url' => UUCSS_PLUGIN_URL,
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'optimizer_url' => $this->rapidload_util_transform_url($this->rapidload_util_get_current_url()),
            'nonce' => self::rapidload_util_create_nonce( 'uucss_nonce' ),
        );

        wp_localize_script( 'rapidload_page_optimizer', 'rapidload_admin', $data );

        wp_enqueue_script( 'rapidload_page_optimizer' );
    }

    public function rapidload_load_on_board_scripts()
    {

        wp_enqueue_style( 'rapidload_admin_on_board', UUCSS_PLUGIN_URL .  'includes/admin/on-board/on-board-view/dist/assets/index.css', null, '1.23');

        wp_register_script( 'rapidload_admin_on_board', UUCSS_PLUGIN_URL .  'includes/admin/on-board/on-board-view/dist/assets/index.js', null, '1.23');

        $data = array(
            'on_board_base' => UUCSS_PLUGIN_URL .  'includes/admin/on-board/on-board-view/dist'
        );

        wp_localize_script( 'rapidload_admin_on_board', 'rapidload_admin', $data );

        wp_enqueue_script( 'rapidload_admin_on_board' );



    }

    public function rapidload_load_legacy_scripts(){

        $deregister_scripts = apply_filters('uucss/scripts/deregister', ['select2']);

        if(isset($deregister_scripts) && is_array($deregister_scripts)){
            foreach ($deregister_scripts as $deregister_script){
                wp_dequeue_script($deregister_script);
                wp_deregister_script($deregister_script);
            }
        }

        wp_enqueue_script( 'select2', UUCSS_PLUGIN_URL . 'assets/libs/select2/select2.min.js', array( 'jquery' ) );

        wp_enqueue_script( 'datatables', UUCSS_PLUGIN_URL . 'assets/libs/datatables/jquery.dataTables.min.js', array(
            'jquery',
            'uucss_admin'
        ) );
        wp_enqueue_style( 'datatables', UUCSS_PLUGIN_URL . 'assets/libs/datatables/jquery.dataTables.min.css' );

        wp_register_script( 'uucss_admin', UUCSS_PLUGIN_URL . 'assets/js/uucss_admin.js', array(
            'jquery',
            'wp-util'
        ), UUCSS_VERSION );

        wp_register_script( 'uucss_log', UUCSS_PLUGIN_URL . 'assets/js/uucss_log.js', array(
            'jquery',
            'wp-util'
        ), UUCSS_VERSION );

        $deregister_styles = apply_filters('uucss/styles/deregister',[]);

        if(isset($deregister_styles) && is_array($deregister_styles)){
            foreach ($deregister_styles as $deregister_style){
                wp_dequeue_style($deregister_style);
            }
        }

        wp_enqueue_style( 'uucss_admin', UUCSS_PLUGIN_URL . 'assets/css/uucss_admin.css', [], UUCSS_VERSION );

        global $rapidload;

        $data = array(
            'api' => RapidLoad_Api::rapidload_api_get_key(),
            'nonce' => self::rapidload_util_create_nonce( 'uucss_nonce' ),
            'url' => site_url(),
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'setting_url'       => admin_url( 'options-general.php?page=uucss_legacy' ),
            'on_board_complete' => apply_filters('uucss/on-board/complete', RapidLoad_Onboard::rapidload_on_board_completed()),
            'api_key_verified' => RapidLoad_Base::is_api_key_verified(),
            'notifications' => $this->getNotifications(),
            'faqs' => [],
            'public_notices' => [],
            'dev_mode' => apply_filters('uucss/dev_mode', isset($this->options['uucss_dev_mode'])) && $this->options['uucss_dev_mode'] === "1",
            'rules_enabled' => $rapidload->rules_enabled(),
            'cpcss_enabled' => $rapidload->critical_css_enabled(),
            'home_url' => home_url(),
            'uucss_enable_debug' => ! empty( $this->options['uucss_enable_debug'] ) && '1' === $this->options['uucss_enable_debug'],
        );

        wp_localize_script( 'uucss_admin', 'uucss', $data );

        wp_enqueue_script( 'uucss_admin' );
        wp_enqueue_script( 'uucss_log' );

        wp_enqueue_style( 'select2', UUCSS_PLUGIN_URL . 'assets/libs/select2/select2.min.css' );

    }

    public function getNotifications() {

        return apply_filters('uucss/notifications', []);
    }

    public function rapidload_menu_item()
    {

        add_menu_page(
            'RapidLoad',
            'RapidLoad',
            'edit_posts',
            'rapidload-legacy-dashboard',
            [$this, 'page_legacy'],
            UUCSS_PLUGIN_URL. 'assets/images/logo-icon-light.svg',
            59
        );

        add_menu_page(
            'RapidLoad',
            'RapidLoad',
            'edit_posts',
            'rapidload',
            [$this, 'page'],
            UUCSS_PLUGIN_URL. 'assets/images/logo-icon-light.svg',
            59
        );

    }

    public function update_rule($args, $old = false){

        $job = null;

        if($old && isset($old['url'])){

            $job = new RapidLoad_Job([
                'url' => $old['url']
            ]);

            $job->url = $args->url;
            $job->rule = $args->rule;
            $job->regex = $args->regex;

            $job->save();

        }else{

            $job = new RapidLoad_Job([
                'url' => $args->url
            ]);
            $job->rule = $args->rule;
            $job->regex = $args->regex;

            $job->save();

        }

        $_args = [
            'requeue' => true
        ];

        if(isset($args->immediate) && $args->immediate){
            $_args['immediate'] = true;
        }

        if(isset($args->requeue) && $args->requeue){
            do_action('rapidload/job/purge', $job, $_args);
        }


    }

    public function page_legacy(){

        ?>
        <style>
            #wpcontent {
                padding-left: 0 !important;
            }
           
            .notice {
                display: none !important;
            }
        </style>
        <div id="rapidload-app">  </div><?php

    }

    public function page(){

        ?>
        <div id="rapidload-page-optimizer">  </div>
        <?php

    }

    public function rapidload_remove_legacey_dashboard_menu(){
        remove_menu_page('rapidload-legacy-dashboard');
    }
}