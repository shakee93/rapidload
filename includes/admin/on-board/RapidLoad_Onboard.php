<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Onboard{
    use RapidLoad_Utils;

    private $uucss;

    public static $onboard_steps;
    public static $current_step;

    public function __construct() {

        add_action( 'admin_init', [ $this, 'redirect' ] );
        add_action( "wp_ajax_rapidload_configured", [ $this, 'rapidload_configured' ] );
        add_action( "wp_ajax_run_first_job", [ $this, 'run_first_job' ] );
        add_action( 'admin_head', [ $this, 'remove_notices' ] );
        add_filter('uucss/on-board/complete', function ($value){
            return self::on_board_completed();
        }, 10, 1);

    }

    function run_first_job(){

        self::verify_nonce();

        if(!RapidLoad_Base::is_api_key_verified()){
            wp_send_json_error(false);
        }

        $site_url = $this->transform_url(get_site_url());

        $job = new RapidLoad_Job([
            'url' => $site_url
        ]);
        $job->save(true);

        $job_data = new RapidLoad_Job_Data($job, 'uucss');

        if(!isset($job_data->id)){
            $job_data->save();
        }

        $store = new RapidLoad_UnusedCSS_Store($job_data, [ 'immediate' => true ]);
        $store->purge_css();

        $this->rapidload_configured();

    }

    function rapidload_configured(){

        self::verify_nonce();

        $status = [];
        $status['rapidload_connected'] = RapidLoad_Base::is_api_key_verified();
        $status['uucss_first_job_done'] = (bool)RapidLoad_DB::get_first_link();
        $status['uucss_first_job'] = RapidLoad_DB::get_first_link();

        if(wp_doing_ajax()){
            wp_send_json_success($status);
        }else{
            return $status;
        }
    }

    function remove_notices(){

        if(!isset($_REQUEST['action'])){
            return;
        }
        if(!isset($_REQUEST['plugin'])){
            return;
        }

        if(get_current_screen() &&
            get_current_screen()->base === 'update' &&
            $_REQUEST['action'] === 'install-plugin' &&
                $_REQUEST['plugin'] === 'autoptimize'){
            echo '<style>div.notice{display: none !important;}</style>';
        }
    }

    public static function on_board_completed(){
        return RapidLoad_Base::is_api_key_verified() || RapidLoad_Base::get_option('rapidload_onboard_skipped', false);
    }

    function redirect() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
        if ( strpos( home_url( $request_uri ), '/options-general.php?page=rapidload-on-board' ) &&
            self::on_board_completed() && !strpos( home_url( $request_uri ), 'nonce' )) {
            wp_safe_redirect( admin_url( 'admin.php?page=rapidload' ) );
        } else if ( RapidLoad_Base::get_option( 'rapidload_do_activation_redirect' ) ) {
            RapidLoad_Base::delete_option( 'rapidload_do_activation_redirect' );
            wp_safe_redirect( '/wp-admin/options-general.php?page=rapidload#/onboard' );
        }
    }

    public static function display_get_start_link() {
        add_filter( 'plugin_action_links_' . plugin_basename( RAPIDLOAD_PLUGIN_FILE ), function ( $links ) {
            $_links = array(
                '<a href="' . admin_url( 'options-general.php?page=rapidload' ) . '">Get Started <span>⚡️</span> </a>',
            );

            return array_merge( $_links, $links );
        } );
    }
}