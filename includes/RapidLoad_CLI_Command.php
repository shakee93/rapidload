<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include_once 'RapidLoad_Utils.php';

if(class_exists('RapidLoad_CLI_Command')){
    return;
}

/**
 * Class RapidLoad_CLI_Command
 */

if (defined('WP_CLI') && WP_CLI) {

    class RapidLoad_CLI_Command
    {
        use RapidLoad_Utils;

        public function connect($args, $assoc_args) {
            list($license_key) = $args;

            $uucss_api         = new RapidLoad_Api();
            $uucss_api->apiKey = $license_key;
            $url = $this->rapidload_util_transform_url(home_url());

            $message = "";

            if (isset($assoc_args['url'])) {
                $url = $assoc_args['url'];
            }else{
                $message = "not set";
            }

            $results           = $uucss_api->rapidload_api_post( 'connect', [ 'url' => $url, 'type' => 'wordpress' ] );

            if ( $uucss_api->rapidload_api_is_error( $results ) ) {
                if(isset($results->errors) && isset($results->errors[0])){
                    WP_CLI::error("License Key," . $results->errors[0]->detail . "! - " . get_site_url() . ' - ' . home_url() . ' - ' . $url . ' - ' . $message);
                }else{
                    WP_CLI::error("License Key verification fail");
                }
            }

            $options = RapidLoad_Base::rapidload_get_option( 'autoptimize_uucss_settings' , RapidLoad_Base::rapidload_get_default_options());
            $options['uucss_api_key_verified'] = "1";
            $options['uucss_api_key']          = $license_key;

            if (isset($assoc_args['uucss'])) {
                RapidLoad_Base::rapidload_update_option('rapidload_module_css',"1");
                $options['uucss_enable_uucss'] = "1";
            }

            if (isset($assoc_args['cpcss'])) {
                RapidLoad_Base::rapidload_update_option('rapidload_module_css',"1");
                $options['uucss_enable_cpcss'] = "1";
            }

            RapidLoad_Base::rapidload_update_option( 'autoptimize_uucss_settings', $options );

            WP_CLI::success("License Key connected , $license_key!");
        }

        public function update_db(){
            RapidLoad_DB::rapidload_db_update_db();
            WP_CLI::success("Database updated");
        }

    }

    WP_CLI::add_command('rapidload', 'RapidLoad_CLI_Command');
}
