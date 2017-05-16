<?php

if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $title
 * @var $values
 * @var $units
 * @var $bgcolor
 * @var $custombgcolor
 * @var $customtxtcolor
 * @var $options
 * @var $el_class
 * @var $css
 * Shortcode class
 * @var $this WPBakeryShortCode_VC_Progress_Bar
 */
$title = $values = $units = $bgcolor = $css = $custombgcolor = $customtxtcolor = $options = $el_class = '';
$output = '';
if (function_exists('vc_map_get_attributes')) {
    $atts = vc_map_get_attributes($this->getShortcode(), $atts);
}

extract($atts);
wp_enqueue_script('waypoints');

$bar_options = array();
$options = explode(',', $options);
if (in_array('animated', $options)) {
    $bar_options[] = 'animated';
}
if (in_array('striped', $options)) {
    $bar_options[] = 'striped';
}

if ('custom' === $bgcolor && '' !== $custombgcolor) {
    $custombgcolor = ' style="' . azvc_get_css_color('background-color', $custombgcolor) . '"';
    if ('' !== $customtxtcolor) {
        $customtxtcolor = ' style="' . azvc_get_css_color('color', $customtxtcolor) . '"';
    }
    $bgcolor = '';
} else {
    $custombgcolor = '';
    $customtxtcolor = '';
    $bgcolor = 'vc_progress-bar-color-' . esc_attr($bgcolor);
    $el_class .= ' ' . $bgcolor;
}

$css_class = $class_to_filter = 'vc_progress_bar wpb_content_element';
$css_class .= ' ' . $el_class;
if (function_exists('vc_shortcode_custom_css_class')) {
    $class_to_filter .= vc_shortcode_custom_css_class($css, ' ') . $this->getExtraClass($el_class);
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $class_to_filter, $this->settings['base'], $atts);
}

$output = '<div class="' . esc_attr($css_class) . '">';

$output .= azvc_widget_title(array('title' => $title, 'extraclass' => 'wpb_progress_bar_heading'));

$values = (array) json_decode(urldecode($values), true);
$max_value = 0.0;
$graph_lines_data = array();
foreach ($values as $data) {
    if (function_exists('vc_icon_element_fonts_enqueue')) {
        vc_icon_element_fonts_enqueue($data['icon_library']);
    } else {
        if (function_exists('azh_icon_font_enqueue')) {
            azh_icon_font_enqueue($data['icon_library']);
        }
    }

    $new_line = $data;
    $new_line['icon'] = isset($data['icon_' . $data['icon_library']]) ? esc_attr($data['icon_' . $data['icon_library']]) : 'fa fa-adjust';
    $new_line['value'] = isset($data['value']) ? $data['value'] : 0;
    $new_line['label'] = isset($data['label']) ? $data['label'] : '';
    $new_line['bgcolor'] = isset($data['color']) && 'custom' !== $data['color'] ? '' : $custombgcolor;
    $new_line['txtcolor'] = isset($data['color']) && 'custom' !== $data['color'] ? '' : $customtxtcolor;
    if (isset($data['customcolor']) && (!isset($data['color']) || 'custom' === $data['color'] )) {
        $new_line['bgcolor'] = ' style="background-color: ' . esc_attr($data['customcolor']) . ';"';
    }
    if (isset($data['customtxtcolor']) && (!isset($data['color']) || 'custom' === $data['color'] )) {
        $new_line['txtcolor'] = ' style="color: ' . esc_attr($data['customtxtcolor']) . ';"';
    }

    if ($max_value < (float) $new_line['value']) {
        $max_value = $new_line['value'];
    }
    $graph_lines_data[] = $new_line;
}

foreach ($graph_lines_data as $line) {
    $unit = ( '' !== $units ) ? ' <span class="vc_label_units">' . $line['value'] . $units . '</span>' : '';
    $output .= '<div class="vc_general vc_single_bar' . ( ( isset($line['color']) && 'custom' !== $line['color'] ) ?
                    ' vc_progress-bar-color-' . $line['color'] : '' )
            . '">';
    $output .= '<span class="vc_icon ' . $line['icon'] . '"></span>';
    $output .= '<span class="vc_label"' . $line['txtcolor'] . '>' . $line['label'] . $unit . '</span>';
    if ($max_value > 100.00) {
        $percentage_value = (float) $line['value'] > 0 && $max_value > 100.00 ? round((float) $line['value'] / $max_value * 100, 4) : 0;
    } else {
        $percentage_value = $line['value'];
    }
    $output .= '<span class="vc_bar ' . esc_attr(implode(' ', $bar_options)) . '" data-percentage-value="' . esc_attr($percentage_value) . '" data-value="' . esc_attr($line['value']) . '"' . $line['bgcolor'] . '></span>';
    $output .= '<span class="vc_value">' . $percentage_value . '</span>';
    $output .= '</div>';
}

$output .= '</div>';

echo $output;
