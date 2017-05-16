<?php
/*
  Plugin Name: AZEXO HTML customizer
  Plugin URI: http://azexo.com
  Description: AZEXO HTML customizer
  Author: azexo
  Author URI: http://azexo.com
  Version: 1.24
  Text Domain: azh
 */

define('AZH_VERSION', '1.24');
define('AZH_URL', plugins_url('', __FILE__));
define('AZH_DIR', trailingslashit(dirname(__FILE__)) . '/');

function azh_linux_path($path) {
    return str_replace('\\', '/', $path);
}

global $azh_shortcodes;
$azh_shortcodes = array();

include_once(AZH_DIR . 'icons.php' );
include_once(AZH_DIR . 'auto_templates.php' );
if (is_admin()) {
    include_once(AZH_DIR . 'envato/updater.php' );
    include_once(AZH_DIR . 'settings.php' );
    include_once(AZH_DIR . 'customizer.php' );
    include_once(AZH_DIR . 'html_template_export.php' );
}

add_action('plugins_loaded', 'azh_plugins_loaded');

function azh_plugins_loaded() {
    load_plugin_textdomain('azh', FALSE, basename(dirname(__FILE__)) . '/languages/');
    add_action('add_meta_boxes', 'azh_add_meta_boxes', 10, 2);
    $settings = get_option('azh-settings');
    $icon_types = array('fontawesome', 'openiconic', 'typicons', 'entypo', 'linecons', 'monosocial');
    global $azh_icons, $azh_icons_index;
    $azh_icons = array();
    $azh_icons_index = array();
    foreach ($icon_types as $icon_type) {
        $azh_icons[$icon_type] = array();
        $arr1 = apply_filters('azh_icon-type-' . $icon_type, array());
        foreach ($arr1 as $arr2) {
            if (is_array($arr2)) {
                if (count($arr2) == 1) {
                    reset($arr2);
                    $azh_icons[$icon_type][key($arr2)] = current($arr2);
                    $azh_icons_index[key($arr2)] = $icon_type;
                } else {
                    foreach ($arr2 as $arr3) {
                        if (count($arr3) == 1) {
                            reset($arr3);
                            $azh_icons[$icon_type][key($arr3)] = current($arr3);
                            $azh_icons_index[key($arr3)] = $icon_type;
                        }
                    }
                }
            }
        }
    }
    if (isset($settings['custom-icons-classes'])) {
        $custom_icons = explode("\n", trim($settings['custom-icons-classes']));
        if (count($custom_icons) <= 1) {
            $custom_icons = explode(" ", trim($settings['custom-icons-classes']));
        }
        $azh_icons['custom'] = array_combine($custom_icons, $custom_icons);
        $azh_icons_index = array_merge($azh_icons_index, array_combine($custom_icons, array_fill(0, count($custom_icons), 'custom')));
    }


    $azh_widgets = get_option('azh_widgets');
    if (is_array($azh_widgets) || empty($azh_widgets)) {
        update_post_cache($azh_widgets);
    } else {
        $azh_widgets = get_posts(array(
            'post_type' => 'azh_widget',
            'posts_per_page' => '-1',
        ));
        update_option('azh_widgets', $azh_widgets);
        update_post_cache($azh_widgets);
    }
    wp_register_script('imagesloaded', plugins_url('js/imagesloaded.pkgd.js', __FILE__), array('jquery'), false, true);
    wp_register_script('isotope', plugins_url('js/isotope.pkgd.js', __FILE__), array('jquery'), false, true);
    wp_register_script('waypoints', plugins_url('js/jquery.waypoints.js', __FILE__), array('jquery'), false, true);
}

add_action('save_post', 'azh_save_post', 10, 3);

function azh_save_post($post_ID, $post, $update) {
    if ($post->post_type == 'azh_widget') {
        $azh_widgets = get_option('azh_widgets');
        if (!is_array($azh_widgets)) {
            $azh_widgets = array();
        }
        $azh_widgets[$post_ID] = $post;
        update_option('azh_widgets', $azh_widgets);
        update_post_cache($azh_widgets);
    }
}

function azh_get_object() {
    $empty_html = esc_html__('Please switch to HTML and input content', 'azh');
    $dirs = apply_filters('azh_directory', array_combine(array(get_template_directory() . '/azh'), array(get_template_directory_uri() . '/azh')));
    if (is_array($dirs)) {
        foreach ($dirs as $dir => $uri) {
            if (is_dir($dir)) {
                $empty_html = esc_html__('Please add new sections from "AZEXO HTML" metabox in right sidebar', 'azh');
                break;
            }
        }
    }
    global $azh_shortcodes;
    $settings = get_option('azh-settings');
    $patterns = isset($settings['patterns']) ? $settings['patterns'] : '';
    $patterns = preg_replace("/\r\n/", "\n", $patterns);
    $properties = explode("\n\n", $patterns);
    $patterns = array();
    foreach ($properties as $property) {
        $property = explode("\n", $property);
        $patterns[$property[0]] = array();
        if ($property[0] == 'dropdown_patterns') {
            for ($i = 1; $i < count($property); $i = $i + 2) {
                $options = array();
                $options_value_label = explode("|", $property[$i + 1]);
                foreach ($options_value_label as $value_label) {
                    $vl = explode(":", $value_label);
                    $options[$vl[0]] = $vl[1];
                }
                $patterns[$property[0]][] = array(
                    'pattern' => $property[$i],
                    'options' => $options,
                );
            }
        } else {
            for ($i = 1; $i < count($property); $i++) {
                $patterns[$property[0]][] = $property[$i];
            }
        }
    }
    return array(
        'options' => apply_filters('azh_options', $patterns),
        'dirs_options' => azh_get_all_settings(),
        'icons' => apply_filters('azh_icons', array()),
        'shortcodes' => $azh_shortcodes,
        'shortcode_instances' => $settings['shortcodes'],
        'site_url' => site_url(),
        'plugin_url' => AZH_URL,
        'ajaxurl' => admin_url('admin-ajax.php'),
        'helpers' => array(
            '.azh-wrapper.azh-inline' => esc_html__('<strong>Right mouse click without text selection</strong> - switch between text and link<br><strong>Right mouse click with text selection</strong> - split text-field to 3 text-fields<br><strong>Drag-and-drop text-field</strong> - merge 2 adjacent text-fields', 'azh'),
        ),
        'user_logged_in' => is_user_logged_in(),
        'post_id' => get_the_ID(),
        'edit_post_link' => is_admin() ? '' : get_edit_post_link(),
        'edit_post_frontend_link' => add_query_arg('azh', 'customize', get_permalink()),
        'fi18n' => array(
            'ok' => esc_html__('OK', 'azh'),
            'cancel' => esc_html__('Cancel', 'azh'),
            'edit_link' => esc_html__('Edit link', 'azh'),
            'edit_image' => esc_html__('Edit image', 'azh'),
            'edit_icon' => esc_html__('Edit icon', 'azh'),
            'clone' => esc_html__('Clone', 'azh'),
            'remove' => esc_html__('Remove', 'azh'),
            'section' => esc_html__('Section', 'azh'),
            'element' => esc_html__('Element', 'azh'),
            'elements' => esc_html__('Elements', 'azh'),
            'general' => esc_html__('General', 'azh'),
            'add_element' => esc_html__('Add element', 'azh'),
            'remove_element' => esc_html__('Remove element', 'azh'),
            'edit_tags' => esc_html__('Edit tags', 'azh'),
            'saved' => esc_html__('Saved', 'azh'),
            'select_image' => esc_html__('Select image', 'azh'),
            'click_to_edit_shortcode' => esc_html__('Click to edit shortcode', 'azh'),
            'shortcode_edit' => esc_html__('Shortcode edit', 'azh'),
        ),
        'i18n' => array(
            'edit_frontend_builder' => esc_html__('Edit page in frontend', 'azh'),
            'empty_html' => $empty_html,
            'enter_text_here' => esc_html__('enter text here', 'azh'),
            'upload_text' => esc_html__('Upload', 'azh'),
            'edit_text' => esc_html__('Edit', 'azh'),
            'clear' => esc_html__('Clear', 'azh'),
            'collapse' => esc_html__('Collapse', 'azh'),
            'expand' => esc_html__('Expand', 'azh'),
            'clone' => esc_html__('Clone', 'azh'),
            'copy' => esc_html__('Copy', 'azh'),
            'copied' => esc_html__('Copied', 'azh'),
            'paste' => esc_html__('Paste', 'azh'),
            'move' => esc_html__('Move', 'azh'),
            'done' => esc_html__('Done', 'azh'),
            'add' => esc_html__('Add', 'azh'),
            'remove' => esc_html__('Remove', 'azh'),
            'set' => esc_html__('Set', 'azh'),
            'title' => esc_html__('Title', 'azh'),
            'url' => esc_html__('URL', 'azh'),
            'selected' => esc_html__('Selected', 'azh'),
            'required' => esc_html__('Required', 'azh'),
            'checked' => esc_html__('Checked', 'azh'),
            'device' => esc_html__('Device', 'azh'),
            'large' => esc_html__('Large', 'azh'),
            'medium' => esc_html__('Medium', 'azh'),
            'small' => esc_html__('Small', 'azh'),
            'preview' => esc_html__('Preview', 'azh'),
            'customize' => esc_html__('Customize', 'azh'),
            'elements' => esc_html__('elements', 'azh'),
            'sections' => esc_html__('sections', 'azh'),
            'extra_small' => esc_html__('Extra small', 'azh'),
            'column_width' => esc_html__('Column width', 'azh'),
            'column_offset' => esc_html__('Column offset', 'azh'),
            'column_responsive' => esc_html__('Column responsive settings', 'azh'),
            'select_url' => esc_html__('Select URL', 'azh'),
            'switch_to_html' => esc_html__('Switch to html', 'azh'),
            'switch_to_customizer' => esc_html__('Switch to customizer', 'azh'),
            'control_description' => esc_html__('Control description', 'azh'),
            'description' => esc_html__('Description', 'azh'),
            'filter_by_tag' => esc_html__('Filter by tag', 'azh'),
            'paste_sections_list_here' => esc_html__('Paste sections list here', 'azh'),
        ),
    );
}

function azh_editor_scripts() {
    wp_enqueue_style('azh_admin', plugins_url('css/admin.css', __FILE__));
    wp_enqueue_script('azh_admin', plugins_url('js/admin.js', __FILE__), array('azexo_html'), false, true);

    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style('azexo_html', plugins_url('css/azexo_html.css', __FILE__));
    wp_enqueue_script('azexo_html', plugins_url('js/azexo_html.js', __FILE__), array('underscore', 'azh_htmlparser', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-sortable', 'jquery-ui-autocomplete'), false, true);
    wp_enqueue_style('jquery-ui', plugins_url('css/jquery-ui.css', __FILE__), false, false, false);
    wp_enqueue_script('azh_htmlparser', plugins_url('js/htmlparser.js', __FILE__), false, true);
    wp_enqueue_script('azh_ace', plugins_url('js/ace/ace.js', __FILE__), false, true);

    wp_localize_script('azh_admin', 'azh', azh_get_object());
}

function azh_add_meta_boxes($post_type, $post) {
    $dirs = apply_filters('azh_directory', array_combine(array(get_template_directory() . '/azh'), array(get_template_directory_uri() . '/azh')));
    if (is_array($dirs)) {
        foreach ($dirs as $dir => $uri) {
            if (is_dir($dir) && in_array($post_type, array('page', 'azh_widget'))) {
                add_meta_box('azh', __('AZEXO HTML', 'azh'), 'azh_meta_box', $post_type, 'side', 'default');
                break;
            }
        }
    }
    if (in_array($post_type, array('page', 'azh_widget'))) {
        azh_editor_scripts();
        do_action('azh_load', $post_type, $post);
    }
}

function azh_get_library() {
    $general_elements = array();
    $elements = array();
    $elements_dir = array();
    $elements_uri = array();
    $elements_categories = array();
    $general_sections = array();
    $sections = array();
    $sections_dir = array();
    $sections_uri = array();
    $sections_categories = array();
    $dirs = apply_filters('azh_directory', array_combine(array(get_template_directory() . '/azh'), array(get_template_directory_uri() . '/azh')));
    if (is_array($dirs)) {
        foreach ($dirs as $dir => $uri) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && $fileInfo->getExtension() == 'html') {
                        if (isset($_GET['azh']) && $_GET['azh'] == 'customize' && strpos($fileInfo->getPathname(), 'pre-made') !== false) {
                            continue;
                        }
                        $sections[azh_linux_path($fileInfo->getPathname())] = $fileInfo->getFilename();
                        $sections_dir[azh_linux_path($fileInfo->getPathname())] = azh_linux_path($dir);
                        $sections_uri[azh_linux_path($fileInfo->getPathname())] = $uri;
                        $sections_categories[trim(str_replace(azh_linux_path($dir), '', azh_linux_path($fileInfo->getPath())), '/')] = true;
                        if (in_array(strtolower(basename(dirname($fileInfo->getPathname()))), array('empty rows'))) {
                            $general_sections[azh_linux_path($fileInfo->getPathname())] = $fileInfo->getFilename();
                        }
                    }
                    if ($fileInfo->isFile() && $fileInfo->getExtension() == 'htm') {
                        $elements[azh_linux_path($fileInfo->getPathname())] = $fileInfo->getFilename();
                        $elements_dir[azh_linux_path($fileInfo->getPathname())] = azh_linux_path($dir);
                        $elements_uri[azh_linux_path($fileInfo->getPathname())] = $uri;
                        $elements_categories[trim(str_replace(azh_linux_path($dir), '', azh_linux_path($fileInfo->getPath())), '/')] = true;
                        if (in_array(strtolower(basename(dirname($fileInfo->getPathname()))), array('empty rows', 'general'))) {
                            $general_elements[azh_linux_path($fileInfo->getPathname())] = $fileInfo->getFilename();
                        }
                    }
                }
            }
        }
    }
    ksort($elements);
    ksort($sections);
    return array(
        'general_elements' => $general_elements,
        'elements' => $elements,
        'elements_dir' => $elements_dir,
        'elements_uri' => $elements_uri,
        'elements_categories' => $elements_categories,
        'general_sections' => $general_sections,
        'sections' => $sections,
        'sections_dir' => $sections_dir,
        'sections_uri' => $sections_uri,
        'sections_categories' => $sections_categories,
    );
}

function azh_meta_box($post = NULL, $metabox = NULL, $post_type = 'page') {
    if (!is_null($post)) {
        $post_type = get_post_type($post);
    }
    $library = azh_get_library();
    extract($library);
    ?>
    <?php //if ($post_type != 'azh_widget'):                                    ?>
    <div class="azh-actions" style="display: none;">
        <a href="#" class="azh-copy-sections-list"><?php esc_html_e('Copy sections', 'azh') ?></a>
        <a href="#" class="azh-insert-sections-list"><?php esc_html_e('Insert sections', 'azh') ?></a>
    </div>
    <div class="azh-structure" style="max-height: 600px;"></div>
    <a href="#" class="azh-add-section" data-open="<?php esc_html_e('Add section', 'azh') ?>" data-close="<?php esc_html_e('Close library', 'azh') ?>"><?php esc_html_e('Add section', 'azh') ?></a>    
    <?php //endif; ?>
    <div class="azh-library" style="display: none;">
        <?php //if ($post_type != 'azh_widget'): ?>
        <div class="azh-library-filters">
            <select class="azh-categories">
                <option value=""><?php esc_html_e('Filter by category', 'azh') ?></option>
                <?php
                foreach ($sections_categories as $category => $flag) {
                    ?>
                    <option value="<?php print esc_attr($category) ?>"><?php print esc_html($category) ?></option>
                    <?php
                }
                ?>
            </select>
        </div>
        <div class="azh-sections">
            <?php
            foreach ($sections as $path => $name) {
                $preview = '';
                if (file_exists(str_replace('.html', '.jpg', $path))) {
                    $preview = str_replace('.html', '.jpg', $path);
                }
                if (file_exists(str_replace('.html', '.png', $path))) {
                    $preview = str_replace('.html', '.png', $path);
                }
                $dir = $sections_dir[$path];
                $url = str_replace($dir, $sections_uri[$path], $path);
                if (file_exists($preview)) {
                    $preview = str_replace($dir, $sections_uri[$path], $preview);
                    ?><div class="azh-section azh-fuzzy <?php print isset($general_sections[$path]) ? 'general' : ''; ?>" data-url="<?php print esc_attr($url); ?>" data-path="<?php print esc_attr(ltrim(str_replace($dir, '', $path), '/')) ?>" data-dir="<?php print esc_attr($dir) ?>" data-dir-uri="<?php print esc_attr($sections_uri[$path]) ?>"  style="background-image: url('<?php print esc_attr($preview); ?>');"><div><?php print esc_html($name) ?></div></div><?php
                } else {
                    ?><div class="azh-section no-image <?php print isset($general_sections[$path]) ? 'general' : ''; ?>" data-url="<?php print esc_attr($url); ?>" data-path="<?php print esc_attr(ltrim(str_replace($dir, '', $path), '/')) ?>" data-dir="<?php print esc_attr($dir) ?>" data-dir-uri="<?php print esc_attr($sections_uri[$path]) ?>"><div><?php print esc_html($name) ?></div></div><?php
                        }
                    }
                    ?>        
        </div>
        <?php //endif; ?>
        <div class="azh-elements" style="display: none;">  
            <div class="azh-elements-filters">
                <select class="azh-categories">
                    <option value="" selected=""><?php esc_html_e('Filter by category', 'azh') ?></option>
                    <?php
                    foreach ($elements_categories as $category => $flag) {
                        ?>
                        <option value="<?php print esc_attr($category) ?>"><?php print esc_html($category) ?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
            <?php
            foreach ($elements as $path => $name) {
                $preview = '';
                if (file_exists(str_replace('.htm', '.jpg', $path))) {
                    $preview = str_replace('.htm', '.jpg', $path);
                }
                if (file_exists(str_replace('.htm', '.png', $path))) {
                    $preview = str_replace('.htm', '.png', $path);
                }
                $dir = $elements_dir[$path];
                $url = str_replace($dir, $elements_uri[$path], $path);
                if (file_exists($preview)) {
                    $preview = str_replace($dir, $elements_uri[$path], $preview);
                    ?><div class="azh-element <?php print isset($general_elements[$path]) ? 'general' : ''; ?>" data-url="<?php print esc_attr($url); ?>" data-path="<?php print esc_attr(ltrim(str_replace($dir, '', $path), '/')) ?>" data-dir="<?php print esc_attr($dir) ?>" data-dir-uri="<?php print esc_attr($elements_uri[$path]) ?>" style="background-image: url('<?php print esc_attr($preview); ?>');"><div><?php print esc_html($name) ?></div></div><?php
                } else {
                    ?><div class="azh-element no-image  <?php print isset($general_elements[$path]) ? 'general' : ''; ?>" data-url="<?php print esc_attr($url); ?>" data-path="<?php print esc_attr(ltrim(str_replace($dir, '', $path), '/')) ?>" data-dir="<?php print esc_attr($dir) ?>" data-dir-uri="<?php print esc_attr($elements_uri[$path]) ?>"><div><?php print esc_html($name) ?></div></div><?php
                        }
                    }
                    ?>        
        </div>
    </div>
    <?php
}

add_action('wp_footer', 'azh_footer');

function azh_footer() {
    $user = wp_get_current_user();
    $post = get_post();
    if (in_array('administrator', (array) $user->roles) || ($post && isset($post->post_author) && $post->post_author == get_current_user_id())) {
        if (isset($_GET['azh']) && $_GET['azh'] == 'customize') {
            ?>
            <div id="azexo-html-library">
                <div class="azh-library-actions">
                    <div class="azh-general" title="<?php esc_html_e('General sections', 'azh'); ?>"></div>
                    <div class="azh-builder azh-active" title="<?php esc_html_e('Builder', 'azh'); ?>"></div>
                    <?php if (defined('YP_VERSION') && function_exists('yp_urlencode')): ?>
                        <a href="<?php print admin_url('admin.php?page=yellow-pencil-editor&href=' . yp_urlencode(get_the_permalink()) . '&yp_id=' . get_the_ID() . ''); ?>" target="_blank" class="azh-style" title="<?php esc_html_e('Customize styles', 'azh'); ?>"></a>
                    <?php endif; ?>
                    <div class="azh-save" title="<?php esc_html_e('Save page', 'azh'); ?>"></div>
                    <a href="<?php print get_permalink(); ?>" target="_blank" class="azh-live" title="<?php esc_html_e('View page', 'azh'); ?>"></a>
                </div>
                <div class="azh-general">
                </div>
                <div class="azh-builder azh-active">
                    <?php
                    azh_meta_box();
                    ?>
                </div>
            </div>
            <?php
        }
    }
}

add_action('admin_enqueue_scripts', 'azh_admin_scripts');

function azh_admin_scripts() {
    azh_icon_font_enqueue('fontawesome');
    azh_icon_font_enqueue('openiconic');
    azh_icon_font_enqueue('typicons');
    azh_icon_font_enqueue('entypo');
    azh_icon_font_enqueue('linecons');
    azh_icon_font_enqueue('monosocial');
    azh_icon_font_enqueue('custom');
}

add_filter('azh_icons', 'azh_icons');

function azh_icons($icons) {
    global $azh_icons;
    return array_merge($azh_icons, $icons);
}

function azh_get_icon_font_url($font) {
    switch ($font) {
        case 'fontawesome':
            return plugins_url('css/font-awesome/css/font-awesome.min.css', __FILE__);
            break;
        case 'openiconic':
            return plugins_url('css/az-open-iconic/az_openiconic.min.css', __FILE__);
            break;
        case 'typicons':
            return plugins_url('css/typicons/src/font/typicons.min.css', __FILE__);
            break;
        case 'entypo':
            return plugins_url('css/az-entypo/az_entypo.min.css', __FILE__);
            break;
        case 'linecons':
            return plugins_url('css/az-linecons/az_linecons_icons.min.css', __FILE__);
            break;
        case 'monosocial':
            return plugins_url('css/monosocialiconsfont/monosocialiconsfont.min.css', __FILE__);
            break;
        case 'custom':
            $urls = array();
            $settings = get_option('azh-settings');
            if (!empty($settings['custom-icons-css'])) {
                $dirs = apply_filters('azh_directory', array_combine(array(get_template_directory() . '/azh'), array(get_template_directory_uri() . '/azh')));
                if (is_array($dirs)) {
                    foreach ($dirs as $dir => $uri) {
                        if (is_dir($dir)) {
                            $custom_icons_css = explode("\n", $settings['custom-icons-css']);
                            if (is_array($custom_icons_css)) {
                                foreach ($custom_icons_css as $file) {
                                    if (file_exists($dir . '/' . $file)) {
                                        $urls[$uri . '/' . $file];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $urls;
            break;
        default:
            return apply_filters('azh_get_icon_font_url', false, $font);
    }
    return false;
}

function azh_icon_font_enqueue($font) {
    $urls = (array) azh_get_icon_font_url($font);
    if (!empty($urls)) {
        foreach ($urls as $url) {
            wp_enqueue_style($url, $url);
        }
    }
}

function azh_content_begin() {
    global $azh_widget_nested;
    if (!isset($azh_widget_nested)) {
        $azh_widget_nested = 0;
    }
    if (is_page() && get_post_meta(get_the_ID(), 'azh', true)) {
        remove_filter('the_content', 'wpautop');
    }
    if (get_post_type() == 'azh_widget') {
        $azh_widget_nested++;
        if ($azh_widget_nested == 1) {
            remove_filter('the_content', 'wptexturize');
            remove_filter('the_content', 'convert_smilies', 20);
            remove_filter('the_content', 'wpautop');
            remove_filter('the_content', 'shortcode_unautop');
            remove_filter('the_content', 'prepend_attachment');
            remove_filter('the_content', 'wp_make_content_images_responsive');

            remove_filter('the_content', 'do_shortcode', 11);
            add_filter('the_content', 'azh_do_shortcode', 11);
        }
    } else {
        
    }
}

function azh_content_end() {
    global $azh_widget_nested;
    if (is_page() && get_post_meta(get_the_ID(), 'azh', true)) {
        add_filter('the_content', 'wpautop');
    }
    if (get_post_type() == 'azh_widget') {
        $azh_widget_nested--;
        if ($azh_widget_nested == 0) {
            add_filter('the_content', 'wptexturize');
            add_filter('the_content', 'convert_smilies', 20);
            add_filter('the_content', 'wpautop');
            add_filter('the_content', 'shortcode_unautop');
            add_filter('the_content', 'prepend_attachment');
            add_filter('the_content', 'wp_make_content_images_responsive');

            remove_filter('the_content', 'azh_do_shortcode', 11);
            add_filter('the_content', 'do_shortcode', 11);
        } else {
            
        }
    }
}

function azh_enqueue_icons($content) {
    global $azh_icons, $azh_icons_index;
    foreach ($azh_icons as $icon_type => $icons) {
        $pattern = '/' . implode('|', array_keys($icons)) . '/';
        if (preg_match($pattern, $content, $matches)) {
            azh_icon_font_enqueue($azh_icons_index[$matches[0]]);
        }
    }
}

function azh_get_icons_fonts_urls($content) {
    $fonts_urls = array();
    global $azh_icons, $azh_icons_index;
    foreach ($azh_icons as $icon_type => $icons) {
        $pattern = '/' . implode('|', array_keys($icons)) . '/';
        if (preg_match($pattern, $content, $matches)) {
            $urls = (array) azh_get_icon_font_url($azh_icons_index[$matches[0]]);
            if (!empty($urls)) {
                $fonts_urls = array_merge($fonts_urls, $urls);
            }
        }
    }
    return $fonts_urls;
}

function azh_replaces($content, $azh_uri = false) {
    $replaces = array(
        'azh-uri' => $azh_uri ? $azh_uri : apply_filters('azh_uri', get_template_directory_uri() . '/azh'),
    );

    $post = azh_get_closest_current_post('azh_widget', false);
    if ($post) {
        $replaces['post_title'] = $post->post_title;
        $replaces['post_excerpt'] = $post->post_excerpt;
        $replaces['post_content'] = $post->post_content;
        $replaces['post_thumbnail'] = get_the_post_thumbnail_url($post, 'full');
        $replaces['post_permalink'] = get_permalink($post);
    } else {
        $replaces['post_title'] = get_bloginfo('name');
        $replaces['post_excerpt'] = get_bloginfo('description');
        $replaces['post_content'] = '';
        $replaces['post_thumbnail'] = '';
        $replaces['post_permalink'] = '';
    }

    $replaces = apply_filters('azh_replaces', $replaces);
    $content = preg_replace_callback('#{{([^}]+)}}#', function($m) use ($replaces) {
        if (isset($replaces[$m[1]])) { // If it exists in our array            
            return $replaces[$m[1]]; // Then replace it from our array
        } else {
            return $m[0]; // Otherwise return the whole match (basically we won't change it)
        }
    }, $content);
    return $content;
}

function azh_remove_comments($content) {
    $content = preg_replace_callback('#\[\[([^\]]+)\]\]#', function($m) {
        return '';
    }, $content);
    return $content;
}

add_filter('the_content', 'azh_the_content', 9);

function azh_the_content($content) {

    azh_content_begin();

    if (get_post_type() == 'azh_widget' || (is_page() && get_post_meta(get_the_ID(), 'azh', true))) {

        if (preg_match('/carousel-wrapper/', $content)) {
            wp_enqueue_script('owl.carousel');
            wp_enqueue_style('owl.carousel');
        }
        if (preg_match('/(image-popup|iframe-popup)/', $content)) {
            wp_enqueue_script('magnific-popup');
            wp_enqueue_style('magnific-popup');
        }
        if (preg_match('/data-sr/', $content)) {
            wp_enqueue_script('scrollReveal');
        }
        if (preg_match('/masonry/', $content)) {
            wp_enqueue_script('masonry');
        }
        if (preg_match('/azexo-tabs/', $content)) {
            wp_enqueue_script('jquery-ui-tabs');
        }
        if (preg_match('/azexo-accordion/', $content)) {
            wp_enqueue_script('jquery-ui-accordion');
        }
    }

    $content = azh_replaces($content);
    $content = azh_remove_comments($content);

    azh_enqueue_icons($content);

    return $content;
}

add_filter('the_content', 'azh_the_content_last', 100);

function azh_the_content_last($content) {
    azh_content_end();
    return $content;
}

add_action('widgets_init', 'azh_widgets_register_widgets');

function azh_widgets_register_widgets() {
    register_widget('AZH_Widget');
}

class AZH_Widget extends WP_Widget {

    function __construct() {
        parent::__construct('azh_widget', __('AZEXO - HTML Widget', 'azh'));
    }

    function widget($args, $instance) {

        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);

        print $args['before_widget'];
        if ($title) {
            print $args['before_title'] . $title . $args['after_title'];
        }

        if (!empty($instance['post'])) {
            $wpautop = false;
            if (has_filter('the_content', 'wpautop')) {
                remove_filter('the_content', 'wpautop');
                $wpautop = true;
            }

            if ($instance['post'] == NULL) {
                print apply_filters('the_content', get_the_content(''));
            } else {
                global $post, $wp_query;
                $original = $post;
                $post = get_post($instance['post']);
                setup_postdata($post);
                print apply_filters('the_content', get_the_content(''));
                $wp_query->post = $original;
                wp_reset_postdata();
            }

            if ($wpautop) {
                add_filter('the_content', 'wpautop');
            }
        }

        print $args['after_widget'];
    }

    function form($instance) {
        $defaults = array('post' => '', 'title' => '');
        $instance = wp_parse_args((array) $instance, $defaults);


        $azh_widgets = array();
        $loop = new WP_Query(array(
            'post_type' => 'azh_widget',
            'post_status' => 'publish',
            'showposts' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        if ($loop->have_posts()) {
            global $post, $wp_query;
            $original = $post;
            while ($loop->have_posts()) {
                $loop->the_post();
                $azh_widgets[] = $post;
            }
            $wp_query->post = $original;
            wp_reset_postdata();
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'azh'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post'); ?>"><?php _e('AZH Widget:', 'azh'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('post'); ?>" name="<?php echo $this->get_field_name('post'); ?>">
                <?php
                foreach ($azh_widgets as $azh_widget) :
                    ?>
                    <option value="<?php echo $azh_widget->ID ?>" <?php selected($azh_widget->ID, $instance['post']) ?>><?php echo $azh_widget->post_title; ?></option>
                <?php endforeach; ?>
            </select>
        </p>        
        <?php
    }

}

add_action('init', 'azh_widgets_register');

function azh_widgets_register() {
    register_post_type('azh_widget', array(
        'labels' => array(
            'name' => __('AZH Widget', 'azh'),
            'singular_name' => __('AZH Widget', 'azh'),
            'add_new' => _x('Add AZH Widget', 'azh'),
            'add_new_item' => _x('Add New AZH Widget', 'azh'),
            'edit_item' => _x('Edit AZH Widget', 'azh'),
            'new_item' => _x('New AZH Widget', 'azh'),
            'view_item' => _x('View AZH Widget', 'azh'),
            'search_items' => _x('Search AZH Widgets', 'azh'),
            'not_found' => _x('No AZH Widget found', 'azh'),
            'not_found_in_trash' => _x('No AZH Widget found in Trash', 'azh'),
            'parent_item_colon' => _x('Parent AZH Widget:', 'azh'),
            'menu_name' => _x('AZH Widgets', 'azh'),
        ),
        'query_var' => false,
        'rewrite' => true,
        'hierarchical' => true,
        'supports' => array('title', 'editor', 'revisions', 'thumbnail', 'custom-fields'),
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_in_menu' => true,
        'public' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
            )
    );
    register_taxonomy('widget_type', array('azh_widget'), array(
        'label' => __('Widget type', 'azh'),
        'hierarchical' => true,
    ));
}

add_filter('default_content', 'my_editor_content', 10, 2);

function my_editor_content($content, $post) {

    if ($post->post_type == 'azh_widget') {
        return '<div data-section="element"><div data-element=" "></div></div>';
    }

    return $content;
}

add_action('wp_enqueue_scripts', 'azh_scripts');

function azh_scripts() {
    $user = wp_get_current_user();
    $post = get_post();
    if (in_array('administrator', (array) $user->roles) || ($post && isset($post->post_author) && $post->post_author == get_current_user_id())) {
        $edit_links = array();
        $azh_widgets_edit = array();
        global $wp_widget_factory;
        foreach ($wp_widget_factory->widgets as $name => $widget_obj) {
            if ($name == 'AZH_Widget') {
                $instances = $widget_obj->get_settings();
                foreach ($instances as $number => $instance) {
                    if (isset($instance['post']) && is_numeric($instance['post'])) {
                        $post = get_post($instance['post']);
                        if ($post) {
                            $azh_widgets_edit['#' . $widget_obj->id_base . '-' . $number] = get_edit_post_link($post);
                        }
                    }
                }
            }
        }
        $edit_links['azh_widgets'] = array(
            'links' => $azh_widgets_edit,
            'text' => esc_html__('Edit AZH Widget', 'azh'),
            'target' => '_blank',
        );
        if (is_page() && get_post_meta(get_the_ID(), 'azh', true)) {
            $sections_edit = array();
            $post = get_post();
            preg_match_all('/ data-section=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $matches);
            if (is_array($matches)) {
                $post_type_object = get_post_type_object('page');
                foreach ($matches[1] as $match) {
                    $sections_edit['[data-section="' . $match . '"]'] = esc_url(admin_url(sprintf($post_type_object->_edit_link . '&action=edit&section=' . $match, get_the_ID())));
                }
            }
            $edit_links['sections'] = array(
                'links' => $sections_edit,
                'css' => array('top' => '0', 'left' => '0'),
                'text' => esc_html__('Edit section', 'azh'),
                'target' => '_self',
            );
            $elements_edit = array();
            preg_match_all('/ data-element=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $matches);
            if (is_array($matches)) {
                $post_type_object = get_post_type_object('page');
                foreach ($matches[1] as $match) {
                    $elements_edit['[data-element="' . $match . '"]'] = esc_url(admin_url(sprintf($post_type_object->_edit_link . '&action=edit&element=' . $match, get_the_ID())));
                }
            }
            $edit_links['elements'] = array(
                'links' => $elements_edit,
                'css' => array('top' => '0', 'right' => '0'),
                'text' => esc_html__('Edit element', 'azh'),
                'target' => '_self',
            );
        }
        wp_enqueue_script('azh_admin_frontend', plugins_url('js/admin-frontend.js', __FILE__), array('jquery', 'underscore'), false, true);
        $azh = azh_get_object();
        $azh['edit_links'] = $edit_links;
        wp_localize_script('azh_admin_frontend', 'azh', $azh);

        if (isset($_GET['azh']) && $_GET['azh'] == 'customize') {
            wp_enqueue_style('azh_admin_frontend', plugins_url('css/admin-frontend.css', __FILE__));
            wp_enqueue_script('simplemodal', plugins_url('js/jquery.simplemodal.js', __FILE__), array('jquery'), false, true);
            wp_enqueue_script('azh_admin', plugins_url('js/admin.js', __FILE__), array('azh_admin_frontend'), array(), false, true);
            wp_enqueue_script('azh_htmlparser', plugins_url('js/htmlparser.js', __FILE__), array('jquery'), false, true);
            wp_enqueue_script('azh_html_editor', plugins_url('js/html_editor.js', __FILE__), array('jquery'), false, true);
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_style('dashicons');
        }
    }
    wp_enqueue_script('azh_frontend', plugins_url('js/frontend.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_style('azh_frontend', plugins_url('css/frontend.css', __FILE__));


    $settings = get_option('azh-settings');

    if (isset($settings['container-widths'])) {
        $widths = explode("\n", trim($settings['container-widths']));
        if (count($widths) > 0) {
            $custom_css = ".az-container {
                padding-right: 15px;
                padding-left: 15px;
                margin-left: auto;
                margin-right: auto;
                box-sizing: border-box;
            }";
            for ($i = 0; $i < count($widths); $i++) {
                $width = $widths[$i];
                $min_max = explode(":", $width);
                $custom_css .= "@media (min-width: " . $min_max[0] . ") {
                    .az-container {
                        max-width: " . $min_max[1] . ";
                    }
                }";
            }
            wp_add_inline_style('azh_frontend', $custom_css);
        }
    }
}

add_filter('post_type_link', 'azh_post_link', 10, 3);

function azh_post_link($permalink, $post, $leavename) {
    if (in_array($post->post_type, array('azh_widget'))) {
        $external_url = get_post_meta($post->ID, 'external_url', true);
        if (!empty($external_url)) {
            return $external_url;
        }
    }
    return $permalink;
}

function azh_group_label_order($a, $b) {
    if ($a['group'] < $b['group']) {
        return -1;
    } else {
        if ($a['group'] > $b['group']) {
            return 1;
        } else {
            if ($a['label'] < $b['label']) {
                return -1;
            } else {
                if ($a['label'] > $b['label']) {
                    return 1;
                } else {
                    return 0;
                }
            }
        }
    }
}

function azh_get_terms_labels() {
    $include = array_filter(explode(',', sanitize_text_field($_POST['values'])));
    if (empty($include)) {
        $include = array(0);
    }
    $data = array();
    $terms = get_terms(array(
        'hide_empty' => false,
        'include' => $include,
    ));
    if (is_array($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            if (is_object($term)) {
                $data[$term->term_id] = $term->name;
            }
        }
    }
    print json_encode($data);
}

function azh_get_posts_labels() {
    $include = array_filter(explode(',', sanitize_text_field($_POST['values'])));
    if (empty($include)) {
        $include = array(0);
    }
    $data = array();
    $posts = get_posts(array(
        'post_type' => 'any',
        'include' => $include,
    ));
    if (is_array($posts) && !empty($posts)) {
        foreach ($posts as $post) {
            if (is_object($post)) {
                $data[$post->ID] = $post->post_title;
            }
        }
    }
    print json_encode($data);
}

add_action('wp_ajax_azh_autocomplete_labels', 'wp_ajax_azh_autocomplete_labels');

function wp_ajax_azh_autocomplete_labels() {
    if (isset($_POST['shortcode']) && isset($_POST['param_name']) && isset($_POST['values'])) {
        do_action('azh_autocomplete_' . sanitize_text_field($_POST['shortcode']) . '_' . sanitize_text_field($_POST['param_name']) . '_labels');
    }
    wp_die();
}

function azh_search_terms() {
    $data = array();
    $taxonomies_types = get_taxonomies(array('public' => true), 'objects');
    $exclude = array();
    if (isset($_POST['exclude'])) {
        $exclude = array_filter(explode(',', sanitize_text_field($_POST['exclude'])));
    }
    $terms = get_terms(array_keys($taxonomies_types), array(
        'hide_empty' => false,
        'exclude' => $exclude,
        'search' => sanitize_text_field($_POST['search']),
    ));
    if (is_array($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            if (is_object($term)) {
                $data[] = array(
                    'label' => $term->name,
                    'value' => $term->term_id,
                    'group' => isset($taxonomies_types[$term->taxonomy], $taxonomies_types[$term->taxonomy]->labels, $taxonomies_types[$term->taxonomy]->labels->name) ? $taxonomies_types[$term->taxonomy]->labels->name : __('Taxonomies', 'azh'),
                );
            }
        }
    }
    usort($data, 'azh_group_label_order');
    print json_encode($data);
}

function azh_search_posts() {
    $data = array();
    $post_types = get_post_types(array('public' => true), 'objects');
    $exclude = array();
    if (isset($_POST['exclude'])) {
        $exclude = array_filter(explode(',', sanitize_text_field($_POST['exclude'])));
    }

    $posts = get_posts(array(
        'post_type' => array_keys($post_types),
        'exclude' => $exclude,
        's' => sanitize_text_field($_POST['search']),
    ));
    if (is_array($posts) && !empty($posts)) {
        foreach ($posts as $post) {
            if (is_object($post)) {
                $data[] = array(
                    'label' => $post->post_title,
                    'value' => $post->ID,
                    'group' => isset($post_types[$post->post_type], $post_types[$post->post_type]->labels, $post_types[$post->post_type]->labels->name) ? $post_types[$post->post_type]->labels->name : __('Posts', 'azh'),
                );
            }
        }
    }
    usort($data, 'azh_group_label_order');
    print json_encode($data);
}

add_action('wp_ajax_azh_autocomplete', 'wp_ajax_azh_autocomplete');

function wp_ajax_azh_autocomplete() {
    if (isset($_POST['shortcode']) && isset($_POST['param_name']) && isset($_POST['search'])) {

        do_action('azh_autocomplete_' . sanitize_text_field($_POST['shortcode']) . '_' . sanitize_text_field($_POST['param_name']));
    }
    wp_die();
}

function azh_get_attributes($tag, $atts) {
    global $azh_shortcodes;
    if (isset($azh_shortcodes)) {
        if ($tag && isset($azh_shortcodes[$tag])) {
            $settings = $azh_shortcodes[$tag];
            if (isset($settings['params']) && !empty($settings['params'])) {
                foreach ($settings['params'] as $param) {
                    if (!isset($atts[$param['param_name']]) && isset($param['value'])) {
                        $atts[$param['param_name']] = $param['value'];
                        if (is_array($atts[$param['param_name']])) {
                            $atts[$param['param_name']] = current($atts[$param['param_name']]);
                        }
                    }
                }
            }
        }
    }
    return $atts;
}

function azh_shortcode($atts, $content = null, $tag = null) {
    global $azh_shortcodes;
    if (isset($azh_shortcodes)) {
        if ($tag && isset($azh_shortcodes[$tag])) {
            $atts = azh_get_attributes($tag, $atts);
            if (isset($azh_shortcodes[$tag]['html_template']) && file_exists($azh_shortcodes[$tag]['html_template'])) {
                ob_start();
                include($azh_shortcodes[$tag]['html_template']);
                return ob_get_clean();
            } else {
                $located = locate_template('vc_templates' . '/' . $tag . '.php');
                if ($located) {
                    ob_start();
                    include($located);
                    return ob_get_clean();
                }
            }
        }
    }
}

function azh_add_element($settings, $func = false) {
    global $azh_shortcodes;
    if (isset($settings['base'])) {
        $azh_shortcodes[$settings['base']] = $settings;
        if (!shortcode_exists($settings['base'])) {
            if ($func) {
                add_shortcode($settings['base'], $func);
            } else {
                add_shortcode($settings['base'], 'azh_shortcode');
            }
        }
    }
}

function azexo_get_dir_files($src) {
    $files = array();
    $dir = opendir($src);
    if (is_resource($dir))
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $files[$file] = realpath($src . '/' . $file);
            }
        }
    closedir($dir);
    return $files;
}

function azexo_get_skins() {
    $skins = array();
    $files = azexo_get_dir_files(get_template_directory() . '/less');
    foreach ($files as $name => $path) {
        if (is_dir($path)) {
            $skin_files = azexo_get_dir_files($path);
            if (isset($skin_files['skin.less'])) {
                $skins[] = $name;
            }
        }
    }
    return $skins;
}

add_action('wp_ajax_azh_save_shortcodes', 'azh_save_shortcodes');

function azh_save_shortcodes() {
    if (isset($_POST['shortcodes'])) {
        $user = wp_get_current_user();
        if (in_array('administrator', (array) $user->roles)) {
            $settings = get_option('azh-settings');
            $settings['shortcodes'] = stripslashes_deep($_POST['shortcodes']);
            update_option('azh-settings', $settings);
        }
    }
    wp_die();
}

add_action('wp_ajax_azh_upload', 'azh_upload');

function azh_upload() {
    if (isset($_POST['code']) && isset($_POST['dir']) && isset($_POST['file'])) {
        $user = wp_get_current_user();
        if (in_array('administrator', (array) $user->roles)) {
            $file = $_POST['dir'] . '/' . $_POST['file'];
            if (file_exists($file)) {
                azh_filesystem();
                global $wp_filesystem;
                if ($wp_filesystem->put_contents($file, stripslashes($_POST['code']), FS_CHMOD_FILE)) {
                    print '1';
                }
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_azh_copy', 'azh_copy');

function azh_copy() {
    if (isset($_POST['code'])) {
        update_option('azh_clipboard', stripslashes($_POST['code']));
    }
    if (isset($_POST['path'])) {
        update_option('azh_clipboard_path', stripslashes($_POST['path']));
    }
    wp_die();
}

add_action('wp_ajax_azh_paste', 'wp_ajax_azh_paste');

function azh_paste() {
    print json_encode(array(
        'code' => get_option('azh_clipboard'),
        'path' => get_option('azh_clipboard_path'),
    ));
    wp_die();
}

add_action('wp_ajax_azh_save', 'azh_save');

function azh_save() {
    if (isset($_POST['content']) && isset($_POST['post_id'])) {
        $user = wp_get_current_user();
        $post = get_post($_POST['post_id']);
        if (in_array('administrator', (array) $user->roles) || ($post && isset($post->post_author) && $post->post_author == get_current_user_id())) {
            
            if (isset($_POST['shortcodes'])) {
                $settings = get_option('azh-settings');
                $settings['shortcodes'] = stripslashes_deep($_POST['shortcodes']);
                update_option('azh-settings', $settings);
            }
            
            wp_update_post(array(
                'ID' => $_POST['post_id'],
                'post_content' => stripslashes($_POST['content']),
            ));
            update_post_meta($_POST['post_id'], 'azh', 'azh');
            
            print '1';
        }
    }
    wp_die();
}

azh_add_element(array(
    "name" => esc_html__('Text', 'azh'),
    "base" => "azh_text",
    "image" => AZH_URL . '/images/text.png',
    "show_settings_on_create" => true,
    'params' => array(
        array(
            'type' => 'textarea_html',
            'heading' => esc_html__('Content', 'azh'),
            'holder' => 'div',
            'param_name' => 'content',
            'value' => wp_kses(__('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>', 'azh'), array('p'))
        ),
    ),
        ), 'azh_text');

function azh_text($atts, $content = null, $tag = null) {
    return do_shortcode(shortcode_unautop($content));
}

function azh_get_file($file, $azh_uri) {
    if (file_exists($file)) {
        azh_filesystem();
        global $wp_filesystem;
        $content = $wp_filesystem->get_contents($file);
        $content = azh_remove_comments($content);
        $content = azh_replaces($content, $azh_uri);
        $content = str_replace(array("\t", "\r", "\n"), '', $content);
        $content = preg_replace('/> +</', '><', $content);
        azh_enqueue_icons($content);
        $content = do_shortcode($content);
        return $content;
    } else {
        return '';
    }
}

add_shortcode('azh_post', 'azh_post');

function azh_post($atts, $content = null, $tag = null) {
    if (isset($atts['id'])) {
        global $post, $wp_query;
        $original = $post;
        $post = get_post($atts['id']);
        setup_postdata($post);
        $content = apply_filters('the_content', get_the_content(''));
        $wp_query->post = $original;
        wp_reset_postdata();
        return $content;
    }
}

add_action('wp_ajax_azh_get_wp_editor', 'azh_get_wp_editor');

function azh_get_wp_editor() {
    ob_start();
    wp_editor('', $_POST['id'], array(
        'dfw' => false,
        'media_buttons' => true,
        'tabfocus_elements' => 'insert-media-button',
        'editor_height' => 360,
        'wpautop' => false,
        'drag_drop_upload' => true,
    ));
    $editor = ob_get_contents();
    ob_end_clean();
    print $editor;
    die();
}

add_action('admin_bar_menu', 'azh_admin_bar_menu', 999);

function azh_admin_bar_menu($wp_admin_bar) {
    if (!(isset($_GET['azh']) && $_GET['azh'] == 'customize')) {
        $wp_admin_bar->add_node(array(
            'id' => 'edit-links',
            'title' => esc_html__('Edit links', 'azh'),
            'href' => '#',
            'meta' => array(
                'class' => 'active',
            ),
        ));
        if (is_page() && get_post_meta(get_the_ID(), 'azh', true)) {
            $wp_admin_bar->add_node(array(
                'id' => 'azh-frontend-builder',
                'title' => esc_html__('Edit page in frontend', 'azh'),
                'href' => add_query_arg('azh', 'customize', get_permalink()),
            ));
        }
    }
}

add_action('template_redirect', 'azh_template_redirect');

function azh_template_redirect() {
    if (isset($_GET['azh']) && $_GET['azh'] == 'library' && isset($_GET['customize'])) {
        $files = explode('|', $_GET['customize']);
        $library = azh_get_library();
        $content = '';
        foreach ($files as $file) {
            if (is_array($library['elements'])) {
                foreach ($library['elements'] as $element_file => $name) {
                    if (strlen($element_file) - strlen($file) == strrpos($element_file, $file)) {
                        $content .= '<div data-element="' . esc_attr(ltrim(str_replace($library['elements_dir'][$element_file], '', $element_file), '/')) . '">';
                        $content .= azh_get_file($element_file, $library['elements_uri'][$element_file]);
                        $content .= '</div>';
                        break;
                    }
                }
            }
            if (is_array($library['sections'])) {
                foreach ($library['sections'] as $section_file => $name) {
                    if (strlen($section_file) - strlen($file) == strrpos($section_file, $file)) {
                        $content .= '<div data-section="' . esc_attr(ltrim(str_replace($library['sections_dir'][$section_file], '', $section_file), '/')) . '">';
                        $content .= azh_get_file($section_file, $library['sections_uri'][$section_file]);
                        $content .= '</div>';
                        break;
                    }
                }
            }
        }
        if (!empty($content)) {
            $post_id = wp_insert_post(array(
                'post_title' => '',
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
                'post_content' => $content,
                    ), true);
            if (!is_wp_error($post_id)) {
                add_post_meta($post_id, 'azh', 'azh', true);
                exit(wp_redirect(get_permalink($post_id)));
            }
        }
    }
}

add_filter('theme_page_templates', 'azh_theme_page_templates', 10, 4);

function azh_theme_page_templates($post_templates, $theme, $post, $post_type) {
    $post_templates['azexo-html-template.php'] = esc_html__('AZEXO HTML', 'azh');
    $post_templates['azexo-html-library.php'] = esc_html__('AZEXO HTML Library', 'azh');
    return $post_templates;
}

add_filter('template_include', 'azh_template_include');

function azh_template_include($template) {
    global $azh_current_post_stack;
    $azh_current_post_stack = array(get_queried_object());
    if (isset($_GET['azh']) && $_GET['azh'] == 'library') {
        return plugin_dir_path(__FILE__) . 'azexo-html-library.php';
    }
    if (is_page()) {
        $template_slug = get_page_template_slug();
        if ($template_slug == 'azexo-html-template.php') {
            $template = locate_template('azexo-html-template.php');
            if (!$template) {
                $template = plugin_dir_path(__FILE__) . 'azexo-html-template.php';
            }
            return $template;
        }
        if ($template_slug == 'azexo-html-library.php') {
            $template = locate_template('azexo-html-library.php');
            if (!$template) {
                $template = plugin_dir_path(__FILE__) . 'azexo-html-library.php';
            }
            return $template;
        }
    }
    return $template;
}

add_filter('post_class', 'azh_post_class');

function azh_post_class($classes) {
    if (is_page()) {
        if (get_page_template_slug() == 'azexo-html-template.php') {
            $classes[] = 'az-container';
        }
    }
    return $classes;
}

function azh_do_shortcode_matches($content, $matches) {
    if (!empty($matches)) {
        $shift = 0;
        for ($i = 0; $i < count($matches[0]); $i++) {
            $m = array($matches[0][$i][0], $matches[1][$i][0], $matches[2][$i][0], $matches[3][$i][0], $matches[4][$i][0], $matches[5][$i][0], $matches[6][$i][0]);
            $replacement = do_shortcode_tag($m);
            $content = substr_replace($content, $replacement, $matches[0][$i][1] + $shift, strlen($m[0]));
            $shift = $shift + strlen($replacement) - strlen($m[0]);
        }
    }
    return $content;
}

function azh_do_shortcode($content, $ignore_html = false) {
    global $shortcode_tags;

    static $cache = array();

    if (false === strpos($content, '[')) {
        return $content;
    }

    if (empty($shortcode_tags) || !is_array($shortcode_tags)) {
        return $content;
    }

    $md5 = md5($content);

    if (isset($cache[$md5])) {
        $content = azh_do_shortcode_matches($content, $cache[$md5]);
    } else {
        // Find all registered tag names in $content.
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect(array_keys($shortcode_tags), $matches[1]);

        if (empty($tagnames)) {
            return $content;
        }

        $content = azh_do_shortcodes_in_html_tags($content, $ignore_html, $tagnames);

        $pattern = get_shortcode_regex($tagnames);
        $matches = array();
        preg_match_all("/$pattern/", $content, $matches, PREG_OFFSET_CAPTURE);
        $cache[$md5] = $matches;
        $content = azh_do_shortcode_matches($content, $matches);
    }

    // Always restore square braces so we don't break things like <!--[if IE ]>
    $content = unescape_invalid_shortcodes($content);

    return $content;
}

function azh_do_shortcodes_in_html_tags($content, $ignore_html, $tagnames) {
    static $cache_split = array();
    static $cache_attributes = array();
    static $cache_tag = array();
    static $cache_attr1 = array();
    static $cache_attr2 = array();

    // Normalize entities in unfiltered HTML before adding placeholders.
    $trans = array('&#91;' => '&#091;', '&#93;' => '&#093;');
    $content = strtr($content, $trans);
    $trans = array('[' => '&#91;', ']' => '&#93;');

    $pattern = get_shortcode_regex($tagnames);
    if (isset($cache_split[md5($content)])) {
        $textarr = $cache_split[md5($content)];
    } else {
        $textarr = wp_html_split($content);
    }

    foreach ($textarr as &$element) {
        if ('' == $element || '<' !== $element[0]) {
            continue;
        }

        $noopen = false === strpos($element, '[');
        $noclose = false === strpos($element, ']');
        if ($noopen || $noclose) {
            // This element does not contain shortcodes.
            if ($noopen xor $noclose) {
                // Need to encode stray [ or ] chars.
                $element = strtr($element, $trans);
            }
            continue;
        }

        if ($ignore_html || '<!--' === substr($element, 0, 4) || '<![CDATA[' === substr($element, 0, 9)) {
            // Encode all [ and ] chars.
            $element = strtr($element, $trans);
            continue;
        }

        if (isset($cache_attributes[md5($element)])) {
            $attributes = $cache_attributes[md5($element)];
        } else {
            $attributes = wp_kses_attr_parse($element);
        }

        if (false === $attributes) {
            // Some plugins are doing things like [name] <[email]>.
            if (1 === preg_match('%^<\s*\[\[?[^\[\]]+\]%', $element)) {
                if (isset($cache_tag[md5($element)])) {
                    $element = azh_do_shortcode_matches($element, $cache_tag[md5($element)]);
                } else {
                    $matches = array();
                    preg_match_all("/$pattern/", $content, $matches, PREG_OFFSET_CAPTURE);
                    $cache_tag[md5($element)] = $matches;
                    $element = azh_do_shortcode_matches($element, $matches);
                }
            }

            // Looks like we found some crazy unfiltered HTML.  Skipping it for sanity.
            $element = strtr($element, $trans);
            continue;
        }

        // Get element name
        $front = array_shift($attributes);
        $back = array_pop($attributes);
        $matches = array();
        preg_match('%[a-zA-Z0-9]+%', $front, $matches);
        $elname = $matches[0];

        // Look for shortcodes in each attribute separately.
        foreach ($attributes as &$attr) {
            $open = strpos($attr, '[');
            $close = strpos($attr, ']');
            if (false === $open || false === $close) {
                continue; // Go to next attribute.  Square braces will be escaped at end of loop.
            }
            $double = strpos($attr, '"');
            $single = strpos($attr, "'");
            if (( false === $single || $open < $single ) && ( false === $double || $open < $double )) {
                // $attr like '[shortcode]' or 'name = [shortcode]' implies unfiltered_html.
                // In this specific situation we assume KSES did not run because the input
                // was written by an administrator, so we should avoid changing the output
                // and we do not need to run KSES here.

                if (isset($cache_attr1[md5($attr)])) {
                    $attr = azh_do_shortcode_matches($attr, $cache_attr1[md5($attr)]);
                } else {
                    $matches = array();
                    preg_match_all("/$pattern/", $attr, $matches, PREG_OFFSET_CAPTURE);
                    $cache_attr1[md5($attr)] = $matches;
                    $attr = azh_do_shortcode_matches($attr, $matches);
                }
            } else {
                // $attr like 'name = "[shortcode]"' or "name = '[shortcode]'"
                // We do not know if $content was unfiltered. Assume KSES ran before shortcodes.
                if (isset($cache_attr2[md5($attr)])) {
                    $new_attr = azh_do_shortcode_matches($attr, $cache_attr2[md5($attr)]);
                } else {
                    $matches = array();
                    preg_match_all("/$pattern/", $attr, $matches, PREG_OFFSET_CAPTURE);
                    $cache_attr2[md5($attr)] = $matches;
                    $new_attr = azh_do_shortcode_matches($attr, $matches);
                }
                if (isset($cache_attr2[md5($attr)])) {
                    // Sanitize the shortcode output using KSES.
                    $new_attr = wp_kses_one_attr($new_attr, $elname);
                    if ('' !== trim($new_attr)) {
                        // The shortcode is safe to use now.
                        $attr = $new_attr;
                    }
                }
            }
        }
        $element = $front . implode('', $attributes) . $back;

        // Now encode any remaining [ or ] chars.
        $element = strtr($element, $trans);
    }

    $content = implode('', $textarr);

    return $content;
}

function azh_filesystem() {
    static $creds = false;

    require_once ABSPATH . '/wp-admin/includes/template.php';
    require_once ABSPATH . '/wp-admin/includes/file.php';

    if ($creds === false) {
        if (false === ( $creds = request_filesystem_credentials(admin_url()) )) {
            exit();
        }
    }

    if (!WP_Filesystem($creds)) {
        request_filesystem_credentials(admin_url(), '', true);
        exit();
    }
}

function azh_get_all_settings() {
    $all_settings = get_option('azh-all-settings', array());
    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles) || empty($all_settings)) {
        $dirs = apply_filters('azh_directory', array_combine(array(get_template_directory() . '/azh'), array(get_template_directory_uri() . '/azh')));
        if (is_array($dirs)) {
            foreach ($dirs as $dir => $uri) {
                if (is_dir($dir)) {
                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($iterator as $fileInfo) {
                        if ($fileInfo->isFile() && $fileInfo->getExtension() == 'json') {
                            azh_filesystem();
                            global $wp_filesystem;
                            $settings = $wp_filesystem->get_contents($fileInfo->getPathname());
                            if ($settings) {
                                $settings = json_decode($settings, true);
                                $all_settings[$fileInfo->getPath()] = $settings;
                            }
                        }
                    }
                }
            }
        }
        update_option('azh-all-settings', $all_settings);
    }

    return $all_settings;
}

function azh_get_content_settings($content) {
    $md5 = md5($content);
    $content_settings = get_option('azh-content-settings', array());
    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles) || !isset($content_settings[$md5])) {
        $pattern = get_shortcode_regex(array('azh_post'));
        $content = preg_replace_callback("/$pattern/", 'do_shortcode_tag', $content);
        preg_match_all('/(data-section|data-element)=[\'"]([^\'"]+)[\'"]/i', $content, $matches);
        if (is_array($matches)) {
            foreach ($matches[2] as $section) {
                $dirs = apply_filters('azh_directory', array_combine(array(get_template_directory() . '/azh'), array(get_template_directory_uri() . '/azh')));
                if (is_array($dirs)) {
                    foreach ($dirs as $dir => $uri) {
                        if (is_dir($dir)) {
                            if (file_exists($dir . '/' . $section)) {
                                $folders = explode('/', $section);
                                $subdir = '';
                                foreach ($folders as $folder) {
                                    $subdir = $subdir . '/' . $folder;
                                    if (!isset($content_settings[$md5][$dir . $subdir]) && is_dir($dir . $subdir)) {
                                        if (file_exists($dir . $subdir . '/azh_settings.json')) {
                                            azh_filesystem();
                                            global $wp_filesystem;
                                            $settings = $wp_filesystem->get_contents($dir . $subdir . '/azh_settings.json');
                                            if ($settings) {
                                                $settings = json_decode($settings, true);
                                                $content_settings[$md5][$dir . $subdir] = $settings;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        update_option('azh-content-settings', $content_settings);
    }

    return $content_settings[$md5];
}

function azh_get_visible_widgets() {
    global $wp_widget_factory;
    $settings = array();
    $widget_areas = wp_get_sidebars_widgets();
    foreach ($widget_areas as $widget_area => $widgets) {
        if (empty($widgets))
            continue;

        if (!is_array($widgets))
            continue;

        if ('wp_inactive_widgets' == $widget_area)
            continue;

        foreach ($widgets as $position => $widget_id) {
            // Find the conditions for this widget.
            if (preg_match('/^(.+?)-(\d+)$/', $widget_id, $matches)) {
                $id_base = $matches[1];
                $widget_number = intval($matches[2]);
            } else {
                $id_base = $widget_id;
                $widget_number = null;
            }

            $wp_widget = null;
            foreach ($wp_widget_factory->widgets as $widget_class => $widget_object) {
                if ($widget_object->id_base == $id_base) {
                    $wp_widget = $widget_object;
                }
            }
            if (!isset($settings[$id_base])) {
                $settings[$id_base] = get_option('widget_' . $id_base);
            }

            // New multi widget (WP_Widget)
            if (!is_null($widget_number)) {
                //$settings[$id_base][$widget_number]
                //$widget_areas[$widget_area][$position]
            }
            // Old single widget
            else if (!empty($settings[$id_base])) {
                //$widget_areas[$widget_area][$position]
            }
        }
    }
    return $settings;
}

function azh_get_widgets_content() {
    $widgets_content = '';
    $visible_widgets = azh_get_visible_widgets();
    foreach ($visible_widgets as $id_base => $widgets) {
        if ($id_base == 'azh_widget') {
            foreach ($widgets as $i => $settings) {
                if (isset($settings['post']) && is_numeric($settings['post'])) {
                    $widget_post = get_post($settings['post']);
                    $widgets_content .= $widget_post->post_content;
                }
            }
        }
    }
    return $widgets_content;
}

function azh_get_post_settings() {
    global $azh_content;
    if ($azh_content) {
        return azh_get_content_settings($azh_content);
    } else {
        $post = get_post();
        return azh_get_content_settings(azh_get_widgets_content() . $post->post_content);
    }
}

function azh_get_content_scripts($content) {
    $post_scripts = array('css' => array(), 'js' => array());

    $pattern = get_shortcode_regex(array('azh_post'));
    $content = preg_replace_callback("/$pattern/", 'do_shortcode_tag', $content);
    preg_match_all('/(data-section|data-element)=[\'"]([^\'"]+)[\'"]/i', $content, $matches);
    if (is_array($matches)) {
        foreach ($matches[2] as $section_element) {
            $dirs = apply_filters('azh_directory', array_combine(array(get_template_directory() . '/azh'), array(get_template_directory_uri() . '/azh')));
            if (is_array($dirs)) {
                foreach ($dirs as $dir => $uri) {
                    if (is_dir($dir)) {
                        if (file_exists($dir . '/' . $section_element)) {
                            $folders = explode('/', $section_element);
                            $subdir = '';
                            foreach ($folders as $folder) {
                                $subdir = $subdir . '/' . $folder;
                                if (is_dir($dir . $subdir)) {
                                    if (file_exists($dir . $subdir . '/azh.css')) {
                                        $post_scripts['css'][$dir . $subdir . '/azh.css'] = $uri . $subdir . '/azh.css';
                                    }
                                    if (file_exists($dir . $subdir . '/azh.js')) {
                                        $post_scripts['js'][$dir . $subdir . '/azh.js'] = $uri . $subdir . '/azh.js';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $post_scripts;
}

function azh_get_post_scripts() {
    global $azh_content;
    if ($azh_content) {
        return azh_get_content_scripts($azh_content);
    } else {
        $post = get_post();
        return azh_get_content_scripts(azh_get_widgets_content() . $post->post_content);
    }
}

global $azh_current_post_stack;
$azh_current_post_stack = array();
add_action('the_post', 'azh_the_post');

function azh_the_post($post) {
    global $azh_current_post_stack;
    $index = count($azh_current_post_stack);
    while ($index) {
        $index--;
        if ($azh_current_post_stack[$index]->ID == $post->ID) {
            array_splice($azh_current_post_stack, $index);
        }
    }
    $azh_current_post_stack[] = $post;
}

function azh_get_earliest_current_post($post_type, $equal = true) {
    global $azh_current_post_stack;
    $post = null;
    $index = 0;
    $post_type = (array) $post_type;
    while ($index < count($azh_current_post_stack)) {
        if ($equal) {
            if (in_array($azh_current_post_stack[$index]->post_type, $post_type)) {
                $post = $azh_current_post_stack[$index];
                break;
            }
        } else {
            if (!in_array($azh_current_post_stack[$index]->post_type, $post_type)) {
                $post = $azh_current_post_stack[$index];
                break;
            }
        }
        $index++;
    }
    if (is_null($post)) {
        $post = apply_filters('azh_get_earliest_current_post', $post, $post_type, $equal);
    }
    return $post;
}

function azh_get_closest_current_post($post_type, $equal = true) {
    global $azh_current_post_stack;
    $post = null;
    $index = count($azh_current_post_stack);
    $post_type = (array) $post_type;
    while ($index) {
        $index--;
        if ($equal) {
            if (in_array($azh_current_post_stack[$index]->post_type, $post_type)) {
                $post = $azh_current_post_stack[$index];
                break;
            }
        } else {
            if (!in_array($azh_current_post_stack[$index]->post_type, $post_type)) {
                $post = $azh_current_post_stack[$index];
                break;
            }
        }
    }
    if (is_null($post)) {
        $post = apply_filters('azh_get_closest_current_post', $post, $post_type, $equal);
    }
    return $post;
}

function azh_is_current_post($id) {
    global $azh_current_post_stack;
    $current_post = reset($azh_current_post_stack);
    return $current_post && is_object($current_post) && (is_single() || is_page()) && ($current_post->ID == $id);
}

function azh_get_first_shortcode($content, $first_shortcode) {
    preg_match_all('/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER);
    if (!empty($matches)) {
        foreach ($matches as $shortcode) {
            if ($first_shortcode === $shortcode[2]) {
                $pos = strpos($content, $shortcode[0]);
                if ($pos !== false)
                    return $shortcode[0];
            }
        }
    }
    return false;
}

function azh_post_video_url() {
    $embed = azh_get_first_shortcode(get_the_content(''), 'embed');
    if ($embed) {
        preg_match('/' . get_shortcode_regex(array('embed')) . '/s', $embed, $matches);
        return trim($matches[5]);
    } else {
        return trim(get_post_meta(get_the_ID(), '_video'));
    }
}

function azh_add_image_size($size) {
    if (!has_image_size($size) && !in_array($size, array('thumbnail', 'medium', 'large'))) {
        $size_array = explode('x', $size);
        if (count($size_array) == 2) {
            add_image_size($size, $size_array[0], $size_array[1], true);
        }
    }
}

function azh_get_attachment_url($attachment_id, $size) {
    azh_add_image_size($size);

    $metadata = wp_get_attachment_metadata($attachment_id);
    if (is_array($metadata)) {
        $regenerate = false;
        $size_array = explode('x', $size);
        if (count($size_array) == 2) {
            $regenerate = true;
            if (isset($metadata['width']) && isset($metadata['height'])) {
                if ((intval($metadata['width']) < intval($size_array[0])) && (intval($metadata['height']) < intval($size_array[1]))) {
                    $regenerate = false;
                }
            } else {
                $regenerate = false;
            }
        }
        if ($regenerate && (!isset($metadata['sizes']) || !isset($metadata['sizes'][$size]))) {
            if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $meta => $data) {
                    azh_add_image_size($meta);
                }
            }
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/post.php');
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, get_attached_file($attachment_id)));
            $metadata = wp_get_attachment_metadata($attachment_id);
        }
    }
    $image = wp_get_attachment_image_src($attachment_id, $size);
    if (empty($image)) {
        $image = wp_get_attachment_image_src($attachment_id, 'full');
    }
    return $image[0];
}

function azh_get_post_gallery_urls($size) {
    $gallery = get_post_gallery(get_the_ID(), false);
    if (is_array($gallery)) {
        if (isset($gallery['ids'])) {
            $attachment_ids = explode(",", $gallery['ids']);
            $urls = array();
            foreach ($attachment_ids as $attachment_id) {
                $urls[] = azh_get_attachment_url($attachment_id, $size);
            }
            return $urls;
        }
    }
    $attachment_ids = get_post_meta(get_the_ID(), '_gallery', true);
    if ($attachment_ids) {
        $attachment_ids = explode(",", $attachment_ids);
        $urls = array();
        foreach ($attachment_ids as $attachment_id) {
            $urls[] = azh_get_attachment_url($attachment_id, $size);
        }
        return $urls;
    }
    $attachment_ids = get_children(array('post_parent' => get_the_ID(), 'fields' => 'ids', 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID'));
    if (!empty($attachment_ids)) {
        $urls = array();
        foreach ($attachment_ids as $attachment_id) {
            $urls[] = azh_get_attachment_url($attachment_id, $size);
        }
        return $urls;
    }
    if (is_array($gallery)) {
        if (isset($gallery['src']) && is_array($gallery['src'])) {
            return $gallery['src'];
        }
    }
    return array();
}

add_action('widgets_init', 'azh_widgets_init');

function azh_widgets_init() {
    if (function_exists('register_sidebar')) {
        register_sidebar(array('name' => esc_html__('AZEXO HTML header', 'AZEXO'), 'id' => "azh_header", 'before_widget' => '', 'after_widget' => '', 'before_title' => '', 'after_title' => ''));
        register_sidebar(array('name' => esc_html__('AZEXO HTML footer', 'AZEXO'), 'id' => "azh_footer", 'before_widget' => '', 'after_widget' => '', 'before_title' => '', 'after_title' => ''));
    }
}

function azh_get_google_fonts($azh_settings) {
    $settings = get_option('azh-settings');
    $gf = isset($settings['google-fonts']) ? $settings['google-fonts'] : '';
    $families = explode("\n", $gf);
    if (is_array($azh_settings)) {
        foreach ($azh_settings as $dir_settings) {
            $settings_families = explode("\n", $dir_settings['google-fonts']);
            $families = array_merge($families, $settings_families);
            $families = array_unique($families);
        }
    }
    $google_fonts = array();
    if (is_array($families)) {
        foreach ($families as $font_family) {
            if (!empty($font_family)) {
                $font = explode(':', $font_family);
                if (!isset($google_fonts[$font[0]])) {
                    $google_fonts[$font[0]] = array();
                }
                if (count($font) == 2) {
                    $weights = explode(',', $font[1]);
                    foreach ($weights as $weight) {
                        if (!isset($google_fonts[$font[0]][$weight])) {
                            $google_fonts[$font[0]][$weight] = true;
                        }
                    }
                }
            }
        }
    }
    return $google_fonts;
}

function azh_get_google_fonts_url($azh_settings) {
    $fonts_url = false;
    $google_fonts = azh_get_google_fonts($azh_settings);
    $font_families = array();
    foreach ($google_fonts as $font_family => $weights) {
        if ('off' !== esc_html_x('on', $font_family . ' font: on or off', 'azh')) {
            $font_families[] = $font_family . ':' . implode(',', array_keys($weights));
        }
    }
    if (!empty($font_families)) {
        $query_args = array(
            'family' => implode(urlencode('|'), $font_families),
            'subset' => 'latin,latin-ext',
        );
        $fonts_url = add_query_arg($query_args, (is_ssl() ? 'https' : 'http') . '://fonts.googleapis.com/css');
        wp_enqueue_style('azh-extension-fonts', $fonts_url, array(), null);
    }
    return $fonts_url;
}

add_action('wp_ajax_azh_get_scripts_urls', 'azh_get_scripts_urls');

function azh_get_scripts_urls() {
    if (isset($_POST['content'])) {
        $scripts = azh_get_content_scripts(stripslashes($_POST['content']));
        $fonts_url = azh_get_google_fonts_url(azh_get_content_settings(stripslashes($_POST['content'])));
        if ($fonts_url) {
            $scripts['css'][] = $fonts_url;
        }
        $scripts['css'] = array_merge($scripts['css'], azh_get_icons_fonts_urls(stripslashes($_POST['content'])));
        print json_encode($scripts);
    }
    die();
}
