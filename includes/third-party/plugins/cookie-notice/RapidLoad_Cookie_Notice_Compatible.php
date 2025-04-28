<?php

defined( 'ABSPATH' ) || exit;

if(class_exists('RapidLoad_Cookie_Notice_Compatible')){
    return;
}

class RapidLoad_Cookie_Notice_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'cookie-notice/cookie-notice.php';
        $this->catgeory = 'cache-bust';
        $this->name = 'cookie-notice';

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/cache/bust', [$this, 'rapidload_handle']);
    }

    public function rapidload_handle($args)
    {
        $args[] = [
            'type' => 'header',
            'rule' => 'User-Agent:RapidLoad'
        ];
        return $args;
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}