<?php

//----------------------------------------------------
// Optimize: Minify HTML
//----------------------------------------------------

// Minify HTML
/*class WP_HTML_Compression {
    // Settings
    protected $compress_css = true;
    protected $compress_js = false;
    protected $info_comment = true;
    protected $remove_comments = true;
    // Variables
    protected $html;
    public function __construct($html) {
        if (!empty($html)) {
            $this->parseHTML($html);
        }
    }
    public function __toString() {
        return $this->html;
    }
    protected function minifyHTML($html) {
        $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        $overriding = false;
        $raw_tag = false;
        // Variable reused for output
        $html = '';
        foreach ( $matches as $token ) {
            $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
            $content = $token[0];
            if ( is_null( $tag ) ) {
                if ( !empty( $token['script'] ) ) {
                    $strip = $this->compress_js;
                } else if ( !empty($token['style'] ) ) {
                    $strip = $this->compress_css;
                } else if ( $content == '<!--wp-html-compression no compression-->' ) {
                    $overriding = !$overriding;
                    // Don't print the comment
                    continue;
                } else if ( $this->remove_comments ) {
                    if ( !$overriding && $raw_tag != 'textarea' ) {
                        // Remove any HTML comments, except MSIE conditional comments
                        $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
                    }
                }
            } else {
                if ( $tag == 'pre' || $tag == 'textarea' || $tag == 'script' ) {
                    $raw_tag = $tag;
                } else if ( $tag == '/pre' || $tag == '/textarea' || $tag == '/script' ) {
                    $raw_tag = false;
                } else {
                    if ($raw_tag || $overriding) {
                        $strip = false;
                    } else {
                        $strip = true;
                        // Remove any empty attributes, except:
                        // action, alt, content, src
                        $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
                        // Remove any space before the end of self-closing XHTML tags
                        // JavaScript excluded
                        $content = str_replace(' />', '/>', $content);
                    }
                }
            }
            $content = $this->removeWhiteSpace($content);
            $html .= $content;
        }
        return $html;
    }
    public function parseHTML($html) {
        $this->html = $this->minifyHTML($html);
    }
    protected function removeWhiteSpace($str) {
        $str = str_replace( "\t", ' ', $str );
        $str = str_replace( "\n",  '', $str );
        $str = str_replace( "\r",  '', $str );
        while ( stristr($str, '  ' ) ) {
            $str = str_replace('  ', ' ', $str);
        }
        return $str;
    }
}*/


//----------------------------------------------------
// Optimize: Minify HTML
//----------------------------------------------------

defined( 'ABSPATH' ) or die();

class WP_HTML_Compression
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


