<?php

defined( 'ABSPATH' ) or die();

/**
 * Class UnusedCSS
 */
trait RapidLoad_Utils {

    private static $log_file_system = null;

	public function url_origin( $s, $use_forwarded_host = false ) {
		$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] === 'on' );
		$sp       = strtolower( $s['SERVER_PROTOCOL'] );
        $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
        $port     = $s['SERVER_PORT'];
        $port     = ( ( ! $ssl && ($port === '80' || $port === 80) ) || ( $ssl && ($port === '443' || $port === 443) ) ) ? '' : ':'.$port;
        $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
        $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
        return $protocol . '://' . $host;
    }

    public function get_current_url($post_id = null)
    {
	    if ( $post_id ) {
		    return get_permalink( get_post( $post_id ) );
	    }


	    global $wp;

	    if ( $wp ) {
            return urldecode(home_url(add_query_arg(array(), $wp->request)));
	    }

        if(isset($_SERVER['REQUEST_URI'])){
            return urldecode(home_url(add_query_arg(array(), sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])))));
        }

	    return null;
    }

    public static function get_defined_rules( $with_permalink = false){

        $rules = apply_filters('uucss/rules', []);

        $rules_with_permalink = [];
        foreach ($rules as $rule){
            if(!isset($rule['permalink']) && isset($rule['name']) && isset($rule['custom_post']) && $with_permalink){
                $posts = get_posts([
                    'posts_per_page' => 1,
                    'post_type' => $rule['name']
                ]);
                $rule['permalink'] = !empty($posts) ? get_permalink($posts[0]->ID) : trailingslashit(get_site_url());
            }else{
                $rule['permalink'] = trailingslashit(get_site_url());
            }
            array_push($rules_with_permalink, $rule);
        }
        usort($rules_with_permalink, function ($a, $b){
            if ($a['priority'] === $b['priority']) {
                return 0;
            }
            return ($a['priority'] < $b['priority']) ? -1 : 1;
        });
        return $rules_with_permalink;
    }

    public function is_cli(){

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return true;
        }

        return false;
        
    }

    public static function get_log_instance(){
	    if(!self::$log_file_system){
            self::$log_file_system = new RapidLoad_FileSystem();
        }
	    return self::$log_file_system;
    }

    private static function get_log_option(){
	    $option = RapidLoad_Base::fetch_options();
        return  isset($option['uucss_enable_debug']);
    }

    public static function log( $object, $callee = false ) {

	    if ( ! self::get_log_option() || (defined( 'UUCSS_DEBUG' ) && UUCSS_DEBUG === false)) {
		    return false;
	    }

	    $data = is_string( $object ) ? $object : json_encode( $object, JSON_PRETTY_PRINT );
	    //error_log( "[UUCSS_LOG] " . $data );

        $data = is_string( $object ) ? [ 'log' => $object] : $object;

        if(!isset($data['time'])){
            $data['time'] = time();
        }

        if(!isset($data['type'])){
            $data['type'] = 'general';
        }

        $data = json_encode($data);

        $log_instance = self::get_log_instance();

        if($log_instance->exists(UUCSS_LOG_DIR .'debug.log') && !empty($log_instance->get_contents(UUCSS_LOG_DIR .'debug.log'))){
            $data = ",\n" . $data;
        }

        $log_instance->put_contents(UUCSS_LOG_DIR .'debug.log', $data, FILE_APPEND);

	    if ( $callee ) {

		    error_log( "[UUCSS_LOG] " . json_encode( [
				    "file" => debug_backtrace()[1]['file'],
				    "function" => debug_backtrace()[1]['function'],
				    "class" => debug_backtrace()[1]['class'],
				    "args" => debug_backtrace()[1]['args'],
			    ], JSON_PRETTY_PRINT));

	    }

	    return $object;
    }

    public static function uucss_log($object){

	    if ( ! self::get_log_option() || (defined( 'UUCSS_DEBUG' ) && UUCSS_DEBUG === false)) {
		    return false;
	    }

	    $data = is_string( $object ) ? $object : json_encode( $object, JSON_PRETTY_PRINT );
	    error_log( "[UUCSS_LOG] " . $data );
    }

    public static function add_admin_notice($message, $type='error') {

        add_action('admin_notices', function () use ($message, $type) {

            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">
                    <p>' . esc_html($message) . '</p>
                 </div>';

        });

    }

    public static function add_advanced_admin_notice($notice) {

        if(!isset($notice)){
            return;
        }
        add_action('admin_notices', function () use ($notice) {

            if(!isset($notice['action'])){
                $notice['action'] = 'uucss-action';
            }

            if(!isset($notice['type'])){
                $notice['type'] = 'error';
            }

            if(!isset($notice['title'])){
                $notice['title'] = 'RapidLoad Power Up';
            }

            if(!isset($notice['message'])){
                $notice['message'] = null;
            }

            if(!isset($notice['actions'])){
                $notice['actions'] = [];
            }

	        if ( ! isset( $notice['main_action'] ) ) {
		        $notice['main_action'] = [];
	        }

	        ?>
            <div class="uucss-notice-action notice notice-action notice-action-<?php echo esc_attr($notice['action']); ?> notice-<?php echo esc_attr($notice['type']); ?>">
                <div class="notice-action-inner">
                    <div class="notice-icon">
                        <div class="logo-wrapper">
                            <img
                                    src="<?php echo esc_url(UUCSS_PLUGIN_URL . 'assets/images/logo-icon.svg') ?>" width="40"
                                    alt="RapidLoad logo">
                        </div>
                    </div>
                    <div class="notice-icon-content">
		                <?php if ( isset( $notice['title'] ) ) : ?>
                            <h2 class="uucss-notice-title"><?php echo esc_html($notice['title']) ?></h2>
				        <?php endif; ?>
                        <p>
					        <?php echo esc_html($notice['message']); ?>
                        </p>
                        <?php if(!empty($notice['actions'])): ?>
                            <p>
                                <?php foreach ($notice['actions'] as $key => $value) : ?>
                                    <a class="button button-primary" href="<?php echo esc_url($value)?>"><?php echo esc_html($key)?></a>
                                <?php endforeach; ?>
                            </p>
                        <?php endif;  ?>

                    </div>
                    <?php if(!empty($notice['main_action'])): ?>
                    <div class="notice-main-action">
                        <p>
                            <a class="button button-primary" href="<?php echo esc_url($notice['main_action']['value']) ?>"><?php echo esc_html($notice['main_action']['key'])?></a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php

        });

    }

    protected function encode($data)
    {
        return rtrim(md5($data));
    }

    function dirSize($directory) {
        $size = 0;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
            $size+=$file->getSize();
        }
        return $size;
    }


    function human_file_size($bytes, $decimals = 2) {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }


    protected function file_name($file, $hash_suffix = null){
    	$file_hash = $this->encode($file . json_encode($hash_suffix));

	    $file_name = explode( "?", basename( $file ) )[0];

	    $final_file_name = 'uucss-' . $file_hash;

	    if ( apply_filters( 'uucss/suffix-original-filename', true ) ) {
            $final_file_name .= '-' . $file_name;
	    }

	    if ( !$this->str_contains( $final_file_name, '.' ) ) {
		    $final_file_name .= '.css';
	    }

	    return $final_file_name;
    }

	function str_contains( $string, $find ) {

	    if(empty($find)){
	        return false;
        }

		if ( strpos( $string, $find ) !== false ) {
			return true;
		}

		return false;
	}

	function endsWith( $haystack, $needle ) {
		$length = strlen( $needle );
		if( !$length ) {
			return true;
		}
		return substr( $haystack, -$length ) === $needle;
	}

	function transform_url( $url ) {

        if (!$url) {
            return $url;
        }

		$url_parts = wp_parse_url( $url );

		$options = RapidLoad_Base::fetch_options();

		if ( ! isset( $url_parts['host'] ) || ! isset( $url_parts['scheme'] ) ) {
			return $url;
		}

        $path = ( isset( $url_parts['path'] ) ) ? $url_parts['path'] : '';

        $url  = $url_parts['scheme'] . '://' . $url_parts['host'] . $path;

        if(apply_filters('uucss/url/trailingslash', true)){
            $url = trailingslashit($url);
        }else{
            $url = rtrim($url,'/');
        }

		if ( isset( $options['uucss_query_string'] ) && $options['uucss_query_string'] === "1" && isset($url_parts['query']) && !empty($url_parts['query']) ) {
            $url .= "?" .$url_parts['query'];
		}

		return apply_filters('uucss/url', $url);
	}

	public static function activate_plugin( $plugin, $action = 'activate' ) {

		if ( strpos( $plugin, '/' ) ) {
			$plugin = str_replace( '\/', '%2F', $plugin );
		}

		$url = sprintf( admin_url( 'plugins.php?action=' . $action . '&plugin=%s&plugin_status=all&paged=1&s' ), $plugin );

		$_REQUEST['plugin'] = sanitize_text_field( $plugin );
		$url                = wp_nonce_url( $url, $action . '-plugin_' . $plugin );

		return $url;
	}

	public function is_uucss_file( $url = null ) {

		if ( ! $url ) {
			return false;
		}

		return preg_match( '/uucss\/uucss-[a-z0-9]{32}-/', $url );
	}

    public static function activation_url( $action, $to = 'options-general.php?page=rapidload' ) {

	    if ( ! defined( 'UUCSS_ACTIVATION_URL' ) ) {
		    define( 'UUCSS_ACTIVATION_URL', 'https://app.rapidload.io/activate' );
	    }

	    return UUCSS_ACTIVATION_URL . '?' . build_query( [
			    'action' => $action,
			    'nonce'  => wp_create_nonce( 'uucss_activation' ),
			    'site'   => trailingslashit(get_site_url()),
			    'back'   => admin_url( $to ),
			    'goto'   => UUCSS_ACTIVATION_URL,
                'utm_source' => RapidLoad_ThirdParty::plugin_exists('autoptimize') ? 'connect_autoptimize' : 'connect_rapidload',
		        'utm_medium' => 'plugin'
            ] );
    }

    public static function onboard_activation_url( $action, $to = 'options-general.php?page=rapidload-on-board' ) {

        if ( ! defined( 'UUCSS_ACTIVATION_URL' ) ) {
            define( 'UUCSS_ACTIVATION_URL', 'https://app.rapidload.io/activate' );
        }

        return UUCSS_ACTIVATION_URL . '?' . build_query( [
                'action' => $action,
                'nonce'  => wp_create_nonce( 'uucss_activation' ),
                'site'   => trailingslashit(get_site_url()),
                'back'   => admin_url( $to ),
                'goto'   => UUCSS_ACTIVATION_URL,
                'utm_source' => RapidLoad_ThirdParty::plugin_exists('autoptimize') ? 'connect_autoptimize' : 'connect_rapidload',
                'utm_medium' => 'plugin'
            ] );
    }

    public static function serialize($data){
        if(isset($data)){
            return serialize($data);
        }else{
            return null;
        }
    }

    public static function unserialize($data){
        if(isset($data)){
            return unserialize($data);
        }else{
            return null;
        }
    }

    public function is_file_excluded( $options, $file ) {

        $files = isset( $options['uucss_excluded_files'] ) && !empty($options['uucss_excluded_files']) ? explode( ',', $options['uucss_excluded_files'] ) : [];

        $files[] = "fonts.googleapis.com";
        $files[] = "admin/rapidload-preview/dist";

        $files = apply_filters('uucss/excluded-files', $files);

        foreach ( $files as $excluded_file ) {

            if($this->str_contains( trim($excluded_file), '*' ) && self::is_path_glob_matched($file, trim($excluded_file))){
                return true;
            }else if ( $this->str_contains( $file, trim($excluded_file) ) ) {
                return true;
            }

        }

        return false;
    }

    public static function is_url_glob_matched($path, $pattern, $ignoreCase = FALSE) {

        $expr = preg_replace_callback('/[\\\\^.?*+\\/]/', function($matches) {
            switch ($matches[0]) {
                case '*':
                    return '.*';
                case '?':
                    return '.';
                default:
                    return '\\'.$matches[0];
            }
        }, $pattern);

        $expr = '/'.$expr.'/';
        if ($ignoreCase) {
            $expr .= 'i';
        }

        return (bool) preg_match($expr, $path);

    }

    public static function is_path_glob_matched($path, $pattern, $ignoreCase = FALSE) {

        $expr = preg_replace_callback('/[\\\\^.[\\]|()?*+{}\\-\\/]/', function($matches) {
            switch ($matches[0]) {
                case '*':
                    return '.*';
                case '?':
                    return '.';
                default:
                    return '\\'.$matches[0];
            }
        }, $pattern);

        $expr = '/'.$expr.'/';
        if ($ignoreCase) {
            $expr .= 'i';
        }

        return (bool) preg_match($expr, $path);

    }

    public static function remove_white_space($str){

        return preg_replace('/[\n\s+]/', '', $str);
    }

    public function schedule_cron($hook_name, $args){
        return wp_schedule_single_event( time() + 5, $hook_name, $args);
    }

    public function size() {

	    $file_system = new RapidLoad_FileSystem();
        $uucss_size = 0;
        $cpcss_size = 0;

        if ( $file_system->exists( RapidLoad_UnusedCSS::$base_dir ) ) {
            $uucss_size = $this->dirSize( RapidLoad_UnusedCSS::$base_dir );
        }

        if ( $file_system->exists( RapidLoad_CriticalCSS::$base_dir ) ) {
            $cpcss_size = $this->dirSize( RapidLoad_CriticalCSS::$base_dir );
        }

        return $this->human_file_size( $uucss_size + $cpcss_size );
    }

    protected function is_doing_api_fetch(){

        $user_agent = $this->get_user_agent();

        return strpos( $user_agent, 'UnusedCSS_bot' ) !== false ||
            strpos( $user_agent, 'RapidLoad' ) !== false;
    }

    protected function is_mobile(){

        return $this->source_is_mobile();
    }

    function source_is_mobile() {
        if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $is_mobile = false;
        } elseif ( strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Mobile' ) !== false // Many mobile devices (all iPhone, iPad, etc.)
            || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Android' ) !== false
            || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Silk/' ) !== false
            || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Kindle' ) !== false
            || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'BlackBerry' ) !== false
            || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Opera Mini' ) !== false
            || strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'Opera Mobi' ) !== false ) {
            $is_mobile = true;
        } else {
            $is_mobile = false;
        }

        return $is_mobile;
    }

    protected function get_user_agent(){

        $headers    = [];

        if ( function_exists( 'getallheaders' ) ) {
            $headers = getallheaders();
        }

        if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
        }

        if ( isset( $headers['User-Agent'] ) ) {
            return sanitize_text_field( wp_unslash( $headers['User-Agent'] ) );
        }

        return '';
    }

    public function is_valid_url($url){
        $pattern = "/\b(?:https?|ftp):\/\/(?:www\.)?[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]{2,})+(?:\/[^\s]*)?\b/";
        $url = urldecode($url);
        return preg_match($pattern, $url);
    }

    public function is_url_allowed($url = null, $args = null)
    {

        if ( ! $url ) {
            return false;
        }

        if(!$this->is_valid_url($url)){
            return false;
        }

        // remove .css .js files from being analyzed
        if ( preg_match( '/cache\/autoptimize/', $url ) ) {
            return false;
        }

        if ( preg_match( '/cache\/rapidload/', $url ) ) {
            return false;
        }

        global $post;
        $_post = $post;

        if ( !$_post && isset( $args['post_id'] )) {
            $_post = get_post( $args['post_id'] );
        }

        if ( $_post ) {
            $page_options = RapidLoad_Base::get_page_options( $_post->ID );
            if ( isset( $page_options['exclude'] ) && $page_options['exclude'] === "on" ) {
                return false;
            }

        }

        $options = RapidLoad_Base::fetch_options();

        if ( isset( $options['uucss_excluded_links'] ) && ! empty( $options['uucss_excluded_links'] ) ) {

            if(is_string($options['uucss_excluded_links'])){
                $exploded = explode( ',', $options['uucss_excluded_links'] );
            }else{
                $exploded = $options['uucss_excluded_links'];
            }

            foreach ( $exploded as $pattern ) {

                if ( filter_var( $pattern, FILTER_VALIDATE_URL ) ) {

                    $pattern = wp_parse_url( $pattern );

                    $path = $pattern['path'];
                    $query = isset($pattern['query']) ? '?' . $pattern['query'] : '';

                    $pattern = $path . $query;

                }

                if(self::str_contains( $pattern, '*' ) && self::is_path_glob_matched(urldecode($url), $pattern)){
                    $this->log( 'skipped glob matched : ' . $url );
                    return false;
                }else if ( self::str_contains( urldecode($url), $pattern ) ) {
                    $this->log( 'skipped str contains: ' . $url );
                    return false;
                }

            }
        }

        $url_parts = wp_parse_url( $url );

        if(isset($url_parts['query']) && $this->str_contains($url_parts['query'], 'customize_changeset_uuid')){
            $this->log( 'skipped  query contains : ' . $url );
            return false;
        }

        if(!apply_filters('uucss/url/exclude', $url)){
            $this->log( 'skipped  url exclude : ' . $url );
            return false;
        }

        return true;
    }

    function is_regex_expression($string) {
        try {
            @preg_match($string, '');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function get_file_path_from_url($url)
    {
        $file_relative_path = wp_parse_url($url, PHP_URL_PATH);
        $site_path = wp_parse_url(site_url(), PHP_URL_PATH);
        $file_path = UUCSS_ABSPATH . preg_replace("$^$site_path$", '', $file_relative_path);
        return str_replace("//","/", $file_path);
    }

    public static function get_width_height($file_path){

        $file_path = urldecode($file_path);

        if (!is_file($file_path)) {
            return false;
        }

        if (pathinfo($file_path, PATHINFO_EXTENSION) === 'svg') {
            $xml = @simplexml_load_file($file_path);
            $attr = $xml->attributes();
            $viewbox = explode(' ', $attr->viewBox);
            $width =
                isset($attr->width) && preg_match('/\d+/', $attr->width, $value)
                    ? (int) $value[0]
                    : (count($viewbox) === 4
                    ? (int) $viewbox[2]
                    : null);
            $height =
                isset($attr->height) && preg_match('/\d+/', $attr->height, $value)
                    ? (int) $value[0]
                    : (count($viewbox) === 4
                    ? (int) $viewbox[3]
                    : null);
            if ($width && $height) {
                return ['width' => $width, 'height' => $height];
            }
        }

        // Get image size by checking the file
        list($width, $height) = getimagesize($file_path);

        if ($width && $height) {
            return ['width' => $width, 'height' => $height];
        }

    }

    public static function verify_nonce($nonce = 'uucss_nonce' ){

        if (defined('RAPIDLOAD_DEV_MODE')) {
            return true;
        }

        if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), $nonce ) || !current_user_can('manage_options')) {
            wp_send_json_error( 'RapidLoad - Malformed Request Detected, Contact Support.' );
        }
    }

    public static function get_wp_content_dir(){
        return apply_filters('rapidload/root-dir', WP_CONTENT_DIR);
    }

    public static function get_wp_content_url($path = ''){
        return apply_filters('rapidload/root-url', content_url($path));
    }

    public static function get_relative_url($fullUrl) {
        $parsedUrl = wp_parse_url($fullUrl);

        if ( strpos( $fullUrl, site_url() ) === false ) {
            return $fullUrl;
        }

        // Get the path of the URL
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

        // Get the query string of the URL
        $queryString = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';

        // Combine the path and query string to get the relative URL
        $relativeUrl = $path;
        if ($queryString !== '') {
            $relativeUrl .= '?' . $queryString;
        }

        return $relativeUrl;
    }

    static function is_wp_cli() {
        return defined('WP_CLI') && WP_CLI;
    }

    public static function get_files_in_dir($directory){

        $files = [];
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $files = array_merge($files,  self::get_files_in_dir($path));
            } else {
                $files[] = $path;
            }
        }

        return $files;

    }

    public function transformRegexToPaths($regex) {
        $pattern = trim($regex, '/');
        $paths = explode('|', $pattern);
        $paths = array_map(function($path) {
            $cleanedPath = rtrim(trim($path, '^$'), '\/?');
            $cleanedPath = str_replace(['\/', '\-'], ['/', '-'], $cleanedPath);
            return $cleanedPath;
        }, $paths);
        return $paths;
    }

    function transformPathsToRegex(array $paths) {
        $escapedPaths = array_map(function($path) {
            if (substr($path, 0, 1) !== '/') {
                $path = '/' . $path;
            }
            return '^' . preg_quote($path, '/') . '\/?$';
        }, $paths);
        $regexPattern = '/' . implode('|', $escapedPaths) . '/';
        return $regexPattern;
    }

    function is_serialized($string) {
        return ($string === serialize(false) || @unserialize($string) !== false);
    }

    public static function debug_log(...$objects)
    {
        if(defined('RAPIDLOAD_DEBUG_LOG') && RAPIDLOAD_DEBUG_LOG){
            foreach ($objects as $object) {
                if (gettype($object) === "string") {
                    error_log("RapidLoad Log:  " . $object);
                } else {
                    error_log("RapidLoad Log:  " . json_encode($object, JSON_PRETTY_PRINT));
                }
            }
        }
    }

    public static function log_user_agent() {
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REQUEST_URI']) && strpos(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), '.') === false) {
            $user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
            self::debug_log("Request URI: " . sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . " - " . "User Agent: " . $user_agent);
        } else {
            if (isset($_SERVER['REQUEST_URI'])) {
                self::debug_log("Request URI: " . sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ));
            }
            self::debug_log("User Agent: Not available or not an HTML request");
        }
    }

    public static function create_nonce($nonce_name)
    {
        if(current_user_can('manage_options')){
            return wp_create_nonce( $nonce_name );
        }
        return '';
    }

     public static function get_active_plugins() {
        $plugins = (array) get_option('active_plugins', []);
        $plugin_details = [];

        foreach ($plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_details[] = [
                'name' => $plugin_data['Name'],
                'source' => $plugin_data['PluginURI'] ? $plugin_data['PluginURI'] : 'Unknown'
            ];
        }

        if (is_multisite()) {
            $sitewide_plugins = array_keys((array) get_site_option('active_sitewide_plugins', []));
            foreach ($sitewide_plugins as $plugin) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $plugin_details[] = [
                    'name' => $plugin_data['Name'],
                    'source' => $plugin_data['PluginURI'] ? $plugin_data['PluginURI'] : 'Unknown'
                ];
            }
        }

        return array_column($plugin_details, 'name');
    }

    public function get_cron_spawn() {

        $doing_wp_cron = sprintf( '%.22F', microtime( true ) );

        $cron_request_array = array(
            'url'  => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ),
            'key'  => $doing_wp_cron,
            'args' => array(
                'timeout'   => 3,
                'blocking'  => true,
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
                'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
            ),
        );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
        $cron_request = apply_filters( 'cron_request', $cron_request_array );

        # Enforce a blocking request in case something that's hooked onto the 'cron_request' filter sets it to false
        $cron_request['args']['blocking'] = true;

        $result = wp_remote_post( $cron_request['url'], $cron_request['args'] );

        return $result;
    }
    
}