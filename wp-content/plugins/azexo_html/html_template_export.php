<?php

function azh_html_beautify($html) {
    $html = preg_replace_callback('/id=[\'\"][\w-_]+-\d+[\'\"]|page-item-\d+|menu-item-\d+|post-\d+/', function($m) {
        return '';
    }, $html);

    if (function_exists('tidy_parse_string')) {
        $empty_tags = array('div', 'span', 'a', 'i');
        foreach ($empty_tags as $tag) {
            $html = preg_replace_callback('/<' . $tag . '[^>]*><\/' . $tag . '>/', function($m) {
                return str_replace('><', '>{{}}<', $m[0]);
            }, $html);
        }
        $html = preg_replace_callback('~<a(.*)</a>~isU', function($m) {
            $blocks = 'div|ul|li|dl|form|fieldset|mena|nav|table|tr|td|th|address|article|aside|blockquote|dir|div|dl|fieldset|footer|form|h1|h2|h3|h4|h5|h6|header|hr|menu|nav|ol|p|pre|section|table|ul';
            if (preg_match('~<(' . $blocks . ')~is', $m[1])) {
                // THIS LINK CONTAINS BLOCK ELEMENT
                return '<alink' . $m[1] . '</alink>';
            }
            return $m[0];
        }, $html);
        $tidy = tidy_parse_string($html, array(
            'indent' => true,
            'indent-spaces' => 4,
            'wrap' => 0,
            'output-xhtml' => true,
            'doctype' => '<!DOCTYPE HTML>',
            'new-blocklevel-tags' => 'alink article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
            'new-empty-tags' => 'command embed keygen source track wbr',
            'new-inline-tags' => 'audio command datalist embed keygen mark menuitem meter output progress source time video wbr',
            'merge-divs' => false,
            'merge-spans' => false,
            'drop-empty-paras' => false,
            'vertical-space' => false,
            'wrap-attributes' => false,
            'break-before-br' => false,
            'char-encoding' => 'utf8',
            'input-encoding' => 'utf8',
            'output-encoding' => 'utf8'
                ), 'utf8');
        $html = tidy_get_output($tidy);
        $html = str_replace('<alink ', '<a ', $html);
        $html = str_replace('</alink>', '</a>', $html);
        foreach ($empty_tags as $tag) {
            $html = preg_replace_callback('/<' . $tag . '[^>]*>(\s*{{}}\s*)<\/' . $tag . '>/', function($m) {
                return str_replace($m[1], '', $m[0]);
            }, $html);
        }
        $html = str_replace('style=""', '', $html);
    }
    return $html;
}

function azh_export_section() {
    
}

add_action('admin_init', 'azh_export_options');

function azh_export_options() {
    add_settings_section(
            'azh_export_section', // Section ID
            esc_html__('Export', 'azh'), // Title above settings section
            'azh_export_section', // Name of function that renders a description of the settings section
            'azh-settings'                     // Page to show on
    );
    $custom_menus = array('' => esc_html__('None', 'azh'));
    $menus = get_terms('nav_menu', array('hide_empty' => false));
    if (is_array($menus) && !empty($menus)) {
        foreach ($menus as $single_menu) {
            if (is_object($single_menu) && isset($single_menu->name, $single_menu->term_id)) {
                $custom_menus[$single_menu->term_id] = $single_menu->name;
            }
        }
    }
    add_settings_field(
            'menu_export', // Field ID
            esc_html__('Menu for export pages', 'azh'), // Label to the left
            'azh_select', // Name of function that renders options on the page
            'azh-settings', // Page to show on
            'azh_export_section', // Associate with which settings section?
            array(
        'id' => 'menu_export',
        'options' => $custom_menus,
        'default' => "",
            )
    );
    azh_export();
}

function azh_processStylesheets($styles) {
    $WPLessPlugin = WPLessPlugin::getInstance();
    WPLessStylesheet::$upload_dir = $WPLessPlugin->getConfiguration()->getUploadDir();
    WPLessStylesheet::$upload_uri = $WPLessPlugin->getConfiguration()->getUploadUrl();

    foreach ($styles as $style_id) {
        $WPLessPlugin->processStylesheet($style_id, false);
    }

    do_action('wp-less_plugin_process_stylesheets', $styles);
}

function azh_put_contents($folder, $filename, $content) {
    global $wp_filesystem;
    $i = '';
    $path_parts = pathinfo($filename);
    while (file_exists(AZH_DIR . 'export/' . $folder . '/' . $path_parts['filename'] . $i . '.' . $path_parts['extension'])) {
        if (is_integer($i)) {
            $i++;
        } else {
            $i = 1;
        }
    }
    $wp_filesystem->put_contents(AZH_DIR . 'export/' . $folder . '/' . $path_parts['filename'] . $i . '.' . $path_parts['extension'], $content, FS_CHMOD_FILE);
    return $folder . '/' . $path_parts['filename'] . $i . '.' . $path_parts['extension'];
}

function azh_html_export_fixes($html, &$inline_css, &$inline_css_classes, &$inline_js, &$css, &$js, &$images, &$menu_items_by_urls) {
    $settings = get_option('azh-settings', array());
    foreach ($html->find('style') as $style) {
        $s = preg_replace('/\s+/', ' ', $style->innertext);
        $s = str_replace("{", "{\n", $s);
        $s = str_replace("}", "\n}\n", $s);
        $inline_css .= $s;
        $style->outertext = '';
    }
    foreach ($html->find('link[href]') as $link) {
        if (strpos($link->href, 'http') !== false && strpos($link->href, 'fonts.googleapis.com') === false) {
            $file_url = explode('?', $link->href);
            $file = explode('/', $file_url[0]);
            if (!isset($css[$link->href])) {
                $css[$link->href] = '';
                $response = wp_remote_get($link->href, array('timeout' => 30));
                if (!is_wp_error($response)) {
                    $css[$link->href] = azh_put_contents('css', $file[count($file) - 1], $response['body']);
                }
            }
            $link->href = $css[$link->href];
        }
    }
    foreach ($html->find('script') as $script) {
        if (isset($script->src)) {
            if (strpos($script->src, 'http') !== false) {
                $file_url = explode('?', $script->src);
                $file = explode('/', $file_url[0]);
                if (!isset($js[$script->src])) {
                    $js[$script->src] = '';
                    $response = wp_remote_get($script->src, array('timeout' => 30));
                    if (!is_wp_error($response)) {
                        $js[$script->src] = azh_put_contents('js', $file[count($file) - 1], $response['body']);
                    }
                }
                $script->src = $js[$script->src];
            }
        } else {
            $inline_js .= $script->innertext;
            $script->outertext = '';
        }
    }
    foreach ($html->find('img[src]') as $img) {
        if (!isset($img->alt)) {
            $img->alt = '';
        }
        $file_url = explode('?', $img->src);
        $file = explode('/', $file_url[0]);
        if (!isset($images[$img->src])) {
            $images[$img->src] = '';
            $response = wp_remote_get($img->src, array('timeout' => 30));
            if (!is_wp_error($response)) {
                $images[$img->src] = azh_put_contents('images', $file[count($file) - 1], $response['body']);
            }
        }
        $img->src = $images[$img->src];
    }
    foreach ($html->find('[style]') as $tag) {
        $tag->style = preg_replace_callback('/background-image\:[^;]*url\([\'\"]?([^\'\"\)]+)[\'\"]?\)/i', function($m) {
            if (!isset($images[$m[1]])) {
                $file_url = explode('?', $m[1]);
                $file = explode('/', $file_url[0]);
                $images[$m[1]] = '';
                $response = wp_remote_get($m[1], array('timeout' => 30));
                if (!is_wp_error($response)) {
                    $images[$m[1]] = '../' . azh_put_contents('images', $file[count($file) - 1], $response['body']);
                }
            }
            return "background-image: url('" . $images[$m[1]] . "')";
        }, (string) $tag->style);
        $style = (string) $tag->style;
        if (!isset($tag->class)) {
            $tag->class = '';
        }
        do {
            $class = 'c' . substr(md5(rand()), 0, 7);
        } while (isset($inline_css_classes[$class]));

        $classes = explode(' ', $tag->class);
        $classes[] = $class;
        $classes = array_filter($classes);
        $tag->class = implode(' ', $classes);

        $selector = $tag->tag . '.' . implode('.', explode(' ', $tag->class));

        if (isset($settings['prefix']) && !empty($settings['prefix'])) {
            $resetter = azh_filter_elements_by_class(azh_get_parents($tag), $settings['prefix']);
            if (!empty($resetter)) {
                $resetter = reset($resetter);
                $selector = $resetter->tag . '.' . implode('.', array_filter(explode(' ', $resetter->class))) . ' ' . $tag->tag . '.' . implode('.', explode(' ', $tag->class));
            }
        }

        $inline_css_classes[$class] = "\n" . $selector . " {" . $style . "}\n";
        $inline_css .= $inline_css_classes[$class];
        $tag->style = '';
    }
    foreach ($html->find('a[href]') as $a) {
        if (isset($menu_items_by_urls[$a->href]) && $a->href != '#') {
            $a->href = sanitize_title($menu_items_by_urls[$a->href]->title) . '.html';
        } else {
            if ((strpos($a->href, 'http') !== false) && !in_array('az-iframe-popup', explode(' ', isset($a->class) ? $a->class : ''))) {
                $a->href = '/';
            }
        }
    }
    foreach ($html->find('form[action]') as $form) {
        $form->action = '/';
    }
}

function azh_page_export($menu_item, $page, &$inline_css, &$inline_css_classes, &$inline_js, &$css, &$js, &$images, &$menu_items_by_urls) {
    global $wp_filesystem, $post, $wp_query, $wp_scripts, $wp_styles, $azexo_current_post_stack, $azh_current_post_stack;
    $wp_styles->done = array();
    $wp_scripts->done = array();
    $azexo_current_post_stack = array();
    $azh_current_post_stack = array();

    $original = $post;
    $post = $page;
    setup_postdata($page);
    $wp_query->is_home = 0;
    $wp_query->is_single = 0;
    $wp_query->is_singular = 1;
    $wp_query->is_page = 1;
    $wp_query->is_paged = 0;
    $wp_query->is_posts_page = 0;
    $wp_query->is_post_type_archive = 0;
    $wp_query->is_archive = 0;
    $wp_query->queried_object = $page;
    $wp_query->queried_object_id = $page->ID;



    $head = "<link rel='stylesheet' href='css/inline.css' type='text/css' media='all'/>";
    $body = '';
    $template = get_post_meta($page->ID, '_wp_page_template', true);
    switch ($template) {
        case 'page-templates/with-container.php':
            if (defined('AZEXO_FRAMEWORK')) {
                include_once(AZH_DIR . 'azexo_templates.php');

                ob_start();
                wp_print_styles(array('animate-css', 'font-awesome', 'themify-icons', 'azexo-skin', 'azexo-fonts', 'azexo-style'));
                $head .= ob_get_clean();

                ob_start();
                azh_azexo_with_container($page);
                $body .= ob_get_clean();
            }
            break;
        case 'page-templates/without-container.php':
            if (defined('AZEXO_FRAMEWORK')) {
                include_once(AZH_DIR . 'azexo_templates.php');

                ob_start();
                wp_print_styles(array('animate-css', 'font-awesome', 'themify-icons', 'azexo-skin', 'azexo-fonts', 'azexo-style'));
                $head .= ob_get_clean();

                ob_start();
                azh_azexo_without_container($page);
                $body .= ob_get_clean();
            }
            break;
        case 'azexo-html-template.php':
            $header = '';
            if (is_active_sidebar('azh_header')) {
                ob_start();
                dynamic_sidebar('azh_header');
                $header = '<div class="az-container">' . ob_get_clean() . '</div>';
            }
            $footer = '';
            if (is_active_sidebar('azh_footer')) {
                ob_start();
                dynamic_sidebar('azh_footer');
                $footer = '<div class="az-container">' . ob_get_clean() . '</div>';
            }
            $body .= $header . '<div class="az-container">' . $page->post_content . '</div>' . $footer;
            break;
        case 'default':
            if (defined('AZEXO_FRAMEWORK')) {
                include_once(AZH_DIR . 'azexo_templates.php');

                ob_start();
                wp_print_styles(array('animate-css', 'font-awesome', 'themify-icons', 'azexo-skin', 'azexo-fonts', 'azexo-style'));
                $head .= ob_get_clean();

                ob_start();
                azh_azexo_without_container($page);
                $body .= ob_get_clean();
                break;
            }
        default:
            break;
    }
    ob_start();
    wp_print_styles(array('azh_frontend'));
    $head .= ob_get_clean();
    ob_start();
    wp_print_scripts(array('azh_frontend'));
    $body .= ob_get_clean();
    if (function_exists('azh_extension_scripts')) {
        ob_start();
        wp_print_styles(array('azh-extension-fonts', 'azexo-extension-skin', 'magnific-popup'));
        $head .= ob_get_clean();

        ob_start();
        wp_print_scripts(array('flexslider', 'azh-owl.carousel', 'imagesloaded', 'isotope', 'knob', 'fitvids', 'countdown', 'scrollReveal', 'magnific-popup', 'waypoints', 'azh-extension-frontend'));
        $body .= ob_get_clean();
    }
    $body .= "<script type='text/javascript' src='js/inline.js'></script>";

    $post_scripts = azh_get_content_scripts(azh_get_widgets_content() . $page->post_content);
    ob_start();
    if (is_array($post_scripts['css'])) {
        wp_print_styles($post_scripts['css']);
    }
    $head .= ob_get_clean();
    ob_start();
    if (is_array($post_scripts['js'])) {
        wp_print_scripts($post_scripts['js']);
    }
    $body .= ob_get_clean();


    $html = str_get_html('<!DOCTYPE html><html lang="en-US"><head><meta charset="UTF-8"><title>' . $menu_item->title . '</title>' . $head . '</head><body class="' . ($template == 'default' ? 'page-template-default' : '') . '">' . $body . '</body></html>');
    if ($html) {
        azh_html_export_fixes($html, $inline_css, $inline_css_classes, $inline_js, $css, $js, $images, $menu_items_by_urls);
        $wp_filesystem->put_contents(AZH_DIR . 'export/' . sanitize_title($menu_item->title) . '.html', azh_html_beautify($html->save()), FS_CHMOD_FILE);
    }


    $wp_query->post = $original;
    wp_reset_postdata();
}

function azh_azexo_blog_list_export($menu_item, $page, &$inline_css, &$inline_css_classes, &$inline_js, &$css, &$js, &$images, &$menu_items_by_urls) {
    global $wp_filesystem, $post, $wp_query, $wp_scripts, $wp_styles;
    $wp_styles->done = array();
    $wp_scripts->done = array();

    query_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 5,
    ));
    $post = $page;
    setup_postdata($page);
    $wp_query->is_home = 1;
    $wp_query->is_page = 0;
    $wp_query->is_single = 0;
    $wp_query->is_singular = 0;
    $wp_query->is_paged = 0;
    $wp_query->is_posts_page = 1;
    $wp_query->is_post_type_archive = 0;
    $wp_query->is_archive = 0;
    $wp_query->queried_object = $page;
    $wp_query->queried_object_id = $page->ID;

    $head = "<link rel='stylesheet' href='css/inline.css' type='text/css' media='all'/>";
    $body = '';

    include_once(AZH_DIR . 'azexo_templates.php');
    ob_start();
    wp_print_styles(array('animate-css', 'font-awesome', 'themify-icons', 'azexo-skin', 'azexo-fonts', 'azexo-style'));
    $head .= ob_get_clean();

    ob_start();
    azh_azexo_list($wp_query);
    $body .= ob_get_clean();

    ob_start();
    wp_print_styles(array('azh_frontend'));
    $head .= ob_get_clean();
    ob_start();
    wp_print_scripts(array('azh_frontend'));
    $body .= ob_get_clean();
    if (function_exists('azh_extension_scripts')) {
        ob_start();
        wp_print_styles(array('azh-extension-fonts', 'azexo-extension-skin', 'magnific-popup'));
        $head .= ob_get_clean();

        ob_start();
        wp_print_scripts(array('flexslider', 'azh-owl.carousel', 'imagesloaded', 'isotope', 'knob', 'fitvids', 'countdown', 'scrollReveal', 'magnific-popup', 'waypoints', 'azh-extension-frontend'));
        $body .= ob_get_clean();
    }
    $body .= "<script type='text/javascript' src='js/inline.js'></script>";

    $html = str_get_html('<!DOCTYPE html><html lang="en-US"><head><meta charset="UTF-8"><title>' . $menu_item->title . '</title>' . $head . '</head><body class="blog">' . $body . '</body></html>');
    if ($html) {
        azh_html_export_fixes($html, $inline_css, $inline_css_classes, $inline_js, $css, $js, $images, $menu_items_by_urls);
        $wp_filesystem->put_contents(AZH_DIR . 'export/' . sanitize_title($menu_item->title) . '.html', azh_html_beautify($html->save()), FS_CHMOD_FILE);
    }
}

function azh_post_export($menu_item, $export_post, &$inline_css, &$inline_css_classes, &$inline_js, &$css, &$js, &$images, &$menu_items_by_urls) {
    global $wp_filesystem, $post, $wp_query, $wp_scripts, $wp_styles, $azexo_current_post_stack;
    $azexo_current_post_stack = array();
    $wp_styles->done = array();
    $wp_scripts->done = array();

    $original = $post;
    $post = $export_post;
    setup_postdata($export_post);
    $wp_query->is_home = 0;
    $wp_query->is_single = 1;
    $wp_query->is_singular = 1;
    $wp_query->is_page = 0;
    $wp_query->is_paged = 0;
    $wp_query->is_posts_page = 0;
    $wp_query->is_post_type_archive = 0;
    $wp_query->is_archive = 0;
    $wp_query->queried_object = $export_post;
    $wp_query->queried_object_id = $export_post->ID;



    $head = "<link rel='stylesheet' href='css/inline.css' type='text/css' media='all'/>";
    $body = '';


    include_once(AZH_DIR . 'azexo_templates.php');

    ob_start();
    wp_print_styles(array('animate-css', 'font-awesome', 'themify-icons', 'azexo-skin', 'azexo-fonts', 'azexo-style'));
    $head .= ob_get_clean();
    ob_start();
    azh_azexo_single_post();
    $body .= ob_get_clean();

    ob_start();
    wp_print_styles(array('azh_frontend'));
    $head .= ob_get_clean();
    ob_start();
    wp_print_scripts(array('azh_frontend'));
    $body .= ob_get_clean();
    if (function_exists('azh_extension_scripts')) {
        ob_start();
        wp_print_styles(array('azh-extension-fonts', 'azexo-extension-skin', 'magnific-popup'));
        $head .= ob_get_clean();

        ob_start();
        wp_print_scripts(array('flexslider', 'azh-owl.carousel', 'imagesloaded', 'isotope', 'knob', 'fitvids', 'countdown', 'scrollReveal', 'magnific-popup', 'waypoints', 'azh-extension-frontend'));
        $body .= ob_get_clean();
    }
    $body .= "<script type='text/javascript' src='js/inline.js'></script>";

    $html = str_get_html('<!DOCTYPE html><html lang="en-US"><head><meta charset="UTF-8"><title>' . $menu_item->title . '</title>' . $head . '</head><body class="post-template-default single single-post">' . $body . '</body></html>');
    if ($html) {
        azh_html_export_fixes($html, $inline_css, $inline_css_classes, $inline_js, $css, $js, $images, $menu_items_by_urls);
        $wp_filesystem->put_contents(AZH_DIR . 'export/' . sanitize_title($menu_item->title) . '.html', azh_html_beautify($html->save()), FS_CHMOD_FILE);
    }


    $wp_query->post = $original;
    wp_reset_postdata();
}

function azh_export_clear() {
    foreach (array(AZH_DIR . 'export', AZH_DIR . 'export/css', AZH_DIR . 'export/js', AZH_DIR . 'export/images') as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } else {
            mkdir($dir);
        }
    }
    if (!is_dir(AZH_DIR . 'export/less')) {
        mkdir(AZH_DIR . 'export/less');
    }
    if (!is_dir(AZH_DIR . 'export/fonts')) {
        mkdir(AZH_DIR . 'export/fonts');
    }
}

function azh_export() {
    include_once(AZH_DIR . 'simple_html_dom.php');
    $settings = get_option('azh-settings', array());
    azh_filesystem();
    global $wp_filesystem;

    if (isset($settings['menu_export']) && is_numeric($settings['menu_export'])) {

        azh_export_clear();

        $menu = wp_get_nav_menu_object($settings['menu_export']);
        $menu_items = wp_get_nav_menu_items($menu->term_id, array('update_post_term_cache' => false));

        $sorted_menu_items = array();
        foreach ((array) $menu_items as $menu_item) {
            $sorted_menu_items[$menu_item->menu_order] = $menu_item;
        }
        $sorted_menu_items = apply_filters('wp_nav_menu_objects', $sorted_menu_items, array());
        $menu_items_children = array();
        $menu_items_by_urls = array();
        foreach ((array) $sorted_menu_items as $menu_item) {
            if (!isset($menu_items_children[$menu_item->menu_item_parent])) {
                $menu_items_children[$menu_item->menu_item_parent] = array();
            }
            $menu_items_children[$menu_item->menu_item_parent][] = $menu_item;
            $menu_items_by_urls[$menu_item->url] = $menu_item;
        }


        wp_set_current_user(0);
        if (class_exists('Jetpack_Widget_Conditions')) {
            add_filter('widget_display_callback', array(Jetpack_Widget_Conditions, 'filter_widget'));
            add_filter('sidebars_widgets', array(Jetpack_Widget_Conditions, 'sidebars_widgets'));
            add_action('template_redirect', array(Jetpack_Widget_Conditions, 'template_redirect'));
        }
        azh_scripts();
        if (defined('AZEXO_FRAMEWORK')) {
            if (function_exists('azexo_styles')) {
                azexo_styles();
            }
            if (function_exists('azexo_scripts')) {
                azexo_scripts();
            }
            azh_processStylesheets(array('azexo-skin'));
        }
        if (function_exists('azh_extension_scripts')) {
            azh_extension_scripts();
        }
        azh_processStylesheets(array('azexo-extension-skin'));
        $inline_css = '';
        $inline_css_classes = array();
        $inline_js = '';
        $css = array();
        $js = array();
        $images = array();
        foreach ((array) $sorted_menu_items as $menu_item) {
            if ($menu_item->object == 'page' && is_numeric($menu_item->object_id)) {
                $page = get_post($menu_item->object_id);
                if ($page->post_type == 'page') {
                    if ($page->ID != get_option('page_for_posts')) {
                        azh_page_export($menu_item, $page, $inline_css, $inline_css_classes, $inline_js, $css, $js, $images, $menu_items_by_urls);
                    } else {
                        if (defined('AZEXO_FRAMEWORK')) {
                            azh_azexo_blog_list_export($menu_item, $page, $inline_css, $inline_css_classes, $inline_js, $css, $js, $images, $menu_items_by_urls);
                        }
                    }
                }
            }
            if (defined('AZEXO_FRAMEWORK')) {
                if ($menu_item->object == 'post' && is_numeric($menu_item->object_id)) {
                    $post = get_post($menu_item->object_id);
                    azh_post_export($menu_item, $post, $inline_css, $inline_css_classes, $inline_js, $css, $js, $images, $menu_items_by_urls);
                }
            }
        }
        $wp_filesystem->put_contents(AZH_DIR . 'export/css/inline.css', $inline_css, FS_CHMOD_FILE);
        $wp_filesystem->put_contents(AZH_DIR . 'export/js/inline.js', $inline_js, FS_CHMOD_FILE);

        $settings['menu_export'] = false;
        update_option('azh-settings', $settings);
    }
}
