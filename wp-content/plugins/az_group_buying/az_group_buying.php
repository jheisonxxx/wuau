<?php
/**
 * Plugin Name:  AZEXO Group Buying
 * Plugin URI:   http://www.azexo.com
 * Description:  Group Buying
 * Author:       AZEXO
 * Author URI:   http://www.azexo.com
 * Version: 1.24
 * Text Domain:  azgb
 * Domain Path:  languages
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('AZGB_URL', plugins_url('', __FILE__));
define('AZGB_DIR', trailingslashit(dirname(__FILE__)) . '/');


add_action('plugins_loaded', 'azgb_plugins_loaded');

function azgb_plugins_loaded() {
    load_plugin_textdomain('azgb', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    if (is_admin()) {
        include_once(AZGB_DIR . 'admin.php');
    }
}

add_action('init', 'azgb_init');

function azgb_init() {

    if (class_exists('WC_Pre_Orders')) {
        global $wc_pre_orders;
        remove_action('wc_pre_orders_completion_check', array($wc_pre_orders->manager, 'check_for_pre_orders_to_complete'), 10);
        remove_action('wc_pre_orders_completion_check', array($wc_pre_orders->manager, 'check_for_pre_order_products_to_reset'), 11);

        function azgb_get_product_total_presales($product_id) {
            global $wpdb;

            $number = $wpdb->get_var($wpdb->prepare("
			SELECT SUM(q.meta_value)
			FROM {$wpdb->prefix}woocommerce_order_items AS items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_meta ON items.order_item_id = item_meta.order_item_id
                        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS q ON items.order_item_id = q.order_item_id
			LEFT JOIN {$wpdb->postmeta} AS post_meta ON items.order_id = post_meta.post_id
			WHERE
				items.order_item_type = 'line_item' AND
				item_meta.meta_key = '_product_id' AND
				item_meta.meta_value = '%s' AND
                                q.meta_key = '_qty' AND
				post_meta.meta_key = '_wc_pre_orders_status' AND
				post_meta.meta_value = 'active'
			", $product_id
            ));

            return (int) $number;
        }

        add_action('woocommerce_order_status_pre-ordered', 'azgb_order_status_pre_ordered');

        function azgb_order_status_pre_ordered($order_id) {
            $order = new WC_Order($order_id);
            $items = $order->get_items();
            foreach ($items as $item) {
                update_post_meta($item['product_id'], 'total_presales', azgb_get_product_total_presales($item['product_id']));
            }
        }

        add_action('wc_pre_orders_completion_check', 'azgb_check_for_pre_orders_to_complete', 10);

        function azgb_check_for_pre_orders_to_complete() {
            global $wc_pre_orders;
            do_action('wc_pre_orders_before_automatic_completion_check');

            $args = array(
                'post_type' => 'shop_order',
                'nopaging' => true,
                'meta_query' => array(
                    array(
                        'key' => '_wc_pre_orders_is_pre_order',
                        'value' => 1,
                    ),
                ),
            );

            if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.2', '>=')) {
                $args['post_status'] = 'wc-pre-ordered';
            } else {
                $args['post_status'] = 'publish';
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'shop_order_status',
                        'field' => 'slug',
                        'terms' => 'pre-ordered'
                    )
                );
            }

            $query = new WP_Query($args);

            if (empty($query->posts)) {
                return;
            }

            $orders_to_complete = array();
            $products_to_disable = array();

            foreach ($query->posts as $order_post) {

                $order = new WC_Order($order_post);

                $product = WC_Pre_Orders_Order::get_pre_order_product($order);

                if (is_null($product)) {
                    continue;
                }

                if (!azgb_is_group_off($product->id)) {

                    // add the pre-order to the list to complete
                    $orders_to_complete[] = $order;

                    // keep track of pre-order products to disable pre-orders on after completion
                    $products_to_disable[] = $product->id;
                }
            }

            // complete the pre-orders
            if (!empty($orders_to_complete)) {
                $wc_pre_orders->manager->complete_pre_orders($orders_to_complete);
            }

            // disable pre-orders on products now that they are available
            if (!empty($products_to_disable)) {
                $wc_pre_orders->manager->disable_pre_orders_for_products(array_unique($products_to_disable));
            }

            do_action('wc_pre_orders_after_automatic_completion_check');
        }

        add_action('wc_pre_orders_completion_check', 'azgb_check_for_pre_order_products_to_reset', 11);

        function azgb_check_for_pre_order_products_to_reset() {
            global $wc_pre_orders;
            do_action('wc_pre_orders_before_products_reset');

            global $wpdb;
            $sql = "SELECT p.id as id"
                    . "FROM {$wpdb->posts} as p "
                    . "LEFT JOIN {$wpdb->postmeta} as po ON po.post_id = p.id "
                    . "LEFT JOIN {$wpdb->postmeta} as ts ON ts.post_id = p.id "
                    . "LEFT JOIN {$wpdb->postmeta} as tps ON tps.post_id = p.id "
                    . "LEFT JOIN {$wpdb->postmeta} as dms ON dms.post_id = p.id "
                    . "WHERE (p.post_status = 'publish') "
                    . "AND p.post_type = 'product' "
                    . "AND po.meta_key = '_wc_pre_orders_enabled' "
                    . "AND po.meta_value = 'yes' "
                    . "AND ts.meta_key = 'total_sales' "
                    . "AND tps.meta_key = 'total_presales' "
                    . "AND dms.meta_key = 'minimum_sales' "
                    . "AND ((CAST(ts.meta_value AS UNSIGNED INTEGER) + CAST(tps.meta_value AS UNSIGNED INTEGER)) >= CAST(dms.meta_value AS UNSIGNED INTEGER) OR (dms.meta_value = '') OR (dms.meta_value IS NULL)) ";
            $pre_order_product_ids = $wpdb->get_col($sql, ARRAY_A);

            if (!empty($pre_order_product_ids)) {
                $wc_pre_orders->manager->disable_pre_orders_for_products($pre_order_product_ids);
            }

            do_action('wc_pre_orders_after_products_reset');
        }

        remove_action('woocommerce_single_product_summary', array($wc_pre_orders->product, 'add_pre_order_product_message'), 11);
        remove_action('woocommerce_after_shop_loop_item_title', array($wc_pre_orders->product, 'add_pre_order_product_message'), 11);
        remove_filter('woocommerce_get_item_data', array($wc_pre_orders->cart, 'get_item_data'), 10, 2);
        remove_filter('woocommerce_order_get_items', array($wc_pre_orders->order, 'add_product_release_date_item_meta'), 10, 2);

        add_filter('wc_pre_orders_pre_order_order_total', 'azgb_pre_orders_pre_order_order_total', 10, 2);

        function azgb_pre_orders_pre_order_order_total($formatted_total, $product) {
            $sales_left = 0;
            $minimum_sales = get_post_meta($product->id, 'minimum_sales', true);
            if (!empty($minimum_sales) && is_numeric($minimum_sales)) {
                $total_sales = get_post_meta($product->id, 'total_sales', true);
                $total_sales = (int) $total_sales + (int) azgb_get_product_total_presales($product->id);
                if ((int) $total_sales < (int) $minimum_sales) {
                    $sales_left = (int) $minimum_sales - (int) $total_sales;
                }
            }
            return str_replace('{sales_left}', $sales_left, $formatted_total);
        }

    }
}

add_action('woocommerce_product_write_panel_tabs', 'azgb_write_panel_tabs');

function azgb_write_panel_tabs() {
    ?>
    <li class="group_buying"><a href="#group_buying"><?php _e('Group buying', 'azgb'); ?></a></li>
    <?php
}

add_action('woocommerce_product_data_panels', 'azgb_data_panels');

function azgb_data_panels() {
    ?>
    <div id="group_buying" class="panel woocommerce_options_panel"><div class="options_group">
            <?php
            woocommerce_wp_text_input(array(
                'id' => 'minimum_sales',
                'label' => __('Minimum sales', 'azgb'),
                'desc_tip' => 'true',
                'description' => __('The deal will only be on if this amount of sales reached during this deal.', 'azgb'),
            ));
            ?>
        </div></div>
    <?php
}

add_action('woocommerce_process_product_meta', 'azgb_save_custom_settings');

function azgb_save_custom_settings($post_id) {

    if (isset($_POST['minimum_sales'])) {
        update_post_meta($post_id, 'minimum_sales', sanitize_text_field($_POST['minimum_sales']));
    }
}

function azgb_is_group_off($id) {
    $minimum_sales = get_post_meta($id, 'minimum_sales', true);
    if (!empty($minimum_sales) && is_numeric($minimum_sales)) {
        $total_sales = (int) get_post_meta($id, 'total_sales', true);
        $total_presales = (int) get_post_meta($id, 'total_presales', true);
        if ((empty($total_sales) && empty($total_presales)) || (($total_sales + $total_presales) < (int) $minimum_sales)) {
            return true;
        }
    }
    return false;
}
