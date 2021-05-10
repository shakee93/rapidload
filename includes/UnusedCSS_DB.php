<?php

defined( 'ABSPATH' ) or die();

class UnusedCSS_DB
{
    use UnusedCSS_Utils;

    static $db_version = "1.3";
    static $db_option = "rapidload_migration";
    static $current_version = "";

    static function uninitialize_site($old_site){

        if(!isset($old_site)){
            return;
        }

        self::drop();
    }

    static function initialize_site($new_site, $args){

        if(!isset($new_site)){
           return;
        }

        $error = self::create_tables($new_site->blog_id . '_');

        if(empty($error)){
            UnusedCSS_Admin::update_site_option( self::$db_option, self::$db_version );
        }
    }

    static function check_db_updates(){

        self::$current_version = UnusedCSS_Admin::get_site_option( self::$db_option );

        add_action( 'wp_initialize_site', [get_called_class(), 'initialize_site'] , 10 , 2);

        add_action('wp_uninitialize_site', [get_called_class(), 'uninitialize_site'], 10, 1);

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
            add_action( "wp_ajax_rapidload_db_update", 'UnusedCSS_DB::update_db' );
        }

        add_filter('uucss/rules/enable', function ($arg){
            return self::$current_version > 1.2;
        },90,1);
    }


	static function update_db(){

        if ( self::$current_version != self::$db_version ) {

            try{
	            $status = self::create_tables();

	            if(!empty($status)){
		            wp_send_json_error(array(
		            	'error' => $status
		            ));
	            }

	            if(!UnusedCSS_Admin::get_site_option(self::$db_option)){
		            self::seed();
	            }

                UnusedCSS_Admin::update_site_option( self::$db_option, self::$db_version );

	            wp_send_json_success([
		            'db_updated' => true
	            ]);

            }catch(Exception $e){
	            wp_send_json_error(null);
            }

        }

    }


	static function seed() {

		$maps = UnusedCSS_Admin::get_site_option( UnusedCSS_Settings::$map_key );

		if ( empty( $maps ) ) {
			return;
		}

		foreach ( $maps as $map ) {
			$data = array();
			if ( isset( $map['meta']['id'] ) ) {
				$data['job_id'] = $map['meta']['id'];
			}
			$data['url'] = $map['url'];
			if ( isset( $map['meta']['stats'] ) ) {
				$data['stats'] = serialize( $map['meta']['stats'] );
			}
			if ( isset( $map['files'] ) ) {
				$data['files'] = serialize( $map['files'] );
			}
			if ( isset( $map['meta']['warnings'] ) ) {
				$data['warnings'] = serialize( $map['meta']['warnings'] );
			}
			if ( isset( $map['meta']['review'] ) ) {
				$data['review'] = serialize( $map['meta']['review'] );
			}
			if ( isset( $map['meta']['error'] ) ) {
				$data['error'] = serialize( $map['meta']['error'] );
			}
			$data['status']     = $map['status'];
			$data['created_at'] = date( "Y-m-d H:m:s", $map['time'] );

			self::add_link( $data );
		}

		// remove old option after seeding completed
        UnusedCSS_Admin::delete_site_option( UnusedCSS_Settings::$map_key );
	}


	static function add_link( $data , $count_attempts = false) {

    	if(!$data['url']){
    		return;
	    }

		global $wpdb;

		$exist = self::get_link( $data['url'] );

		if ( $exist ) {

			if(!isset($exist['attempts']) || !is_numeric($exist['attempts'])){

                $exist['attempts'] = 0;

            }

            if($count_attempts){

                $data['attempts'] = $exist['attempts'] + 1;

                if($data['status'] == 'failed' && $data['attempts'] <= 3){

                    $data['status'] = 'queued';
                }
            }

            $data['created_at'] =  date( "Y-m-d H:m:s", time() );

			self::update( $data, array(
				'url' => $data['url']
			));

        }else{

			if($data['status'] == 'failed' || $data['status'] == 'queued'){

				$data['attempts'] = 0;

			}

			$wpdb->insert(
				$wpdb->prefix . 'rapidload_uucss_job',
				$data
			);

			$error = $wpdb->last_error;

			if(!empty($error)){
				self::show_db_error($error);
			}

		}

	}

    static function get_link($url){
        global $wpdb;

	    $link = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_job WHERE url = '" . $url . "'", OBJECT);

	    $error = $wpdb->last_error;

	    if(!empty($error)){
		    self::show_db_error($error);
	    }

	    if(!empty($link)){

		    return self::transform_link($link[0]);

	    }else{

		    return null;
	    }
    }

    static function get_rule($rule, $regex = '/'){
        global $wpdb;

        $link = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule WHERE rule = '" . $rule . "' AND regex = '" . $regex . "'", OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        if(!empty($link)){

            return self::transform_link($link[0], 'rule');

        }else{

            return null;
        }
    }

    static function get_links_by_status($status, $limit = 1, $order_by = 'id DESC'){
        global $wpdb;

        $status = implode(",", $status);

        $status = str_replace('"', '', $status);

        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_job WHERE status IN(" . $status . ") ORDER BY {$order_by} LIMIT " . $limit, OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        $transformed_links = array();

        if(!empty($links)){

            foreach ($links as $link){

                array_push($transformed_links, $link);

            }

        }

        return $transformed_links;
    }

    static function migrated(){
        $option = UnusedCSS_Admin::get_site_option(self::$db_option);
        return isset($option) && !empty($option );
    }

    static function get_total_job_count($where = ''){

        if(self::$current_version < 1.2 && strpos( $where, 'hits' ) !== false){
            return 0;
        }

        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rapidload_uucss_job {$where}");

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return (int)$count;
    }

    static function get_total_rule_count($where = ''){

        if(self::$current_version < 1.3){
            return 0;
        }

        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rapidload_uucss_rule {$where}");

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return (int)$count;
    }

    static function get_links($start_from = 0, $limit = 10, $where = '', $order_by = 'id DESC'){
        global $wpdb;

	    $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_job {$where} ORDER BY {$order_by} LIMIT {$start_from},{$limit}", OBJECT);

	    $links = array_map(function ($link){
		    return self::transform_link($link);
	    }, $links);

	    $error = $wpdb->last_error;

	    if(!empty($error)){
		    self::show_db_error($error);
	    }

	    return $links;
    }

    static function get_rules($start_from = 0, $limit = 10, $where = '', $order_by = 'id DESC'){

        if(self::$current_version < 1.3){
            return [];
        }

        global $wpdb;

        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule {$where} ORDER BY {$order_by} LIMIT {$start_from},{$limit}", OBJECT);

        $links = array_map(function ($link){
            return self::transform_link($link, 'rule');
        }, $links);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $links;
    }

    static function get_links_where($where = ''){
        global $wpdb;

        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_job {$where} ORDER BY id DESC ", OBJECT);

        $links = array_map(function ($link){
            return self::transform_link($link);
        }, $links);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $links;
    }

    static function get_rules_where($where = '', $object = false){
        global $wpdb;

        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule {$where} ORDER BY id DESC ", OBJECT);

        if(!$object){
            $links = array_map(function ($link){
                return self::transform_link($link, 'rule');
            }, $links);
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $links;
    }

    static function get_links_exclude($url){
        global $wpdb;

	    $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_job WHERE url != '" . $url . "'", OBJECT);

	    $links = array_map(function ($link){
		    return self::transform_link($link);
	    }, $links);

	    $error = $wpdb->last_error;

	    if(!empty($error)){
		    self::show_db_error($error);
	    }

	    return $links;
    }

    static function get_rules_exclude($rule, $regex = '/'){
        global $wpdb;

        $links = $wpdb->get_results(
            "SELECT id,job_id,url,stats,files,warnings,review,error,attempts,status,created_at,rule,hits
            FROM {$wpdb->prefix}rapidload_uucss_rule WHERE rule != '" . $rule . "' AND regex != '" . $regex . "'
            UNION
            SELECT id,job_id,url,stats,files,warnings,review,error,attempts,status,created_at,rule,hits
            FROM {$wpdb->prefix}rapidload_uucss_job WHERE ignore_rule = 1 AND rule != '" . $rule . "'
            ", OBJECT);

        $links = array_map(function ($link){
            return self::transform_link($link);
        }, $links);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $links;
    }

    static function transform_link($link, $rule = 'path'){

        if(empty($link)){
            return null;
        }

        $data = array();

        $data['id'] = isset($link->id) ? $link->id : null;
        $data['url'] = isset( $link->url ) ? $link->url : null;
        $data['status'] = isset( $link->status ) ? $link->status : null;
        $data['success_count'] = isset( $link->hits ) ? $link->hits : 0;

        if($rule == 'path'){
            $data['ignore_rule'] = isset( $link->ignore_rule ) ? $link->ignore_rule : 0;

            if(isset($link->rule) && !empty($link->rule) && $data['ignore_rule'] == 0){

                $appied_rule = self::get_applied_rule($link->rule, $link->url);

                if($appied_rule){
                    $link = $appied_rule;
                    $data['rule'] = $link->rule ? $link->rule : null;
                    $data['base'] = $link->url ? $link->url : null;
                }

            }
        }

        if($rule == 'rule'){
            $data['regex'] = isset( $link->regex ) ? $link->regex : '/';
        }

        $data['files'] = isset($link->files) ? unserialize($link->files) : null;
        $data['job_id'] = isset($link->job_id) ? $link->job_id : null;
        $data['meta']['id'] = isset($link->job_id) ? $link->job_id : null;
        $data['meta']['stats'] = isset($link->stats) ? unserialize($link->stats) : null;
        $data['meta']['review'] = isset($link->review) ? unserialize($link->review) : null;
        $data['meta']['warnings'] = isset($link->warnings) ? unserialize($link->warnings) : [];
        $data['meta']['error'] = isset($link->error) ? unserialize($link->error) : null;
        $data['meta']['status'] = isset( $link->status ) ? $link->status : null;
        $data['time'] = isset( $link->created_at ) ? strtotime( $link->created_at ) : null;
        $data['attempts'] = isset( $link->attempts ) ? $link->attempts : null;
        $data['rule'] = isset( $link->rule ) ? $link->rule : null;

        return $data;

    }

    static function get_applied_rule($rule, $url){

        $rules = self::get_rules_where("WHERE rule = '" . $rule . "'", true);
        $applied_rule = false;

        foreach ($rules as $rule){
            if(self::is_path_glob_matched($url,$rule->regex)){
                $applied_rule = $rule;
                break;
            }
        }

        return $applied_rule;
    }

    static function get_first_link(){
	    global $wpdb;

	    $link = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rapidload_uucss_job LIMIT 1", OBJECT );

	    $error = $wpdb->last_error;

	    if ( ! empty( $error ) ) {
		    self::show_db_error( $error );
	    }

	    if ( count( $link ) > 0 ) {
		    return self::transform_link( $link[0] );
	    }

	    return false;
    }

    static function link_exists($url){
        global $wpdb;

	    $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_job WHERE url = '" . $url . "' AND status IN('success','processing','waiting','rule-based')", OBJECT);

	    $error = $wpdb->last_error;

	    if(!empty($error)){
		    self::show_db_error($error);
	    }

	    return isset($result) && !empty($result );
    }

    static function rule_exists($rule, $regex = '/'){

        if(self::$current_version < 1.3){
            return false;
        }

        global $wpdb;

        $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule WHERE rule = '" . $rule . "' AND status IN('success','processing','waiting') AND regex = '" . $regex ."'", OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return isset($result) && !empty($result );
    }

    static function link_exists_with_error($url){
        global $wpdb;

	    $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_job WHERE url = '" . $url . "'", OBJECT);

	    $error = $wpdb->last_error;

	    if(!empty($error)){
		    self::show_db_error($error);
	    }

	    return isset($result) && !empty($result);
    }

    static function rule_exists_with_error($rule, $regex = '/'){
        global $wpdb;

        $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule WHERE rule = '" . $rule . "' AND regex = '" . $regex . "'", OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return isset($result) && !empty($result);
    }

    static function rule_exist_by_url($url){
        global $wpdb;

        $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule WHERE url = '" . $url . "'", OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return isset($result) && !empty($result);
    }

    static function delete_link($url){
        global $wpdb;

	    $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_uucss_job WHERE url = '" . $url . "'" );

	    $error = $wpdb->last_error;

	    if(!empty($error)){
		    self::show_db_error($error);
	    }
    }

    static function delete_rule($args = []){
        global $wpdb;

        if(isset($args['rule']) && isset($args['regex'])){
            $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_uucss_rule WHERE rule = '" . $args['rule'] . "' AND regex = '" . $args['regex'] . "'" );
            $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_uucss_job WHERE rule = '" . $args['rule'] . "' AND ignore_rule = 0" );
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

	static function update_status($status = 'queued', $link = false){
		global $wpdb;

		if(!$link){

			$wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_job SET status = '". $status ."' , job_id = NULL WHERE id > 0");

		}else{

			$wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_job SET status = '". $status ."' , job_id = NULL WHERE url = '" . $link . "'" );

		}

		$error = $wpdb->last_error;

		if(!empty($error)){
			self::show_db_error($error);
		}
	}

    static function update_rule_status($status = 'queued', $rule = false, $regex = '/'){
        global $wpdb;

        if(!$rule){

            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_rule SET status = '". $status ."' , job_id = NULL WHERE id > 0");

        }else{

            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_rule SET status = '". $status ."' , job_id = NULL WHERE rule = '" . $rule . "' AND regex = '" . $regex . "'" );

        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function requeue_urls($list = false){

        global $wpdb;

        if($list){

            $urls = implode("','", $list);
            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_job SET status = 'queued', job_id = NULL WHERE url IN('{$urls}')");
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function requeue_rules($list = false){

        global $wpdb;

        if($list){

            foreach ($list as $item){

                if(isset($item['rule']) && isset($item['regex'])){

                    $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_rule SET status = 'queued', job_id = NULL WHERE rule ='". $item['rule'] ."' AND regex ='" . $item['regex'] ."'" );
                }
            }
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

	static function requeue_jobs($status = 'failed'){

        global $wpdb;

        if($status == 'warnings'){

            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_job SET status = 'queued' , job_id = NULL WHERE warnings IS NOT NULL");

        }else{

            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_job SET status = 'queued' , job_id = NULL WHERE status ='{$status}'");
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function requeue_rule_jobs($status = 'failed'){

        global $wpdb;

        if($status == 'warnings'){

            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_rule SET status = 'queued' , job_id = NULL WHERE warnings IS NOT NULL");

        }else{

            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_rule SET status = 'queued' , job_id = NULL WHERE status ='{$status}'");
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function reset_attempts($link){
        global $wpdb;

        if(!$link){

            return;

        }


        $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_uucss_job SET attempts = 0 WHERE url = '" . $link . "'" );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::log($error);
        }
    }

    static function update_meta($data, $link){

        if(isset($data['warnings'])){

            $data['warnings'] = serialize($data['warnings']);

        }

        self::update($data, [
            'url' => $link
        ]);
    }

    static function update_failed($link, $error){

        $path = new UnusedCSS_Path([
            'url' => $link
        ]);

        $path->files = null;
        $path->status = 'failed';
        $path->error = serialize($error);
        $path->hits = 0;
        $path->save();

    }

    static function clear_links(){
        global $wpdb;

	    $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_uucss_job WHERE id > 0");

	    $error = $wpdb->last_error;

	    if(!empty($error)){
		    self::show_db_error($error);
	    }
    }

    static function clear_rules($soft = false, $args = []){

        if($soft){

            if(isset($args['rule']) && isset($args['regex'])){

                self::update_rule_status('queued', $args['rule'], $args['regex']);

            }else{

                self::update_rule_status();
            }

        }else{

            global $wpdb;

            $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_uucss_rule WHERE id > 0");

            $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_uucss_job WHERE rule IS NOT NULL AND ignore_rule = 0" );

            $error = $wpdb->last_error;

            if(!empty($error)){
                self::show_db_error($error);
            }

        }
    }

    static function update($data, $where){
        global $wpdb;

	    $wpdb->update(
		    $wpdb->prefix . 'rapidload_uucss_job',
		    $data,
		    $where
	    );

	    $error = $wpdb->last_error;

	    if(!empty($error)){
	    	self::show_db_error($error);
	    }
    }

    static function initialize(){
        $error = self::create_tables();

        if(!empty($error)){
        	self::show_db_error($error);
        	return;
        }

        UnusedCSS_Admin::update_site_option( self::$db_option, self::$db_version );
        UnusedCSS_Admin::delete_site_option(UnusedCSS_Settings::$map_key );
    }

    static function link_files_used_elsewhere( $link , $rule = false){

        $links = !$rule ? self::get_links_exclude($link) : self::get_rules_exclude($rule);

        $file = !$rule ? (array) self::get_link($link) : (array) self::get_rule($rule);

        $files = $file && isset($file['files']) ? $file['files'] : [];

        $used   = [];
        $unused = [];

        if($file){

            if(isset($files) && !empty($files)){

                foreach ( $files as $item ) {

                    foreach ( $links as $key => $value ) {

                        if ( isset($value['files']) && in_array( $item['uucss'], array_column( $value['files'], 'uucss' ) ) ) {
                            $used[] = $item['uucss'];
                            break;
                        }
                    }

                }

            }

            if(isset($files) && !empty($files)){

                $unused = array_column( $files, 'uucss' );

                foreach ( $used as $item ) {

                    if ( ( $key = array_search( $item, $unused ) ) !== true ) {
                        unset( $unused[ $key ] );
                    }

                }

            }
        }

        return $unused;
    }

    static function create_tables($blog_id = ''){
        global $wpdb;

        $rapidload_uucss_job = $wpdb->prefix . $blog_id . 'rapidload_uucss_job';
        $rapidload_uucss_rule = $wpdb->prefix . $blog_id . 'rapidload_uucss_rule';

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        if(self::$current_version < 1.1 && in_array($blog_id . 'rapidload_uucss_job', $wpdb->tables)){
            $index = 'url';
            $wpdb->query( "ALTER TABLE `$rapidload_uucss_job` DROP INDEX `$index`" );
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
		ignore_rule mediumint(1) NULL DEFAULT 1,
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
	) ;";

        dbDelta( $sql );
	    return $wpdb->last_error;
    }

    static function drop(){
        global $wpdb;

        $tableArray = [
            $wpdb->prefix . "rapidload_uucss_job",
            $wpdb->prefix . "rapidload_uucss_rule",
        ];

        foreach ($tableArray as $tablename) {
            $wpdb->query("DROP TABLE IF EXISTS $tablename");
        }

        if(empty($wpdb->last_error)){

            UnusedCSS_Admin::delete_site_option(self::$db_option);

		}

    }

    public static function get_rules_by_status($status = ['queued'], $limit = 1, $order_by = 'id DESC'){

        if(self::$current_version < 1.3){
            return [];
        }

        global $wpdb;

        $status = implode(",", $status);

        $status = str_replace('"', '', $status);

        $rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_uucss_rule WHERE status IN(" . $status . ") ORDER BY {$order_by} LIMIT  " . $limit, OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $rules;
    }

    static function show_db_error($message){
        self::log([
            'log' => $message,
            'type' => 'general',
            'url' => get_site_url()
        ]);
    }
}