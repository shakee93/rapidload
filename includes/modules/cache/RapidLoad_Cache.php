<?php

class RapidLoad_Cache
{
    use RapidLoad_Utils;

    public static $options = [];

    public static function init() {

        new self();
    }

    public function __construct()
    {
        self::$options = RapidLoad_Base::fetch_options();

        add_action( 'uucss/cached', [$this, 'clear_cache'], 10, 2 );

        add_action( 'uucss/cache_cleared', [$this, 'clear_cache'], 10, 2 );

        $cache_module_enabled = RapidLoad_Base::get_option('rapidload_module_cache');

        if(isset(self::$options['uucss_disable_wp_emoji']) && self::$options['uucss_disable_wp_emoji'] === "1"){
            $this->disable_wp_emojis();
        }

        if(!isset($cache_module_enabled) || $cache_module_enabled !== "1" ){
            return;
        }

        RapidLoad_Cache_Engine::start();

        add_filter('uucss/enqueue/content/update', [$this, 'cache_page'], 100);

        add_action('rapidload/cdn/validated', [$this, 'clear_cache_on_cdn_url_validate']);

        add_filter('rapidload/active-module/options', [$this, 'update_module_options']);

        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_items' ), 90 );

        add_action( 'wp_initialize_site', array( __CLASS__, 'install_later' ) );

        add_action( 'wp_uninitialize_site', array( __CLASS__, 'uninstall_later' ) );

        add_filter('uucss/notifications', [$this, 'add_notification'], 10 , 1);

        add_filter('uucss/third-party/plugins', function ($plugins){
            $plugins[] = [
                'category' => 'cache',
                'plugin' => 'rapidload'
            ];
            return $plugins;
        }, 10, 1 );

        add_action( 'upgrader_process_complete', array( __CLASS__, 'on_upgrade' ), 10, 2 );
        add_action( 'save_post', array( __CLASS__, 'on_save_trash_post' ) );
        add_action( 'pre_post_update', array( __CLASS__, 'on_pre_post_update' ), 10, 2 );
        add_action( 'wp_trash_post', array( __CLASS__, 'on_save_trash_post' ) );
        add_action( 'comment_post', array( __CLASS__, 'on_comment_post' ), 99, 2 );
        add_action( 'edit_comment', array( __CLASS__, 'on_edit_comment' ), 10, 2 );
        add_action( 'transition_comment_status', array( __CLASS__, 'on_transition_comment_status' ), 10, 3 );
        add_action( 'saved_term', array( __CLASS__, 'on_saved_delete_term' ), 10, 3 );
        add_action( 'edit_terms', array( __CLASS__, 'on_edit_terms' ), 10, 2 );
        add_action( 'delete_term', array( __CLASS__, 'on_saved_delete_term' ), 10, 3 );
        add_action( 'user_register', array( __CLASS__, 'on_register_update_delete_user' ) );
        add_action( 'profile_update', array( __CLASS__, 'on_register_update_delete_user' ) );
        add_action( 'delete_user', array( __CLASS__, 'on_register_update_delete_user' ) );
        add_action( 'deleted_user', array( __CLASS__, 'on_deleted_user' ), 10, 2 );

        add_action( 'rapidload_cache_clear_complete_cache', array( __CLASS__, 'clear_complete_cache' ) );
        add_action( 'rapidload_cache_clear_site_cache', array( __CLASS__, 'clear_site_cache' ) );
        add_action( 'rapidload_cache_clear_expired_cache', array( __CLASS__, 'clear_expired_cache' ) );
        add_action( 'rapidload_cache_clear_page_cache_by_post', array( __CLASS__, 'clear_page_cache_by_post' ) );
        add_action( 'rapidload_cache_clear_page_cache_by_url', array( __CLASS__, 'clear_page_cache_by_url' ) );

        add_action( 'rapidload_cache_page_cache_created', array( __CLASS__, 'on_cache_created_cleared' ), 10, 3 );
        add_action( 'rapidload_cache_site_cache_cleared', array( __CLASS__, 'on_cache_created_cleared' ), 10, 3 );
        add_action( 'rapidload_cache_page_cache_cleared', array( __CLASS__, 'on_cache_created_cleared' ), 10, 3 );

        add_action('init', function (){
            self::process_clear_cache_request();
        });

        //$this->display_admin_notice_for_directory_permission_issue();
    }

    public function disable_wp_emojis(){
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');

        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        add_filter('tiny_mce_plugins', function($plugins) {
            return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
        });
    }

    public function clear_cache_on_cdn_url_validate(){
        RapidLoad_Cache_Store::clearDirectory(RAPIDLOAD_CACHE_DIR);
    }

    public function display_admin_notice_for_directory_permission_issue(){

        $cache_dir = dirname(RapidLoad_Cache_Store::get_cache_dir(site_url()));

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
        
        global $wp_filesystem;

        if(!isset($wp_filesystem) || !$wp_filesystem->is_writable($cache_dir)){
            $message = 'writing permission error for the path : ' . $cache_dir;
            $type = "error";

            add_action('admin_notices', function () use ($message, $type) {
                echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible rapidload-cache-notice">
                    <p>' . esc_html($message) . '</p>
                 </div>';
            });
        }
    }

    public function add_notification($notifications)
    {
        if(defined('WP_CACHE') && !WP_CACHE){
            $notifications[] = [
                "title" => "WP_CACHE Constant",
                "message" => "Please set the value to true of the WP_CACHE constant in WP-Config.php file",
                "type" => "error"
            ];
        }

        return $notifications;
    }

    public static function clear_expired_cache( $site = null ) {

        $args['expired'] = 1;
        $args['hooks']['include'] = 'rapidload_cache_page_cache_cleared';

        self::clear_page_cache_by_site( $site, $args );
    }

    public static function on_deleted_user( $user_id, $reassign ) {

        if ( $reassign ) {
            self::clear_cache_on_user_save( $reassign );
        }
    }

    public static function clear_cache_on_user_save( $user ) {

        if ( RapidLoad_Cache_Engine::$settings['clear_site_cache_on_saved_user'] ) {
            self::clear_site_cache();
        } else {
            self::clear_user_cache( $user );
        }
    }

    public static function clear_page_cache_by_user( $user, $args = array() ) {

        if ( is_numeric( $user ) ) {
            $user = get_userdata( $user );
        }

        if ( ! $user instanceof WP_User ) {
            return;
        }

        $post_query_args = array(
            'author'        => $user->ID,
            'post_type'     => 'any',
            'post_status'   => 'publish',
            'numberposts'   => -1,
            'fields'        => 'ids',
            'order'         => 'none',
            'cache_results' => false,
            'no_found_rows' => true,
        );

        $post_ids = get_posts( $post_query_args );

        $comment_query_args = array(
            'status'  => 'approve',
            'user_id' => $user->ID,
        );

        $comments = get_comments( $comment_query_args );

        foreach ( $comments as $comment ) {
            $comment_post_id = (int) $comment->comment_post_ID;

            if ( ! in_array( $comment_post_id, $post_ids, true ) ) {
                $post_ids[] = $comment_post_id;
            }
        }

        foreach ( $post_ids as $post_id ) {
            self::clear_page_cache_by_post( $post_id, $args );
        }
    }

    public static function clear_user_cache( $user = null ) {

        if ( empty( $user ) ) {
            $user = wp_get_current_user();
        } elseif ( is_numeric( $user ) ) {
            $user = get_userdata( $user );
        }

        if ( $user instanceof WP_User ) {
            self::clear_page_cache_by_user( $user, 'pagination' );
            self::clear_author_archive_cache( $user );
        }
    }

    public static function on_register_update_delete_user( $user_id ) {

        self::clear_cache_on_user_save( $user_id );
    }

    public static function on_edit_terms( $term_id, $taxonomy ) {

        if ( is_taxonomy_viewable( $taxonomy ) ) {
            self::clear_cache_on_term_save( $term_id, $taxonomy );
        }
    }

    public static function clear_term_cache( $term, $taxonomy = '' ) {

        $term = get_term( $term, $taxonomy );

        if ( $term instanceof WP_Term ) {
            self::clear_page_cache_by_term( $term, '', 'pagination' );
            self::clear_term_archive_cache( $term );

            if ( is_taxonomy_hierarchical( $term->taxonomy ) ) {
                self::clear_term_children_archives_cache( $term );
                self::clear_term_parents_archives_cache( $term );
            }
        }
    }

    public static function clear_term_children_archives_cache( $term, $taxonomy = '' ) {

        $term = get_term( $term, $taxonomy );

        if ( $term instanceof WP_Term ) {
            $child_ids = get_term_children( $term->term_id, $term->taxonomy );

            if ( is_array( $child_ids ) ) {
                foreach ( $child_ids as $child_id ) {
                    self::clear_term_archive_cache( $child_id, $term->taxonomy );
                }
            }
        }
    }

    public static function clear_term_parents_archives_cache( $term, $taxonomy = '' ) {

        $term = get_term( $term, $taxonomy );

        if ( $term instanceof WP_Term ) {
            $parent_ids = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );

            foreach ( $parent_ids as $parent_id ) {
                self::clear_term_archive_cache( $parent_id, $term->taxonomy );
            }
        }
    }

    public static function clear_term_archive_cache( $term, $taxonomy = '' ) {

        $term = get_term( $term, $taxonomy );

        if ( $term instanceof WP_Term ) {
            if ( ! is_taxonomy_viewable( $term->taxonomy ) ) {
                return; // Term archive cache does not exist.
            }

            $term_archive_url = get_term_link( $term );

            if ( ! is_wp_error( $term_archive_url ) && strpos( $term_archive_url, '?' ) === false ) {
                self::clear_page_cache_by_url( $term_archive_url, 'pagination' );
            }
        }
    }

    public static function clear_page_cache_by_term( $term, $taxonomy = '', $args = array() ) {

        $term = get_term( $term, $taxonomy );

        if ( ! $term instanceof WP_Term ) {
            return;
        }

        $post_query_args = array(
            'post_type'     => 'any',
            'post_status'   => 'publish',
            'numberposts'   => -1,
            'order'         => 'none',
            'cache_results' => false,
            'no_found_rows' => true,
            'tax_query'     => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'terms'    => $term->term_id,
                ),
            ),
        );

        $posts = get_posts( $post_query_args );

        foreach ( $posts as $post ) {
            self::clear_page_cache_by_post( $post, $args );
        }
    }

    public static function clear_cache_on_term_save( $term, $taxonomy = '' ) {

        if ( RapidLoad_Cache_Engine::$settings['clear_site_cache_on_saved_term'] ) {
            self::clear_site_cache();
        } else {
            self::clear_term_cache( $term, $taxonomy );
        }
    }

    public static function on_saved_delete_term( $term_id, $tt_id, $taxonomy ) {

        if ( is_taxonomy_viewable( $taxonomy ) ) {
            self::clear_cache_on_term_save( $term_id, $taxonomy );
        }
    }

    public static function on_transition_comment_status( $new_status, $old_status, $comment ) {

        if ( $old_status === 'approved' || $new_status === 'approved' ) {
            self::clear_cache_on_comment_save( $comment );
        }
    }

    public static function on_edit_comment( $comment_id, $comment_data ) {

        $comment_approved = (int) $comment_data['comment_approved'];

        if ( $comment_approved === 1 ) {
            self::clear_cache_on_comment_save( $comment_id );
        }
    }

    public static function on_comment_post( $comment_id, $comment_approved ) {

        if ( $comment_approved === 1 ) {
            self::clear_cache_on_comment_save( $comment_id );
        }
    }

    public static function clear_cache_on_comment_save( $comment ) {

        if ( RapidLoad_Cache_Engine::$settings['clear_site_cache_on_saved_comment'] ) {
            self::clear_site_cache();
        } else {
            self::clear_comment_cache( $comment );
        }
    }

    public static function clear_comment_cache( $comment = null ) {

        $comment = get_comment( $comment );

        if ( $comment instanceof WP_Comment ) {
            self::clear_page_cache_by_comment( $comment, 'pagination' );
        }
    }

    public static function clear_page_cache_by_comment( $comment, $args = array() ) {

        $comment = get_comment( $comment );

        if ( $comment instanceof WP_Comment ) {
            if ( $comment->comment_approved !== '1' ) {
                return; // Page cache does not exist.
            }

            self::clear_page_cache_by_post( (int) $comment->comment_post_ID, $args );
        }
    }

    public static function on_pre_post_update( $post_id, $post_data ) {

        $old_post_status = get_post_status( $post_id );
        $new_post_status = $post_data['post_status'];

        if ( $old_post_status === 'publish' && $new_post_status !== 'trash' ) {
            self::clear_cache_on_post_save( $post_id );
        }
    }

    public static function on_save_trash_post( $post_id ) {

        $post_status = get_post_status( $post_id );

        if ( $post_status === 'publish' ) {
            self::clear_cache_on_post_save( $post_id );
        }
    }

    public static function clear_cache_on_post_save( $post ) {

        if ( isset(RapidLoad_Cache_Engine::$settings['clear_site_cache_on_saved_post']) && RapidLoad_Cache_Engine::$settings['clear_site_cache_on_saved_post'] ) {
            self::clear_site_cache();
        } else {
            self::clear_post_cache( $post );
        }
    }

    public static function clear_post_cache( $post = null ) {

        $post = get_post( $post );

        if ( $post instanceof WP_Post ) {
            self::clear_page_cache_by_post( $post, 'pagination' );
            self::clear_post_type_archive_cache( $post );
            self::clear_post_terms_archives_cache( $post );

            if ( $post->post_type === 'post' ) {
                self::clear_post_author_archive_cache( $post );
                self::clear_post_date_archives_cache( $post );
            }
        }
    }

    public static function clear_post_author_archive_cache( $post = null ) {

        $post = get_post( $post );

        if ( $post instanceof WP_Post ) {
            self::clear_author_archive_cache( (int) $post->post_author );
        }
    }

    public static function clear_author_archive_cache( $author = null ) {

        if ( empty( $author ) ) {
            $author = wp_get_current_user();
        } elseif ( is_numeric( $author ) ) {
            $author = get_userdata( $author );
        }

        if ( $author instanceof WP_User ) {
            if ( empty( $author->user_nicename ) ) {
                return; // Author archive cache does not exist.
            }

            $author_archive_url = get_author_posts_url( $author->ID, $author->user_nicename );

            if ( strpos( $author_archive_url, '?' ) === false ) {
                self::clear_page_cache_by_url( $author_archive_url, 'pagination' );
            }
        }
    }

    public static function clear_post_date_archives_cache( $post = null ) {

        $post = get_post( $post );

        if ( $post instanceof WP_Post ) {
            $date_archive_day    = get_the_date( 'd', $post );
            $date_archive_month  = get_the_date( 'm', $post );
            $date_archive_year   = get_the_date( 'Y', $post );
            $date_archive_urls[] = get_day_link( $date_archive_year, $date_archive_month, $date_archive_day );
            $date_archive_urls[] = get_month_link( $date_archive_year, $date_archive_month );
            $date_archive_urls[] = get_year_link( $date_archive_year );

            foreach ( $date_archive_urls as $date_archive_url ) {
                if ( strpos( $date_archive_url, '?' ) === false ) {
                    self::clear_page_cache_by_url( $date_archive_url, 'pagination' );
                }
            }
        }
    }

    public static function clear_post_terms_archives_cache( $post = null ) {

        $post = get_post( $post );

        if ( $post instanceof WP_Post ) {
            $terms = wp_get_post_terms( $post->ID, get_taxonomies() );

            if ( is_array( $terms ) ) {
                foreach ( $terms as $term ) {
                    self::clear_term_archive_cache( $term );

                    if ( is_taxonomy_hierarchical( $term->taxonomy ) ) {
                        self::clear_term_parents_archives_cache( $term ); // Post can be in the term's parents' archives.
                    }
                }
            }
        }
    }

    public static function clear_post_type_archive_cache( $post = null ) {

        $post = get_post( $post );

        if ( $post instanceof WP_Post ) {
            $post_type_archive_url = get_post_type_archive_link( $post->post_type );

            if ( $post_type_archive_url !== false && strpos( $post_type_archive_url, '?' ) === false ) {
                self::clear_page_cache_by_url( $post_type_archive_url, 'pagination' );
            }
        }
    }

    public static function clear_page_cache_by_post( $post, $args = array() ) {

        $post = get_post( $post );

        if ( $post instanceof WP_Post ) {
            if ( $post->post_status !== 'publish' ) {
                return; // Page cache does not exist.
            }

            $post_url = get_permalink( $post );

            if ( $post_url !== false && strpos( $post_url, '?' ) === false ) {
                self::clear_page_cache_by_url( $post_url, $args );
            }
        }
    }

    public static function on_upgrade( $upgrader, $data ) {

        if ( $data['action'] !== 'update' ) {
            return;
        }

        if ( $data['type'] === 'core' ) {
            self::clear_complete_cache();
        }

        if ( $data['type'] === 'theme' && isset( $data['themes'] ) ) {
            $updated_themes = (array) $data['themes'];
            $sites_themes   = self::each_site( is_multisite(), 'wp_get_theme' );

            foreach ( $sites_themes as $blog_id => $site_theme ) {
                // Clear the site cache if the active or parent theme has been updated.
                if ( in_array( $site_theme->stylesheet, $updated_themes, true ) || in_array( $site_theme->template, $updated_themes, true ) ) {
                    self::clear_page_cache_by_site( $blog_id );
                }
            }
        }

        if ( $data['type'] === 'plugin' && isset( $data['plugins'] ) ) {
            $updated_plugins = (array) $data['plugins'];
            $network_plugins = is_multisite() ? array_flip( (array) get_site_option( 'active_sitewide_plugins', array() ) ) : array();

            // Clear the complete cache if a network activated plugin has been updated.
            if ( ! empty( array_intersect( $updated_plugins, $network_plugins ) ) ) {
                self::clear_complete_cache();
            } else {
                $sites_plugins = self::each_site( is_multisite(), 'get_option', array( 'active_plugins', array() ) );

                foreach ( $sites_plugins as $blog_id => $site_plugins ) {
                    // Clear the site cache if an activated plugin has been updated.
                    if ( ! empty( array_intersect( $updated_plugins, (array) $site_plugins ) ) ) {
                        self::clear_page_cache_by_site( $blog_id );
                    }
                }
            }
        }
    }

    public static function clear_complete_cache() {

        self::each_site( is_multisite(), 'self::clear_site_cache', array(), true );
    }

    public static function uninstall_later( $old_site ) {

        RapidLoad_Cache_Store::clean();

        self::clear_page_cache_by_site( (int) $old_site->blog_id );
    }

    public static function install_later( $new_site ) {

        if ( ! is_plugin_active_for_network( RAPIDLOAD_BASE ) ) {
            return;
        }

        self::switch_to_blog( (int) $new_site->blog_id );
        self::update_backend();
        self::restore_current_blog();
    }

    public function clear_cache($args){

        if ( isset( $args['url'] ) ) {
            self::clear_page_cache_by_url( $args['url'] );
//            wp_remote_get( $args['url'] );
        }

    }

    private static function user_can_clear_cache() {

        $can_clear_cache = apply_filters( 'rapidload_cache_user_can_clear_cache', current_user_can( 'manage_options' ) );

        return $can_clear_cache;
    }

    public static function add_admin_bar_items( $wp_admin_bar ) {

        if ( ! self::user_can_clear_cache() ) {
            return;
        }

        $title = ( is_multisite() && is_network_admin() ) ? esc_html__( 'Clear Network Cache', 'unusedcss' ) : esc_html__( 'Clear Site Cache', 'unusedcss' );

        $wp_admin_bar->add_menu(
            array(
                'id'     => 'rapidload_cache_clear_cache',
                'href'   => wp_nonce_url( add_query_arg( array(
                    '_cache'  => 'rapidload-cache',
                    '_action' => 'clear',
                ) ), 'rapidload_cache_clear_cache_nonce' ),
                'parent' => 'rapidload',
                'title'  => '<span class="ab-label">' . $title . '</span>',
                'meta'   => array( 'title' => $title ),
            )
        );

        if ( ! is_admin() ) {
            $wp_admin_bar->add_menu(
                array(
                    'id'     => 'rapidload_cache_clear_page_cache',
                    'href'   => wp_nonce_url( add_query_arg( array(
                        '_cache'  => 'rapidload-cache',
                        '_action' => 'clearurl',
                    ) ), 'rapidload_cache_clear_cache_nonce' ),
                    'parent' => 'rapidload',
                    'title'  => '<span class="ab-label">' . esc_html__( 'Clear Page Cache', 'unusedcss' ) . '</span>',
                    'meta'   => array( 'title' => esc_html__( 'Clear Page Cache', 'unusedcss' ) ),
                )
            );
        }
    }

    public static function process_clear_cache_request() {

        if ( empty( $_GET['_cache'] ) || empty( $_GET['_action'] ) || $_GET['_cache'] !== 'rapidload-cache' || ( $_GET['_action'] !== 'clear' && $_GET['_action'] !== 'clearurl' ) ) {
            return;
        }

        if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rapidload_cache_clear_cache_nonce' ) ) {
            return;
        }

        if ( ! self::user_can_clear_cache() ) {
            return;
        }

        if ( $_GET['_action'] === 'clearurl' ) {

            $url = isset($_GET['_url']) ? sanitize_url(wp_unslash($_GET['_url'])) : RapidLoad_Cache_Engine::$request_headers['Host'] . RapidLoad_Cache_Engine::sanitize_server_input($_SERVER['REQUEST_URI'], false);

            self::clear_page_cache_by_url( $url );
        } elseif ( $_GET['_action'] === 'clear' ) {

            self::each_site( ( is_multisite() && is_network_admin() ), 'self::clear_site_cache', array(), true );
        }

        // Redirect to the same page.
        wp_safe_redirect( remove_query_arg( array( '_cache', '_action', '_wpnonce' ) ) );

        if ( is_admin() ) {
            set_transient( self::get_cache_cleared_transient_name(), 1 );
        }

        exit;
    }

    public function update_module_options($options){

        if(isset($options['cache'])){
            $options['cache']['options'] = self::get_settings();
        }

        return $options;

    }

    public function cache_page($state){

        self::debug_log('doing cache page');

        if(isset($state['dom']) && RapidLoad_Cache_Engine::$to_be_cached){

            if(gettype($state['dom']) === "string"){
                $content = $state['dom'];
            }else{
                $content = $state['dom']->__toString();
            }

            if (RapidLoad_Cache_Engine::is_cacheable( $content ) && !RapidLoad_Cache_Engine::bypass_cache()) {

                if(isset($_REQUEST['rapidload_preview'])){
                    $content = str_replace("</body>","<div id='rapidload-cache-status' style='display:none;text-align:center;background-color:#a080c6;padding:1px;color:white;font-weight:500;'><p>Served by RapidLoad Cache</p></div></body>",$content);
                }

                RapidLoad_Cache_Store::cache_page( $content );

            }

        }

        return $state;
    }

    public static function setup_cache($status){

        if($status === "1"){

            RapidLoad_Cache_Store::create_advanced_cache_file();
            RapidLoad_Cache_Store::set_wp_cache_constant();

        }else{

            RapidLoad_Cache_Store::clean();

        }

    }

    public static function get_settings( $update = true ) {

        $settings = get_option( 'rapidload_cache' );

        if ( $settings === false || ! isset( $settings['version'] ) || $settings['version'] !== UUCSS_VERSION ) {
            if ( $update ) {
                self::update();
                $settings = self::get_settings( false );
            }
        }

        return $settings;
    }

    public static function update() {

        self::update_disk();
        self::update_backend();
        self::clear_site_cache();
    }

    public static function update_backend() {

        delete_metadata( 'user', 0, '_clear_post_cache_on_update', '', true ); // < 1.5.0

        $old_value = get_option( 'rapidload_cache', array() );
        $value     = self::upgrade_settings( $old_value );
        $value     = self::validate_settings( $value );

        update_option( 'rapidload_cache', $value );

        if ( has_action( 'update_option', array( __CLASS__, 'on_update_option' ) ) === false ) {
            self::on_update_backend( 'rapidload_cache', $value );
        }

        return $value;
    }

    public static function update_settings($args){

        $settings = self::get_default_settings();

        foreach($args as $key => $val){

            $settings[$key] = $val;

        }

        update_option( 'rapidload_cache', $settings );
        self::on_update_backend('', $settings);

    }

    public static function on_update_backend( $option, $value ) {

        RapidLoad_Cache_Store::create_settings_file( $value );

        self::unschedule_events();
    }

    public static function unschedule_events() {

        $events = self::get_events();

        foreach ( $events as $hook => $recurrence ) {
            wp_unschedule_event( wp_next_scheduled( $hook ), $hook );
        }
    }

    private static function get_events() {

        $events = array( 'rapidload_cache_clear_expired_cache' => 'hourly' );

        return $events;
    }

    private static function upgrade_settings( $settings ) {

        if ( empty( $settings ) ) {
            return $settings;
        }

        if ( isset( $settings['expires'] ) && $settings['expires'] > 0 ) {
            $settings['cache_expires'] = 1;
        }

        if ( isset( $settings['minify_html'] ) && $settings['minify_html'] === 2 ) {
            $settings['minify_html'] = 1;
            $settings['minify_inline_css_js'] = 1;
        }

        $settings_names = array(
            'excl_regexp'                            => 'excluded_page_paths',
            'incl_attributes'                        => '',
            'expires'                                => 'cache_expiry_time',
            'new_post'                               => 'clear_site_cache_on_saved_post',
            'update_product_stock'                   => '',
            'new_comment'                            => 'clear_site_cache_on_saved_comment',
            'clear_on_upgrade'                       => 'clear_site_cache_on_changed_plugin',
            'webp'                                   => 'convert_image_urls_to_webp',
            'compress'                               => 'compress_cache',
            'excl_ids'                               => 'excluded_post_ids',
            'excl_paths'                             => 'excluded_page_paths',
            'excl_cookies'                           => 'excluded_cookies',
            'incl_parameters'                        => '',
            'clear_complete_cache_on_saved_post'     => 'clear_site_cache_on_saved_post',
            'clear_complete_cache_on_new_comment'    => 'clear_site_cache_on_saved_comment',
            'clear_complete_cache_on_changed_plugin' => 'clear_site_cache_on_changed_plugin',
            'clear_site_cache_on_new_comment'        => 'clear_site_cache_on_saved_comment',
        );

        foreach ( $settings_names as $old_name => $new_name ) {
            if ( array_key_exists( $old_name, $settings ) ) {
                if ( ! empty( $new_name ) ) {
                    $settings[ $new_name ] = $settings[ $old_name ];
                }

                unset( $settings[ $old_name ] );
            }
        }

        return $settings;
    }

    private static function get_default_settings( $settings_type = '' ) {

        switch ( $settings_type ) {
            case 'system':
                return self::get_default_system_settings();
            case 'user':
                return self::get_default_user_settings();
            default:
                return wp_parse_args( self::get_default_user_settings(), self::get_default_system_settings() );
        }
    }

    private static function get_default_user_settings() {

        $default_user_settings = array(
            'cache_expires'                      => 1,
            'cache_expiry_time'                  => 0,
            'clear_site_cache_on_saved_post'     => 0,
            'clear_site_cache_on_saved_comment'  => 0,
            'clear_site_cache_on_saved_term'     => 0,
            'clear_site_cache_on_saved_user'     => 0,
            'clear_site_cache_on_changed_plugin' => 0,
            'convert_image_urls_to_webp'         => 0,
            'mobile_cache'                       => 1,
            'compress_cache'                     => 0,
            'minify_html'                        => 1,
            'minify_inline_css_js'               => 0,
            'excluded_post_ids'                  => '',
            'excluded_page_paths'                => '',
            'excluded_query_strings'             => '',
            'excluded_cookies'                   => '',
        );

        return $default_user_settings;
    }

    private static function get_default_system_settings() {

        $default_system_settings = array(
            'version'              => (string) UUCSS_VERSION,
            'use_trailing_slashes' => (int) ( substr( get_option( 'permalink_structure' ), -1, 1 ) === '/' ),
            'permalink_structure'  => (string) self::get_permalink_structure(), // Deprecated in 1.8.0.
        );

        return $default_system_settings;
    }

    private static function get_permalink_structure() {

        $permalink_structure = get_option( 'permalink_structure' );

        if ( $permalink_structure && preg_match( '/\/$/', $permalink_structure ) ) {
            return 'has_trailing_slash';
        }

        if ( $permalink_structure && ! preg_match( '/\/$/', $permalink_structure ) ) {
            return 'no_trailing_slash';
        }

        if ( empty( $permalink_structure ) ) {
            return 'plain';
        }
    }

    public static function validate_settings( $settings ) {

        $settings = (array) apply_filters( 'rapidload_cache_settings_before_validation', $settings );
        $settings = wp_parse_args( $settings, self::get_default_settings( 'user' ) );

        $validated_settings = wp_parse_args( array(
            'cache_expires'                      => (int) ( ! empty( $settings['cache_expires'] ) ),
            'cache_expiry_time'                  => absint( $settings['cache_expiry_time'] ),
            'clear_site_cache_on_saved_post'     => (int) ( ! empty( $settings['clear_site_cache_on_saved_post'] ) ),
            'clear_site_cache_on_saved_comment'  => (int) ( ! empty( $settings['clear_site_cache_on_saved_comment'] ) ),
            'clear_site_cache_on_saved_term'     => (int) ( ! empty( $settings['clear_site_cache_on_saved_term'] ) ),
            'clear_site_cache_on_saved_user'     => (int) ( ! empty( $settings['clear_site_cache_on_saved_user'] ) ),
            'clear_site_cache_on_changed_plugin' => (int) ( ! empty( $settings['clear_site_cache_on_changed_plugin'] ) ),
            'convert_image_urls_to_webp'         => (int) ( ! empty( $settings['convert_image_urls_to_webp'] ) ),
            'mobile_cache'                       => (int) ( ! empty( $settings['mobile_cache'] ) ),
            'compress_cache'                     => (int) ( ! empty( $settings['compress_cache'] ) ),
            'minify_html'                        => (int) ( ! empty( $settings['minify_html'] ) ),
            'minify_inline_css_js'               => (int) ( ! empty( $settings['minify_inline_css_js'] ) ),
            'excluded_post_ids'                  => (string) $settings['excluded_post_ids'],
            'excluded_page_paths'                => (string) self::validate_regex( $settings['excluded_page_paths'] ),
            'excluded_query_strings'             => (string) self::validate_regex( $settings['excluded_query_strings'] ),
            'excluded_cookies'                   => (string) self::validate_regex( $settings['excluded_cookies'] ),
        ), self::get_default_settings( 'system' ) );

        if ( ! empty( $settings['clear_site_cache_on_saved_settings'] ) ) {
            self::clear_site_cache();
            set_transient( self::get_cache_cleared_transient_name(), 1 );
        }

        return $validated_settings;
    }

    public static function validate_regex( $regex ) {

        if ( ! empty( $regex ) ) {
            if ( ! preg_match( '/^\/.*\/$/', $regex ) ) {
                $regex = '/' . $regex . '/';
            }

            if ( @preg_match( $regex, null ) === false ) {
                return '';
            }

            $validated_regex = $regex;

            return $validated_regex;
        }

        return '';
    }

    private static function get_cache_cleared_transient_name() {

        $transient_name = 'rapidlaod_cache_cleared_' . get_current_user_id();

        return $transient_name;
    }

    public static function clear_site_cache( $site = null ) {

        self::clear_page_cache_by_site( $site );
    }

    public static function clear_page_cache_by_site( $site, $args = array() ) {

        $blog_id = self::get_blog_id( $site );

        if ( $blog_id === 0 ) {
            return; // Page cache does not exist.
        }

        if ( is_array( $args ) ) {
            $args['subpages']['exclude'] = self::get_root_blog_exclusions();

            if ( ! isset( $args['hooks']['include'] ) ) {
                $args['hooks']['include'] = 'rapidload_cache_complete_cache_cleared,rapidload_cache_site_cache_cleared';
            }
        }

        self::clear_page_cache_by_url( get_home_url( $blog_id ), $args );
    }

    public static function clear_page_cache_by_url( $url, $args = array() ) {

        if ( is_array( $args ) ) {
            $args['clear'] = 1;

            if ( ! isset( $args['hooks']['include'] ) ) {
                $args['hooks']['include'] = 'rapidload_cache_page_cache_cleared';
            }
        }

        RapidLoad_Cache_Store::cache_iterator( $url, $args );
    }

    public static function get_blog_path_from_url( $url ) {

        $url_path        = (string) parse_url( $url, PHP_URL_PATH );
        $url_path_pieces = explode( '/', trim( $url_path, '/' ) );
        $blog_path       = '/';
        $blog_paths      = self::get_blog_paths();

        foreach ( $url_path_pieces as $url_path_piece ) {
            $url_path_piece = '/' . $url_path_piece . '/';

            if ( in_array( $url_path_piece, $blog_paths, true ) ) {
                $blog_path = $url_path_piece;
                break;
            }
        }

        return $blog_path;
    }

    private static function get_root_blog_exclusions() {

        if ( ! is_multisite() || is_subdomain_install() ) {
            return array();
        }

        $current_blog_path  = self::get_blog_path();
        $network_blog_paths = self::get_blog_paths();

        if ( ! in_array( $current_blog_path, $network_blog_paths, true ) ) {
            return $network_blog_paths;
        }

        return array();
    }

    public static function get_blog_paths() {

        if ( is_multisite() ) {
            global $wpdb;
            $blog_paths = $wpdb->get_col( "SELECT path FROM $wpdb->blogs" );
        } else {
            $blog_paths = array( '/' );
        }

        return $blog_paths;
    }

    public static function get_blog_path() {

        $site_url_path        = (string) parse_url( home_url(), PHP_URL_PATH );
        $site_url_path_pieces = explode( '/', trim( $site_url_path, '/' ) );

        $blog_path = end( $site_url_path_pieces );
        $blog_path = ( ! empty( $blog_path ) ) ? '/' . $blog_path . '/' : '';

        return $blog_path;
    }

    private static function get_blog_id( $site = null ) {

        if ( empty( $site ) ) {
            return get_current_blog_id();
        }

        if ( $site instanceof WP_Site ) {
            return (int) $site->blog_id;
        }

        if ( is_numeric( $site ) ) {
            $blog_id = (int) $site;

            if ( in_array( $blog_id, self::get_blog_ids(), true ) ) {
                return $blog_id;
            }
        }

        return 0;
    }

    public static function update_disk() {

        if ( is_multisite() ) {
            if ( get_site_transient( 'rapidload_cache_disk_updated' ) !== UUCSS_VERSION ) {
                self::each_site( true, 'RapidLoad_Cash_Store::clean' );
                RapidLoad_Cache_Store::setup();
                set_site_transient( 'rapidload_cache_disk_updated', UUCSS_VERSION, HOUR_IN_SECONDS );
            }
        } else {
            RapidLoad_Cache_Store::clean();
            RapidLoad_Cache_Store::setup();
        }
    }

    private static function each_site( $sites, $callback, $callback_params = array(), $restart_engine = false ) {

        $blog_ids          = $sites ? self::get_blog_ids() : array( get_current_blog_id() );
        $last_blog_id      = end( $blog_ids );
        $skip_active_check = ! self::is_rapidload_active();
        $callback_return   = array();

        foreach ( $blog_ids as $blog_id ) {
            try {
                self::switch_to_blog( $blog_id, $restart_engine, $skip_active_check );

                if ( ($skip_active_check || self::is_rapidload_active()) && is_callable($callback)) {
                    $callback_return[ $blog_id ] = call_user_func_array( $callback, $callback_params );
                }

                $_restart_engine = ( $restart_engine && $blog_id === $last_blog_id ) ? true : false;

                self::restore_current_blog( $_restart_engine, $skip_active_check );
            }catch (Exception $exception){

            }
        }

        return $callback_return;
    }

    private static function get_blog_ids() {

        if ( is_multisite() ) {
            global $wpdb;
            $blog_ids = array_map( 'absint', $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) );
        } else {
            $blog_ids = array( 1 );
        }

        return $blog_ids;
    }

    private static function is_rapidload_active() {

        if ( in_array( RAPIDLOAD_BASE, (array) get_option( 'active_plugins', array() ), true ) ) {
            return true;
        }

        if ( ! is_multisite() ) {
            return false;
        }

        $plugins = get_site_option( 'active_sitewide_plugins' );
        if ( isset( $plugins[ RAPIDLOAD_BASE ] ) ) {
            return true;
        }

        return false;
    }

    public static function switch_to_blog( $blog_id, $restart_engine = false, $force_restart = false ) {

        if ( ! is_multisite() || $blog_id === get_current_blog_id() ) {
            return false;
        }

        switch_to_blog( $blog_id );

        if ( ( $force_restart || self::is_rapidload_active() ) && $restart_engine ) {
            RapidLoad_Cache_Engine::start();
        }

        return true;
    }

    public static function restore_current_blog( $restart_engine = false, $force_restart = false  ) {

        if ( ! is_multisite() || ! ms_is_switched() ) {
            return false;
        }

        restore_current_blog();

        if ( ( $force_restart || self::is_rapidload_active() ) && $restart_engine ) {
            RapidLoad_Cache_Engine::start( true );
        }

        return true;
    }

    public static function get_cache_index() {

        $args['subpages']['exclude'] = self::get_root_blog_exclusions();
        $cache = RapidLoad_Cache_Store::cache_iterator( home_url(), $args );
        $cache_index = $cache['index'];

        return $cache_index;
    }

    public static function on_cache_created_cleared( $url, $id, $index ) {

        if ( is_multisite() && ! wp_is_site_initialized( get_current_blog_id() ) ) {
            return;
        }

        $current_cache_size = get_transient( 'rapidload_page_cache_size' );

        if ( count( $index ) > 1 ) {
            if ( $current_cache_size !== false ) {
                // Prevent an incorrect cache size being built when the cache cleared index is not the entire site.
                delete_transient( 'rapidload_page_cache_size' );
            }
        } else {
            // The changed cache size is negative when the cache is cleared.
            $changed_cache_size = array_sum( current( $index )['versions'] );

            if ( $current_cache_size === false ) {
                if ( $changed_cache_size > 0 ) {
                    self::get_cache_size();
                }
            } else {
                $new_cache_size = $current_cache_size + $changed_cache_size;
                $new_cache_size = ( $new_cache_size >= 0 ) ? $new_cache_size : 0;

                set_transient( 'rapidload_page_cache_size', $new_cache_size, DAY_IN_SECONDS );
            }
        }
    }

    public static function get_cache_size() {

        $cache_size = get_transient( 'rapidload_page_cache_size' );

        if ( $cache_size === false ) {
            $args['subpages']['exclude'] = self::get_root_blog_exclusions();
            $cache = RapidLoad_Cache_Store::cache_iterator( home_url(), $args );
            $cache_size = $cache['size'];

            set_transient( 'rapidload_page_cache_size', $cache_size, DAY_IN_SECONDS );
        }

        return $cache_size;
    }

    public static function on_activation( $network_wide ) {

        self::$options = RapidLoad_Base::fetch_options();

        if(!isset(self::$options['uucss_enable_cache']) || self::$options['uucss_enable_cache'] !== "1" ){
            return;
        }

        self::each_site( $network_wide, self::class . '::update_backend' );

        RapidLoad_Cache_Store::setup();
    }

    public static function on_deactivation( $network_wide ) {

        self::$options = RapidLoad_Base::fetch_options();

        if(!isset(self::$options['uucss_enable_cache']) || self::$options['uucss_enable_cache'] !== "1" ){
            return;
        }

        self::each_site( $network_wide, 'RapidLoad_Cache_Store::clean' );
        self::each_site( $network_wide, self::class . '::clear_site_cache', array(), true );
        self::each_site( $network_wide, self::class . '::unschedule_events' );
    }
}