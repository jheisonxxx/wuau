<?php

/**
 * Plugin Name:  AZEXO Voting
 * Plugin URI:   http://www.azexo.com
 * Description:  Post/Comments voting system
 * Author:       AZEXO
 * Author URI:   http://www.azexo.com
 * Version: 1.24
 * Text Domain:  azpv
 * Domain Path:  languages
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('AZPV_URL', plugins_url('', __FILE__));
define('AZPV_DIR', trailingslashit(dirname(__FILE__)) . '/');

add_action('plugins_loaded', 'azpv_plugins_loaded');

function azpv_plugins_loaded() {
    load_plugin_textdomain('azpv', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('wp_enqueue_scripts', 'azpv_enqueue_scripts');

function azpv_enqueue_scripts() {
    wp_enqueue_script('azpv-voting', AZPV_URL . '/js/voting.js', array('jquery'), false, true);
    wp_localize_script('azpv-voting', 'azpv', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'loading' => esc_html__('Loading...', 'azpv'),
    ));
}

function azpv_vote($vote, $post_id, $is_comment) {
    if (is_user_logged_in()) { // user is logged in
        $user_id = get_current_user_id();

        $post_users = ($vote == 'up' ? azpv_post_user_up_votes($user_id, $post_id, $is_comment) : azpv_post_user_down_votes($user_id, $post_id, $is_comment));
        if ($is_comment == 1) {
            // Update Comment
            if ($post_users) {
                update_comment_meta($post_id, "_user_comment_voted_" . $vote, $post_users);
            }
        } else {
            // Update Post
            if ($post_users) {
                update_post_meta($post_id, "_user_voted_" . $vote, $post_users);
            }
            $voted_posts = get_user_meta($user_id, "_" . $vote . "_voted_posts", true);
            if (empty($voted_posts)) {
                $voted_posts = array();
            }
            $voted_posts[] = $post_id;
            $voted_posts = array_unique($voted_posts);
            update_user_meta($user_id, "_" . $vote . "_voted_posts", $voted_posts);
        }
    } else { // user is anonymous
        $user_ip = azpv_get_ip();
        $post_users = ($vote == 'up' ? azpv_post_ip_up_votes($user_ip, $post_id, $is_comment) : azpv_post_ip_down_votes($user_ip, $post_id, $is_comment));
        // Update Post
        if ($post_users) {
            if ($is_comment == 1) {
                update_comment_meta($post_id, "_user_comment_IP_" . $vote, $post_users);
            } else {
                update_post_meta($post_id, "_user_IP_" . $vote, $post_users);
            }
        }
    }
}

function azpv_unvote($vote, $post_id, $is_comment) {
    if (is_user_logged_in()) { // user is logged in
        $user_id = get_current_user_id();
        $post_users = ($vote == 'up' ? azpv_post_user_up_votes($user_id, $post_id, $is_comment) : azpv_post_user_down_votes($user_id, $post_id, $is_comment));
        // Update Post
        if ($post_users) {
            $uid_key = array_search($user_id, $post_users);
            unset($post_users[$uid_key]);
            if ($is_comment == 1) {
                update_comment_meta($post_id, "_user_comment_voted_" . $vote, $post_users);
            } else {
                update_post_meta($post_id, "_user_voted_" . $vote, $post_users);

                $voted_posts = get_user_meta($user_id, "_" . $vote . "_voted_posts", true);
                if (empty($voted_posts)) {
                    $voted_posts = array();
                }
                $post_key = array_search($post_id, $voted_posts);
                unset($voted_posts[$post_key]);
                update_user_meta($user_id, "_" . $vote . "_voted_posts", $voted_posts);
            }
        }
    } else { // user is anonymous
        $user_ip = azpv_get_ip();
        $post_users = ($vote == 'up' ? azpv_post_ip_up_votes($user_ip, $post_id, $is_comment) : azpv_post_ip_down_votes($user_ip, $post_id, $is_comment));
        // Update Post
        if ($post_users) {
            $uip_key = array_search($user_ip, $post_users);
            unset($post_users[$uip_key]);
            if ($is_comment == 1) {
                update_comment_meta($post_id, "_user_comment_IP_" . $vote, $post_users);
            } else {
                update_post_meta($post_id, "_user_IP_" . $vote, $post_users);
            }
        }
    }
}

function azpv_get_count($post_id, $is_comment) {
    $up_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_up", true) : get_post_meta($post_id, "_user_voted_up", true);
    $down_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_down", true) : get_post_meta($post_id, "_user_voted_down", true);
    $up_ips = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_up", true) : get_post_meta($post_id, "_user_IP_up", true);
    $down_ips = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_down", true) : get_post_meta($post_id, "_user_IP_down", true);
    return (is_array($up_users) ? count($up_users) : 0) + (is_array($up_ips) ? count($up_ips) : 0) + (is_array($down_users) ? count($down_users) : 0) + (is_array($down_ips) ? count($down_ips) : 0);
}

function azpv_get_up_count($post_id, $is_comment) {
    $up_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_up", true) : get_post_meta($post_id, "_user_voted_up", true);
    $up_ips = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_up", true) : get_post_meta($post_id, "_user_IP_up", true);
    return (is_array($up_users) ? count($up_users) : 0) + (is_array($up_ips) ? count($up_ips) : 0);
}

function azpv_get_votes($post_id, $is_comment) {
    $up_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_up", true) : get_post_meta($post_id, "_user_voted_up", true);
    $down_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_down", true) : get_post_meta($post_id, "_user_voted_down", true);
    $up_ips = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_up", true) : get_post_meta($post_id, "_user_IP_up", true);
    $down_ips = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_down", true) : get_post_meta($post_id, "_user_IP_down", true);
    return (is_array($up_users) ? count($up_users) : 0) + (is_array($up_ips) ? count($up_ips) : 0) - (is_array($down_users) ? count($down_users) : 0) - (is_array($down_ips) ? count($down_ips) : 0);
}

add_action('wp_ajax_nopriv_process_vote', 'azpv_process_vote');
add_action('wp_ajax_process_vote', 'azpv_process_vote');

function azpv_process_vote() {
    $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field($_REQUEST['nonce']) : 0;
    if (!wp_verify_nonce($nonce, 'votes-nonce')) {
        exit(esc_html__('Not permitted', 'azpv'));
    }
    // Test if javascript is disabled
    $disabled = ( isset($_REQUEST['disabled']) && $_REQUEST['disabled'] == true ) ? true : false;
    $is_comment = ( isset($_REQUEST['is_comment']) && $_REQUEST['is_comment'] == 1 ) ? 1 : 0;
    $post_id = ( isset($_REQUEST['post_id']) && is_numeric($_REQUEST['post_id']) ) ? $_REQUEST['post_id'] : '';
    $vote = ( isset($_REQUEST['vote']) && $_REQUEST['vote'] == 'up' ) ? 'up' : 'down';
    $result = array();
    $post_users = NULL;
    if ($post_id != '') {
        if ($vote == 'up' && azpv_already_voted_down($post_id, $is_comment)) {
            azpv_unvote('down', $post_id, $is_comment);
        }
        if ($vote == 'down' && azpv_already_voted_up($post_id, $is_comment)) {
            azpv_unvote('up', $post_id, $is_comment);
        }
        $already_voted = ($vote == 'up' ? azpv_already_voted_up($post_id, $is_comment) : azpv_already_voted_down($post_id, $is_comment));
        if (!$already_voted) { // Vote the post
            azpv_vote($vote, $post_id, $is_comment);
            $response['status'] = $vote;
        } else { // Unvote the post
            azpv_unvote($vote, $post_id, $is_comment);
            $response['status'] = '';
        }
        $count = azpv_get_count($post_id, $is_comment);
        if ($is_comment == 1) {
            update_comment_meta($post_id, "_comment_vote_count", $count);
            update_comment_meta($post_id, "_comment_vote_modified", date('Y-m-d H:i:s'));
        } else {
            update_post_meta($post_id, "_post_vote_count", $count);
            update_post_meta($post_id, "_post_vote_modified", date('Y-m-d H:i:s'));
        }
        $votes = azpv_get_votes($post_id, $is_comment);
        $response['formated_votes'] = azpv_format_count($votes);
        $response['formated_count'] = azpv_format_count($count);
        $response['up_count'] = azpv_get_up_count($post_id, $is_comment);
        $response['count'] = $count;
        if ($disabled == true) {
            if ($is_comment == 1) {
                wp_redirect(get_permalink(get_the_ID()));
                exit();
            } else {
                wp_redirect(get_permalink($post_id));
                exit();
            }
        } else {
            wp_send_json($response);
        }
    }
}

function azpv_already_voted_up($post_id, $is_comment) {
    $post_users = NULL;
    $user_id = NULL;
    if (is_user_logged_in()) { // user is logged in
        $user_id = get_current_user_id();
        $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_up", true) : get_post_meta($post_id, "_user_voted_up", true);
    } else { // user is anonymous
        $user_id = azpv_get_ip();
        $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_up", true) : get_post_meta($post_id, "_user_IP_up", true);
    }
    if (is_array($post_users) && in_array($user_id, $post_users)) {
        return true;
    } else {
        return false;
    }
}

function azpv_already_voted_down($post_id, $is_comment) {
    $post_users = NULL;
    $user_id = NULL;
    if (is_user_logged_in()) { // user is logged in
        $user_id = get_current_user_id();
        $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_down", true) : get_post_meta($post_id, "_user_voted_down", true);
    } else { // user is anonymous
        $user_id = azpv_get_ip();
        $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_down", true) : get_post_meta($post_id, "_user_IP_down", true);
    }
    if (is_array($post_users) && in_array($user_id, $post_users)) {
        return true;
    } else {
        return false;
    }
}

function azpv_get_votes_button($post_id, $is_comment = NULL) {
    $is_comment = ( NULL == $is_comment ) ? 0 : 1;
    $output = '';
    $nonce = wp_create_nonce('votes-nonce'); // Security
    if ($is_comment == 1) {
        $post_id_class = esc_attr(' voting-comment-button-' . $post_id);
        $comment_class = esc_attr(' voting-comment');
    } else {
        $post_id_class = esc_attr(' voting-button-' . $post_id);
        $comment_class = esc_attr('');
    }
    $votes = azpv_get_votes($post_id, $is_comment);
    $count = '<span class="voting-votes">' . azpv_format_count($votes) . '</span>';
    // Loader
    $loader = '<span class="voting-loader"></span>';
    // Voted/Unvoted Variables
    $class = '';
    if (azpv_already_voted_up($post_id, $is_comment)) {
        $class = 'up';
    }
    if (azpv_already_voted_down($post_id, $is_comment)) {
        $class = 'down';
    }
    $output = '<span class="voting-wrapper ' . $class . '">'
            . '<a href="' . esc_url(admin_url('admin-ajax.php?action=process_vote' . '&nonce=' . $nonce . '&post_id=' . $post_id . '&vote=up&disabled=true&is_comment=' . $is_comment)) . '" class="up voting-button' . $post_id_class . $comment_class . '" data-nonce="' . $nonce . '" data-post-id="' . $post_id . '" data-vote="up" data-iscomment="' . $is_comment . '" title="' . esc_html__('Up', 'azpv') . '"><span>↑</span></a>'
            . $count . $loader
            . '<a href="' . esc_url(admin_url('admin-ajax.php?action=process_vote' . '&nonce=' . $nonce . '&post_id=' . $post_id . '&vote=down&disabled=true&is_comment=' . $is_comment)) . '" class="down voting-button' . $post_id_class . $comment_class . '" data-nonce="' . $nonce . '" data-post-id="' . $post_id . '" data-vote="down" data-iscomment="' . $is_comment . '" title="' . esc_html__('Down', 'azpv') . '"><span>↓</span></a>'
            . '</span>';
    return $output;
}

function azpv_shortcode() {
    return azpv_get_votes_button(get_the_ID(), 0);
}

function azpv_post_user_up_votes($user_id, $post_id, $is_comment) {
    $post_users = '';
    $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_up", true) : get_post_meta($post_id, "_user_voted_up", true);
    if (!is_array($post_users)) {
        $post_users = array();
    }
    if (!in_array($user_id, $post_users)) {
        $post_users['user-' . $user_id] = $user_id;
    }
    return $post_users;
}

function azpv_post_user_down_votes($user_id, $post_id, $is_comment) {
    $post_users = '';
    $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_voted_down", true) : get_post_meta($post_id, "_user_voted_down", true);
    if (!is_array($post_users)) {
        $post_users = array();
    }
    if (!in_array($user_id, $post_users)) {
        $post_users['user-' . $user_id] = $user_id;
    }
    return $post_users;
}

function azpv_post_ip_up_votes($user_ip, $post_id, $is_comment) {
    $post_users = '';
    $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_up", true) : get_post_meta($post_id, "_user_IP_up", true);
    // Retrieve post information
    if (!is_array($post_users)) {
        $post_users = array();
    }
    if (!in_array($user_ip, $post_users)) {
        $post_users['ip-' . $user_ip] = $user_ip;
    }
    return $post_users;
}

function azpv_post_ip_down_votes($user_ip, $post_id, $is_comment) {
    $post_users = '';
    $post_users = ( $is_comment == 1 ) ? get_comment_meta($post_id, "_user_comment_IP_down", true) : get_post_meta($post_id, "_user_IP_down", true);
    // Retrieve post information
    if (!is_array($post_users)) {
        $post_users = array();
    }
    if (!in_array($user_ip, $post_users)) {
        $post_users['ip-' . $user_ip] = $user_ip;
    }
    return $post_users;
}

function azpv_get_ip() {
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = ( isset($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    $ip = filter_var($ip, FILTER_VALIDATE_IP);
    $ip = ( $ip === false ) ? '0.0.0.0' : $ip;
    return $ip;
}

function azpv_format_count($number) {
    $precision = 2;
    if ($number >= 1000 && $number < 1000000) {
        $formatted = number_format($number / 1000, $precision) . 'K';
    } else if ($number >= 1000000 && $number < 1000000000) {
        $formatted = number_format($number / 1000000, $precision) . 'M';
    } else if ($number >= 1000000000) {
        $formatted = number_format($number / 1000000000, $precision) . 'B';
    } else {
        $formatted = $number; // Number is less than 1000
    }
    $formatted = str_replace('.00', '', $formatted);
    return $formatted;
}

function azpv_up_voted_post_clauses($args, $query) {
    global $wpdb;

    if (is_user_logged_in()) {
        $voted_posts = get_user_meta(get_current_user_id(), "_up_voted_posts", true);
        if (empty($voted_posts)) {
            $voted_posts = array();
        }
        $args['where'] .= " AND ( $wpdb->posts.ID IN (" . implode(',', $voted_posts) . ")) ";
    }

    return $args;
}

function azpv_down_voted_post_clauses($args, $query) {
    global $wpdb;

    if (is_user_logged_in()) {
        $voted_posts = get_user_meta(get_current_user_id(), "_down_voted_posts", true);
        if (empty($voted_posts)) {
            $voted_posts = array();
        }
        $args['where'] .= " AND ( $wpdb->posts.ID IN (" . implode(',', $voted_posts) . ")) ";
    }

    return $args;
}

add_filter('azexo_fields', 'azexo_azpv_fields');

function azexo_azpv_fields($azexo_fields) {
    return array_merge($azexo_fields, array(
        'post_voting' => esc_html__('Post voting', 'azpv'),
        'post_up_voting' => esc_html__('Post up-voting percent', 'azpv'),
        'post_voting_count' => esc_html__('Post voting count', 'azpv'),
    ));
}

add_filter('azexo_fields_post_types', 'azexo_azpv_fields_post_types');

function azexo_azpv_fields_post_types($azexo_fields_post_types) {
    $azexo_fields_post_types['post_voting'] = '';
    $azexo_fields_post_types['post_up_voting'] = '';
    $azexo_fields_post_types['post_voting_count'] = '';
    return $azexo_fields_post_types;
}

add_filter('azexo_entry_field', 'azexo_azpv_entry_field', 10, 2);

function azexo_azpv_entry_field($output, $name) {
    switch ($name) {
        case 'post_voting':
            return azpv_get_votes_button(get_the_ID());
            break;
        case 'post_up_voting':
            $up_count = azpv_get_up_count(get_the_ID(), 0);
            $count = azpv_get_count(get_the_ID(), 0);
            return '<span class="up-voting up-voting-' . get_the_ID() . '">' . round((($count > 0 ? $up_count / $count : 0)) * 100) . '%</span>';
            break;
        case 'post_voting_count':
            return '<span class="voting-count voting-count-' . get_the_ID() . '">' . azpv_get_count(get_the_ID(), 0) . '</span>';
            break;
    }
    return $output;
}
