<?php

//----------------------------------------------------
// Optimize: Minify HTML
//----------------------------------------------------

defined( 'ABSPATH' ) or die();

class RapidLoad_WP_HTML_Compression
{
    // Settings
    protected $compress_css = true;
    protected $compress_js = false;
    protected $info_comment = true;
    protected $remove_comments = true;

    // Variables
    protected $html;

    public function __construct($html)
    {
        if (!empty($html)) {
            $this->parseHTML($html);
        }
    }

    public function __toString()
    {
        return $this->html;
    }

    protected function minifyHTML($html)
    {
        $pattern = '/<(script|style).*?<\/\\1>|<!--.*?-->|<[^>]+>|[^<]+/is';

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $overriding = false;
        $html = '';

        foreach ($matches as $token) {
            $content = $token[0];

            if ($overriding) {
                // Skip everything if overriding
                $html .= $content;
                if ($content === '<!--wp-html-compression no compression-->') {
                    $overriding = false;
                }
                continue;
            } elseif ($content === '<!--wp-html-compression no compression-->') {
                $overriding = true;
                continue;
            }

            if ($this->isIgnorableTag($content)) {
                // Don't minify script, style, or comments
                $html .= $content;
            } else {
                // Minify everything else
                if ($this->remove_comments) {
                    $content = preg_replace('/<!--(.|\s)*?-->/', '', $content);
                }
                $html .= $this->removeWhiteSpace($content);
            }
        }

        return $html;
    }

    protected function isIgnorableTag($content)
    {
        return preg_match('/^<(script|style|!--)/', $content);
    }

    public function parseHTML($html)
    {
        $this->html = $this->minifyHTML($html);
    }

    protected function removeWhiteSpace($str)
    {
        $str = str_replace("\t", ' ', $str);
        $str = str_replace("\n", '', $str);
        $str = str_replace("\r", '', $str);

        // Replace multiple spaces with a single space
        $str = preg_replace('/ {2,}/', ' ', $str);

        return $str;
    }
}


