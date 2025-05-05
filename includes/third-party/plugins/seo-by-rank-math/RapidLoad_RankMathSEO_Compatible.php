<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_RankMathSEO_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'seo-by-rank-math/rank-math.php';
        $this->catgeory = 'seo';
        $this->name = 'rank-math-seo';

        parent::__construct();
    }

    public function is_mu_plugin()
    {
        return false;
    }

    public function init_hooks()
    {
        add_filter('uucss/sitemap-path', [$this, 'handle'], 10, 1);
    }

    public function handle($args)
    {
        $path = $args;
        if(function_exists('rank_math_get_sitemap_url')){
            $path = rank_math_get_sitemap_url();
        }
        return $path;
    }
}