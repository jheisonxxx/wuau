<?php

if (class_exists('WPLessPlugin')) {
    $less = WPLessPlugin::getInstance();
    $options = get_option(AZEXO_FRAMEWORK);
    if (isset($options['brand-color'])) {
        $less->addVariable('brand-color', $options['brand-color']);
    }
    if (isset($options['accent-1-color'])) {
        $less->addVariable('accent-1-color', $options['accent-1-color']);
    }
    if (isset($options['accent-2-color'])) {
        $less->addVariable('accent-2-color', $options['accent-2-color']);
    }    
    if (isset($options['google_font_families']) && is_array($options['google_font_families'])) {
        $font_families = array();
        $i = 0;
        foreach ($options['google_font_families'] as $font_family) {
            $font = explode(':', $font_family);
            $i++;
            $less->addVariable('google-font-family-' . $i, str_replace('+', ' ', $font[0]));
        }
    }
}