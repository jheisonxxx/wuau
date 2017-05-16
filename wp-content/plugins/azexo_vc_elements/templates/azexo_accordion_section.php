<?php

if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $title
 * @var $el_id
 * @var $content - shortcode content
 * Shortcode class
 * @var $this WPBakeryShortCode_VC_Accordion_section
 */
$title = $el_id = '';
if (function_exists('vc_map_get_attributes')) {
    $atts = vc_map_get_attributes($this->getShortcode(), $atts);
}
extract($atts);

$icon_span = '';
if (isset($icon) && $icon == 'yes') {
    if (function_exists('vc_icon_element_fonts_enqueue')) {
        vc_icon_element_fonts_enqueue($icon_library);
    } else {
        if (function_exists('azh_icon_font_enqueue')) {
            azh_icon_font_enqueue($icon_library);
        }
    }
    $icon_class = isset(${"icon_" . $icon_library}) ? esc_attr(${"icon_" . $icon_library}) : 'fa fa-adjust';
    $icon_span = '<span class="icon ' . $icon_class . '"></span> ';
}

$css_class = '';
if (defined('VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, '', $this->settings['base'], $atts);
}

$output = '
	<div ' . ( isset($el_id) && !empty($el_id) ? "id='" . esc_attr($el_id) . "'" : '' ) . 'class="accordion-section ' . esc_attr($css_class) . '">
		<h3 class="section-header"><a href="#' . sanitize_title($title) . '">' . $icon_span . '<span class="title">' . $title . '</span></a></h3>
		<div class="section-content">
			' . ( ( '' === trim($content) ) ? __('Empty section. Edit page to add content here.', 'azvc') : do_shortcode(shortcode_unautop($content)) ) . '
		</div>
	</div>
';

echo $output;
