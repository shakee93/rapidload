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
        $this->options = RapidLoad_Base::fetch_options();

        if(!isset($this->options['uucss_enable_uucss'])){
            //return;
        }

        $this->file_system = new RapidLoad_FileSystem();

        if( ! $this->initFileSystem() ){
            return;
        }

        if(apply_filters('uucss/enable/notfound_fallback', true)){
            add_action( 'template_redirect', [$this, 'uucss_notfound_fallback'] );
        }

        add_action('rapidload/vanish', [ $this, 'vanish' ]);

        $this->cache_trigger_hooks();

        add_action('rapidload/job/purge', [$this, 'cache_uucss'], 10, 2);

        add_action('rapidload/job/handle', [$this, 'cache_uucss'], 10, 2);

        add_action('rapidload/job/handle', [$this, 'enqueue_uucss'], 20, 2);

        add_action('uucss_async_queue', [$this, 'init_async_store'], 10, 2);

        add_filter('uucss/link', [$this, 'update_link']);

        add_action('rapidload/job/updated', [$this, 'handle_job_updated'], 10 , 2);

        add_filter('uucss/enqueue/cache-file-url', function ($uucss_file){
            return $this->get_cached_file($uucss_file, apply_filters('uucss/enqueue/cache-file-url/cdn', null));
        },10,1);

        add_action( 'admin_notices', [ $this, 'first_uucss_job' ] );

        new UnusedCSS_Queue();
    }

    public function uucss_notfound_fallback(){

        $original_request = strtok( $_SERVER['REQUEST_URI'], '?' );
        $original_path = WP_CONTENT_DIR . apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR)  . 'uucss' . "/" . basename($original_request);

        $options = RapidLoad_Base::fetch_options(false);

        if ( strpos( $original_request, wp_basename( WP_CONTENT_DIR ) . apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR)  . 'uucss' ) !== false
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

    public function first_uucss_job() {

        if ( class_exists('PAnD') && ! PAnD::is_admin_notice_active( 'first-uucss-job-forever' ) ) {
            return;
        }

        if(get_current_screen() && get_current_screen()->base == 'settings_page_uucss'){
            return;
        }

        $first_link = RapidLoad_DB::get_first_link();

        $job = false;

        if($first_link){

            $first_link = new RapidLoad_Job([
                'url' => $first_link
            ]);

            $job = new RapidLoad_Job_Data($first_link, 'uucss');
        }

        if ( $job && isset($job->id) && $job->status == 'success' ) : ?>
            <div data-dismissible="first-uucss-job-forever"
                 class="updated notice uucss-notice notice-success is-dismissible">
                <h4><span class="dashicons dashicons-yes-alt"></span> RapidLoad successfully ran your first job!</h4>
                <p><?php _e( 'You slashed <strong>' . $job->get_stats()->reductionSize . ' </strong> of unused CSS - that\'s <strong>' . $job->get_stats()->reduction . '% </strong> of your total CSS file size. Way to go 👏', 'sample-text-domain' ); ?></p>
            </div>
        <?php endif;

        if ( $job && isset($job->id) && $job->status == 'failed' ) : ?>
            <div data-dismissible="first-uucss-job-forever"
                 class="error notice uucss-notice notice-error is-dismissible">
                <h4><span class="dashicons dashicons-no-alt"></span> RapidLoad : We were unable to remove unused css
                    from
                    your site 🤕</h4>

                <div>
                    <p> Our team can help. Get in touch with support <a target="_blank"
                                                                        href="https://rapidload.zendesk.com/hc/en-us/requests/new">here</a>
                    </p>
                    <blockquote class="error notice">
                        <strong>Link :</strong> <?php echo $job['url'] ?> <br>
                        <strong>Error :</strong> <?php echo $job['meta']['error']['code'] ?> <br>
                        <strong>Message :</strong> <?php echo $job['meta']['error']['message'] ?>
                    </blockquote>
                </div>

            </div>
        <?php endif;
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
                        $link['rule_status'] = $job_data->status;
                        $link['rule_hits'] = $job_data->hits;
                        $link['applied_links'] = count($job->get_urls());
                        $link['applied_successful_links'] = 0;
                    }

                    $link['status'] = $job_data->status;
                    $link['success_count'] = $job_data->hits;
                    $link['files'] = $job_data->get_files();
                    $link['job_id'] = $job_data->queue_job_id;
                    $link['meta']['id'] = $job_data->queue_job_id;
                    $link['meta']['warnings'] = $job_data->get_warnings();
                    $link['meta']['stats'] = isset($job_data->stats) ? unserialize($job_data->stats) : null;
                    $link['meta']['error'] = isset($job_data->error) ? unserialize($job_data->error) : null;
                    $link['meta']['status'] = isset( $job_data->status ) ? $job_data->status : null;
                    $link['time'] = isset( $job_data->created_at ) ? strtotime( $job_data->created_at ) : null;
                    $link['attempts'] = isset( $job_data->attempts ) ? $job_data->attempts : null;
                    $link['rule'] = $job_data->job->rule;
                }

            }

        }

        return $link;
    }

    public function get_cached_file( $file_url, $cdn = null ) {

        if ( ! $cdn || empty( $cdn ) ) {
            $cdn = content_url();
        } else {

            $url_parts = parse_url( content_url() );

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

        self::log([
            'log' => 'UnusedCSS->enqueue_uucss',
            'url' => $job->url,
            'type' => 'injection'
        ]);

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

        if(!$this->is_url_allowed($job->url, $args)){
            return false;
        }

        $this->job_data = new RapidLoad_Job_Data($job, 'uucss');

        if(!isset($this->job_data->id)){
            $this->job_data->save();
        }

        if($this->job_data->status == 'failed' && $this->job_data->attempts > 2 && !isset($args['immediate'])){
            return false;
        }

        if(!in_array($this->job_data->status, ['success', 'waiting', 'processing','queued']) || isset( $args['immediate']) || isset( $args['requeue'])){
            $this->job_data->requeue(isset( $args['immediate']) ? 0 : -1);
            $this->job_data->save();
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

            if(isset($job_data->id)){

                $this->clear_files($job_data);
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

                        $this->file_system->delete( self::$base_dir . '/' . $file['uucss'] );

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

        self::$base_dir = WP_CONTENT_DIR . $this->base;

        if ( $this->file_system->exists( self::$base_dir ) ) {
            return true;
        }

        // make dir if not exists
        $created = $this->file_system->mkdir( self::$base_dir );

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
}