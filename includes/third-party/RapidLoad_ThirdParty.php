<?php

defined( 'ABSPATH' ) || exit;

abstract class RapidLoad_ThirdParty
{
    use RapidLoad_Utils;

    public $plugin = null;
    public $catgeory = null;
    public $name = null;
    public $has_conflict = false;

    public function __construct(){

        if($this->rapidload_is_exists()){
            $this->rapidload_register_plugin();
            $this->rapidload_init_hooks();
        }
    }

    abstract public function rapidload_is_mu_plugin();

    abstract public function rapidload_init_hooks();

    public function rapidload_is_exists(){
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if(function_exists('is_plugin_active') && is_plugin_active($this->plugin) || $this->is_mu_plugin()){
            return true;
        }
        return false;
    }

    abstract public function rapidload_handle($args);

    public function rapidload_register_plugin(){

        add_filter('uucss/third-party/plugins', function ($plugins){
            $plugins[] = [
                'category' => $this->catgeory,
                'plugin' => $this->name,
                'path' => $this->plugin,
                'has_conflict' => $this->has_conflict
            ];
            return $plugins;
        }, 10, 1 );

    }

    public static function rapidload_plugin_exists($plugin){
        $third_party_lugins = apply_filters('uucss/third-party/plugins', []);
        if(empty($third_party_lugins)){
            return false;
        }
        $key = array_search($plugin, array_column($third_party_lugins, 'plugin'));
        return isset($key) && is_numeric($key);
    }

    public static function rapidload_initialize(){

        $third_party_plugins_dir = plugin_dir_path(UUCSS_PLUGIN_FILE) . '/includes/third-party/plugins';

        $class_iterator = new RecursiveTreeIterator(new RecursiveDirectoryIterator($third_party_plugins_dir, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach($class_iterator as $class_path) {

            if(substr_compare($class_path, '.php', -strlen('.php')) === 0){

                $class = str_replace('.php', '', basename($class_path));

                if(class_exists($class)){
                    new $class;
                }
            }
        }

    }

}