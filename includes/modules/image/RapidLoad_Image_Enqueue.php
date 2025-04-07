<?php

class RapidLoad_Image_Enqueue
{
    use RapidLoad_Utils;

    private $job = null;

    private $dom;
    private $inject;
    private $options;
    private $file_system;
    private $settings;
    private $imgExt;
    private $cdn;
    private $strategy;

    public function __construct($job)
    {
        $this->job = $job;
        $this->file_system = new RapidLoad_FileSystem();
        $this->imgExt = ["jpg", "jpeg", "png", "webp"];
        $this->cdn = RapidLoad_Image::$image_indpoint;

        add_filter('uucss/enqueue/content/update', [$this, 'update_content'], 50);
    }

    public function update_content($state){

        self::debug_log('doing image optimization');

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

        if(!isset($this->options['uucss_exclude_above_the_fold_image_count'])){
            $this->options['uucss_exclude_above_the_fold_image_count'] = 3;
        }

        if(isset($this->options['preload_lcp_images']) && $this->options['preload_lcp_images'] === "1"){
            $this->preload_images();
        }

        $this->set_width_and_height();

        $this->lazy_load_images();

        $this->lazy_load_iframes();

        // replacing urls

        if(isset($this->options['uucss_support_next_gen_formats']) && $this->options['uucss_support_next_gen_formats'] === "1"){

            $attributes = [
                [
                    'tag' => 'img',
                    'attr' => 'src'
                ],
                [
                    'tag' => 'img',
                    'attr' => 'data-src'
                ],
                [
                    'tag' => 'img',
                    'attr' => 'data-lazyload'
                ]
            ];

            foreach ($attributes as $attribute){

                $images = $this->dom->find( $attribute['tag'] . '[' . $attribute['attr'] . ']' );

                foreach ( $images as $img ) {

                    if($this->str_contains($img->{$attribute['attr']}, RapidLoad_Image::$image_indpoint)){
                        continue;
                    }

                    if($this->is_file_excluded($img->{$attribute['attr']})){
                        continue;
                    }

                    if($this->is_file_excluded($img->{$attribute['attr']}, 'uucss_exclude_images_from_modern_images')){
                        continue;
                    }

                    if(apply_filters('rapidload/image/exclude_from_modern_image_format', false, $img->{$attribute['attr']})){
                        continue;
                    }

                    $url = $this->extractUrl($img->{$attribute['attr']});

                    $urlExt = pathinfo($url, PATHINFO_EXTENSION);

                    if (in_array($urlExt, $this->imgExt)) {

                        $data_src = 'data-rp-src';
                        $img->{$attribute['attr']} = RapidLoad_Image::get_replaced_url($url, null, $img->width, $img->height, [
                            'optimize_level' => 'lqip'
                        ]);
                        //$this->get_placeholder($img);

                        $img->$data_src = $url;
                        //unset($img->{'srcset'});

                    }

                }
            }

            $srcset_attributes = [
                [
                    'tag' => 'img',
                    'attr' => 'srcset'
                ],
                [
                    'tag' => 'img',
                    'attr' => 'data-srcset'
                ]
            ];

            foreach ($srcset_attributes as $srcset_attribute){

                $srcsets = $this->dom->find( $srcset_attribute['tag'] . '[' . $srcset_attribute['attr'] . ']' );

                if(!empty($srcsets)){

                    foreach ($srcsets as $srcset){

                        if(isset($srcset->{$srcset_attribute['attr']}) && !empty($srcset->{$srcset_attribute['attr']})){

                            $_sets = explode(",",$srcset->{$srcset_attribute['attr']});

                            if(!empty($_sets)){

                                foreach ($_sets as $set){

                                    $pattern = '/(https?:\/\/[^\s]+)\s(\d+)w/';

                                    if (preg_match_all($pattern, $set, $matches, PREG_SET_ORDER)) {
                                        foreach ($matches as $match) {
                                            if(isset($match[1]) && isset($match[2])){
                                                $url = $match[1];
                                                $width = intval($match[2]);
                                                $_replaced = RapidLoad_Image::get_replaced_url($url,RapidLoad_Image::$image_indpoint, str_replace("w", "",$width), false, ['retina' => 'ret_img']);
                                                $srcset->{$srcset_attribute['attr']} = str_replace($url . " " .  $width,$_replaced . " " . $width, $srcset->{$srcset_attribute['attr']});
                                            }
                                        }
                                    }

                                    /*$_set = explode($set," ");

                                    if(isset($_set[0]) && isset($_set[1])){

                                        $_replaced = RapidLoad_Image::get_replaced_url($_set[0],RapidLoad_Image::$image_indpoint, str_replace("w", "", $_set[1]), false, ['retina' => 'ret_img']);

                                        $srcset->srcset = str_replace($_set[0] . " " .  $_set[1],$_replaced . " " . $_set[1], $srcset->srcset);
                                    }*/

                                }

                            }

                        }

                    }

                }

            }

            $videos = $this->dom->find( 'video[poster]' );

            foreach ( $videos as $video ) {

                if($this->str_contains($video->{'poster'}, RapidLoad_Image::$image_indpoint)){
                    continue;
                }

                if($this->is_file_excluded($video->{'poster'})){
                    continue;
                }

                if($this->is_file_excluded($video->{'poster'},'uucss_exclude_images_from_modern_images')){
                    continue;
                }

                $url = $this->extractUrl($video->{'poster'});

                $urlExt = pathinfo($url, PATHINFO_EXTENSION);

                if (in_array($urlExt, $this->imgExt)) {

                    $data_src = 'data-rp-poster';
                    $video->{'poster'} = RapidLoad_Image::get_replaced_url($url, null, $video->width, $video->height, [
                        'optimize_level' => 'lqip'
                    ]);
                    //$this->get_placeholder($img);

                    $video->$data_src = $url;

                }

            }

            $data_attributes = apply_filters('rapidload/image/optimize/data_attributes', []);

            foreach ($data_attributes as $data_attribute){

                $_data_attribute = $this->dom->find( 'div[' . $data_attribute . ']' );

                if(!empty($_data_attribute) && isset($_data_attribute[0])){
                    $_data_attribute = $_data_attribute[0];
                    $_data_attribute->{$data_attribute} = RapidLoad_Image::get_replaced_url($_data_attribute->{$data_attribute}, null, null, null, ['retina' => 'ret_img']);
                }
            }

            $inline_styles = $this->dom->find('[style]');

            foreach ($inline_styles as $inline_style) {

                $style_lines = explode(";", $inline_style->style);
                $_style_lines = [];
                $background_image_found = false;
                $replace_url = "";

                foreach ($style_lines as $style_line) {

                    if (!$this->str_contains($style_line, "background")) {
                        $_style_lines[] = $style_line;
                    } else {
                        preg_match_all('/background[^;]*url[ ]?\([\'|"]?(.*?\.(?:png|jpg|jpeg|webp))/', $style_line, $matches, PREG_SET_ORDER);

                        if (!empty($matches)) {

                            foreach ($matches as $match) {

                                $style_tag = explode(":",$match[0]);

                                if(isset($style_tag[0])){
                                    $style_tag = $style_tag[0];
                                }else{
                                    continue;
                                }

                                if($style_tag === 'background'){
                                    $_style_lines[] = preg_replace('/\burl\([^)]*\)/', '', $style_line);
                                }

                                $url = $this->extractUrl($match[1]);

                                if ($this->is_file_excluded($url,'uucss_exclude_images_from_lazy_load')) {
                                    continue;
                                }

                                $urlExt = pathinfo($url, PATHINFO_EXTENSION);

                                if (in_array($urlExt, $this->imgExt)) {
                                    $background_image_found = true;
                                    $replace_url = RapidLoad_Image::get_replaced_url($url, $this->cdn);
                                    if($style_tag === "background-image"){
                                        $style_line = str_replace($match[1], $replace_url, $style_line);
                                    }
                                }
                            }

                        } else {
                            $_style_lines[] = $style_line;
                        }
                    }

                }

                if ($background_image_found && isset($this->options['uucss_lazy_load_images']) && $this->options['uucss_lazy_load_images'] === "1") {
                    $inline_style->style = implode(";", $_style_lines);
                    $inline_style->{'data-rapidload-lazy-bg'} = $replace_url; // Assuming you want to store the lazy-loaded URL
                    $inline_style->{'data-rapidload-lazy-method'} = 'viewport';
                    $inline_style->{'data-rapidload-lazy-attributes'} = 'bg';
                }

            }


            $styles = $this->dom->find( "style" );

            foreach ( $styles as $style ) {

                $url_replaced = false;

                $pattern = '/https?:\/\/[^\s]+\.(?:jpg|jpeg|png|webp)/i';
                preg_match_all($pattern, $style->innertext, $matches);

                if(isset($matches[0]) && is_array($matches[0]) && !empty($matches[0])){

                    $matches[0] = array_unique($matches[0]);

                    foreach ($matches[0] as $match){

                        $urlExt = pathinfo($match, PATHINFO_EXTENSION);

                        if($this->is_file_excluded($match,'uucss_exclude_images_from_modern_images')){
                            continue;
                        }

                        if($this->is_file_excluded($match)){
                            continue;
                        }

                        if (in_array($urlExt, $this->imgExt)) {
                            $replace_url = RapidLoad_Image::get_replaced_url($match,$this->cdn,false,false, [
                                'retina' => 'ret_img'
                            ]);
                            $style->__set('innertext', str_replace($match,$replace_url,$style->innertext));
                            $url_replaced = true;
                        }

                    }

                }

                if(!$url_replaced){
                    $parser = new \Sabberworm\CSS\Parser($style->innertext);
                    $cssDocument = $parser->parse();
                    foreach ($cssDocument->getAllValues() as $value) {
                        if( $value instanceof \Sabberworm\CSS\Value\URL){
                            $url = $this->extractUrl($value->getURL()->getString());
                            $urlExt = pathinfo($url, PATHINFO_EXTENSION);
                            if (in_array($urlExt, $this->imgExt)) {
                                $replace_url = RapidLoad_Image::get_replaced_url($url,$this->cdn);
                                $value->setURL(new \Sabberworm\CSS\Value\CSSString($replace_url));
                            }
                        }
                    }
                }

            }

        }

        return [
            'dom' => $this->dom,
            'inject' => $this->inject,
            'options' => $this->options,
            'strategy' => $this->strategy
        ];
    }

    public function preload_images(){

        $preloaded_images = $this->dom->find('link[as*=image]');

        foreach ($preloaded_images as $preloaded_image){

            if(isset($preloaded_image->as) && $preloaded_image->as === "image"){

                if(isset($preloaded_image->href)){

                    if(filter_var($preloaded_image->href, FILTER_VALIDATE_URL)){
                        $preloaded_image->href = RapidLoad_Image::get_replaced_url($preloaded_image->href, null, null, null, ['retina' => 'ret_img']);
                    }

                }

            }

        }

        $preloaded_files = isset($this->options['uucss_preload_lcp_image']) && !empty($this->options['uucss_preload_lcp_image']) ? explode("\n", $this->options['uucss_preload_lcp_image']) : [];

        $preloaded_files = apply_filters('rapidload/enqueue/preload/images', $preloaded_files, $this->job, $this->strategy);

        $preloaded_files = array_unique($preloaded_files);

        foreach ($preloaded_files as $preloaded_file){

            $preloaded_file = str_replace("\r", "", $preloaded_file);
            if(filter_var($preloaded_file, FILTER_VALIDATE_URL)){
                $preload_image = '<link rel="preload" href="' . RapidLoad_Image::get_replaced_url($preloaded_file, null, null, null, ['retina' => 'ret_img']) .'" as="image" > ';
                $title_content = $this->dom->find( 'title' )[0]->outertext;
                $this->dom->find( 'title' )[0]->__set('outertext', $title_content . $preload_image);
            }

        }

    }

    public function lazy_load_iframes(){

        if(isset($this->options['uucss_lazy_load_iframes']) && $this->options['uucss_lazy_load_iframes'] === "1"){

            $iframes = $this->dom->find( 'iframe[src]' );

            foreach ( $iframes as $index => $iframe ) {

                $parent = $iframe->parent();

                if(isset($parent) && $parent->tag === 'noscript'){
                    continue;
                }

                if ($iframe->srcdoc) {

                    if($this->is_file_excluded($iframe->srcdoc, 'uucss_exclude_iframes_from_lazy_load')){
                        continue;
                    }

                    if($this->is_file_excluded($iframe->srcdoc)){
                        continue;
                    }

                    if($this->is_youtube_iframe($iframe->srcdoc)){
                        $this->handle_youtube_iframe($iframe, $iframe->srcdoc);
                    }else{
                        $iframe->{'data-rapidload-lazy-srcdoc'} = $iframe->srcdoc;
                        $iframe->{'data-rapidload-lazy-src'} = $iframe->src ? $iframe->src : $iframe->{'data-src'};
                        $iframe->{'data-rapidload-lazy-method'} = 'viewport';
                        $iframe->{'data-rapidload-lazy-attributes'} = 'srcdoc,src';
                        unset($iframe->{'data-src'});
                        unset($iframe->{'srcdoc'});
                        unset($iframe->{'src'});
                        $iframe->loading = "lazy";
                    }


                }else{

                    if($this->is_file_excluded($iframe->src, 'uucss_exclude_iframes_from_lazy_load')){
                        continue;
                    }

                    if($this->is_file_excluded($iframe->src)){
                        continue;
                    }

                    if($this->is_youtube_iframe($iframe->src)){
                        $this->handle_youtube_iframe($iframe, $iframe->src);
                    }else{
                        $iframe->{'data-rapidload-lazy-src'} = $iframe->src ? $iframe->src : $iframe->{'data-src'};
                        $iframe->{'data-rapidload-lazy-method'} = 'viewport';
                        $iframe->{'data-rapidload-lazy-attributes'} = 'src';
                        unset($iframe->{'data-src'});
                        unset($iframe->{'src'});
                        $iframe->loading = "lazy";
                    }
                }


            }
        }
    }

    function handle_youtube_iframe($iframe, $src) {
        $video_id = $this->get_youtube_video_id($src);
        $video_poster = $this->get_youtube_poster($video_id);

        if ($video_poster) {
            $youtube_embed_url = $src;
            if (strpos($youtube_embed_url, '?') === false) {
                $youtube_embed_url .= '?autoplay=1';
            } else {
                if (strpos($youtube_embed_url, 'autoplay=') !== false) {
                    $youtube_embed_url = preg_replace('/autoplay=[0-9]*/', 'autoplay=1', $youtube_embed_url);
                } else {
                    $youtube_embed_url .= '&autoplay=1';
                }
            }
            $iframe->src = $youtube_embed_url;

            $styles = '<style>@media only screen and (max-width:768px){.rapidload-yt-play-button-' . $video_id . '{width:42px!important;height:32px!important;}}.rapidload-yt-video-container-' . $video_id . '{position: relative;top: 0;left:0;width:100%;height:100%;display:flex;justify-content:center;background-color:black}.rapidload-yt-poster-image-' . $video_id . '{display:block;}.rapidload-yt-play-button-' . $video_id . '{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:85px;height:65px;background-image:url(\'data:image/svg+xml,%3Csvg width=%2740%27 height=%2730%27 viewBox=%270%200%2040%2030%27 fill=%27none%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Crect x=%2712.1055%27 y=%278.10596%27 width=%2715.7895%27 height=%2714.2105%27 fill=%27white%27/%3E%3Cpath d=%27M31.6925 0.737305H8.30749C3.71938 0.737305 0 4.45669 0 9.04479V20.7348C0 25.3229 3.71938 29.0423 8.30749 29.0423H31.6925C36.2806 29.0423 40 25.3229 40 20.7348V9.04479C40 4.45669 36.2806 0.737305 31.6925 0.737305ZM26.0742 15.4586L15.1363 20.6753C14.8448 20.8143 14.5082 20.6018 14.5082 20.279V9.51941C14.5082 9.19195 14.8537 8.97972 15.1457 9.12774L26.0837 14.6705C26.4089 14.8353 26.4032 15.3017 26.0742 15.4586Z%27 fill=%27%23F61C0D%27/%3E%3C/svg%3E%0A\');background-size:cover;cursor:pointer;z-index: 10000;}</style>';

            $play_button = $styles . '<div class="rapidload-yt-play-button rapidload-yt-play-button-' . $video_id . '"></div>';

            $place_holder_image = RapidLoad_Image::get_replaced_url(UUCSS_PLUGIN_URL . 'assets/images/yt-placeholder.svg', null, $iframe->width, $iframe->height, ['retina' => 'ret_img']);

            $iframe->outertext = '<div class="rapidload-yt-video-container rapidload-yt-video-container-' . $video_id . '" style="width: 100%">' . $play_button . '<noscript>' . $iframe->outertext . '</noscript>' . '<img class="rapidload-yt-poster-image rapidload-yt-poster-image-' . $video_id . '" alt="' . $place_holder_image . '" src="' . $place_holder_image . '" width="' . $iframe->width . '" height="' . $iframe->height . '" data-video-id="' . $video_id . '"/></div>';
        }
    }


    function is_youtube_iframe($iframe_src) {
        $domain = wp_parse_url($iframe_src, PHP_URL_HOST);
        if (strpos($domain, 'youtube.com') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function get_youtube_video_id($embedUrl) {
        $pattern = '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/';
        preg_match($pattern, $embedUrl, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        } else {
            return false;
        }
    }

    public function get_youtube_poster($videoId) {
        if ($videoId) {
            return $this->fetch_highest_res_youtube_poster($videoId);
        }
        return false;
    }

    public function fetch_highest_res_youtube_poster($videoid) {
        $resolutions = ['maxresdefault', 'hqdefault', 'mqdefault'];
        foreach($resolutions as $res) {
            $imgUrl = "https://i.ytimg.com/vi/{$videoid}/{$res}.jpg";
            if(@getimagesize(($imgUrl)))
                return $imgUrl;
        }
    }

    public function lazy_load_images(){

        if(isset($this->options['uucss_lazy_load_images']) && $this->options['uucss_lazy_load_images'] === "1"){
            $images = $this->dom->find( 'img[src]' );

            foreach ( $images as $index => $img ) {

                if($this->is_file_excluded($img->src, 'uucss_exclude_images_from_lazy_load') || $this->is_file_excluded($img->src) || $this->is_lcp_image($img->src)){
                    $img->loading = "eager";
                    $img->decoding = "sync";
                    $img->fetchpriority = "high";
                }else if(($index + 1) <= $this->options['uucss_exclude_above_the_fold_image_count']){
                    $img->loading = "eager";
                    $img->decoding = "sync";
                    $img->fetchpriority = "high";
                }else{
                    $img->loading = "lazy";
                    $img->decoding = "async";
                    $img->fetchpriority = "low";
                }

            }
        }
    }

    public function is_lcp_image($url){

        if($this->str_contains($url, RapidLoad_Image::$image_indpoint)){

            $url = str_replace(RapidLoad_Image::$image_indpoint, "", $url);

            if (preg_match('/https:\/\/[^\s]+/', $url, $matches)) {
                if (!empty($matches[0])) {
                    $url = $matches[0];
                }

            }
        }

        $found = false;

        if(isset($this->options['uucss_exclude_above_the_fold_images']) && $this->options['uucss_exclude_above_the_fold_images'] === "1"){

            if(isset($this->options['uucss_preload_lcp_image'])){

                $images = explode("\n",$this->options['uucss_preload_lcp_image']);

                foreach($images as $image){

                    $_image = preg_replace('/-\d+x\d+/', '', $image);

                    if($this->str_contains($_image,$url)){
                        $found = true;
                        break;
                    }

                }
            }

        }

        return $found;
    }

    public function set_width_and_height(){

        $attributes = [
            [
                'tag' => 'img',
                'attr' => 'src'
            ],
            [
                'tag' => 'img',
                'attr' => 'data-src'
            ]
        ];

        if(isset($this->options['uucss_set_width_and_height']) && $this->options['uucss_set_width_and_height'] === "1"){

            foreach ($attributes as $attribute){

                $images = $this->dom->find( $attribute['tag'] . '[' . $attribute['attr'] . ']' );

                foreach ( $images as $img ) {

                    if($this->is_file_excluded($img->{$attribute['attr']},'uucss_exclude_images_from_set_width_and_height')){
                        continue;
                    }

                    if($this->is_file_excluded($img->{$attribute['attr']},'uucss_exclude_images')){
                        continue;
                    }

                    if (strpos($img->{$attribute['attr']}, 'data:image/svg+xml;base64,') === 0) {

                        $encoded_svg = str_replace("data:image/svg+xml;base64,","",$img->{$attribute['attr']});

                        $decoded_svg_data = base64_decode($encoded_svg);

                        if (preg_match('/<svg[^>]*>/i', $decoded_svg_data, $matches)) {

                            $dom = new DOMDocument();
                            $dom->loadXML($decoded_svg_data);
                            $svgElement = $dom->getElementsByTagName('svg')->item(0);

                            if ($svgElement) {

                                $width = $svgElement->getAttribute('width');
                                $height = $svgElement->getAttribute('height');

                                if (!empty($width) && !isset($img->width)) {
                                    $img->width = str_replace(array("px", "pt"),"", $width);
                                }

                                if (!empty($height) && !isset($img->height)) {
                                    $img->height = str_replace(array("px", "pt"),"", $height);
                                }
                            }

                        }

                    } else {

                        $url = $this->extractUrl($img->{$attribute['attr']});

                        $file_path = self::get_file_path_from_url($url);

                        $dimension = self::get_width_height($file_path);

                        if ($dimension && isset($dimension['width']) && isset($dimension['height'])) {

                            if (!isset($img->width)) {
                                $img->width = $dimension['width'];
                            }

                            if (!isset($img->height) || $img->height === "auto") {
                                $img->height = $this->calculateSecondImageHeight($dimension['width'],  $dimension['height'], $img->width);
                            }

                        }
                    }

                }

            }

        }

    }

    public function calculateSecondImageHeight($firstImageWidth, $firstImageHeight, $secondImageWidth) {
        if (is_numeric($firstImageWidth) && is_numeric($firstImageHeight) && $firstImageWidth > 0 && $firstImageHeight > 0 &&$firstImageHeight >= $firstImageWidth) {
            $aspectRatio = $firstImageHeight / $firstImageWidth;
            $secondImageHeight = $aspectRatio * $secondImageWidth;
            return $secondImageHeight;
        }else if (is_numeric($firstImageWidth) && is_numeric($firstImageHeight) && $firstImageHeight > 0 && $firstImageWidth > 0 && $firstImageWidth >= $firstImageHeight) {
            $aspectRatio = $firstImageWidth / $firstImageHeight;
            $secondImageHeight = $secondImageWidth / $aspectRatio;
            return $secondImageHeight;
        } else {
            return null; // Return 0 if first image width is 0 to avoid division by zero.
        }
    }

    public function extractUrl($url){

        if(!$this->isAbsolute($url)){

            $url = $this->makeURLAbsolute($url, site_url());
        }

        if (substr($url, 0, 2) === '//') {

            $completeUrl = 'https:' . $url;

            if (filter_var($completeUrl, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\/[\w-]+\.[\w.-]+/', $completeUrl)) {

                $url = $completeUrl;
            }
        }

        return $url;
    }

    function isAbsolute($url) {
        return isset(wp_parse_url($url)['host']);
    }

    function makeURLAbsolute($relative_url, $base_url) {

        $parsed_base_url = wp_parse_url($base_url);

        if (strpos($relative_url, '/') !== 0) {
            $relative_url = '/' . $relative_url;
        }

        $absolute_url = $parsed_base_url['scheme'] . '://';
        $absolute_url .= $parsed_base_url['host'];
        $absolute_url .= (isset($parsed_base_url['port'])) ? ':' . $parsed_base_url['port'] : '';
        $absolute_url .= $relative_url;

        return $absolute_url;
    }

    function get_placeholder($image)
    {
        if ($image->width && $image->height) {
            return $image->src = "data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg' viewBox%3D'0 0 $image->width $image->height'%2F%3E";
        }
        return 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw';
    }

    private function is_file_excluded($file, $option_name = 'uucss_exclude_images'){

        $exclude_files = isset($this->options[$option_name]) && !empty($this->options[$option_name]) ? explode("\n", $this->options[$option_name]) : [];

        $excluded = false;

        foreach ($exclude_files as $exclude_file){

            $exclude_file = str_replace("\r", "", $exclude_file);

            if(self::is_regex_expression($exclude_file)){

                $excluded = @preg_match($exclude_file, $file);

            }

            if(!$excluded){

                $excluded = $this->str_contains($file, $exclude_file);

            }

            if($excluded){

                break;
            }

        }

        return $excluded;
    }

    public function convertImageUrlToDataUri($imageUrl) {
        // Get image data using WordPress HTTP API
        $response = wp_remote_get($imageUrl);

        // Check for errors
        if (is_wp_error($response)) {
            return $imageUrl;
        }

        // Get response body
        $imageData = wp_remote_retrieve_body($response);

        // Get content type from headers
        $contentType = wp_remote_retrieve_header($response, 'content-type');

        // If content type not found in headers, try to detect it
        if (!$contentType) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $contentType = $finfo->buffer($imageData);
        }

        // Encode the image data in Base64
        $base64 = base64_encode($imageData);

        // Create the Data URI
        return "data:$contentType;base64,$base64";
    }


}