<?php
add_action('admin_menu', 'azh_admin_menu');

function azh_admin_menu() {
    add_menu_page(__('HTML Customizer', 'azh'), __('HTML Customizer', 'azh'), 'manage_options', 'azh-settings', 'azh_settings_page');
}

function azh_settings_page() {
    wp_enqueue_style('azh_admin', plugins_url('css/admin.css', __FILE__));
    ?>

    <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e('AZEXO HTML Customizer Settings', 'azh'); ?></h2>

        <form method="post" action="options.php" class="azh-form">
            <?php
            settings_errors();
            settings_fields('azh-settings');
            do_settings_sections('azh-settings');
            submit_button(__('Save Settings', 'azh'));
            ?>
        </form>
    </div>

    <?php
}

function azh_general_options_callback() {
    
}

function azh_license_callback() {
    
}

function azh_settings_sanitize_callback($input) {
    $input = apply_filters('azh_settings_sanitize_callback', $input);
    return $input;
}

function azh_active_license_callback() {
    ?>
    <p><?php echo esc_html_e('Active license', 'azh'); ?></p>
    <?php
}

add_action('admin_init', 'azh_general_options');

function azh_general_options() {
    register_setting('azh-settings', 'azh-settings', array('sanitize_callback' => 'azh_settings_sanitize_callback'));

    add_settings_section(
            'azh_license_section', // Section ID
            esc_html__('Product license', 'azh'), // Title above settings section
            'azh_license_callback', // Name of function that renders a description of the settings section
            'azh-settings'                     // Page to show on
    );
    if (azexo_is_activated()) {
        add_settings_field(
                'oauth_keys', // Field ID
                esc_html__('Status', 'azh'), // Label to the left
                'azh_active_license_callback', // Name of function that renders options on the page
                'azh-settings', // Page to show on
                'azh_license_section' // Associate with which settings section?
        );
    } else {
        add_settings_field(
                'oauth_keys', // Field ID
                esc_html__('Login with Envato to activate', 'azh'), // Label to the left
                'azexo_oauth_login_callback', // Name of function that renders options on the page
                'azh-settings', // Page to show on
                'azh_license_section' // Associate with which settings section?
        );
    }


    add_settings_section(
            'azh_general_options_section', // Section ID
            esc_html__('General options', 'azh'), // Title above settings section
            'azh_general_options_callback', // Name of function that renders a description of the settings section
            'azh-settings'                     // Page to show on
    );

    add_settings_field(
            'patterns', // Field ID
            esc_html__('Customizer patterns', 'azh'), // Label to the left
            'azh_textarea', // Name of function that renders options on the page
            'azh-settings', // Page to show on
            'azh_general_options_section', // Associate with which settings section?
            array(
        'id' => 'patterns',
            )
    );

    add_settings_field(
            'custom-icons-classes', // Field ID
            esc_html__('Custom icons classes', 'azh'), // Label to the left
            'azh_textarea', // Name of function that renders options on the page
            'azh-settings', // Page to show on
            'azh_general_options_section', // Associate with which settings section?
            array(
        'id' => 'custom-icons-classes',
            )
    );

    add_settings_field(
            'custom-icons-css', // Field ID
            esc_html__('Custom icons css files', 'azh'), // Label to the left
            'azh_textarea', // Name of function that renders options on the page
            'azh-settings', // Page to show on
            'azh_general_options_section', // Associate with which settings section?
            array(
        'id' => 'custom-icons-css',
        'desc' => esc_html('Path relative "azh" theme/plugin folder', 'azh'),
            )
    );
    add_settings_field(
            'container-widths', // Field ID
            esc_html__('Container class widths', 'azh'), // Label to the left
            'azh_textarea', // Name of function that renders options on the page
            'azh-settings', // Page to show on
            'azh_general_options_section', // Associate with which settings section?
            array(
        'id' => 'container-widths',
        'default' => "768px:750px\n"
        . "992px:970px\n"
        . "1200px:1170px",
            )
    );
        
}

function azh_textfield($args) {
    extract($args);
    $settings = get_option('azh-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    if (!isset($type)) {
        $type = 'text';
    }
    ?>
    <input type="<?php print esc_attr($type); ?>" name="azh-settings[<?php print esc_attr($id); ?>]" value="<?php print esc_attr($settings[$id]); ?>">
    <p>
        <em>
            <?php if (isset($desc)) print esc_html($desc); ?>
        </em>
    </p>
    <?php
}

function azh_textarea($args) {
    extract($args);
    $settings = get_option('azh-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    ?>
    <textarea name="azh-settings[<?php print esc_attr($id); ?>]" cols="50" rows="5"><?php print esc_attr($settings[$id]); ?></textarea>
    <p>
        <em>
            <?php if (isset($desc)) print esc_html($desc); ?>
        </em>
    </p>
    <?php
}

function azh_checkbox($args) {
    extract($args);
    $settings = get_option('azh-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    foreach ($options as $value => $label) {
        ?>
        <div>
            <input id="<?php print esc_attr($id) . esc_attr($value); ?>" type="checkbox" name="azh-settings[<?php print esc_attr($id); ?>][<?php print esc_attr($value); ?>]" value="1" <?php @checked($settings[$id][$value], 1); ?>>
            <label for="<?php print esc_attr($id) . esc_attr($value); ?>"><?php print esc_html($label); ?></label>
        </div>
        <?php
    }
    ?>
    <p>
        <em>
            <?php if (isset($desc)) print esc_html($desc); ?>
        </em>
    </p>
    <?php
}

function azh_select($args) {
    extract($args);
    $settings = get_option('azh-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    ?>
    <select name="azh-settings[<?php print esc_attr($id); ?>]">
        <?php
        foreach ($options as $value => $label) {
            ?>
            <option value="<?php print esc_attr($value); ?>" <?php @selected($settings[$id], $value); ?>><?php print esc_html($label); ?></option>
            <?php
        }
        ?>
    </select>
    <p>
        <em>
            <?php if (isset($desc)) print esc_html($desc); ?>
        </em>
    </p>
    <?php
}

function azh_radio($args) {
    extract($args);
    $settings = get_option('azh-settings');
    if (isset($default) && !isset($settings[$id])) {
        $settings[$id] = $default;
    }
    ?>
    <div>
        <?php
        foreach ($options as $value => $label) {
            ?>
            <input id="<?php print esc_attr($id) . esc_attr($value); ?>" type="radio" name="azh-settings[<?php print esc_attr($id); ?>]" value="<?php print esc_attr($value); ?>" <?php @checked($settings[$id], $value); ?>>
            <label for="<?php print esc_attr($id) . esc_attr($value); ?>"><?php print esc_html($label); ?></label>
            <?php
        }
        ?>
    </div>
    <p>
        <em>
            <?php if (isset($desc)) print esc_html($desc); ?>
        </em>
    </p>
    <?php
}
