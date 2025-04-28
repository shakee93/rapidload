<?php

defined( 'ABSPATH' ) || exit;

if(class_exists('RapidLoad_RankMathSEO_Compatible')){
    return;
}

class RapidLoad_RankMathSEO_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'seo-by-rank-math/rank-math.php';
        $this->catgeory = 'seo';
        $this->name = 'rank-math-seo';

        parent::__construct();
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/sitemap-path', [$this, 'rapidload_handle'], 10, 1);
    }

    public function rapidload_handle($args)
    {
        $path = $args;
        if(function_exists('rank_math_get_sitemap_url')){
            $path = rank_math_get_sitemap_url();
        }
        return $path;
    }
}