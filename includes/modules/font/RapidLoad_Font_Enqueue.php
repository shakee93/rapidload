<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_Font_Enqueue')){
    return;
}

class RapidLoad_Font_Enqueue
{
    use RapidLoad_Utils;

    private $job = null;

    private $dom;
    private $inject;
    private $options;
    private $strategy;
    private $file_system;
    private $font_handler;

    private $frontend_data = [];

    public function __construct($job, $font_handler = null)
    {
        $this->job = $job;
        $this->file_system = new RapidLoad_FileSystem();
        $this->font_handler = $font_handler;

        add_filter('uucss/enqueue/content/update', [$this, 'rapidload_update_content'], 70);
    }

    public function rapidload_update_content($state){

        self::rapidload_util_debug_log('doing font optimization');

        if(isset($state['dom'])){
            $this->dom = $state['dom'];
        }

        if(isset($state['inject'])){
            $this->inject = $state['inject'];
        }

        if(isset($state['options'])){
            $this->options = $state['options'];
        }

        if(isset($state['strategy'])){
            $this->strategy = $state['strategy'];
        }

        $this->rapidload_add_display_swap_to_inline_styles();

        $this->rapidload_add_display_swap_to_google_fonts();

        if(isset($this->options['uucss_self_host_google_fonts']) && $this->options['uucss_self_host_google_fonts'] === "1"){

            $this->rapidload_self_host_google_fonts();

        }

        $this->rapidload_preload_font_urls();

        $this->rapidload_load_web_fonts_on_user_interaction();

        return [
            'dom' => $this->dom,
            'inject' => $this->inject,
            'options' => $this->options,
            'strategy' => $this->strategy
        ];
    }

    public function rapidload_load_web_fonts_on_user_interaction(){

        $ids = isset($this->options['web_font_loader_ids']) ?
            explode(",", $this->options['web_font_loader_ids']) :
            [];

        foreach ($ids as $id){

            $script = $this->dom->find('script[id="'. $id .'"]');
            $inlne_script = $this->dom->find('script[id="'. $id .'-after"]');

            if(isset($script[0]) && isset($inlne_script[0])){
                $script[0]->{'data-rapidload-src'} = $script[0]->src;
                unset($script[0]->src);

                $script[0]->onload = 'if(document.getElementById("'. $id .'-after")) {var newScript = document.createElement("script");
                                var inlineScript = document.createTextNode(document.getElementById("'. $id .'-after").innerHTML);
                                newScript.appendChild(inlineScript);
                                document.body.appendChild(newScript);}';

                $inlne_script[0]->__set('outertext','<noscript id="'.  $id . '-after" data-rapidload-web-font>' . $inlne_script[0]->innertext() . '</noscript>');

            }

        }

    }

    public function rapidload_preload_font_urls()
    {

        $font_urls = isset($this->options['uucss_preload_font_urls']) ?
            explode("\n", $this->options['uucss_preload_font_urls']) :
            [];

        $font_urls = apply_filters('rapidload/enqueue/preload/fonts', $font_urls, $this->job, $this->strategy);

        $font_urls = array_unique($font_urls);

        foreach ($font_urls as $url) {
            if(empty($url)){
                continue;
            }
            $extension = pathinfo(wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            $preload_font = '<link rel="preload" href="'. $url .'" as="font" fetchpriority="high" type="font/'. $extension .'" crossorigin> ';
            $title_content = $this->dom->find( 'title' )[0]->outertext;
            $this->dom->find( 'title' )[0]->__set('outertext', $title_content . $preload_font);
        }

    }

    public function rapidload_add_display_swap_to_google_fonts(){

        $google_fonts = $this->dom->find('link[href*=fonts.googleapis.com/css]');
        foreach ($google_fonts as $google_font) {
            $url = wp_parse_url($google_font->href);
            parse_str($url['query'], $q);
            $q['display'] = 'swap';
            $new_url = 'https://' . $url['host'] . $url['path'] . '?' . http_build_query($q);
            $google_font->href = $new_url;
        }

    }

    public function rapidload_add_display_swap_to_inline_styles(){

        $styles = $this->dom->find('style');
        foreach ($styles as $style){
            $inner_text = RapidLoad_Font::rapidload_add_display_swap($style->innertext);
            $style->__set('innertext', $inner_text);
        }
    }

    public function rapidload_self_host_google_fonts(){

        $google_fonts = $this->dom->find('link[href*=fonts.googleapis.com]');

        foreach ($google_fonts as $google_font) {

            if ($google_font->parentNode() && $google_font->parentNode()->tag === 'noscript') {
                continue;
            }

            $_frontend_data = [];

            $_frontend_data['href'] =  $google_font->href;

            $version = substr(md5($google_font->href), 0, 15);
            $filename = $version . ".google-font.css";

            $file_path = RapidLoad_Font::$base_dir . '/' . $filename;
            $file_url = RapidLoad_Font::$base_url . '/' . $filename;

            if (!is_file($file_path)) {
                RapidLoad_Font::rapidload_self_host_style_sheet($google_font->href, $file_path);
            }

            if (is_file($file_path)) {

                if(apply_filters('uucss/enqueue/inline/google-font', true)){
                    $content = @file_get_contents($file_path);
                    if($this->font_handler){
                        $content = $this->font_handler->rapidload_add_display_swap_to_inline_styles($content);
                    }
                    $minifier = new \MatthiasMullie\Minify\CSS();
                    $minifier->add($content);
                    $inline_style_content = sprintf('<style id="google-font-%s">%s</style>', $version, $minifier->minify());
                    $title_content = $this->dom->find( 'title' )[0]->outertext;
                    $this->dom->find( 'title' )[0]->outertext = $title_content . $inline_style_content;
                }else{
                    $google_font->href = $file_url;
                    $_frontend_data['new_href'] = $file_url;

                    if(isset($google_font->id)){
                        $google_font->{'data-id'} = $google_font->id;
                        $google_font->id = 'rapidload-google-font-' . $version;
                        $google_font->onload = null;
                        $google_font->as = null;
                        $google_font->rel = 'stylesheet';
                    }
                }

            }
        }

        $this->frontend_data['font'] = [];

        if(!empty($_frontend_data)){
            $this->frontend_data['font']['google_fonts'][] = $_frontend_data;
        }

        add_filter('rapidload/optimizer/frontend/data', function ($data){
            return array_merge($data,$this->frontend_data);
        });

        $inline_styles = $this->dom->find('style');
        $pattern = "/@import\s*'(https:\/\/fonts.googleapis.com[^']+)';/";
        foreach ($inline_styles as $inline_style){
            if (preg_match($pattern, $inline_style->innertext(), $matches)) {
                if(isset($matches[1])){
                    $googleFontsUrl = $matches[1];
                    $version = substr(md5($googleFontsUrl), 0, 15);
                    $filename = $version . ".google-font.css";

                    $file_path = RapidLoad_Font::$base_dir . '/' . $filename;

                    if (!is_file($file_path)) {
                        RapidLoad_Font::rapidload_self_host_style_sheet($googleFontsUrl, $file_path);
                    }

                    if (is_file($file_path)) {
                        $content = @file_get_contents($file_path);
                        $inline_style->__set('innertext', $content);
                    }
                }
            }
        }

        $preload_fonts = $this->dom->find(
            //'link[rel*=pre][href*=fonts.gstatic.com],link[rel*=pre][href*=fonts.googleapis.com]'
            'link[href*=fonts.googleapis.com]'
        );

        foreach ($preload_fonts as $preload_font) {
            $preload_font->remove();
        }
    }
}