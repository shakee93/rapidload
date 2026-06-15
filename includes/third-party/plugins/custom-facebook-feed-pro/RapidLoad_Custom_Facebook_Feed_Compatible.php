<?php

defined( 'ABSPATH' ) or die();

/**
 * Smash Balloon - Custom Facebook Feed Pro compatibility.
 *
 * Custom Facebook Feed Pro refreshes its feed data in the background via
 * WP-Cron (the 'cff_cache_cron' / 'cff_cron_job' events). The cron writes
 * fresh posts into its own cache tables/transients but fires none of the
 * WordPress content hooks that RapidLoad listens to for page cache
 * invalidation, so the cached HTML keeps serving the old feed until the
 * cache is purged manually.
 *
 * We hook into the plugin's own cron events at a late priority (after the
 * feed has been re-fetched) and clear the RapidLoad page cache so the next
 * visitor regenerates the page with the latest posts.
 */
class RapidLoad_Custom_Facebook_Feed_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin   = 'custom-facebook-feed-pro/custom-facebook-feed.php';
        $this->catgeory = 'social-feed';
        $this->name     = 'custom-facebook-feed-pro';

        parent::__construct();
    }

    public function init_hooks()
    {
        // v4 background cache updater (runs cff_v4_do_background_updates()).
        // Priority 99 so it fires after the feed data has been refreshed.
        // @see custom-facebook-feed-pro/custom-facebook-feed-admin.php
        add_action( 'cff_cache_cron', [ $this, 'handle' ], 99 );

        // Large feeds defer the remaining batches to a one-off cron event
        // (~120s later); clear again once those have refreshed too.
        add_action( 'cff_cron_additional_batch', [ $this, 'handle' ], 99 );

        // Legacy transient cron updater.
        add_action( 'cff_cron_job', [ $this, 'handle' ], 99 );
    }

    public function handle($args = null)
    {
        if ( ! class_exists( 'RapidLoad_Cache' ) ) {
            return;
        }

        // These are WP-Cron events; the guard is defensive in case the action
        // is ever dispatched outside a cron run (we must not purge mid-render).
        if ( ! wp_doing_cron() ) {
            return;
        }

        global $wpdb;
        $locator_table = $wpdb->prefix . ( defined( 'CFF_FEED_LOCATOR' ) ? CFF_FEED_LOCATOR : 'cff_facebook_feed_locator' );

        self::clear_cache_by_feed_locations( $locator_table, 'Custom Facebook Feed Pro' );
    }

    /**
     * Smash Balloon records where each feed is rendered in its "feed locator"
     * table (html_location = content|header|footer|sidebar, plus post_id).
     *
     * - If a feed is placed in a global theme area (header/footer/sidebar) it
     *   appears on every page, so we must clear the whole site cache.
     * - If feeds only live inside specific post/page content, we clear just
     *   those URLs and leave the rest of the cache warm.
     *
     * Note: we look at all recorded feed placements (not just the one feed the
     * cron is refreshing). Mapping the cron run back to a single feed_id is
     * fragile, and the conservative full-table check is cheap and correct.
     */
    protected static function clear_cache_by_feed_locations( $locator_table, $label )
    {
        global $wpdb;

        // Guard against the table not existing yet (fresh install / pre-render).
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $locator_table ) );
        if ( $exists !== $locator_table ) {
            RapidLoad_Cache::clear_site_cache();
            return;
        }

        $rows = $wpdb->get_results( "SELECT DISTINCT post_id, html_location FROM `{$locator_table}`", ARRAY_A );

        if ( empty( $rows ) ) {
            // No placement info recorded — fall back to a full site clear to be safe.
            RapidLoad_Cache::clear_site_cache();
            return;
        }

        $post_ids = array();

        foreach ( $rows as $row ) {

            // 'content' is the only placement we can resolve to a single URL.
            // Anything else — header/footer/sidebar (global) or 'unknown'
            // (placement could not be determined) — must be treated as
            // site-wide so we never leave an undetected feed stale.
            if ( $row['html_location'] !== 'content' ) {
                RapidLoad_Cache::clear_site_cache();

                self::log( [
                    'log'  => 'RapidLoad: cleared full site cache after ' . $label . ' cron update (global/unresolved feed placement)',
                    'type' => 'compatibility',
                ] );
                return;
            }

            if ( ! empty( $row['post_id'] ) && $row['post_id'] !== 'unknown' ) {
                $post_ids[ (int) $row['post_id'] ] = true;
            }
        }

        if ( empty( $post_ids ) ) {
            // Placements recorded but none we can resolve to a URL — clear everything.
            RapidLoad_Cache::clear_site_cache();
            return;
        }

        foreach ( array_keys( $post_ids ) as $post_id ) {
            RapidLoad_Cache::clear_page_cache_by_post( $post_id );
        }

        self::log( [
            'log'  => 'RapidLoad: cleared ' . count( $post_ids ) . ' page(s) after ' . $label . ' cron update (content placement)',
            'type' => 'compatibility',
        ] );
    }

    public function is_mu_plugin()
    {
        return false;
    }
}
