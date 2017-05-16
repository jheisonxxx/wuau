<?php

function azexo_tgmpa_register() {

    $plugins = array(
        array(
            'name' => esc_html__('Redux Framework', 'AZEXO'),
            'slug' => 'redux-framework',
            'required' => true,
        ),
        array(
            'name' => esc_html__('Custom classes for page/post', 'AZEXO'),
            'slug' => 'custom-classes',
            'source' => get_template_directory() . '/plugins/custom-classes.zip',
            'required' => true,
            'version' => '0.1',
        ),
        array(
            'name' => esc_html__('WordPress Importer', 'AZEXO'),
            'slug' => 'wordpress-importer',
            'required' => true,
        ),
        array(
            'name' => esc_html__('WP-LESS', 'AZEXO'),
            'slug' => 'wp-less',
        ),
        array(
            'name' => esc_html__('Infinite scroll', 'AZEXO'),
            'slug' => 'infinite-scroll',
        ),
        array(
            'name' => esc_html__('Widget CSS Classes', 'AZEXO'),
            'slug' => 'widget-css-classes',
        ),
        array(
            'name' => esc_html__('JP Widget Visibility', 'AZEXO'),
            'slug' => 'jetpack-widget-visibility',
        ),
        array(
            'name' => esc_html__('Contact Form 7', 'AZEXO'),
            'slug' => 'contact-form-7',
        ),
        array(
            'name' => esc_html__('Custom Sidebars', 'AZEXO'),
            'slug' => 'custom-sidebars',
        ),
    );
    $plugin_path = get_template_directory() . '/plugins/js_composer.zip';
    if (file_exists($plugin_path)) {
        $plugins[] = array(
            'name' => esc_html__('WPBakery Visual Composer', 'AZEXO'),
            'slug' => 'js_composer',
            'source' => get_template_directory() . '/plugins/js_composer.zip',
            'required' => true,
            'version' => '5.1.1',
            'external_url' => '',
        );
    }
    $plugins = apply_filters('azexo_plugins', $plugins);
    if (!empty($plugins)) {
        tgmpa($plugins, array());
    }


    $additional_plugins = array(
        'vc_widgets' => esc_html__('Visual Composer Widgets', 'AZEXO'),
        'azexo_vc_elements' => esc_html__('AZEXO Visual Composer elements', 'AZEXO'),
        'az_social_login' => esc_html__('AZEXO Social Login', 'AZEXO'),
        'az_email_verification' => esc_html__('AZEXO Email Verification', 'AZEXO'),
        'az_likes' => esc_html__('AZEXO Post/Comments likes', 'AZEXO'),
        'az_voting' => esc_html__('AZEXO Voting', 'AZEXO'),
        'azexo_html' => esc_html__('AZEXO HTML cusomizer', 'AZEXO'),
        'azh_extension' => esc_html__('AZEXO HTML Library', 'AZEXO'),
        'az_listings' => esc_html__('AZEXO Listings', 'AZEXO'),
        'az_query_form' => esc_html__('AZEXO Query Form', 'AZEXO'),
        'az_group_buying' => esc_html__('AZEXO Group Buying', 'AZEXO'),
        'az_vouchers' => esc_html__('AZEXO Vouchers', 'AZEXO'),
        'az_bookings' => esc_html__('AZEXO Bookings', 'AZEXO'),
        'az_deals' => esc_html__('AZEXO Deals', 'AZEXO'),
        'az_sport_club' => esc_html__('AZEXO Sport Club', 'AZEXO'),
        'az_locations' => esc_html__('AZEXO Locations', 'AZEXO'),
        'circular_countdown' => esc_html__('Circular CountDown', 'AZEXO'),
    );
    $plugins = array();
    foreach ($additional_plugins as $additional_plugin_slug => $additional_plugin_name) {
        $plugin_path = get_template_directory() . '/plugins/' . $additional_plugin_slug . '.zip';
        if (file_exists($plugin_path)) {
            $plugins[] = array(
                        'name' => $additional_plugin_name,
                        'slug' => $additional_plugin_slug,
                        'source' => $plugin_path,
                        'required' => true,
                        'version' => AZEXO_FRAMEWORK_VERSION,
            );
        }
    }
    $plugins = apply_filters('azexo_plugins', $plugins);
    if (!empty($plugins)) {
        tgmpa($plugins, array(
//            'is_automatic' => true,
        ));
    }
}

add_action('tgmpa_register', 'azexo_tgmpa_register');
