<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_Cookie_Notice_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'cookie-notice/cookie-notice.php';
        $this->catgeory = 'cache-bust';
        $this->name = 'cookie-notice';

        parent::__construct();
    }

    public function init_hooks()
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