<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_CriticalCSS_Queue')){
    return;
}

class RapidLoad_CriticalCSS_Queue
{
    use RapidLoad_Utils;

    public function __construct()
    {
        if(RapidLoad_DB::$current_version < 1.3){
            return;
        }

        add_action('uucss/queue/task',[$this, 'rapidload_fetch_job_id'], 10);
        add_action('uucss/queue/task',[$this, 'rapidload_fetch_result'], 20);
    }

    function rapidload_fetch_job_id(){

        $current_waiting = RapidLoad_CriticalCSS_DB::rapidload_get_current_waiting_tasks_count();

        if( (RapidLoad_Queue::$job_count - $current_waiting) <= 0 ){
            return;
        }

        global $wpdb;

        $links = RapidLoad_CriticalCSS_DB::rapidload_get_current_queued_tasks_job_ids(RapidLoad_Queue::$job_count - $current_waiting);

        if(!empty($links)){

            foreach ($links as $link){

                $job = RapidLoad_Job::rapidload_job_find_or_fail($link->job_id);

                if($job){

                    $job_data = new RapidLoad_Job_Data($job, 'cpcss');

                    $store = new RapidLoad_CriticalCSS_Store($job_data, apply_filters('rapidload/purge/args', []));
                    $store->rapidload_purge_css();

                }else{

                    RapidLoad_CriticalCSS_DB::rapidload_delete_by_job_id($link->job_id);

                }

            }

        }

    }

    function rapidload_fetch_result(){

        $links = RapidLoad_CriticalCSS_DB::rapidload_get_current_processing_tasks_job_ids(RapidLoad_Queue::$job_count);

        if(!empty($links)){

            foreach ($links as $link){

                $job = RapidLoad_Job::rapidload_job_find_or_fail($link->job_id);

                if($job){

                    $job_data = new RapidLoad_Job_Data($job, 'cpcss');

                    $store = new RapidLoad_CriticalCSS_Store($job_data, []);
                    $store->rapidload_update_css();

                }else{

                    RapidLoad_CriticalCSS_DB::rapidload_delete_by_job_id($link->job_id);

                }

            }

        }

    }
}