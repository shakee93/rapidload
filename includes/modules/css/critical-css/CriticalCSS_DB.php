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
     * Get data from the database based on where clause.
     *
     * @param string $where Additional WHERE clause conditions.
     * @return array|object|null Database results.
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

    /**
     * Get data from the database with custom select, where, limit and order.
     *
     * @param string $select Columns to select.
     * @param string $where Additional WHERE clause conditions.
     * @param int $limit Number of records to return.
     * @param string $order_by Order by clause.
     * @return array|object|null Database results.
     */
    public static function get_data($select = ' * ', $where = '', $limit = 1, $order_by = 'id DESC')
    {
        global $wpdb;

        if (empty($where)) {
            $where = " WHERE job_type='cpcss' ";
        } else {
            $where .= " AND job_type='cpcss' ";
        }

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT %5s FROM {$wpdb->prefix}rapidload_job_data %5s ORDER BY %5s LIMIT %d",
                $select,
                $where,
                $order_by,
                $limit
            ),
            OBJECT
        );

        $error = $wpdb->last_error;

        if (!empty($error)) {
            self::show_db_error($error);
        }

        return $data;
    }

    /**
     * Get data by status with limit and order.
     *
     * @param array|string $status Status or array of statuses to filter by.
     * @param int $limit Number of records to return.
     * @param string $order_by Order by clause.
     * @return array Transformed results.
     */
    public static function get_data_by_status($status, $limit = 1, $order_by = 'id DESC')
    {
        global $wpdb;

        $status = implode(",", $status);
        $status = str_replace('"', '', $status);

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rapidload_job_data WHERE job_type = 'cpcss' AND status IN(%5s) ORDER BY %5s LIMIT %d",
                $status,
                $order_by,
                $limit
            ),
            OBJECT
        );

        $error = $wpdb->last_error;

        if (!empty($error)) {
            self::show_db_error($error);
        }

        $transformed_links = array();

        if (!empty($data)) {
            foreach ($data as $value) {
                array_push($transformed_links, $value);
            }
        }

        return $transformed_links;
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
}