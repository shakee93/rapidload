<?php

defined( 'ABSPATH' ) or die();

/**
 * Smash Balloon - Instagram Feed Pro compatibility.
 *
 * Instagram Feed Pro refreshes its feed data in the background via WP-Cron.
 * When the cron runs it writes fresh posts into its own transients/DB cache,
 * but it fires none of the WordPress content hooks (save_post, etc.) that
 * RapidLoad listens to for page cache invalidation. As a result the cached
 * HTML keeps serving the old feed until the cache is purged manually.
 *
 * We hook into the plugin's own "feed cache was updated by cron" action and
 * clear the RapidLoad page cache so the next visitor regenerates the page
 * with the latest posts.
 */
class RapidLoad_Instagram_Feed_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin   = 'instagram-feed-pro/instagram-feed.php';
        $this->catgeory = 'social-feed';
        $this->name     = 'instagram-feed-pro';

        parent::__construct();
    }

    public function init_hooks()
    {
        // Fired by Instagram Feed Pro after a single feed is refreshed.
        // @see instagram-feed-pro/inc/pro/class-sbi-cron-update-pro.php::do_single_feed_cron_update()
        add_action( 'sbi_after_single_feed_cron_update', [ $this, 'handle' ], 10, 1 );
    }

    public function handle($args = null)
    {
        if ( ! class_exists( 'RapidLoad_Cache' ) ) {
            return;
        }

        // do_single_feed_cron_update() also runs on the front-end when a
        // background-cached feed first sets up its cron job. In that case the
        // page is being rendered fresh anyway, so there is no stale page cache
        // to clear — and purging mid-request would hurt that visitor. Only act
        // during an actual (loopback/WP-CLI) cron run.
        if ( ! wp_doing_cron() ) {
            return;
        }

        global $wpdb;
        $locator_table = $wpdb->prefix . ( defined( 'SBI_INSTAGRAM_FEED_LOCATOR' ) ? SBI_INSTAGRAM_FEED_LOCATOR : 'sbi_instagram_feed_locator' );

        self::clear_cache_by_feed_locations( $locator_table, 'Instagram Feed Pro' );
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
     * cron is refreshing). Mapping the cron's transient name back to a single
     * feed_id is fragile, and the conservative full-table check is cheap and
     * correct.
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
