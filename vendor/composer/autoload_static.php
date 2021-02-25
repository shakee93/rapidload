<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0846b02cb01ca9de966b580779093b89
{
    public static $classMap = array (
        'Cache_Enabler_Compatible' => __DIR__ . '/../..' . '/includes/third-party/plugins/cache-enabler/Cache_Enabler_Compatible.php',
        'Cloudflare_Compatible' => __DIR__ . '/../..' . '/includes/third-party/plugins/cloudflare/Cloudflare_Compatible.php',
        'LiteSpeed_Cache_Compatible' => __DIR__ . '/../..' . '/includes/third-party/plugins/litespeed-cache/LiteSpeed_Cache_Compatible.php',
        'Optimole_WP_Compatible' => __DIR__ . '/../..' . '/includes/third-party/plugins/optimole-wp/Optimole_WP_Compatible.php',
        'RapidLoad_ThirdParty' => __DIR__ . '/../..' . '/includes/third-party/RapidLoad_ThirdParty.php',
        'UnusedCSS' => __DIR__ . '/../..' . '/includes/UnusedCSS.php',
        'UnusedCSS_Admin' => __DIR__ . '/../..' . '/includes/UnusedCSS_Admin.php',
        'UnusedCSS_Api' => __DIR__ . '/../..' . '/includes/UnusedCSS_Api.php',
        'UnusedCSS_Autoptimize' => __DIR__ . '/../..' . '/includes/Autoptimize/UnusedCSS_Autoptimize.php',
        'UnusedCSS_Autoptimize_Admin' => __DIR__ . '/../..' . '/includes/Autoptimize/UnusedCSS_Autoptimize_Admin.php',
        'UnusedCSS_Autoptimize_Onboard' => __DIR__ . '/../..' . '/includes/Autoptimize/UnusedCSS_Autoptimize_Onboard.php',
        'UnusedCSS_DB' => __DIR__ . '/../..' . '/includes/UnusedCSS_DB.php',
        'UnusedCSS_Feedback' => __DIR__ . '/../..' . '/includes/Utils/Feedback/UnusedCSS_Feedback.php',
        'UnusedCSS_FileSystem' => __DIR__ . '/../..' . '/includes/UnusedCSS_FileSystem.php',
        'UnusedCSS_Queue' => __DIR__ . '/../..' . '/includes/UnusedCSS_Queue.php',
        'UnusedCSS_Settings' => __DIR__ . '/../..' . '/includes/UnusedCSS_Settings.php',
        'UnusedCSS_Sitemap' => __DIR__ . '/../..' . '/includes/UnusedCSS_Sitemap.php',
        'UnusedCSS_Store' => __DIR__ . '/../..' . '/includes/UnusedCSS_Store.php',
        'UnusedCSS_Utils' => __DIR__ . '/../..' . '/includes/UnusedCSS_Utils.php',
        'WP_Optimize_Compatible' => __DIR__ . '/../..' . '/includes/third-party/plugins/wp-optimize/WP_Optimize_Compatible.php',
        'WP_Rocket_Compatible' => __DIR__ . '/../..' . '/includes/third-party/plugins/wp-rocket/WP_Rocket_Compatible.php',
        'simplehtmldom\\Debug' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/Debug.php',
        'simplehtmldom\\HtmlDocument' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/HtmlDocument.php',
        'simplehtmldom\\HtmlNode' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/HtmlNode.php',
        'simplehtmldom\\HtmlWeb' => __DIR__ . '/..' . '/simplehtmldom/simplehtmldom/HtmlWeb.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit0846b02cb01ca9de966b580779093b89::$classMap;

        }, null, ClassLoader::class);
    }
}
