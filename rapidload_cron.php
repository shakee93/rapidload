<?php
/**
 * RapidLoad CLI Cron Script
 * 
 * Usage: php rapidload_cron.php
 * Add to crontab: * * * * * /usr/bin/php /path/to/wp-content/plugins/unusedcss/rapidload_cron.php
 */

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

// Define WordPress path (now two levels up from plugin root)
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!file_exists($wp_load_path)) {
    die('WordPress installation not found at: ' . $wp_load_path);
}

// Load WordPress
try {
    // Load wp-config.php first to check database settings
    $wp_config_path = dirname($wp_load_path) . '/wp-config.php';

    if (!file_exists($wp_config_path)) {
        throw new Exception('wp-config.php not found at: ' . $wp_config_path);
    }
    
    // Load WordPress
    require_once $wp_load_path;
    
    // Verify WordPress loaded correctly
    if (!defined('ABSPATH')) {
        throw new Exception('WordPress did not load correctly - ABSPATH not defined');
    }
    
    // Verify database connection
    global $wpdb;
    if (!$wpdb) {
        throw new Exception('WordPress database object not available');
    }

    // Get uploads directory for logging
    $upload_dir = wp_upload_dir();
    $log_file_path = $upload_dir['basedir'] . '/rapidload_cron.log';

} catch (Exception $e) {
    die('Error loading WordPress: ' . $e->getMessage() . "\n");
}

// Check if RapidLoad is active
if (!class_exists('RapidLoad_Base')) {
    die('RapidLoad plugin is not active');
}

// Log function
function rapidload_cron_log($message) {
    global $log_file_path;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    echo $log_message;
    file_put_contents($log_file_path, $log_message, FILE_APPEND);
}

try {
    // Log start of process
    rapidload_cron_log('Starting RapidLoad optimization process');
    rapidload_cron_log('WordPress loaded from: ' . ABSPATH);
    rapidload_cron_log('Database prefix: ' . $wpdb->prefix);

    // Trigger the optimization process
    do_action('uucss/queue/task');

    // Log completion
    rapidload_cron_log('RapidLoad optimization process completed successfully');
    
    exit(0);
} catch (Exception $e) {
    $error_message = 'Error: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString();
    rapidload_cron_log($error_message);
    exit(1);
} 