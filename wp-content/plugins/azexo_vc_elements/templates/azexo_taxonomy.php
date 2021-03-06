<?php

$output = '';
if (function_exists('vc_map_get_attributes')) {
    $atts = vc_map_get_attributes($this->getShortcode(), $atts);
}
extract($atts);

$options = explode(',', $options);
if (in_array('dropdown', $options)) {
    $atts['dropdown'] = true;
}
if (in_array('count', $options)) {
    $atts['count'] = true;
}
if (in_array('hierarchical', $options)) {
    $atts['hierarchical'] = true;
}

$output = '<div class="vc_wp_categories wpb_content_element ' . esc_attr($el_class) . '">';
$type = 'AZEXO_Taxonomy';
$args = array();
global $wp_widget_factory;
// to avoid unwanted warnings let's check before using widget
if (is_object($wp_widget_factory) && isset($wp_widget_factory->widgets, $wp_widget_factory->widgets[$type])) {
    ob_start();
    the_widget($type, $atts, $args);
    $output .= ob_get_clean();

    $output .= '</div>';

    print $output;
}
