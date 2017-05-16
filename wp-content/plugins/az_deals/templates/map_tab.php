<?php
wp_enqueue_style('azd-frontend', AZD_URL . '/css/frontend.css');
wp_enqueue_script('azd-frontend', AZD_URL . '/js/frontend.js', false, false, true);

if (function_exists('azl_google_maps_js')) {
    azl_google_maps_js();
} else {
    wp_enqueue_script('google-maps', (is_ssl() ? 'https' : 'http') . '://maps.google.com/maps/api/js?sensor=false&libraries=places', false, false, true);
}

if (!empty($deal_markers)):
    $deal_markers = preg_split("/[^,][\s]+/", trim($deal_markers));
    $markers = array();
    foreach ($deal_markers as $deal_marker) {
        $marker = explode(",", trim($deal_marker));
        $markers[] = array('latitude' => trim($marker[0]), 'longitude' => trim($marker[1]));
    }
    ?>
    <div id="deal-map" data-markers='<?php echo json_encode($markers) ?>'></div>
<?php endif; ?>