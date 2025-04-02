<?php

defined( 'ABSPATH' ) or die();

class UnusedCSS
{
    use RapidLoad_Utils;

    public $base;
    public $file_system;
    public $options = [];

    public $async = true;

    public static $base_dir;

    public $job_data = null;

    public function __construct()
    {
        $this->options = RapidLoad_Base::get_merged_options();

        $this->file_system = new RapidLoad_FileSystem();

        if( ! $this->initFileSystem() ){
            return;
        }

        if(!isset($this->options['uucss_enable_css']) || !isset($this->options['uucss_enable_uucss']) || $this->options['uucss_enable_css'] != "1" || $this->options['uucss_enable_uucss'] != "1" || !RapidLoad_Base::is_api_key_verified()){
            return;
        }

        if(defined('RAPIDLOAD_CPCSS_ENABLED')){
            return;
        }

        if (!defined('RAPIDLOAD_UUCSS_ENABLED')) {
            define('RAPIDLOAD_UUCSS_ENABLED', true);
        }

        new UnusedCSS_Queue();

        add_action('rapidload/job/purge', [$this, 'cache_uucss'], 10, 2);

        add_action('uucss_async_queue', [$this, 'init_async_store'], 10, 2);

        if(apply_filters('uucss/enable/notfound_fallback', true)){
            add_action( 'template_redirect', [$this, 'uucss_notfound_fallback'] );
        }

        add_action('rapidload/vanish', [ $this, 'vanish' ]);

        add_action('rapidload/vanish/css', [ $this, 'vanish' ]);

        add_action('rapidload/job/handle', [$this, 'cache_uucss'], 10, 2);

        add_action('rapidload/job/handle', [$this, 'enqueue_uucss'], 20, 2);

        add_filter('uucss/link', [$this, 'update_link']);

        add_action('rapidload/job/updated', [$this, 'handle_job_updated'], 10 , 2);

        add_filter('uucss/enqueue/cache-file-url', function ($uucss_file){
            return $this->get_cached_file($uucss_file, apply_filters('uucss/enqueue/cache-file-url/cdn', null));
        },10,1);

        if(is_admin()){

            $this->cache_trigger_hooks();

            add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'] );
            add_action( 'save_post', [$this, 'save_meta_box_options'] , 10, 2);
        }

        add_action('rapidload/admin-bar-actions', [$this, 'add_admin_clear_action']);

        add_action('rapidload/cdn/validated', [$this, 'update_cdn_url_in_cached_files']);

        add_action('rapidload/uucss/job/handle', [$this, 'initiate_uucss_job'], 10, 2);
    }

    public function initiate_uucss_job($job, $args){

        if(!isset($job)){
            return;
        }

        $job_data = new RapidLoad_Job_Data($job, 'uucss');
        if(!isset($job_data->id)){
            $job_data->save();
        }

        do_action('uucss_async_queue', $job_data, $args);
    }

    public function update_cdn_url_in_cached_files($args) {
        RapidLoad_CDN::update_cdn_url_in_cached_files(self::$base_dir, $args);
    }

    public function add_admin_clear_action($wp_admin_bar){

        $wp_admin_bar->add_node( array(
            'id'    => 'rapidload-clear-css-cache',
            'title' => '<span class="ab-label">' . __( 'Clear CSS Optimizations', 'unusedcss' ) . '</span>',
            //'href'  => admin_url( 'admin.php?page=rapidload&action=rapidload_purge_all' ),
            'href'   => wp_nonce_url( add_query_arg( array(
                '_action' => 'rapidload_purge_all',
                'job_type' => 'css',
                'clear' => true,
            ) ), 'uucss_nonce', 'nonce' ),
            'meta'  => array( 'class' => 'rapidload-clear-all', 'title' => 'RapidLoad will clear cached css files' ),
            'parent' => 'rapidload'
        ));
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'uucss-options',
            __( 'RapidLoad Options', 'unusedcss' ),
            [$this, 'meta_box'],
            get_post_types(),
            'side'
        );
    }

    public function meta_box( $post ) {

        $options = RapidLoad_Base::get_page_options($post->ID);

        include('parts/admin-post.html.php');
    }

    public function save_meta_box_options($post_id, $post)
    {
        if ( !isset( $_POST['uucss_nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uucss_nonce'] ) ), 'uucss_option_save' ) ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $this->update_meta($post_id);

    }

    public function update_meta($post_id)
    {
        foreach (RapidLoad_Base::$page_options as $option) {

            if ( ! isset( $_POST[ 'uucss_' . $option ] ) ) {
                delete_post_meta( $post_id, '_uucss_' . $option );
                continue;
            }

            $value = sanitize_text_field( $_POST[ 'uucss_' . $option ] );

            update_post_meta( $post_id, '_uucss_' . $option, $value );
        }
    }

    public function uucss_notfound_fallback(){

        $original_request = strtok( $_SERVER['REQUEST_URI'], '?' );
        $original_path = self::get_wp_content_dir() . apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR)  . 'uucss' . "/" . basename($original_request);

        $options = RapidLoad_Base::fetch_options(false);

        if ( strpos( $original_request, wp_basename( self::get_wp_content_dir() ) . apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR)  . 'uucss' ) !== false
            && !file_exists($original_path)
            //&& isset($options['uucss_disable_add_to_re_queue']) && $options['uucss_disable_add_to_re_queue'] == "1"
        ) {

            global $wp_query;
            $wp_query->is_404 = false;

            $fallback_target = UnusedCSS_DB::get_original_file_name($original_request);

            if ( isset($fallback_target) ) {

                wp_redirect( $fallback_target, 302 );
            } else {

                status_header( 410 );
            }
        }

    }

    function update_link($link){

        if(isset($link['url'])){

            $url = isset($link['base']) ? $link['base'] : $link['url'];

            $job = new RapidLoad_Job([
                'url' => $url,
            ]);

            if(isset($job->id)){

                $job_data = new RapidLoad_Job_Data($job, 'uucss');

                if(isset($job_data->id)){

                    if($job->rule != 'is_url'){
                        //$link['rule_status'] = $job_data->status;
                       // $link['rule_hits'] = $job_data->hits;
                        //$link['applied_links'] = count($job->get_urls());
                    }

                    if(!isset($link['status'])){
                        $link['status'] = $job_data->status;
                    }
                    $link['success_count'] = $job_data->hits;
                    $link['files'] = $job_data->get_files();
                    $link['meta']['id'] = $job_data->job->id;
                    $link['meta']['warnings'] = $job_data->get_warnings();
                    $link['meta']['stats'] = isset($job_data->stats) ? unserialize($job_data->stats) : null;
                    $link['meta']['error'] = isset($job_data->error) ? unserialize($job_data->error) : null;
                    $link['meta']['status'] = isset( $job_data->status ) ? $job_data->status : null;
                    $link['time'] = isset( $job_data->created_at ) ? strtotime( $job_data->created_at ) : null;
                    $link['attempts'] = isset( $job_data->attempts ) ? $job_data->attempts : null;
                    $link['rule'] = $job_data->job->rule;

                    if(boolval($link['meta']['warnings']) == false){
                        $link['meta']['warnings'] = [];
                    }
                }

            }

        }

        return $link;
    }

    public function get_cached_file( $file_url, $cdn = null ) {

        if ( ! $cdn || empty( $cdn ) ) {
            $cdn = self::get_wp_content_url();
        } else {

            $url_parts = parse_url( self::get_wp_content_url() );

            $cdn = rtrim( $cdn, '/' ) . (isset($url_parts['path']) ? rtrim( $url_parts['path'], '/' ) : '/wp-content');

        }

        return implode( '/', [
            $cdn,
            trim($this->base, "/"),
            $file_url
        ] );
    }

    public function handle_job_updated($job, $new){

        if($new){

            $job_data = new RapidLoad_Job_Data($job, 'uucss');

            if(!isset($job_data->id)){

                $job_data->save();

            }
        }
    }

    public function cache_trigger_hooks() {
        add_action( 'save_post', [ $this, 'cache_on_actions' ], 110, 3 );
        add_action( 'untrash_post', [ $this, 'cache_on_actions' ], 10, 1 );
        add_action( 'wp_trash_post', [ $this, 'clear_on_actions' ], 10, 1 );
        add_action('wp_ajax_uucss_purge_url', [$this, 'uucss_purge_url']);
    }

    function enqueue_uucss($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_uucss'] )){
            return false;
        }

        if(!$this->job_data){
            $this->job_data = new RapidLoad_Job_Data($job, 'uucss');
        }

        new UnusedCSS_Enqueue($this->job_data);

    }

    public function cache_on_actions($post_id, $post = null, $update = null)
    {
        if(!$post_id){
            return;
        }

        $post = get_post($post_id);

        if($post->post_status == "publish") {

            $this->clear_on_actions( $post->ID );

            $job = new RapidLoad_Job([
                'url' => get_permalink( $post )
            ]);

            if(isset($job->id) || !RapidLoad_Base::get()->rules_enabled()){

                $this->cache_uucss($job);

            }

        }
    }

    function cache_uucss($job, $args = []){

        if(!$job || !isset($job->id)){
            return false;
        }

        if(isset( $this->options['uucss_disable_add_to_queue'] ) && $this->options['uucss_disable_add_to_queue'] == "1" && !wp_doing_ajax()){
            return false;
        }

        if(!$this->is_url_allowed($job->url, $args)){
            return false;
        }

        $this->job_data = new RapidLoad_Job_Data($job, 'uucss');

        if(!isset($this->job_data->id)){
            $this->job_data->save();
        }

        if($this->job_data->status == 'failed' && $this->job_data->attempts >= 2 && (!isset($args['immediate']) || !isset( $args['requeue']))){
            return false;
        }

        if(!in_array($this->job_data->status, ['success', 'waiting', 'processing','queued']) || isset( $args['immediate']) || isset( $args['requeue'])){
            self::log([
                'log' =>  'requeue-> uucss requeue manually',
                'url' => $this->job_data->job->url,
            ]);
            $this->job_data->requeue(isset( $args['immediate']) || isset( $args['requeue']) ? 1 : -1);
            if(isset( $args['immediate'])){
                $this->job_data->status = 'processing';
            }
            $this->job_data->save();
        }

        if(!isset($args['options'])){
            $args['options'] = $this->api_options(isset($args['post_id']) ? $args['post_id'] : null);
        }

        $this->async = apply_filters('uucss/purge/async',true);

        if (! $this->async ) {

            $this->init_async_store($this->job_data, $args);

        }else if(isset( $args['immediate'] )){

            $spawned = $this->schedule_cron('uucss_async_queue', [
                'job_data' => $this->job_data,
                'args'     => $args
            ]);

            if(!$spawned){
                $this->init_async_store($this->job_data, $args);
            }

        }

        return true;
    }

    function uucss_purge_url()
    {

        self::verify_nonce();

        if (isset($_REQUEST['url']) && !empty($_REQUEST['url'])) {

            $url = sanitize_url($_REQUEST['url']);

            if(!$this->is_url_allowed($url)){
                wp_send_json_error('url not allowed');
            }

            $job = new RapidLoad_Job([
                'url' => $this->transform_url($url)
            ]);

            if (!isset($job->id)) {
                $job->save();
            }

            $this->cache_uucss($job, ['immediate' => true]);

        }

        if (isset($_REQUEST['post_type'])){

            switch ($_REQUEST['post_type']) {

                case 'url':
                case 'post':
                case 'page':
                case 'site_map':
                {
                    break;
                }
                case 'warnings':
                {
                    UnusedCSS_DB::requeue_where(" WHERE status ='success' AND warnings IS NOT NULL ");
                    break;
                }
                case 'failed':
                {
                    UnusedCSS_DB::requeue_where(" WHERE status ='failed' ");
                    break;
                }
                case 'processing':
                {
                    UnusedCSS_DB::requeue_where(" WHERE status ='processing' ");
                    break;
                }
                default:
                {
                    UnusedCSS_DB::requeue_where();
                    break;
                }
            }
        }

        if ( isset( $_REQUEST['clear'] ) && boolval($_REQUEST['clear'] == 'true') ) {

            $this->clear_cache();

        }

        $this->cleanCacheFiles();

        wp_send_json_success('Successfully purged');
    }

    public function cleanCacheFiles(){

    }

    public function clear_on_actions($post_id)
    {
        if(!$post_id){
            return;
        }

        $link = get_permalink($post_id);

        if($link){

            $job = new RapidLoad_Job([
                'url' => $link
            ]);

            if(isset($job->id)){

                $this->clear_cache($job);

            }
        }
    }

    function clear_cache($job = null, $args = []){

        if($job){

            $job_data = new RapidLoad_Job_Data($job, 'uucss');

            if(isset($job_data->id) && (!isset($job_data->job->rule_id) && $job_data->job->rule == "is_url" || $job_data->job->rule != "is_url")){

                $this->clear_files($job_data);
                self::log([
                    'log' =>  'requeue-> clear cache by job id manually',
                    'url' => $job_data->job->url,
                ]);
                $job_data->requeue();
                $job_data->save();

            }

        }else{

            UnusedCSS_DB::clear_data(isset($args['soft']));
            $this->clear_files();

        }

    }

    function clear_files($job_data = null){

        if($job_data){

            if(!empty($job_data->data)){

                $files = isset($job_data->data) && !empty($job_data->data) ? unserialize($job_data->data) : [];

                $used_files = UnusedCSS_DB::get_used_files_exclude($job_data->id);

                foreach ($files as $file){

                    $key = array_search($file['uucss'], $used_files);

                    if ( !isset($key) || empty($key)){

                        $this->file_system->delete( self::$base_dir . '/' . $used_files[$key] );

                    }
                }
            }

        }else{

            $this->file_system->delete( self::$base_dir );

        }

    }

    public function initFileSystem() {

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'uucss';

        if ( ! $this->file_system ) {
            return false;
        }

        if ( ! $this->init_base_dir() ) {
            return false;
        }

        return true;
    }

    public function init_base_dir() {

        self::$base_dir = self::get_wp_content_dir() . $this->base;

        if ( $this->file_system->exists( self::$base_dir ) ) {
            return true;
        }

        // make dir if not exists
        $created = RapidLoad_Cache_Store::mkdir_p( self::$base_dir );

        if (!$created || ! $this->file_system->is_writable( self::$base_dir ) || ! $this->file_system->is_readable( self::$base_dir ) ) {
            return false;
        }

        return true;
    }

    public function init_async_store($job_data, $args)
    {
        $store = new UnusedCSS_Store($job_data, $args);
        $store->purge_css();
    }

    public function vanish() {

        UnusedCSS_DB::clear_data();

        if ( $this->file_system->exists( self::$base_dir ) ){
            $this->file_system->delete( self::$base_dir, true );
        }

    }

    public function api_options( $post_id = null ) {

        $whitelist_packs = [ 'wp' ];

        if ( isset( $this->options['whitelist_packs'] ) ) {

            foreach ( $this->options['whitelist_packs'] as $whitelist_pack ) {

                // 9:wordpress
                $pack              = $name = explode( ':', $whitelist_pack );
                $whitelist_packs[] = $pack[0];

            }

        }

        $post_options = $post_id ? RapidLoad_Base::get_page_options( $post_id ) : [];

        $safelist = isset( $this->options['uucss_safelist'] ) ? json_decode( $this->options['uucss_safelist'] ) : [];

        $blocklist = isset( $this->options['uucss_blocklist'] ) ? json_decode( $this->options['uucss_blocklist'] ) : [];

        // merge post and global safelists
        if ( ! empty( $post_options['safelist'] ) ) {
            $safelist = array_merge( $safelist, json_decode( $post_options['safelist'] ) );
        }

        if ( ! empty( $post_options['blocklist'] ) ) {
            $blocklist = array_merge( $blocklist, json_decode( $post_options['blocklist'] ) );
        }

        $cacheBusting = false;

        if(isset($this->options['uucss_cache_busting_v2'])){

            $cacheBusting = apply_filters('uucss/cache/bust',[]);

        }

        return apply_filters('uucss/api/options', [
            "keyframes"         => isset( $this->options['uucss_keyframes'] ),
            "fontFace"          => isset( $this->options['uucss_fontface'] ),
            "variables"         => isset( $this->options['uucss_variables'] ),
            "minify"            => isset( $this->options['uucss_minify'] ),
            "analyzeJavascript" => isset( $this->options['uucss_analyze_javascript'] ),
            "inlineCss"          => isset( $this->options['uucss_include_inline_css'] ),
            "whitelistPacks"    => $whitelist_packs,
            "safelist"          => $safelist,
            "blocklist"          => $blocklist,
            "cacheBusting"          => $cacheBusting,
        ]);
    }
}