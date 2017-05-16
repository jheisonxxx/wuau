<?php

if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $tab_id
 * @var $title
 * @var $content - shortcode content
 * Shortcode class
 * @var $this WPBakeryShortCode_VC_Tab
 */
$tab_id = $title = '';
if (function_exists('vc_map_get_attributes')) {
    $atts = vc_map_get_attributes($this->getShortcode(), $atts);
}
extract($atts);

$css_class = '';
if (defined('VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, '', $this->settings['base'], $atts);
}

$output = '
	<div id="tab-' . ( empty($tab_id) ? sanitize_title($title) : esc_attr($tab_id) ) . '" class="tab-wrapper ' . esc_attr($css_class) . '">
		' . ( ( '' === trim($content) ) ? __('Empty tab. Edit page to add content here.', 'azvc') : do_shortcode(shortcode_unautop($content)) ) . '
	</div>
';

echo $output;
