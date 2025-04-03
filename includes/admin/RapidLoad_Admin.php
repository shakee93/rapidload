<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Admin
{
    use RapidLoad_Utils;

    public function __construct()
    {
        if(is_admin()){

            add_action('current_screen', [$this, 'validate_domain']);
            add_action('updated_option', [$this, 'update_cloudflare_settings'], 10, 3 );
            add_action('updated_option', [ $this, 'clear_cache_on_option_update' ], 10, 3 );
            add_action('uucss/options/after_settings_section',[$this, 'render_cloudflare_settings']);
            add_action('wp_ajax_get_robots_text', [$this, 'get_robots_text']);
            add_action('wp_ajax_frontend_logs', [$this, 'frontend_logs']);
            add_action('wp_ajax_uucss_logs', [$this, 'rapidload_logs']);
            add_action('wp_ajax_clear_uucss_logs', [$this, 'clear_rapidload_logs']);
            add_action('wp_ajax_uucss_license', [ $this, 'uucss_license' ] );
            add_action('wp_ajax_rapidload_image_usage', [ $this, 'rapidload_image_usage' ] );
            add_action('wp_ajax_rapidload_cdn_usage', [ $this, 'rapidload_cdn_usage' ] );
            add_action('wp_ajax_uucss_deactivate', [ $this, 'ajax_deactivate' ] );
            add_action('wp_ajax_uucss_connect', [ $this, 'uucss_connect' ] );
            add_action('wp_ajax_clear_page_cache', [$this, 'clear_page_cache']);
            add_action('wp_ajax_verify_api_key', [ $this, 'verify_api_key' ] );
            add_action('wp_ajax_attach_rule', [ $this, 'attach_rule' ] );
            add_action('wp_ajax_activate_module', [ $this, 'activate_module' ] );
            add_action('wp_ajax_list_module', [ $this, 'list_module' ] );
            add_action('wp_ajax_update_rapidload_settings', [$this, 'update_rapidload_settings']);
            add_action('wp_ajax_purge_rapidload_cdn', [$this, 'purge_rapidload_cdn']);
            add_action('wp_ajax_rapidload_titan_feedback', [$this, 'rapidload_titan_feedback']);
            add_action('wp_ajax_rapidload_enable_cdn_metering', [$this, 'rapidload_enable_cdn_metering']);
            add_action('wp_ajax_rapidload_enable_cdn_metering', [$this, 'rapidload_enable_image_metering']);
            add_action('wp_ajax_rapidload_delete_titan_optimizations', [$this, 'rapidload_delete_titan_optimizations']);
            add_action('wp_ajax_rapidload_titan_optimizations_data', [$this, 'rapidload_titan_optimizations_data']);
            add_action('wp_ajax_rapidload_fetch_post_types_with_links', [$this, 'rapidload_fetch_post_types_with_links']);
            add_action('wp_ajax_rapidload_fetch_post_search_by_title_or_permalink', [$this, 'rapidload_fetch_post_search_by_title_or_permalink']);


            add_action('wp_ajax_titan_checklist_crawler', [$this, 'titan_checklist_crawler']);
            add_action('wp_ajax_titan_checklist_cron', [$this, 'titan_checklist_cron']);
            add_action('wp_ajax_titan_checklist_plugins', [$this, 'titan_checklist_plugins']);
            add_action('wp_ajax_titan_checklist_status', [$this, 'titan_checklist_status']);
            add_action('wp_ajax_rapidload_switch_test_mode', [$this, 'rapidload_switch_test_mode']);
            add_action('wp_ajax_rapidload_onboard_skipped', [$this, 'rapidload_onboard_skipped']);
            

            if (defined('RAPIDLOAD_DEV_MODE')) {
                add_action('wp_ajax_nopriv_uucss_license', [ $this, 'uucss_license' ] );
                add_action('wp_ajax_nopriv_uucss_connect', [ $this, 'uucss_connect' ] );
                add_action('wp_ajax_nopriv_rapidload_switch_test_mode', [$this, 'rapidload_switch_test_mode']);
                add_action('wp_ajax_nopriv_titan_checklist_crawler', [$this, 'titan_checklist_crawler']);
                add_action('wp_ajax_nopriv_clear_page_cache', [$this, 'clear_page_cache']);
                add_action('wp_ajax_nopriv_titan_checklist_cron', [$this, 'titan_checklist_cron']);
                add_action('wp_ajax_nopriv_titan_checklist_plugins', [$this, 'titan_checklist_plugins']);
                add_action('wp_ajax_nopriv_titan_checklist_status', [$this, 'titan_checklist_status']);
                add_action('wp_ajax_nopriv_rapidload_delete_titan_optimizations', [$this, 'rapidload_delete_titan_optimizations']);
                add_action('wp_ajax_nopriv_rapidload_titan_optimizations_data', [$this, 'rapidload_titan_optimizations_data']);
                add_action('wp_ajax_nopriv_rapidload_fetch_post_types_with_links', [$this, 'rapidload_fetch_post_types_with_links']);
                add_action('wp_ajax_nopriv_rapidload_fetch_post_search_by_title_or_permalink', [$this, 'rapidload_fetch_post_search_by_title_or_permalink']);
            }

        }

        add_action('wp_ajax_rapidload_image_optimization_status', [ $this, 'rapidload_image_optimization_status' ] );
        add_action('wp_ajax_nopriv_rapidload_image_optimization_status', [ $this, 'rapidload_image_optimization_status' ] );

        add_action('cron_check_rapidload', function (){
            update_option('cron_check_rapidload_success',"1");
        });

        add_filter('uucss/api/options', [$this, 'inject_cloudflare_settings'], 10 , 1);
        add_filter('uucss/rules', [$this, 'rapidload_rule_types'], 90 , 1);
        add_action('add_sitemap_to_jobs', [$this, 'add_sitemap_to_jobs'], 10, 1);

    }

    public function rapidload_onboard_skipped(){
        self::verify_nonce();
        update_option('rapidload_onboard_skipped', true);
        wp_send_json_success(true);
    }

    function rapidload_image_optimization_status(){

        self::verify_nonce();

        if (!isset($_REQUEST['image_urls'])) {
            wp_send_json_error('No image URLs provided');
        }

        $image_url_status = [];

        $image_urls = json_decode(sanitize_text_field(wp_unslash($_REQUEST['image_urls'])), true);

        if (!is_array($image_urls)) {
            wp_send_json_error('Invalid image URLs format');
        }

        foreach ($image_urls as $url) {

            $response = wp_remote_head($url);
            
            if (is_wp_error($response)) {
                $image_url_status[] = [
                    'url' => $url,
                    'status' => 0,
                    'redirected' => false,
                    'error' => $response->get_error_message()
                ];
                continue;
            }
    
            $status_code = wp_remote_retrieve_response_code($response);
            $is_redirected = wp_remote_retrieve_header($response, 'location') ? true : false;

            $image_url_status[] = [
                'url' => $url,
                'status' => $status_code,
                'redirected' => $is_redirected
            ];

        }

        wp_send_json_success($image_url_status);
    }

    function rapidload_fetch_post_types_with_links() {
        $data = [];

        if ( class_exists( 'WooCommerce' ) ) {
            $cart_page_id = wc_get_page_id( 'cart' );
            $checkout_page_id = wc_get_page_id( 'checkout' );
        } else {
            $cart_page_id = $checkout_page_id = 0;
        }

        $post_types = get_post_types( ['public' => true], 'names' );

        foreach ( $post_types as $post_type ) {

            $query = new WP_Query([
                'post_type'      => $post_type,
                'posts_per_page' => 10,
                'orderby'        => 'post_modified',
                'order'          => 'DESC',
                'no_found_rows'  => true
            ]);

            if ( $query->have_posts() ) {
                $posts_data = [];
                $unique_urls = [];

                foreach ( $query->posts as $post ) {

                    $post_id = $post->ID;

                    if ( $post_type === 'page' && ( $post_id == $cart_page_id || $post_id == $checkout_page_id ) ) {
                        continue;
                    }

                    $permalink = get_permalink( $post_id );

                    $parsed_url = wp_parse_url( $permalink );
                    $base_url = isset( $parsed_url['scheme'] ) && isset( $parsed_url['host'] )
                        ? $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path']
                        : $permalink;

                    if ( !in_array( $base_url, $unique_urls ) ) {
                        $unique_urls[] = $base_url;
                        $posts_data[] = [
                            'title'     => get_the_title( $post_id ),
                            'permalink' => $permalink,
                        ];
                    }
                }

                $data[] = [
                    'post_type' => $post_type,
                    'links'     => $posts_data
                ];
            }
        }

        wp_send_json_success($data);
    }

    function rapidload_fetch_post_search_by_title_or_permalink() {

        if ( !isset( $_REQUEST['s'] ) ) {
            wp_send_json_error( 'Search key not found' );
        }

        $search_term = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
        $search_term_lower = strtolower( $search_term );

        if ( strlen( $search_term ) < 3 ) {
            wp_send_json_error( 'Search term must be at least 3 characters long' );
        }

        if ( class_exists( 'WooCommerce' ) ) {
            $cart_page_id = wc_get_page_id( 'cart' );
            $checkout_page_id = wc_get_page_id( 'checkout' );
        } else {
            $cart_page_id = $checkout_page_id = 0;
        }

        $post_type = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ) : 'any';
        $posts_per_page = 10;
        $paged = 1;
        $data = [];

        do {
            $args = [
                'posts_per_page'   => $posts_per_page,
                'post_type'        => $post_type,
                'post_status'      => 'publish',
                'paged'            => $paged,
                'fields'           => 'ids'
            ];

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post_id ) {

                    if ( $post_type === 'page' && ( $post_id == $cart_page_id || $post_id == $checkout_page_id ) ) {
                        continue;
                    }

                    $permalink = get_permalink( $post_id );
                    $permalink_lower = strtolower( $permalink );

                    if ( stripos( $permalink_lower, $search_term_lower ) !== false || stripos( strtolower( get_the_title( $post_id ) ), $search_term_lower ) !== false ) {
                        $data[] = [
                            'id'        => $post_id,
                            'post_type' => get_post_type( $post_id ),
                            'title'     => get_the_title( $post_id ),
                            'permalink' => $permalink,
                        ];
                    }
                }
            }

            $paged++;
        } while ( $query->found_posts > count( $data ) && $query->max_num_pages >= $paged );

        if ( empty( $data ) ) {
            wp_send_json_error( 'No results found' );
        }

        wp_send_json_success( $data );
    }

    public function rapidload_titan_optimizations_data(){

        self::verify_nonce();

        $start_from = 0;
        $limit = 10;
        $s = null;

        if(isset($_REQUEST['start_from'])){
            $start_from = absint($_REQUEST['start_from']);
        }
        if(isset($_REQUEST['limit'])){
            $limit = absint($_REQUEST['limit']);
        }
        if(isset($_REQUEST['s']) && !empty($_REQUEST['s'])){
            $s = sanitize_text_field(wp_unslash($_REQUEST['s']));
        }

        wp_send_json_success(RapidLoad_Job::get_all_optimizations_data_for('desktop', $start_from, $limit, $s));

    }

    public function rapidload_delete_titan_optimizations(){

        if(!isset($_REQUEST['url'])){
            wp_send_json_error('url required');
        }

        $url = $this->transform_url(sanitize_url(wp_unslash($_REQUEST['url'])));

        if($url == $this->transform_url(site_url())){
            wp_send_json_error('cannot delete home page optimizations');
        }

        $job = new RapidLoad_Job(['url' => $url]);

        if(!isset($job->id)){
            wp_send_json_error('job not found');
        }

        $job->delete_all_revisions();
        $job->set_desktop_options(null);
        $job->set_mobile_options(null);
        $job->save();

        wp_send_json_success('successfully deleted all optimizations data');
    }

    public function rapidload_enable_cdn_metering(){

        $options = RapidLoad_Base::fetch_options();

        if(!isset($options['uucss_cdn_zone_id'])){
            wp_send_json_error('cdn zone id not set');
        }

        $api = new RapidLoad_Api();

        $response = $api->post('enable-cdn-metering',[
           'zone_id' =>  $options['uucss_cdn_zone_id']
        ]);

        if(is_wp_error($response)){
            wp_send_json_error($api->extract_error($response));
        }

        wp_send_json_success([
            'success' => true
        ]);

    }

    public function rapidload_enable_image_metering(){

        $api = new RapidLoad_Api();

        $response = $api->post('enable-cdn-metering',[
            'url' =>  site_url()
        ]);

        if(is_wp_error($response)){
            wp_send_json_error($api->extract_error($response));
        }

        wp_send_json_success([
            'success' => true
        ]);

    }

    public function rapidload_switch_test_mode(){

        self::verify_nonce();

        $options = RapidLoad_Base::fetch_options();

        if(!isset($_REQUEST['test_mode']) || empty($_REQUEST['test_mode'])){
            if(isset($options['rapidload_test_mode']) && $options['rapidload_test_mode'] == "1"){
                wp_send_json_success([
                    'status' => true
                ]);
            }else{
                wp_send_json_success([
                    'status' => false
                ]);
            }
        }

        $status = $_REQUEST['test_mode'] == "true" ? "1" : "0";

        $options['rapidload_test_mode'] = $status;

        RapidLoad_Base::update_option('autoptimize_uucss_settings', $options);

        wp_send_json_success([
            'status' => $options['rapidload_test_mode'] == "1"
        ]);

    }

    public function titan_checklist_status(){

        self::verify_nonce();

        if(isset($_REQUEST['status'])){
            RapidLoad_Base::update_option('titan_checklist_status', sanitize_text_field(wp_unslash($_REQUEST['status'])));
            wp_send_json_success(true);
        }

        $updated_status = RapidLoad_Base::get_option('titan_checklist_status', 'pending');

        if(isset($updated_status) && !empty($updated_status)){
            wp_send_json_success($updated_status);
        }

        wp_send_json_error();
    }

    public function titan_checklist_plugins(){

        self::verify_nonce();

        $cnflict_plugins = apply_filters('uucss/third-party/plugins',[]);

        $plugins = get_plugins();

        $conflict_plugin_names = [];

        foreach($cnflict_plugins as $cnflict_plugin){

            if(isset($cnflict_plugin['has_conflict']) && $cnflict_plugin['has_conflict'] && isset($plugins[$cnflict_plugin['path']])){
                $conflict_plugin_names[] = $plugins[$cnflict_plugin['path']]['Name'];
            }
        }

        wp_send_json_success($conflict_plugin_names);

    }

    public function titan_checklist_crawler(){

        self::verify_nonce();

        $api = new RapidLoad_Api();

        $result = $api->post('crawler-check',[
            'url' => site_url()
        ]);

        if($result == "200"){
            update_option('crawler_check_rapidload_success',"1");
            wp_send_json_success(true);
        }

        wp_send_json_error(false);

    }

    public function titan_checklist_cron(){

        self::verify_nonce();

        // Create server array first
        $server_info = array(
            'software' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '',
            'php_version' => PHP_VERSION,
            'cron_status' => 0
        );

        if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
            wp_send_json_error( [
                'cron_message' => 'The DISABLE_WP_CRON constant is set to true. WP-Cron spawning is disabled.',
                'server' => $server_info
            ] );
        }

        if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
            wp_send_json_error( [
                'cron_message' => 'The ALTERNATE_WP_CRON constant is set to true. WP-Cron spawning is not asynchronous.',
                'server' => $server_info
            ] );
        }

        $spawn = $this->get_cron_spawn();

        if ( is_wp_error( $spawn ) ) {
            wp_send_json_error( [
                'cron_message' => sprintf( 'WP-Cron spawn failed with error: %s', $spawn->get_error_message() ),
                'server' => $server_info
            ] );
        }

        $code = wp_remote_retrieve_response_code( $spawn );
        $message = wp_remote_retrieve_response_message( $spawn );
        
        // Update cron status with actual code
        $server_info['cron_status'] = $code;
        
        $response = array(
            'cron_message' => $code === 200 ? 'WP-Cron spawning is working as expected.' : sprintf( 'WP-Cron spawn returned HTTP status code: %1$s %2$s', $code, $message ),
            'server' => $server_info
        );

        wp_send_json_success($response);
    }

    public function rapidload_titan_feedback(){

        if (!isset($_REQUEST['smiley'])) {
            wp_send_json_error(false);
        }

        $version = sanitize_text_field(wp_unslash($_REQUEST['smiley']));
        $reason = isset($_REQUEST['detail']) ? sanitize_textarea_field(wp_unslash($_REQUEST['detail'])) : '';

        $api = new RapidLoad_Api();

        $api->post('feedback', [
           'url' => site_url(),
           'type' => 'titan_feedback',
           'version' => $version,
           'reason' => $reason
        ]);

        wp_send_json_success(true);

    }

    public function purge_rapidload_cdn(){

        self::verify_nonce();

        $options = RapidLoad_Base::fetch_options();

        if(!isset($options['uucss_cdn_zone_id'])){
            wp_send_json_error(false);
        }

        $api = new RapidLoad_Api();

        $api->post('purge-cdn/' . $options['uucss_cdn_zone_id'],[]);

        wp_send_json_success(true);
    }

    public function update_rapidload_settings(){

        self::verify_nonce();

        $options = RapidLoad_Base::fetch_options();

        // update  css options

        if(isset($_REQUEST['uucss_minify'])){

            $options['uucss_minify'] = (sanitize_text_field(wp_unslash($_REQUEST['uucss_minify'])) == 'true' ? "1" : null);

        }

        if(isset($_REQUEST['uucss_minify_excluded_files'])){

            $options['uucss_minify_excluded_files'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_minify_excluded_files']));

        }

        if(isset($_REQUEST['rapidload_aggregate_css'])){

            $options['rapidload_aggregate_css'] = sanitize_text_field(wp_unslash($_REQUEST['rapidload_aggregate_css'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_inline_css'])){

            $options['uucss_inline_css'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_inline_css'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_enable_cpcss'])){

            $options['uucss_enable_cpcss'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_enable_cpcss'])) == 'true' ? "1" : null;

            if(isset($_REQUEST['remove_cpcss_on_user_interaction'])){

                $options['remove_cpcss_on_user_interaction'] = sanitize_text_field(wp_unslash($_REQUEST['remove_cpcss_on_user_interaction'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_additional_css'])){

                $options['uucss_additional_css'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_additional_css']));

            }

            if(isset($_REQUEST['rapidload_cpcss_file_character_length'])){

                $options['rapidload_cpcss_file_character_length'] = intval($_REQUEST['rapidload_cpcss_file_character_length']);

            }

        }

        if(isset($_REQUEST['uucss_excluded_files'])){

            $value = explode("\r\n", sanitize_textarea_field(wp_unslash($_REQUEST['uucss_excluded_files'])));

            $value = array_filter($value, function ($v){
                return !empty($v);
            });

            $value = implode(",",array_values($value));

            $options['uucss_excluded_files'] = $value;

        }

        if(isset($_REQUEST['uucss_enable_uucss'])){

            $options['uucss_enable_uucss'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_enable_uucss'])) == 'true' ? "1" : null;

            if(isset($_REQUEST['uucss_variables'])){

                $options['uucss_variables'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_variables'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_keyframes'])){

                $options['uucss_keyframes'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_keyframes'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_fontface'])){

                $options['uucss_fontface'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_fontface'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_include_inline_css'])){

                $options['uucss_include_inline_css'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_include_inline_css'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_cache_busting_v2'])){

                $options['uucss_cache_busting_v2'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_cache_busting_v2'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_safelist'])){

                $value = explode("\r\n", sanitize_textarea_field(wp_unslash($_REQUEST['uucss_safelist'])));

                $value = array_filter($value, function ($v){
                    return !empty($v);
                });

                $value = array_map(function ($v){
                    return (object)[
                        'type' => 'greedy',
                        'rule' => $v
                    ];
                }, array_values($value));

                $options['uucss_safelist'] = json_encode($value);

            }

            if(isset($_REQUEST['uucss_blocklist'])){

                $value = explode("\r\n", sanitize_textarea_field(wp_unslash($_REQUEST['uucss_blocklist'])));

                $value = array_filter($value, function ($v){
                   return !empty($v);
                });

                $options['uucss_blocklist'] = json_encode(array_values($value));

            }

            if(isset($_REQUEST['whitelist_packs'])){

                $options['whitelist_packs'] = sanitize_textarea_field(wp_unslash($_REQUEST['whitelist_packs']));

            }else{

                $options['whitelist_packs'] = [];

            }


        }

        if(isset($_REQUEST['uucss_enable_rules'])){

            $options['uucss_enable_rules'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_enable_rules'])) == 'true' ? "1" : null;

        }

        // update js options

        if(isset($_REQUEST['uucss_load_js_method'])){

            $options['uucss_load_js_method'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_load_js_method']));

        }

        if(isset($_REQUEST['defer_inline_js'])){

            $options['defer_inline_js'] = sanitize_text_field(wp_unslash($_REQUEST['defer_inline_js'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['minify_js'])){

            $options['minify_js'] = sanitize_text_field(wp_unslash($_REQUEST['minify_js'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['preload_internal_links'])){

            $options['preload_internal_links'] = sanitize_text_field(wp_unslash($_REQUEST['preload_internal_links'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['delay_javascript'])){

            $options['delay_javascript'] = sanitize_text_field(wp_unslash($_REQUEST['delay_javascript'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_excluded_js_files'])){

            $options['uucss_excluded_js_files'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_excluded_js_files']));

        }

        if(isset($_REQUEST['delay_javascript_callback'])){

            $options['delay_javascript_callback'] = sanitize_textarea_field(wp_unslash($_REQUEST['delay_javascript_callback']));

        }

        if(isset($_REQUEST['uucss_excluded_js_files_from_defer'])){

            $options['uucss_excluded_js_files_from_defer'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_excluded_js_files_from_defer']));

        }

        if(isset($_REQUEST['uucss_load_scripts_on_user_interaction'])){

            $options['uucss_load_scripts_on_user_interaction'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_load_scripts_on_user_interaction'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_exclude_files_from_delay_js'])){

            $options['uucss_exclude_files_from_delay_js'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_exclude_files_from_delay_js']));

        }

        if(isset($_REQUEST['uucss_exclude_files_from_minify_js'])){

            $options['uucss_exclude_files_from_minify_js'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_exclude_files_from_minify_js']));

        }

        // update general settings

        if(isset($_REQUEST['uucss_query_string'])){

            $options['uucss_query_string'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_query_string'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['rapidload_minify_html'])){

            $options['rapidload_minify_html'] = sanitize_text_field(wp_unslash($_REQUEST['rapidload_minify_html'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_enable_debug'])){

            $options['uucss_enable_debug'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_enable_debug'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_disable_add_to_queue'])){

            $options['uucss_disable_add_to_queue'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_disable_add_to_queue'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_disable_add_to_re_queue'])){

            $options['uucss_disable_add_to_re_queue'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_disable_add_to_re_queue'])) == 'true' ? "1" : null;

        }

        if(isset($_REQUEST['uucss_jobs_per_queue'])){

            $options['uucss_jobs_per_queue'] = intval($_REQUEST['uucss_jobs_per_queue']);

        }

        if(isset($_REQUEST['uucss_queue_interval'])){

            $options['uucss_queue_interval'] = intval($_REQUEST['uucss_queue_interval']);

        }

        if(isset($_REQUEST['uucss_excluded_links'])){

            $options['uucss_excluded_links'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_excluded_links']));

        }

        if(isset($_REQUEST['uucss_disable_error_tracking'])){

            $options['uucss_disable_error_tracking'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_disable_error_tracking'])) == 'true' ? "1" : null;

        }

        // update image delivery settings

        if(isset($_REQUEST['uucss_enable_image_delivery'])){

            if(isset($_REQUEST['uucss_image_optimize_level'])){

                $options['uucss_image_optimize_level'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_image_optimize_level']));

            }

            if(isset($_REQUEST['uucss_exclude_above_the_fold_image_count'])){

                $options['uucss_exclude_above_the_fold_image_count'] = intval($_REQUEST['uucss_exclude_above_the_fold_image_count']);

            }

            if(isset($_REQUEST['uucss_support_next_gen_formats'])){

                $options['uucss_support_next_gen_formats'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_support_next_gen_formats'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_adaptive_image_delivery'])){

                $options['uucss_adaptive_image_delivery'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_adaptive_image_delivery'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_set_width_and_height'])){

                $options['uucss_set_width_and_height'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_set_width_and_height'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_lazy_load_images'])){

                $options['uucss_lazy_load_images'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_lazy_load_images'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_lazy_load_iframes'])){

                $options['uucss_lazy_load_iframes'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_lazy_load_iframes'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_generate_blurry_place_holder'])){

                $options['uucss_generate_blurry_place_holder'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_generate_blurry_place_holder'])) == 'true' ? "1" : null;

            }

            if(isset($_REQUEST['uucss_exclude_images'])){

                $options['uucss_exclude_images'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_exclude_images']));

            }

            if(isset($_REQUEST['uucss_exclude_images_from_lazy_load'])){

                $options['uucss_exclude_images_from_lazy_load'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_exclude_images_from_lazy_load']));

            }

            if(isset($_REQUEST['uucss_exclude_images_from_modern_images'])){

                $options['uucss_exclude_images_from_modern_images'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_exclude_images_from_modern_images']));

            }

            if(isset($_REQUEST['uucss_preload_lcp_image'])){

                $options['uucss_preload_lcp_image'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_preload_lcp_image'])) == 'true' ? "1" : null;

            }

        }

        // update font settings

        if(isset($_REQUEST['uucss_enable_font_optimization'])){

            if(isset($_REQUEST['uucss_preload_font_urls'])){

                $options['uucss_preload_font_urls'] = sanitize_textarea_field(wp_unslash($_REQUEST['uucss_preload_font_urls']));

            }

            if(isset($_REQUEST['uucss_self_host_google_fonts'])){

                $options['uucss_self_host_google_fonts'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_self_host_google_fonts'])) == 'true' ? "1" : null;

            }
        }

        // update cdn options

        if(isset($_REQUEST['uucss_enable_cdn'])){

            if(isset($_REQUEST['uucss_cdn_url'])){

                $options['uucss_cdn_url'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_cdn_url']));

            }

            if(isset($_REQUEST['uucss_cdn_dns_id'])){

                $options['uucss_cdn_dns_id'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_cdn_dns_id']));

            }

            if(isset($_REQUEST['uucss_cdn_zone_id'])){

                $options['uucss_cdn_zone_id'] = sanitize_text_field(wp_unslash($_REQUEST['uucss_cdn_zone_id']));

            }

        }

        if(isset($_REQUEST['uucss_enable_cache'])){

            $args = [
                'cache_expires' => 0,
                'cache_expiry_time' => 0,
                'mobile_cache' => 0,
                'excluded_post_ids'=> '',
                'excluded_page_paths' => '',
                'excluded_query_strings' => '',
                'excluded_cookies' => ''
            ];

            if(isset($_REQUEST['cache_expires'])){

                $args['cache_expires'] = sanitize_text_field(wp_unslash($_REQUEST['cache_expires'])) == 'true' ? 1 : 0;

            }

            if(isset($_REQUEST['cache_expiry_time'])){

                $args['cache_expiry_time'] = floatval($_REQUEST['cache_expiry_time']);

            }

            if(isset($_REQUEST['mobile_cache'])){

                $args['mobile_cache'] = sanitize_text_field(wp_unslash($_REQUEST['mobile_cache'])) == 'true' ? 1 : 0;

            }

            if(isset($_REQUEST['excluded_post_ids']) && !empty($_REQUEST['excluded_post_ids'])){

                $args['excluded_post_ids'] = sanitize_textarea_field(wp_unslash($_REQUEST['excluded_post_ids']));

            }

            if(isset($_REQUEST['excluded_page_paths']) && !empty($_REQUEST['excluded_page_paths'])){

                $args['excluded_page_paths'] = sanitize_textarea_field(wp_unslash($_REQUEST['excluded_page_paths']));

            }

            if(isset($_REQUEST['excluded_query_strings']) && !empty($_REQUEST['excluded_query_strings'])){

                $args['excluded_query_strings'] = sanitize_textarea_field(wp_unslash($_REQUEST['excluded_query_strings']));

            }

            if(isset($_REQUEST['excluded_cookies']) && !empty($_REQUEST['excluded_cookies'])){

                $args['excluded_cookies'] = sanitize_textarea_field(wp_unslash($_REQUEST['excluded_cookies']));

            }

            RapidLoad_Cache::update_settings($args);

        }

        if(isset($_REQUEST['rapidload_test_mode'])){

            $options['rapidload_test_mode'] = sanitize_text_field(wp_unslash($_REQUEST['rapidload_test_mode'])) == 'true' ? 1 : 0;
        }

        RapidLoad_Base::update_option('autoptimize_uucss_settings',$options);

        wp_send_json_success(RapidLoad_Base::get()->modules()->active_modules());
    }

    public function list_module(){
        self::verify_nonce();
        wp_send_json_success(RapidLoad_Base::get()->modules()->active_modules());
    }

    public function activate_module(){

        RapidLoad_Base::get()->modules()->activate_module();

    }

    public function attach_rule(){

        self::verify_nonce();

        $type = isset($_REQUEST['type']) ? sanitize_text_field(wp_unslash($_REQUEST['type'])) : false;
        $url = isset($_REQUEST['url']) ? sanitize_url(wp_unslash($_REQUEST['url'])) : false;
        $rule_id = isset($_REQUEST['rule_id']) ? sanitize_text_field(wp_unslash($_REQUEST['rule_id'])) : false;

        if(!$type || !$url){
            wp_send_json_error('Required field missing');
        }

        if($type == 'detach'){

            $path = new RapidLoad_Job([
                'url' => $url
            ]);
            $path->attach_rule();
            $path->save();
            wp_send_json_success('Successfully detached from rule');
        }

        if(!$type || $type == 'attach' && !$rule_id){
            wp_send_json_error('Required field missing');
        }

        if($type == 'attach'){

            $rule = RapidLoad_Job::find_or_fail($rule_id);

            if(!$rule){
                wp_send_json_error('Rule not found');
            }

            $path = new RapidLoad_Job([
                'url' => $url
            ]);

            if(!self::is_url_glob_matched($url, $rule->regex)){
                wp_send_json_success('Pattern not matched');
            }

            $path->attach_rule($rule->id, $rule->rule);
            $path->save();
            wp_send_json_success('Successfully attached to rule');
        }

    }

    public function verify_api_key() {

        if(is_ajax()){
            self::verify_nonce();
        }

        if ( ! isset( $_POST['api_key'] ) ) {
            wp_send_json_error();

            return;
        }

        $uucss_api         = new RapidLoad_Api();
        $uucss_api->apiKey = sanitize_text_field(wp_unslash($_POST['api_key']));

        $results = $uucss_api->get( 'verify' );

        if ( isset( $results->data ) ) {
            wp_send_json_success( true );
        }

        wp_send_json_error();

    }

    public function clear_rapidload_logs(){

        self::verify_nonce();

        $file_system = new RapidLoad_FileSystem();

        if(!$file_system->exists(self::get_wp_content_dir() . '/uploads/rapidload/')){
            wp_send_json_success(true);
        }

        $file_system->delete_folder(self::get_wp_content_dir() . '/uploads/rapidload/');
        wp_send_json_success(true);
    }

    public function rapidload_logs(){

        self::verify_nonce();

        $file_system = new RapidLoad_FileSystem();

        if(!$file_system->exists(UUCSS_LOG_DIR . 'debug.log')){
            wp_send_json_success([]);
        }

        $data = $file_system->get_contents(UUCSS_LOG_DIR . 'debug.log');

        if(empty($data)){
            wp_send_json_success([]);
        }

        $data = '[' . $data . ']';

        wp_send_json_success(json_decode($data));
    }

    public function clear_page_cache(){

        self::verify_nonce();

        $url = isset($_REQUEST['url']) ? sanitize_url(wp_unslash($_REQUEST['url'])) : false;
        $rule = isset($_REQUEST['rule']) ? sanitize_text_field(wp_unslash($_REQUEST['rule'])) : false;
        $regex = isset($_REQUEST['regex']) ? sanitize_text_field(wp_unslash($_REQUEST['regex'])) : false;

        $status = isset($_REQUEST['status']) ? sanitize_text_field(wp_unslash($_REQUEST['status'])) : false;

        if($url){

            RapidLoad_DB::resetHits($url);
            do_action( 'uucss/cached', [
                'url' => $url
            ] );
        }

        $links = false;

        if($rule && $regex){

            $rule_object = new RapidLoad_Job([
                'rule' => $rule,
                'regex' => $regex
            ]);

            if(isset($rule_object->id)){

                RapidLoad_DB::resetRuleHits($rule_object->id);
                $links = $rule_object->get_urls();
                array_push($links, $rule_object->url);

            }
        }

        if($status == 'warnings'){

            RapidLoad_DB::resetWarningHits();
            $links = RapidLoad_DB::getUrlsWithWarnings();

        }

        if($links && !empty($links)){

            foreach ($links as $link){
                do_action( 'uucss/cached', [
                    'url' => $link
                ] );
            }
        }

        wp_send_json_success('page cache cleared');
    }

    public function uucss_connect(){

        self::verify_nonce();

        if ( ! isset( $_REQUEST['license_key'] ) || empty( $_REQUEST['license_key'] ) ) {
            wp_send_json_error( 'License Key required' );
        }

        $license_key = sanitize_text_field(wp_unslash($_REQUEST['license_key']));

        $uucss_api         = new RapidLoad_Api();
        $uucss_api->apiKey = $license_key;
        $results           = $uucss_api->post( 'connect', [ 'url' => $this->transform_url(get_site_url()), 'type' => 'wordpress' ] );

        if ( $uucss_api->is_error( $results ) ) {
            if(isset($results->errors) && isset($results->errors[0])){
                wp_send_json_error($results->errors[0]->detail);
            }else{
                wp_send_json_error('License Key verification fail');
            }
        }

        $options['uucss_api_key_verified'] = 1;
        $options['uucss_api_key']          = $license_key;

        RapidLoad_Base::update_option( 'autoptimize_uucss_settings', $options );

        wp_send_json_success([
            'success' => true,
            'message' => 'License Key verification success',
            'activation_nonce' => wp_create_nonce( 'uucss_activation' ),
        ]);
    }


    public function validate_domain() {

        if ( get_current_screen() && (get_current_screen()->base != 'settings_page_uucss_legacy' && get_current_screen()->base != 'toplevel_page_rapidload')) {
            return;
        }

        $options   = RapidLoad_Base::get_option( 'autoptimize_uucss_settings' );

        if(!isset( $options['uucss_api_key_verified'] ) || $options['uucss_api_key_verified'] != '1'){
            return;
        }

        $uucss_api = new RapidLoad_Api();

        if ( ! isset( $options['uucss_api_key'] ) ) {
            return;
        }

        $results = $uucss_api->get( 'verify', [ 'url' => site_url(), 'token' => $options['uucss_api_key'] ] );

        if($uucss_api->is_error($results)){
            $options['valid_domain'] = false;
            RapidLoad_Base::update_option('autoptimize_uucss_settings', $options);
            return;
        }

        if(!isset($options['valid_domain']) || !$options['valid_domain']){
            $options['valid_domain'] = true;
            RapidLoad_Base::update_option('autoptimize_uucss_settings', $options);
        }
    }

    public function ajax_deactivate() {

        self::verify_nonce();

        $options = RapidLoad_Base::get_option( 'autoptimize_uucss_settings' );

        $cache_key = 'pand-' . md5( 'first-uucss-job' );
        RapidLoad_Base::delete_option( $cache_key );

        do_action('rapidload/vanish');

        $api = new RapidLoad_Api();

        // remove domain from authorized list
        $api->post( 'deactivate', [
            'url' => site_url()
        ] );

        unset( $options['uucss_api_key_verified'] );
        unset( $options['uucss_api_key'] );
        unset( $options['whitelist_packs'] );

        RapidLoad_Base::update_option( 'autoptimize_uucss_settings', $options );

        wp_send_json_success( true );
    }

    function rapidload_rule_types($rules){

        $custom_posts = get_post_types(
            array(
                'public'   => true,
                '_builtin' => false,
            ),
            'names',
            'and'
        );

        $taxonomies = get_taxonomies([
            'public' => true
        ]);

        $rules[] = [
            'name' => 'front_page',
            'rule' => 'is_front_page',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_front_page();
            },
        ];

        $rules[] = [
            'name' => '404',
            'rule' => 'is_404',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_404();
            },
        ];

        $rules[] = [
            'name' => 'archive',
            'rule' => 'is_archive',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_archive();
            },
        ];

        foreach ($custom_posts as $key => $value){
            if($value == 'page' || $value == 'post' || $value == 'product'){
                continue;
            }
            if(( $key = array_search($value, array_column($rules, 'name')) ) === false){

                $rules[] = [
                    'name' => $value,
                    'rule' => 'is_' . $value,
                    'category' => 'Custom Post Types',
                    'priority' => 5,
                    'callback' => function() use($value){
                        return get_post_type( get_the_ID() ) == $value;
                    }
                ];
            }
        }

        foreach ($taxonomies as $key => $value){
            if(( $key = array_search($value, array_column($rules, 'name')) ) === false){

                $rules[] = [
                    'name' => $value,
                    'rule' => 'is_' . $value,
                    'category' => 'Taxonomies',
                    'priority' => 5,
                    'callback' => function() use($value){
                        return is_tax($value);
                    },
                ];
            }
        }

        $rules[] = [
            'name' => 'author',
            'rule' => 'is_author',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_author();
            },
        ];

        $rules[] = [
            'name' => 'home',
            'rule' => 'is_home',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_home();
            },
        ];

        $rules[] = [
            'name' => 'page',
            'rule' => 'is_page',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_page();
            },
        ];

        $rules[] = [
            'name' => 'post',
            'rule' => 'is_post',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_singular();
            },
        ];

        $rules[] = [
            'name' => 'search',
            'rule' => 'is_search',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_search();
            },
        ];

        $rules[] = [
            'name' => 'attachment',
            'rule' => 'is_attachment',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_attachment();
            },
        ];

        $rules[] = [
            'name' => 'single',
            'rule' => 'is_single',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_single();
            },
        ];

        $rules[] = [
            'name' => 'sticky',
            'rule' => 'is_sticky',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_sticky();
            },
        ];

        $rules[] = [
            'name' => 'paged',
            'rule' => 'is_paged',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_paged();
            },
        ];

        $rules[] = [
            'name' => 'path',
            'rule' => 'is_path',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return true;
            },
        ];

        return $rules;
    }


    public function clear_cache_on_option_update( $option, $old_value, $value ) {

        if ( $option == 'autoptimize_uucss_settings' ) {

            $needs_to_cleared = false;

            $diffs = [];
            $diffs_invert = [];

            if ( $old_value && $value ) {
                $diffs        = array_diff_key( $old_value, $value );
                $diffs_invert = array_diff_key( $value, $old_value );
            }

            if ( isset( $diffs_invert['valid_domain'] ) ) {
                unset( $diffs_invert['valid_domain'] );
            }
            if ( isset( $diffs['valid_domain'] ) ) {
                unset( $diffs['valid_domain'] );
            }

            $diffs = array_merge( $diffs, $diffs_invert );

            // if these settings are changed cache will be cleared
            if ( isset( $diffs['uucss_keyframes'] ) ||
                isset( $diffs['uucss_fontface'] ) ||
                isset( $diffs['uucss_analyze_javascript'] ) ||
                isset( $diffs['uucss_safelist'] ) ||
                isset( $diffs['whitelist_packs'] ) ||
                isset( $diffs['uucss_blocklist'] ) ||
                isset( $diffs['uucss_variables'] ) ) {
                $needs_to_cleared = true;
            }

            foreach ( [ 'whitelist_packs', 'uucss_safelist', 'uucss_blocklist' ] as $compare_value ) {
                if ( isset( $value[ $compare_value ] ) && isset( $old_value[ $compare_value ] ) && $old_value[ $compare_value ] !== $value[ $compare_value ] ) {
                    $needs_to_cleared = true;
                    break;
                }
            }

            if(isset( $diffs['uucss_enable_rules'] )){
                RapidLoad_DB::detach_all_rules();
            }

            if ( $needs_to_cleared ) {

                /*$this->uucss->clear_cache( null, [
                    'soft' => true
                ] );*/
            }

            RapidLoad_Base::fetch_options(false);
        }

    }

    public function rapidload_image_usage(){

        self::verify_nonce();

        $api = new RapidLoad_Api();

        $data = $api->get( 'image-usage', [
            'url' => $this->transform_url(get_site_url()),
        ]);

        if ( is_wp_error( $data ) ) {
            wp_send_json_error('Something went wrong');
        }

        wp_send_json_success((array)$data->data);
    }

    public function rapidload_cdn_usage(){

        self::verify_nonce();

        $api = new RapidLoad_Api();

        $options = RapidLoad_Base::fetch_options();

        $data = $api->get( 'cdn-usage', [
            'zone_id' => isset($options['uucss_cdn_zone_id']) ? $options['uucss_cdn_zone_id'] : '',
        ]);

        if ( is_wp_error( $data ) ) {
            wp_send_json_error('Something went wrong');
        }

        wp_send_json_success((array)$data->data);
    }

    public function uucss_license() {

        self::verify_nonce();

        $api = new RapidLoad_Api();

        $disconnect = isset($_REQUEST['disconnect']);

        if($disconnect){

            $options = RapidLoad_Base::fetch_options();
            unset($options['uucss_api_key_verified']);
            unset($options['uucss_api_key']);
            unset($options['valid_domain']);
            RapidLoad_Base::update_option('autoptimize_uucss_settings', $options);
            RapidLoad_Base::fetch_options(false);

            $cache_key = 'pand-' . md5( 'first-uucss-job' );
            RapidLoad_Base::delete_option( $cache_key );

            do_action('rapidload/vanish');

            wp_send_json_success(true);
        }

        $data = $api->get( 'license', [
            'url' => $this->transform_url(get_site_url()),
            'version' => UUCSS_VERSION,
            'db_version' => RapidLoad_DB::$db_version,
            'db_version_exist' => RapidLoad_DB::$current_version
        ] );

        if ( ! is_wp_error( $data ) ) {

            if ( isset( $data->errors ) ) {
                wp_send_json_error( $data->errors[0]->detail );
            }

            if ( gettype( $data ) === 'string' ) {
                wp_send_json_error( $data );
            }

            do_action( 'uucss/license-verified' );

            update_option('rapidload_license_data', serialize($data->data));

            if(isset($data->data)){
                wp_send_json_success( $data->data );
            }

            wp_send_json_error( 'unknown error occurred' );
        }

        wp_send_json_error( 'unknown error occurred' );
    }

    public function frontend_logs(){

        self::verify_nonce();

        $args = [];

        $args['type'] = isset($_REQUEST['type']) && !empty($_REQUEST['type']) ? sanitize_text_field(wp_unslash($_REQUEST['type'])) : 'frontend';
        $args['log'] = isset($_REQUEST['log']) && !empty($_REQUEST['log']) ? sanitize_text_field(wp_unslash($_REQUEST['log'])) : '';
        $args['url'] = isset($_REQUEST['url']) && !empty($_REQUEST['url']) ? sanitize_url(wp_unslash($_REQUEST['url'])) : '';

        self::log($args);

        wp_send_json_success(true);
    }

    public function inject_cloudflare_settings($data){

        $options = RapidLoad_Base::fetch_options();

        if(isset($options['cf_bot_toggle_mode']) && $options['cf_bot_toggle_mode'] == "1"){

            if(isset($options['cf_email']) && isset($options['cf_token']) && isset($options['cf_zone_id'])){
                $data['cloudflare'] = [
                    'auth_email' => $options['cf_email'],
                    'zone_id' => $options['cf_zone_id'],
                    'token' => $options['cf_token'],
                ];
            }

        }

        return $data;

    }

    public function update_cloudflare_settings( $option, $old_value, $value ){

        if ( $option != 'autoptimize_uucss_settings' ) {
            return;
        }

        if(isset($value['cf_token']) && isset($value['cf_email']) && isset($value['cf_zone_id'])){

            wp_remote_request('https://api.cloudflare.com/client/v4/zones/'. $value['cf_zone_id'] .'/settings/development_mode',[
                'method'     => 'PATCH',
                'headers' => [
                    'X-Auth-Email' => $value['cf_email'],
                    'Authorization' => 'Bearer ' . $value['cf_token'],
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode((object)[
                    'value' => isset($value['cf_is_dev_mode']) && $value['cf_is_dev_mode'] == "1" ? 'on' : 'off'
                ])
            ]);

        }

    }

    public function render_cloudflare_settings(){

        if(apply_filters('rapidload/cloudflare/bot-fight-mode/disable', true)){
            return;
        }

        $options = RapidLoad_Base::fetch_options();

        ?>

        <li>
            <h2>
                Cloudflare Settings
                <span class="uucss-toggle-section rotate">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </span>
            </h2>
            <div class="content" style="display: none; ">
                <table class="cf-table">
                    <tr>
                        <td>
                            <label for="cloudflare-dev-mode">Enable Bot toggle mode</label>
                        </td>
                        <td>
                            <input type="checkbox" name="autoptimize_uucss_settings[cf_bot_toggle_mode]" id="cf_bot_toggle_mode" value="1" <?php if(isset($options['cf_bot_toggle_mode']) && $options['cf_bot_toggle_mode'] == "1") : echo 'checked'; endif; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 150px">
                            <label for="cloudflare-api-key">Api Token</label>
                        </td>
                        <td>
                            <input type="text" name='autoptimize_uucss_settings[cf_token]' id="cf_token" style="width: 450px" value="<?php if(isset($options['cf_token'])) : echo esc_attr($options['cf_token']); endif; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="cloudflare-account-email" >Account Email</label>
                        </td>
                        <td>
                            <input type="text" name="autoptimize_uucss_settings[cf_email]" id="cf_email" style="width: 350px" value="<?php if(isset($options['cf_email'])) : echo esc_attr($options['cf_email']); endif; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="cloudflare-zone-id">Zone ID</label>
                        </td>
                        <td>
                            <input type="text" name="autoptimize_uucss_settings[cf_zone_id]" id="cf_zone_id" style="width: 350px" value="<?php if(isset($options['cf_zone_id'])) : echo esc_attr($options['cf_zone_id']); endif; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="cloudflare-dev-mode">Development Mode</label>
                        </td>
                        <td>
                            <input type="checkbox" name="autoptimize_uucss_settings[cf_is_dev_mode]" id="cf_is_dev_mode" value="1" <?php if(isset($options['cf_is_dev_mode']) && $options['cf_is_dev_mode'] == "1") : echo 'checked'; endif; ?>>
                        </td>
                    </tr>
                </table>
            </div>
        </li>

        <?php
    }

    public function get_robots_text(){

        self::verify_nonce();

        $robotsUrl = trailingslashit(get_site_url()) . "robots.txt";

        $robot = new stdClass();
        $robot->disAllow = [];
        $robot->allow = [];

        try {

            $fh = wp_remote_get($robotsUrl);

            if(!is_wp_error($fh) && isset($fh['body'])){

                foreach(preg_split("/((\r?\n)|(\r\n?))/", $fh['body']) as $line){

                    if (preg_match("/user-agent.*/i", $line) ){
                        $robot->userAgent = trim(explode(':', $line, 2)[1]);
                    }
                    else if (preg_match("/disallow.*/i", $line)){
                        array_push($robot->disAllow, trim(explode(':', $line, 2)[1]));
                    }
                    else if (preg_match("/^allow.*/i", $line)){
                        array_push($robot->allow, trim(explode(':', $line, 2)[1]));
                    }
                    else if(preg_match("/sitemap.*/i", $line)){
                        $robot->sitemap = trim(explode(':', $line, 2)[1]);
                    }

                }

            }

            wp_send_json_success($robot);

        }catch (Exception $ex){

            wp_send_json_error();
        }

    }

    function add_sitemap_to_jobs($url = false){

        if(!$url){

            $url = apply_filters('uucss/sitemap/default', stripslashes(get_site_url(get_current_blog_id())) . '/sitemap_index.xml');
        }

        $site_map = new RapidLoad_Sitemap();
        $urls = $site_map->process_site_map($url);

        if(isset($urls) && !empty($urls)){

            foreach ($urls as $value){

                $_url = $this->transform_url($value);

                if($this->is_url_allowed($_url)){

                    $job = new RapidLoad_Job([
                       'url' => $_url
                    ]);
                    $job->save(true);
                }

            }
        }
    }

}