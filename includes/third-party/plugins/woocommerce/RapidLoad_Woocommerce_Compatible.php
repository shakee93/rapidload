<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_Woocommerce_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'woocommerce/woocommerce.php';
        $this->catgeory = 'e-commerce';
        $this->name = 'woocommerce';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('uucss/rules', [$this, 'handle'], 50, 1);
        add_filter('uucss/url/exclude', [$this, 'exclude']);
    }

    public function handle($args)
    {
        if(function_exists('is_product')){
            $args[] = [
                'name' => 'product',
                'rule' => 'is_product',
                'category' => 'Woocommerce',
                'priority' => 5,
                'callback' => function(){
                    return is_product();
                },
            ];
        }

        if(function_exists('is_product_category')){
            $args[] = [
                'name' => 'product_category',
                'rule' => 'is_product_category',
                'category' => 'Woocommerce',
                'priority' => 5,
                'callback' => function(){
                    return is_product_category();
                },
            ];
        }

        if(function_exists('is_product_tag')){
            $args[] = [
                'name' => 'product_tag',
                'rule' => 'is_product_tag',
                'category' => 'Woocommerce',
                'priority' => 5,
                'callback' => function(){
                    return is_product_tag();
                },
            ];
        }

        return $args;
    }

    public function exclude($args){
        $url_parts = wp_parse_url( $args );

        if(isset($url_parts['query']) &&
            ( $this->str_contains($url_parts['query'], 'post_type=shop_coupon') ||
                $this->str_contains($url_parts['query'], 'post_type=shop_order')
            )
        ){
            return false;
        }

        if(function_exists('is_cart') && is_cart()){
            return false;
        }

        if(function_exists('is_checkout') && is_checkout()){
            return false;
        }

        $excludable_urls = [];

        $permalink_structure = get_option('permalink_structure');

        if(class_exists('WC_Order') && $permalink_structure){

            $order = new WC_Order();

            $excludable_urls[] = wp_parse_url($order->get_checkout_payment_url(), PHP_URL_PATH);
            $excludable_urls[] = wp_parse_url($order->get_checkout_order_received_url(), PHP_URL_PATH);
            $excludable_urls[] = wp_parse_url($order->get_view_order_url(), PHP_URL_PATH);

            foreach ($excludable_urls as $key => $excludable_url){

                if(!$this->str_contains($args, rtrim($excludable_url,"/"))){
                    unset($excludable_urls[$key]);
                }

            }

        }

        if(!empty($excludable_urls)){
            return false;
        }

        return $args;
    }

    public function is_mu_plugin()
    {
        return false;
    }
}