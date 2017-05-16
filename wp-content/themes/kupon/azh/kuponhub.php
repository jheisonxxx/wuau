<?php

add_filter('azh_replaces', 'kuponhub_azh_replaces');

function kuponhub_azh_replaces($replaces) {
    $post = azexo_get_closest_current_post('azh_widget', false);
    if ($post) {
        $replaces['post_id'] = $post->ID;
        $replaces['post_title'] = $post->post_title;
        $replaces['post_excerpt'] = $post->post_excerpt;
        $replaces['post_content'] = $post->post_content;
        $replaces['thumbnail'] = get_the_post_thumbnail_url($post, 'full');
        $replaces['permalink'] = get_permalink($post);
    } else {
        $replaces['post_title'] = get_bloginfo('name');
        $replaces['post_excerpt'] = get_bloginfo('description');
        $replaces['post_content'] = '';
        $replaces['thumbnail'] = '';
        $replaces['permalink'] = '';
    }
    return $replaces;
}

add_filter('azexo_plugins', 'kuponhub_azexo_plugins');

function kuponhub_azexo_plugins($plugins) {
    $plugins = array_filter($plugins, function($plugin) {
        return $plugin['slug'] != 'az_deals' && $plugin['slug'] != 'vc_widgets' && $plugin['slug'] != 'js_composer';
    });
    return $plugins;
}
