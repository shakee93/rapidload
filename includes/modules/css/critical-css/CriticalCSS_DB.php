<?php

defined( 'ABSPATH' ) or die();

class CriticalCSS_DB extends RapidLoad_DB{

    static function clear_data($soft = false){

        global $wpdb;

        if($soft){
            $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type = %s", 'queued', 'cpcss'));
        }else{
            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = %s", 'cpcss'));
        }

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    static function data_used_elsewhere( $id , $data){

        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data WHERE data = %s AND id != %d", $data, $id));

        $error = $wpdb->last_error;

        if(!empty($error)){
            self::show_db_error($error);
        }

        return $count;
    }

    /**
     * Requeue jobs based on where clause.
     *
     * @param string $where Additional WHERE clause conditions.
     */
    public static function requeue_where($where = '')
    {
        global $wpdb;

        if (empty($where)) {
            $where = " WHERE job_type='cpcss' ";
        } else {
            $where .= " AND job_type='cpcss' ";
        }

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 %5s",
                'queued',
                $where
            )
        );

        $error = $wpdb->last_error;

        if (!empty($error)) {
            self::show_db_error($error);
        }
    }

    /**
     * Delete job by ID.
     *
     * @param int $id Job ID to delete.
     */
    public static function delete_by_job_id($id)
    {
        if(!$id){
            return;
        }

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type='cpcss' AND job_id = %d", $id ) );

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    /**
     * Get count of tasks based on where clause.
     *
     * @param string $where Additional WHERE clause conditions.
     * @return int Number of tasks.
     */
    public static function get_task_count($where = '')
    {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data %5s",
                $where
            )
        );

        $error = $wpdb->last_error;

        if (!empty($error)) {
            self::show_db_error($error);
        }

        return (int)$count;
    }

    public static function get_current_waiting_tasks_count(){
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'processing' OR status = 'waiting' AND job_type = %s",
                'cpcss'
            )
        );

        return (int)$count;
    }

    public static function get_current_queued_tasks_job_ids($limit = 1){
        global $wpdb;

        $job_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT job_id FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'queued' AND job_type = %s ORDER BY id DESC LIMIT %d",  
                'cpcss',
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
                'cpcss',
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
                'cpcss',
                $limit
            ),
            OBJECT
        );

        return $data;
    }
}