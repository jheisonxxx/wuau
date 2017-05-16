<?php
/**
 * Single Product Price, including microdata for SEO
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     10.0.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $product;
$options = get_option(AZEXO_FRAMEWORK);
?>
<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">

    <p class="price"><?php print isset($options['single_price_prefix']) ? '<span class="prefix">' . $options['single_price_prefix'] . '</span>' : ''; ?> <?php print $product->get_price_html(); ?></p>

    <meta itemprop="price" content="<?php print esc_attr($product->get_price()); ?>" />
    <meta itemprop="priceCurrency" content="<?php echo get_woocommerce_currency(); ?>" />
    <link itemprop="availability" href="http://schema.org/<?php print $product->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>" />

</div>
