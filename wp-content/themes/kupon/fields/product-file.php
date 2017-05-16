<?php
/*
  Field Name: Product attached file link
 */
?>
<?php
global $product;
$url = get_post_meta($product->id, 'file', true);
?>

<?php if (!empty($url)) : ?>
    <a href="<?php print esc_url($url); ?>" class="add-review"><?php esc_attr_e('Download', 'AZEXO'); ?></a>
<?php endif; ?>


