<?php
/*
  Plugin Name: AZEXO Deals
  Plugin URI: http://azexo.com
  Description: AZEXO WooCommerce Deals
  Text Domain: azd
  Domain Path: /languages
  Version: 1.24
  Author: azexo
  Author URI: http://azexo.com
  License: GNU General Public License version 3.0
 */

define('AZD_URL', plugins_url('', __FILE__));
define('AZD_DIR', trailingslashit(dirname(__FILE__)) . '/');

add_action('plugins_loaded', 'azd_plugins_loaded');

function azd_plugins_loaded() {
    load_plugin_textdomain('azd', FALSE, basename(dirname(__FILE__)) . '/languages/');
}

add_action('init', 'azd_init');

function azd_init() {
    wp_enqueue_script('azd_deals', AZD_URL . '/js/azwoo_deals.js', array('jquery'), false, true);
    register_taxonomy('location', array('product'), array(
        'label' => __('Location', 'azd'),
        'hierarchical' => true,
        'labels' => array(
            'name' => __('Location', 'azd'),
            'singular_name' => __('Location', 'azd'),
            'menu_name' => __('Location', 'azd'),
            'all_items' => __('All Locations', 'azd'),
            'edit_item' => __('Edit Location', 'azd'),
            'view_item' => __('View Location', 'azd'),
            'update_item' => __('Update Location', 'azd'),
            'add_new_item' => __('Add New Location', 'azd'),
            'new_item_name' => __('New Location Name', 'azd'),
            'parent_item' => __('Parent Location', 'azd'),
            'parent_item_colon' => __('Parent Location:', 'azd'),
            'search_items' => __('Search Locations', 'azd'),
            'popular_items' => __('Popular Locations', 'azd'),
            'separate_items_with_commas' => __('Separate locations with commas', 'azd'),
            'add_or_remove_items' => __('Add or remove locations', 'azd'),
            'choose_from_most_used' => __('Choose from the most used locations', 'azd'),
            'not_found' => __('No locations found', 'azd'),
        )
    ));
    $role = get_role('vendor');
    if ($role) {
        $role->add_cap('upload_files');
    }

    remove_action('woocommerce_single_product_summary', 'woo_vou_display_expiry_product');
}

add_action('admin_enqueue_scripts', 'azd_admin_enqueue_scripts');

function azd_admin_enqueue_scripts() {
    wp_enqueue_script('azd-backend', AZD_URL . '/js/backend.js');
    wp_enqueue_style('azd-backend', AZD_URL . '/css/backend.css');
}

add_action('wp_enqueue_scripts', 'azd_enqueue_scripts', 11);

function azd_enqueue_scripts() {
    if (function_exists('woo_vou_plugin_loaded')) {
        wp_dequeue_style('woo-vou-public-style');
    }
}

add_shortcode('azd-voucher-check', 'azd_voucher_check');

function azd_voucher_check($atts) {
    if (function_exists('woo_vou_plugin_loaded')) {
        wp_register_script('woo-vou-check-code-script', WOO_VOU_URL . 'includes/js/woo-vou-check-code.js', array(), WOO_VOU_PLUGIN_VERSION);
        wp_enqueue_script('woo-vou-check-code-script');

        wp_localize_script('woo-vou-check-code-script', 'WooVouCheck', array(
            'ajaxurl' => admin_url('admin-ajax.php', ( is_ssl() ? 'https' : 'http')),
            'check_code_error' => __('Please enter voucher code.', 'azd'),
            'code_invalid' => __('Voucher code doest not exist.', 'azd'),
            'code_used_success' => __('Thank you for your business, voucher code submitted successfully.', 'azd')
        ));
        wp_register_script('woo-vou-public-script', WOO_VOU_URL . 'includes/js/woo-vou-public.js', array(), WOO_VOU_PLUGIN_VERSION);
        wp_enqueue_script('woo-vou-public-script');
        wp_enqueue_style('woo-vou', AZD_URL . '/css/woo-vou.css');
        return do_shortcode('[woo_vou_check_code]');
    }
}

add_filter('woocommerce_product_tabs', 'azd_product_tabs');

function azd_product_tabs($tabs = array()) {
    global $product;
    $deal_markers = get_post_meta($product->id, 'deal_markers', true);
    if (!empty($deal_markers)) {
        $tabs['map'] = array(
            'title' => __('Maps and Directions', 'azd'),
            'priority' => 25,
            'callback' => 'azd_product_map_tab'
        );
    }
    return $tabs;
}

function azd_product_map_tab() {
    global $product;
    $deal_markers = get_post_meta($product->id, 'deal_markers', true);
    wc_get_template('map_tab.php', array('deal_markers' => $deal_markers), '', AZD_DIR . 'templates/');
}

function azd_is_post_type_query($query, $post_type) {
    if (!$query->is_main_query()) {
        return false;
    }
    $post_types = $query->get('post_type');
    if (!is_array($post_types)) {
        $post_types = array($post_types);
    }

    $taxonomy = false;
    $taxonomy_names = get_object_taxonomies($post_type);
    foreach ($taxonomy_names as $taxonomy_name) {
        if ($query->get($taxonomy_name)) {
            $taxonomy = true;
        }
    }
    return (in_array($post_type, $post_types) && count($post_types) == 1) || $taxonomy;
}

add_shortcode('azd-google-map', 'azd_google_map');

function azd_google_map($atts, $content = null) {
    $atts = shortcode_atts(array(
        'image_size' => 'shop_catalog',
        'all' => false,
            ), $atts, 'azd-google-map');

    global $wp_query;
    $query_vars = false;
    if (azl_is_post_type_query($wp_query, 'product')) {
        $query_vars = $wp_query->query_vars;
        $query_vars['nopaging'] = true;
        $query_vars['posts_per_page'] = -1;
    }
    if ($atts['all']) {
        $query_vars = array(
            'post_type' => 'product',
            'nopaging' => true,
            'posts_per_page' => -1,
        );
    }
    if ($query_vars) {
        $query = new WP_Query($query_vars);
        $deals = array();
        if (count($query->posts) > 0) {
            foreach ($query->posts as $product) {
                $post_metas = get_post_meta($product->ID);
                if (isset($post_metas['deal_markers']) && !empty($post_metas['deal_markers'])) {

                    $allowed = apply_filters('azd_google_map_meta_allowed', array('deal_markers', '_price', '_regular_price', '_sale_price', '_stock', 'total_sales'));
                    $post_metas = array_intersect_key($post_metas, array_flip($allowed));

                    $product_cats = wp_get_post_terms($product->ID, 'product_cat', array('parent' => 0));
                    $cat_image = '';
                    if (!empty($product_cats)) {
                        $product_cat = reset($product_cats);
                        $cat_thumbnail_id = get_woocommerce_term_meta($product_cat->term_id, 'thumbnail_id', true);
                        if ($cat_thumbnail_id) {
                            $cat_image = wp_get_attachment_url($cat_thumbnail_id);
                        }
                    }

                    $src = wp_get_attachment_image_src(get_post_thumbnail_id($product->ID), $atts['image_size']);
                    $deals[] = array_merge(array(
                        'product_cat' => $cat_image,
                        'url' => get_permalink($product->ID),
                        'title' => $product->post_title,
                        'description' => $product->post_excerpt,
                        'image' => $src[0]
                            ), $post_metas);
                }
            }
            wc_get_template('map.php', array('deals' => $deals), '', AZD_DIR . 'templates/');
        }
    }
}

function azd_is_group_off($id) {
    $deal_minimum_sales = get_post_meta($id, 'minimum_sales', true);
    if (!empty($deal_minimum_sales) && is_numeric($deal_minimum_sales)) {
        $total_sales = (int) get_post_meta($id, 'total_sales', true);
        $total_presales = (int) get_post_meta($id, 'total_presales', true);
        if ((empty($total_sales) && empty($total_presales)) || (($total_sales + $total_presales) < (int) $deal_minimum_sales)) {
            return true;
        }
    }
    return false;
}

add_action('azl_post_updated', 'azd_post_updated');

function azd_post_updated($post_ID) {
    $post = get_post($post_ID);
    if ($post->post_type == 'product') {

        $product_url = get_post_meta($post_ID, '_product_url', true);
        if (empty($product_url)) {
            update_post_meta($post_ID, '_virtual', 'yes');
        } else {
            wp_set_object_terms($post_ID, 'external', 'product_type', false);
        }

        if (class_exists('WC_Pre_Orders')) {
            $minimum_sales = get_post_meta($post_ID, 'minimum_sales', true);
            if (!empty($minimum_sales)) {
                update_post_meta($post_ID, '_wc_pre_orders_enabled', 'yes');
            }
        }

        if (function_exists('woo_vou_plugin_loaded')) {
            update_post_meta($post_ID, '_downloadable', 'yes');
            update_post_meta($post_ID, '_woo_vou_enable', 'yes');
            update_post_meta($post_ID, '_woo_vou_vendor_user', $post->post_author);
            $item_vouchers = get_post_meta($post_ID, 'item_vouchers', true);
            if (empty($item_vouchers)) {
                $stock = get_post_meta($post_ID, '_stock', true);
                if (is_numeric($stock)) {
                    $vouchers = array_map(function() {
                        global $woo_vou_model;
                        return $woo_vou_model->woo_vou_get_pattern_string('LLDDD');
                    }, array_fill(0, $stock, ''));
                    update_post_meta($post_ID, '_woo_vou_codes', implode(',', $vouchers));
                    update_post_meta($post_ID, '_woo_vou_using_type', '0');
                } else {
                    update_post_meta($post_ID, '_woo_vou_using_type', '1');
                }
            } else {
                update_post_meta($post_ID, '_woo_vou_codes', implode(',', array_map('trim', preg_split("/[\s,]+/", $item_vouchers))));
            }
            $voucher_expire = get_post_meta($post_ID, 'voucher_expire', true);
            if (is_numeric($voucher_expire)) {
                update_post_meta($post_ID, '_woo_vou_exp_type', 'based_on_purchase');
                update_post_meta($post_ID, '_woo_vou_days_diff', $voucher_expire);
            } else {
                $expire = get_post_meta($post_ID, '_sale_price_dates_to', true);
                if (is_numeric($expire)) {
                    update_post_meta($post_ID, '_woo_vou_exp_type', 'specific_date');
                    update_post_meta($post_ID, '_woo_vou_exp_date', $expire);
                }
            }
        }
    }
}

add_action('woocommerce_product_write_panel_tabs', 'azd_write_panel_tabs');

function azd_write_panel_tabs() {
    ?>
    <li class="deal"><a href="#deal"><?php _e('Deal', 'azd'); ?></a></li>
    <?php
}

add_action('woocommerce_product_data_panels', 'azd_data_panels');

function azd_data_panels() {
    ?>
    <div id="deal" class="panel woocommerce_options_panel"><div class="options_group">
            <?php
            woocommerce_wp_textarea_input(
                    array(
                        'id' => 'deal_markers',
                        'label' => __('Markers', 'azd'),
                        'desc_tip' => 'true',
                        'description' => __('Set places where customers can use their vauchers. Use comma separated Latitude & Longitude for google map markers. One line - one marker.', 'azd'),
            ));
            ?>
        </div></div>
    <?php
}

add_action('woocommerce_process_product_meta', 'azd_save_custom_settings');

function azd_save_custom_settings($post_id) {
    if (isset($_POST['deal_markers'])) {
        update_post_meta($post_id, 'deal_markers', sanitize_text_field($_POST['deal_markers']));
    }
}
