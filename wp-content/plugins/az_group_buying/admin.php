<?php

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Deals_Orders_Table extends WP_List_Table {

    function __construct() {

        $this->index = 0;
        parent::__construct(array(
            'singular' => 'order',
            'plural' => 'orders',
            'ajax' => false
        ));
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'order' => __('Order', 'azgb'),
            'date' => __('Date', 'azgb'),
            'product' => __('Product', 'azgb'),
            'buyer' => __('Buyer', 'azgb'),
            'vendor' => __('Vendor', 'azgb'),
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

    function get_views() {
        $views = array();
        $current = (!empty($_REQUEST['mode']) ? $_REQUEST['mode'] : 'failed');

        $class = ($current == 'failed' ? ' class="current"' : '');
        $failed_url = remove_query_arg('mode');
        $views['failed'] = "<a href='{$failed_url }' {$class} >" . esc_html__('Failed deals', 'azgb') . "</a>";

        $not_active_url = add_query_arg('mode', 'not_active');
        $class = ($current == 'not_active' ? ' class="current"' : '');
        $views['not_active'] = "<a href='{$not_active_url}' {$class} >" . esc_html__('Not active deals', 'azgb') . "</a>";

        return $views;
    }

    function prepare_items() {
        global $wpdb;

        $_SERVER['REQUEST_URI'] = remove_query_arg('_wp_http_referer', $_SERVER['REQUEST_URI']);
        $mode = ( isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'failed');

        $per_page = $this->get_items_per_page('voucher_per_page', 10);
        $current_page = $this->get_pagenum();

        $orderby = !empty($_REQUEST['orderby']) ? esc_attr($_REQUEST['orderby']) : 'date';
        $order = (!empty($_REQUEST['order']) && $_REQUEST['order'] == 'asc' ) ? 'ASC' : 'DESC';

        $this->_column_headers = $this->get_column_info();



        switch ($mode) {
            case 'failed':
                $sql = "FROM {$wpdb->prefix}woocommerce_order_items as i "
                        . "LEFT JOIN {$wpdb->posts} as o ON i.order_id = o.id "
                        . "LEFT JOIN {$wpdb->order_itemmeta} as p ON p.order_item_id = i.order_item_id "
                        . "LEFT JOIN {$wpdb->postmeta} as m ON m.post_id = o.id "
                        . "LEFT JOIN {$wpdb->users} as u ON m.meta_value = u.id "
                        . "LEFT JOIN {$wpdb->posts} as pp ON pp.id = p.meta_value "
                        . "LEFT JOIN {$wpdb->users} as pv ON pv.id = pp.post_author "
                        . "LEFT JOIN {$wpdb->postmeta} as dms ON dms.post_id = pp.id "
                        . "LEFT JOIN {$wpdb->postmeta} as ts ON ts.post_id = pp.id "
                        . "LEFT JOIN {$wpdb->postmeta} as spdt ON spdt.post_id = pp.id "
                        . "LEFT JOIN {$wpdb->posts} AS r ON ( r.post_type = 'shop_order_refund' AND r.post_parent = o.id ) "
                        . "WHERE p.meta_key = '_product_id' "
                        . "AND dms.meta_key = 'minimum_sales' "
                        . "AND dms.meta_value IS NOT NULL "
                        . "AND ts.meta_key = 'total_sales' "
                        . "AND ts.meta_value IS NOT NULL "
                        . "AND spdt.meta_key = '_sale_price_dates_to' "
                        . "AND spdt.meta_value IS NOT NULL "
                        . "AND (CAST(dms.meta_value AS UNSIGNED INTEGER) > CAST(ts.meta_value AS UNSIGNED INTEGER)) "
                        . "AND CAST(spdt.meta_value AS UNSIGNED INTEGER) < UNIX_TIMESTAMP() "
                        . "AND r.id IS NULL "
                        . "AND m.meta_key = '_customer_user'";
                break;
            case 'not_active':
                $sql = "FROM {$wpdb->prefix}woocommerce_order_items as i "
                        . "LEFT JOIN {$wpdb->posts} as o ON i.order_id = o.id "
                        . "LEFT JOIN {$wpdb->order_itemmeta} as p ON p.order_item_id = i.order_item_id "
                        . "LEFT JOIN {$wpdb->postmeta} as m ON m.post_id = o.id "
                        . "LEFT JOIN {$wpdb->users} as u ON m.meta_value = u.id "
                        . "LEFT JOIN {$wpdb->posts} as pp ON pp.id = p.meta_value "
                        . "LEFT JOIN {$wpdb->users} as pv ON pv.id = pp.post_author "
                        . "LEFT JOIN {$wpdb->postmeta} as dms ON dms.post_id = pp.id "
                        . "LEFT JOIN {$wpdb->postmeta} as ts ON ts.post_id = pp.id "
                        . "LEFT JOIN {$wpdb->postmeta} as spdt ON spdt.post_id = pp.id "
                        . "WHERE p.meta_key = '_product_id' "
                        . "AND dms.meta_key = 'minimum_sales' "
                        . "AND dms.meta_value IS NOT NULL "
                        . "AND ts.meta_key = 'total_sales' "
                        . "AND ts.meta_value IS NOT NULL "
                        . "AND spdt.meta_key = '_sale_price_dates_to' "
                        . "AND spdt.meta_value IS NOT NULL "
                        . "AND (CAST(dms.meta_value AS UNSIGNED INTEGER) > CAST(ts.meta_value AS UNSIGNED INTEGER)) "
                        . "AND CAST(spdt.meta_value AS UNSIGNED INTEGER) > UNIX_TIMESTAMP() "
                        . "AND m.meta_key = '_customer_user'";
                break;
        }


        $max = $wpdb->get_var("SELECT COUNT(*) " . $sql);


        $sql = "SELECT i.order_item_id as id, o.id as order_id, p.meta_value as product_id, pp.post_title as product_title, o.post_date as date, u.user_login as buyer_name, u.id as buyer_id, pv.user_login as vendor_name, pv.id as vendor_id " . $sql;


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
            case 'vendor':
                return '<a href="' . admin_url('user-edit.php?user_id=' . $item->vendor_id) . '">' . $item->vendor_name . '</a>';
            case 'buyer':
                return '<a href="' . admin_url('user-edit.php?user_id=' . $item->buyer_id) . '">' . $item->buyer_name . '</a>';
            case 'date':
                return date_i18n(get_option('date_format'), strtotime($item->date));
        }
    }

}

add_action('admin_menu', 'azgb_add_menu_items');

function azgb_add_menu_items() {
    if (!class_exists('WC_Pre_Orders')) {
        $hook = add_submenu_page('woocommerce', __('Deals orders', 'azgb'), __('Deals orders', 'azgb'), 'view_woocommerce_reports', 'deals_orders', 'azgb_deals_orders', 'dashicons-migrate', 32);
        add_action("load-$hook", 'azgb_deals_orders_add_options');
    }
}

function azgb_deals_orders_add_options() {
    global $DealsOrdersTable;

    $args = array(
        'label' => 'Rows',
        'default' => 10,
        'option' => 'orders_per_page'
    );
    add_screen_option('per_page', $args);

    $DealsOrdersTable = new Deals_Orders_Table();
}

function azgb_deals_orders() {
    global $DealsOrdersTable;

    echo '<div class="wrap"><h2>' . __('Deals orders', 'azgb') . '</h2>';

    $DealsOrdersTable->prepare_items();
    $DealsOrdersTable->views();
    $DealsOrdersTable->display();

    echo '</div>';
}
