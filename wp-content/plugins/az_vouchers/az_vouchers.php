<?php
/**
 * Plugin Name:  AZEXO Vouchers
 * Plugin URI:   http://www.azexo.com
 * Description:  Text-code vouchers
 * Author:       AZEXO
 * Author URI:   http://www.azexo.com
 * Version: 1.24
 * Text Domain:  azv
 * Domain Path:  languages
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('AZV_URL', plugins_url('', __FILE__));
define('AZV_DIR', trailingslashit(dirname(__FILE__)) . '/');
define('AZV_PLUGIN_FILE', __FILE__);


add_action('plugins_loaded', 'azv_plugins_loaded');

function azv_plugins_loaded() {
    load_plugin_textdomain('azv', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    if (is_admin()) {
        include_once(AZV_DIR . 'admin.php');
    }
}

add_action('admin_enqueue_scripts', 'azv_enqueue_admin_scripts');

function azv_enqueue_admin_scripts() {
    global $post;
    $screen = get_current_screen();
    if (in_array($screen->id, array('product'))) {
        wp_enqueue_script('azv', plugins_url('js/azv.js', AZV_PLUGIN_FILE), array('jquery'), '1.0', true);
    }
}

add_action('product_type_options', 'azv_product_type_options');

function azv_product_type_options($product_type_options) {
    global $post;

    $is_voucherable = get_post_meta($post->ID, '_voucher_option', true);

    $product_type_options['voucher_option'] = array(
        'id' => '_voucher_option',
        'wrapper_class' => 'show_if_simple show_if_variable show_if_grouped',
        'label' => __('Voucherable', 'azv'),
        'description' => __('Voucherable products generate voucher after payment', 'azv'),
        'default' => $is_voucherable === 'yes' ? 'yes' : 'no'
    );

    return $product_type_options;
}

add_filter('woocommerce_product_data_tabs', 'azv_product_data_tabs');

function azv_product_data_tabs($product_data_tabs) {

    $product_data_tabs['AZV'] = array(
        'label' => __('Vouchers', 'azv'),
        'target' => 'vouchers_product_data',
        'class' => array('show_if_simple show_if_variable show_if_grouped show_if_voucherable'),
    );

    return $product_data_tabs;
}

add_action('woocommerce_product_data_panels', 'azv_data_panels');

function azv_data_panels() {
    ?>
    <div id="vouchers_product_data" class="panel woocommerce_options_panel"><div class="options_group">
            <?php
            woocommerce_wp_text_input(
                    array(
                        'id' => 'voucher_expire',
                        'label' => __('Vouchers Expire (days)', 'azv'),
                        'placeholder' => '',
                        'desc_tip' => 'true',
                        'description' => __('Set number of days after which voucher is expire after purchase or leave empty for unlimited last.', 'azv'),
                        'data_type' => 'decimal'
            ));
            woocommerce_wp_textarea_input(
                    array(
                        'id' => 'item_vouchers',
                        'label' => __('Items Vouchers', 'azv'),
                        'desc_tip' => 'true',
                        'description' => __('If you want to serve predefined vouchers instead of random generated ones, input them here one in a row and make sure that you have same amount of these vouchers as the number of items.', 'azv'),
            ));
            ?>
        </div></div>
    <?php
}

add_action('woocommerce_process_product_meta', 'azv_save_custom_settings');

function azv_save_custom_settings($post_id) {
    $is_voucherable = isset($_POST['_voucher_option']) ? 'yes' : '';
    update_post_meta($post_id, '_voucher_option', $is_voucherable);
    if (isset($_POST['voucher_expire'])) {
        update_post_meta($post_id, 'voucher_expire', sanitize_text_field($_POST['voucher_expire']));
    }
    if (isset($_POST['item_vouchers'])) {
        update_post_meta($post_id, 'item_vouchers', sanitize_text_field($_POST['item_vouchers']));
    }
}

function azv_generate_voucher($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    global $wpdb;
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }

    $exists = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->order_itemmeta} WHERE meta_key = '" . get_option('azv_voucher_code', 'Voucher code') . "' AND meta_value = %s", $random_string));
    $exists = array_shift($exists);
    if (!empty($exists)) {
        $random_string = azv_generate_voucher();
    } else {
        return $random_string;
    }
}

add_action('woocommerce_payment_complete', 'azv_payment_complete');
add_action('woocommerce_order_status_processing', 'azv_payment_complete');
add_action('woocommerce_order_status_completed', 'azv_payment_complete');

function azv_payment_complete($id) {
    $order = new WC_Order($id);
    $items = $order->get_items();
    foreach ($items as $item_id => $item) {
        if ($item['type'] == 'line_item') {
            $is_voucherable = get_post_meta($item['product_id'], '_voucher_option', true);
            if ($is_voucherable == 'yes') {
                $codes = wc_get_order_item_meta($item_id, get_option('azv_voucher_code', 'Voucher code'), false);
                if (!$codes || empty($codes)) {
                    $item_vouchers = get_post_meta($item['product_id'], 'item_vouchers', true);
                    if (!empty($item_vouchers)) {
                        $item_vouchers = array_map('trim', preg_split("/[\s,]+/", $item_vouchers));
                    }
                    for ($i = 0; $i < $item['qty']; $i++) {
                        if (empty($item_vouchers)) {
                            wc_add_order_item_meta($item_id, get_option('azv_voucher_code', 'Voucher code'), azv_generate_voucher());
                        } else {
                            wc_add_order_item_meta($item_id, get_option('azv_voucher_code', 'Voucher code'), array_shift($item_vouchers));
                        }
                    }
                    if (is_array($item_vouchers)) {
                        update_post_meta($item['product_id'], 'item_vouchers', implode("\n", $item_vouchers));
                    }
                }
                $cache_key = WC_Cache_Helper::get_cache_prefix('orders') . 'item_meta_array_' . $item_id;
                wp_cache_set($cache_key, false, 'orders');
            }
        }
    }
}

function azv_set_voucher_status($item_id, $voucher_code, $used) {
    $voucher_code = str_replace('*', '', $voucher_code);
    if ($used) {
        wc_update_order_item_meta($item_id, get_option('azv_voucher_code', 'Voucher code'), '*' . $voucher_code, $voucher_code);
    } else {
        wc_update_order_item_meta($item_id, get_option('azv_voucher_code', 'Voucher code'), $voucher_code, '*' . $voucher_code);
    }
}

function azv_get_vouchers($product_id) {
    global $wpdb;
    $sql = "SELECT i.order_id as order_id, i.order_item_id as item_id, m.meta_value as voucher_expire, o.post_date, v.meta_value as voucher_code "
            . "FROM {$wpdb->order_itemmeta} as v "
            . "LEFT JOIN {$wpdb->prefix}woocommerce_order_items as i ON i.order_item_id = v.order_item_id "
            . "LEFT JOIN {$wpdb->posts} as o ON i.order_id = o.id "
            . "LEFT JOIN {$wpdb->order_itemmeta} as p ON p.order_item_id = i.order_item_id "
            . "LEFT JOIN {$wpdb->posts} as pp ON pp.id = p.meta_value "
            . "LEFT JOIN {$wpdb->postmeta} as m ON m.post_id = pp.id "
            . "WHERE v.meta_key = '" . get_option('azv_voucher_code', 'Voucher code') . "' "
            . "AND p.meta_key = '_product_id' "
            . "AND (m.meta_key = 'voucher_expire' OR m.meta_key IS NULL)  "
            . "AND pp.id = %d "
            . "AND pp.post_author = %d";
    return $wpdb->get_results($wpdb->prepare($sql, $product_id, get_current_user_id()));
}

function azv_get_voucher_order($voucher_code) {
    global $wpdb;
    $sql = "SELECT i.order_id as order_id, i.order_item_id as item_id, m.meta_value as voucher_expire, o.post_date, v.meta_value as voucher_code "
            . "FROM {$wpdb->order_itemmeta} as v "
            . "LEFT JOIN {$wpdb->prefix}woocommerce_order_items as i ON i.order_item_id = v.order_item_id "
            . "LEFT JOIN {$wpdb->posts} as o ON i.order_id = o.id "
            . "LEFT JOIN {$wpdb->order_itemmeta} as p ON p.order_item_id = i.order_item_id "
            . "LEFT JOIN {$wpdb->posts} as pp ON pp.id = p.meta_value "
            . "LEFT JOIN {$wpdb->postmeta} as m ON m.post_id = pp.id "
            . "WHERE v.meta_key = '" . get_option('azv_voucher_code', 'Voucher code') . "' "
            . "AND p.meta_key = '_product_id' "
            . "AND (m.meta_key = 'voucher_expire' OR m.meta_key IS NULL)  "
            . "AND v.meta_value LIKE %s ";
    if (!current_user_can('manage_options')) {
        $sql .= "AND pp.post_author = %d";
        return $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($voucher_code), get_current_user_id()));
    } else {
        return $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($voucher_code)));
    }
}

add_shortcode('azv-voucher-check', 'azv_check_voucher');

function azv_check_voucher($atts = array()) {
    if (is_user_logged_in()) {
        $output = '<form class="azv-voucher-check" action="' . get_permalink() . '" method="post">';
        $output .= '<h3>' . esc_html__('Voucher check form', 'azv') . '</h3>';
        $output .= '<p><input type="text" name="voucher-code" value="' . (isset($_POST['voucher-code']) ? sanitize_text_field($_POST['voucher-code']) : '') . '" placeholder="' . esc_html__('Enter voucher code here', 'azv') . '"></p>';
        $submit = '<p class="submit"><input name="check" type="submit" value="' . esc_html__('Check', 'azv') . '">';

        if (isset($_POST['voucher-code']) && !empty($_POST['voucher-code']) && isset($_POST['azv-nonce']) && wp_verify_nonce($_POST['azv-nonce'], 'voucher')) {
            $voucher_order = azv_get_voucher_order(sanitize_text_field($_POST['voucher-code']));
            if ($voucher_order && isset($_POST['redeem'])) {
                if (isset($_POST['item_id']) && is_numeric($_POST['item_id'])) {
                    azv_set_voucher_status($_POST['item_id'], sanitize_text_field($_POST['voucher-code']), true);
                }
            }
            if ($voucher_order && isset($_POST['revert'])) {
                if (isset($_POST['item_id']) && is_numeric($_POST['item_id'])) {
                    azv_set_voucher_status($_POST['item_id'], sanitize_text_field($_POST['voucher-code']), false);
                }
            }
            $voucher_order = azv_get_voucher_order(sanitize_text_field($_POST['voucher-code']));
            if ($voucher_order) {
                $voucher_order = reset($voucher_order);
                if (is_numeric($voucher_order->voucher_expire)) {
                    if (strtotime($voucher_order->post_date) + $voucher_order->voucher_expire * 24 * 60 * 60 <= current_time('timestamp')) {
                        wc_add_notice(__('Voucher expired', 'azv'), 'error');
                    } else {
                        wc_add_notice(__('Voucher is valid', 'azv'), 'success');
                    }
                }

                if (strpos($voucher_order->voucher_code, '*') === false) {
                    $submit .= '<input type="hidden" name="item_id" value="' . $voucher_order->item_id . '"><input name="redeem" type="submit" value="' . esc_html__('Redeem', 'azv') . '">';
                    wc_add_notice(__('Voucher not used', 'azv'), 'notice');
                } else {
                    $submit .= '<input type="hidden" name="item_id" value="' . $voucher_order->item_id . '"><input name="revert" type="submit" value="' . esc_html__('Mark as not used', 'azv') . '">';
                    wc_add_notice(__('Voucher is used', 'azv'), 'notice');
                }

                $output .= $submit . '</p>';

                ob_start();
                wc_print_notices();
                $output .= ob_get_clean();

                ob_start();
                wc_get_template('myaccount/view-order.php', array(
                    'order' => wc_get_order($voucher_order->order_id),
                    'order_id' => $voucher_order->order_id
                ));
                $output .= ob_get_clean();
            } else {

                $output .= $submit . '</p>';

                wc_add_notice(__('Order not found', 'azv'), 'notice');
                ob_start();
                wc_print_notices();
                $output .= ob_get_clean();
            }
        } else {
            $output .= $submit . '</p>';
        }

        $output .= wp_nonce_field('voucher', 'azv-nonce', true, false);

        $output .= '</form>';
        return $output;
    }
}

add_filter('woocommerce_get_sections_products', 'azv_add_wc_product_section');

function azv_add_wc_product_section($sections) {

    $sections['azv'] = __('AZEXO Vouchers', 'azv');
    return $sections;
}

add_filter('woocommerce_get_settings_products', 'wcslider_all_settings', 10, 2);

function wcslider_all_settings($settings, $current_section) {
    if ($current_section == 'azv') {
        $settings_slider = array();
        $settings_slider[] = array('name' => __('AZEXO Vouchers', 'azv'), 'type' => 'title', 'id' => 'azv');
        $settings_slider[] = array(
            'name' => __('Voucher code meta key', 'azv'),
            'id' => 'azv_voucher_code',
            'type' => 'text',
            'desc' => __('Meta key to display voucher code in order item.', 'azv'),
        );

        $settings_slider[] = array('type' => 'sectionend', 'id' => 'wcslider');
        return $settings_slider;
    } else {
        return $settings;
    }
}
