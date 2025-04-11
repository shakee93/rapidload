<?php


class CriticalCSS_Queue
{
    use RapidLoad_Utils;

    public function __construct()
    {
        if(RapidLoad_DB::$current_version < 1.3){
            return;
        }

        add_action('uucss/queue/task',[$this, 'fetch_job_id'], 10);
        add_action('uucss/queue/task',[$this, 'fetch_result'], 20);
    }

    function fetch_job_id(){

        $current_waiting = CriticalCSS_DB::get_current_waiting_tasks_count();

        if( (RapidLoad_Queue::$job_count - $current_waiting) <= 0 ){
            return;
        }

        global $wpdb;

        $links = CriticalCSS_DB::get_current_queued_tasks_job_ids(RapidLoad_Queue::$job_count - $current_waiting);

        if(!empty($links)){

            foreach ($links as $link){

                $job = RapidLoad_Job::find_or_fail($link->job_id);

                if($job){

                    $job_data = new RapidLoad_Job_Data($job, 'cpcss');

                    $store = new CriticalCSS_Store($job_data, apply_filters('rapidload/purge/args', []));
                    $store->purge_css();

                }else{

                    CriticalCSS_DB::delete_by_job_id($link->job_id);

                }

            }

        }

    }

    function fetch_result(){

        $links = CriticalCSS_DB::get_current_processing_tasks_job_ids(RapidLoad_Queue::$job_count);

        if(!empty($links)){

            foreach ($links as $link){

                $job = RapidLoad_Job::find_or_fail($link->job_id);

                if($job){

                    $job_data = new RapidLoad_Job_Data($job, 'cpcss');

                    $store = new CriticalCSS_Store($job_data, []);
                    $store->update_css();

                }else{

                    CriticalCSS_DB::delete_by_job_id($link->job_id);

                }

            }

        }

    }
}