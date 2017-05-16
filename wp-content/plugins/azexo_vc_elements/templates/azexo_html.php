<?php

if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $el_class
 * @var $content - shortcode content
 * @var $css
 * Shortcode class
 * @var $this WPBakeryShortCode_VC_Raw_html
 */
$el_class = $css = '';
if (function_exists('vc_map_get_attributes')) {
    $atts = vc_map_get_attributes($this->getShortcode(), $atts);
}
extract($atts);

$content = rawurldecode(base64_decode(strip_tags($content)));

global $azvc_icons, $azvc_icons_index;
if (isset($azvc_icons) && is_array($azvc_icons)) {
    foreach ($azvc_icons as $icon_type => $icons) {
        $pattern = '/' . implode('|', array_keys($icons)) . '/';
        if (preg_match($pattern, $content, $matches)) {
            if (function_exists('vc_icon_element_fonts_enqueue')) {
                vc_icon_element_fonts_enqueue($azvc_icons_index[$matches[0]]);
            }
        }
    }
}
global $azh_icons, $azh_icons_index;
if (isset($azh_icons) && is_array($azh_icons)) {
    foreach ($azh_icons as $icon_type => $icons) {
        $pattern = '/' . implode('|', array_keys($icons)) . '/';
        if (preg_match($pattern, $content, $matches)) {
            if (function_exists('azh_icon_font_enqueue')) {
                azh_icon_font_enqueue($azh_icons_index[$matches[0]]);
            }
        }
    }
}


if (strpos($content, 'image-popup') !== false || strpos($content, 'iframe-popup') !== false) {
    wp_enqueue_script('magnific-popup');
    wp_enqueue_style('magnific-popup');
}

$css_class = $class_to_filter = 'azexo_html';
if (function_exists('vc_shortcode_custom_css_class')) {
    $class_to_filter .= vc_shortcode_custom_css_class($css, ' ') . $this->getExtraClass($el_class);
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $class_to_filter, $this->settings['base'], $atts);
}

$content_without_desc = preg_replace_callback('#\[\[([^\]]+)\]\]#', function($m) {
        return '';
    }, $content);
    
$output = '<div class="' . esc_attr($css_class) . '">' . do_shortcode($content_without_desc) . '</div>';

echo $output;
