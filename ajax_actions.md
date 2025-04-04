# RapidLoad WordPress AJAX Actions

This document lists all WordPress AJAX actions used in the RapidLoad plugin.

## Core Actions

### Database
- `rapidload_db_update` - Handle database updates

### License & API
- `uucss_license` - Handle license management
- `uucss_connect` - Handle API connection
- `verify_api_key` - Verify API key
- `uucss_deactivate` - Handle plugin deactivation

### Logs & Debug
- `get_robots_text` - Get robots.txt content
- `frontend_logs` - Get frontend logs
- `uucss_logs` - Get RapidLoad logs
- `clear_uucss_logs` - Clear RapidLoad logs

## Module-Specific Actions

### Cache Module
- `clear_page_cache` - Clear page cache
- `rapidload_purge_all` - Purge all caches

### CSS Module
#### Critical CSS
- `cpcss_purge_url` - Purge Critical CSS for specific URL
- `rapidload_css_job_status` - Get CSS job status

#### Unused CSS
- `uucss_purge_url` - Purge Unused CSS for specific URL

### CDN Module
- `validate_cdn` - Validate CDN configuration
- `purge_rapidload_cdn` - Purge CDN cache
- `rapidload_cdn_usage` - Get CDN usage statistics
- `rapidload_enable_cdn_metering` - Enable CDN metering

### Image Module
- `rapidload_image_usage` - Get image optimization usage
- `rapidload_image_optimization_status` - Get image optimization status
- `rapidload_enable_image_metering` - Enable image optimization metering

### Page Optimizer Module
- `fetch_page_speed` - Fetch page speed metrics
- `latest_page_speed` - Get latest page speed data
- `preload_page` - Preload page
- `fetch_titan_settings` - Get Titan optimizer settings
- `update_titan_settings` - Update Titan optimizer settings
- `update_titan_performance_gear` - Update performance gear settings
- `rapidload_titan_home_page_performance` - Get homepage performance data
- `rapidload_titan_feedback` - Handle Titan feedback
- `rapidload_titan_optimizations_data` - Get optimization data
- `rapidload_delete_titan_optimizations` - Delete optimizations

### Rules & Settings
- `get_all_rules` - Get all optimization rules
- `upload_rules` - Upload optimization rules
- `uucss_update_rule` - Update optimization rule
- `attach_rule` - Attach new rule
- `update_rapidload_settings` - Update plugin settings
- `update_htaccess_file` - Update .htaccess file

### Module Management
- `activate_module` - Activate/deactivate module
- `list_module` - List available modules

### Post & URL Management
- `rapidload_fetch_post_types_with_links` - Get post types with links
- `rapidload_fetch_post_search_by_title_or_permalink` - Search posts by title/permalink

### Status & Tests
- `uucss_test_url` - Test URL optimization
- `uucss_data` - Get optimization data
- `rapidload_notifications` - Get plugin notifications
- `titan_checklist_crawler` - Check crawler status
- `titan_checklist_cron` - Check cron status
- `titan_checklist_plugins` - Check plugin compatibility
- `titan_checklist_status` - Get overall checklist status
- `rapidload_switch_test_mode` - Toggle test mode

### Onboarding
- `rapidload_configured` - Handle initial configuration
- `run_first_job` - Run first optimization job

### UI/UX
- `mark_faqs_read` - Mark FAQs as read
- `mark_notice_read` - Mark notices as read
- `suggest_whitelist_packs` - Get whitelist suggestions

## AJAX Actions Available Without Authentication

The following actions are also available for non-logged-in users when `RAPIDLOAD_DEV_MODE` is defined:

- `validate_cdn`
- `uucss_license`
- `uucss_connect`
- `rapidload_switch_test_mode`
- `titan_checklist_crawler`
- `clear_page_cache`
- `titan_checklist_cron`
- `titan_checklist_plugins`
- `titan_checklist_status`
- `rapidload_delete_titan_optimizations`
- `rapidload_titan_optimizations_data`
- `rapidload_fetch_post_types_with_links`
- `rapidload_fetch_post_search_by_title_or_permalink`
- `rapidload_image_optimization_status` 