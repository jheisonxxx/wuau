<?php
/*
  Field Name: Product coupon
 */
?>
<?php
global $product;
$coupon = get_post_meta($product->id, 'coupon', true);
$product_url = get_post_meta($product->id, '_product_url', true);
wp_enqueue_script('azexo-fields', get_template_directory_uri() . '/js/fields.js', array('jquery'), AZEXO_FRAMEWORK_VERSION, true);
?>

<?php if (!empty($coupon)) : ?>
    <div class="coupon-wrapper">
        <a href="<?php print (!empty($product_url) ? esc_url($product_url) : '#'); ?>" target="_blank" data-code="<?php print esc_attr($coupon); ?>" class="coupon" data-copied="<?php esc_attr_e('Code copied to the clipboard', 'AZEXO'); ?>"><?php esc_attr_e('Show code', 'AZEXO'); ?></a>
    </div>
<?php endif; ?>


