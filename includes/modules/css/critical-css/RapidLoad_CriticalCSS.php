<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_CriticalCSS')){
    return;
}

class RapidLoad_CriticalCSS
{
    use RapidLoad_Utils;

    public $base;
    public $file_system;
    public $options = [];

    public $async = true;

    public static $base_dir;

    public $job_data = null;

    public static $cpcss_other_plugins;

    public function __construct()
    {
        $this->options = RapidLoad_Base::rapidload_get_merged_options();

        $this->file_system = new RapidLoad_FileSystem();

        add_action('wp_ajax_cpcss_purge_url', [$this, 'rapidload_cpcss_purge_url']);
        add_action('wp_ajax_nopriv_cpcss_purge_url', [$this, 'rapidload_cpcss_purge_url']);

        self::$cpcss_other_plugins = apply_filters('cpcss/other-plugins', []);

        if( ! $this->rapidload_initFileSystem() ){
            return;
        }

        if(!isset($this->options['uucss_enable_css']) || !isset($this->options['uucss_enable_cpcss']) || $this->options['uucss_enable_css'] !== "1" || $this->options['uucss_enable_cpcss'] !== "1" || !empty(self::$cpcss_other_plugins) || !RapidLoad_Base::rapidload_is_api_key_verified()){
            return;
        }

        new RapidLoad_CriticalCSS_Queue();

        add_action('uucss/options/css', [$this, 'rapidload_render_options']);

        add_action('cpcss_async_queue', [$this, 'rapidload_init_async_store'], 10, 2);

        if ((!isset($this->options['enable_uucss_on_cpcss']) || isset($this->options['enable_uucss_on_cpcss']) && $this->options['enable_uucss_on_cpcss'] !== "1" ) && !defined('RAPIDLOAD_CPCSS_ENABLED')) {
            define('RAPIDLOAD_CPCSS_ENABLED', true);
        }

        add_action('rapidload/vanish', [ $this, 'rapidload_vanish' ]);

        add_action('rapidload/vanish/css', [ $this, 'rapidload_vanish' ]);

        add_action('rapidload/job/purge', [$this, 'rapidload_cache_cpcss'], 10, 2);

        add_action('rapidload/job/handle', [$this, 'rapidload_cache_cpcss'], 10, 2);

        add_action('rapidload/job/handle', [$this, 'rapidload_enqueue_cpcss'], 20, 2);

        add_filter('uucss/link', [$this, 'rapidload_update_link']);

        add_action('rapidload/job/updated', [$this, 'rapidload_handle_job_updated'], 10 , 2);

        add_action('rapidload/cdn/validated', [$this, 'rapidload_update_cdn_url_in_cached_files']);

        if(is_admin()){

            $this->rapidload_cache_trigger_hooks();

        }

        add_action('rapidload/admin-bar-actions', [$this, 'rapidload_add_admin_clear_action']);

        add_action('rapidload/cpcss/job/handle', [$this, 'rapidload_initiate_cpcss_job'], 10, 3);
    }

    public function rapidload_add_admin_clear_action($wp_admin_bar){
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

    public function rapidload_initiate_cpcss_job($job, $first_arg, $second_arg = null){

        if(!isset($job)){
            return;
        }

        $job_data = new RapidLoad_Job_Data($job, 'cpcss');
        if(!isset($job_data->id)){
            $job_data->rapidload_job_data_save();
        }

        do_action('cpcss_async_queue', $job_data, $first_arg);
        if(isset($second_arg)){
            do_action('cpcss_async_queue', $job_data, $second_arg);
        }
    }

    public function rapidload_update_cdn_url_in_cached_files($args) {
        RapidLoad_CDN::rapidload_update_cdn_url_in_cached_files(self::$base_dir, $args);
    }

    public function rapidload_render_options($args){
        $options = $args;
        include_once 'parts/options.html.php';

    }

    public function rapidload_handle_job_updated($job, $new){

        if($new){

            $job_data = new RapidLoad_Job_Data($job, 'cpcss');

            if(!isset($job_data->id)){

                $job_data->rapidload_job_data_save();

            }
        }
    }

    public function rapidload_cache_trigger_hooks() {
        add_action( 'save_post', [ $this, 'rapidload_cache_on_actions' ], 110, 3 );
        add_action( 'untrash_post', [ $this, 'rapidload_cache_on_actions' ], 10, 1 );
        add_action( 'wp_trash_post', [ $this, 'rapidload_clear_on_actions' ], 10, 1 );
    }

    public function rapidload_vanish() {

        RapidLoad_CriticalCSS_DB::rapidload_clear_data();

        if ( $this->file_system->rapidload_file_exists( self::$base_dir ) ){
            $this->file_system->rapidload_file_delete( self::$base_dir, true );
        }

    }

    public function rapidload_refresh( $url, $args = [] ) {

        $job = null;

        if(isset($url)){

            $job = new RapidLoad_Job([
               'url' => $url
            ]);

        }

        $this->rapidload_clear_cache( $job );
        $this->rapidload_cache_cpcss( $job, $args );
    }

    public function rapidload_clear_on_actions($post_id)
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

                $this->rapidload_clear_cache($job);

            }
        }
    }

    public function rapidload_cache_on_actions($post_id, $post = null, $update = null)
    {
        if(!$post_id){
            return;
        }

        $post = get_post($post_id);

        if($post->post_status === "publish") {

            $this->rapidload_clear_on_actions( $post->ID );

            $job = new RapidLoad_Job([
                'url' => get_permalink( $post )
            ]);

            if(isset($job->id) || !RapidLoad_Base::rapidload_get()->rules_enabled()){

                $this->rapidload_cache_cpcss($job);

            }

        }
    }

    function rapidload_clear_cache($job = null, $args = []){

        if($job){

            $job_data = new RapidLoad_Job_Data($job, 'cpcss');

            if(isset($job_data->id) && (!isset($job_data->job->rule_id) && $job_data->job->rule === "is_url" || $job_data->job->rule !== "is_url")){

                $this->rapidload_clear_files($job_data);
                self::rapidload_util_log([
                    'log' =>  'requeue-> cpcss clear cache by id manually',
                    'url' => $job_data->job->url,
                ]);
                $job_data->rapidload_job_data_requeue();
                $job_data->rapidload_job_data_save();

            }

        }else{

            RapidLoad_CriticalCSS_DB::rapidload_clear_data(isset($args['soft']));
            $this->rapidload_clear_files();

        }

    }

    function rapidload_clear_files($job_data = null){

    }

    function rapidload_cpcss_purge_url()
    {
        self::rapidload_util_verify_nonce();

        if (isset($_REQUEST['url']) && !empty($_REQUEST['url'])) {

            $url = sanitize_url(wp_unslash($_REQUEST['url']));

            if(!$this->is_url_allowed($url)){
                wp_send_json_error('url not allowed');
            }

            $job = new RapidLoad_Job([
                'url' => $this->rapidload_util_transform_url($url)
            ]);

            if (!isset($job->id)) {
                $job->rapidload_job_save();
            }

            $this->rapidload_cache_cpcss($job, ['immediate' => true]);

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
                    RapidLoad_CriticalCSS_DB::rapidload_requeue_where_status('success');
                    break;
                }
                case 'failed':
                {
                    RapidLoad_CriticalCSS_DB::rapidload_requeue_where_status('failed');
                    break;
                }
                case 'processing':
                {
                    RapidLoad_CriticalCSS_DB::rapidload_requeue_where_status('processing');
                    break;
                }
                default:
                {
                    RapidLoad_CriticalCSS_DB::rapidload_requeue_where_status('');
                    break;
                }
            }
        }

        if ( isset( $_REQUEST['clear'] ) && boolval($_REQUEST['clear'] === 'true') ) {

            $this->rapidload_clear_cache();

        }

        $this->rapidload_cleanCacheFiles();

        wp_send_json_success('Successfully purged');
    }

    function rapidload_update_link($link){

        if(isset($link['url'])){

            $url = isset($link['base']) ? $link['base'] : $link['url'];

            $job = new RapidLoad_Job([
                'url' => $url,
            ]);

            if(isset($job->id)){

                $job_data = new RapidLoad_Job_Data($job, 'cpcss');

                if(isset($job_data->id)){

                    $link['cpcss'] = (array) $job_data;
                    if($job->rule !== 'is_url'){
                        $link['rule_status'] = $job_data->status;
                        $link['rule_hits'] = $job_data->hits;
                        $link['applied_links'] = count($job->rapidload_job_get_urls());
                    }

                }

            }

        }

        return $link;
    }

    function rapidload_cache_cpcss($job, $args = []){

        if(!$job || !isset($job->id)){
            return false;
        }

        if(isset( $this->options['uucss_disable_add_to_queue'] ) && $this->options['uucss_disable_add_to_queue'] === "1" && !wp_doing_ajax()){
            return false;
        }

        if(!$this->rapidload_util_is_url_allowed($job->url, $args)){
            return false;
        }

        $this->job_data = new RapidLoad_Job_Data($job, 'cpcss');

        if(!isset($this->job_data->id)){
            $this->job_data->rapidload_job_data_save();
        }

        if($this->job_data->status === 'failed' && $this->job_data->attempts >= 2 && !isset($args['immediate'])){
            return false;
        }

        if(!in_array($this->job_data->status, ['success', 'waiting', 'processing','queued']) || isset( $args['immediate']) || isset( $args['requeue'])){
            self::log([
                'log' =>  'requeue-> cpcss requeue manually',
                'url' => $this->job_data->job->url,
            ]);
            $this->job_data->rapidload_job_data_requeue(isset( $args['immediate']) || isset( $args['requeue']) ? 1 : -1);
            $this->job_data->rapidload_job_data_save();
        }

        $this->async = apply_filters('uucss/purge/async',true);

        if (! $this->async ) {

            $this->rapidload_init_async_store($this->job_data, $args);

        }else if(isset( $args['immediate'] )){

            $this->rapidload_schedule_cron('cpcss_async_queue', [
                'job_data' => $this->job_data,
                'args'     => $args
            ]);

        }

        return true;
    }

    function rapidload_enqueue_cpcss($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_cpcss'] )){
            return false;
        }

        if(!$this->job_data){
            $this->job_data = new RapidLoad_Job_Data($job, 'cpcss');
        }

        new RapidLoad_CriticalCSS_Enqueue($this->job_data);

    }

    public function rapidload_initFileSystem() {

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'cpcss';

        if ( ! $this->file_system ) {
            return false;
        }

        if ( ! $this->rapidload_init_base_dir() ) {
            return false;
        }

        return true;
    }

    public function rapidload_init_base_dir() {

        self::$base_dir = self::rapidload_util_get_wp_content_dir() . $this->base;

        if ( $this->file_system->rapidload_file_exists( self::$base_dir ) ) {
            return true;
        }

        // make dir if not exists
        //$created = $this->file_system->mkdir( self::$base_dir );.
        $created = RapidLoad_Cache_Store::mkdir_p( self::$base_dir );

        if (!$created || ! $this->file_system->rapidload_file_is_writable( self::$base_dir ) || ! $this->file_system->rapidload_file_is_readable( self::$base_dir ) ) {
            return false;
        }

        return true;
    }

    public function rapidload_init_async_store($job_data, $args)
    {
        $store = new RapidLoad_CriticalCSS_Store($job_data, $args);
        $store->rapidload_purge_css();
    }

    public function rapidload_cleanCacheFiles(){

        $data = RapidLoad_CriticalCSS_DB::rapidload_get_success_data();

        $used_files = [];

        foreach ($data as $value){
            if(!empty($value->data)){
                $files = RapidLoad_Job_Data::rapidload_job_data_transform_cpcss_data_to_array($value->data);
                foreach ($files as $file){
                    $file_data = self::rapidload_extract_file_data($file);
                    for ($i = 1; $i <= $file_data['file_count']; $i++) {
                        $file_name = ($i === 1) ? $file_data['file_name'] : str_replace(".css","-" . $i . ".css", $file_data['file_name']);
                        array_push($used_files,$file_name);
                    }
                }
            }
        }

        if($this->file_system->rapidload_file_exists(RapidLoad_CriticalCSS::$base_dir)){
            if ($handle = opendir(RapidLoad_CriticalCSS::$base_dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ('.' === $file) continue;
                    if ('..' === $file) continue;

                    if(!in_array($file, $used_files) && $this->file_system->rapidload_file_exists(RapidLoad_CriticalCSS::$base_dir . '/' . $file)){
                        $this->file_system->rapidload_file_delete(RapidLoad_CriticalCSS::$base_dir . '/' . $file);
                    }
                }
                closedir($handle);
            }
        }
    }

    public static function rapidload_extract_file_data($fileName){
        $pattern = '/(.*)\[(\d+)\]\.css$/';
        if (preg_match($pattern, $fileName, $matches)) {
            return [
                'file_name' => $matches[1] . '.css',
                'file_count' => (int)$matches[2]
            ];
        }
        return [
            'file_name' => $fileName,
            'file_count' => 1
        ];
    }

    public static function rapidload_breakCSSIntoParts($cssContent, $maxLength = 60000) {
        $parser = new \Sabberworm\CSS\Parser($cssContent);
        $cssDocument = $parser->parse();
        $cssParts = [];
        $currentPart = '';
        $currentLength = 0;

        if(!function_exists('addToCurrentRapidLoadCpssPart')){
            function addToCurrentRapidLoadCpssPart(&$currentPart, &$currentLength, &$cssParts, $blockContent, $maxLength) {
                if (($currentLength + strlen($blockContent)) > $maxLength) {
                    $cssParts[] = $currentPart;
                    $currentPart = '';
                    $currentLength = 0;
                }
                $currentPart .= $blockContent;
                $currentLength += strlen($blockContent);
            }
        }

        foreach ($cssDocument->getContents() as $content) {
            $blockContent = $content->render(Sabberworm\CSS\OutputFormat::createCompact());
            addToCurrentRapidLoadCpssPart($currentPart, $currentLength, $cssParts, $blockContent, $maxLength);
        }
        if (!empty($currentPart)) {
            $cssParts[] = $currentPart;
        }
        return $cssParts;
    }
}