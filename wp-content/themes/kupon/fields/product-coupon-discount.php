<?php
/*
  Field Name: Product coupon discount
 */
?>
<?php
global $product;
$discount = get_post_meta($product->id, 'discount', true);
?>
<span class="discount"><?php print esc_html($discount); ?></span>
