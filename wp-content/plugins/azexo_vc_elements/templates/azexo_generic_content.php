<?php

$output = $media_type = $image = $gallery = $thumbnails = $img_size = $video = $icon_library = $icon_fontawesome = $icon_openiconic = $icon_typicons = $icon_entypo = $icon_linecons = $media_link = $media_link_click = $title_link = $title_link_click = $title = $extra = $meta = $footer_link = $footer_link_click = $footer = $trigger = $trigger_on = $trigger_off = $trigger_class = $sr = $el_class = $css = '';
extract(shortcode_atts(array(
    'media_type' => '', 'image' => '', 'img_size' => 'thumbnail', 'gallery' => '', 'thumbnails' => false, 'video' => '', 'icon_library' => 'fontawesome', 'icon_fontawesome' => '', 'icon_openiconic' => '', 'icon_typicons' => '', 'icon_entypo' => '', 'icon_linecons' => '', 'media_link' => '', 'media_link_click' => '', 'title_link' => '', 'title_link_click' => '', 'title' => '', 'extra' => '', 'meta' => '', 'footer_link' => '', 'footer_link_click' => '', 'footer' => '',
    'trigger' => false,
    'trigger_on' => '',
    'trigger_off' => '',
    'trigger_class' => '',
    'sr' => '',
    'el_class' => '',
    'css' => '',
                ), $atts, 'azexo_generic_content'));

$css_class = $el_class;
if (function_exists('vc_shortcode_custom_css_class')) {
    $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $el_class . vc_shortcode_custom_css_class($css, ' '), $this->settings['base'], $atts);
}

$popups = array('image_popup', 'iframe_popup');
$intersect = array_intersect($popups, array($media_link_click, $title_link_click, $footer_link_click));
if (count($intersect) > 0) {
    wp_enqueue_script('magnific-popup');
    wp_enqueue_style('magnific-popup');
}

if (!empty($sr)) {
    wp_enqueue_script('scrollReveal');
    $sr = 'data-sr="' . $sr . '"';
}

if (!function_exists('azexo_generic_content_image')) {

    function azexo_generic_content_image($image, $img_size, $media_link_click, $popups, $media_link) {
        print '<div class="entry-thumbnail">';
        $attachment_id = preg_replace('/[^\d]/', '', $image);
        azexo_add_image_size($img_size);
        $size = azexo_get_image_sizes($img_size);
        $image_url = azexo_get_attachment_thumbnail($attachment_id, $img_size, true);
        if (!empty($image_url)) {
            $link_class = in_array($media_link_click, $popups) ? str_replace('_', '-', $media_link_click) : '';
            $media_link = array_filter(azexo_build_link($media_link));
            if ($img_size == 'full') {
                if (!is_array($media_link) || empty($media_link)) {
                    print '<img class="image" src="' . esc_url($image_url[0]) . '" alt="">';
                } else {
                    print '<a class="' . $link_class . '" ' . azexo_build_link_attributes($media_link) . '><img class="image" src="' . esc_url($image_url[0]) . '" alt=""></a>';
                }
            } else {
                if (!is_array($media_link) || empty($media_link)) {
                    print '<div class="image" style=\'background-image: url("' . esc_url($image_url[0]) . '"); height: ' . esc_attr($size['height']) . 'px;\'></div>';
                } else {
                    print '<a class="' . $link_class . '" ' . azexo_build_link_attributes($media_link) . '><div class="image" style=\'background-image: url("' . esc_url($image_url[0]) . '"); height: ' . esc_attr($size['height']) . 'px;\'></div></a>';
                }
            }
        }
        print "</div>\n";
    }

}

if (!function_exists('azexo_generic_content_icon')) {

    function azexo_generic_content_icon($icon_class, $media_link_click, $popups, $media_link) {
        print '<div class="entry-icon">';
        $media_link = array_filter(azexo_build_link($media_link));
        if (!is_array($media_link) || empty($media_link)) {
            print '<span class="' . $icon_class . '"></span>';
        } else {
            $link_class = in_array($media_link_click, $popups) ? str_replace('_', '-', $media_link_click) : '';
            print '<a class="' . $link_class . '" ' . azexo_build_link_attributes($media_link) . '><span class="' . $icon_class . '"></span></a>';
        }
        print "</div>\n";
    }

}

if ($trigger) {
    print '<div class="entry ' . esc_attr($css_class) . ' trigger" ' . $sr . ' data-trigger-on="' . esc_attr($trigger_on) . '" data-trigger-off="' . esc_attr($trigger_off) . '" data-trigger-class="' . esc_attr($trigger_class) . '">';
} else {
    print '<div class="entry ' . esc_attr($css_class) . '" ' . $sr . '>';
}

switch ($media_type) {
    case 'image':
        azexo_generic_content_image($image, $img_size, $media_link_click, $popups, $media_link);
        break;
    case 'gallery':
        print '<div class="entry-gallery">';
        $images = explode(',', $gallery);
        print azexo_entry_gallery($images, false, $thumbnails, $img_size);
        print "</div>\n";
        break;
    case 'video':
        if ($video) {
            print '<div class="entry-video">';
            global $wp_embed;
            print $wp_embed->run_shortcode('[embed]' . $video . '[/embed]');
            print "</div>\n";
        }
        break;
    case 'icon':
        if (function_exists('vc_icon_element_fonts_enqueue')) {
            vc_icon_element_fonts_enqueue($icon_library);
        } else {
            if (function_exists('azh_icon_font_enqueue')) {
                azh_icon_font_enqueue($icon_library);
            }
        }

        $icon_class = isset(${"icon_" . $icon_library}) ? esc_attr(${"icon_" . $icon_library}) : 'fa fa-adjust';
        azexo_generic_content_icon($icon_class, $media_link_click, $popups, $media_link);
        break;
    case 'image_icon':
        azexo_generic_content_image($image, $img_size, $media_link_click, $popups, $media_link);

        if (function_exists('vc_icon_element_fonts_enqueue')) {
            vc_icon_element_fonts_enqueue($icon_library);
        } else {
            if (function_exists('azh_icon_font_enqueue')) {
                azh_icon_font_enqueue($icon_library);
            }
        }
        $icon_class = isset(${"icon_" . $icon_library}) ? esc_attr(${"icon_" . $icon_library}) : 'fa fa-adjust';
        azexo_generic_content_icon($icon_class, $media_link_click, $popups, $media_link);
        break;
}

print '<div class="entry-data">';
print '<div class="entry-header">';

if (!empty($extra)) {
    print '<div class="entry-extra">';
    print rawurldecode(base64_decode(strip_tags($extra)));
    print "</div>\n";
}

if (!empty($title)) {
    print '<div class="entry-title">';
    $title_link = array_filter(azexo_build_link($title_link));
    if (!is_array($title_link) || empty($title_link)) {
        print $title;
    } else {
        $link_class = in_array($title_link_click, $popups) ? str_replace('_', '-', $title_link_click) : '';
        print '<a class="' . $link_class . '" ' . azexo_build_link_attributes($title_link) . '>' . $title . '</a>';
    }
    print "</div>\n";
}

if (!empty($meta)) {
    print '<div class="entry-meta">';
    print rawurldecode(base64_decode(strip_tags($meta)));
    print "</div>\n";
}

print "</div><!-- header -->\n";

if (!empty($content) && ($content != "<br />\n")) {
    print '<div class="entry-content">';
    print do_shortcode(shortcode_unautop(wpautop(preg_replace('/<\/?p\>/', "\n", $content) . "\n")));
    print "</div>\n";
}

if (!empty($footer)) {
    print '<div class="entry-footer">';
    $footer_link = array_filter(azexo_build_link($footer_link));
    if (!is_array($footer_link) || empty($footer_link)) {
        print rawurldecode(base64_decode(strip_tags($footer)));
    } else {
        $link_class = in_array($footer_link_click, $popups) ? str_replace('_', '-', $footer_link_click) : '';
        print '<a class="' . $link_class . '" ' . azexo_build_link_attributes($footer_link) . '>' . rawurldecode(base64_decode(strip_tags($footer))) . '</a>';
    }
    print "</div>\n";
}

print "</div><!-- data -->\n";

print "</div><!-- entry -->\n";


