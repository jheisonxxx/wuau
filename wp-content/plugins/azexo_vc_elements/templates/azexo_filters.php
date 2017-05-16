<?php

$output = $title = $filters = $masonry = $responsive = $gutter = $el_class = $css = '';
extract(shortcode_atts(array(
    'title' => '',
    'filters' => '',
    'masonry' => false,
    'responsive' => '',
    'gutter' => '0',
    'el_class' => '',
    'css' => '',
                ), $atts));

$css_class = $el_class;
if (function_exists('vc_shortcode_custom_css_class')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $el_class . vc_shortcode_custom_css_class($css, ' '), $this->settings['base'], $atts);
}

print '<div class="filters-wrapper ' . esc_attr($css_class) . '"><div class="filters-header">';
if (!empty($title)) {
    print '<div class="filters-title"><h3>' . esc_html($title) . '</h3></div>';
}
$filters = (array) json_decode(urldecode($filters), true);
if (is_array($filters)) {
    print '<div class="filters">';
    foreach ($filters as $filter) {
        print '<span class="filter" data-selector="' . esc_html($filter['selector']) . '">' . esc_html($filter['title']) . '</span>';
    }
    print '</div>';
}
print '</div>';
if ($masonry) {
    wp_enqueue_script('masonry');
    $responsive = (array) json_decode(urldecode($responsive), true);
    $responsive_param = array();
    foreach ($responsive as $el) {
        $responsive_param[$el['window_width']] = $el;
        unset($responsive_param[$el['window_width']]['window_width']);
    }
    $r = rand(0, 99999999);
    print '<script type="text/javascript">';
    print 'window["filters-' . $r . '"] = ' . json_encode($responsive_param) . ';';
    print '</script>';
    print '<div class="filterable masonry" data-selector=".entry, .azexo_html" data-responsive="filters-' . $r . '" data-gutter="' . esc_attr($gutter) . '">';
} else {
    print '<div class="filterable">';
}
print do_shortcode(shortcode_unautop($content));
print '</div></div>';
