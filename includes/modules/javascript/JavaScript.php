<?php

class JavaScript
{

    use RapidLoad_Utils;

    public $options = [];

    public function __construct()
    {
        $this->options = RapidLoad_Base::fetch_options();

        add_action('uucss/options/js', [$this, 'render_options']);

        if(!isset($this->options['uucss_enable_javascript'])){
            return;
        }

        add_action('rapidload/job/handle', [$this, 'optimize_javascript'], 30, 2);

        add_action( 'admin_bar_menu', [$this, 'add_admin_bar_items' ], 90 );

        if(is_user_logged_in()){



            add_action('wp_after_load_template', function (){

                global $post;

                if(isset($post->ID)){

                    wp_enqueue_style( 'featherlight', UUCSS_PLUGIN_URL . 'assets/libs/popup/featherlight.css' );
                    wp_enqueue_style('rapidload-optimizer', UUCSS_PLUGIN_URL . 'includes/modules/javascript/assets/css/style.css', UUCSS_VERSION);

                    wp_enqueue_script( 'featherlight', UUCSS_PLUGIN_URL . 'assets/libs/popup/featherlight.js' , array( 'jquery' ) );
                    wp_register_script( 'rapidload-js-optimizer', UUCSS_PLUGIN_URL . 'includes/modules/javascript/assets/js/js-core.min.js', array(
                        'jquery',
                    ) , UUCSS_VERSION);

                    wp_localize_script( 'rapidload-js-optimizer', 'rapidload_js_optimizer', [
                        'post_id' => $post->ID
                    ] );

                    wp_enqueue_script('rapidload-js-optimizer');
                }

            });

        }
    }

    public function add_admin_bar_items($wp_admin_bar){

        global $post;

        if(isset($post->ID)){

            $wp_admin_bar->add_menu(
                array(
                    'id'     => 'rapidload_psa',
                    'parent' => 'top-secondary',
                    'title'  => '<span class="ab-item">RapidLoad Optimizer</span>',
                    'meta'   => array( 'title' => 'RapidLoad Optimizer' ),
                )
            );

        }


    }

    public function render_options($args){
        $options = $args;
        include_once 'parts/options.html.php';

    }

    public function optimize_javascript($job, $args){

        new Javascript_Enqueue($job);

    }
}