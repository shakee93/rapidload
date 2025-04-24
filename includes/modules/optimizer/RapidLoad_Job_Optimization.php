<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Job_Optimization
{
    use RapidLoad_Utils;

    public $id;
    public $job_id;
    public $strategy;
    public $data;
    public $created_at;

    public $job;

    public function __construct($job, $strategy, $id = null)
    {
        $this->id = $id;
        $this->job = $job;
        $this->strategy = $strategy;

        $this->job_id = $this->job->id;

        $exist = $this->rapidload_job_optimization_exist();

        if($exist){
            $this->data = $exist->data;
            $this->created_at = $exist->created_at;
        }else{
            $this->created_at = gmdate( "Y-m-d H:m:s", time() );
        }
    }

    public function rapidload_job_optimization_save($exclude = []){

        global $wpdb;
        $data = (array) $this;

        unset($data['id']);
        unset($data['job']);

        foreach ($exclude as $value){
            if(isset($data[$value])){
                unset($data[$value]);
            }
        }

        if(isset($this->id)){

            $wpdb->update(
                $wpdb->prefix . 'rapidload_job_optimizations',
                $data,
                [
                    'id' => $this->id
                ]
            );

        }else{

            $wpdb->insert(
                $wpdb->prefix . 'rapidload_job_optimizations',
                $data
            );

            $exist = $this->rapidload_job_optimization_exist();

            if($exist){

                $this->id = $exist->id;

            }

        }
    }

    public function rapidload_job_optimization_exist(){

        global $wpdb;

        if(isset($this->id)){
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}rapidload_job_optimizations WHERE id = %d LIMIT 1",
                    $this->id
                ),
                OBJECT
            );
        }

        return false;
    }

    public function rapidload_job_optimization_get_data(){
        if(isset($this->data) && !empty($this->data)){
            return unserialize($this->data);
        }
        return null;
    }

    public function rapidload_job_optimization_set_data($data){
        $this->data = json_encode($data);
    }
}