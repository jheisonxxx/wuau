<?php

if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $title
 * @var $el_class
 * @var $collapsible
 * @var $disable_keyboard
 * @var $active_section
 * @var $content - shortcode content
 * Shortcode class
 * @var $this WPBakeryShortCode_VC_Accordion
 */
$title = $el_class = $collapsible = $disable_keyboard = $active_section = '';
if (function_exists('vc_map_get_attributes')) {
    $atts = vc_map_get_attributes($this->getShortcode(), $atts);
}
extract($atts);

wp_enqueue_script('jquery-ui-accordion');
$css_class = $el_class;
if (defined('VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, ' ' . $el_class . ' ', $this->settings['base'], $atts);
}

$header = empty($title) ? '' : '<div class="accordion-header">' . azvc_widget_title(array('title' => $title)) . '</div>';

$output = '
	<div class="azexo-accordion ' . esc_attr($css_class) . '" data-collapsible="' . esc_attr($collapsible) . '"  data-active-section="' . $active_section . '">
' . $header . '
		<div class="accordion-wrapper">
' . do_shortcode(shortcode_unautop($content)) . '
		</div>
	</div>
';

echo $output;
