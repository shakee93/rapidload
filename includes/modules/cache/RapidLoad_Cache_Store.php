<?php

class RapidLoad_Cache_Store
{
    private static $cache_file;

    public function __construct()
    {

    }

    public static function create_advanced_cache_file(){

        if ( ! is_writable( WP_CONTENT_DIR ) ) {
            return false;
        }

        $advanced_cache_sample_file = RAPIDLOAD_PLUGIN_DIR . '/includes/modules/cache/advanced-cache.php';

        if ( ! is_readable( $advanced_cache_sample_file ) ) {
            return false;
        }

        $advanced_cache_file          = WP_CONTENT_DIR . '/advanced-cache.php';
        $advanced_cache_file_contents = file_get_contents( $advanced_cache_sample_file );

        $search  = '/your/path/to/rapidload/constants.php';
        $replace = RAPIDLOAD_CONSTANT_FILE;

        $advanced_cache_file_contents = str_replace( $search, $replace, $advanced_cache_file_contents );
        $advanced_cache_file_created  = file_put_contents( $advanced_cache_file, $advanced_cache_file_contents, LOCK_EX );

        return ( $advanced_cache_file_created === false ) ? false : $advanced_cache_file;

    }

    public static function get_page_cache_errors($url = null)
    {

        if(!isset($url)){
            $url = site_url();
        }

        if ( !file_exists( ABSPATH . 'wp-config.php' ) ) {
            return 'wp-config.php file missing';
        }

        $wp_config_file = ABSPATH . 'wp-config.php';

        if(!is_writable( $wp_config_file )){
            return 'wp-config.php file has no write permission';
        }

        $wp_config_file_contents = file_get_contents( $wp_config_file );

        $found_wp_cache_constant = preg_match( '#define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*true\s*\);#', $wp_config_file_contents, $matches );

        if(!$found_wp_cache_constant){
            return 'WP_CACHE constant not found or set to false';
        }

        $cache_dir = self::get_cache_dir( $url );

        $cache_dir_root = dirname($cache_dir);

        if ( ! is_dir( $cache_dir_root ) || !is_writable( $cache_dir_root )) {
            return 'no permission for cache directory';
        }

        if(!is_dir($cache_dir)){
            return 'no page cache directory found';
        }

        if(defined('DONOTCACHEPAGE') && DONOTCACHEPAGE){
            return 'DONOTCACHEPAGE constant set to true';
        }

        return 'Hit';
    }

    public static function set_wp_cache_constant( $set = true ) {

        if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
            // The config file resides in ABSPATH.
            $wp_config_file = ABSPATH . 'wp-config.php';
        } elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
            // The config file resides one level above ABSPATH but is not part of another installation.
            $wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
        } else {
            // The config file could not be found.
            return false;
        }

        if ( ! is_writable( $wp_config_file ) ) {
            return false;
        }

        $wp_config_file_contents = file_get_contents( $wp_config_file );

        if ( ! is_string( $wp_config_file_contents ) ) {
            return false;
        }

        $found_wp_cache_constant = preg_match( '#define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*(true|false)\s*\);#', $wp_config_file_contents, $matches );

        if ( $found_wp_cache_constant ) {

            if($set){
                $new_wp_config_file_contents = preg_replace(
                    '#define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*false\s*\);#',
                    "define( 'WP_CACHE', true );",
                    $wp_config_file_contents
                );
            }else{
                $new_wp_config_file_contents = preg_replace(
                    '#define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*true\s*\);#',
                    "define( 'WP_CACHE', false );",
                    $wp_config_file_contents
                );
            }

        }else{

            if ( $set ) {

                $default_wp_config_file = ( strpos( $wp_config_file_contents, '/** Sets up WordPress vars and included files. */' ) !== false );

                if ( ! $default_wp_config_file ) {
                    return false;
                }

                $new_wp_config_lines  = '/** Enables page caching for RapidLoad. */' . PHP_EOL;
                $new_wp_config_lines .= "if ( ! defined( 'WP_CACHE' ) ) {" . PHP_EOL;
                $new_wp_config_lines .= "\tdefine( 'WP_CACHE', true );" . PHP_EOL;
                $new_wp_config_lines .= '}' . PHP_EOL;
                $new_wp_config_lines .= PHP_EOL;

                $new_wp_config_file_contents = preg_replace( '#(/\*\* Sets up WordPress vars and included files\. \*/)#', $new_wp_config_lines . '$1', $wp_config_file_contents );

            } else { // Unset.
                $new_wp_config_file_contents = preg_replace(
                    '#define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*true\s*\);#',
                    "define( 'WP_CACHE', false );",
                    $wp_config_file_contents
                );
            }

        }

        if ( ! is_string( $new_wp_config_file_contents ) || empty( $new_wp_config_file_contents ) ) {
            return false;
        }

        $wp_config_file_updated = file_put_contents( $wp_config_file, $new_wp_config_file_contents, LOCK_EX );

        return ( $wp_config_file_updated === false ) ? false : $wp_config_file;
    }

    public static function clean(){

        $advanced_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';

        if(file_exists($advanced_cache_file)){
            wp_delete_file($advanced_cache_file);
            RapidLoad_Cache_Store::set_wp_cache_constant(false);
            self::clearDirectory(RAPIDLOAD_CACHE_DIR);
        }
    }

    public static function clearDirectory($dirPath) {
        if (!is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = scandir($dirPath);
        foreach ($files as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }

            $filePath = $dirPath . $file;
            if (is_dir($filePath)) {
                self::clearDirectory($filePath);
                @rmdir($filePath);
            } else {
                wp_delete_file($filePath);
            }
        }
    }

    public static function setup() {

        self::create_advanced_cache_file();
        self::set_wp_cache_constant();
    }

    public static function cache_iterator( $url, $args = array() ) {

        $cache = array(
            'index' => array(),
            'size'  => 0,
        );

        if ( ! is_string( $url ) || empty( $url ) ) {
            return $cache;
        }

        $url       = filter_var($url, FILTER_SANITIZE_URL);
        $cache_dir = self::get_cache_dir( $url );

        if ( ! is_dir( $cache_dir ) ) {
            return $cache;
        }

        $switched = false;
        if ( is_multisite() && ! ms_is_switched() ) {
            $blog_domain = (string) parse_url( $url, PHP_URL_HOST );
            $blog_path   = is_subdomain_install() ? '/' : RapidLoad_Cache::get_blog_path_from_url( $url );
            $blog_id     = get_blog_id_from_url( $blog_domain, $blog_path );

            if ( $blog_id !== 0 ) {
                $switched = RapidLoad_Cache::switch_to_blog( $blog_id, true );
            }
        }

        $args             = self::get_cache_iterator_args( $url, $args );
        $recursive        = ( $args['subpages'] === 1 || ! empty( $args['subpages']['include'] ) || isset( $args['subpages']['exclude'] ) );
        $filter           = ( $recursive && $args['subpages'] !== 1 ) ? $args['subpages'] : null;
        $cache_objects    = self::get_dir_objects( $cache_dir, $recursive, $filter );
        $cache_keys_regex = self::get_cache_keys_regex( $args['keys'] );

        foreach ( $cache_objects as $cache_object ) {
            if ( is_file( $cache_object ) ) {
                if ( $args['root'] && strpos( $cache_object, $args['root'] ) !== 0 ) {
                    // Skip to the next object because the file does not start with the provided root path.
                    continue;
                }

                $cache_object_name = basename( $cache_object );

                if ( $cache_keys_regex && ! preg_match( $cache_keys_regex, $cache_object_name ) ) {
                    // Skip to the next object because the file name does not match the provided cache keys.
                    continue;
                }

                if ( $args['expired'] && ! self::cache_expired( $cache_object ) ) {
                    // Skip to the next object because the file is not expired.
                    continue;
                }

                $cache_object_dir  = dirname( $cache_object );
                $cache_object_size = (int) @filesize( $cache_object );

                if ( $args['clear'] ) {

                    if ( ! wp_delete_file( $cache_object ) ) {
                        // Skip to the next object because the file deletion failed.
                        continue;
                    }

                    // The cache size is negative when cleared.
                    $cache_object_size = -$cache_object_size;

                    // Remove the containing directory if empty along with any of its empty parents.
                    self::rmdir( $cache_object_dir, true );
                }

                if ( strpos( $cache_object_name, 'index' ) === false ) {
                    // Skip to the next object because the file is not a cache version and no longer
                    // needs to be handled, such as a hidden file.
                    continue;
                }

                if ( ! isset( $cache['index'][ $cache_object_dir ]['url'] ) ) {
                    $cache['index'][ $cache_object_dir ]['url'] = self::get_cache_url( $cache_object_dir );
                    $cache['index'][ $cache_object_dir ]['id']  = url_to_postid( $cache['index'][ $cache_object_dir ]['url'] );
                }

                $cache['index'][ $cache_object_dir ]['versions'][ $cache_object_name ] = $cache_object_size;
                $cache['size'] += $cache_object_size;
            }
        }

        // Sort the cache index by forward slashes from the lowest to highest.
        uksort( $cache['index'], 'self::sort_dir_objects' );

        if ( $args['clear'] ) {
            self::fire_cache_cleared_hooks( $cache['index'], $args['hooks'] );
        }

        if ( $switched ) {
            RapidLoad_Cache::restore_current_blog( true );
        }

        return $cache;
    }

    private static function sort_dir_objects( $a, $b ) {

        $a = substr_count( $a, '/' );
        $b = substr_count( $b, '/' );

        if ( $a === $b ) {
            return 0;
        }

        return ( $a > $b ) ? 1 : -1;
    }

    private static function rmdir( $dir, $parents = false ) {

        $removed_dir = @rmdir( $dir );

        clearstatcache();

        if ( $removed_dir ) {
            $removed_dir = array( $dir => array() );

            if ( $parents ) {
                $parent_dir = dirname( $dir );

                while ( @rmdir( $parent_dir ) ) {
                    clearstatcache();
                    $removed_dir[ $parent_dir ] = array();
                    $parent_dir = dirname( $parent_dir );
                }
            }
        }

        return $removed_dir;
    }

    private static function fire_cache_cleared_hooks( $cache_cleared_index, $hooks ) {

        if ( empty( $cache_cleared_index ) || empty( $hooks ) ) {
            return;
        }

        if ( isset( $hooks['include'] ) ) {
            $hooks_to_fire = $hooks['include'];
        } else {
            $hooks_to_fire = array( 'rapidload_cache_complete_cache_cleared', 'rapidload_cache_site_cache_cleared', 'rapidload_cache_page_cache_cleared' );
        }

        if ( ! empty( $hooks['exclude'] ) ) {
            $hooks_to_fire = array_diff( $hooks_to_fire, $hooks['exclude'] );
        }

        if ( empty( $hooks_to_fire ) ) {
            return;
        }

        if ( in_array( 'rapidload_cache_page_cache_cleared', $hooks_to_fire, true ) ) {
            foreach ( $cache_cleared_index as $cache_cleared_dir => $cache_cleared_data ) {
                $page_cleared_url = $cache_cleared_data['url'];
                $page_cleared_id  = $cache_cleared_data['id'];

                do_action( 'rapidload_cache_page_cache_cleared', $page_cleared_url, $page_cleared_id, $cache_cleared_index );
                do_action( 'rapidload_cache_action_cache_by_url_cleared', $page_cleared_url ); // Deprecated in 1.6.0.
            }
        }

        if ( in_array( 'rapidload_cache_site_cache_cleared', $hooks_to_fire, true ) && empty( RapidLoad_Cache::get_cache_index() ) ) {
            $site_cleared_url = user_trailingslashit( home_url() );
            $site_cleared_id  = get_current_blog_id();

            do_action( 'rapidload_cache_site_cache_cleared', $site_cleared_url, $site_cleared_id, $cache_cleared_index );
        }

        if ( in_array( 'rapidload_complete_cache_cleared', $hooks_to_fire, true ) && ! is_dir( RAPIDLOAD_CACHE_DIR ) ) {

            do_action( 'rapidload_complete_cache_cleared' );
        }
    }

    private static function get_cache_url( $dir ) {

        if ( strpos( $dir, RAPIDLOAD_CACHE_DIR ) !== 0 ) {
            return '';
        }

        $cache_url = parse_url( home_url(), PHP_URL_SCHEME ) . '://' . str_replace( RAPIDLOAD_CACHE_DIR . '/', '', $dir );
        $cache_url = user_trailingslashit( $cache_url );

        return $cache_url;
    }

    public static function cache_expired( $cache_file ) {

        if ( RapidLoad_Cache_Engine::$settings['cache_expiry_time'] == 0 ) {
            return false;
        }

        $expires_seconds = 3600 * RapidLoad_Cache_Engine::$settings['cache_expiry_time'];

        if ( ( filemtime( $cache_file ) + $expires_seconds ) <= time() ) {
            return true;
        }

        return false;
    }

    private static function get_cache_keys_regex( $cache_keys ) {

        if ( ! is_array( $cache_keys ) ) {
            return false;
        }

        $cache_keys_regex = '#^';

        foreach ( $cache_keys as $filter_type => $filter_value ) {
            switch ( $filter_type ) {
                case 'include':
                    $lookahead = '?=';
                    break;
                case 'exclude':
                    $lookahead = '?!';
                    break;
                default:
                    continue 2; // Skip to the next filter value.
            }

            foreach ( $filter_value as $cache_key ) {
                $cache_keys_regex .= '(' . $lookahead . '.*' . preg_quote( $cache_key ) . ')';
            }
        }

        $cache_keys_regex .= '.+$#';

        return $cache_keys_regex;
    }

    private static function get_cache_iterator_args( $url = null, $args = array() ) {

        $default_args = array(
            'clear'    => 0,
            'expired'  => 0,
            'hooks'    => 0,
            'keys'     => 0,
            'root'     => '',
            'subpages' => 0,
        );

        if ( ! is_array( $args ) ) {
            $args_template = $args;
            $args = array(
                'clear' => 1,
                'hooks' => array( 'include' => 'rapidload_cache_page_cache_cleared' ),
            );

            switch ( $args_template ) {
                case 'pagination':
                    global $wp_rewrite;
                    $included_subpages[] = isset( $wp_rewrite->pagination_base ) ? $wp_rewrite->pagination_base : '';
                    $included_subpages[] = isset( $wp_rewrite->comments_pagination_base ) ? $wp_rewrite->comments_pagination_base . '-*' : '';
                    $args['subpages']['include'] = $included_subpages;
                    break;
                case 'subpages':
                    $args['subpages'] = 1;
                    break;
                default:
                    $args = array();
            }
        }

        $url_path = (string) parse_url( $url, PHP_URL_PATH );
        if ( substr( $url_path, -1, 1 ) === '*' ) {
            $args['root'] = RAPIDLOAD_CACHE_DIR . '/' . substr( (string) parse_url( $url, PHP_URL_HOST ) . $url_path, 0, -1 );
            $args['subpages']['include'] = basename( $url_path );
        }

        // Merge query string arguments into the parameter arguments and then the default arguments.
        wp_parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $query_string_args );
        $args = wp_parse_args( $query_string_args, $args );
        $args = wp_parse_args( $args, $default_args );
        $args = self::validate_cache_iterator_args( $args );

        return $args;
    }

    private static function validate_cache_iterator_args( $args ) {

        $validated_args = array();

        foreach ( $args as $arg_name => $arg_value ) {
            if ( $arg_name === 'root' ) {
                $validated_args[ $arg_name ] = (string) $arg_value;
            } elseif ( is_array( $arg_value ) ) {
                foreach ( $arg_value as $filter_type => $filter_value ) {
                    if ( is_string( $filter_value ) ) {
                        $filter_value = ( substr_count( $filter_value, '|' ) > 0 ) ? explode( '|', $filter_value ) : explode( ',', $filter_value );
                    } elseif ( ! is_array( $filter_value ) ) {
                        $filter_value = array(); // The type is not being converting to avoid unwanted values.
                    }

                    foreach ( $filter_value as $filter_value_key => &$filter_value_item ) {
                        $filter_value_item = trim( $filter_value_item, '/- ' );

                        if ( empty( $filter_value_item ) ) {
                            unset( $filter_value[ $filter_value_key ] );
                        }
                    }

                    if ( $filter_type !== 'include' || $filter_type !== 'exclude' ) {
                        unset( $arg_value[ $filter_type ] );

                        if ( $filter_type === 0 || $filter_type === 'i' ) {
                            $filter_type = 'include';
                        } elseif ( $filter_type === 1 || $filter_type === 'e' ) {
                            $filter_type = 'exclude';
                        }
                    }

                    $arg_value[ $filter_type ] = $filter_value;
                }

                $validated_args[ $arg_name ] = $arg_value;
            } else {
                $validated_args[ $arg_name ] = (int) $arg_value;
            }
        }

        return $validated_args;
    }

    private static function get_dir_objects( $dir, $recursive = false, $filter = null ) {

        $dir_objects = array();

        if ( ! is_dir( $dir ) ) {
            return $dir_objects;
        }

        $dir_object_names = scandir( $dir ); // The sorted order is alphabetical in ascending order.

        if ( is_array( $filter ) && empty( $filter['full_path'] ) ) {
            $filter['full_path'] = 1;

            foreach ( $filter as $filter_type => &$filter_value ) {
                if ( $filter_type === 'include' || $filter_type === 'exclude' ) {
                    foreach ( $filter_value as &$filter_object ) {
                        $filter_object = $dir . '/' . $filter_object;
                    }
                }
            }
        }

        foreach ( $dir_object_names as $dir_object_name ) {
            if ( $dir_object_name === '.' || $dir_object_name === '..' ) {
                continue; // Skip object because it is the current or parent folder link.
            }

            $dir_object = $dir . '/' . $dir_object_name;

            if ( is_dir( $dir_object ) ) {
                if ( ! empty( $filter['full_path'] ) && ! self::filter_dir_object( $dir_object, $filter ) ) {
                    continue; // Skip object because it is excluded.
                }

                if ( $recursive ) {
                    $dir_objects = array_merge( $dir_objects, self::get_dir_objects( $dir_object, $recursive, $filter ) );
                }
            }

            $dir_objects[] = $dir_object;
        }

        return $dir_objects;
    }

    private static function filter_dir_object( $dir_object, $filter ) {

        if ( isset( $filter['exclude'] ) ) {
            $match = in_array( $dir_object, $filter['exclude'], true );

            if ( $match ) {
                return false;
            }
        }

        if ( isset( $filter['include'] ) ) {
            $match = in_array( $dir_object, $filter['include'], true );

            if ( $match ) {
                return true;
            }
        }

        if ( ! isset( $match ) ) {
            return true;
        }

        ksort( $filter ); // Sort the keys in alphabetical order to check for an exclusion first.

        if ( is_dir( $dir_object ) ) {
            $dir_object = $dir_object . '/'; // Append a trailing slash to prevent a false match.
        }

        foreach ( $filter as $filter_type => $filter_value ) {
            if ( $filter_type !== 'include' && $filter_type !== 'exclude' ) {
                continue;
            }

            foreach ( $filter_value as $filter_object ) {
                // If a trailing asterisk exists remove it to allow a wildcard match.
                if ( substr( $filter_object, -1, 1 ) === '*' ) {
                    $filter_object = substr( $filter_object, 0, -1 );
                    // Otherwise, maybe append a trailing slash to force a strict match.
                } elseif ( is_dir( $dir_object ) ) {
                    $filter_object = $filter_object . '/';
                }

                if ( str_replace( $filter_object, '', $dir_object ) !== $dir_object ) {
                    switch ( $filter_type ) {
                        case 'include':
                            return true; // Past inclusion or present wildcard inclusion.
                        case 'exclude':
                            return false; // Present wildcard exclusion.
                    }
                }

                if ( strpos( $filter_object, $dir_object ) === 0 && $filter_type === 'include' ) {
                    return true; // Future strict or wildcard inclusion.
                }
            }
        }

        if ( isset( $filter['include'] ) ) {
            return false; // Match not found.
        }

        return true;
    }

    public static function get_cache_dir( $url = null ) {

        if(!RapidLoad_Cache_Engine::$request_headers){
            return RAPIDLOAD_CACHE_DIR;
        }

        if ( empty ( $url ) && isset( $_SERVER['REQUEST_URI'] ) ) {
            $url = 'http://' . RapidLoad_Cache_Engine::$request_headers['Host'] . RapidLoad_Cache_Engine::sanitize_server_input( $_SERVER['REQUEST_URI'], false );
        }else{
            $url = site_url();
        }

        $url = filter_var($url, FILTER_SANITIZE_URL);

        $url_host = parse_url( $url, PHP_URL_HOST );
        if ( ! is_string( $url_host ) ) {
            return RAPIDLOAD_CACHE_DIR;
        }

        $url_path = parse_url( $url, PHP_URL_PATH );
        if ( ! is_string( $url_path ) ) {
            $url_path = '';
        } elseif ( substr( $url_path, -1, 1 ) === '*' ) {
            $url_path = dirname( $url_path );
        }

        $cache_dir = sprintf(
            '%s/%s%s',
            RAPIDLOAD_CACHE_DIR,
            strtolower( $url_host ),
            $url_path
        );

        $cache_dir = rtrim( $cache_dir, '/\\' );

        return $cache_dir;
    }

    private static function get_settings_file( $fallback = false ) {

        $settings_file = sprintf(
            '%s/%s',
            RAPIDLOAD_SETTINGS_DIR,
            self::get_settings_file_name( $fallback )
        );

        return $settings_file;
    }

    private static function get_settings_file_name( $fallback = false, $skip_blog_path = false ) {

        $settings_file_name = '';

        if ( function_exists( 'home_url' ) ) {
            $settings_file_name = parse_url( home_url(), PHP_URL_HOST );

            if ( is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL ) {
                $blog_path = RapidLoad_Cache::get_blog_path();
                $settings_file_name .= ( ! empty( $blog_path ) ) ? '.' . trim( $blog_path, '/' ) : '';
            }

            $settings_file_name .= '.php';
        } elseif ( is_dir( RAPIDLOAD_SETTINGS_DIR ) ) {
            if ( $fallback ) {
                $settings_files      = array_map( 'basename', self::get_dir_objects( RAPIDLOAD_SETTINGS_DIR ) );
                $settings_file_regex = '/\.php$/';

                if ( is_multisite() ) {
                    $settings_file_regex = '/^' . strtolower( RapidLoad_Cache_Engine::$request_headers['Host'] );
                    $settings_file_regex = str_replace( '.', '\.', $settings_file_regex );

                    if ( defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL && ! $skip_blog_path ) {
                        $url_path = trim( parse_url( RapidLoad_Cache_Engine::sanitize_server_input( $_SERVER['REQUEST_URI'], false ), PHP_URL_PATH ), '/' );

                        if ( ! empty( $url_path ) ) {
                            $url_path_regex = str_replace( '/', '|', $url_path );
                            $url_path_regex = '\.(' . $url_path_regex . ')';
                            $settings_file_regex .= $url_path_regex;
                        }
                    }

                    $settings_file_regex .= '\.php$/';
                }

                $filtered_settings_files = preg_grep( $settings_file_regex, $settings_files );

                if ( ! empty( $filtered_settings_files ) ) {
                    $settings_file_name = current( $filtered_settings_files );
                } elseif ( is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL && ! $skip_blog_path ) {
                    $fallback = true;
                    $skip_blog_path = true;
                    $settings_file_name = self::get_settings_file_name( $fallback, $skip_blog_path );
                }
            } else {
                $settings_file_name = strtolower( RapidLoad_Cache_Engine::$request_headers['Host'] );

                if ( is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL && ! $skip_blog_path ) {
                    $url_path = parse_url( RapidLoad_Cache_Engine::sanitize_server_input( $_SERVER['REQUEST_URI'], false ), PHP_URL_PATH );
                    $url_path_pieces = explode( '/', $url_path, 3 );
                    $blog_path = $url_path_pieces[1];

                    if ( ! empty( $blog_path ) ) {
                        $settings_file_name .= '.' . $blog_path;
                    }

                    $settings_file_name .= '.php';

                    // Check if the main site in a subdirectory network.
                    if ( ! is_file( RAPIDLOAD_SETTINGS_DIR . '/' . $settings_file_name ) ) {
                        $fallback = false;
                        $skip_blog_path = true;
                        $settings_file_name = self::get_settings_file_name( $fallback, $skip_blog_path );
                    }
                }

                $settings_file_name .= ( strpos( $settings_file_name, '.php' ) === false ) ? '.php' : '';
            }
        }

        return $settings_file_name;
    }

    private static function get_current_time() {

        $current_time = current_time( 'D, d M Y H:i:s', true ) . ' GMT';

        return $current_time;
    }

    public static function create_settings_file( $settings ) {

        if ( ! is_array( $settings ) || ! function_exists( 'home_url' ) ) {
            return false;
        }

        $new_settings_file = self::get_settings_file();

        $new_settings_file_contents  = '<?php' . PHP_EOL;
        $new_settings_file_contents .= '/**' . PHP_EOL;
        $new_settings_file_contents .= ' * The settings file for RapidLoad.' . PHP_EOL;
        $new_settings_file_contents .= ' *' . PHP_EOL;
        $new_settings_file_contents .= ' * This file is automatically created, mirroring the plugin settings saved in the' . PHP_EOL;
        $new_settings_file_contents .= ' * database. It is used to cache and deliver pages.' . PHP_EOL;
        $new_settings_file_contents .= ' *' . PHP_EOL;
        $new_settings_file_contents .= ' * @site  ' . home_url() . PHP_EOL;
        $new_settings_file_contents .= ' * @time  ' . self::get_current_time() . PHP_EOL;
        $new_settings_file_contents .= ' */' . PHP_EOL;
        $new_settings_file_contents .= PHP_EOL;
        $new_settings_file_contents .= 'return ' . var_export( $settings, true ) . ';';

        if ( ! self::mkdir_p( dirname( $new_settings_file ) ) ) {
            return false;
        }

        $new_settings_file_created = file_put_contents( $new_settings_file, $new_settings_file_contents, LOCK_EX );

        return ( $new_settings_file_created === false ) ? false : $new_settings_file;
    }

    public static function mkdir_p( $dir ) {

        $mode_octal  = (int) apply_filters( 'rapidload_mkdir_mode', 0755 );
        $mode_string = decoct( $mode_octal ); // Get the last three digits (e.g. '755').
        $parent_dir  = dirname( $dir );
        $fs          = self::get_filesystem();

        if ( $fs->is_dir( $dir ) && $fs->getchmod( $dir ) === $mode_string && $fs->getchmod( $parent_dir ) === $mode_string ) {
            return true;
        }

        if ( strpos( $dir, '../' ) !== false ) {
            return false;
        }

        if ( ! wp_mkdir_p( $dir ) ) {
            return false;
        }

        if ( $fs->getchmod( $parent_dir ) !== $mode_string ) {
            return $fs->chmod( $parent_dir, $mode_octal, true );
        }

        if ( $fs->getchmod( $dir ) !== $mode_string ) {
            return $fs->chmod( $dir, $mode_octal );
        }

        return true;
    }

    public static function get_filesystem() {

        global $wp_filesystem;

        if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
            return $wp_filesystem;
        }

        try {
            require_once ABSPATH . 'wp-admin/includes/file.php';

            $filesystem = WP_Filesystem();

            if ( $filesystem === null ) {
                throw new \RuntimeException( 'The provided filesystem method is unavailable.' );
            }

            if ( $filesystem === false ) {
                if ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
                    throw new \RuntimeException(
                        $wp_filesystem->errors->get_error_message(),
                        is_numeric( $wp_filesystem->errors->get_error_code() ) ? (int) $wp_filesystem->errors->get_error_code() : 0
                    );
                }

                throw new \RuntimeException( 'Unspecified failure.' );
            }

            if ( ! is_object( $wp_filesystem ) || ! $wp_filesystem instanceof WP_Filesystem_Base ) {
                throw new \RuntimeException( '$wp_filesystem is not an instance of WP_Filesystem_Base.' );
            }
        } catch ( \Exception $e ) {
            throw new \RuntimeException(
                sprintf( 'There was an error initializing the WP_Filesystem class: %1$s', $e->getMessage() ),
                $e->getCode(),
                $e
            );
        }

        return $wp_filesystem;
    }

    public static function get_settings( $update = true ) {

        $settings      = array();
        $settings_file = self::get_settings_file();

        if ( is_file( $settings_file ) ) {
            $settings = include $settings_file;
        } else {
            $fallback      = true;
            $settings_file = self::get_settings_file( $fallback );

            if ( is_file( $settings_file ) ) {
                $settings = include $settings_file;
            }
        }

        $outdated_settings = ( ! empty( $settings ) && ( ! defined( 'UUCSS_VERSION' ) || ! isset( $settings['version'] ) || $settings['version'] !== UUCSS_VERSION ) );

        if ( $outdated_settings ) {
            $settings = array();
        }

        if ( empty( $settings ) && class_exists( 'RapidLoad_Cache' ) ) {
            if ( $outdated_settings ) {
                if ( $update ) {
                    RapidLoad_Cache::update();
                }
            } else {
                $_settings = RapidLoad_Cache::get_settings();
                $settings_file = self::get_settings_file();

                if ( is_file( $settings_file ) ) {
                    $settings = include $settings_file;
                } else {
                    $settings_file = self::create_settings_file( $_settings );

                    if ( $settings_file !== false ) {
                        $settings = include $settings_file;
                    }
                }
            }
        }

        return $settings;
    }

    public static function get_cache_file($url = null) {

        if ( ! empty( self::$cache_file ) ) {
            return self::$cache_file;
        }

        self::$cache_file = sprintf(
            '%s/%s',
            self::get_cache_dir($url),
            self::get_cache_file_name()
        );

        return self::$cache_file;
    }

    private static function get_cache_file_name() {

        $cache_keys      = self::get_cache_keys();
        $cache_file_name = $cache_keys['scheme'] . 'index' . $cache_keys['preview'] . $cache_keys['device'] . $cache_keys['webp'] . '.html' . $cache_keys['compression'];

        return $cache_file_name;
    }

    private static function get_cache_keys() {

        $cache_keys = array(
            'scheme'      => 'http-',
            'device'      => '',
            'webp'        => '',
            'compression' => '',
            'preview' => '',
        );

        if ( isset( $_SERVER['HTTPS'] ) && ( strtolower( $_SERVER['HTTPS'] ) === 'on' || $_SERVER['HTTPS'] == '1' ) ) {
            $cache_keys['scheme'] = 'https-';
        } elseif ( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == '443' ) {
            $cache_keys['scheme'] = 'https-';
        } elseif ( isset(RapidLoad_Cache_Engine::$request_headers['X-Forwarded-Proto']) && RapidLoad_Cache_Engine::$request_headers['X-Forwarded-Proto'] === 'https'
            || isset(RapidLoad_Cache_Engine::$request_headers['X-Forwarded-Scheme']) && RapidLoad_Cache_Engine::$request_headers['X-Forwarded-Scheme'] === 'https'
        ) {
            $cache_keys['scheme'] = 'https-';
        }

        if ( isset(RapidLoad_Cache_Engine::$settings['mobile_cache']) && RapidLoad_Cache_Engine::$settings['mobile_cache'] ) {
            if ( strpos( RapidLoad_Cache_Engine::$request_headers['User-Agent'], 'Mobile' ) !== false
                || strpos( RapidLoad_Cache_Engine::$request_headers['User-Agent'], 'Android' ) !== false
                || strpos( RapidLoad_Cache_Engine::$request_headers['User-Agent'], 'Silk/' ) !== false
                || strpos( RapidLoad_Cache_Engine::$request_headers['User-Agent'], 'Kindle' ) !== false
                || strpos( RapidLoad_Cache_Engine::$request_headers['User-Agent'], 'BlackBerry' ) !== false
                || strpos( RapidLoad_Cache_Engine::$request_headers['User-Agent'], 'Opera Mini' ) !== false
                || strpos( RapidLoad_Cache_Engine::$request_headers['User-Agent'], 'Opera Mobi' ) !== false
            ) {
                $cache_keys['device'] = '-mobile';
            }
        }

        if ( isset(RapidLoad_Cache_Engine::$settings['convert_image_urls_to_webp']) && RapidLoad_Cache_Engine::$settings['convert_image_urls_to_webp'] ) {
            if ( strpos( RapidLoad_Cache_Engine::$request_headers['Accept'], 'image/webp' ) !== false ) {
                $cache_keys['webp'] = '-webp';
            }
        }

        if ( isset(RapidLoad_Cache_Engine::$settings['compress_cache']) && RapidLoad_Cache_Engine::$settings['compress_cache'] ) {
            if ( function_exists( 'brotli_compress' )
                && $cache_keys['scheme'] === 'https-'
                && strpos( RapidLoad_Cache_Engine::$request_headers['Accept-Encoding'], 'br' ) !== false
            ) {
                $cache_keys['compression'] = '.br';
            } elseif ( strpos( RapidLoad_Cache_Engine::$request_headers['Accept-Encoding'], 'gzip' ) !== false ) {
                $cache_keys['compression'] = '.gz';
            }
        }

        if(isset($_REQUEST['rapidload_preview'])){
            //$cache_keys['preview'] = '-preview';
        }

        return $cache_keys;
    }

    public static function cache_exists( $cache_file ) {

        return is_readable( $cache_file );
    }

    public static function cache_page( $page_contents ) {

        $page_contents = (string) apply_filters( 'rapidload_page_contents_before_store', $page_contents );
        $page_contents = (string) apply_filters_deprecated( 'rapidload_before_store', array( $page_contents ), UUCSS_VERSION, 'rapidload_page_contents_before_store' );

        self::create_cache_file( $page_contents );
    }

    private static function create_cache_file( $page_contents ) {

        if ( ! is_string( $page_contents ) || strlen( $page_contents ) === 0 ) {
            return;
        }

        $new_cache_file      = self::get_cache_file();
        $new_cache_file_dir  = dirname( $new_cache_file );
        $new_cache_file_name = basename( $new_cache_file );

        if ( RapidLoad_Cache_Engine::$settings['minify_html'] && apply_filters('rapidload/page-cache/minify/enabled', true)) {
            $page_contents = self::minify_html( $page_contents );
        }

        $page_contents = $page_contents . self::get_cache_signature( $new_cache_file_name );

        if ( strpos( $new_cache_file_name, 'webp' ) !== false ) {
            $page_contents = self::converter( $page_contents );
        }

        if ( ! RapidLoad_Cache_Engine::is_cacheable( $page_contents ) ) {
            return; // Filter, HTML minification, or WebP conversion failed.
        }

        switch ( substr( $new_cache_file_name, -2, 2 ) ) {
            case 'br':
                $page_contents = brotli_compress( $page_contents );
                break;
            case 'gz':
                $page_contents = gzencode( $page_contents, 9 );
                break;
        }

        if ( $page_contents === false ) {
            return; // Compression failed.
        }

        if ( ! self::mkdir_p( $new_cache_file_dir ) ) {

            return;
        }

        $new_cache_file_created = file_put_contents( $new_cache_file, $page_contents );

        if ( $new_cache_file_created !== false ) {
            clearstatcache();
            $new_cache_file_stats = @stat( $new_cache_file_dir );
            $new_cache_file_perms = $new_cache_file_stats['mode'] & 0007777;
            $new_cache_file_perms = $new_cache_file_perms & 0000666;
            @chmod( $new_cache_file, $new_cache_file_perms );
            clearstatcache();

            $page_created_url = self::get_cache_url( $new_cache_file_dir );
            $page_created_id  = url_to_postid( $page_created_url );
            $cache_created_index[ $new_cache_file_dir ]['url'] = $page_created_url;
            $cache_created_index[ $new_cache_file_dir ]['id']  = $page_created_id;
            $cache_created_index[ $new_cache_file_dir ]['versions'][ $new_cache_file_name ] = $new_cache_file_created;

            do_action( 'rapidload_cache_page_cache_created', $page_created_url, $page_created_id, $cache_created_index );
        }
    }

    private static function minify_html( $html ) {

        if ( strlen( $html ) > 700000 ) {
            return $html;
        }

        $ignore_tags = (array) apply_filters( 'rapidload_minify_html_ignore_tags', array( 'textarea', 'pre', 'code' ) );
        $ignore_tags = (array) apply_filters_deprecated( 'cache_minify_ignore_tags', array( $ignore_tags ), UUCSS_VERSION, 'rapidload_minify_html_ignore_tags' );

        if ( ! RapidLoad_Cache_Engine::$settings['minify_inline_css_js'] ) {
            array_push( $ignore_tags, 'style', 'script' );
        }

        if ( ! $ignore_tags ) {
            return $html; // At least one HTML tag is required.
        }

        $ignore_tags_regex = implode( '|', $ignore_tags );

        // Remove HTML comments.
        $minified_html = preg_replace( '#<!--[^\[><].*?-->#s', '', $html );

        if ( RapidLoad_Cache_Engine::$settings['minify_inline_css_js'] ) {
            // Remove CSS and JavaScript comments.
            $minified_html = preg_replace(
                '#/\*(?!!)[\s\S]*?\*/|(?:^[ \t]*)//.*$|((?<!\()[ \t>;,{}[\]])//[^;\n]*$#m',
                '$1',
                $minified_html
            );
        }

        // Replace whitespaces of any size with a single space.
        $minified_html = preg_replace(
            '#(?>[^\S ]\s*|\s{2,})(?=[^<]*+(?:<(?!/?(?:' . $ignore_tags_regex . ')\b)[^<]*+)*+(?:<(?>' . $ignore_tags_regex . ')\b|\z))#ix',
            ' ',
            $minified_html
        );

        if ( strlen( $minified_html ) <= 1 ) {
            return $html; // HTML minification failed.
        }

        return $minified_html;
    }

    private static function get_cache_signature( $cache_file_name ) {

        $cache_signature = sprintf(
            '<!-- %s @ %s (%s) -->',
            'RapidLoad',
            self::get_current_time(),
            $cache_file_name
        );

        return $cache_signature;
    }

    private static function converter( $page_contents ) {

        $attributes       = (array) apply_filters( 'rapidload_convert_webp_attributes', array( 'src', 'srcset', 'data-[^=]+' ) );
        $attributes_regex = implode( '|', $attributes );

        if ( apply_filters( 'rapidload_convert_webp_ignore_query_strings', true ) ) {
            $image_urls_regex = '#(?:(?:(' . $attributes_regex . ')\s*=|(url)\()\s*[\'\"]?\s*)\K(?:[^\?\"\'\s>]+)(?:\.jpe?g|\.png)(?:\s\d+[wx][^\"\'>]*)?(?=\/?[\"\'\s\)>])(?=[^<{]*(?:\)[^<{]*\}|>))#i';
        } else {
            $image_urls_regex = '#(?:(?:(' . $attributes_regex . ')\s*=|(url)\()\s*[\'\"]?\s*)\K(?:[^\"\'\s>]+)(?:\.jpe?g|\.png)(?:\s\d+[wx][^\"\'>]*)?(?=\/?[\?\"\'\s\)>])(?=[^<{]*(?:\)[^<{]*\}|>))#i';
        }

        $converted_page_contents = (string) apply_filters( 'rapidload_page_contents_after_webp_conversion', preg_replace_callback( $image_urls_regex, 'self::convert_webp', $page_contents ) );
        $converted_page_contents = (string) apply_filters_deprecated( 'rapdiload_disk_webp_converted_data', array( $converted_page_contents ), UUCSS_VERSION, 'radpiload_page_contents_after_webp_conversion' );

        return $converted_page_contents;
    }

    private static function convert_webp( $matches ) {

        $full_match            = $matches[0];
        $image_extension_regex = '/(\.jpe?g|\.png)/i';
        $image_found           = preg_match( $image_extension_regex, $full_match );

        if ( ! $image_found ) {
            return $full_match;
        }

        $image_urls = explode( ',', $full_match );

        foreach ( $image_urls as &$image_url ) {
            $image_url       = trim( $image_url, ' ' );
            $image_url_webp  = preg_replace( $image_extension_regex, '$1.webp', $image_url ); // Append the .webp extension.
            $image_path_webp = self::get_image_path( $image_url_webp );

            if ( is_file( $image_path_webp ) ) {
                $image_url = $image_url_webp;
            } else {
                $image_url_webp  = preg_replace( $image_extension_regex, '', $image_url_webp ); // Remove the default extension.
                $image_path_webp = self::get_image_path( $image_url_webp );

                if ( is_file( $image_path_webp ) ) {
                    $image_url = $image_url_webp;
                }
            }
        }

        $conversion = implode( ', ', $image_urls );

        return $conversion;
    }

    private static function get_image_path( $image_url ) {

        // In case there is an intrinsic width or density descriptor.
        $image_pieces = explode( ' ', $image_url );
        $image_url    = $image_pieces[0];

        // In case installation is in a subdirectory.
        $image_url_path   = ltrim( parse_url( $image_url, PHP_URL_PATH ), '/' );
        $installation_dir = ltrim( parse_url( site_url( '/' ), PHP_URL_PATH ), '/' );
        $image_path       = str_replace( $installation_dir, '', ABSPATH ) . $image_url_path;

        return $image_path;
    }
}