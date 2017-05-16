<?php

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AZ_Vouchers_Table extends WP_List_Table {

    function __construct() {

        $this->index = 0;
        parent::__construct(array(
            'singular' => 'voucher',
            'plural' => 'vouchers',
            'ajax' => false
        ));
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'order' => __('Order', 'azv'),
            'product' => __('Product', 'azv'),
            'voucher_code' => __('Voucher Code', 'azv'),
            'used' => __('Used', 'azv'),
            'vendor' => __('Vendor', 'azv'),
            'buyer' => __('Buyer', 'azv'),
            'date' => __('Date', 'azv'),
        );
        return $columns;
    }

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                /* $1%s */ 'id',
                /* $2%s */ $item->id
        );
    }

    function prepare_items() {
        global $wpdb;

        $_SERVER['REQUEST_URI'] = remove_query_arg('_wp_http_referer', $_SERVER['REQUEST_URI']);

        $per_page = $this->get_items_per_page('voucher_per_page', 10);
        $current_page = $this->get_pagenum();

        $orderby = !empty($_REQUEST['orderby']) ? esc_attr($_REQUEST['orderby']) : 'date';
        $order = (!empty($_REQUEST['order']) && $_REQUEST['order'] == 'asc' ) ? 'ASC' : 'DESC';

        $this->_column_headers = $this->get_column_info();


        $sql = "FROM {$wpdb->order_itemmeta} as v "
                . "LEFT JOIN {$wpdb->prefix}woocommerce_order_items as i ON i.order_item_id = v.order_item_id "
                . "LEFT JOIN {$wpdb->posts} as o ON i.order_id = o.id "
                . "LEFT JOIN {$wpdb->postmeta} as m ON m.post_id = o.id "
                . "LEFT JOIN {$wpdb->users} as u ON m.meta_value = u.id "
                . "LEFT JOIN {$wpdb->order_itemmeta} as p ON p.order_item_id = i.order_item_id "
                . "LEFT JOIN {$wpdb->posts} as pp ON pp.id = p.meta_value "
                . "LEFT JOIN {$wpdb->users} as pv ON pv.id = pp.post_author "
                . "WHERE v.meta_key = '" . get_option('azv_voucher_code', 'Voucher code') . "' "
                . "AND p.meta_key = '_product_id' "
                . "AND m.meta_key = '_customer_user'";

        $max = $wpdb->get_var("SELECT COUNT(*) " . $sql);


        $sql = "SELECT v.meta_value as id, i.order_id as order_id, p.meta_value as product_id, pp.post_title as product_title, REPLACE(v.meta_value, '*', '') as voucher_code, (CASE WHEN left(v.meta_value, 1) = '*' THEN 'Yes' ELSE 'No' END) AS used, o.post_date as date, u.user_login as buyer_name, u.id as buyer_id, pv.user_login as vendor_name, pv.id as vendor_id " . $sql;


        $offset = ( $current_page - 1 ) * $per_page;

        $sql .= " ORDER BY `{$orderby}` {$order} LIMIT {$offset}, {$per_page}";

        $this->items = $wpdb->get_results($sql);

        $this->set_pagination_args(array(
            'total_items' => $max,
            'per_page' => $per_page,
            'total_pages' => ceil($max / $per_page)
        ));
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'order' => array('order_id', false),
            'product' => array('product_title', false),
            'used' => array('used', false),
            'vendor' => array('vendor_name', false),
            'buyer' => array('buyer_name', false),
            'date' => array('date', true),
        );
        return $sortable_columns;
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'order':
                return '<a href="' . admin_url('post.php?post=' . $item->order_id . '&action=edit') . '">' . $item->order_id . '</a>';
            case 'product':
                $parent = get_post_ancestors($item->product_id);
                $product_id = $parent ? $parent[0] : $item->product_id;
                return '<a href="' . admin_url('post.php?post=' . $product_id . '&action=edit') . '">' . $item->product_title . '</a>';
            case 'used':
                return $item->used;
            case 'voucher_code':
                return $item->voucher_code;
            case 'vendor':
                return '<a href="' . admin_url('user-edit.php?user_id=' . $item->vendor_id) . '">' . $item->vendor_name . '</a>';
            case 'buyer':
                return '<a href="' . admin_url('user-edit.php?user_id=' . $item->buyer_id) . '">' . $item->buyer_name . '</a>';
            case 'date':
                return date_i18n(get_option('date_format'), strtotime($item->date));
        }
    }

}

add_action('admin_menu', 'azv_add_menu_items');

function azv_add_menu_items() {
    $hook = add_submenu_page('woocommerce', __('Products vouchers', 'azv'), __('Products vouchers', 'azv'), 'view_woocommerce_reports', 'vouchers', 'azv_vouchers', 'dashicons-tickets-alt', 31);
    add_action("load-$hook", 'azv_vouchers_add_options');
}

function azv_vouchers_add_options() {
    global $VouchersTable;

    $args = array(
        'label' => 'Rows',
        'default' => 10,
        'option' => 'vouchers_per_page'
    );
    add_screen_option('per_page', $args);

    $VouchersTable = new AZ_Vouchers_Table();
}

function azv_vouchers() {
    global $VouchersTable;

    echo '<div class="wrap"><h2>' . __('Products vouchers', 'azv') . '</h2>';

    $VouchersTable->prepare_items();
    $VouchersTable->display();

    echo '</div>';
}
