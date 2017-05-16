<?php

/*
  Plugin Name: AZEXO Visual Composer elements
  Plugin URI: http://azexo.com
  Description: AZEXO Visual Composer elements
  Author: azexo
  Author URI: http://azexo.com
  Version: 1.24
  Text Domain: azvc
 */

define('AZVC_URL', plugins_url('', __FILE__));
define('AZVC_DIR', trailingslashit(dirname(__FILE__)) . '/');

add_action('plugins_loaded', 'azvc_plugins_loaded');

function azvc_plugins_loaded() {
    load_plugin_textdomain('azvc', FALSE, basename(dirname(__FILE__)) . '/lang/');
}

add_filter('upload_mimes', 'azvc_upload_mimes');

function azvc_upload_mimes($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}

function azvc_add_shortcode($settings) {
    if (function_exists('vc_map')) {
        vc_map($settings);
        global $azh_shortcodes;
        if (isset($azh_shortcodes)) {
            $azh_shortcodes[$settings['base']] = $settings;
        }
    } else {
        if (function_exists('azh_add_element')) {
            azh_add_element($settings);
        }
    }
}

add_action('add_meta_boxes', 'azvc_add_meta_boxes', 10, 2);

function azvc_add_meta_boxes($post_type, $post) {
    if (in_array($post_type, array('vc_widget'))) {
        if (function_exists('azh_editor_scripts')) {
            azh_editor_scripts();
        }
    }
}

add_action('init', 'azvc_init');

function azvc_init() {
    wp_enqueue_script('azexo_vc', plugins_url('js/azexo_vc.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_style('vc_linecons'); //need in some AZEXO theme

    $taxonomies = get_taxonomies(array(), 'objects');
    $taxonomy_options = array();
    foreach ($taxonomies as $slug => $taxonomy) {
        $taxonomy_options[$taxonomy->label] = $slug;
    }

    if (function_exists('vc_map')) {
        $icon_types = array('fontawesome', 'openiconic', 'typicons', 'entypo', 'linecons', 'monosocial');
        global $azvc_icons, $azvc_icons_index;
        $azvc_icons = array();
        $azvc_icons_index = array();
        foreach ($icon_types as $icon_type) {
            $azvc_icons[$icon_type] = array();
            $arr1 = apply_filters('vc_iconpicker-type-' . $icon_type, array());
            foreach ($arr1 as $arr2) {
                if (is_array($arr2)) {
                    if (count($arr2) == 1) {
                        reset($arr2);
                        $azvc_icons[$icon_type][key($arr2)] = current($arr2);
                        $azvc_icons_index[key($arr2)] = $icon_type;
                    } else {
                        foreach ($arr2 as $arr3) {
                            if (count($arr3) == 1) {
                                reset($arr3);
                                $azvc_icons[$icon_type][key($arr3)] = current($arr3);
                                $azvc_icons_index[key($arr3)] = $icon_type;
                            }
                        }
                    }
                }
            }
        }
    }

    if (class_exists('WPBakeryShortCode')) {

        class WPBakeryShortCode_azexo_panel extends WPBakeryShortCodesContainer {
            
        }

        class WPBakeryShortCode_azexo_taxonomy extends WPBakeryShortCode {
            
        }

        class WPBakeryShortCode_azexo_carousel extends WPBakeryShortCodesContainer {
            
        }

        class WPBakeryShortCode_azexo_filters extends WPBakeryShortCodesContainer {
            
        }

        class WPBakeryShortCode_azexo_generic_content extends WPBakeryShortCode {
            
        }

        class WPBakeryShortCode_azexo_progress_bar extends WPBakeryShortCode {

            public static function convertAttributesToNewProgressBar($atts) {
                if (isset($atts['values']) && strlen($atts['values']) > 0) {
                    $values = vc_param_group_parse_atts($atts['values']);
                    if (!is_array($values)) {
                        $temp = explode(',', $atts['values']);
                        $paramValues = array();
                        foreach ($temp as $value) {
                            $data = explode('|', $value);
                            $colorIndex = 2;
                            $newLine = array();
                            $newLine['value'] = isset($data[0]) ? $data[0] : 0;
                            $newLine['label'] = isset($data[1]) ? $data[1] : '';
                            if (isset($data[1]) && preg_match('/^\d{1,3}\%$/', $data[1])) {
                                $colorIndex += 1;
                                $newLine['value'] = (float) str_replace('%', '', $data[1]);
                                $newLine['label'] = isset($data[2]) ? $data[2] : '';
                            }
                            if (isset($data[$colorIndex])) {
                                $newLine['customcolor'] = $data[$colorIndex];
                            }
                            $paramValues[] = $newLine;
                        }
                        $atts['values'] = urlencode(json_encode($paramValues));
                    }
                }

                return $atts;
            }

        }

        class WPBakeryShortCode_azexo_tabs extends WPBakeryShortCode {

            static $filter_added = false;
            protected $controls_css_settings = 'out-tc vc_controls-content-widget';
            protected $controls_list = array('edit', 'clone', 'delete');

            public function __construct($settings) {
                parent::__construct($settings);
                if (!self::$filter_added) {
                    $this->addFilter('vc_inline_template_content', 'setCustomTabId');
                    self::$filter_added = true;
                }
            }

            public function contentAdmin($atts, $content = null) {
                $width = $custom_markup = '';
                $shortcode_attributes = array('width' => '1/1');
                foreach ($this->settings['params'] as $param) {
                    if ('content' !== $param['param_name']) {
                        $shortcode_attributes[$param['param_name']] = isset($param['value']) ? $param['value'] : null;
                    } elseif ('content' === $param['param_name'] && null === $content) {
                        $content = $param['value'];
                    }
                }
                extract(shortcode_atts($shortcode_attributes, $atts));

                // Extract tab titles

                preg_match_all('/azexo_tab title="([^\"]+)"(\stab_id\=\"([^\"]+)\"){0,1}/i', $content, $matches, PREG_OFFSET_CAPTURE);

                $output = '';
                $tab_titles = array();

                if (isset($matches[0])) {
                    $tab_titles = $matches[0];
                }
                $tmp = '';
                if (count($tab_titles)) {
                    $tmp .= '<ul class="clearfix tabs_controls">';
                    foreach ($tab_titles as $tab) {
                        preg_match('/title="([^\"]+)"(\stab_id\=\"([^\"]+)\"){0,1}/i', $tab[0], $tab_matches, PREG_OFFSET_CAPTURE);
                        if (isset($tab_matches[1][0])) {
                            $tmp .= '<li><a href="#tab-' . ( isset($tab_matches[3][0]) ? $tab_matches[3][0] : sanitize_title($tab_matches[1][0]) ) . '">' . $tab_matches[1][0] . '</a></li>';
                        }
                    }
                    $tmp .= '</ul>' . "\n";
                } else {
                    $output .= do_shortcode($content);
                }

                $elem = $this->getElementHolder($width);

                $iner = '';
                foreach ($this->settings['params'] as $param) {
                    $param_value = isset(${$param['param_name']}) ? ${$param['param_name']} : '';
                    if (is_array($param_value)) {
                        // Get first element from the array
                        reset($param_value);
                        $first_key = key($param_value);
                        $param_value = $param_value[$first_key];
                    }
                    $iner .= $this->singleParamHtmlHolder($param, $param_value);
                }

                if (isset($this->settings['custom_markup']) && '' !== $this->settings['custom_markup']) {
                    if ('' !== $content) {
                        $custom_markup = str_ireplace('%content%', $tmp . $content, $this->settings['custom_markup']);
                    } elseif ('' === $content && isset($this->settings['default_content_in_template']) && '' !== $this->settings['default_content_in_template']) {
                        $custom_markup = str_ireplace('%content%', $this->settings['default_content_in_template'], $this->settings['custom_markup']);
                    } else {
                        $custom_markup = str_ireplace('%content%', '', $this->settings['custom_markup']);
                    }
                    $iner .= do_shortcode($custom_markup);
                }
                $elem = str_ireplace('%wpb_element_content%', $iner, $elem);
                $output = $elem;

                return $output;
            }

            public function getTabTemplate() {
                return '<div class="wpb_template">' . do_shortcode('[azexo_tab title="Tab" tab_id=""][/azexo_tab]') . '</div>';
            }

            public function setCustomTabId($content) {
                return preg_replace('/tab\_id\=\"([^\"]+)\"/', 'tab_id="$1-' . time() . '"', $content);
            }

        }

        require_once vc_path_dir('SHORTCODES_DIR', 'vc-column.php');

        class WPBakeryShortCode_azexo_tab extends WPBakeryShortCode_VC_Column {

            protected $controls_css_settings = 'tc vc_control-container';
            protected $controls_list = array('add', 'edit', 'clone', 'delete');
            protected $predefined_atts = array(
                'tab_id' => '',
                'title' => '',
            );
            protected $controls_template_file = 'editors/partials/backend_controls_tab.tpl.php';

            public function __construct($settings) {
                parent::__construct($settings);
            }

            public function customAdminBlockParams() {
                return ' id="tab-' . $this->atts['tab_id'] . '"';
            }

            public function mainHtmlBlockParams($width, $i) {
                $sortable = ( vc_user_access_check_shortcode_all($this->shortcode) ? 'wpb_sortable' : $this->nonDraggableClass );

                return 'data-element_type="' . $this->settings['base'] . '" class="wpb_' . $this->settings['base'] . ' ' . $sortable . ' wpb_content_holder"' . $this->customAdminBlockParams();
            }

            public function containerHtmlBlockParams($width, $i) {
                return 'class="wpb_column_container vc_container_for_children"';
            }

            public function getColumnControls($controls, $extended_css = '') {
                return $this->getColumnControlsModular($extended_css);
            }

        }

        class WPBakeryShortCode_azexo_accordion extends WPBakeryShortCode {

            protected $controls_css_settings = 'out-tc vc_controls-content-widget';

            public function __construct($settings) {
                parent::__construct($settings);
            }

            public function contentAdmin($atts, $content = null) {
                $width = $custom_markup = '';
                $shortcode_attributes = array('width' => '1/1');
                foreach ($this->settings['params'] as $param) {
                    if ('content' !== $param['param_name']) {
                        $shortcode_attributes[$param['param_name']] = isset($param['value']) ? $param['value'] : null;
                    } elseif ('content' === $param['param_name'] && null === $content) {
                        $content = $param['value'];
                    }
                }
                extract(shortcode_atts($shortcode_attributes, $atts));

                $elem = $this->getElementHolder($width);

                $inner = '';
                foreach ($this->settings['params'] as $param) {
                    $param_value = isset(${$param['param_name']}) ? ${$param['param_name']} : '';
                    if (is_array($param_value)) {
                        // Get first element from the array
                        reset($param_value);
                        $first_key = key($param_value);
                        $param_value = $param_value[$first_key];
                    }
                    $inner .= $this->singleParamHtmlHolder($param, $param_value);
                }

                $tmp = '';

                if (isset($this->settings['custom_markup']) && '' !== $this->settings['custom_markup']) {
                    if ('' !== $content) {
                        $custom_markup = str_ireplace('%content%', $tmp . $content, $this->settings['custom_markup']);
                    } elseif ('' === $content && isset($this->settings['default_content_in_template']) && '' !== $this->settings['default_content_in_template']) {
                        $custom_markup = str_ireplace('%content%', $this->settings['default_content_in_template'], $this->settings['custom_markup']);
                    } else {
                        $custom_markup = str_ireplace('%content%', '', $this->settings['custom_markup']);
                    }
                    $inner .= do_shortcode($custom_markup);
                }
                $output = str_ireplace('%wpb_element_content%', $inner, $elem);

                return $output;
            }

        }

        class WPBakeryShortCode_azexo_accordion_section extends WPBakeryShortCode_azexo_tab {

            protected $controls_css_settings = 'tc vc_control-container';
            protected $controls_list = array('add', 'edit', 'clone', 'delete');
            protected $predefined_atts = array(
                'el_class' => '',
                'width' => '',
                'title' => '',
            );
            public $nonDraggableClass = 'vc-non-draggable-container';

            public function contentAdmin($atts, $content = null) {
                $width = $el_class = $title = '';
                extract(shortcode_atts($this->predefined_atts, $atts));
                $output = '';

                $column_controls = $this->getColumnControls($this->settings('controls'));
                $column_controls_bottom = $this->getColumnControls('add', 'bottom-controls');

                if ('column_14' === $width || '1/4' === $width) {
                    $width = array('vc_col-sm-3');
                } elseif ('column_14-14-14-14' === $width) {
                    $width = array(
                        'vc_col-sm-3',
                        'vc_col-sm-3',
                        'vc_col-sm-3',
                        'vc_col-sm-3',
                    );
                } elseif ('column_13' === $width || '1/3' === $width) {
                    $width = array('vc_col-sm-4');
                } elseif ('column_13-23' === $width) {
                    $width = array('vc_col-sm-4', 'vc_col-sm-8');
                } elseif ('column_13-13-13' === $width) {
                    $width = array('vc_col-sm-4', 'vc_col-sm-4', 'vc_col-sm-4');
                } elseif ('column_12' === $width || '1/2' === $width) {
                    $width = array('vc_col-sm-6');
                } elseif ('column_12-12' === $width) {
                    $width = array('vc_col-sm-6', 'vc_col-sm-6');
                } elseif ('column_23' === $width || '2/3' === $width) {
                    $width = array('vc_col-sm-8');
                } elseif ('column_34' === $width || '3/4' === $width) {
                    $width = array('vc_col-sm-9');
                } elseif ('column_16' === $width || '1/6' === $width) {
                    $width = array('vc_col-sm-2');
                } else {
                    $width = array('');
                }
                $sortable = ( vc_user_access_check_shortcode_all($this->shortcode) ? 'wpb_sortable' : $this->nonDraggableClass );

                for ($i = 0; $i < count($width); $i ++) {
                    $output .= '<div class="group ' . $sortable . '">';
                    $output .= '<h3><span class="tab-label"><%= params.title %></span></h3>';
                    $output .= '<div ' . $this->mainHtmlBlockParams($width, $i) . '>';
                    $output .= str_replace('%column_size%', wpb_translateColumnWidthToFractional($width[$i]), $column_controls);
                    $output .= '<div class="wpb_element_wrapper">';
                    $output .= '<div ' . $this->containerHtmlBlockParams($width, $i) . '>';
                    $output .= do_shortcode(shortcode_unautop($content));
                    $output .= '</div>';
                    if (isset($this->settings['params'])) {
                        $inner = '';
                        foreach ($this->settings['params'] as $param) {
                            $param_value = isset(${$param['param_name']}) ? ${$param['param_name']} : '';
                            if (is_array($param_value)) {
                                // Get first element from the array
                                reset($param_value);
                                $first_key = key($param_value);
                                $param_value = $param_value[$first_key];
                            }
                            $inner .= $this->singleParamHtmlHolder($param, $param_value);
                        }
                        $output .= $inner;
                    }
                    $output .= '</div>';
                    $output .= str_replace('%column_size%', wpb_translateColumnWidthToFractional($width[$i]), $column_controls_bottom);
                    $output .= '</div>';
                    $output .= '</div>';
                }

                return $output;
            }

            public function mainHtmlBlockParams($width, $i) {
                return 'data-element_type="' . $this->settings['base'] . '" class=" wpb_' . $this->settings['base'] . '"' . $this->customAdminBlockParams();
            }

            public function containerHtmlBlockParams($width, $i) {
                return 'class="wpb_column_container vc_container_for_children"';
            }

            public function contentAdmin_old($atts, $content = null) {
                $width = $el_class = $title = '';
                extract(shortcode_atts($this->predefined_atts, $atts));
                $output = '';
                $column_controls = $this->getColumnControls($this->settings('controls'));
                for ($i = 0; $i < count($width); $i ++) {
                    $output .= '<div class="group wpb_sortable">';
                    $output .= '<div class="wpb_element_wrapper">';
                    $output .= '<div class="vc_row-fluid wpb_row_container">';
                    $output .= '<h3><a href="#">' . $title . '</a></h3>';
                    $output .= '<div data-element_type="' . $this->settings['base'] . '" class=" wpb_' . $this->settings['base'] . ' wpb_sortable">';
                    $output .= '<div class="wpb_element_wrapper">';
                    $output .= '<div class="vc_row-fluid wpb_row_container">';
                    $output .= do_shortcode(shortcode_unautop($content));
                    $output .= '</div>';
                    if (isset($this->settings['params'])) {
                        $inner = '';
                        foreach ($this->settings['params'] as $param) {
                            $param_value = isset(${$param['param_name']}) ? ${$param['param_name']} : '';
                            if (is_array($param_value)) {
                                // Get first element from the array
                                reset($param_value);
                                $first_key = key($param_value);
                                $param_value = $param_value[$first_key];
                            }
                            $inner .= $this->singleParamHtmlHolder($param, $param_value);
                        }
                        $output .= $inner;
                    }
                    $output .= '</div>';
                    $output .= '</div>';
                    $output .= '</div>';
                    $output .= '</div>';
                    $output .= '</div>';
                }

                return $output;
            }

            protected function outputTitle($title) {
                return '';
            }

            public function customAdminBlockParams() {
                return '';
            }

        }

        class WPBakeryShortCode_azexo_html extends WPBakeryShortCode {
            
        }

        vc_add_shortcode_param('azexo_html', 'azexo_html_form_field', plugins_url('js/azexo_html_vc.js', __FILE__));
        function azexo_html_form_field($settings, $value) {
            return '<textarea name="'
                    . $settings['param_name'] . '" class="wpb_vc_param_value '
                    . $settings['param_name'] . ' ' . $settings['type'] . '" rows="16">'
                    . htmlentities(rawurldecode(base64_decode($value)), ENT_COMPAT, 'UTF-8') . '</textarea>';
        }

    }


    azvc_add_shortcode(array(
        "name" => "AZEXO - Panel",
        "base" => "azexo_panel",
        'category' => esc_html__('AZEXO', 'azvc'),
        "as_parent" => array('except' => 'azexo_panel'),
        "content_element" => true,
        "controls" => "full",
        "show_settings_on_create" => true,
        //"is_container" => true,
        'html_template' => AZVC_DIR . 'templates/azexo_panel.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Panel title', 'azvc'),
                'param_name' => 'title',
                'description' => esc_html__('Enter text which will be used as title. Leave blank if no title is needed.', 'azvc'),
                'admin_label' => true
            ),
            array(
                'type' => 'vc_link',
                'heading' => esc_html__('URL (Link)', 'azvc'),
                'param_name' => 'link',
                'dependency' => array(
                    'element' => 'title',
                    'not_empty' => true,
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'admin_label' => true,
                'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'azvc'),
            ),
            array(
                'type' => 'css_editor',
                'heading' => esc_html__('Css', 'azvc'),
                'param_name' => 'css',
                'group' => esc_html__('Design options', 'azvc'),
            ),
        ),
        "js_view" => 'VcColumnView'
    ));

    azvc_add_shortcode(array(
        'name' => "AZEXO - Taxonomy",
        'base' => 'azexo_taxonomy',
        'category' => esc_html__('AZEXO', 'azvc'),
        'description' => esc_html__('A list or dropdown of categories', 'azvc'),
        'html_template' => AZVC_DIR . 'templates/azexo_taxonomy.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Widget title', 'azvc'),
                'param_name' => 'title',
                'description' => esc_html__('What text use as a widget title. Leave blank to use default widget title.', 'azvc'),
                'value' => esc_html__('Categories', 'azvc'),
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Taxonomy', 'azvc'),
                'param_name' => 'taxonomy',
                'value' => array_merge(array(esc_html__('Select', 'azvc') => ''), $taxonomy_options),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Display options', 'azvc'),
                'param_name' => 'options',
                'value' => array(
                    esc_html__('Dropdown', 'azvc') => 'dropdown',
                    esc_html__('Show post counts', 'azvc') => 'count',
                    esc_html__('Show hierarchy', 'azvc') => 'hierarchical'
                ),
                'description' => esc_html__('Select display options for categories.', 'azvc')
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'description' => esc_html__('Style particular content element differently - add a class name and refer to it in custom CSS.', 'azvc')
            )
        )
    ));


    azvc_add_shortcode(array(
        "name" => "AZEXO - Carousel",
        "base" => "azexo_carousel",
        'category' => esc_html__('AZEXO', 'azvc'),
        "as_parent" => array('only' => 'azexo_generic_content, azexo_html'),
        "content_element" => true,
        "controls" => "full",
        "show_settings_on_create" => true,
        //"is_container" => true,
        'html_template' => AZVC_DIR . 'templates/azexo_carousel.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Carousel title', 'azvc'),
                'param_name' => 'title',
                'description' => esc_html__('Enter text which will be used as title. Leave blank if no title is needed.', 'azvc')
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Item margin', 'azvc'),
                'param_name' => 'item_margin',
                'value' => '0',
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Center item?', 'azvc'),
                'param_name' => 'center',
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Autoplay?', 'azvc'),
                'param_name' => 'autoplay',
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Loop?', 'azvc'),
                'param_name' => 'loop',
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Contents per carousel item', 'azvc'),
                'param_name' => 'contents_per_item',
                'value' => '1',
            ),
            array(
                'type' => 'param_group',
                'heading' => esc_html__('Responsive', 'azvc'),
                'param_name' => 'responsive',
                'value' => urlencode(json_encode(array(
                    array(
                        'window_width' => '0',
                        'items' => '1'
                    ),
                    array(
                        'window_width' => '768',
                        'items' => '1'
                    )
                ))),
                'params' => array(
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Window width', 'azvc'),
                        'param_name' => 'window_width',
                        'admin_label' => true
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Items', 'azvc'),
                        'param_name' => 'items',
                        'admin_label' => true
                    ),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'azvc'),
                'admin_label' => true
            ),
            array(
                'type' => 'css_editor',
                'heading' => esc_html__('Css', 'azvc'),
                'param_name' => 'css',
                'group' => esc_html__('Design options', 'azvc'),
            ),
        ),
        "js_view" => 'VcColumnView'
    ));

    azvc_add_shortcode(array(
        "name" => "AZEXO - Filters",
        "base" => "azexo_filters",
        'category' => esc_html__('AZEXO', 'azvc'),
        "as_parent" => array('only' => 'azexo_generic_content, azexo_html'),
        "content_element" => true,
        "controls" => "full",
        "show_settings_on_create" => true,
        //"is_container" => true,
        'html_template' => AZVC_DIR . 'templates/azexo_filters.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Filters title', 'azvc'),
                'param_name' => 'title',
                'description' => esc_html__('Enter text which will be used as title. Leave blank if no title is needed.', 'azvc')
            ),
            array(
                'type' => 'param_group',
                'heading' => esc_html__('Filters', 'azvc'),
                'param_name' => 'filters',
                'value' => urlencode(json_encode(array(
                    array(
                        'title' => 'All',
                        'selector' => '> *'
                    ),
                ))),
                'params' => array(
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Title', 'azvc'),
                        'param_name' => 'title',
                        'admin_label' => true
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Selector', 'azvc'),
                        'param_name' => 'selector',
                    ),
                ),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Masonry?', 'azvc'),
                'param_name' => 'masonry',
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'param_group',
                'heading' => esc_html__('Masonry responsive', 'azvc'),
                'param_name' => 'responsive',
                'dependency' => array(
                    'element' => 'masonry',
                    'value' => array('yes'),
                ),
                'value' => urlencode(json_encode(array(
                    array(
                        'window_width' => '0',
                        'items' => '1'
                    ),
                    array(
                        'window_width' => '768',
                        'items' => '1'
                    )
                ))),
                'params' => array(
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Window width', 'azvc'),
                        'param_name' => 'window_width',
                        'admin_label' => true
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Items', 'azvc'),
                        'param_name' => 'items',
                        'admin_label' => true
                    ),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Masonry gutter (px)', 'azvc'),
                'param_name' => 'gutter',
                'value' => '0',
                'dependency' => array(
                    'element' => 'masonry',
                    'value' => array('yes'),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'azvc'),
                'admin_label' => true
            ),
            array(
                'type' => 'css_editor',
                'heading' => esc_html__('Css', 'azvc'),
                'param_name' => 'css',
                'group' => esc_html__('Design options', 'azvc'),
            ),
        ),
        "js_view" => 'VcColumnView'
    ));


    azvc_add_shortcode(array(
        "name" => "AZEXO - Generic Content",
        "base" => "azexo_generic_content",
        'category' => esc_html__('AZEXO', 'azvc'),
        "controls" => "full",
        "show_settings_on_create" => true,
        'html_template' => AZVC_DIR . 'templates/azexo_generic_content.php',
        'params' => array(
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Media type', 'azvc'),
                'param_name' => 'media_type',
                'admin_label' => true,
                'value' => array(
                    esc_html__('No media', 'azvc') => 'no_media',
                    esc_html__('Image', 'azvc') => 'image',
                    esc_html__('Gallery', 'azvc') => 'gallery',
                    esc_html__('Video', 'azvc') => 'video',
                    esc_html__('Icon', 'azvc') => 'icon',
                    esc_html__('Image and Icon', 'azvc') => 'image_icon',
                ),
                'group' => esc_html__('Media', 'azvc'),
            ),
            array(
                'type' => 'attach_image',
                'heading' => esc_html__('Image', 'azvc'),
                'param_name' => 'image',
                'group' => esc_html__('Media', 'azvc'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('image', 'image_icon'),
                ),
            ),
            array(
                'type' => 'attach_images',
                'heading' => esc_html__('Images', 'azvc'),
                'param_name' => 'gallery',
                'group' => esc_html__('Media', 'azvc'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('gallery'),
                ),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Thumbnails?', 'azvc'),
                'param_name' => 'thumbnails',
                'group' => esc_html__('Media', 'azvc'),
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('gallery'),
                )),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Image size', 'azvc'),
                'param_name' => 'img_size',
                'group' => esc_html__('Media', 'azvc'),
                'value' => 'thumbnail',
                'description' => esc_html__('Enter image size (Example: "thumbnail", "medium", "large", "full" or other sizes defined by theme). Alternatively enter size in pixels (Example: 200x100 (Width x Height)). Leave parameter empty to use "thumbnail" by default.', 'azvc'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('image', 'image_icon', 'gallery'),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Video link', 'azvc'),
                'param_name' => 'video',
                'group' => esc_html__('Media', 'azvc'),
                'value' => 'http://vimeo.com/92033601',
                'description' => sprintf(wp_kses(__('Enter link to video (Note: read more about available formats at WordPress <a href="%s" target="_blank">codex page</a>).', 'azvc'), array('a')), 'http://codex.wordpress.org/Embeds#Okay.2C_So_What_Sites_Can_I_Embed_From.3F'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('video'),
                ),
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Icon library', 'azvc'),
                'value' => array(
                    esc_html__('Font Awesome', 'azvc') => 'fontawesome',
                    esc_html__('Open Iconic', 'azvc') => 'openiconic',
                    esc_html__('Typicons', 'azvc') => 'typicons',
                    esc_html__('Entypo', 'azvc') => 'entypo',
                    esc_html__('Linecons', 'azvc') => 'linecons',
                ),
                'param_name' => 'icon_library',
                'group' => esc_html__('Media', 'azvc'),
                'description' => esc_html__('Select icon library.', 'azvc'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('icon', 'image_icon'),
                ),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_fontawesome',
                'group' => esc_html__('Media', 'azvc'),
                'value' => 'fa fa-adjust', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'fontawesome',
                    'iconsPerPage' => 4000,
                // default 100, how many icons per/page to display, we use (big number) to display all icons in single page
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'fontawesome',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_openiconic',
                'group' => esc_html__('Media', 'azvc'),
                'value' => 'vc-oi vc-oi-dial', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'openiconic',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'openiconic',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_typicons',
                'group' => esc_html__('Media', 'azvc'),
                'value' => 'typcn typcn-adjust-brightness', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'typicons',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'typicons',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_entypo',
                'group' => esc_html__('Media', 'azvc'),
                'value' => 'entypo-icon entypo-icon-note', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'entypo',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'entypo',
                ),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_linecons',
                'group' => esc_html__('Media', 'azvc'),
                'value' => 'vc_li vc_li-heart', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'linecons',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'linecons',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Link click effect', 'azvc'),
                'value' => array(
                    esc_html__('Classic link', 'azvc') => 'classic',
                    esc_html__('Image popup', 'azvc') => 'image_popup',
                    esc_html__('IFrame  popup', 'azvc') => 'iframe_popup',
                ),
                'param_name' => 'media_link_click',
                'group' => esc_html__('Media', 'azvc'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('image', 'icon', 'image_icon'),
                ),
            ),
            array(
                'type' => 'vc_link',
                'heading' => esc_html__('URL (Link)', 'azvc'),
                'param_name' => 'media_link',
                'group' => esc_html__('Media', 'azvc'),
                'dependency' => array(
                    'element' => 'media_type',
                    'value' => array('image', 'icon', 'image_icon'),
                ),
            ),
            array(
                'type' => 'textarea_raw_html',
                'heading' => esc_html__('Extra', 'azvc'),
                'param_name' => 'extra',
                'group' => esc_html__('Header', 'azvc'),
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Link click effect', 'azvc'),
                'value' => array(
                    esc_html__('Classic link', 'azvc') => 'classic',
                    esc_html__('Image popup', 'azvc') => 'image_popup',
                    esc_html__('IFrame  popup', 'azvc') => 'iframe_popup',
                ),
                'param_name' => 'title_link_click',
                'group' => esc_html__('Header', 'azvc'),
            ),
            array(
                'type' => 'vc_link',
                'heading' => esc_html__('URL (Link)', 'azvc'),
                'param_name' => 'title_link',
                'group' => esc_html__('Header', 'azvc'),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Title', 'azvc'),
                'param_name' => 'title',
                'admin_label' => true,
                'group' => esc_html__('Header', 'azvc'),
            ),
            array(
                'type' => 'textarea_raw_html',
                'heading' => esc_html__('Meta', 'azvc'),
                'param_name' => 'meta',
                'group' => esc_html__('Header', 'azvc'),
            ),
            array(
                'type' => 'textarea_html',
                'heading' => esc_html__('Content', 'azvc'),
                'holder' => 'div',
                'param_name' => 'content',
                'group' => esc_html__('Content', 'azvc'),
                'value' => wp_kses(__('<p>I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>', 'azvc'), array('p'))
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Link click effect', 'azvc'),
                'value' => array(
                    esc_html__('Classic link', 'azvc') => 'classic',
                    esc_html__('Image popup', 'azvc') => 'image_popup',
                    esc_html__('IFrame  popup', 'azvc') => 'iframe_popup',
                ),
                'param_name' => 'footer_link_click',
                'group' => esc_html__('Footer', 'azvc'),
            ),
            array(
                'type' => 'vc_link',
                'heading' => esc_html__('URL (Link)', 'azvc'),
                'param_name' => 'footer_link',
                'group' => esc_html__('Footer', 'azvc'),
            ),
            array(
                'type' => 'textarea_raw_html',
                'heading' => esc_html__('Footer', 'azvc'),
                'param_name' => 'footer',
                'group' => esc_html__('Footer', 'azvc'),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Trigger?', 'azvc'),
                'param_name' => 'trigger',
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Trigger ON selector', 'azvc'),
                'param_name' => 'trigger_on',
                'dependency' => array(
                    'element' => 'trigger',
                    'value' => array('yes'),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Trigger OFF selector', 'azvc'),
                'param_name' => 'trigger_off',
                'dependency' => array(
                    'element' => 'trigger',
                    'value' => array('yes'),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Trigger Class', 'azvc'),
                'param_name' => 'trigger_class',
                'dependency' => array(
                    'element' => 'trigger',
                    'value' => array('yes'),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Scroll Reveal settings', 'azvc'),
                'param_name' => 'sr',
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'admin_label' => true,
                'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'azvc'),
            ),
            array(
                'type' => 'css_editor',
                'heading' => esc_html__('Css', 'azvc'),
                'param_name' => 'css',
                'group' => esc_html__('Design options', 'azvc'),
            ),
        ),
    ));


    azvc_add_shortcode(array(
        'name' => __('AZEXO - Progress Bar', 'azvc'),
        'base' => 'azexo_progress_bar',
        'icon' => 'icon-wpb-graph',
        'category' => __('AZEXO', 'azvc'),
        'description' => __('Animated progress bar', 'azvc'),
        'html_template' => AZVC_DIR . 'templates/azexo_progress_bar.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __('Widget title', 'azvc'),
                'param_name' => 'title',
                'description' => __('Enter text used as widget title (Note: located above content element).', 'azvc'),
            ),
            array(
                'type' => 'param_group',
                'heading' => __('Values', 'azvc'),
                'param_name' => 'values',
                'description' => __('Enter values for graph - value, title and color.', 'azvc'),
                'value' => urlencode(json_encode(array(
                    array(
                        'label' => __('Development', 'azvc'),
                        'value' => '90',
                    ),
                    array(
                        'label' => __('Design', 'azvc'),
                        'value' => '80',
                    ),
                    array(
                        'label' => __('Marketing', 'azvc'),
                        'value' => '70',
                    ),
                ))),
                'params' => array(
                    array(
                        'type' => 'dropdown',
                        'heading' => esc_html__('Icon library', 'azvc'),
                        'value' => array(
                            esc_html__('Font Awesome', 'azvc') => 'fontawesome',
                            esc_html__('Open Iconic', 'azvc') => 'openiconic',
                            esc_html__('Typicons', 'azvc') => 'typicons',
                            esc_html__('Entypo', 'azvc') => 'entypo',
                            esc_html__('Linecons', 'azvc') => 'linecons',
                        ),
                        'param_name' => 'icon_library',
                        'group' => esc_html__('Media', 'azvc'),
                        'description' => esc_html__('Select icon library.', 'azvc'),
                        'dependency' => array(
                            'element' => 'media_type',
                            'value' => array('icon', 'image_icon'),
                        ),
                    ),
                    array(
                        'type' => 'iconpicker',
                        'heading' => esc_html__('Icon', 'azvc'),
                        'param_name' => 'icon_fontawesome',
                        'group' => esc_html__('Media', 'azvc'),
                        'value' => 'fa fa-adjust', // default value to backend editor admin_label
                        'settings' => array(
                            'emptyIcon' => false,
                            // default true, display an "EMPTY" icon?
                            'iconsPerPage' => 4000,
                        // default 100, how many icons per/page to display, we use (big number) to display all icons in single page
                        ),
                        'dependency' => array(
                            'element' => 'icon_library',
                            'value' => 'fontawesome',
                        ),
                        'description' => esc_html__('Select icon from library.', 'azvc'),
                    ),
                    array(
                        'type' => 'iconpicker',
                        'heading' => esc_html__('Icon', 'azvc'),
                        'param_name' => 'icon_openiconic',
                        'group' => esc_html__('Media', 'azvc'),
                        'value' => 'vc-oi vc-oi-dial', // default value to backend editor admin_label
                        'settings' => array(
                            'emptyIcon' => false, // default true, display an "EMPTY" icon?
                            'type' => 'openiconic',
                            'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                        ),
                        'dependency' => array(
                            'element' => 'icon_library',
                            'value' => 'openiconic',
                        ),
                        'description' => esc_html__('Select icon from library.', 'azvc'),
                    ),
                    array(
                        'type' => 'iconpicker',
                        'heading' => esc_html__('Icon', 'azvc'),
                        'param_name' => 'icon_typicons',
                        'group' => esc_html__('Media', 'azvc'),
                        'value' => 'typcn typcn-adjust-brightness', // default value to backend editor admin_label
                        'settings' => array(
                            'emptyIcon' => false, // default true, display an "EMPTY" icon?
                            'type' => 'typicons',
                            'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                        ),
                        'dependency' => array(
                            'element' => 'icon_library',
                            'value' => 'typicons',
                        ),
                        'description' => esc_html__('Select icon from library.', 'azvc'),
                    ),
                    array(
                        'type' => 'iconpicker',
                        'heading' => esc_html__('Icon', 'azvc'),
                        'param_name' => 'icon_entypo',
                        'group' => esc_html__('Media', 'azvc'),
                        'value' => 'entypo-icon entypo-icon-note', // default value to backend editor admin_label
                        'settings' => array(
                            'emptyIcon' => false, // default true, display an "EMPTY" icon?
                            'type' => 'entypo',
                            'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                        ),
                        'dependency' => array(
                            'element' => 'icon_library',
                            'value' => 'entypo',
                        ),
                    ),
                    array(
                        'type' => 'iconpicker',
                        'heading' => esc_html__('Icon', 'azvc'),
                        'param_name' => 'icon_linecons',
                        'group' => esc_html__('Media', 'azvc'),
                        'value' => 'vc_li vc_li-heart', // default value to backend editor admin_label
                        'settings' => array(
                            'emptyIcon' => false, // default true, display an "EMPTY" icon?
                            'type' => 'linecons',
                            'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                        ),
                        'dependency' => array(
                            'element' => 'icon_library',
                            'value' => 'linecons',
                        ),
                        'description' => esc_html__('Select icon from library.', 'azvc'),
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => __('Label', 'azvc'),
                        'param_name' => 'label',
                        'description' => __('Enter text used as title of bar.', 'azvc'),
                        'admin_label' => true,
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => __('Value', 'azvc'),
                        'param_name' => 'value',
                        'description' => __('Enter value of bar.', 'azvc'),
                        'admin_label' => true,
                    ),
                    array(
                        'type' => 'dropdown',
                        'heading' => __('Color', 'azvc'),
                        'param_name' => 'color',
                        'value' => array(
                    __('Default', 'azvc') => '',
                        ) + array(
                    __('Classic Grey', 'azvc') => 'bar_grey',
                    __('Classic Blue', 'azvc') => 'bar_blue',
                    __('Classic Turquoise', 'azvc') => 'bar_turquoise',
                    __('Classic Green', 'azvc') => 'bar_green',
                    __('Classic Orange', 'azvc') => 'bar_orange',
                    __('Classic Red', 'azvc') => 'bar_red',
                    __('Classic Black', 'azvc') => 'bar_black',
                        ) + (function_exists('getVcShared') ? getVcShared('colors-dashed') : array()) + array(
                    __('Custom Color', 'azvc') => 'custom',
                        ),
                        'description' => __('Select single bar background color.', 'azvc'),
                        'admin_label' => true,
                        'param_holder_class' => 'vc_colored-dropdown',
                    ),
                    array(
                        'type' => 'colorpicker',
                        'heading' => __('Custom color', 'azvc'),
                        'param_name' => 'customcolor',
                        'description' => __('Select custom single bar background color.', 'azvc'),
                        'dependency' => array(
                            'element' => 'color',
                            'value' => array('custom'),
                        ),
                    ),
                    array(
                        'type' => 'colorpicker',
                        'heading' => __('Custom text color', 'azvc'),
                        'param_name' => 'customtxtcolor',
                        'description' => __('Select custom single bar text color.', 'azvc'),
                        'dependency' => array(
                            'element' => 'color',
                            'value' => array('custom'),
                        ),
                    ),
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Units', 'azvc'),
                'param_name' => 'units',
                'description' => __('Enter measurement units (Example: %, px, points, etc. Note: graph value and units will be appended to graph title).', 'azvc'),
            ),
            array(
                'type' => 'dropdown',
                'heading' => __('Color', 'azvc'),
                'param_name' => 'bgcolor',
                'value' => array(
            __('Classic Grey', 'azvc') => 'bar_grey',
            __('Classic Blue', 'azvc') => 'bar_blue',
            __('Classic Turquoise', 'azvc') => 'bar_turquoise',
            __('Classic Green', 'azvc') => 'bar_green',
            __('Classic Orange', 'azvc') => 'bar_orange',
            __('Classic Red', 'azvc') => 'bar_red',
            __('Classic Black', 'azvc') => 'bar_black',
                ) + (function_exists('getVcShared') ? getVcShared('colors-dashed') : array()) + array(
            __('Custom Color', 'azvc') => 'custom',
                ),
                'description' => __('Select bar background color.', 'azvc'),
                'admin_label' => true,
                'param_holder_class' => 'vc_colored-dropdown',
            ),
            array(
                'type' => 'colorpicker',
                'heading' => __('Bar custom background color', 'azvc'),
                'param_name' => 'custombgcolor',
                'description' => __('Select custom background color for bars.', 'azvc'),
                'dependency' => array(
                    'element' => 'bgcolor',
                    'value' => array('custom'),
                ),
            ),
            array(
                'type' => 'colorpicker',
                'heading' => __('Bar custom text color', 'azvc'),
                'param_name' => 'customtxtcolor',
                'description' => __('Select custom text color for bars.', 'azvc'),
                'dependency' => array(
                    'element' => 'bgcolor',
                    'value' => array('custom'),
                ),
            ),
            array(
                'type' => 'checkbox',
                'heading' => __('Options', 'azvc'),
                'param_name' => 'options',
                'value' => array(
                    __('Add stripes', 'azvc') => 'striped',
                    __('Add animation (Note: visible only with striped bar).', 'azvc') => 'animated',
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'azvc'),
            ),
            array(
                'type' => 'css_editor',
                'heading' => __('CSS box', 'azvc'),
                'param_name' => 'css',
                'group' => __('Design Options', 'azvc'),
            ),
        ),
    ));


    azvc_add_shortcode(array(
        'name' => __('AZEXO - Tabs', 'azvc'),
        'base' => 'azexo_tabs',
        'show_settings_on_create' => false,
        'is_container' => true,
        'icon' => 'icon-wpb-ui-tab-content',
        'category' => __('AZEXO', 'azvc'),
        'description' => __('Tabbed content', 'azvc'),
        'html_template' => AZVC_DIR . 'templates/azexo_tabs.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __('Widget title', 'azvc'),
                'param_name' => 'title',
                'description' => __('Enter text used as widget title (Note: located above content element).', 'azvc'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'azvc'),
            ),
        ),
        'custom_markup' => '
<div class="wpb_tabs_holder wpb_holder vc_container_for_children">
<ul class="tabs_controls">
</ul>
%content%
</div>',
        'default_content' => '
[azexo_tab title="' . __('Tab 1', 'azvc') . '" tab_id=""][/azexo_tab]
[azexo_tab title="' . __('Tab 2', 'azvc') . '" tab_id=""][/azexo_tab]
',
        'js_view' => 'AZEXOTabsView',
    ));


    azvc_add_shortcode(array(
        'name' => __('Tab', 'azvc'),
        'base' => 'azexo_tab',
        'allowed_container_element' => 'vc_row',
        'is_container' => true,
        'content_element' => false,
        'html_template' => AZVC_DIR . 'templates/azexo_tab.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __('Title', 'azvc'),
                'param_name' => 'title',
                'description' => __('Enter title of tab.', 'azvc'),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Icon?', 'azvc'),
                'param_name' => 'icon',
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Icon library', 'azvc'),
                'value' => array(
                    esc_html__('Font Awesome', 'azvc') => 'fontawesome',
                    esc_html__('Open Iconic', 'azvc') => 'openiconic',
                    esc_html__('Typicons', 'azvc') => 'typicons',
                    esc_html__('Entypo', 'azvc') => 'entypo',
                    esc_html__('Linecons', 'azvc') => 'linecons',
                ),
                'param_name' => 'icon_library',
                'description' => esc_html__('Select icon library.', 'azvc'),
                'dependency' => array(
                    'element' => 'icon',
                    'value' => array('yes'),
                ),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_fontawesome',
                'value' => 'fa fa-adjust', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false,
                    // default true, display an "EMPTY" icon?
                    'iconsPerPage' => 4000,
                // default 100, how many icons per/page to display, we use (big number) to display all icons in single page
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'fontawesome',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_openiconic',
                'value' => 'vc-oi vc-oi-dial', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'openiconic',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'openiconic',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_typicons',
                'value' => 'typcn typcn-adjust-brightness', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'typicons',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'typicons',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_entypo',
                'value' => 'entypo-icon entypo-icon-note', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'entypo',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'entypo',
                ),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_linecons',
                'value' => 'vc_li vc_li-heart', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'linecons',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'linecons',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'tab_id',
                'heading' => __('Tab ID', 'azvc'),
                'param_name' => 'tab_id',
            ),
        ),
        'js_view' => 'AZEXOTabView',
    ));


    azvc_add_shortcode(array(
        'name' => __('AZEXO - Accordion', 'azvc'),
        'base' => 'azexo_accordion',
        'show_settings_on_create' => false,
        'is_container' => true,
        'icon' => 'icon-wpb-ui-accordion',
        'category' => __('AZEXO', 'azvc'),
        'description' => __('Collapsible content panels', 'azvc'),
        'html_template' => AZVC_DIR . 'templates/azexo_accordion.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __('Widget title', 'azvc'),
                'param_name' => 'title',
                'description' => __('Enter text used as widget title (Note: located above content element).', 'azvc'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Active section', 'azvc'),
                'param_name' => 'active_section',
                'value' => 1,
                'description' => __('Enter section number to be active on load or enter "false" to collapse all sections.', 'azvc'),
            ),
            array(
                'type' => 'checkbox',
                'heading' => __('Allow collapse all sections?', 'azvc'),
                'param_name' => 'collapsible',
                'description' => __('If checked, it is allowed to collapse all sections.', 'azvc'),
                'value' => array(__('Yes', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'azvc'),
            ),
        ),
        'custom_markup' => '
<div class="wpb_accordion_holder wpb_holder clearfix vc_container_for_children">
%content%
</div>
<div class="tab_controls">
    <a class="add_tab" title="' . __('Add section', 'azvc') . '"><span class="vc_icon"></span> <span class="tab-label">' . __('Add section', 'azvc') . '</span></a>
</div>
',
        'default_content' => '
    [azexo_accordion_section title="' . __('Section 1', 'azvc') . '"][/azexo_accordion_section]
    [azexo_accordion_section title="' . __('Section 2', 'azvc') . '"][/azexo_accordion_section]
',
        'js_view' => 'AZEXOAccordionView',
    ));


    azvc_add_shortcode(array(
        'name' => __('Section', 'azvc'),
        'base' => 'azexo_accordion_section',
        'allowed_container_element' => 'azexo_row',
        'is_container' => true,
        'content_element' => false,
        'html_template' => AZVC_DIR . 'templates/azexo_accordion_section.php',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __('Title', 'azvc'),
                'param_name' => 'title',
                'value' => __('Section', 'azvc'),
                'description' => __('Enter accordion section title.', 'azvc'),
            ),
            array(
                'type' => 'checkbox',
                'heading' => esc_html__('Icon?', 'azvc'),
                'param_name' => 'icon',
                'value' => array(esc_html__('Yes, please', 'azvc') => 'yes'),
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Icon library', 'azvc'),
                'value' => array(
                    esc_html__('Font Awesome', 'azvc') => 'fontawesome',
                    esc_html__('Open Iconic', 'azvc') => 'openiconic',
                    esc_html__('Typicons', 'azvc') => 'typicons',
                    esc_html__('Entypo', 'azvc') => 'entypo',
                    esc_html__('Linecons', 'azvc') => 'linecons',
                ),
                'param_name' => 'icon_library',
                'description' => esc_html__('Select icon library.', 'azvc'),
                'dependency' => array(
                    'element' => 'icon',
                    'value' => array('yes'),
                ),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_fontawesome',
                'value' => 'fa fa-adjust', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false,
                    // default true, display an "EMPTY" icon?
                    'iconsPerPage' => 4000,
                // default 100, how many icons per/page to display, we use (big number) to display all icons in single page
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'fontawesome',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_openiconic',
                'value' => 'vc-oi vc-oi-dial', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'openiconic',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'openiconic',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_typicons',
                'value' => 'typcn typcn-adjust-brightness', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'typicons',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'typicons',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_entypo',
                'value' => 'entypo-icon entypo-icon-note', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'entypo',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'entypo',
                ),
            ),
            array(
                'type' => 'iconpicker',
                'heading' => esc_html__('Icon', 'azvc'),
                'param_name' => 'icon_linecons',
                'value' => 'vc_li vc_li-heart', // default value to backend editor admin_label
                'settings' => array(
                    'emptyIcon' => false, // default true, display an "EMPTY" icon?
                    'type' => 'linecons',
                    'iconsPerPage' => 4000, // default 100, how many icons per/page to display
                ),
                'dependency' => array(
                    'element' => 'icon_library',
                    'value' => 'linecons',
                ),
                'description' => esc_html__('Select icon from library.', 'azvc'),
            ),
            array(
                'type' => 'el_id',
                'heading' => __('Section ID', 'azvc'),
                'param_name' => 'el_id',
                'description' => sprintf(__('Enter optional row ID. Make sure it is unique, and it is valid as w3c specification: %s (Must not have spaces)', 'azvc'), '<a target="_blank" href="http://www.w3schools.com/tags/att_global_id.asp">' . __('link', 'azvc') . '</a>'),
            ),
        ),
        'js_view' => 'AZEXOAccordionTabView',
    ));



    azvc_add_shortcode(array(
        'name' => __('AZEXO HTML', 'azvc'),
        'base' => 'azexo_html',
        'icon' => 'icon-wpb-raw-html',
        'wrapper_class' => 'clearfix',
        'category' => __('AZEXO', 'azvc'),
        'html_template' => AZVC_DIR . 'templates/azexo_html.php',
        'description' => __('Output raw HTML code on your page', 'azvc'),
        'params' => array(
            array(
                'type' => 'azexo_html',
                'holder' => 'div',
                'heading' => __('Raw HTML', 'azvc'),
                'param_name' => 'content',
                'value' => base64_encode('<p>I am raw html block.<br/>Click edit button to change this html</p>'),
                'description' => __('Click to change.', 'azvc'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'azvc'),
                'param_name' => 'el_class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'azvc'),
            ),
            array(
                'type' => 'css_editor',
                'heading' => __('CSS box', 'azvc'),
                'param_name' => 'css',
                'group' => __('Design Options', 'azvc'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Helper', 'azvc'),
                'param_name' => 'helper',
                'admin_label' => true,
                'description' => __('Helper text in admin side.', 'azvc'),
            ),
        ),
    ));
}

add_filter('azh_icons', 'azvc_icons', 11);

function azvc_icons($icons) {
    global $azvc_icons;
    if (empty($azvc_icons)) {
        return $icons;
    } else {
        return $azvc_icons;
    }
}

add_action('azh_load', 'azvc_azh_load', 10, 2);

function azvc_azh_load($post_type, $post) {
    if (class_exists('Vc_Manager')) {
        $vc_manager = Vc_Manager::getInstance();
        $post_types = $vc_manager->editorPostTypes();
        if (in_array($post_type, $post_types)) {
            //return true;
        }
    }
}

add_action('admin_enqueue_scripts', 'azvc_admin_scripts');

function azvc_admin_scripts() {
    wp_enqueue_style('azvc_admin', plugins_url('css/admin.css', __FILE__));
    wp_enqueue_script('azvc_admin', plugins_url('js/admin.js', __FILE__));
}

function azvc_widget_title($params = array('title' => '')) {
    if ('' === $params['title']) {
        return '';
    }

    $extraclass = ( isset($params['extraclass']) ) ? ' ' . $params['extraclass'] : '';
    $output = '<h2 class="wpb_heading' . $extraclass . '">' . $params['title'] . '</h2>';

    return $output;
}

function azvc_get_css_color($prefix, $color) {
    $rgb_color = preg_match('/rgba/', $color) ? preg_replace(array(
                '/\s+/',
                '/^rgba\((\d+)\,(\d+)\,(\d+)\,([\d\.]+)\)$/',
                    ), array(
                '',
                'rgb($1,$2,$3)',
                    ), $color) : $color;
    $string = $prefix . ':' . $rgb_color . ';';
    if ($rgb_color !== $color) {
        $string .= $prefix . ':' . $color . ';';
    }

    return $string;
}

function azexo_build_link($value) {
    return azexo_parse_multi_attribute($value, array('url' => '', 'title' => '', 'target' => '', 'rel' => ''));
}

function azexo_build_link_attributes($link) {
    $attributes = ' ';
    if (isset($link['url']) && !empty($link['url'])) {
        $attributes .= 'href="' . htmlentities(esc_url($link['url'])) . '" ';
    }
    if (isset($link['title']) && !empty($link['title'])) {
        $attributes .= 'title="' . esc_attr($link['title']) . '" ';
    }
    if (isset($link['target']) && !empty($link['target'])) {
        $attributes .= 'target="' . esc_attr($link['target']) . '" ';
    }
    return $attributes;
}

function azexo_parse_multi_attribute($value, $default = array()) {
    $result = $default;
    $params_pairs = explode('|', $value);
    if (!empty($params_pairs)) {
        foreach ($params_pairs as $pair) {
            $param = preg_split('/\:/', $pair);
            if (!empty($param[0]) && isset($param[1])) {
                $result[$param[0]] = rawurldecode($param[1]);
            }
        }
    }

    return $result;
}
