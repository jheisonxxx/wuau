<?php

$output = $el_class = $css = '';
extract(shortcode_atts(array(
    'el_class' => '',
    'css' => '',
                ), $atts));

$css_class = $el_class;
if (function_exists('vc_shortcode_custom_css_class')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $el_class . vc_shortcode_custom_css_class($css, ' '), $this->settings['base'], $atts);
}

$product = azexo_get_closest_current_post('product');

if ($product) {

    global $post, $wp_query;
    $original = $post;
    $post = $product;
    setup_postdata($product);

    ob_start();
    post_class(array('entry', 'product', esc_attr($css_class)));

    print '<div ' . ob_get_clean() . ' itemscope itemtype="' . woocommerce_get_product_schema() . '">';
    print '<meta itemprop="url" content="' . esc_url(apply_filters('the_permalink', get_permalink())) . '" />';
    print '<meta itemprop="image" content="' . esc_url(wp_get_attachment_url(get_post_thumbnail_id($product->ID))) . '" />';
    print do_shortcode(shortcode_unautop($content));
    print '</div>';

    $wp_query->post = $original;
    wp_reset_postdata();
}