<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Queue
{
    use RapidLoad_Utils;

    public static $interval = 600;
    public static $job_count = 4;
    public static $post_types = [];

    function __construct()
    {
        $this->init();
    }

    function init(){

        global $rapidload;

        $options = RapidLoad_Base::fetch_options();

        if(isset($options['uucss_queue_interval'])){
            self::$interval = (int) $options['uucss_queue_interval'];
        }else{
            self::$interval = 60;
        }

        if(isset($options['uucss_jobs_per_queue'])){
            self::$job_count = defined('UUCSS_JOB_COUNT_PER_QUEUE') ? UUCSS_JOB_COUNT_PER_QUEUE : (int) $options['uucss_jobs_per_queue'];

            if(self::$job_count > 8){
                self::$job_count = self::$job_count / count($rapidload->modules()->active_modules());
            }
        }else{
            self::$job_count = 1;
        }

        //add_action('uucss_cron_queue', [$this, 'cache'], 2 , 1);

        add_filter( 'cron_schedules', [$this, 'uucss_process_queue_schedule'] );

        $uucss_cron = $this->cron_exist();

        if ( ! wp_next_scheduled( 'cron_uucss_process_queue' ) && !$uucss_cron) {
            wp_schedule_event( time(), 'uucss_cron_interval', 'cron_uucss_process_queue');
        }

        add_action( 'cron_uucss_process_queue', [$this ,'uucss_process_queue'] );

        self::$post_types = apply_filters('uucss/queue/post_types',array(
            'post',
            'page',
            'product',
        ));

        register_deactivation_hook( UUCSS_PLUGIN_FILE, [ $this, 'unschedule_cron' ] );
    }

    function unschedule_cron(){

        $timestamp = wp_next_scheduled( 'cron_uucss_process_queue' );
        if(isset($timestamp)){
            wp_unschedule_event( $timestamp, 'cron_uucss_process_queue' );
        }

    }

    function cron_exist($cron_name = 'cron_uucss_process_queue'){

        $cron_array = _get_cron_array();

        if(!isset($cron_array) || empty($cron_array)){
            return false;
        }

        $uucss_cron = array_column($cron_array, $cron_name);

        if(!isset($uucss_cron) || empty($uucss_cron)){
            return false;
        }

        $uucss_cron = array_shift($uucss_cron);

        if(!isset($uucss_cron) || empty($uucss_cron)){
            return false;
        }

        $uucss_cron = array_shift($uucss_cron);

        if(!isset($uucss_cron) || empty($uucss_cron)){
            return false;
        }

        return $uucss_cron;
    }

    static function get_post_types(){
        return self::$post_types;
    }

    function uucss_process_queue(){

        do_action('uucss/queue/task');

    }

    function uucss_process_queue_schedule($schedules){
        $schedules['uucss_cron_interval'] = array(
            'interval' => self::$interval,
            'display'  => __( 'uucss cron interval', 'unusedcss' ),
        );
        return $schedules;
    }
}
