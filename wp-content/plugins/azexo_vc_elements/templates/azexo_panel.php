<?php

$output = $title = $link = $el_class = $css = '';
extract(shortcode_atts(array(
    'title' => '',
    'link' => '',
    'el_class' => '',
    'css' => '',
                ), $atts));

$css_class = $el_class;
if (function_exists('vc_shortcode_custom_css_class')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $el_class . vc_shortcode_custom_css_class($css, ' '), $this->settings['base'], $atts);
}


print '<div class="panel ' . esc_attr($css_class) . '">';
if (!empty($title)) {
    $link = array_filter(azexo_build_link($link));
    if (!is_array($link) || empty($link)) {
        print '<div class="panel-title"><h3>' . $title . '</h3></div>';
    } else {
        print '<div class="panel-title"><a ' . azexo_build_link_attributes($link) . '><h3>' . $title . '</h3></a></div>';
    }
}
print '<div class="panel-content">';
print do_shortcode(shortcode_unautop($content));
print '</div></div>';
