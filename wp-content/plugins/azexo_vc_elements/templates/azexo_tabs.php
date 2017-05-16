<?php

if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $title
 * @var $el_class
 * @var $content - shortcode content
 * Shortcode class
 * @var $this WPBakeryShortCode_VC_Tabs
 */
$title = $el_class = '';
if (function_exists('vc_map_get_attributes')) {
    $atts = vc_map_get_attributes($this->getShortcode(), $atts);
}
extract($atts);

wp_enqueue_script('jquery-ui-tabs');


// Extract tab titles
preg_match_all('/azexo_tab([^\]]+)/i', $content, $matches, PREG_OFFSET_CAPTURE);
$tabs = array();
/**
 * azexo_tabs
 *
 */
if (isset($matches[1])) {
    $tabs = $matches[1];
}
$tabs_nav = '';
$tabs_nav .= '<ul class="tabs-nav">';
foreach ($tabs as $tab) {
    $tab_atts = shortcode_parse_atts($tab[0]);
    if (function_exists('vc_map_get_attributes')) {
        $tab_atts = vc_map_get_attributes('azexo_tab', $tab_atts);
    } else {
        if (function_exists('azh_get_attributes')) {
            $tab_atts = azh_get_attributes('azexo_tab', $tab_atts);
        }
    }

    $icon = '';
    if (isset($tab_atts['icon']) && $tab_atts['icon'] == 'yes') {
        if (function_exists('vc_icon_element_fonts_enqueue')) {
            vc_icon_element_fonts_enqueue($tab_atts['icon_library']);
        } else {
            if (function_exists('azh_icon_font_enqueue')) {
                azh_icon_font_enqueue($tab_atts['icon_library']);
            }
        }

        $icon_att = "icon_" . $tab_atts['icon_library'];
        $icon_class = isset($tab_atts[$icon_att]) ? esc_attr($tab_atts[$icon_att]) : 'fa fa-adjust';
        $icon = '<span class="icon ' . $icon_class . '"></span> ';
    }
    if (isset($tab_atts['title'])) {
        $tabs_nav .= '<li><a href="#tab-' . ( isset($tab_atts['tab_id']) ? $tab_atts['tab_id'] : sanitize_title($tab_atts['title']) ) . '">' . $icon . '<span class="title">' . $tab_atts['title'] . '</span></a></li>';
    }
}
$tabs_nav .= '</ul>';


$css_class = $el_class;
if (defined('VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $el_class, $this->settings['base'], $atts);
}

$header = empty($title) ? '' : '<div class="tabs-header">' . azvc_widget_title(array('title' => $title)) . '</div>';

$output = '<div class="azexo-tabs ' . $css_class . '">' . $header
        . $tabs_nav
        . '<div class="tabs-content">' . do_shortcode(shortcode_unautop($content))
        . '</div></div>';

echo $output;
