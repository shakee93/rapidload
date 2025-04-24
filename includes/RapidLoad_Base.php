<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Base
{
    use RapidLoad_Utils;

    public static $options;
    public static $paged_options;

    public $url = null;
    public $rule = null;

    public $applicable_rule = false;
    public $defined_rules = false;

    private static $base_instance = null;

    public static function get(){
        if(!self::$base_instance){
            self::$base_instance = new RapidLoad_Base();
        }
        return self::$base_instance;
    }

    private $container = [];

    public static $page_options = [
        'safelist',
        'exclude',
        'blocklist'
    ];

    public static $modules = [];

    public function __construct()
    {
        self::$modules = [
            [
                'name' => 'Cache',
                'key' => 'uucss_enable_cache',
                'option_name' => 'rapidload_module_cache'
            ],
            [
                'name' => 'CDN',
                'key' => 'uucss_enable_cdn',
                'option_name' => 'rapidload_module_cdn'
            ],
            [
                'name' => 'CSS',
                'key' => 'uucss_enable_css',
                'option_name' => 'rapidload_module_css'
            ],
            [
                'name' => 'Font',
                'key' => 'uucss_enable_font_optimization',
                'option_name' => 'rapidload_module_font'
            ],
            [
                'name' => 'Javascript',
                'key' => 'uucss_enable_javascript',
                'option_name' => 'rapidload_module_js'
            ],
            [
                'name' => 'Image',
                'key' => 'uucss_enable_image_delivery',
                'option_name' => 'rapidload_module_image'
            ],
        ];

        add_action('plugins_loaded', function (){

            RapidLoad_DB::rapidload_db_update_db_version();

            if (isset($_REQUEST['rapidload_preview'])) {
                add_filter('determine_current_user', function (){
                    return 0;
                }, 99);
                show_admin_bar(false);
            }

            self::rapidload_fetch_options();

            add_filter('rapidload/options', [$this, 'merge_job_options']);

            self::get_merged_options();

            self::activateByLicenseKey();
            self::activate();
            self::flush_rapidload();

            if(is_admin()){

                new RapidLoad_Onboard();

            }

            $this->check_dependencies();

            $this->init_log_dir();

            RapidLoad_ThirdParty::rapidload_initialize();

            register_deactivation_hook( UUCSS_PLUGIN_FILE, [ $this, 'vanish' ] );

            add_filter('uucss/cache-base-dir', function ($dir){

                if(function_exists('is_multisite') && is_multisite()){

                    $excludes = ["http://","https://"];

                    $url = get_site_url();

                    foreach ($excludes as $exclude){
                        $url = str_replace($exclude, "", $url);
                    }

                    return $dir . $url . "/";

                }

                return $dir;

            }, 10 , 1);

            add_filter('plugin_row_meta',[$this, 'add_plugin_row_meta_links'],10,4);

            add_filter( 'plugin_action_links_' . plugin_basename( UUCSS_PLUGIN_FILE ), [
                $this,
                'add_plugin_action_link'
            ] );

            $this->add_plugin_update_message();

            if(is_admin()){

                RapidLoad_DB::rapidload_db_check_db_updates();

                self::enqueueGlobalScript();

            }

            $this->container['feedback'] = new RapidLoad_Feedback();
            $this->container['buffer'] = new RapidLoad_Buffer();
            $this->container['modules'] = RapidLoad_Module::get();
            $this->container['queue'] = new RapidLoad_Queue();
            $this->container['admin'] = new RapidLoad_Admin();
            $this->container['admin_frontend'] = new RapidLoad_Admin_Frontend();
            $this->container['rest_api'] = new RapidLoadRestApi();
            $this->container['enqueue'] = new RapidLoad_Enqueue();

        });

        add_action( 'admin_init', array( 'PAnD', 'init' ) );

        $this->enqueue_diagnose_script();

    }

    public function enqueue_diagnose_script(){

        if (defined('RAPIDLOAD_DEV_MODE') && RAPIDLOAD_DEV_MODE) {
            $diagnose_script_content = file_get_contents(plugin_dir_path(UUCSS_PLUGIN_FILE) . 'assets/js/rapidload-diagnose-script.js');
        } else {
            $diagnose_script_content = file_get_contents(plugin_dir_path(UUCSS_PLUGIN_FILE) . 'assets/js/rapidload-diagnose-script.min.js');
        }

        add_action('wp_enqueue_scripts', function() use ($diagnose_script_content) {
            if (isset($_REQUEST['rapidload_preview'])) {
                wp_register_script('rapidload-diagnose-script', '', [], '');
                wp_enqueue_script('rapidload-diagnose-script');

                wp_add_inline_script('rapidload-diagnose-script', $diagnose_script_content);

                wp_localize_script('rapidload-diagnose-script', 'rapidload_diagnose_tool', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('uucss_nonce'),
                ));
            }
        });

    }

    public static function get_license_key(){

        $options = self::rapidload_fetch_options();

        if(isset($options['uucss_api_key'])){
            return $options['uucss_api_key'];
        }
        return null;
    }

    function merge_job_options($option){

        $this->url = $this->rapidload_util_get_current_url();

        $this->url = $this->rapidload_util_transform_url($this->url);

        if(RapidLoad_DB::$current_version !== RapidLoad_DB::$db_version){
            return $option;
        }

        RapidLoad_Enqueue::$job = new RapidLoad_Job(['url' => $this->url]);

        if(isset(RapidLoad_Enqueue::$job->id)){

            $strategy = $this->is_mobile() ? 'mobile' : 'desktop';

            if($strategy === "mobile"){
                $page_options = RapidLoad_Enqueue::$job->rapidload_job_get_mobile_options(true);
            }else{
                $page_options = RapidLoad_Enqueue::$job->rapidload_job_get_desktop_options(true);
            }

            foreach ($page_options as $key => $op){
                $option[$key] = $op;
            }

        }

        return $option;
    }

    public function add_plugin_action_link( $links ) {

        $_links = array(
            '<a href="' . admin_url( 'admin.php?page=rapidload' ) . '">Settings</a>',
        );

        return array_merge( $_links, $links );
    }

    public function check_dependencies() {

        if(self::is_api_key_verified()) {
            return true;
        }else {
            self::rapidload_display_get_start_link();
        }

        return false;
    }

    public function init_log_dir(){

        if(!self::rapidload_util_get_log_option()){
            return false;
        }

        $file_system = self::get_log_instance();

        if ( $file_system->rapidload_file_exists( UUCSS_LOG_DIR ) ) {
            return true;
        }

        if( $file_system->is_writable( UUCSS_LOG_DIR ) ){
            return false;
        }

        $created = $file_system->mkdir( UUCSS_LOG_DIR , 0755, !$file_system->rapidload_file_exists( wp_get_upload_dir()['basedir'] . '/rapidload/' ));

        if (!$created || ! $file_system->is_writable( UUCSS_LOG_DIR ) || ! $file_system->rapidload_file_is_readable( UUCSS_LOG_DIR ) ) {
            return false;
        }

        @file_put_contents(wp_get_upload_dir()['basedir'] . '/rapidload/.htaccess','Require all denied' . PHP_EOL . 'Options -Indexes');

        return true;
    }

    public function modules(){
        return isset($this->container['modules']) ? $this->container['modules'] : null;
    }

    public static function enqueueGlobalScript() {
        add_action( 'admin_enqueue_scripts', function () {

            $deregister_scripts = apply_filters('uucss/scripts/global/deregister', ['popper']);

            if(isset($deregister_scripts) && is_array($deregister_scripts)){
                foreach ($deregister_scripts as $deregister_script){
                    wp_dequeue_script($deregister_script);
                    wp_deregister_script($deregister_script);
                }
            }

            wp_enqueue_script( 'popper', UUCSS_PLUGIN_URL . 'assets/libs/tippy/popper.min.js', array( 'jquery' ) );
            wp_enqueue_script( 'noty', UUCSS_PLUGIN_URL . 'assets/libs/noty/noty.js', array( 'jquery' ) );
            wp_enqueue_script( 'tippy', UUCSS_PLUGIN_URL . 'assets/libs/tippy/tippy-bundle.umd.min.js', array( 'jquery' ) );
            wp_enqueue_style( 'tippy', UUCSS_PLUGIN_URL . 'assets/libs/tippy/tippy.css' );
            wp_enqueue_style( 'noty', UUCSS_PLUGIN_URL . 'assets/libs/noty/noty.css' );
            wp_enqueue_style( 'noty-animate', UUCSS_PLUGIN_URL . 'assets/libs/noty/animate.css' );
            wp_enqueue_style( 'noty-theme', UUCSS_PLUGIN_URL . 'assets/libs/noty/themes/mint.css' );
            wp_enqueue_style( 'featherlight', UUCSS_PLUGIN_URL . 'assets/libs/popup/featherlight.css' );
            wp_enqueue_script( 'featherlight', UUCSS_PLUGIN_URL . 'assets/libs/popup/featherlight.js' , array( 'jquery' ) );

            $rapidload_license_data = get_option('rapidload_license_data', null);   

            if($rapidload_license_data){
                $rapidload_license_data = unserialize($rapidload_license_data);
            }

            wp_register_script( 'uucss_global_admin_script', UUCSS_PLUGIN_URL . 'assets/js/uucss_global.js', [ 'jquery', 'wp-util' ], UUCSS_VERSION );
            $data = array(
                'ajax_url'          => admin_url( 'admin-ajax.php' ),
                'setting_url'       => admin_url( 'options-general.php?page=uucss_legacy' ),
                'on_board_complete' => self::is_api_key_verified() || self::get_option('rapidload_onboard_skipped', false),
                'home_url' => home_url(),
                'api_url' => RapidLoad_Api::rapidload_api_get_key(),
                'nonce' => self::rapidload_util_create_nonce( 'uucss_nonce' ),
                'active_modules' => (array)self::get()->modules()->active_modules(),
                'notifications' => apply_filters('uucss/notifications', []),
                'activation_url' => self::rapidload_util_activation_url('authorize' ),
                'onboard_activation_url' => self::rapidload_util_onboard_activation_url('authorize' ),
                'app_url' => defined('UUCSS_APP_URL') ? trailingslashit(UUCSS_APP_URL) : 'https://app.rapidload.io/',
                'db_tobe_updated' => RapidLoad_DB::$current_version < 1.6,
                "test_mode" => isset(self::$options['rapidload_test_mode']) && self::$options['rapidload_test_mode'] === "1",
                "uucss_disable_error_tracking" => isset(self::$options['uucss_disable_error_tracking']) && self::$options['uucss_disable_error_tracking'] === "1",
                "rapidload_license_data" => $rapidload_license_data
            );
            wp_localize_script( 'uucss_global_admin_script', 'uucss_global', $data );
            wp_enqueue_script( 'uucss_global_admin_script' );
            wp_enqueue_style( 'uucss_global_admin', UUCSS_PLUGIN_URL . 'assets/css/uucss_global.css', [], UUCSS_VERSION );

        }, apply_filters('uucss/scripts/global/priority', 90));

    }

    function add_plugin_update_message(){

        global $pagenow;

        if ( 'plugins.php' === $pagenow )
        {
            $file   = basename( UUCSS_PLUGIN_FILE );
            $folder = basename( dirname( UUCSS_PLUGIN_FILE ) );
            $hook = "in_plugin_update_message-{$folder}/{$file}";
            add_action( $hook, [$this, 'render_update_message'], 20, 2 );
        }

    }

    function render_update_message($plugin_data, $r ){

        $response = wp_remote_get( 'https://raw.githubusercontent.com/shakee93/autoptimize-unusedcss/master/readme.txt?format=txt' );
        
        if ( is_wp_error( $response ) ) {
            return;
        }

        $data = wp_remote_retrieve_body( $response );

        $changelog  = stristr( $data, '== Changelog ==' );

        $changelog = preg_split("/\=(.*?)\=/", str_replace('== Changelog ==','',$changelog));

        if(isset($changelog[1])){

            $changelog = explode('*', $changelog[1]);

            array_shift($changelog);

            if(count($changelog) > 0){
                echo '<div style="margin-bottom: 1em"><strong style="padding-left: 25px;">What\'s New ?</strong><ol style="list-style-type: disc;margin: 5px 50px">';
            }

            foreach ($changelog as $index => $log){
                if($index === 3){
                    break;
                }
                echo '<li style="margin-bottom: 0">' . esc_html(preg_replace("/\r|\n/","",$log)) . '</li>';
            }

            if(count($changelog) > 0){
                echo '</ol></div><p style="display: none" class="empty">';
            }

        }

        ?>
        <!--<hr class="rapidload-major-update-separator"/>
        <div class="rapidload-major-update-message">
            <div class="rapidload-major-update-message-icon">
                <span class="dashicons dashicons-info-outline"></span>
            </div>
            <div class="rapidload-major-update-message-content">
                <div class="rapidload-major-update-message-content-title">
                    <strong>Heads up, New RapidLoad comes in as ALL-IN-ONE solution for page-speed optimization.</strong>
                </div>
                <div class="rapidload-major-update-message-content-description">
                    RapidLoad 2.1 unveils the revolutionary <strong>Titan Optimizer</strong> for page optimization.
                </div>
            </div>
        </div>
        <p style="display: none" class="empty">-->
        <?php

    }

    function add_plugin_row_meta_links($plugin_meta, $plugin_file, $plugin_data, $status)
    {
        if(isset($plugin_data['TextDomain']) && $plugin_data['TextDomain'] === 'autoptimize-unusedcss'){
            $plugin_meta[] = '<a href="https://docs.rapidload.io/" target="_blank">Documentation</a>';
            $plugin_meta[] = '<a href="https://rapidload.zendesk.com/hc/en-us/requests/new" target="_blank">Submit Ticket</a>';
        }
        return $plugin_meta;
    }

    public function vanish(){

        do_action('rapidload/vanish');

        $this->clear_jobs('url');

    }

    public function clear_jobs($type = 'all'){

        RapidLoad_DB::clear_jobs($type);

    }

    public static function rapidload_fetch_options($cache = true)
    {

        if(isset(self::$options) && $cache){
            return self::$options;
        }

        $is_multisite = is_multisite();

        if($is_multisite){

            self::$options = get_blog_option(get_current_blog_id(), 'autoptimize_uucss_settings', self::get_default_options());

        }else{

            self::$options = get_site_option( 'autoptimize_uucss_settings', self::get_default_options() );
        }

        foreach (self::$modules as $module){

            $def_value = isset(self::$options[$module['key']]) ? self::$options[$module['key']] : "";

            if($is_multisite){

                self::$options[$module['key']] = get_blog_option(get_current_blog_id(), $module['option_name'], $def_value);

            }else{

                self::$options[$module['key']] = get_site_option( $module['option_name'], $def_value );
            }

        }

        return self::$options;
    }

    public static function get_merged_options(){

        if(!isset(self::$options)){
            self::$options = self::rapidload_fetch_options();
        }

        if(isset(self::$paged_options)){
            return self::$paged_options;
        }

        self::$paged_options = apply_filters('rapidload/options', self::$options);

        if(isset($_REQUEST['rapidload_delay_js']) && $_REQUEST['rapidload_delay_js'] === "1"){
            self::$paged_options['delay_javascript'] = "1";
            self::$paged_options['uucss_enable_javascript'] = "1";
        }
        if(isset($_REQUEST['rapidload_defer_js']) && $_REQUEST['rapidload_defer_js'] === "1"){
            self::$paged_options['uucss_load_js_method'] = "defer";
            self::$paged_options['uucss_enable_javascript'] = "1";
        }
        if(isset($_REQUEST['rapidload_enable_cpcss']) && $_REQUEST['rapidload_enable_cpcss'] === "1"){
            self::$paged_options['uucss_enable_cpcss'] = "1";
            self::$paged_options['uucss_enable_css'] = "1";
        }

        return self::$paged_options;
    }

    public static function get_option($name, $default = null)
    {
        if(is_multisite()){

            return get_blog_option(get_current_blog_id(), $name, $default);

        }
        return get_site_option( $name, $default );
    }

    public static function get_page_options($post_id)
    {
        $options = [];

        if($post_id){

            foreach (self::$page_options as $option) {
                $options[$option] = get_post_meta( $post_id, '_uucss_' . $option, true );
            }

        }

        return $options;
    }

    public static function update_option($name, $default)
    {
        if(is_multisite()){

            return update_blog_option(get_current_blog_id(), $name, $default);

        }
        return update_site_option( $name, $default );
    }

    public static function delete_option($name)
    {
        if(is_multisite()){

            return delete_blog_option(get_current_blog_id(), $name);

        }
        return delete_site_option( $name );
    }

    public static function get_default_options(){
        return [
            'uucss_enable_css' => "1",
            'uucss_fontface' => "1",
            'uucss_keyframes' => "1",
            'uucss_variables' => "1",
            'uucss_enable_uucss' => "0",
            'uucss_enable_cpcss' => "1",
            'uucss_minify' => "1",
            'uucss_support_next_gen_formats' => "1",
            'uucss_set_width_and_height' => "1",
            'uucss_self_host_google_fonts' => "1",
            'uucss_image_optimize_level' => "lossless",
            'uucss_exclude_above_the_fold_image_count' => 3,
            'uucss_enable_page_optimizer' => "1",
            'rapidload_test_mode' => "0",
            'rapidload_cpcss_file_character_length' => 0,
            'uucss_adaptive_image_delivery' => "1",
            'uucss_load_js_method' => "1",
            'uucss_lazy_load_images' => "1",
            'minify_js' => "1",
            'uucss_enable_cache' => "0",
            'uucss_enable_cdn' => "0",
            'update_htaccess_file' => "0",
            'uucss_lazy_load_iframes' => "0",
            'uucss_disable_add_to_queue' => "1"

        ];
    }

    public static function uucss_activate() {

        $default_options = self::get_option('autoptimize_uucss_settings',self::get_default_options());

        if(!isset($default_options['uucss_api_key'])){
            self::update_option('autoptimize_uucss_settings', $default_options);
        }

        add_option( 'rapidload_do_activation_redirect', true );
    }

    public static function activateByLicenseKey(){

        if(!isset($_REQUEST['rapidload_license']) || empty($_REQUEST['rapidload_license'])){
            return;
        }

        $token = sanitize_text_field( wp_unslash( $_REQUEST['rapidload_license'] ) );

        $options = self::get_option( 'autoptimize_uucss_settings' , []);

        if ( ! isset( $options ) || empty( $options ) || ! $options ) {
            $options = [];
        }

        $uucss_api         = new RapidLoad_Api();
        $uucss_api->apiKey = $token;
        $results           = $uucss_api->rapidload_api_post( 'connect', [ 'url' => trailingslashit(home_url()), 'type' => 'wordpress' ] );

        if ( !$uucss_api->is_error( $results ) ) {

            $options['uucss_api_key_verified'] = "1";
            $options['uucss_api_key']          = $token;

            self::update_option( 'autoptimize_uucss_settings', $options );

            header( 'Location: ' . admin_url( 'admin.php?page=rapidload') );
            exit;
        }

    }

    public static function flush_rapidload(){

        if(isset($_REQUEST['flush_rapidload'])){
            RapidLoad_DB::flush_all_rapidload(get_current_blog_id());
        }

    }

    public static function activate() {

        if ( ! isset( $_REQUEST['token'] ) || empty( $_REQUEST['token'] ) ) {
            return;
        }

        if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'uucss_activation' ) ) {
            self::add_admin_notice( 'RapidLoad : Request verification failed for Activation. Contact support if the problem persists.', 'error' );

            return;
        }

        $token = sanitize_text_field( wp_unslash( $_REQUEST['token'] ) );

        if ( strlen( $token ) !== 32 ) {
            self::add_admin_notice( 'RapidLoad : Invalid Api Token Received from the Activation. Contact support if the problem persists.', 'error' );

            return;
        }

        $options = self::rapidload_fetch_options();

        if ( ! isset( $options ) || empty( $options ) || ! $options ) {
            $options = [];
        }

        // Hey 👋 you stalker ! you can set this key to true, but its no use ☹️ api_key will be verified on each server request
        $options['uucss_api_key_verified'] = "1";
        $options['uucss_api_key']          = $token;

        self::update_option( 'autoptimize_uucss_settings', $options );

        if(!isset($options['whitelist_packs']) || isset($options['whitelist_packs']) && empty($options['whitelist_packs'])){

            $data        = self::suggest_whitelist_packs();
            $white_packs = isset($data) ? $data : [];

            $options['whitelist_packs'] = array();
            foreach ( $white_packs as $white_pack ) {
                $options['whitelist_packs'][] = $white_pack->id . ':' . $white_pack->name;
            }

            self::update_option( 'autoptimize_uucss_settings', $options );
        }

        self::rapidload_fetch_options(false);

        self::add_admin_notice( 'RapidLoad : 🙏 Thank you for using our plugin. if you have any questions feel free to contact us.', 'success' );
    }

    public static function suggest_whitelist_packs($from = null) {

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins        = get_plugins();
        $active_plugins = array_map( function ( $key, $item ) {

            $item['slug'] = $key;

            return $item;
        }, array_keys( $plugins ), $plugins );

        $api = new RapidLoad_Api();

        $data = $api->rapidload_api_post( 'whitelist-packs/wp-suggest', [
            'plugins' => $active_plugins,
            'theme'   => get_template(),
            'url'     => site_url()
        ] );

        if(isset($data) && isset($data->data) && is_array($data->data)){
            self::$options['suggested_whitelist_packs'] = $data->data;
            self::update_option( 'autoptimize_uucss_settings', self::$options );

            if(wp_doing_ajax()){
                wp_send_json_success( $data->data);
            }
        }

        return isset($data) && isset($data->data) && is_array($data->data) ? $data->data : [];
    }

    public function rules_enabled(){
        return
            isset(self::$options['uucss_enable_rules']) &&
            self::$options['uucss_enable_rules'] === "1" &&
            RapidLoad_DB::$current_version > 1.1 &&
            apply_filters('uucss/rules/enable', true);
    }

    public function critical_css_enabled(){
        return
            isset(self::$options['uucss_enable_cpcss']) &&
            self::$options['uucss_enable_cpcss'] === "1" &&
            RapidLoad_DB::$current_version > 1.2;
    }

    public function get_applicable_rule($url, $args = []){

        if(!$this->applicable_rule){

            if(isset($args['rule']) && self::get()->rules_enabled()){

                $this->applicable_rule = RapidLoad_DB::get_applied_rule($args['rule'], $url);

            }

        }

        return $this->applicable_rule;
    }

    public static function is_domain_verified(){
        $options = self::rapidload_fetch_options();
        return  $options['valid_domain'];
    }

    public function get_pre_defined_rules($with_permalink = false){

        if(!$this->defined_rules){

            $this->defined_rules = self::get_defined_rules($with_permalink);
        }

        return $this->defined_rules;
    }

    public static function cache_file_count(){
        $uucss_files = isset(UnusedCSS::$base_dir) && !empty(UnusedCSS::$base_dir) ? scandir(UnusedCSS::$base_dir) : [];
        if(is_array($uucss_files)){
            $uucss_files = array_filter($uucss_files, function ($file){
                return false !== strpos($file, '.css');
            });
        }else{
            $uucss_files = [];
        }
        $cpcss_files = isset(CriticalCSS::$base_dir) && !empty(CriticalCSS::$base_dir) ? scandir(CriticalCSS::$base_dir) : [];
        if(is_array($cpcss_files)){
            $cpcss_files = array_filter($cpcss_files, function ($file){
                return false !== strpos($file, '.css');
            });
        }else{
            $cpcss_files = [];
        }
        return count($uucss_files) + count($cpcss_files);
    }

    public static function is_api_key_verified() {

        $api_key_status = isset( self::$options['uucss_api_key_verified'] ) ? self::$options['uucss_api_key_verified'] : '';

        return $api_key_status === '1' || $api_key_status === 1;

    }

    public static function rapidload_display_get_start_link() {
        add_filter( 'plugin_action_links_' . plugin_basename( UUCSS_PLUGIN_FILE ), function ( $links ) {
            $_links = array(
                '<a href="' . admin_url( 'options-general.php?page=rapidload' ) . '">Get Started <span>⚡️</span> </a>',
            );

            return array_merge( $_links, $links );
        } );
    }
}