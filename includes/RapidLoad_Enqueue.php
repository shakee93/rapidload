<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Enqueue {

    use RapidLoad_Utils;

    private $options;
    private $url;
    private $rule;
    private $group;
    public static $job;
    public static $test_mode;

    public static $frontend_debug = false;

    public function __construct()
    {
        if(!self::rapidload_should_start()){
            return;
        }

        $this->options = RapidLoad_Base::get_merged_options();

        if(isset($_COOKIE['rapidload_debug']) && $_COOKIE['rapidload_debug'] === "1" || apply_filters('rapidload/enable/frontend_rapidload_debug', false)){
            self::$frontend_debug = true;
        }

        add_filter('uucss/enqueue/content', [$this, 'rapidload_the_content'], 10, 2);

        add_action('wp_enqueue_scripts', function (){

            if(wp_doing_ajax()){
                return;
            }

            $this->url = $this->rapidload_util_get_current_url();

            $this->url = $this->rapidload_util_transform_url($this->url);

            self::$test_mode = isset($this->options['rapidload_test_mode']) && $this->options['rapidload_test_mode'] === "1";

            if(self::$test_mode){
                self::debug_log("test mode enabled");
            }

            if($this->rapidload_enabled($this->url)){

                if(RapidLoad_Base::get()->rules_enabled()){

                    $this->rule = $this->rapidload_get_current_rule();

                }

                $this->rapidload_handle_job($this->url, []);

            }

        });
    }

    public static function rapidload_should_start(){
        if(wp_doing_cron()){
            return false;
        }
        if(wp_doing_ajax()){
            return false;
        }
        if (isset($_SERVER['REQUEST_URI']) && strpos(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), 'favicon.ico') !== false) {
            return false;
        }
        if (isset($_SERVER['REQUEST_URI']) && strpos(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), 'robots.txt') !== false) {
            return false;
        }
        return true;
    }

    public function rapidload_replace_css($job)
    {
        $buffer = apply_filters('uucss/enqueue/buffer','rapidload_buffer');
        add_filter( $buffer, function ( $html ) use($job){
            return apply_filters('uucss/enqueue/content', $html, $job);
        }, 10 );
    }

    public function rapidload_the_content($html, $job){

        if ( ! class_exists( \simplehtmldom\HtmlDocument::class ) ) {
            return $html;
        }

        $dom = new \simplehtmldom\HtmlDocument(
            null,
            false,
            false,
            \simplehtmldom\DEFAULT_TARGET_CHARSET,
            false
        );

        $dom->load(
            $html,
            false,
            false,
            \simplehtmldom\DEFAULT_TARGET_CHARSET,
            false
        );

        if ( $dom ) {

            $inject = (object) [
                "parsed_html"           => false,
                "found_sheets"          => false,
                "found_css_files"       => [],
                "found_css_cache_files" => [],
                "ao_optimized_css" => [],
                "injected_css_files"    => [],
                "successfully_injected"    => true,
            ];

            $inject->parsed_html = true;

            $strategy = $this->rapidload_util_is_mobile() ? 'mobile' : 'desktop';

            $state = apply_filters('uucss/enqueue/content/update',[
                'dom' => $dom,
                'inject' => $inject,
                'options' => $this->options,
                'job' => $job,
                'strategy' => $strategy,
            ]) ;

            if(isset($state['dom'])){
                $dom = $state['dom'];
            }

            if(isset($state['inject'])){
                $inject = $state['inject'];
            }

            if(isset($state['options'])){
                $this->options = $state['options'];
            }

            if(self::$frontend_debug){
                header( 'uucss:' . 'v' . UUCSS_VERSION . ' [' . count( $inject->found_css_files ) . count( $inject->found_css_cache_files ) . count( $inject->injected_css_files ) . ']' );
            }

            $pattern = '/(?:href|src)=["\']?([^"\'>\s]+)/';

            $_html = "";

            if(isset($dom)){

                if(gettype($dom) === "string"){
                    $_html = $dom;
                }else{
                    $_html = $dom->__toString();
                }

            }

            $_html = preg_match_all('/(?:href|src)=["\']?([^"\'>\s]+)/', $_html, $matches);

            $domains = [];

            foreach ($matches[1] as $url) {
                if(filter_var($url, FILTER_VALIDATE_URL)){
                    if(isset($url) && !empty($url)){
                        $_parsed_url = wp_parse_url($url);
                        if(isset($_parsed_url['host'])){
                            array_push($domains, $_parsed_url['host']);
                        }
                    }
                }
            }

            if(apply_filters('rapidload/enqueue/dns-prefetch/enabled', true)){
                $domains = array_unique($domains);

                if(gettype($dom) !== "string"){
                    foreach ($domains as $domain){
                        if(!$this->rapidload_util_str_contains(site_url(), $domain)){
                            $head = $dom->find('head', 0);
                            $preconnect = '<link href="//' . $domain . '" rel="dns-prefetch" crossorigin>';
                            $first_child = $head->first_child();
                            $first_child->__set('outertext', $preconnect . $first_child->outertext);
                        }
                    }
                }
            }

            if (gettype($dom) === "string") {
                $html = $dom;
            } else {
                $html = $dom->__toString();
            }
        }

        header('X-Optimized-By: RapidLoad v' . UUCSS_VERSION);

        return apply_filters('uucss/enqueue/content/after', $html);
    }

    public function rapidload_is_url_allowed($url = null, $args = null)
    {

        if(!$this->rapidload_util_is_valid_url($url)){
            return false;
        }

        // remove .css .js files from being analyzed
        if ( preg_match( '/cache\/autoptimize/', $url ) ) {
            return false;
        }

        if ( preg_match( '/cache\/rapidload/', $url ) ) {
            return false;
        }

        global $post;
        $_post = $post;

        if ( !$_post && isset( $args['post_id'] ) ) {
            $_post = get_post( $args['post_id'] );
        }

        if ( $_post ) {
            $page_options = RapidLoad_Base::get_page_options( $_post->ID );
            if ( isset( $page_options['exclude'] ) && $page_options['exclude'] === "on" ) {
                return false;
            }

        }

        if ( isset( $this->options['uucss_excluded_links'] ) && ! empty( $this->options['uucss_excluded_links'] ) ) {
            
            if(is_string($this->options['uucss_excluded_links'])){
                $exploded = explode( ',', $this->options['uucss_excluded_links'] );
            }else{
                $exploded = $this->options['uucss_excluded_links'];
            }
            
            foreach ( $exploded as $pattern ) {

                if ( filter_var( $pattern, FILTER_VALIDATE_URL ) ) {

                    $pattern = wp_parse_url( $pattern );

                    $path = $pattern['path'];
                    $query = isset($pattern['query']) ? '?' . $pattern['query'] : '';

                    $pattern = $path . $query;

                }

                if(self::rapidload_util_str_contains( $pattern, '*' ) && self::is_path_glob_matched(urldecode($url), $pattern)){
                    $this->log( 'skipped glob pattern match : ' . $url );
                    return false;
                }else if ( self::rapidload_util_str_contains( urldecode($url), $pattern ) ) {
                    $this->log( 'skipped string contains : ' . $url );
                    return false;
                }

            }
        }

        $url_parts = wp_parse_url( $url );

        if(isset($url_parts['query']) && $this->rapidload_util_str_contains($url_parts['query'], 'customize_changeset_uuid')){
            $this->log( 'skipped query contains: ' . $url );
            return false;
        }

        if(!apply_filters('uucss/url/exclude', $url)){
            $this->log( 'skipped url exclude : ' . $url );
            return false;
        }

        return true;
    }

    public function rapidload_enabled($url) {

        if(!RapidLoad_Base::is_api_key_verified()){
            return false;
        }

        if ( $this->rapidload_util_is_doing_api_fetch() ) {
            return false;
        }

        // fix for uucss fallback css files being purged as url's
        if ( $this->rapidload_util_is_uucss_file() ) {
            return false;
        }

        if ( ! $this->rapidload_is_url_allowed($url) ) {
            return false;
        }

        if ( is_admin() ) {
            return false;
        }

        if ( wp_doing_ajax() ) {
            return false;
        }

        if ( is_404() ) {
            return false;
        }

        if ( is_preview() ) {
            return false;
        }

        if ( $this->is_cli() ) {
            return false;
        }

        if ( is_search() ) {
            return false;
        }

        if ( defined( 'DOING_CRON' ) ) {
            return false;
        }

        if( RapidLoad_DB::$current_version < 1.3){
            return false;
        }

        if(isset($this->options['rapidload_test_mode']) && $this->options['rapidload_test_mode'] === "1" && !isset($_REQUEST['rapidload_preview'])){
            return false;
        }

        return apply_filters('uucss/enabled', true);

    }

    public function rapidload_handle_job($url, $args){

        global $rapidload;

        if(!isset($args['post_id'])){
            $args['post_id'] = url_to_postid($url);
        }

        if(!isset(RapidLoad_Enqueue::$job)){
            RapidLoad_Enqueue::$job = new RapidLoad_Job([
                'url' => $url
            ]);
        }

        if(!isset(RapidLoad_Enqueue::$job->rule_id) && $this->rule && RapidLoad_Enqueue::$job->rule_note !== "detached") {

            $rule = new RapidLoad_Job([
                'url' => $url,
                'rule' => $this->rule->rule,
                'regex' => $this->rule->regex,
            ]);

            RapidLoad_Enqueue::$job->rule_id = $rule->id;
            RapidLoad_Enqueue::$job->status = 'rule-based';
            RapidLoad_Enqueue::$job->parent = $rule;
            RapidLoad_Enqueue::$job->save();

        }

        $front_end_enabled = [];
        $front_end_enabled['add_queue_enabled'] = !isset( $this->options['uucss_disable_add_to_queue'] ) ||
            isset( $this->options['uucss_disable_add_to_queue'] ) && $this->options['uucss_disable_add_to_queue'] !== "1";


        if(!isset(RapidLoad_Enqueue::$job->id) && $front_end_enabled['add_queue_enabled']){

            RapidLoad_Enqueue::$job->save();

        }

        do_action('rapidload/job/handle', RapidLoad_Enqueue::$job, $args);

        $front_end_enabled['job_id_set'] = isset(RapidLoad_Enqueue::$job->id);
        $front_end_enabled['enabled'] = $this->enabled_frontend();

        if($front_end_enabled['job_id_set'] && $front_end_enabled['enabled']){
            $this->replace_css(RapidLoad_Enqueue::$job);
            self::debug_log('modify html content');
        }

    }

    function rapidload_get_current_rule(){

        $rules = RapidLoad_Base::get()->get_pre_defined_rules();
        $user_defined_rules = RapidLoad_DB::get_rule_names();

        $related_rule = false;

        foreach ($user_defined_rules as $rule){

            $key = array_search($rule, array_column($rules, 'rule'));

            if(is_numeric($key) && isset($rules[$key]) && isset($rules[$key]['callback']) && is_callable($rules[$key]['callback']) && $rules[$key]['callback']()){

                $related_rule = RapidLoad_Base::get()->get_applicable_rule($this->url, $rules[$key]);

                if($related_rule){
                    break;
                }

            }

        }

        return $related_rule;
    }

    function rapidload_get_current_group(){
        $current_page_type = $this->rapidload_get_current_page_type();
        $current_page_content_type = $this->rapidload_get_current_content_type();
        $current_page_id = get_queried_object_id();
        if($current_page_id === "0"){
            $current_page_id = null;
        }

        return[
            untrailingslashit($current_page_type . "/" . $current_page_content_type . "/" . $current_page_id),
            $current_page_type . "/" . $current_page_content_type . "/all",
            $current_page_type . "/all",
            'all',
        ];
    }

    function rapidload_get_current_page_type() {
        if (is_singular()) {
            if (class_exists('WooCommerce') && is_woocommerce()) {
                return 'woocommerce';
            }
            return 'single';
        } elseif (is_archive() || is_home() || is_front_page() || is_category() || is_tag() || is_tax()) {
            if (class_exists('WooCommerce') && is_woocommerce()) {
                return 'woocommerce';
            }
            return 'archive';
        }
        return 'general';
    }

    function rapidload_get_current_content_type() {
        if (is_front_page()) {
            return 'front_page';
        } elseif (is_page()) {
            if (function_exists('is_account_page') && is_account_page()) {
                return 'account';
            }
            return 'page';
        } elseif (is_single()) {
            if (function_exists('is_product') && is_product()) {
                return 'product';
            }
            $post_type = get_post_type();
            if ($post_type) {
                return $post_type;
            } else {
                return 'post';
            }
        } elseif (is_category()) {
            /*$category_name = single_cat_title('', false);
            if ($category_name) {
                return preg_replace('/\s+/', '_', strtolower($category_name));
            }*/
            return 'category';
        } elseif (is_tag()) {
            /*$tag_name = single_tag_title('', false);
            if ($tag_name) {
                return preg_replace('/\s+/', '_', strtolower($tag_name));
            }*/
            return 'tag';
        } elseif (is_tax()) {
            if (function_exists('is_product_category') && is_product_category()) {
                return 'product_category';
            }elseif (function_exists('is_product_tag') && is_product_tag()) {
                return 'product_tag';
            }
            /*$tax_name = single_term_title('', false);
            if ($tax_name) {
                return preg_replace('/\s+/', '_', strtolower($tax_name));
            }*/
            return 'taxonomy';
        }elseif (is_post_type_archive()) {
            if (function_exists('is_shop') && is_shop()) {
                return 'shop';
            }
            $post_type = get_post_type();
            if ($post_type) {
                return $post_type;
            }
            return 'archive';
        }elseif (is_archive()) {
            if(is_author()){
                return 'author';
            }else if(is_date()){
                return 'date';
            }
            return 'archive';
        }elseif (is_home()){
            return 'posts';
        }else{
            return 'general';
        }
    }

    function rapidload_enabled_frontend() {

        if(isset($_REQUEST['rapidload_preview'])){
            return true;
        }

        if ( is_user_logged_in() ) {
            return apply_filters('uucss/frontend/enabled/logged-in', false);
        }

        if ( is_admin() ) {
            return false;
        }

        return apply_filters('uucss/frontend/enabled', true);
    }

}