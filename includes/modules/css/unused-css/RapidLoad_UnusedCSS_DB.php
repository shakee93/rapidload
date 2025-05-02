<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_UnusedCSS_DB extends RapidLoad_DB{

    public static function requeue_where_status($status = ''){

        global $wpdb;

        if(empty($status)){
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type='uucss'",
                    'queued'
                )
            );
        }else if($status === 'success'){
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type='uucss' AND status = 'success' AND warnings IS NOT NULL",
                    'queued'
                )
            );
        }else{
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type='uucss' AND status = %s",
                    'queued',
                    $status
                )
            );
        }

        $error = $wpdb->last_error;

        if (!empty($error)) {
            self::show_db_error($error);
        }
    }

    public static function clear_data($soft = false){

        global $wpdb;

        if($soft){
            $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type = %s", 'queued', 'uucss'));
        }else{
            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = %s", 'uucss'));
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    public static function delete_by_job_id($id){

        if(!$id){
            return;
        }

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type='uucss' AND job_id = %d", $id ) );

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    public static function get_used_files_exclude($id){

        if(!$id){
            return [];
        }

        global $wpdb;

        $result = $wpdb->get_results( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}rapidload_job_data WHERE job_type='uucss' AND status = 'success' AND job_id != %d", $id ), ARRAY_A );

        if(!empty($error)){
            self::show_db_error($error);
        }

        $used = [];

        foreach ($result as $res){

            if(isset($res) && !empty($res)){

                if(is_array($res) && isset($res['data'])){

                    $res_data = unserialize($res['data']);

                    if(!empty($res_data)){
                        $used = array_merge($used, array_column($res_data, 'uucss'));
                    }
                }
            }
        }

        return array_values(array_unique($used));
    }

    public static function get_original_file_name($path){

        $orinal_file_name = null;

        global $wpdb;

        $files_list = $wpdb->get_col( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}rapidload_job_data WHERE data IS NOT NULL AND job_type = %s", 'uucss' ) );

        foreach ($files_list as $value){

            $files = isset($value) ? unserialize($value) : [];

            foreach ($files as $file){

                if($file['uucss'] === basename($path)){
                    $orinal_file_name = $file['original'];
                    break;
                }

            }

        }

        return $orinal_file_name;
    }

    public static function get_current_waiting_tasks_count(){
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'processing' OR status = 'waiting' AND job_type = %s",
                'uucss'
            )
        );

        return (int)$count;
    }

    public static function get_current_queued_tasks_job_ids($limit = 1){
        global $wpdb;

        $job_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT job_id FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'queued' AND job_type = %s ORDER BY id DESC LIMIT %d",  
                'uucss',
                $limit
            ),
            OBJECT
        );

        return $job_ids;
    }

    public static function get_current_processing_tasks_job_ids($limit = 1){
        global $wpdb;

        $job_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT job_id FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'processing' OR status = 'waiting' AND job_type = %s ORDER BY id DESC LIMIT %d",  
                'uucss',
                $limit
            ),
            OBJECT
        );

        return $job_ids;
    }

    public static function get_success_data($limit = 1){
        global $wpdb;

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT data FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'success' AND job_type = %s ORDER BY id DESC LIMIT %d",
                'uucss',
                $limit
            ),
            OBJECT
        );

        return $data;
    }

    public static function get_data_for_gpsi_test($limit = 1){
        global $wpdb;

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, hits FROM {$wpdb->prefix}rapidload_job_data WHERE status IN('success','rule-based') AND job_type = %s ORDER BY id DESC LIMIT %d",
                'uucss',
                $limit
            ),
            OBJECT
        );

        return $data;
    }
}