<?php

defined( 'ABSPATH' ) or die();

class CriticalCSS_DB extends RapidLoad_DB{

    public static function clear_data($soft = false){

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

    public static function requeue_where_status($status = ''){

        global $wpdb;

        if(empty($status)){
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type='cpcss'",
                    'queued'
                )
            );
        }else if($status == 'success'){
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type='cpcss' AND status = 'success' AND warnings IS NOT NULL",
                    'queued'
                )
            );
        }else{
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}rapidload_job_data SET status = %s, queue_job_id = NULL, data = NULL, stats = NULL, warnings = NULL, error = NULL, hits = 0 WHERE job_type='cpcss' AND status = %s",
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

    /**
     * Delete job by ID.
     *
     * @param int $id Job ID to delete.
     */
    public static function delete_by_job_id($id){

        if(!$id){
            return;
        }

        global $wpdb;

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_type='cpcss' AND job_id = %d", $id ) );

        if(!empty($error)){
            self::show_db_error($error);
        }
    }

    public static function get_current_waiting_tasks_count(){
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(id) FROM {$wpdb->prefix}rapidload_job_data WHERE (status = 'processing' OR status = 'waiting') AND job_type = %s",
                'cpcss'
            )
        );

        $error = $wpdb->last_error;
        if (!empty($error)) {
            self::show_db_error($error);
            return 0;
        }

        return (int)$count;
    }

    public static function get_current_queued_tasks_job_ids(int $limit = 1){
        global $wpdb;

        $job_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT job_id FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'queued' AND job_type = %s ORDER BY id DESC LIMIT %d",  
                'cpcss',
                $limit
            ),
            OBJECT
        );

        $error = $wpdb->last_error;
        if (!empty($error)) {
            self::show_db_error($error);
            return [];
        }

        return $job_ids;
    }

    public static function get_current_processing_tasks_job_ids(int $limit = 1){
        global $wpdb;

        $job_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT job_id FROM {$wpdb->prefix}rapidload_job_data WHERE (status = 'processing' OR status = 'waiting') AND job_type = %s ORDER BY id DESC LIMIT %d",  
                'cpcss',
                $limit
            ),
            OBJECT
        );

        $error = $wpdb->last_error;
        if (!empty($error)) {
            self::show_db_error($error);
            return [];
        }

        return $job_ids;
    }

    public static function get_success_data(int $limit = 1) {
        global $wpdb;

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT data FROM {$wpdb->prefix}rapidload_job_data WHERE status = 'success' AND job_type = %s ORDER BY id DESC LIMIT %d",
                'cpcss',
                $limit
            ),
            OBJECT
        );

        $error = $wpdb->last_error;
        if (!empty($error)) {
            self::show_db_error($error);
            return [];
        }

        return $data;
    }
}