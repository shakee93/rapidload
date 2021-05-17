<?php

defined( 'ABSPATH' ) || exit;

class Woocommerce_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'woocommerce/woocommerce.php';
        $this->catgeory = 'e-commerce';
        $this->name = 'woocommerce';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('uucss/rules', [$this, 'handle'], 50, 1);
    }

    public function handle($args)
    {
        $args[] = [
            'name' => 'product',
            'rule' => 'is_product',
            'category' => 'Standard Conditional Tags',
            'priority' => 10,
            'callback' => is_product(),
        ];

        return $args;
    }

    public function is_mu_plugin()
    {
        return false;
    }
}