<?php

class RapidLoad_Font
{
    use RapidLoad_Utils;

    public $options = [];
    public $base;
    public $file_system;
    public static $base_dir;
    public static $base_url;

    public function __construct()
    {
        $this->options = RapidLoad_Base::get_merged_options();

        if(!isset($this->options['uucss_enable_font_optimization']) || $this->options['uucss_enable_font_optimization'] !== "1"){
            return;
        }

        $this->file_system = new RapidLoad_FileSystem();

        if( ! $this->initFileSystem() ){
            return;
        }

        add_action('rapidload/job/handle', [$this, 'optimize_font'], 30, 2);

        add_filter('rapidload/cpcss/minify', [$this, 'add_display_swap_to_inline_styles']);

        add_filter('uucss/excluded-files', [$this, 'exclude_google_font_uucss']);

        add_filter('rapidload/cache_file_creating/css', [$this, 'add_display_swap_to_inline_styles'], 10 , 1);

        add_action('rapidload/vanish', [ $this, 'vanish' ]);

        add_action('rapidload/vanish/font', [ $this, 'vanish' ]);

        add_filter('rapidload/webfont/handle', [$this, 'handle_web_font_js'], 10, 2);

        add_action('rapidload/admin-bar-actions', [$this, 'add_admin_clear_action']);

        add_action('rapidload/cdn/validated', [$this, 'update_cdn_url_in_cached_files']);
    }

    public function update_cdn_url_in_cached_files($args) {
        RapidLoad_CDN::update_cdn_url_in_cached_files(self::$base_dir, $args);
    }

    public function add_admin_clear_action($wp_admin_bar){
        $wp_admin_bar->add_node( array(
            'id'    => 'rapidload-clear-font-cache',
            'title' => '<span class="ab-label">' . __( 'Clear Font Optimizations', 'unusedcss' ) . '</span>',
            //'href'  => admin_url( 'admin.php?page=rapidload&action=rapidload_purge_all' ),
            'href'   => wp_nonce_url( add_query_arg( array(
                '_action' => 'rapidload_purge_all',
                'job_type' => 'fonts',
                'clear' => true,
            ) ), 'uucss_nonce', 'nonce' ),
            'meta'  => array( 'class' => 'rapidload-clear-all', 'title' => 'RapidLoad will clear cached font files' ),
            'parent' => 'rapidload'
        ));
    }

    public function handle_web_font_js($handle, $link){

        $ids = isset($this->options['web_font_loader_ids']) ? explode(",", $this->options['web_font_loader_ids']) : [];

        return $this->array_search_partial($link->id, $ids);
    }

    public function array_search_partial($needle, $haystack){
        $results = array();
        foreach ($haystack as $value) {
            if (strpos($needle, $value) !== false) { $results[] = $value; }
        }
        if(!empty($results)){
            return true;
        }
        return false;
    }

    public function vanish() {

        if ( $this->file_system->exists( self::$base_dir ) ){
            $this->file_system->delete( self::$base_dir, true );
        }

    }

    public function self_host_gstatic_fonts($css){

        $font_urls = self::get_font_urls($css);

        self::download_urls_in_parallel($font_urls);

        foreach ($font_urls as $font_url) {
            $cached_font_url = self::$base_url . '/' . basename($font_url);
            $css = str_replace($font_url, $cached_font_url, $css);
        }

        return $css;
    }

    public function exclude_google_font_uucss($args){

        $args[] = 'fonts.googleapis.com';

        return $args;
    }

    public function add_display_swap_to_inline_styles($content){
        return self::add_display_swap($content);
    }

    public static function add_display_swap($content){
        $content = preg_replace_callback('/@font-face\s*{[^}]*}/', function($matches) {
            $font_face_block = $matches[0];
            if (preg_match('/font-display\s*:/', $font_face_block)) {
                $font_face_block = preg_replace(
                    '/font-display\s*:\s*(auto|block|fallback|optional|swap)/',
                    'font-display:swap',
                    $font_face_block
                );
            } else {
                $font_face_block = preg_replace('/(@font-face\s*{)/', '$1font-display:swap;', $font_face_block);
            }
            return $font_face_block;
        }, $content);
        return $content;
    }

    public function initFileSystem() {

        $this->base = apply_filters('uucss/cache-base-dir', UUCSS_CACHE_CHILD_DIR) . 'font';

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
        self::$base_url = apply_filters('uucss/enqueue/cdn',untrailingslashit(self::get_wp_content_url($this->base)));

        if ( $this->file_system->exists( self::$base_dir ) ) {
            return true;
        }

        // make dir if not exists
        $created = RapidLoad_Cache_Store::mkdir_p( self::$base_dir );

        if (!$created || ! $this->file_system->is_writable( self::$base_dir ) || ! $this->file_system->is_readable( self::$base_dir ) ) {
            return false;
        }

        return true;
    }

    public function optimize_font($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_rapidload_font'] )){
            return false;
        }

        new RapidLoad_Font_Enqueue($job, $this);

    }

    public static function self_host_style_sheet($url, $file_path)
    {

        if (substr($url, 0, 2) === '//') {
            $url = 'https:' . $url;
        }

        $css_file_response = wp_remote_get($url, [
            'user-agent' =>
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36',
        ]);

        if (
            is_wp_error($css_file_response) ||
            wp_remote_retrieve_response_code($css_file_response) !== 200
        ) {
            return false;
        }

        $css = $css_file_response['body'];

        $font_urls = self::get_font_urls($css);

        self::download_urls_in_parallel($font_urls);

        foreach ($font_urls as $font_url) {
            $cached_font_url = self::$base_url . '/' . basename($font_url);
            $css = str_replace($font_url, $cached_font_url, $css);
        }

        $css = apply_filters('rapidload/cpcss/minify', $css, false);

        file_put_contents($file_path, $css);
    }

    private static function get_font_urls($css)
    {
        $regex = '/url\((https:\/\/fonts\.gstatic\.com\/.*?)\)/';
        preg_match_all($regex, $css, $matches);
        return array_unique($matches[1]);
    }

    public static function download_urls_in_parallel($urls)
    {

        $file_system = new RapidLoad_FileSystem();

        if(!$file_system->is_writable(self::$base_dir)){
            return;
        }
        
        $multi_handle = curl_multi_init();
        $file_pointers = [];
        $curl_handles = [];

        foreach ($urls as $key => $url) {
            $file = self::$base_dir . '/' . basename($url);
            $curl_handles[$key] = curl_init($url);
            $file_pointers[$key] = fopen($file, 'w');
            if ($file_pointers[$key] === false) {
                continue;
            }
            curl_setopt($curl_handles[$key], CURLOPT_FILE, $file_pointers[$key]);
            curl_setopt($curl_handles[$key], CURLOPT_HEADER, 0);
            curl_setopt($curl_handles[$key], CURLOPT_CONNECTTIMEOUT, 60);
            curl_multi_add_handle($multi_handle, $curl_handles[$key]);
        }

        do {
            curl_multi_exec($multi_handle, $running);
        } while ($running > 0);

        foreach ($urls as $key => $url) {
            curl_multi_remove_handle($multi_handle, $curl_handles[$key]);
            curl_close($curl_handles[$key]);
            fclose($file_pointers[$key]);
        }
        curl_multi_close($multi_handle);
    }
}