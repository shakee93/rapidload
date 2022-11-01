<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Admin
{
    use RapidLoad_Utils;

    public function __construct()
    {
        if(is_admin()){

            add_action('wp_ajax_get_robots_text', [$this, 'get_robots_text']);
            add_action('updated_option', [$this, 'update_cloudflare_settings'], 10, 3 );
            add_action('uucss/options/after_settings_section',[$this, 'render_cloudflare_settings']);
            add_action('wp_ajax_frontend_logs', [$this, 'frontend_logs']);
            add_action( "wp_ajax_uucss_license", [ $this, 'uucss_license' ] );
        }

        add_filter('uucss/api/options', [$this, 'inject_cloudflare_settings'], 10 , 1);
        add_filter('uucss/rules', [$this, 'rapidload_rule_types'], 90 , 1);
        add_action( 'add_sitemap_to_jobs', [$this, 'add_sitemap_to_jobs'], 10, 1);
        add_action( 'updated_option', [ $this, 'clear_cache_on_option_update' ], 10, 3 );
    }

    function rapidload_rule_types($rules){

        $custom_posts = get_post_types(
            array(
                'public'   => true,
                '_builtin' => false,
            ),
            'names',
            'and'
        );

        $taxonomies = get_taxonomies([
            'public' => true
        ]);

        $rules[] = [
            'name' => 'front_page',
            'rule' => 'is_front_page',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_front_page();
            },
        ];

        $rules[] = [
            'name' => '404',
            'rule' => 'is_404',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_404();
            },
        ];

        $rules[] = [
            'name' => 'archive',
            'rule' => 'is_archive',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_archive();
            },
        ];

        foreach ($custom_posts as $key => $value){
            if($value == 'page' || $value == 'post' || $value == 'product'){
                continue;
            }
            if(( $key = array_search($value, array_column($rules, 'name')) ) === false){

                $rules[] = [
                    'name' => $value,
                    'rule' => 'is_' . $value,
                    'category' => 'Custom Post Types',
                    'priority' => 5,
                    'callback' => function() use($value){
                        return get_post_type( get_the_ID() ) == $value;
                    }
                ];
            }
        }

        foreach ($taxonomies as $key => $value){
            if(( $key = array_search($value, array_column($rules, 'name')) ) === false){

                $rules[] = [
                    'name' => $value,
                    'rule' => 'is_' . $value,
                    'category' => 'Taxonomies',
                    'priority' => 5,
                    'callback' => function() use($value){
                        return is_tax($value);
                    },
                ];
            }
        }

        $rules[] = [
            'name' => 'author',
            'rule' => 'is_author',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_author();
            },
        ];

        $rules[] = [
            'name' => 'home',
            'rule' => 'is_home',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_home();
            },
        ];

        $rules[] = [
            'name' => 'page',
            'rule' => 'is_page',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_page();
            },
        ];

        $rules[] = [
            'name' => 'post',
            'rule' => 'is_post',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_singular();
            },
        ];

        $rules[] = [
            'name' => 'search',
            'rule' => 'is_search',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_search();
            },
        ];

        $rules[] = [
            'name' => 'attachment',
            'rule' => 'is_attachment',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_attachment();
            },
        ];

        $rules[] = [
            'name' => 'single',
            'rule' => 'is_single',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_single();
            },
        ];

        $rules[] = [
            'name' => 'sticky',
            'rule' => 'is_sticky',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_sticky();
            },
        ];

        $rules[] = [
            'name' => 'paged',
            'rule' => 'is_paged',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return is_paged();
            },
        ];

        $rules[] = [
            'name' => 'path',
            'rule' => 'is_path',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => function(){
                return true;
            },
        ];

        return $rules;
    }


    public function clear_cache_on_option_update( $option, $old_value, $value ) {

        if ( $option == 'autoptimize_uucss_settings' ) {

            $needs_to_cleared = false;

            $diffs = [];
            $diffs_invert = [];

            if ( $old_value && $value ) {
                $diffs        = array_diff_key( $old_value, $value );
                $diffs_invert = array_diff_key( $value, $old_value );
            }

            if ( isset( $diffs_invert['valid_domain'] ) ) {
                unset( $diffs_invert['valid_domain'] );
            }
            if ( isset( $diffs['valid_domain'] ) ) {
                unset( $diffs['valid_domain'] );
            }

            $diffs = array_merge( $diffs, $diffs_invert );

            // if these settings are changed cache will be cleared
            if ( isset( $diffs['uucss_minify'] ) ||
                isset( $diffs['uucss_keyframes'] ) ||
                isset( $diffs['uucss_fontface'] ) ||
                isset( $diffs['uucss_analyze_javascript'] ) ||
                isset( $diffs['uucss_safelist'] ) ||
                isset( $diffs['whitelist_packs'] ) ||
                isset( $diffs['uucss_blocklist'] ) ||
                isset( $diffs['uucss_variables'] ) ) {
                $needs_to_cleared = true;
            }

            foreach ( [ 'whitelist_packs', 'uucss_safelist', 'uucss_blocklist' ] as $compare_value ) {
                if ( isset( $value[ $compare_value ] ) && isset( $old_value[ $compare_value ] ) && $old_value[ $compare_value ] !== $value[ $compare_value ] ) {
                    $needs_to_cleared = true;
                    break;
                }
            }

            if(isset( $diffs['uucss_enable_rules'] )){
                RapidLoad_DB::detach_all_rules();
            }

            if ( $needs_to_cleared ) {

                /*$this->uucss->clear_cache( null, [
                    'soft' => true
                ] );*/
            }

            RapidLoad_Base::fetch_options(false);
        }

    }

    public function uucss_license() {

        $api = new RapidLoad_Api();

        $data = $api->get( 'license', [
            'url' => $this->transform_url(get_site_url()),
            'version' => UUCSS_VERSION,
            'db_version' => RapidLoad_DB::$db_version,
            'db_version_exist' => RapidLoad_DB::$current_version
        ] );

        if ( ! is_wp_error( $data ) ) {

            if ( isset( $data->errors ) ) {
                wp_send_json_error( $data->errors[0]->detail );
            }

            if ( gettype( $data ) === 'string' ) {
                wp_send_json_error( $data );
            }

            do_action( 'uucss/license-verified' );

            wp_send_json_success( $data->data );
        }

        wp_send_json_error( 'unknown error occurred' );
    }

    public function frontend_logs(){

        $args = [];

        $args['type'] = isset($_REQUEST['type']) && !empty($_REQUEST['type']) ? $_REQUEST['type'] : 'frontend';
        $args['log'] = isset($_REQUEST['log']) && !empty($_REQUEST['log']) ? $_REQUEST['log'] : '';
        $args['url'] = isset($_REQUEST['url']) && !empty($_REQUEST['url']) ? $_REQUEST['url'] : '';

        self::log($args);

        wp_send_json_success(true);
    }

    public function inject_cloudflare_settings($data){

        $options = RapidLoad_Base::fetch_options();

        if(isset($options['cf_bot_toggle_mode']) && $options['cf_bot_toggle_mode'] == "1"){

            if(isset($options['cf_email']) && isset($options['cf_token']) && isset($options['cf_zone_id'])){
                $data['cloudflare'] = [
                    'auth_email' => $options['cf_email'],
                    'zone_id' => $options['cf_zone_id'],
                    'token' => $options['cf_token'],
                ];
            }

        }

        return $data;

    }

    public function update_cloudflare_settings( $option, $old_value, $value ){

        if ( $option != 'autoptimize_uucss_settings' ) {
            return;
        }

        if(isset($value['cf_token']) && isset($value['cf_email']) && isset($value['cf_zone_id'])){

            wp_remote_request('https://api.cloudflare.com/client/v4/zones/'. $value['cf_zone_id'] .'/settings/development_mode',[
                'method'     => 'PATCH',
                'headers' => [
                    'X-Auth-Email' => $value['cf_email'],
                    'Authorization' => 'Bearer ' . $value['cf_token'],
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode((object)[
                    'value' => isset($value['cf_is_dev_mode']) && $value['cf_is_dev_mode'] == "1" ? 'on' : 'off'
                ])
            ]);

        }

    }

    public function render_cloudflare_settings(){

        if(apply_filters('rapidload/cloudflare/bot-fight-mode/disable', true)){
            return;
        }

        $options = RapidLoad_Base::fetch_options();

        ?>

        <li>
            <h2>
                Cloudflare Settings
                <span class="uucss-toggle-section rotate">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </span>
            </h2>
            <div class="content" style="display: none; ">
                <table class="cf-table">
                    <tr>
                        <td>
                            <label for="cloudflare-dev-mode">Enable Bot toggle mode</label>
                        </td>
                        <td>
                            <input type="checkbox" name="autoptimize_uucss_settings[cf_bot_toggle_mode]" id="cf_bot_toggle_mode" value="1" <?php if(isset($options['cf_bot_toggle_mode']) && $options['cf_bot_toggle_mode'] == "1") : echo 'checked'; endif; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 150px">
                            <label for="cloudflare-api-key">Api Token</label>
                        </td>
                        <td>
                            <input type="text" name='autoptimize_uucss_settings[cf_token]' id="cf_token" style="width: 450px" value="<?php if(isset($options['cf_token'])) : echo $options['cf_token']; endif; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="cloudflare-account-email" >Account Email</label>
                        </td>
                        <td>
                            <input type="text" name="autoptimize_uucss_settings[cf_email]" id="cf_email" style="width: 350px" value="<?php if(isset($options['cf_email'])) : echo $options['cf_email']; endif; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="cloudflare-zone-id">Zone ID</label>
                        </td>
                        <td>
                            <input type="text" name="autoptimize_uucss_settings[cf_zone_id]" id="cf_zone_id" style="width: 350px" value="<?php if(isset($options['cf_zone_id'])) : echo $options['cf_zone_id']; endif; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="cloudflare-dev-mode">Development Mode</label>
                        </td>
                        <td>
                            <input type="checkbox" name="autoptimize_uucss_settings[cf_is_dev_mode]" id="cf_is_dev_mode" value="1" <?php if(isset($options['cf_is_dev_mode']) && $options['cf_is_dev_mode'] == "1") : echo 'checked'; endif; ?>>
                        </td>
                    </tr>
                </table>
            </div>
        </li>

        <?php
    }

    public function get_robots_text(){

        $robotsUrl = trailingslashit(get_site_url()) . "robots.txt";

        $robot = new stdClass();
        $robot->disAllow = [];
        $robot->allow = [];

        try {

            $fh = wp_remote_get($robotsUrl);

            if(!is_wp_error($fh) && isset($fh['body'])){

                foreach(preg_split("/((\r?\n)|(\r\n?))/", $fh['body']) as $line){

                    if (preg_match("/user-agent.*/i", $line) ){
                        $robot->userAgent = trim(explode(':', $line, 2)[1]);
                    }
                    else if (preg_match("/disallow.*/i", $line)){
                        array_push($robot->disAllow, trim(explode(':', $line, 2)[1]));
                    }
                    else if (preg_match("/^allow.*/i", $line)){
                        array_push($robot->allow, trim(explode(':', $line, 2)[1]));
                    }
                    else if(preg_match("/sitemap.*/i", $line)){
                        $robot->sitemap = trim(explode(':', $line, 2)[1]);
                    }

                }

            }

            wp_send_json_success($robot);

        }catch (Exception $ex){

            wp_send_json_error();
        }

    }

    function add_sitemap_to_jobs($url = false){

        if(!$url){

            $url = apply_filters('uucss/sitemap/default', stripslashes(get_site_url(get_current_blog_id())) . '/sitemap_index.xml');
        }

        $site_map = new RapidLoad_Sitemap();
        $urls = $site_map->process_site_map($url);

        if(isset($urls) && !empty($urls)){

            foreach ($urls as $value){

                $_url = $this->transform_url($value);

                if($this->is_url_allowed($_url)){

                    $job = new RapidLoad_Job([
                       'url' => $_url
                    ]);
                    $job->save(true);
                }

            }
        }
    }



}