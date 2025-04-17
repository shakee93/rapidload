<?php

defined( 'ABSPATH' ) or die();

abstract class RapidLoad_DB
{
    use RapidLoad_Utils;

    static $db_version = "1.9";
    static $db_option = "rapidload_migration";
    static $current_version = "1.4";
    static $map_key = 'uucss_map';

    public static function initialize_site($new_site, $args){

        if(!isset($new_site)){
            return;
        }

        $error = self::create_tables($new_site->blog_id . '_');

        if(empty($error)){
            RapidLoad_Base::update_option( self::$db_option, self::$db_version );
        }
    }

    public static function uninitialize_site($old_site){

        if(!isset($old_site)){
            return;
        }

        self::drop();
    }

    public static function drop(){
        global $wpdb;

        $tableArray = [
            $wpdb->prefix . "rapidload_uucss_job",
            $wpdb->prefix . "rapidload_uucss_rule",
            $wpdb->prefix . "rapidload_job",
            $wpdb->prefix . "rapidload_job_data",
        ];

        foreach ($tableArray as $tablename) {
            $tablename = sanitize_key($tablename);
            $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %5s", $tablename ) );
        }

        if(empty($wpdb->last_error)){

            RapidLoad_Base::delete_option(self::$db_option);

        }

    }

    public static function create_tables($blog_id = ''){
        global $wpdb;

        $rapidload_uucss_job = $wpdb->prefix . $blog_id . 'rapidload_uucss_job';
        $rapidload_uucss_rule = $wpdb->prefix . $blog_id . 'rapidload_uucss_rule';
        $rapidload_job = $wpdb->prefix . $blog_id . 'rapidload_job';
        $rapidload_job_data = $wpdb->prefix . $blog_id . 'rapidload_job_data';
        $rapidload_job_optimizations = $wpdb->prefix . $blog_id . 'rapidload_job_optimizations';

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        if(self::$current_version < 1.1 && in_array($blog_id . 'rapidload_uucss_job', $wpdb->tables)){
            $index = 'url';
            $wpdb->query("ALTER TABLE `" . esc_sql($rapidload_uucss_job) . "` DROP INDEX `" . esc_sql($index) . "`");
        }

        $sql = "CREATE TABLE $rapidload_uucss_job (
		id INT NOT NULL AUTO_INCREMENT,
		job_id INT NULL,
		rule longtext NULL,
		url longtext NOT NULL,
		stats longtext NULL,
		files longtext NULL,
		warnings longtext NULL,
		review longtext NULL,
		error longtext NULL,
		attempts mediumint(2) NULL DEFAULT 0,
		hits mediumint(3) NULL DEFAULT 0,
		rule_id INT NULL,
		rule_note longtext NULL,
		status varchar(15) NOT NULL,
		created_at TIMESTAMP NOT NULL DEFAULT NOW(),
		PRIMARY KEY  (id)
	) ;
	    CREATE TABLE $rapidload_uucss_rule (
		id INT NOT NULL AUTO_INCREMENT,
		job_id INT NULL,
		rule longtext NOT NULL,
		url longtext NOT NULL,
		regex longtext NOT NULL,
		stats longtext NULL,
		files longtext NULL,
		warnings longtext NULL,
		review longtext NULL,
		error longtext NULL,
		attempts mediumint(2) NULL,
		hits mediumint(3) NULL,
		status varchar(15) NOT NULL,
		created_at TIMESTAMP NOT NULL DEFAULT NOW(),
		PRIMARY KEY  (id)
	) ;
	    CREATE TABLE $rapidload_job (
		id INT NOT NULL AUTO_INCREMENT,
		url longtext NOT NULL,
		rule longtext NOT NULL,
		regex longtext NOT NULL,
		desktop_options longtext NULL,
		mobile_options longtext NULL,
        diagnose_data longtext NULL,
		rule_id INT NULL,
		rule_note longtext NULL,
		status varchar(15) NULL,
		created_at TIMESTAMP NOT NULL DEFAULT NOW(),
		PRIMARY KEY  (id)
	) ;
	    CREATE TABLE $rapidload_job_data (
		id INT NOT NULL AUTO_INCREMENT,
		job_id INT NOT NULL,
		job_type varchar(15) NOT NULL,
		queue_job_id INT NULL,
		data longtext NULL,
		stats longtext NULL,
		warnings longtext NULL,
		error longtext NULL,
		attempts mediumint(2) NULL,
		hits mediumint(3) NULL,
		status varchar(15) NOT NULL,
		created_at TIMESTAMP NOT NULL DEFAULT NOW(),
		PRIMARY KEY  (id),
		KEY idx_rapidload_job_data_job_id (job_id),       
		KEY idx_rapidload_job_data_job_type (job_type)            
	) ;
	    CREATE TABLE $rapidload_job_optimizations (
		id INT NOT NULL AUTO_INCREMENT,
		job_id INT NOT NULL,
		strategy varchar(15) NOT NULL,
		data longtext NULL,
		created_at TIMESTAMP NOT NULL DEFAULT NOW(),
		PRIMARY KEY  (id),
		KEY idx_rapidload_job_optimizations_job_id (job_id)          
	);";

        dbDelta( $sql );
        return $wpdb->last_error;
    }

    public static function update_db_version(){
        self::$current_version = RapidLoad_Base::get_option( self::$db_option , "0");
    }

    public static function check_db_updates(){

        add_action( 'wp_initialize_site', [get_called_class(), 'initialize_site'] , 10 , 2);

        add_action('wp_uninitialize_site', [get_called_class(), 'uninitialize_site'], 10, 1);

        if(self::$current_version < 1.5){

            self::rules_migration_two_point_zero();

        }

        if (self::$current_version  < self::$db_version ) {
            $notice = [
                'action'  => 'rapidload-db-update',
                'title'   => 'RapidLoad Power Up',
                'message' => 'Migrate your database to the latest version to enjoy optimized data handling.',

                'main_action' => [
                    'key'   => 'Update Database',
                    'value' => '#'
                ],
                'type'        => 'warning'
            ];
            self::add_advanced_admin_notice($notice);
            add_action( "wp_ajax_rapidload_db_update", 'RapidLoad_DB::update_db' );
        }

    }

    public static function rules_migration_two_point_zero(){

        try {
            global $wpdb;

            $rules = $wpdb->get_results($wpdb->prepare("SELECT url, rule, regex FROM {$wpdb->prefix}rapidload_job WHERE rule != %s ORDER BY id", 'is_url'), OBJECT);

            foreach ($rules as $rule){

                $job = new RapidLoad_Job([
                    'url' => $rule->url,
                    'rule' => $rule->rule,
                    'regex' => $rule->regex
                ]);

                $job_data = new RapidLoad_Job_Data($job, 'uucss');

                $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule WHERE rule = %s AND url = %s AND regex = %s", $job->rule, $job->url, $job->regex), OBJECT);

                if(isset($data) && $data[0]){
                    $job_data->data = $data[0]->files;
                    $job_data->stats = $data[0]->stats;
                    $job_data->warnings = $data[0]->warnings;
                    $job_data->error = $data[0]->error;
                    $job_data->status = $data[0]->status;
                    $job_data->attempts = $data[0]->attempts;
                    $job_data->hits = $data[0]->hits;
                }

                $job_data->save();

            }

        }catch (Exception $ex){

        }

    }

    public static function update_db(){

        if(!self::is_wp_cli()){
            self::verify_nonce();
        }

        if ( self::$current_version < self::$db_version ) {

            try{
                $status = self::create_tables();

                if(!empty($status)){
                    wp_send_json_error(array(
                        'error' => $status
                    ));
                }

                if(self::$current_version < 1.3){
                    self::seed();
                }

                RapidLoad_Base::update_option( self::$db_option, self::$db_version );

                wp_send_json_success([
                    'db_updated' => true
                ]);

            }catch(Exception $e){
                wp_send_json_error(null);
            }

        }

    }

    public static function seed(){

        global $wpdb;

        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rapidload_job (url, rule, regex, created_at, status) 
    SELECT url, rule, regex, created_at, %s AS status FROM {$wpdb->prefix}rapidload_uucss_rule", 'processing'));

        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rapidload_job (url, rule, regex, rule_id, created_at, status) 
    SELECT url, %s as rule, %s as regex, rule_id, created_at, %s AS status FROM {$wpdb->prefix}rapidload_uucss_job WHERE status != %s", 'is_url', '/', 'processing', 'rule-based'));

        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rapidload_job_data (job_id, job_type, attempts, hits, status, created_at) 
    SELECT id, %s as job_type, 1 as attempts, 0 as hits, %s AS status, created_at  FROM {$wpdb->prefix}rapidload_job", 'cpcss', 'queued'));
    }

    public static function migrated(){
        return true;
    }

    public static function show_db_error($message){
        self::log([
            'log' => $message,
            'type' => 'general',
            'url' => get_site_url()
        ]);
    }

    public static function initialize(){
        $error = self::create_tables();

        if(!empty($error)){
            self::show_db_error($error);
            return;
        }

        RapidLoad_Base::update_option( self::$db_option, self::$db_version );
        RapidLoad_Base::delete_option(self::$map_key );
    }

    public static function get_rule_names(){

        global $wpdb;

        $names = $wpdb->get_results($wpdb->prepare("SELECT rule FROM {$wpdb->prefix}rapidload_job WHERE rule != %s ORDER BY id", 'is_url'), ARRAY_A);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return array_unique(array_column($names, 'rule'));
    }

    public static function get_applied_rule($rule, $url){

        $rules = self::get_rules_by_rule($rule);
        $applied_rule = false;

        foreach ($rules as $rule){
            if(self::is_url_glob_matched($url,$rule->regex)){
                $applied_rule = $rule;
                break;
            }
        }

        return $applied_rule;
    }

    public static function get_all_rules(){

        global $wpdb;

        $rules = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_job WHERE rule != %s ORDER BY id DESC", 'is_url'), OBJECT);
        
        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $rules;
    }   

    public static function get_rules_by_rule($rule){
        global $wpdb;

        $rules = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_job WHERE rule = %s ORDER BY id DESC", $rule), OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $rules;
    }

    public static function rule_exists_with_error($rule, $regex = '/'){
        global $wpdb;

        $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_job WHERE rule = %s AND regex = %s", $rule, $regex), OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return isset($result) && !empty($result);
    }

    public static function clear_jobs( $type = 'all', $args = [], $ids = []){

        global $wpdb;

        if(!empty($ids)){

            $ids = implode(",", array_map('intval', $ids));
            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job WHERE id IN (%s)", $ids));

        }else{

            switch ($type){

                case 'all':{
                    $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_job");
                    break;
                }

                case 'url':{
                    if(isset($args['url'])){
                        $url = sanitize_url(wp_unslash($args['url']));
                        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job WHERE url = %s AND rule = 'is_url'", $url));
                    }else{
                        $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_job WHERE rule = 'is_url'");
                    }
                    break;
                }

                case 'rule':{
                    if(isset($args['rule']) && isset($args['regex'])){
                        
                        $rule = sanitize_text_field(wp_unslash($args['rule']));
                        $regex = sanitize_text_field(wp_unslash($args['regex']));

                        $id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}rapidload_job WHERE rule = %s AND regex = %s LIMIT 1", $rule, $regex));

                        if(!empty($id)){
                            $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}rapidload_job SET regex = '/', rule_id = NULL, status = 'processing' WHERE rule = 'is_url' AND rule_id = %d", $id));
                        }
                        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job WHERE rule = %s AND regex = %s", $rule, $regex));
                    }else{
                        $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}rapidload_job SET regex = %s, rule_id = NULL, status = %s WHERE status = %s", '/', 'processing', 'rule-based'));
                        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job WHERE rule != %s", 'is_url'));
                    }

                    break;
                }

            }

        }

        // Check for errors and handle them
        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

    }

    public static function clear_job_data( $type = 'all', $args = [], $ids = []){

        global $wpdb;

        if(!empty($ids)){

            // Sanitize and prepare the query for IDs
            $ids = implode(",", array_map('intval', $ids));
            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_id IN (%s)", $ids));

        }else{

            switch ($type){

                case 'all':{
                    $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_job_data" );
                    break;
                }

                case 'url':{
                    if(isset($args['url'])){
                        $url = sanitize_text_field(wp_unslash($args['url']));
                        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_id IN (SELECT id FROM {$wpdb->prefix}rapidload_job WHERE url = %s)", $url));
                    }else{
                        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_id IN (SELECT id FROM {$wpdb->prefix}rapidload_job WHERE rule != %s)", 'is_url' ) );
                    }
                    break;
                }

                case 'rule':{
                    if(isset($args['rule']) && isset($args['regex'])){
                        $rule = sanitize_text_field(wp_unslash($args['rule']));
                        $regex = sanitize_text_field(wp_unslash($args['regex']));

                        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_id IN (SELECT id FROM {$wpdb->prefix}rapidload_job WHERE rule = %s AND regex = %s)", $rule, $regex));
                    }else{
                        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_id IN (SELECT id FROM {$wpdb->prefix}rapidload_job WHERE rule != %s)", 'is_url' ) );
                    }
                    break;
                }

                case 'uucss':{
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = %s", 'uucss' ) );
                    break;
                }

                case 'cpcss':{
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = %s", 'cpcss' ) );
                    break;
                }
            }

        }

        // Check for errors and handle them
        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

    }

    public static function get_total_job_count($rules = false){

        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM (SELECT * FROM (SELECT 
        job.id, job.url, job.rule, job.regex, job.rule_id, job.rule_note, job.status AS job_status, job.created_at AS job_created_at,
        uucss.data AS files, uucss.stats, uucss.warnings, uucss.attempts, uucss.hits, 
        CASE WHEN job.rule = 'is_url' AND job.rule_id IS NOT NULL THEN 'rule-based' ELSE uucss.status END AS status, 
        cpcss.data AS cpcss, cpcss.stats AS cpcss_stats, cpcss.warnings AS cpcss_warnings, 
        cpcss.attempts AS cpcss_attempts, cpcss.hits AS cpcss_hits, cpcss.status AS cpcss_status 
        
        FROM (SELECT (CASE WHEN rule_id IS NOT NULL THEN rule_id ELSE id END) AS id, url, rule, regex, rule_id, rule_note, status, created_at 
        FROM {$wpdb->prefix}rapidload_job) AS job
        LEFT JOIN (SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'uucss') AS uucss ON job.id = uucss.job_id
        LEFT JOIN (SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'cpcss') AS cpcss ON job.id = cpcss.job_id) AS derived_table) AS derived_table_2 where rule %5s 'is_url' ", $rules ? '!=' : '='));

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return (int)$count;
    }

    public static function get_merged_data($rules = false, $start_from = 0, $limit = 10) {

        global $wpdb;

        $status_column = 'uucss';

        if (defined('RAPIDLOAD_CPCSS_ENABLED') && RAPIDLOAD_CPCSS_ENABLED) {
            $status_column = 'cpcss';
        }

        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM (
            SELECT 
                job.id, job.job_id, job.url, job.rule, job.regex, job.rule_id, job.rule_note, job.status AS job_status, job.created_at AS job_created_at,
                (CASE WHEN job.rule = 'is_url' THEN 0 ELSE (
                    SELECT COUNT(id) 
                    FROM {$wpdb->prefix}rapidload_job 
                    WHERE rule_id = job.id AND rule = 'is_url'
                ) END) AS applied_successful_links,
                uucss.data AS files, uucss.stats, uucss.warnings, uucss.attempts, uucss.hits, 
                CASE 
                    WHEN job.rule = 'is_url' AND job.rule_id IS NOT NULL THEN 'rule-based'
                    WHEN %5s.status IS NULL THEN 'queued'  
                    ELSE %5s.status 
                END AS status,
                cpcss.data AS cpcss, cpcss.stats AS cpcss_stats, cpcss.warnings AS cpcss_warnings, cpcss.attempts AS cpcss_attempts, cpcss.hits AS cpcss_hits, cpcss.status AS cpcss_status 
            FROM (
                SELECT 
                    (CASE WHEN rule_id IS NOT NULL THEN rule_id ELSE id END) AS id,
                    id AS job_id, url, rule, regex, rule_id, rule_note, status, created_at 
                FROM {$wpdb->prefix}rapidload_job
            ) AS job
            LEFT JOIN (
                SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'uucss'
            ) AS uucss ON job.id = uucss.job_id
            LEFT JOIN (
                SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'cpcss'
            ) AS cpcss ON job.id = cpcss.job_id
        ) AS derived_table where rule %5s 'is_url'
        ORDER BY id
        LIMIT %d, %d", 
        $status_column, 
        $status_column, 
        $rules ? '!=' : '=',
        $start_from, 
        $limit
        ), OBJECT);

        $data = array_map(function ($job) {
            return self::transform_link($job);
        }, $data);

        return $data;
    }

    public static function transform_link($link, $rule = 'path'){

        if(empty($link)){
            return null;
        }

        $data = array();

        $data['id'] = isset($link->id) ? $link->id : null;
        $data['job_id'] = isset($link->job_id) ? $link->job_id : null;
        $data['url'] = isset( $link->url ) ? $link->url : null;
        $data['regex'] = isset( $link->regex ) ? $link->regex : null;
        $data['rule'] = isset( $link->rule ) ? $link->rule : null;
        $data['rule_id'] = isset( $link->rule_id ) ? $link->rule_id : null;
        $data['job_status'] = isset( $link->job_status ) ? $link->job_status : null;
        $data['created_at'] = isset( $link->job_created_at ) ? $link->job_created_at : null;
        $data['hits'] = isset( $link->hits ) ? $link->hits : null;
        $data['applied_successful_links'] = isset( $link->applied_successful_links ) ? $link->applied_successful_links : 0;
        $data['applied_links'] = isset( $link->applied_successful_links ) ? $link->applied_successful_links : 0;
        $data['time'] = isset( $link->created_at ) ? strtotime( $link->created_at ) * 1000 : null;
        $data['status'] = isset( $link->status ) ? $link->status : null;
        if(isset($data['rule_id'])){
            $job_url = RapidLoad_Job::find_or_fail($data['rule_id']);
            $data['base'] = $job_url->url;
            if($data['rule'] === "is_url"){
                $data['status'] = 'rule-based';
            }

        }

        return apply_filters('uucss/link', $data);

    }

    public static function detach_all_rules(){

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job SET rule = %s, regex = %s, status = %s", 'is_url', null, 'queued' ) );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return true;
    }

    public static function requeueJob($id){

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s WHERE job_id = %d", 'queued', $id ) );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return true;
    }

    public static function updateUrlJobDataStatusWhere($status = 'queued', $ids = [], $wherStatus = null){

        global $wpdb;

        if (!empty($ids)) {
            $ids = implode(",", array_map('intval', $ids));
        } else {
            $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}rapidload_job WHERE rule = %s", 'is_url'));
            $ids = implode(',', array_map('intval', $ids));
        }

        if (empty($wherStatus)) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s WHERE job_id IN (%d)", $status, $ids));
        } else {
            if($wherStatus === "success"){
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s WHERE job_id IN (%d) AND status = %s AND warnings IS NOT NULL", $status, $ids, $wherStatus));
            }else{
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s WHERE job_id IN (%d) AND status = %s", $status, $ids, $wherStatus));
            }
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return true;
    }

    public static function updateRuleJobDataStatusWhere($status = 'queued', $ids = [], $wherStatus = null){

        global $wpdb;

        if (!empty($ids)) {
            $ids = implode(",", array_map('intval', $ids));
        } else {
            $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}rapidload_job WHERE rule != %s", 'is_url'));
            $ids = implode(',', array_map('intval', $ids));
        }

        if (empty($wherStatus)) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s WHERE job_id IN (%d)", $status, $ids));
        } else {
            if($wherStatus === "success"){
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s WHERE job_id IN (%d) AND status = %s AND warnings IS NOT NULL", $status, $ids, $wherStatus));
            }else{
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s WHERE job_id IN (%d) AND status = %s", $status, $ids, $wherStatus));
            }
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return true;
    }

    public static function resetHits($url){

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET hits = 0 WHERE job_id IN (SELECT id FROM {$wpdb->prefix}rapidload_job WHERE url = %s)", $url ) );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return true;
    }

    public static function resetRuleHits($id){

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET hits = 0 WHERE job_id = %d", $id ) );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return true;
    }

    public static function resetWarningHits(){

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET hits = 0 WHERE status = %s AND warnings IS NOT NULL", 'success' ) );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return true;
    }

    public static function getUrlsWithWarnings(){

        global $wpdb;

        $data = $wpdb->get_results( $wpdb->prepare( "SELECT url FROM {$wpdb->prefix}rapidload_job WHERE id IN (SELECT job_id FROM {$wpdb->prefix}rapidload_job_data WHERE status = %s AND warnings IS NOT NULL)", 'success' ), ARRAY_A );

        if(!empty($data))
        {
            return array_unique(array_column($data, 'url'));
        }

        return [];
    }

    public static function get_first_link(){

        global $wpdb;

        $first_link = false;

        $link = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM (
                        SELECT 
                            job.id, job.url, job.rule, job.regex, job.rule_id, job.rule_note, job.status as job_status, job.created_at as job_created_at,
                            uucss.data as files, uucss.stats, uucss.warnings, uucss.attempts, uucss.hits, 
                            CASE WHEN job.rule = 'is_url' AND job.rule_id IS NOT NULL THEN 'rule-based' ELSE uucss.status END AS status, 
                            cpcss.data as cpcss, cpcss.stats as cpcss_stats, cpcss.warnings as cpcss_warnings, cpcss.attempts as cpcss_attempts, 
                            cpcss.hits as cpcss_hits, cpcss.status as cpcss_status 
                        FROM {$wpdb->prefix}rapidload_job as job
                        LEFT JOIN (SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'uucss') as uucss ON job.id = uucss.job_id
                        LEFT JOIN (SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'cpcss') as cpcss ON job.id = cpcss.job_id
                    ) as derived_table
                    WHERE rule = %s LIMIT 1", 'is_url' ), OBJECT );

        $error = $wpdb->last_error;

        if ( ! empty( $error ) ) {
            self::show_db_error( $error );
        }

        if ( count( $link ) > 0 ) {
            $first_link = self::transform_link($link[0]);
        }

        if(!isset($first_link['status']) || ($first_link['status'] !== "success" && $first_link['status'] !== "failed")){
            return false;
        }

        return $first_link;

    }

    public static function flush_all_rapidload($blog_id = ''){

        global $wpdb;

        $table_prefix = $wpdb->prefix . ($blog_id !== '' && is_multisite() ? $blog_id . '_' : '');

        $tableArray = [
            $table_prefix . "rapidload_uucss_job",
            $table_prefix . "rapidload_uucss_rule",
            $table_prefix . "rapidload_job_optimizations",
            $table_prefix . "rapidload_job_data",
            $table_prefix . "rapidload_job",
        ];

        foreach ($tableArray as $tablename) {
            $tablename = sanitize_key($tablename);
            $wpdb->query( $wpdb->prepare( "DELETE FROM %5s", $tablename ) );
        }

        $option_table = $table_prefix . "options";

        $options_to_delete = [
            'autoptimize_uucss_settings',
            'rapidload_cache',
            'rapidload_module_cache',
            'rapidload_module_cdn',
            'rapidload_module_css',
            'rapidload_module_font',
            'rapidload_module_js',
            'rapidload_module_image',
            'rapidload_module_titan',
            'rapidload_titan_gear',
        ];

        foreach ($options_to_delete as $option_name) {
            $wpdb->query( $wpdb->prepare( "DELETE FROM %5s WHERE option_name = %s", $option_table, $option_name ) );
        }

        delete_option('rapidload_license_data');
        delete_option('rapidload_onboard_skipped');
    }

    public static function get_optimization_count(){

        global $wpdb;

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rapidload_job" );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return (int)$count;
    }
}