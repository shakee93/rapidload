<?php

defined( 'ABSPATH' ) or die();

use MatthiasMullie\Minify;
use Peast\Peast;
use Peast\Query;
use Peast\Traverser;
use Peast\Renderer;
use Peast\Formatter\PrettyPrint;
use Peast\Formatter\Compact;

class RapidLoad_Javascript_Enqueue
{
    use RapidLoad_Utils;

    private $job = null;

    private $dom;
    private $global_scripts;
    private $inject;
    private $options;
    private $strategy;
    private $file_system;
    private $settings;
    private $default_inline_js_exclusion_pattern;
    private $default_js_exclusion_pattern;
    private $dynamic_exclusions;

    private $frontend_data = [];

    public function __construct($job)
    {
        $this->job = $job;
        $this->file_system = new RapidLoad_FileSystem();
        $this->init();

        add_filter('uucss/enqueue/content/update', [$this, 'update_content'], 60);
    }

    public function init(){

        $this->default_inline_js_exclusion_pattern = "";
        $this->default_js_exclusion_pattern = "";
        $default_inline_js_exclusion_list = $this->get_default_inline_js_exclusions();
        $default_js_exclusion_list = $this->get_default_js_exclusions();

        foreach ($default_inline_js_exclusion_list as $exclusion){
            $this->default_inline_js_exclusion_pattern .= preg_quote( (string) $exclusion, '#' ) . '|';
        }

        $this->default_inline_js_exclusion_pattern = rtrim( $this->default_inline_js_exclusion_pattern, '|' );

        foreach ($default_js_exclusion_list as $exclusion){
            $this->default_js_exclusion_pattern .= str_replace( '#', '\#', $exclusion ) . '|';
        }

        $this->default_js_exclusion_pattern = rtrim( $this->default_js_exclusion_pattern, '|' );

        $this->dynamic_exclusions = apply_filters('rapidload/js/excluded-files', [], 'uucss_excluded_js_files');

    }

    public function update_content($state){

        self::debug_log('doing js optimization');

        global $wp_scripts;

        $this->global_scripts = $wp_scripts;

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

        $remove_js_files = apply_filters('rapidload/js/remove-js-files', []);

        $links = $this->dom->find( 'script' );

        foreach ( $links as $link ) {

            if(isset($link->id) && in_array($link->id, $remove_js_files)){
                $link->outertext = '';
                continue;
            }

            if(isset($link->id) && in_array($link->id, ['rapidload-image-handler','rapidload-diagnose-script-js-after'])){
                continue;
            }

            $original_src = self::is_js($link) ? $link->src : null;

            if(isset($link->src) && self::is_file_excluded($link->src)){
                continue;
            }

            if($original_src && self::is_file_excluded($original_src)){
                continue;
            }

            $this->optimize_js_delivery($link);

            // legacy delay starts here

            if(isset($this->options['delay_javascript']) && $this->options['delay_javascript'] === "1"){

                if(!apply_filters('rapidload/js/delay-excluded', false, $link, $this->job, $this->strategy, $this->options)){
                    $this->load_scripts_on_user_interaction($link, $original_src);
                }

            }

            // legacy delay ended

            if(isset($this->options['minify_js']) && $this->options['minify_js'] === "1"){
                $this->minify_js($link);
            }

            do_action('rapidload/enqueue/optimize-js', $link, $this->job, $this->strategy, $this->options);

        }

        if(isset($this->options['delay_javascript']) && $this->options['delay_javascript'] === "1" || apply_filters('rapidload/delay-script/enable', false)){

            $this->add_call_back_script();

            // Inject header delay script
            $title = $this->dom->find('title')[0];

            // get the file content from ./assets/js/inline-scripts/delay-script-header.min.js
            $content = "//!injected by RapidLoad \n
!(function(){var supportedEvents=['DOMContentLoaded','readystatechanges','load'];var supportedTargets=[window,document];var originalDispatchEvent=EventTarget.prototype.dispatchEvent;var originalAddEventListener=EventTarget.prototype.addEventListener;var originalRemoveEventListener=EventTarget.prototype.removeEventListener;var capturedEvents=[];var dispatchedEvents=[];window.RaipdLoadJSDebug={capturedEvents:capturedEvents,dispatchedEvents:dispatchedEvents};EventTarget.prototype.addEventListener=function(type,listener,...options){var isDispatched=!type.includes('RapidLoad:')&&!!dispatchedEvents.find(e=>e.type===type)&&supportedTargets.includes(this);var catchEvent=!type.includes('RapidLoad:')&&!type.includes(':norapidload')&&supportedEvents.includes(type)&&supportedTargets.includes(this)&&typeof listener==='function';if(catchEvent||catchEvent!==isDispatched&&isDispatched){if(this===document&&document.readyState!=='loading'||this===window&&document.readyState!=='loading'){setTimeout(()=>{if(typeof listener!=='function'){return}if(listener){listener.call(this,new Event(type))}},10)}else{capturedEvents.push({target:this,type:type,listener:listener,options:options})}}if(type.includes(':norapidload')){type=type.replace(':norapidload','')}originalAddEventListener.call(this,type,listener,...options)};EventTarget.prototype.removeEventListener=function(type,listener,...options){if(supportedEvents.includes(type)&&supportedTargets.includes(this)){capturedEvents=capturedEvents.filter(e=>!(e.type===type&&e.listener===listener&&e.target===this))}originalRemoveEventListener.call(this,type,listener,...options)};EventTarget.prototype.dispatchEvent=function(event){dispatchedEvents.push(event);if(supportedEvents.includes(event.type)&&supportedTargets.includes(this)){capturedEvents=capturedEvents.filter(e=>{if(e.type===event.type&&e.target===this){e.target.removeEventListener(e.type,e.listener,...e.options);return false}return true})}return originalDispatchEvent.call(this,event)};supportedEvents.forEach(function(eventType){supportedTargets.forEach(function(target){target.addEventListener(eventType,function(){})})})})();";

            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true || defined('RAPIDLOAD_DEV_MODE') && RAPIDLOAD_DEV_MODE === true) {
                $filePath = RAPIDLOAD_PLUGIN_DIR . '/includes/modules/javascript/assets/js/inline-scripts/delay-script-head.js';

                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                }
            }

            $node_header = '<script type="text/javascript" >' . $content . '</script>';

            $title_content = $title->outertext;

            $title->__set('outertext', $title_content . $node_header);


            // Inject footer delay script
            $body = $this->dom->find('body', 0);

            // get the file content from ./assets/js/inline-scripts/delay-script-footer.min.js
            $content = "//!injected by RapidLoad \n
            (function(){var totalScripts=prepareScripts();const events=['click','mousemove','touchstart','keydown','scroll'];let userInteracted=false;function rpDebug(method='log',...args){if(window.location.search.includes('rapidload_debug_js')){console[method](...args)}}rpDebug('info','totalScripts');rpDebug('table',totalScripts);function onScriptLoad(script,success=true){totalScripts=totalScripts.map(s=>s.id===script.id&&script.loaded===null?{...script,loaded:true,success:success}:s);if(totalScripts.filter(s=>s.loaded).length===totalScripts.length){window.rapidloadScripts=totalScripts;var allScriptsLoadedEvent=new CustomEvent('RapidLoad:DelayedScriptsLoaded',{bubbles:true,cancelable:true});document.dispatchEvent(allScriptsLoadedEvent);rpDebug('table',totalScripts);rpDebug('info','fired: RapidLoad:DelayedScriptsLoaded')}}function prepareScripts(){var allScripts=Array.from(document.querySelectorAll('[data-rapidload-src], noscript[data-rapidload-delayed]'));return allScripts.map(function(script,index){if(script.tagName.toLowerCase()==='noscript'){return{id:`script-${index}`,scriptElement:script,loaded:null,success:false,content:script.innerHTML,type:'inline'}}var scriptId=script.getAttribute('id');var src=script.getAttribute('data-rapidload-src');return{id:scriptId||`script-${index}`,scriptElement:script,loaded:null,success:false,src:src,type:'external'}})}function loadScript(script){return new Promise((resolve,reject)=>{var scriptElement=script.scriptElement;scriptElement.addEventListener('load',()=>{onScriptLoad(script);rpDebug('info','loaded',script.src);resolve()});scriptElement.addEventListener('error',()=>{onScriptLoad(script,false);rpDebug('info','error',script.src);resolve()});setTimeout(()=>{if(script.src){scriptElement.setAttribute('src',script.src);scriptElement.removeAttribute('data-rapidload-src')}},0)})}async function preloadScripts(scripts){const externalScripts=scripts.filter(script=>script.type==='external');const preloadPromises=[];externalScripts.forEach(script=>{const link=document.createElement('link');link.rel='preload';link.as='script';link.fetchpriority='high';link.href=script.src;let promise=null;try{promise=new Promise((resolve,reject)=>{link.onload=()=>{link.parentNode.removeChild(link);resolve(script)};link.onerror=error=>{link.parentNode.removeChild(link);resolve(script)}})}catch(e){console.log(e)}if(promise){preloadPromises.push(promise)}document.head.appendChild(link)});await Promise.all(preloadPromises)}function loadInlineScript(script){return new Promise(resolve=>{const newScript=document.createElement('script');const inlineScript=document.createTextNode(script.content);newScript.appendChild(inlineScript);script.scriptElement.parentNode.insertBefore(newScript,script.scriptElement);script.scriptElement.parentNode.removeChild(script.scriptElement);onScriptLoad(script,true);rpDebug('info','loaded inline script',script.content);resolve()})}async function loadScriptsInDependencyOrder(){await preloadScripts(totalScripts);for(const script of totalScripts){if(script.type==='inline'){await loadInlineScript(script)}else{await loadScript(script)}}}var listener=async function(){if(!userInteracted){userInteracted=true;removeEventListeners();await loadScriptsInDependencyOrder();if(totalScripts===0){var allScriptsLoadedEvent=new CustomEvent('RapidLoad:DelayedScriptsLoaded',{bubbles:true,cancelable:true});document.dispatchEvent(allScriptsLoadedEvent)}}};events.forEach(function(event){addEventListener(event,listener)});function removeEventListeners(){events.forEach(function(event){removeEventListener(event,listener)})}function load_inline_delayed(){Array.from(document.getElementsByTagName('noscript')).forEach(function(e){var tag=e.getAttribute('data-rapidload-delayed');if(tag!==null&&tag!==undefined){var newScript=document.createElement('script');var inlineScript=document.createTextNode(e.innerHTML);newScript.appendChild(inlineScript);e.parentNode.insertBefore(newScript,e)}})}})();";

            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true || defined('RAPIDLOAD_DEV_MODE') && RAPIDLOAD_DEV_MODE === true) {
                $filePath = RAPIDLOAD_PLUGIN_DIR . '/includes/modules/javascript/assets/js/inline-scripts/delay-script-footer.js';

                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                }
            }

            $node = $this->dom->createElement('script', "" . $content . "");

            $node->setAttribute('type', 'text/javascript');
            $body->appendChild($node);



        }

        add_filter('rapidload/optimizer/frontend/data', function ($data){
            return array_merge($data,$this->frontend_data);
        });

        return [
            'dom' => $this->dom,
            'inject' => $this->inject,
            'options' => $this->options,
            'strategy' => $this->strategy
        ];
    }

    public function add_call_back_script(){

        $body = $this->dom->find('body', 0);

        $content = isset($this->options['delay_javascript_callback']) && !empty($this->options['delay_javascript_callback']) ? $this->options['delay_javascript_callback'] : "";

        if(!empty($content)){

            $node = $this->dom->createElement('script', "" . $this->wrapWithJavaScriptEvent("DOMContentLoaded", stripslashes($content)) . "");

            $node->setAttribute('type', 'text/javascript');
            $node->setAttribute('id', 'rapidload-delay-script-callback');
            $body->appendChild($node);
        }

    }

    public function load_scripts_on_user_interaction($link, $original_src = null){

        $_frontend_data = [];

        if(self::is_js($link)){

            $_frontend_data['src'] = $link->src;

            if(self::is_file_excluded($link->src) || self::is_file_excluded($link->src,'uucss_exclude_files_from_delay_js')){
                return;
            }

            if(self::is_file_excluded($original_src) || self::is_file_excluded($original_src,'uucss_exclude_files_from_delay_js')){
                return;
            }

            if(isset($this->options['uucss_load_scripts_on_user_interaction']) && !empty($this->options['uucss_load_scripts_on_user_interaction'])){
                if(self::is_load_on_user_interaction($link->src) || self::is_load_on_user_interaction($original_src)){

                    $_frontend_data['delayed'] = true;
                    $data_attr = "data-rapidload-src";
                    $link->{$data_attr} = $link->src;
                    unset($link->src);

                }
            }else{

                if($this->str_contains($link->src,"rapidload.frontend.min.js") || ($original_src && $this->str_contains($original_src,"rapidload.frontend.min.js"))){
                    return;
                }

                $_frontend_data['delayed'] = true;
                $data_attr = "data-rapidload-src";
                $link->{$data_attr} = $link->src;

                unset($link->src);
                unset($link->defer);

            }


        }else if(self::is_inline_script($link)){

            if(isset($link->id) && $this->str_contains($link->id,"rapidload-")){
                return;
            }

            if(self::is_file_excluded($link->innertext()) || self::is_file_excluded($link->innertext(),'uucss_exclude_files_from_delay_js')){
                return;
            }

            if(isset($this->options['uucss_load_scripts_on_user_interaction']) && !empty($this->options['uucss_load_scripts_on_user_interaction']) && self::is_load_on_user_interaction($link->innertext())
                || isset($link->{"data-rapidload-delayed"})){

                $link->__set('outertext',"<noscript data-rapidload-delayed>" . $link->innertext() . "</noscript>");
            }
        }

        if(!empty($_frontend_data)){
            $this->frontend_data['js']['delay'][] = $_frontend_data;
        }

    }

    public function minify_js($link){

        $_frontend_data = [];

        if(!self::is_js($link) || self::is_file_excluded($link->src, 'uucss_exclude_files_from_minify_js')){
            return;
        }

        $_frontend_data['src'] = $link->src;

        $file_path = self::get_file_path_from_url(apply_filters('uucss/enqueue/js-url', $link->src));

        if(!file_exists($file_path)){
            return;
        }

        $version = "";

        if(is_file($file_path)){
            $version = substr(hash_file('md5', $file_path), 0, 12);
        }

        if(!$file_path){
            return;
        }

        $filename = basename(preg_replace('/\?.*/', '', apply_filters('uucss/enqueue/js-url', $link->src)));

        if(!$filename){
            return;
        }

        if(preg_match('/\.min\.js/', $filename)){
            //return;
        }

        if($this->str_contains($filename, ".min.js")){
            $filename = str_replace(".min.js","-{$version}.min.js", $filename);
        }else if($this->str_contains($filename, ".js" )){
            $filename = str_replace(".js","-{$version}.min.js", $filename);
        }

        $minified_file = RapidLoad_JavaScript::$base_dir . '/' . $filename;
        $minified_url = apply_filters('uucss/enqueue/js-minified-url', $filename);

        $file_exist = $this->file_system->exists($minified_file);

        if(!$file_exist){

            $minifier = new \MatthiasMullie\Minify\JS($file_path);
            $minifier->minify($minified_file);

        }

        if(@file_exists($minified_file)){
            $link->setAttribute('src', $minified_url);
            $link->{'data-rpd-minify-js'} = true;
        }

        $_frontend_data['new_src'] = $minified_url;

        $this->frontend_data['js']['minify'][] = $_frontend_data;

    }

    public function optimize_js_delivery($link){

        $_frontend_data = [];

        if(!isset($link->type)){
            $link->type = 'text/javascript';
        }

        if(apply_filters('rapidload/webfont/handle', false, $link)){
            return;
        }

        $js_to_be_defer = isset($this->options['uucss_load_js_method']) &&
            ($this->options['uucss_load_js_method'] === "defer" || $this->options['uucss_load_js_method'] === "1");

        $js_to_be_delay = isset($this->options['delay_javascript']) && $this->options['delay_javascript'] === "1" && (!isset($this->options['uucss_load_scripts_on_user_interaction']) || empty($this->options['uucss_load_scripts_on_user_interaction']));

        if(self::is_js($link)){

            $_frontend_data['src'] = $link->src;

            if($js_to_be_defer){

                if(!self::is_file_excluded($link->src) && !self::is_file_excluded($link->src, 'uucss_excluded_js_files_from_defer')){

                    if(isset($link->defer) || isset($link->async)){
                        return;
                    }

                    if(preg_match( '#(' . $this->default_js_exclusion_pattern . ')#i', $link->src )){
                        return;
                    }

                    $link->defer = true;
                    $link->{'data-rpd-strategy'} = 'defer';
                    unset($link->async);

                    $_frontend_data['deferred'] = true;
                }

            }

        }elseif (self::is_inline_script($link)){

            if(($js_to_be_delay && !self::is_file_excluded($link->innertext(), 'uucss_exclude_files_from_delay_js') && !self::is_file_excluded($link->innertext())) ||
                ($js_to_be_defer && !self::is_file_excluded($link->innertext(), 'uucss_excluded_js_files_from_defer'))){

                $this->defer_inline_js($link);

            }

        }

        if(!empty($_frontend_data)){
            $this->frontend_data['js']['defer'][] = $_frontend_data;
        }

    }

    public function defer_inline_js($link){

        $inner_text = $link->innertext();

        if(!empty($inner_text)){

            if ( isset($link->type) && preg_match( '/(application\/ld\+json)/i', $link->type ) ) {
                return;
            }

            if ($link->norapidload) {
                return;
            }

            if(isset($link->id) && in_array($link->id, ["rapidload-js-extra","rapidload-diagnose-script-js-after"])){
                $link->norapidload = true;
                return;
            }

            if(!empty($this->default_inline_js_exclusion_pattern) && preg_match( "/({$this->default_inline_js_exclusion_pattern})/msi", $inner_text )){
                return;
            }

            $manipulated_snippet = $this->analyzeJavaScriptCode($inner_text, $link);

            if (!$manipulated_snippet) {
                return;
            }

            $link->__set('outertext','<script ' . ( $link->id ? 'id="' . $link->id . '"' : '' ) .' type="text/javascript">' . $manipulated_snippet . '</script>');
        }


    }

    public static function is_js( $el ) {
        if (isset($el->src) && preg_match("#" . preg_quote("googletagmanager.com/gtag/js", "#") . "#", $el->src)) {
            return true;
        }
        if (isset($el->src) && preg_match("#" . preg_quote("maps.googleapis.com/maps/api/js", "#") . "#", $el->src)) {
            return true;
        }
        return !empty($el->src) && strpos($el->src,".js");
    }

    public static function is_inline_script( $el ) {
        return !empty($el->type) && $el->type === "text/javascript" && !isset($el->src);
    }

    private function is_file_excluded($file, $option_name = 'uucss_excluded_js_files'){

        $exclude_files = isset($this->options[$option_name]) && !empty($this->options[$option_name]) ? explode("\n", $this->options[$option_name]) : [];

        if($option_name === 'uucss_excluded_js_files'){
            $exclude_files = array_merge($exclude_files, $this->dynamic_exclusions);
        }

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

    private function is_load_on_user_interaction($file){

        $files = isset($this->options['uucss_load_scripts_on_user_interaction']) && !empty($this->options['uucss_load_scripts_on_user_interaction']) ? explode("\n", $this->options['uucss_load_scripts_on_user_interaction']) : [];

        $excluded = false;

        foreach ($files as $_file){

            $_file = str_replace("\r", "", $_file);

            if(self::is_regex_expression($_file)){

                $excluded = @preg_match($_file, $file);

            }

            if(!$excluded){

                $excluded = $this->str_contains($file, $_file);

            }

            if($excluded){

                break;
            }

        }

        return $excluded;
    }

    public function get_default_inline_js_exclusions(){
        $list = [
            //"DOMContentLoaded",
            "document.write",
            /*"window.lazyLoadOptions",
            "N.N2_",
            "rev_slider_wrapper",
            "FB3D_CLIENT_LOCALE",
            "ewww_webp_supported",
            "anr_captcha_field_div",
            "renderInvisibleReCaptcha",
            "bookingInProgress",
            "do_not_load_original_css"*/
        ];
        return apply_filters('rapidload/defer/exclusions/inline_js', $list);
    }

    public function get_default_js_exclusions(){
        $list = [
            "gist.github.com",
            "content.jwplatform.com",
            "js.hsforms.net",
            "www.uplaunch.com",
            "google.com\/recaptcha",
            "widget.reviews.co.uk",
            "verify.authorize.net\/anetseal",
            "lib\/admin\/assets\/lib\/webfont\/webfont.min.js",
            "app.mailerlite.com",
            "widget.reviews.io",
            "simplybook.(.*)\/v2\/widget\/widget.js",
            "\/wp-includes\/js\/dist\/i18n.min.js",
            "\/wp-content\/plugins\/wpfront-notification-bar\/js\/wpfront-notification-bar(.*).js",
            "\/wp-content\/plugins\/oxygen\/component-framework\/vendor\/aos\/aos.js",
            "\/wp-content\/plugins\/ewww-image-optimizer\/includes\/check-webp(.min)?.js",
            "static.mailerlite.com\/data\/(.*).js",
            "cdn.voxpow.com\/static\/libs\/v1\/(.*).js",
            "cdn.voxpow.com\/media\/trackers\/js\/(.*).js",
            "use.typekit.net",
            "www.idxhome.com",
            "\/wp-includes\/js\/dist\/vendor\/lodash(.min)?.js",
            "\/wp-includes\/js\/dist\/api-fetch(.min)?.js",
            "\/wp-includes\/js\/dist\/i18n(.min)?.js",
            "\/wp-includes\/js\/dist\/vendor\/wp-polyfill(.min)?.js",
            "\/wp-includes\/js\/dist\/url(.min)?.js",
            "\/wp-includes\/js\/dist\/hooks(.min)?.js",
            "www.paypal.com\/sdk\/js",
            "js-eu1.hsforms.net",
            "yanovis.Voucher.js",
            "\/carousel-upsells-and-related-product-for-woocommerce\/assets\/js\/glide.min.js",
            "use.typekit.com",
            "\/artale\/modules\/kirki\/assets\/webfont.js",
            "\/api\/scripts\/lb_cs.js",
            "js.hscta.net\/cta\/current.js",
            "widget.refari.co",
            "player.vdocipher.com",
            "\/assets\/js\/preloaded-elements-handlers(.min)?.js" // popup not working
        ];

        return apply_filters('rapidload/defer/exclusions/js', $list);
    }

    public function analyzeJavaScriptCode($jsSnippet, $script)
    {
        try {
            // Determine the global event based on the 'delay_javascript' option
            $eventToBind = isset($this->options['delay_javascript']) && $this->options['delay_javascript'] === "1" && (!isset($this->options['uucss_load_scripts_on_user_interaction']) || empty($this->options['uucss_load_scripts_on_user_interaction']))
                ? 'RapidLoad:DelayedScriptsLoaded'
                : 'DOMContentLoaded';

            // Skip processing for specific cases
            if (preg_match('/window\._wpemojiSettings|data-rapidload-delayed/', $jsSnippet)) {
                return false;
            }

            // Parse the JavaScript snippet into an AST (Abstract Syntax Tree)
            $parsedAst = Peast::latest($jsSnippet)->parse();

            // Configure the AST traverser
            $astTraverser = new Traverser([
                'passParentNode' => true,
                'skipStartingNode' => true,
            ]);

            // Define a renderer for pretty printing the AST
            $astRenderer = new Renderer();
            $astRenderer->setFormatter(new PrettyPrint());
            $rootStatements = [];

            // Traverse and modify the AST
            $updatedSnippet = $astTraverser->addFunction(function ($currentNode) use ($eventToBind, $astRenderer, &$rootStatements) {

                $type = $currentNode->getType();

                $rootStatements[] = (object) [
                    'type' => $type,
                    'node' => $currentNode,
                ];

                return Traverser::DONT_TRAVERSE_CHILD_NODES;

            })->traverse($parsedAst)->render(new PrettyPrint());

            // Check if there are only variable or function declarations
            if (count(array_filter($rootStatements, function ($statement) {
                    return $statement->type === 'VariableDeclaration' || $statement->type === 'FunctionDeclaration';
                })) === count($rootStatements)) {
                // No need to delay, return the original snippet
                return $updatedSnippet;
            }

            $snippets = '';

            // Traverse again to wrap only function calls (CallExpression)
            foreach ($rootStatements as $rootStatement){

                $inner_content = $rootStatement->node->render(new Compact()) . ";";

                $pattern = "/document\.addEventListener\((?:'|\")DOMContentLoaded(?:'|\")|window\.addEventListener/";

                if(preg_match($pattern, $inner_content)){

                    $snippets .= str_replace("DOMContentLoaded",$eventToBind,$inner_content) . "\n";

                } else {
                    // Delay only function calls (CallExpression)
                    if ($rootStatement->type === 'ExpressionStatement' && $this->containsFunctionCall($rootStatement->node)) {
                        $snippets .= $this->wrapWithJavaScriptEvent($eventToBind, $inner_content) . "\n";
                    } else {
                        // Leave the declarations alone
                        $snippets .= $inner_content . "\n";
                    }
                }
            }

            return $snippets;

        } catch (Exception $exception) {
            return $this->wrapWithJavaScriptEvent($eventToBind, $jsSnippet);
        }
    }

    public function wrapWithJavaScriptEvent($event, $codeSnippet)
    {
        // Wrap the given code snippet in a JavaScript event listener
        return "document.addEventListener('$event', function(){ " . $codeSnippet . "});";
    }

    private function containsFunctionCall($node) {
        // Check if the node itself is a CallExpression
        if ($node->getType() === 'CallExpression') {
            //self::debug_log('found CallExpression', $node->render(new PrettyPrint()));
            return true;
        }

        // Manually traverse children if they exist
        if (method_exists($node, 'getExpression')) {
            $expression = $node->getExpression();
            if ($expression && $this->containsFunctionCall($expression)) {
                //self::debug_log('found getExpression', $expression->render(new PrettyPrint()));
                return true;
            }
        }

        if (method_exists($node, 'getDeclarations')) {
            $declarations = $node->getDeclarations();
            foreach ($declarations as $declaration) {
                if ($this->containsFunctionCall($declaration)) {
                    //self::debug_log('found getDeclarations', $declaration->render(new PrettyPrint()));
                    return true;
                }
            }
        }

        if (method_exists($node, 'getArguments')) {
            $arguments = $node->getArguments();
            foreach ($arguments as $arg) {
                if ($this->containsFunctionCall($arg)) {
                    //self::debug_log('found getArguments', $arg->render(new PrettyPrint()));
                    return true;
                }
            }
        }

        if (method_exists($node, 'getCallee')) {
            $callee = $node->getCallee();
            if ($callee && $this->containsFunctionCall($callee)) {
                //self::debug_log('found getCallee', $callee->render(new PrettyPrint()));
                return true;
            }
        }

        return false;
    }


    public function assignWindow($node)
    {
        $id = null;
        $type = $node->getType();
        $defineWindow = '';
        $additionalSnippets = [];

        if ($type === 'FunctionDeclaration') {
            $id = $node->getId()->getRawName();
            $defineWindow = $id ? "window." . $id . "=" : "";
        }

        if ($type === 'VariableDeclaration' && count($node->getDeclarations()) === 1) {
            $id = $node->getDeclarations()[0]->getId()->getRawName();
            $additionalSnippets[] = "window." . $id . " = " . $id . ";";
        }

        if ($type === 'VariableDeclaration' && count($node->getDeclarations()) > 1) {

            foreach($node->getDeclarations() as $declaration){
                $id = $declaration->getId()->getRawName();
                $additionalSnippets[] = "window." . $id . " = " . $id . ";";
            }

        }

        if (!$id) {
            return [$node->render(new Compact())];
        }

        $renderedFunction = $node->render(new Compact());

        $updatedAst = Peast::latest( $defineWindow . $renderedFunction)->parse();
        return array_merge([$updatedAst->render(new Compact())], $additionalSnippets);
    }




}