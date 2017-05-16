<?php
if (function_exists('azl_google_maps_js')) {
    azl_google_maps_js();
} else {
    wp_enqueue_script('google-maps', (is_ssl() ? 'https' : 'http') . '://maps.google.com/maps/api/js?sensor=false&libraries=places', false, false, true);
}

wp_enqueue_script('infobox', AZD_URL . '/js/infobox.js', false, false, true);
wp_enqueue_script('markerclusterer', AZD_URL . '/js/markerclusterer.js', false, false, true);
wp_enqueue_script('mustache', AZD_URL . '/js/mustache.js', false, false, true);
wp_enqueue_script('richmarker', AZD_URL . '/js/richmarker.js', false, false, true);

wp_enqueue_style('azd-frontend', AZD_URL . '/css/frontend.css');
wp_enqueue_script('azd-frontend', AZD_URL . '/js/frontend.js', false, false, true);


if (!empty($deals)):
    ?>
    <script type="text/javascript">
        window.azd = {};
        azd.directory = "<?php print AZD_URL ?>";
    </script>
    <div id="deals-map" data-deals='<?php print json_encode($deals) ?>'></div>
<?php endif; ?>