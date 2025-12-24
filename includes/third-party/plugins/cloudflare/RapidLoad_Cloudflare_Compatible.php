<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_Cloudflare_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'cloudflare/cloudflare.php';
        $this->catgeory = 'cache';
        $this->name = 'cloudflare';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('uucss/cache/bust', [$this, 'add_cache_busting_params'], 10, 1);
        add_action( 'uucss/cached', [$this, 'handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'handle'], 10, 2 );
    }

    public function add_cache_busting_params($cacheBusting){

        array_push($cacheBusting, [
            'type' => 'query',
            'rule' => 'nocache'
        ]);

        return $cacheBusting;
    }

    public function handle($args)
    {
        $url = null;

        if ( isset( $args['url'] ) ) {
            $url = $this->transform_url( $args['url'] );
        }

        if(empty($url)){
            return;
        }

        // Try to get post ID from URL
        // Note: url_to_postid() returns 0 for non-post URLs (archives, taxonomies, etc.)
        $post_id = url_to_postid( $url );

        if($post_id <= 0){
            return;
        }

        try {
            // Use new namespace (Cloudflare plugin v4.13.0+)
            // Old namespace \CF\WordPress\Hooks is deprecated but still works for backward compatibility
            $cfHooks = null;
            
            if(class_exists('\Cloudflare\APO\WordPress\Hooks')){
                // New namespace (preferred)
                $cfHooks = new \Cloudflare\APO\WordPress\Hooks();
            } elseif(class_exists('\CF\WordPress\Hooks')){
                // Old namespace (deprecated since v4.13.0 but still supported)
                $cfHooks = new \CF\WordPress\Hooks();
            }

            if($cfHooks && method_exists($cfHooks, 'purgeCacheByRelevantURLs')){
                // Use Cloudflare's Hooks class to purge cache by relevant URLs
                // This method automatically purges related URLs (archives, taxonomies, etc.)
                // Compatible with Cloudflare plugin v4.14.2+
                $cfHooks->purgeCacheByRelevantURLs($post_id);
            }
        } catch (Exception $e) {
            // Silently fail to avoid breaking the main process
            // Error logging can be added here if needed
        } catch (Error $e) {
            // Handle PHP 7+ errors (method doesn't exist, etc.)
            // Silently fail to avoid breaking the main process
        }
    }

    public function is_mu_plugin()
    {
        return false;
    }
}