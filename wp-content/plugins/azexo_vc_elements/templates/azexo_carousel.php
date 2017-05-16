<?php

$output = $title = $center = $autoplay = $loop = $item_margin = $contents_per_item = $responsive = $el_class = $css = '';
extract(shortcode_atts(array(
    'title' => '',
    'center' => false,
    'autoplay' => false,
    'loop' => false,
    'item_margin' => 0,
    'contents_per_item' => 1,
    'responsive' => '',
    'el_class' => '',
    'css' => '',
                ), $atts));

$css_class = $el_class;
if (function_exists('vc_shortcode_custom_css_class')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $el_class . vc_shortcode_custom_css_class($css, ' '), $this->settings['base'], $atts);
}

wp_enqueue_script('owl.carousel');
wp_enqueue_style('owl.carousel');

$responsive = (array) json_decode(urldecode($responsive), true);
$responsive_param = array();
foreach ($responsive as $el) {
    $responsive_param[$el['window_width']] = $el;
    unset($responsive_param[$el['window_width']]['window_width']);
}

print '<div class="carousel-wrapper ' . esc_attr($css_class) . '">';
if (!empty($title)) {
    print '<div class="carousel-title"><h3>' . esc_html($title) . '</h3></div>';
}
$r = rand(0, 99999999);
print '<script type="text/javascript">';
print 'window["carousel-' . $r . '"] = ' . json_encode($responsive_param) . ';';
print '</script>';
print '<div class="carousel" data-contents-per-item="' . esc_attr($contents_per_item) . '" data-margin="' . esc_attr($item_margin) . '" data-autoplay="' . esc_attr($autoplay) . '" data-center="' . esc_attr($center) . '" data-loop="' . esc_attr($loop) . '" data-responsive="carousel-' . $r . '">';
print do_shortcode(shortcode_unautop($content));
print '</div></div>';
