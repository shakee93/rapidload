<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Job{

    use RapidLoad_Utils;

    public $id;
    public $url;
    public $rule;
    public $regex;
    public $status;
    public $rule_id = null;
    public $rule_note;
    public $created_at;
    public $desktop_options;
    public $mobile_options;

    public $parent;

    public function __construct($args = [])
    {

        if(isset($args['url'])){
            $this->url = $args['url'];
        }

        if(isset($args['rule']) && isset($args['regex'])){
            $this->rule = $args['rule'];
            $this->regex = $args['regex'];
        }else{
            $this->rule = 'is_url';
            $this->regex = '/';
        }

        if(isset($args['status'])){
            $this->status = $args['status'];
        }else{
            $this->status = 'processing';
        }

        $exist = $this->exist();

        if($exist){

            $this->id = $exist->id;
            $this->url = $exist->url;
            $this->rule = $exist->rule;
            $this->regex = $exist->regex;
            $this->status = $exist->status;
            $this->rule_id = $exist->rule_id;
            $this->rule_note = $exist->rule_note;
            $this->created_at = $exist->created_at;
            $this->desktop_options = isset($exist->desktop_options) ? $exist->desktop_options : null;
            $this->mobile_options = isset($exist->mobile_options) ? $exist->mobile_options : null;
            $this->diagnose_data = isset($exist->diagnose_data) ? $exist->diagnose_data : null;

            if(isset($this->rule_id) && $this->rule_id != $this->id && $this->rule == 'is_url'){
                $this->parent = RapidLoad_Job::find_or_fail($this->rule_id);
            }

        }else{

            $this->created_at = date( "Y-m-d H:m:s", time() );
        }

    }

    public function exist($id = false){
        global $wpdb;

        $exist = null;

        if(!$id && isset($this->id)){
            $id = $this->id;
        }

        if($id){
            $exist = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_job WHERE id = %d", $id),
                OBJECT
            );
        }
        else if($this->rule == 'is_url'){
            $exist = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_job WHERE url = %s LIMIT 1", $this->url),
                OBJECT
            );
        }else{
            $exist = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_job WHERE rule = %s AND regex = %s LIMIT 1", $this->rule, $this->regex),
                OBJECT
            );
        }

        return $exist;
    }

    public function save($notify = false){

        global $wpdb;
        $data = (array) $this;

        unset($data['id']);
        unset($data['parent']);

        if(RapidLoad_DB::$current_version < 1.6){
            if(isset($data['desktop_options'])){
                unset($data['desktop_options']);
            }
            if(isset($data['mobile_options'])){
                unset($data['mobile_options']);
            }
        }

        if(RapidLoad_DB::$current_version < 1.9){
            if(isset($data['diagnose_data'])){
                unset($data['diagnose_data']);
            }
        }

        if(isset($this->id)){

            $wpdb->update(
                $wpdb->prefix . 'rapidload_job',
                $data,
                [
                    'id' => $this->id
                ]
            );

            if($notify){
                do_action('rapidload/job/updated', $this, false);
            }

        }else{

            $wpdb->insert(
                $wpdb->prefix . 'rapidload_job',
                $data
            );

            $exist = $this->exist();

            if($exist){

                $this->id = $exist->id;

                if($notify){
                    do_action('rapidload/job/updated', $this, true);
                }
            }

        }
    }

    static function find_or_fail($id){

        global $wpdb;

        $exist = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rapidload_job WHERE id = %d",
                $id
            ),
            OBJECT
        );

        if(!$exist){
            return null;
        }

        return new RapidLoad_Job([
            'url' => $exist->url
        ]);

    }

    static function find_by_url($url, $include_failed = false){

        global $wpdb;

        $exist = false;

        if(!$include_failed){
            $exist = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}rapidload_job WHERE rule = %s AND url = %s AND status IN('cached','rule-based') LIMIT 1",
                    'is_url',
                    $url
                ),
                OBJECT
            );
        }else{
            $exist = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}rapidload_job WHERE rule = %s AND url = %s LIMIT 1",
                    'is_url', 
                    $url
                ),
                OBJECT
            );
        }

        if(!$exist){
            return null;
        }

        return new RapidLoad_Job([
            'url' => $exist->url
        ]);

    }

    static function create($args){
        (new RapidLoad_Job($args))->save();
    }

    static function all(){

        return RapidLoad_DB::get_rules_where();

    }

    function delete() {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_data WHERE job_id = %d", $this->id)
        );

        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job WHERE id = %d", $this->id)
        );
    }

    function get_urls() {
        if ($this->rule == "is_url") {
            return [];
        }

        global $wpdb;

        $data = $wpdb->get_results($wpdb->prepare("SELECT url FROM {$wpdb->prefix}rapidload_job WHERE rule_id = %d AND rule = 'is_url'",$this->id),ARRAY_A);

        if (!empty($data)) {
            return array_column($data, 'url');
        }

        return [];
    }

    function attach_rule($rule_id = false , $rule = null){
        if(!$rule_id){
            $this->rule_id = NULL;
            $this->rule = 'is_url';
            $this->regex = '/';
            $this->rule_note = 'detached';
            $this->status = 'queued';
        }else{
            $this->rule_id = $rule_id;
            $this->rule_note = NULL;
            $this->status = 'rule-based';
        }

    }

    function get_desktop_options($transformed = false, $recursive = false){

        if(isset($this->desktop_options) && !empty($this->desktop_options)){
            return !$transformed ? unserialize($this->desktop_options) : $this->transform_individual_file_actions(unserialize($this->desktop_options));
        }
        if(!$recursive){
            return $this->get_mobile_options($transformed, true);
        }
        return [];
    }

    function get_mobile_options($transformed = false, $recursive = false){

        if(isset($this->mobile_options) && !empty($this->mobile_options)){
            return !$transformed ? unserialize($this->mobile_options) : $this->transform_individual_file_actions(unserialize($this->mobile_options));
        }
        if(!$recursive){
            return $this->get_desktop_options($transformed, true);
        }
        return [];
    }

    function get_diagnose_data(){
        if(isset($this->diagnose_data) && !empty($this->diagnose_data)){
            return unserialize($this->diagnose_data);
        }
        return [
            'mobile' => null,
            'desktop' => null,
        ];
    }   

    function set_desktop_options($options){

        if(isset($options) && is_array($options) && !empty($options)){
            $this->desktop_options = serialize($options);
        }else{
            $this->desktop_options = null;
        }
    }

    function set_mobile_options($options){

        if(isset($options) && is_array($options) && !empty($options)){
            $this->mobile_options = serialize($options);
        }else{
            $this->mobile_options = null;
        }
    }

    function set_diagnose_data($data){
        if(isset($data) && is_array($data) && !empty($data)){
            $this->diagnose_data = serialize($data);
        }else{
            $this->diagnose_data = serialize([
                'mobile_data' => null,
                'desktop_data' => null,
            ]);
        }
    }

    function get_optimization_revisions($strategy, $limit = 10) {
        global $wpdb;

        if (!isset($this->id)) {
            return [];
        }

        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rapidload_job_optimizations WHERE strategy = %s AND job_id = %d ORDER BY id DESC LIMIT %d OFFSET 1",$strategy,$this->id,$limit), OBJECT);

        $transformed_data = [];

        foreach ($data as $d) {
            $d->data = json_decode($d->data);

            $transformed_object = new stdClass();
            $transformed_object->id = $d->id;
            $transformed_object->created_at = $d->created_at;
            $transformed_object->data = new stdClass();
            if (isset($d->data) && isset($d->data->performance)) {
                $transformed_object->data->performance = $d->data->performance;
            }

            try {
                $date = new DateTime($d->created_at);
                $date->setTimezone(new DateTimeZone('UTC'));
                $transformed_object->timestamp = $date->getTimestamp();
            } catch (Exception $exception) {
                $transformed_object->timestamp = null;
            }

            $transformed_data[] = $transformed_object;
        }

        return $transformed_data;
    }

    function get_last_optimization_revision_hash($strategy) {
        global $wpdb;

        if (!isset($this->id)) {
            return (object)[];
        }

        $data = $wpdb->get_var(
            $wpdb->prepare("SELECT data FROM {$wpdb->prefix}rapidload_job_optimizations WHERE strategy = %s AND job_id = %d ORDER BY id DESC LIMIT 1",$strategy,$this->id)
        );

        return $data ? hash('md5', $data) : false;
    }

    function get_last_optimization_revision($strategy) {
        global $wpdb;

        if (!isset($this->id)) {
            return (object)[];
        }

        $data = $wpdb->get_var(
            $wpdb->prepare("SELECT data FROM {$wpdb->prefix}rapidload_job_optimizations WHERE strategy = %s AND job_id = %d ORDER BY id DESC LIMIT 1",$strategy, $this->id )
        );

        return $data ? json_decode($data) : false;
    }

    function get_revision_count($strategy) {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare("SELECT count(id) FROM {$wpdb->prefix}rapidload_job_optimizations WHERE strategy = %s AND job_id = %d",$strategy,$this->id)
        );
    }

    function delete_old_revision($strategy, $revision_count) {
        $revsions = $this->get_revision_ids($strategy);

        if (!empty($revsions) && count($revsions) > ($revision_count - 1)) {
            $revsions = array_slice($revsions, -($revision_count - 1));
            $revsions = array_map(function ($r) {
                return (double)$r[0];
            }, $revsions);
            $revsions = implode(",", $revsions);

            global $wpdb;
            $wpdb->query(
                $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_optimizations WHERE id NOT IN( %s ) AND strategy = %s",$revsions,$strategy)
            );
        }
    }

    function get_revision_ids($strategy) {
        global $wpdb;

        $query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}rapidload_job_optimizations WHERE strategy = %s AND job_id = %d",$strategy,$this->id);

        return $wpdb->get_results($query, ARRAY_N);
    }

    function delete_all_revisions(){
        global $wpdb;
        $id = $this->id;
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->prefix}rapidload_job_optimizations WHERE job_id = %d", $id)
        );
    }

    function transform_individual_file_actions($options){

        $files = [];

        if(isset($options['individual-file-actions']) && !empty($options['individual-file-actions'])){

            foreach ($options['individual-file-actions'] as $tag){

                foreach ($tag as $action){

                    $files[] = (object)[
                        'url' => $action->url,
                        'type' => $action->url_object->file_type->value,
                        'action' => isset($action->action) && isset($action->action->value) ? $action->action->value : (object)[],
                        'regex' => isset($action->url_object) && isset($action->url_object->regex) ? $action->url_object->regex : $this->generateUrlRegex($action->url)
                    ];

                }

            }

            $files = array_map('serialize', $files);

            $files = array_unique($files);

            $files = array_map('unserialize', $files);

            if(!empty($files)){
                $options['individual-file-actions'] = array_values($files);
            }
        }

        return $options;
    }

    function generateUrlRegex($url) {
        // Escape characters with special meanings in regex
        $urlParts = parse_url($url);
        if (isset($urlParts['path'])) {
            $path = $urlParts['path'];
        } else {
            return false; // Invalid URL
        }

        $regexPattern = null;

        $patterns = [
            /*[
                'find' => '/^https:\/\/script\.hotjar\.com\/modules\.[a-zA-Z0-9]+\.js$/',
                'regex' => '/static\.hotjar\.com\/c\/hotjar/'
            ],
            [
                'find' => '/^https:\/\/www\.gstatic\.com\/recaptcha\/releases\/[A-Za-z0-9-]+\/recaptcha__en\.js$/',
                'regex' => '/https:\/\/www\.google\.com\/recaptcha\/api\.js/'
            ],
            [
                'find' => '/^https:\/\/connect\.facebook\.net\/signals\/config\/\d+/',
                'regex' => '/https:\/\/connect\.facebook\.net\/en_US\/fbevents\.js/'
            ]*/
        ];

        $patterns = apply_filters('rapidload/optimizer/regex/js-scripts', $patterns);

        foreach ($patterns as $pattern){

            if(isset($pattern['find']) && isset($pattern['regex'])){

                if(preg_match($pattern['find'], $url)){
                    $regexPattern = $pattern['regex'];
                    break;
                }
            }
        }

        if($regexPattern){
            return $regexPattern;
        }

        // Escape characters with special meanings in regex
        $escapedPath = preg_quote($path, '/');

        // Replace placeholders for dynamic parts of the path with regex patterns
        $escapedPath = preg_replace('/\\\\\{[^\\\\]+\}/', '([^/]+)', $escapedPath);

        // Add regex anchors to match the entire path
        $regexPattern = '/' . $escapedPath . '/';

        return $regexPattern;
    }

    public static function get_all_optimizations_data_for($strategy, $start_from, $limit = 10, $s = null){

        global $wpdb;
        $data = [];

        $query = "
        SELECT t1.id, t1.job_id, t3.url, t1.strategy, t1.data AS last_data, 
               IF(t1.id != t2.id, t2.data, NULL) AS first_data, 
               t1.created_at 
        FROM  {$wpdb->prefix}rapidload_job_optimizations t1 
        LEFT JOIN  {$wpdb->prefix}rapidload_job_optimizations t2 
        ON t1.job_id = t2.job_id 
        AND t2.id = (SELECT MIN(id) FROM  {$wpdb->prefix}rapidload_job_optimizations WHERE strategy = %s AND job_id = t1.job_id) 
        LEFT JOIN {$wpdb->prefix}rapidload_job t3 
        ON t1.job_id = t3.id 
        WHERE t1.strategy = %s";

        if ($s !== null) {
            $query .= " AND t3.url LIKE %s";
        }

        $query .= " AND (t1.job_id, t1.created_at) IN (
                    SELECT job_id, MAX(created_at) 
                    FROM  {$wpdb->prefix}rapidload_job_optimizations 
                    WHERE strategy = %s 
                    GROUP BY job_id
                ) 
                ORDER BY t1.id DESC 
                LIMIT %d, %d;";

        if ($s !== null) {
            $query = $wpdb->prepare($query, $strategy, $strategy, '%' . $wpdb->esc_like($s) . '%', $strategy, $start_from, $limit);
        } else {
            $query = $wpdb->prepare($query, $strategy, $strategy, $strategy, $start_from, $limit);
        }

        $result = $wpdb->get_results($query, OBJECT);

        foreach ($result as $value) {

            $first_data = [];
            $last_data = [];

            if (isset($value->first_data)) {
                $value->first_data = json_decode($value->first_data);
                $first_data = [
                    'performance' => $value->first_data->performance,
                ];
            }

            if (isset($value->last_data)) {
                $value->last_data = json_decode($value->last_data);
                $last_data = [
                    'performance' => $value->last_data->performance,
                ];
            }

            array_push($data, [
                'id' => $value->id,
                'job_id' => $value->job_id,
                'url' => $value->url,
                'strategy' => $value->strategy,
                'last_data' => $last_data,
                'first_data' => $first_data,
                'created_at' => $value->created_at,
            ]);
        }

        return $data;
    }

    public static function get_first_and_last_optimization_score($url, $strategy) {
        global $wpdb;

        $job_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}rapidload_job WHERE url = %s LIMIT 1", $url));

        if (!$job_id) {
            return (object)[
                'first_entry' => 0,
                'last_entry' => 0,
                'first_response_time' => 0,
                'last_response_time' => 0
            ];
        }

        $first_data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, data FROM {$wpdb->prefix}rapidload_job_optimizations 
                WHERE strategy = %s 
                AND job_id = %d 
                ORDER BY id ASC LIMIT 1",
                $strategy,
                $job_id
            ),
            OBJECT
        );

        $last_data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, data FROM {$wpdb->prefix}rapidload_job_optimizations 
                WHERE strategy = %s 
                AND job_id = %d 
                ORDER BY id DESC LIMIT 1",
                $strategy,
                $job_id
            ),
            OBJECT
        );

        $first_entry = isset($first_data[0]) ? $first_data[0] : false;
        $last_entry = isset($last_data[0]) && $first_entry && $first_data[0]->id != $last_data[0]->id ? $last_data[0] : false;

        $get_response_time = function ($data) {
            $decoded_data = json_decode($data);
            if (isset($decoded_data->metrics)) {
                foreach ($decoded_data->metrics as $metric) {
                    if (isset($metric->id) && $metric->id === 'speed-index') {
                        return isset($metric->displayValue) ? $metric->displayValue : "0 ms";
                    }
                }
            }
            return 0;
        };

        return (object)[
            'first_entry' => $first_entry ? json_decode($first_entry->data)->performance : 0,
            'last_entry' => $last_entry ? json_decode($last_entry->data)->performance : 0,
            'first_response_time' => $first_entry ? $get_response_time($first_entry->data) : 0,
            'last_response_time' => $last_entry ? $get_response_time($last_entry->data) : 0,
            'first_entry_metrics' => $first_entry ? json_decode($first_entry->data)->metrics : [],
            'last_entry_metrics' => $last_entry ? json_decode($last_entry->data)->metrics : [],
        ];
    }


}