<?php
defined( 'ABSPATH' ) or die();

/**
 * Class RapidLoad_FileSystem
 */

class RapidLoad_FileSystem
{
    public function put_contents( $file_location, $css , $mode = null){
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        return $wp_filesystem->put_contents($file_location, $css, $mode);
    }

    public function exists( $dir ){
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        return $wp_filesystem->exists( $dir );
    }

    public function mkdir( $dir, $mode = 0755, $recursive = true ) {

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;
    
        if ( $wp_filesystem->exists( $dir ) ) {
            return true;
        }
    
        try {
            return $wp_filesystem->mkdir( $dir, $mode );
        } catch ( Exception $exception ) {
            return false;
        }
    }

    public function is_writable( $dir ) {
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        return $wp_filesystem->is_writable( $dir );
    }

    public function is_readable( $dir ) {
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        return $wp_filesystem->is_readable( $dir );
    }

    public function delete($path, $recursive = true){
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        if($wp_filesystem->is_dir($path)){
            $wp_filesystem->delete($path, true);
        }else{
            if($wp_filesystem->exists($path)){
                $wp_filesystem->delete($path);
            }
        }
        return true;
    }

    public function size($file){
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        return $wp_filesystem->size($file);
    }

    public function get_contents($file){
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        return $wp_filesystem->get_contents($file);
    }

    public function delete_folder($dir){
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        if($wp_filesystem->is_dir($dir)){
            // Second parameter true means recursive delete
            return $wp_filesystem->delete($dir, true); 
        }
        
        return false;
    }

    public function copy($source, $destination){
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        WP_Filesystem();
    
        global $wp_filesystem;

        return $wp_filesystem->copy($source, $destination);
    }

    public function format_size_units($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = '1 byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public function get_folder_size_in_bytes($dir) {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    public function get_folder_size($dir) {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $this->format_size_units($size);
    }
}