<?php
include_once('api.php' );
include_once('items.php' );

define('AZEXO_ENVATO_ID', '16350601');

function azexo_purchase_url() {
    return 'http://codecanyon.net/cart/add_items?item_ids=' . AZEXO_ENVATO_ID;
}

function azexo_oauth_script() {
    return 'http://azexo.com/envato/api/server-script.php';
}

function azexo_envato_username() {
    return 'azexo';
}

function azexo_updater_option() {
    return 'azh-settings';
}

function azexo_plugin_name() {
    return 'azexo_html/azexo_html.php';
}

function azexo_updater_page_url() {
    return admin_url('admin.php?page=' . azexo_updater_option());
}

function azexo_envato_market_token() {
    $option = get_option(azexo_updater_option(), array());
    return $option['oauth'][azexo_envato_username()]['access_token'];
}

function azexo_envato_market_items() {
    $option = get_option(azexo_updater_option(), array());
    return $option['items'];
}

function azexo_is_activated() {
    $option = get_option(azexo_updater_option(), array());
    if (isset($option['items'])) {
        foreach ($option['items'] as $item) {
            if ($item['id'] == AZEXO_ENVATO_ID && !empty($item['purchase_code'])) {
                return true;
            }
        }
    }
    return false;
}

function azexo_manage_oauth_token($token) {
    static $_current_manage_token = false;
    if (is_array($token) && !empty($token['access_token'])) {
        if ($_current_manage_token == $token['access_token']) {
            return false; // stop loops when refresh auth fails.
        }
        $_current_manage_token = $token['access_token'];
        // yes! we have an access token. store this in our options so we can get a list of items using it.
        $option = get_option(azexo_updater_option(), array());
        if (!is_array($option)) {
            $option = array();
        }
        if (empty($option['items'])) {
            $option['items'] = array();
        }
        // check if token is expired.
        if (empty($token['expires'])) {
            $token['expires'] = time() + 3600;
        }
        if ($token['expires'] < time() + 120 && !empty($token['oauth_session'])) {
            // time to renew this token!
            $my_theme = wp_get_theme();
            $oauth_nonce = get_option('envato_oauth_' . azexo_envato_username());
            $response = wp_remote_post(azexo_oauth_script(), array(
                'method' => 'POST',
                'timeout' => 10,
                'redirection' => 1,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => array(
                    'oauth_session' => $token['oauth_session'],
                    'oauth_nonce' => $oauth_nonce,
                    'refresh_token' => 'yes',
                    'url' => home_url(),
                    'theme' => $my_theme->get('Name'),
                    'version' => $my_theme->get('Version'),
                ),
                'cookies' => array(),
                    )
            );
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong while trying to retrieve oauth token: $error_message";
            } else {
                $new_token = @json_decode(wp_remote_retrieve_body($response), true);
                $result = false;
                if (is_array($new_token) && !empty($new_token['new_token'])) {
                    $token['access_token'] = $new_token['new_token'];
                    $token['expires'] = time() + 3600;
                }
            }
        }
        if (!isset($option['oauth'])) {
            $option['oauth'] = array();
        }
        // store our 1 hour long token here. we can refresh this token when it comes time to use it again (i.e. during an update)
        $option['oauth'][azexo_envato_username()] = $token;
        update_option(azexo_updater_option(), $option);

        // use this token to get a list of purchased items
        // add this to our items array.
        $response = AZEXO_Envato_Market_API::instance()->request('https://api.envato.com/v3/market/buyer/purchases', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token['access_token'],
            ),
        ));
        $_current_manage_token = false;
        if (is_array($response) && is_array($response['purchases'])) {
            // up to here, add to items array
            foreach ($response['purchases'] as $purchase) {
                // check if this item already exists in the items array.
                $exists = false;
                foreach ($option['items'] as $id => $item) {
                    if (!empty($item['id']) && $item['id'] == $purchase['item']['id']) {
                        $exists = true;
                        // update token.
                        $option['items'][$id]['token'] = $token['access_token'];
                        $option['items'][$id]['token_data'] = $token;
                        $option['items'][$id]['oauth'] = azexo_envato_username();
                        if (!empty($purchase['code'])) {
                            $option['items'][$id]['purchase_code'] = $purchase['code'];
                        }
                    }
                }
                if (!$exists) {
                    $option['items'][] = array(
                        'id' => '' . $purchase['item']['id'],
                        // item id needs to be a string for market download to work correctly.
                        'name' => $purchase['item']['name'],
                        'token' => $token['access_token'],
                        'token_data' => $token,
                        'oauth' => azexo_envato_username(),
                        'type' => !empty($purchase['item']['wordpress_theme_metadata']) ? 'theme' : 'plugin',
                        'purchase_code' => !empty($purchase['code']) ? $purchase['code'] : '',
                    );
                }
            }
        } else {
            return false;
        }
        update_option(azexo_updater_option(), $option);

        AZEXO_Envato_Market_Items::instance()->set_themes(true);
        AZEXO_Envato_Market_Items::instance()->set_plugins(true);

        return true;
    } else {
        return false;
    }
}

function azexo_oauth_login_callback() {
    $option = get_option(azexo_updater_option(), array());
    if (isset($option['items']) && !azexo_is_activated()) {
        ?>
        <p class="oauth-login" data-username="<?php echo esc_attr(azexo_envato_username()); ?>">
            <a href="<?php echo esc_url(azexo_purchase_url()); ?>" class="button button-primary" target="_blank">
                <?php echo esc_html_e('Purchase license of AZEXO HTML Customizer to activate it', 'azh'); ?>
            </a>
            <?php echo esc_html_e('or', 'azh'); ?>
            <a href="<?php echo esc_url(azexo_get_oauth_login_url(admin_url('admin.php?page=' . azexo_updater_option()))); ?>" class="oauth-login-button button button-primary">
                <?php echo esc_html_e('Refresh purchased licenses from Envato', 'azh'); ?>
            </a>
        </p>
        <?php
    } else {
        ?>
        <p class="oauth-login" data-username="<?php echo esc_attr(azexo_envato_username()); ?>">
            <a href="<?php echo esc_url(azexo_get_oauth_login_url(admin_url('admin.php?page=' . azexo_updater_option()))); ?>" class="oauth-login-button button button-primary">
                <?php echo esc_html_e('Activate AZEXO HTML Customizer', 'azh'); ?>
            </a>
        </p>
        <?php
    }
}

/// a better filter would be on the post-option get filter for the items array.
// we can update the token there.

function azexo_get_oauth_login_url($return) {
    return azexo_oauth_script() . '?bounce_nonce=' . wp_create_nonce('envato_oauth_bounce_' . azexo_envato_username()) . '&wp_return=' . urlencode($return);
}

add_action('admin_init', 'azexo_envato_market_admin_init', 20);

function azexo_envato_market_admin_init() {

    // pull our custom options across to envato.
    $option = get_option(azexo_updater_option(), array());
    //add_thickbox();

    if (!empty($_POST['oauth_session']) && !empty($_POST['bounce_nonce']) && wp_verify_nonce($_POST['bounce_nonce'], 'envato_oauth_bounce_' . azexo_envato_username())) {
        // request the token from our bounce url.
        $my_theme = wp_get_theme();
        $oauth_nonce = get_option('envato_oauth_' . azexo_envato_username());
        if (!$oauth_nonce) {
            // this is our 'private key' that is used to request a token from our api bounce server.
            // only hosts with this key are allowed to request a token and a refresh token
            // the first time this key is used, it is set and locked on the server.
            $oauth_nonce = wp_create_nonce('envato_oauth_nonce_' . azexo_envato_username());
            update_option('envato_oauth_' . azexo_envato_username(), $oauth_nonce);
        }
        $response = wp_remote_post(azexo_oauth_script(), array(
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 1,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array(
                'oauth_session' => $_POST['oauth_session'],
                'oauth_nonce' => $oauth_nonce,
                'get_token' => 'yes',
                'url' => home_url(),
                'theme' => $my_theme->get('Name'),
                'version' => $my_theme->get('Version'),
            ),
            'cookies' => array(),
                )
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $class = 'error';
            echo "<div class=\"$class\"><p>" . sprintf(esc_html__('Something went wrong while trying to retrieve oauth token: %s', 'azh'), $error_message) . '</p></div>';
        } else {
            $token = @json_decode(wp_remote_retrieve_body($response), true);
            $result = false;
            if (is_array($token) && !empty($token['access_token'])) {
                $token['oauth_session'] = $_POST['oauth_session'];
                $result = azexo_manage_oauth_token($token);
            }
            if ($result !== true) {
                echo esc_html__('Failed to get oAuth token. Please go back and try again', 'azh');
                exit;
            }
        }
    }
}

add_filter('http_request_args', 'azexo_envato_market_http_request_args', 10, 2);

function azexo_envato_market_http_request_args($args, $url) {
    if (strpos($url, 'api.envato.com')) {
        // we have an API request.
        // check if it's using an expired token.
        if (!empty($args['headers']['Authorization'])) {
            $token = str_replace('Bearer ', '', $args['headers']['Authorization']);
            if ($token) {
                // check our options for a list of active oauth tokens and see if one matches, for this envato username.
                $option = get_option(azexo_updater_option(), array());
                if ($option && !empty($option['oauth'][azexo_envato_username()]) && $option['oauth'][azexo_envato_username()]['access_token'] == $token && $option['oauth'][azexo_envato_username()]['expires'] < time() + 120) {
                    // we've found an expired token for this oauth user!
                    // time to hit up our bounce server for a refresh of this token and update associated data.
                    azexo_manage_oauth_token($option['oauth'][azexo_envato_username()]);
                    $updated_option = get_option(azexo_updater_option(), array());
                    if ($updated_option && !empty($updated_option['oauth'][azexo_envato_username()]['access_token'])) {
                        // hopefully this means we have an updated access token to deal with.
                        $args['headers']['Authorization'] = 'Bearer ' . $updated_option['oauth'][azexo_envato_username()]['access_token'];
                    }
                }
            }
        }
    }

    return $args;
}

add_action('admin_notices', 'azexo_updater_notices');

function azexo_updater_notices() {
    if (!azexo_is_activated()) {
        $redirect = esc_url(azexo_updater_page_url());
        ?>
        <style>
            .azexo_license-activation-notice {
                position: relative;
            }
        </style>
        <script type="text/javascript">
            (function($) {
                var setCookie = function(c_name, value, exdays) {
                    var exdate = new Date();
                    exdate.setDate(exdate.getDate() + exdays);
                    var c_value = encodeURIComponent(value) + ((null === exdays) ? "" : "; expires=" + exdate.toUTCString());
                    document.cookie = c_name + "=" + c_value;
                };
                $(document).on('click.azexonotice-dismiss',
                        '.azexonotice-dismiss',
                        function(e) {
                            e.preventDefault();
                            var $el = $(this).closest(
                                    '#azexo_license-activation-notice');
                            $el.fadeTo(100, 0, function() {
                                $el.slideUp(100, function() {
                                    $el.remove();
                                });
                            });
                        });
            })(window.jQuery);
        </script>
        <?php
        echo '<div class="updated azexo_license-activation-notice" id="azexo_license-activation-notice"><p>' . sprintf(__('Hola! Would you like to receive automatic updates and unlock premium support? Please <a href="%s">activate your copy</a> of AZEXO HTML Customizer.', 'azh'), wp_nonce_url($redirect)) . '</p>' . '<button type="button" class="notice-dismiss azexonotice-dismiss"><span class="screen-reader-text">' . __('Dismiss this notice.') . '</span></button></div>';
    }
}

add_action('in_plugin_update_message-' . azexo_plugin_name(), 'azexo_add_update_message');

function azexo_add_update_message() {
    if (!azexo_is_activated()) {
        $url = esc_url(azexo_updater_page_url());
        $redirect = sprintf('<a href="%s" target="_blank">%s</a>', $url, __('settings', 'azh'));
        echo sprintf(' ' . __('To receive automatic updates license activation is required. Please visit %s to activate your AZEXO HTML Customizer.', 'azh'), $redirect);
    }
}
