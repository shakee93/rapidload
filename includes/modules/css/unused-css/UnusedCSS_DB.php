<?php

defined( 'ABSPATH' ) or die();

class UnusedCSS_DB extends RapidLoad_DB{

    static function requeue_where($where = ''){

        global $wpdb;

        if(empty($where)){
            $where = " WHERE job_type='uucss' ";
        }else{
            $where .= " AND job_type='uucss' ";
        }

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 {$where}", 'queued' ) );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

    }

    static function clear_data($soft = false){

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

    static function get_data_where($where = '')
    {
        global $wpdb;

        if(empty($where)){
            $where = " WHERE job_type='uucss' ";
        }else{
            $where .= " AND job_type='uucss' ";
        }

        $data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rapidload_job_data {$where} "), OBJECT  );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $data;
    }

    static function get_data($select = ' * ' , $where = '', $limit = 1, $order_by = 'id DESC')
    {
        global $wpdb;

        if(empty($where)){
            $where = " WHERE job_type='uucss' ";
        }else{
            $where .= " AND job_type='uucss' ";
        }

        $data = $wpdb->get_results( $wpdb->prepare( "SELECT {$select} FROM {$wpdb->prefix}rapidload_job_data {$where} ORDER BY {$order_by} LIMIT %d", round($limit, 0)), OBJECT );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $data;
    }

    static function get_task_count($where = ''){

        global $wpdb;

        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data {$where} " ) );

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return (int)$count;
    }

    static function delete_by_job_id($id){

        if(!$id){
            return;
        }

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type='uucss' AND job_id = %d", $id ) );

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function get_used_files_exclude($id){

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

    static function get_original_file_name($path){

        $orinal_file_name = null;

        global $wpdb;

        $files_list = $wpdb->get_col( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}rapidload_job_data WHERE data IS NOT NULL AND job_type = %s", 'uucss' ) );

        foreach ($files_list as $value){

            $files = isset($value) ? unserialize($value) : [];

            foreach ($files as $file){

                if($file['uucss'] == basename($path)){
                    $orinal_file_name = $file['original'];
                    break;
                }

            }

        }

        return $orinal_file_name;
    }
}