<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Revslider_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'revslider/revslider.php';
        $this->catgeory = 'image';
        $this->name = 'revslider';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('rapidload/image/exclude_from_modern_image_format', [$this, 'exclude_from_modern_images'], 10, 2);
        add_filter('uucss/enqueue/before/wrap-inline-js', [$this, 'handle_before_wrap_inline_js'], 10, 1);
    }

    public function handle_before_wrap_inline_js($snippet){

        $pattern = '/(?<!window\.)\b(revapi\d+)\b\.revolutionInit/';
        $replacement = 'window.$1.revolutionInit';

        return preg_replace($pattern, $replacement, $snippet);
    }

    public function exclude_from_modern_images($value, $src){

        if($this->str_contains($src, '/revslider/public/assets/assets/dummy.png')){
            //return true;
        }

        return $value;

    }

    public function handle($args)
    {

    }

    public function is_mu_plugin()
    {
        return false;
    }
}