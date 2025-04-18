<?php defined( 'ABSPATH' ) or die(); ?>

<script>document.title = "Autoptimize: RapidLoad " + document.title;</script>

<?php

global $uucss;
global $rapidload;

$third_party_plugins = apply_filters('uucss/third-party/plugins', []);
$third_party_cache_plugins = array_filter($third_party_plugins, function ($plugin){
    return isset($plugin['category']) && $plugin['category'] === 'cache';
});

?>

<form id='ao_settings_form' action='<?php echo esc_url(admin_url( 'options.php' )); ?>' method='post'>
    <?php settings_fields( 'autoptimize_uucss_settings' );

    $api_key_verified = isset( $options['uucss_api_key_verified'] ) && ($options['uucss_api_key_verified'] === '1' || $options['uucss_api_key_verified'] === 1);
    $default_debug_mode = ! empty( $options['uucss_enable_debug'] ) && '1' === $options['uucss_enable_debug'];
    $hide_view_log = apply_filters('uucss/view_debug/frontend', (boolean)$default_debug_mode);

    ?>
    <div>
        <input id='thirtd_part_cache_plugins' type='hidden'
               value="<?php if ( ! empty( $third_party_cache_plugins ) ) {
                   echo '1';
               } ?>">
        <input id='uucss_auto_refresh_frontend-hidden' type='hidden'
               name='autoptimize_uucss_settings[uucss_auto_refresh_frontend]'  value="1">
        <input id='uucss_auto_refresh_frontend-hidden_rule' type='hidden'
               name='autoptimize_uucss_settings[uucss_auto_refresh_frontend_rule]'  value="1">
    </div>
    <div style="display: flex">
        <ul style="width: 78%" id="uucss-wrapper">
            <li class="uucss-notification" style="display: none">
                <div class="content"></div>
            </li>

            <?php if ( ! $api_key_verified ) : ?>
                <li class="uucss-intro">
                    <?php include_once 'intro.html.php' ?>
                </li>
            <?php endif; ?>
            <?php if ( $api_key_verified) : ?>

                <?php if(isset($_GET['uucss_jobs'])) : ?>
                <li class="uucss-history uucss-job-history">
                    <h2>
                        Optimization Jobs
                        <span class="multiple-selected-text">
                            <span class="multiple-selected-value multiple-selected-value-job"></span>
                            Selected
                            <a href="#" id="js-uucss-clear-selection">clear</a>
                        </span>
                        <span class="uucss-add-menu uucss-add-site-urls" data-featherlight="#add_url_featherlight_content" data-featherlight-open-speed="50" data-featherlight-close-speed="50" data-featherlight-variant="add-site-url-model show-url">
                            <span class="dashicons dashicons-plus"></span>Add
                        </span>
                        <button class="uucss-sub-menu uucss-add-site-urls-submenu" aria-expanded="false">
                            <span class="dashicons dashicons-ellipsis"></span>
                        </button>
                    </h2>
                    <div class="content">
                        <div class="spinner spinner-history"></div>
                        <table id="uucss-history" width="100%" class="hover uucss-history-table uucss-job-history-table"></table>
                    </div>
                </li>
                <?php endif; ?>

                <?php if($rapidload->rules_enabled()) : ?>
                    <li class="uucss-history uucss-rule-history">
                        <h2>
                            Rules <a target="_blank" href="https://rapidload.zendesk.com/hc/en-us/articles/4404081180179-Rule-Based-Injection" style="font-size: 11px">Learn More</a>
                            <span class="multiple-selected-text">
                            <span class="multiple-selected-value multiple-selected-value-rule"></span>
                            Selected
                            <a href="#" id="js-uucss-clear-selection-rule">clear</a>
                        </span>
                            <span class="uucss-add-menu uucss-add-site-rule" data-featherlight="#add_rule_featherlight_content" data-featherlight-open-speed="50" data-featherlight-close-speed="50" data-featherlight-variant="add-site-rule-model">
                            <span class="dashicons dashicons-plus"></span>Add
                        </span>
                            <button class="uucss-sub-menu uucss-add-site-rule-submenu" aria-expanded="false">
                                <span class="dashicons dashicons-ellipsis"></span>
                            </button>
                        </h2>
                        <div class="content">
                            <div class="spinner spinner-history"></div>
                            <table id="uucss-rule-history" width="100%" class="hover uucss-history-table uucss-rule-history-table"></table>
                        </div>
                    </li>
                <?php endif; ?>

            <?php endif; ?>

        </ul>
    </div>

</form>

<?php
do_action('uucss/options/after_render_form');
?>

<div id="add_rule_featherlight_content" class="main-content uucss-update-form-fetherlight" >
    <div class="action-content">
        <div>
            <label for="model-uucss-rules">
                <strong>Type</strong>
            </label>
            <select id="model-uucss-rules">
                <?php
                $rules = RapidLoad_Base::get()->get_pre_defined_rules(true);

                if(isset($rules) && !empty($rules)){

                    $rules_by_category = [];

                    foreach($rules as $rule)
                    {
                        if(isset($rule['category'])){
                            $rules_by_category[$rule['category']][] = $rule;
                        }else{
                            $rules_by_category['general'][] = $rule;
                        }
                    }

                    foreach ($rules_by_category as $key => $category){

                        echo sprintf('<optgroup label="%s">', esc_html($key));

                        foreach ($category as $rule){

                            if(isset($rule['rule']) && !empty($rule['rule'])){

                                $permalink = isset($rule['permalink']) ? $rule['permalink'] : trailingslashit(get_site_url());

                                echo sprintf('<option data-type="%s" data-permalink="%s" value="%s">%s</option>', esc_attr($rule['rule']), esc_url($permalink), esc_attr($rule['rule']), esc_attr($rule['rule']));

                            }

                        }

                        echo '</optgroup>';
                    }


                }
                ?>
            </select>
        </div>
        <div>
            <label for="rule-base-url">
                <strong>Base URL</strong>
            </label>
            <input type="text" class="rule-base-url"
                   placeholder="<?php echo esc_attr(trailingslashit(get_site_url()))?>" value="<?php echo esc_attr(trailingslashit(get_site_url()))?>">
        </div>
        <div>
            <label for="rule-url-regex">
                <strong>Pattern</strong>
            </label>
            <input type="text" class="rule-url-regex"
                   placeholder="*/slug/*" value="/">
        </div>
        <div>
            <label for="orce-requeue-rule">
                <strong>Regenerate</strong>
            </label>
            <input type="checkbox" id="force-requeue-rule" style="width:20px">
        </div>
        <div class="add-action-wrap">
            <input id="model-update-rule" type="button" class="button button-primary" value="Update Rule">
        </div>
    </div>
</div>

<div id="add_url_featherlight_content" class="main-content uucss-update-form-fetherlight">
    <div class="action-content">
        <div>
            <select id="model-requeue-post-type">
                <option value="url">URL</option>
                <?php
                $include = RapidLoad_Queue::get_post_types();
                if(isset($include)){
                    foreach ($include as $value){
                        $post_object = get_post_type_object( $value );

                        if($post_object){

                            echo sprintf('<option value="%s">%s</option>', esc_attr($value), esc_html($post_object->label));

                        }

                    }
                }
                ?>
                <option value="site_map">Site Map</option>
            </select>
        </div>
        <div>
            <input type="text" class="site-map-url show" value="<?php echo esc_url(trailingslashit(get_site_url()))?>" placeholder="<?php echo esc_url(trailingslashit(get_site_url()))?>" data-site_url="<?php echo esc_url(trailingslashit(get_site_url()))?>" data-sitemap_url="<?php
            /*$robots = UnusedCSS_Admin::get_robots_text(get_site_url());
            if($robots && isset($robots->sitemap)){
                echo apply_filters('uucss/sitemap-path', $robots->sitemap);
            }else{
                echo apply_filters('uucss/sitemap-path', home_url('/sitemap_index.xml'));
            }*/
            echo esc_url(apply_filters('uucss/sitemap-path', home_url('/sitemap_index.xml')));
            ?>">
        </div>
        <div class="add-action-wrap">
            <input id="model-queue-posts-type" type="button" class="button button-primary" value="Add to Optimization">
        </div>
    </div>
</div>