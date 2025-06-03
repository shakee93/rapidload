<?php
/*
Plugin Name: RapidLoad AI - Optimize Web Vitals Automatically
Plugin URI:  https://rapidload.ai/
Description: Supercharge your website with RapidLoad AI, featuring cutting-edge AI to automate optimizing CSS, JavaScript, images, fonts, and caching.
Version:     3.1.13
Author:      RapidLoad 
Author URI:  https://rapidload.ai/
License:     GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: unusedcss
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if(class_exists('RapidLoad_Base')){
    return;
}

if(isset($_REQUEST['no_rapidload'])){
    return;
}

require_once __DIR__ . '/constants.php';

define( 'RAPIDLOAD_PLUGIN_FILE', __FILE__ );
define('RAPIDLOAD_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('RAPIDLOAD_ABSPATH', str_replace(wp_basename(WP_CONTENT_DIR), '', WP_CONTENT_DIR));
define('RAPIDLOAD_BASE',  ( function_exists( 'wp_normalize_path' ) ) ? plugin_basename( __DIR__ . '/' . basename(__FILE__) ) : null);

if (file_exists(dirname(__FILE__) . '/includes/RapidLoad_CLI_Command.php')) {
    require_once dirname(__FILE__) . '/includes/RapidLoad_CLI_Command.php';
}

if ( is_multisite() ) {
    $blog_id = get_current_blog_id();
    define('RAPIDLOAD_LOG_DIR', wp_get_upload_dir()['basedir'] . '/rapidload/' . gmdate('Ymd') . '/' . $blog_id . '/');
} else {
    define('RAPIDLOAD_LOG_DIR', wp_get_upload_dir()['basedir'] .  '/rapidload/' . gmdate('Ymd') . '/');
}


require_once __DIR__ . '/vendor/autoload.php';

if(is_admin()){

    register_activation_hook( RAPIDLOAD_PLUGIN_FILE, 'RapidLoad_Base::uucss_activate' );

    register_activation_hook( RAPIDLOAD_PLUGIN_FILE, 'RapidLoad_DB::initialize' );

    register_uninstall_hook(RAPIDLOAD_PLUGIN_FILE, 'RapidLoad_DB::drop');

    register_activation_hook( RAPIDLOAD_PLUGIN_FILE, 'RapidLoad_Cache::on_activation' );

    register_deactivation_hook( RAPIDLOAD_PLUGIN_FILE, 'RapidLoad_Cache::on_deactivation' );

}

/**
 * @type $uucss UnusedCSS_Autoptimize
 */

global $rapidload;

$rapidload = RapidLoad_Base::get();



