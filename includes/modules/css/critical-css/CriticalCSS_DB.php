<?php

defined( 'ABSPATH' ) or die();

class CriticalCSS_DB extends RapidLoad_DB{

    static function clear_data($soft = false){

        global $wpdb;

        if($soft){
            $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = 'queued', queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type='cpcss'");
        }else{
            $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type='cpcss'");
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function data_used_elsewhere( $id , $data){

        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data WHERE data = '" . $data . "' AND id !=" . $id);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $count;
    }

    static function requeue_where($where = ''){

        global $wpdb;

        if(empty($where)){
            $where = " WHERE job_type='cpcss' ";
        }else{
            $where .= " AND job_type='cpcss' ";
        }

        $wpdb->query( "UPDATE {$wpdb->prefix}rapidload_job_data SET status = 'queued', queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 {$where}");

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

    }

    /**
     * Get data from the database based on where clause
     *
     * @param string $where Additional WHERE clause conditions
     * @return array|object|null Database results
     */
    public static function get_data_where($where = '')
    {
        global $wpdb;

        if (empty($where)) {
            $where = " WHERE job_type='cpcss' ";
        } else {
            $where .= " AND job_type='cpcss' ";
        }

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rapidload_job_data %5s",
                $where
            ),
            OBJECT
        );

        $error = $wpdb->last_error;

        if (!empty($error)) {
            self::show_db_error($error);
        }

        return $data;
    }

    static function get_data($select = ' * ' , $where = '', $limit = 1, $order_by = 'id DESC')
    {
        global $wpdb;

        if(empty($where)){
            $where = " WHERE job_type='cpcss' ";
        }else{
            $where .= " AND job_type='cpcss' ";
        }

        $data = $wpdb->get_results( "SELECT {$select} FROM {$wpdb->prefix}rapidload_job_data {$where} ORDER BY {$order_by} LIMIT " . round($limit, 0), OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $data;
    }

    static function get_data_by_status($status, $limit = 1, $order_by = 'id DESC'){

        global $wpdb;

        $status = implode(",", $status);

        $status = str_replace('"', '', $status);

        $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'cpcss' AND status IN(" . $status . ") ORDER BY {$order_by} LIMIT " . $limit, OBJECT);

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        $transformed_links = array();

        if(!empty($data)){

            foreach ($data as $value){

                array_push($transformed_links, $value);

            }

        }

        return $transformed_links;
    }

    static function delete_by_job_id($id){

        if(!$id){
            return;
        }

        global $wpdb;

        $wpdb->query( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type='cpcss' AND job_id = " . $id);

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function get_task_count($where = ''){

        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data {$where} ");

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return (int)$count;
    }
}