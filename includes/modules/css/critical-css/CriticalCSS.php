<?php

defined( 'ABSPATH' ) or die();

class CriticalCSS
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
        $this->options = RapidLoad_Base::get_merged_options();

        $this->file_system = new RapidLoad_FileSystem();

        add_action('wp_ajax_cpcss_purge_url', [$this, 'cpcss_purge_url']);
        add_action('wp_ajax_nopriv_cpcss_purge_url', [$this, 'cpcss_purge_url']);

        self::$cpcss_other_plugins = apply_filters('cpcss/other-plugins', []);

        if( ! $this->initFileSystem() ){
            return;
        }

        if(!isset($this->options['uucss_enable_css']) || !isset($this->options['uucss_enable_cpcss']) || $this->options['uucss_enable_css'] != "1" || $this->options['uucss_enable_cpcss'] != "1" || !empty(self::$cpcss_other_plugins) || !RapidLoad_Base::is_api_key_verified()){
            return;
        }

        new CriticalCSS_Queue();

        add_action('uucss/options/css', [$this, 'render_options']);

        add_action('cpcss_async_queue', [$this, 'init_async_store'], 10, 2);

        if ((!isset($this->options['enable_uucss_on_cpcss']) || isset($this->options['enable_uucss_on_cpcss']) && $this->options['enable_uucss_on_cpcss'] != "1" ) && !defined('RAPIDLOAD_CPCSS_ENABLED')) {
            define('RAPIDLOAD_CPCSS_ENABLED', true);
        }

        add_action('rapidload/vanish', [ $this, 'vanish' ]);

        add_action('rapidload/vanish/css', [ $this, 'vanish' ]);

        add_action('rapidload/job/purge', [$this, 'cache_cpcss'], 10, 2);

        add_action('rapidload/job/handle', [$this, 'cache_cpcss'], 10, 2);

        add_action('rapidload/job/handle', [$this, 'enqueue_cpcss'], 20, 2);

        add_filter('uucss/link', [$this, 'update_link']);

        add_action('rapidload/job/updated', [$this, 'handle_job_updated'], 10 , 2);

        add_action('rapidload/cdn/validated', [$this, 'update_cdn_url_in_cached_files']);

        if(is_admin()){

            $this->cache_trigger_hooks();

        }

        add_action('rapidload/admin-bar-actions', [$this, 'add_admin_clear_action']);

        add_action('rapidload/cpcss/job/handle', [$this, 'initiate_cpcss_job'], 10, 3);
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

    public function initiate_cpcss_job($job, $first_arg, $second_arg = null){

        if(!isset($job)){
            return;
        }

        $job_data = new RapidLoad_Job_Data($job, 'cpcss');
        if(!isset($job_data->id)){
            $job_data->save();
        }

        do_action('cpcss_async_queue', $job_data, $first_arg);
        if(isset($second_arg)){
            do_action('cpcss_async_queue', $job_data, $second_arg);
        }
    }

    public function update_cdn_url_in_cached_files($args) {
        RapidLoad_CDN::update_cdn_url_in_cached_files(self::$base_dir, $args);
    }

    public function render_options($args){
        $options = $args;
        include_once 'parts/options.html.php';

    }

    public function handle_job_updated($job, $new){

        if($new){

            $job_data = new RapidLoad_Job_Data($job, 'cpcss');

            if(!isset($job_data->id)){

                $job_data->save();

            }
        }
    }

    public function cache_trigger_hooks() {
        add_action( 'save_post', [ $this, 'cache_on_actions' ], 110, 3 );
        add_action( 'untrash_post', [ $this, 'cache_on_actions' ], 10, 1 );
        add_action( 'wp_trash_post', [ $this, 'clear_on_actions' ], 10, 1 );
    }

    public function vanish() {

        CriticalCSS_DB::clear_data();

        if ( $this->file_system->exists( self::$base_dir ) ){
            $this->file_system->delete( self::$base_dir, true );
        }

    }

    public function refresh( $url, $args = [] ) {

        $job = null;

        if(isset($url)){

            $job = new RapidLoad_Job([
               'url' => $url
            ]);

        }

        $this->clear_cache( $job );
        $this->cache_cpcss( $job, $args );
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

                $this->cache_cpcss($job);

            }

        }
    }

    function clear_cache($job = null, $args = []){

        if($job){

            $job_data = new RapidLoad_Job_Data($job, 'cpcss');

            if(isset($job_data->id) && (!isset($job_data->job->rule_id) && $job_data->job->rule == "is_url" || $job_data->job->rule != "is_url")){

                $this->clear_files($job_data);
                self::log([
                    'log' =>  'requeue-> cpcss clear cache by id manually',
                    'url' => $job_data->job->url,
                ]);
                $job_data->requeue();
                $job_data->save();

            }

        }else{

            CriticalCSS_DB::clear_data(isset($args['soft']));
            $this->clear_files();

        }

    }

    function clear_files($job_data = null){

        if($job_data){

            if(!empty($job_data->data)){

                $count = CriticalCSS_DB::data_used_elsewhere($job_data->id, $job_data->data);

                if($count == 0){

                    if($this->file_system->exists(self::$base_dir . '/' .  $job_data->data)){
                        $this->file_system->delete( self::$base_dir . '/' .  $job_data->data);
                    }
                    if($this->file_system->exists(self::$base_dir . '/' .  str_replace(".css","-mobile.css", $job_data->data))){
                        $this->file_system->delete( self::$base_dir . '/' .  str_replace(".css","-mobile.css", $job_data->data));
                    }

                }
            }

        }else{

            $this->file_system->delete( self::$base_dir );

        }

    }

    function cpcss_purge_url()
    {
        self::verify_nonce();

        if (isset($_REQUEST['url']) && !empty($_REQUEST['url'])) {

            $url = $_REQUEST['url'];

            if(!$this->is_url_allowed($url)){
                wp_send_json_error('url not allowed');
            }

            $job = new RapidLoad_Job([
                'url' => $this->transform_url($url)
            ]);

            if (!isset($job->id)) {
                $job->save();
            }

            $this->cache_cpcss($job, ['immediate' => true]);

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
                    CriticalCSS_DB::requeue_where(" WHERE status ='success' AND warnings IS NOT NULL ");
                    break;
                }
                case 'failed':
                {
                    CriticalCSS_DB::requeue_where(" WHERE status ='failed' ");
                    break;
                }
                case 'processing':
                {
                    CriticalCSS_DB::requeue_where(" WHERE status ='processing' ");
                    break;
                }
                default:
                {
                    CriticalCSS_DB::requeue_where();
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

    function update_link($link){

        if(isset($link['url'])){

            $url = isset($link['base']) ? $link['base'] : $link['url'];

            $job = new RapidLoad_Job([
                'url' => $url,
            ]);

            if(isset($job->id)){

                $job_data = new RapidLoad_Job_Data($job, 'cpcss');

                if(isset($job_data->id)){

                    $link['cpcss'] = (array) $job_data;
                    if($job->rule != 'is_url'){
                        $link['rule_status'] = $job_data->status;
                        $link['rule_hits'] = $job_data->hits;
                        $link['applied_links'] = count($job->get_urls());
                    }

                }

            }

        }

        return $link;
    }

    function cache_cpcss($job, $args = []){

        if(!$job || !isset($job->id)){
            return false;
        }

        if(isset( $this->options['uucss_disable_add_to_queue'] ) && $this->options['uucss_disable_add_to_queue'] == "1" && !wp_doing_ajax()){
            return false;
        }

        if(!$this->is_url_allowed($job->url, $args)){
            return false;
        }

        $this->job_data = new RapidLoad_Job_Data($job, 'cpcss');

        if(!isset($this->job_data->id)){
            $this->job_data->save();
        }

        if($this->job_data->status == 'failed' && $this->job_data->attempts >= 2 && !isset($args['immediate'])){
            return false;
        }

        if(!in_array($this->job_data->status, ['success', 'waiting', 'processing','queued']) || isset( $args['immediate']) || isset( $args['requeue'])){
            self::log([
                'log' =>  'requeue-> cpcss requeue manually',
                'url' => $this->job_data->job->url,
            ]);
            $this->job_data->requeue(isset( $args['immediate']) || isset( $args['requeue']) ? 1 : -1);
            $this->job_data->save();
        }

        $this->async = apply_filters('uucss/purge/async',true);

        if (! $this->async ) {

            $this->init_async_store($this->job_data, $args);

        }else if(isset( $args['immediate'] )){

            $this->schedule_cron('cpcss_async_queue', [
                'job_data' => $this->job_data,
                'args'     => $args
            ]);

        }

        return true;
    }

    function enqueue_cpcss($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_cpcss'] )){
            return false;
        }

        if(!$this->job_data){
            $this->job_data = new RapidLoad_Job_Data($job, 'cpcss');
        }

        new CriticalCSS_Enqueue($this->job_data);

    }

    public function initFileSystem() {

        // Todo cache base setup
        /*$cache_base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR);

        $cache_base_option = RapidLoad_Base::get_option('rapidload_cache_base', null);

        if(!isset($cache_base_option)){

            $cache_base_option = $cache_base;
            RapidLoad_Base::update_option('rapidload_cache_base', $cache_base_option);
        }

        $this->base = RapidLoad_ThirdParty::plugin_exists('autoptimize') ? $cache_base_option . 'cpcss' : $cache_base . 'cpcss';*/

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'cpcss';

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
        //$created = $this->file_system->mkdir( self::$base_dir );.
        $created = RapidLoad_Cache_Store::mkdir_p( self::$base_dir );

        if (!$created || ! $this->file_system->is_writable( self::$base_dir ) || ! $this->file_system->is_readable( self::$base_dir ) ) {
            return false;
        }

        return true;
    }

    public function init_async_store($job_data, $args)
    {
        $store = new CriticalCSS_Store($job_data, $args);
        $store->purge_css();
    }

    public function cleanCacheFiles(){

        $data = CriticalCSS_DB::get_data_where(" WHERE status = 'success' ");

        $used_files = [];

        foreach ($data as $value){
            if(!empty($value->data)){
                $files = RapidLoad_Job_Data::transform_cpcss_data_to_array($value->data);
                foreach ($files as $file){
                    $file_data = self::extract_file_data($file);
                    for ($i = 1; $i <= $file_data['file_count']; $i++) {
                        $file_name = ($i == 1) ? $file_data['file_name'] : str_replace(".css","-" . $i . ".css", $file_data['file_name']);
                        array_push($used_files,$file_name);
                    }
                }
            }
        }

        if($this->file_system->exists(CriticalCSS::$base_dir)){
            if ($handle = opendir(CriticalCSS::$base_dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ('.' === $file) continue;
                    if ('..' === $file) continue;

                    if(!in_array($file, $used_files) && $this->file_system->exists(CriticalCSS::$base_dir . '/' . $file)){
                        $this->file_system->delete(CriticalCSS::$base_dir . '/' . $file);
                    }
                }
                closedir($handle);
            }
        }
    }

    public static function extract_file_data($fileName){
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

    public static function breakCSSIntoParts($cssContent, $maxLength = 60000) {
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