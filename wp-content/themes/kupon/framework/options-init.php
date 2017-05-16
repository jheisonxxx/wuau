<?php
/*
  ReduxFramework Config File
 */

if (!class_exists('AZEXO_Redux_Framework')) {

    class AZEXO_Redux_Framework {

        public $args = array();
        public $sections = array();
        public $theme;
        public static $redux_framework;

        public function __construct() {

            if (!class_exists('ReduxFramework')) {
                return;
            }

            add_action('init', array($this, 'initSettings'), 11); // after woocommerce.php
        }

        public function initSettings() {
            $this->theme = wp_get_theme();
            $this->setArguments();
            $this->setSections();

            if (!isset($this->args['opt_name'])) { // No errors please
                return;
            }
            add_action('redux/loaded', array($this, 'remove_demo'));
            add_filter('redux/options/' . $this->args['opt_name'] . '/args', array($this, 'change_arguments'));
            AZEXO_Redux_Framework::$redux_framework = new ReduxFramework($this->sections, $this->args);
        }

        function change_arguments($args) {
            $args['dev_mode'] = false;

            return $args;
        }

        function remove_demo() {
            if (class_exists('ReduxFrameworkPlugin')) {
                remove_filter('plugin_row_meta', array(ReduxFrameworkPlugin::instance(), 'plugin_metalinks'), null, 2);
                remove_action('admin_notices', array(ReduxFrameworkPlugin::instance(), 'admin_notices'));
            }
        }

        public function setSections() {

            ob_start();

            $ct = wp_get_theme();
            $this->theme = $ct;
            $item_name = $this->theme->get('Name');
            $tags = $this->theme->Tags;
            $screenshot = $this->theme->get_screenshot();
            $class = $screenshot ? 'has-screenshot' : '';

            $customize_title = sprintf(esc_html__('Customize &#8220;%s&#8221;', 'AZEXO'), $this->theme->display('Name'));
            ?>
            <div id="current-theme" class="<?php echo esc_attr($class); ?>">
                <?php if ($screenshot) : ?>
                    <?php if (current_user_can('edit_theme_options')) : ?>
                        <a href="<?php echo wp_customize_url(); ?>" class="load-customize hide-if-no-customize" title="<?php echo esc_attr($customize_title); ?>">
                            <img src="<?php echo esc_url($screenshot); ?>" alt="<?php esc_attr_e('Current theme preview', 'AZEXO'); ?>" />
                        </a>
                    <?php endif; ?>
                    <img class="hide-if-customize" src="<?php echo esc_url($screenshot); ?>" alt="<?php esc_attr_e('Current theme preview', 'AZEXO'); ?>" />
                <?php endif; ?>

                <h4><?php print esc_html($this->theme->display('Name')); ?></h4>

                <div>
                    <ul class="theme-info">
                        <li><?php printf(esc_html__('By %s', 'AZEXO'), $this->theme->display('Author')); ?></li>
                        <li><?php printf(esc_html__('Version %s', 'AZEXO'), $this->theme->display('Version')); ?></li>
                        <li><?php echo '<strong>' . esc_html__('Tags', 'AZEXO') . ':</strong> '; ?><?php printf($this->theme->display('Tags')); ?></li>
                    </ul>
                    <p class="theme-description"><?php print esc_html($this->theme->display('Description')); ?></p>
                    <?php
                    if ($this->theme->parent()) {
                        printf(' <p class="howto">' . wp_kses(__('This <a href="%1$s">child theme</a> requires its parent theme, %2$s.', 'AZEXO'), array('a' => array('href' => array()))) . '</p>', esc_html__('http://codex.wordpress.org/Child_Themes', 'AZEXO'), $this->theme->parent()->display('Name'));
                    }
                    ?>

                </div>
            </div>

            <?php
            $item_info = ob_get_contents();

            ob_end_clean();

            $options = get_option(AZEXO_FRAMEWORK);
            $azexo_templates = azexo_get_templates();

            global $azexo_fields;
            if (!isset($azexo_fields)) {
                $azexo_fields = array();
            }
            global $azexo_post_fields;
            $azexo_post_fields = array(
                'post_title' => esc_html__('Post title', 'AZEXO'),
                'post_summary' => esc_html__('Post summary', 'AZEXO'),
                'post_content' => esc_html__('Post content', 'AZEXO'),
                'post_thumbnail' => esc_html__('Post thumbnail', 'AZEXO'),
                'post_video' => esc_html__('Post video', 'AZEXO'),
                'post_gallery' => esc_html__('Post gallery', 'AZEXO'),
                'post_sticky' => esc_html__('Post sticky', 'AZEXO'),
                'post_date' => esc_html__('Post date', 'AZEXO'),
                'post_splitted_date' => esc_html__('Post splitted date', 'AZEXO'),
                'post_author' => esc_html__('Post author', 'AZEXO'),
                'post_author_avatar' => esc_html__('Post author avatar', 'AZEXO'),
                'post_category' => esc_html__('Post category', 'AZEXO'),
                'post_tags' => esc_html__('Post tags', 'AZEXO'),
                'post_like' => esc_html__('Post like', 'AZEXO'),
                'post_last_comment' => esc_html__('Post last comment', 'AZEXO'),
                'post_last_comment_author' => esc_html__('Post last comment author', 'AZEXO'),
                'post_last_comment_author_avatar' => esc_html__('Post last comment author avatar', 'AZEXO'),
                'post_last_comment_date' => esc_html__('Post last comment date', 'AZEXO'),
                'post_comments_count' => esc_html__('Post comments count', 'AZEXO'),
                'post_read_more' => esc_html__('Post read more link', 'AZEXO'),
                'post_share' => esc_html__('Post social share', 'AZEXO'),
                'post_comments' => esc_html__('Post comments', 'AZEXO'),
            );

            $azexo_fields = array_merge($azexo_fields, $azexo_post_fields);


            $field_templates = azexo_get_field_templates();
            $azexo_fields = array_merge($azexo_fields, $field_templates);


            $taxonomy_fields = array();
            $taxonomies = get_taxonomies(array(), 'objects');
            foreach ($taxonomies as $slug => $taxonomy) {
                $taxonomy_fields['taxonomy_' . $slug] = esc_html__('Taxonomy: ', 'AZEXO') . $taxonomy->label;
            }
            $azexo_fields = array_merge($azexo_fields, $taxonomy_fields);

            $meta_fields = array();
            if (isset($options['meta_fields']) && is_array($options['meta_fields'])) {
                $options['meta_fields'] = array_filter($options['meta_fields']);
                if (!empty($options['meta_fields'])) {
                    $meta_fields = array_combine($options['meta_fields'], $options['meta_fields']);
                }
            }
            $azexo_fields = array_merge($azexo_fields, $meta_fields);


            $azexo_fields = apply_filters('azexo_fields', $azexo_fields);

            $vc_widgets = get_posts(array(
                'numberposts' => -1,
                'post_type' => 'vc_widget',
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
            ));
            if (is_array($vc_widgets)) {
                foreach ($vc_widgets as $vc_widget) {
                    $azexo_fields[$vc_widget->ID] = $vc_widget->post_title . ' ' . esc_html__('VC Widget', 'AZEXO');
                }
            }
            $azh_widgets = get_posts(array(
                'numberposts' => -1,
                'post_type' => 'azh_widget',
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
            ));
            if (is_array($azh_widgets)) {
                foreach ($azh_widgets as $azh_widget) {
                    $azexo_fields[$azh_widget->ID] = $azh_widget->post_title . ' ' . esc_html__('AZH Widget', 'AZEXO');
                }
            }
            $pages = get_posts(array(
                'numberposts' => -1,
                'post_type' => 'page',
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
            ));
            $pages_options = array();
            if (is_array($pages)) {
                foreach ($pages as $page) {
                    $pages_options[$page->ID] = $page->post_title;
                }
            }


            $general_settings_fields = array();
            $general_settings_fields[] = array(
                'id' => 'framework_confirmation',
                'type' => 'checkbox',
                'title' => esc_html__('Framework use confirmation', 'AZEXO'),
                'description' => esc_html__('Almost all settings (including VC elements) in admin-interface of theme used as part of development framework - it allow to make HTML/JS output with high flexibility. It is complex to make CSS theme styles to cover such a big freedom fully. Many possible settings/configurations which not provided with demo content must be used with LESS/CSS sources editing.', 'AZEXO'),
                'default' => '0'
            );
            $general_settings_fields[] = array(
                'id' => 'logo',
                'type' => 'media',
                'title' => esc_html__('Logo', 'AZEXO'),
                'subtitle' => esc_html__('Upload any media using the WordPress native uploader', 'AZEXO'),
                'required' => array('header', 'contains', 'logo')
            );
            if (class_exists('WPLessPlugin')) {
                $general_settings_fields[] = array(
                    'id' => 'brand-color',
                    'type' => 'color',
                    'title' => esc_html__('Brand color', 'AZEXO'),
                    'validate' => 'color',
                    'default' => '#000',
                );
                $general_settings_fields[] = array(
                    'id' => 'accent-1-color',
                    'type' => 'color',
                    'title' => esc_html__('Accent 1 color', 'AZEXO'),
                    'validate' => 'color',
                    'default' => '#000',
                );
                $general_settings_fields[] = array(
                    'id' => 'accent-2-color',
                    'type' => 'color',
                    'title' => esc_html__('Accent 2 color', 'AZEXO'),
                    'validate' => 'color',
                    'default' => '#000',
                );
            }
            if (class_exists('Infinite_Scroll')) {
                $general_settings_fields[] = array(
                    'id' => 'infinite_scroll',
                    'type' => 'checkbox',
                    'title' => esc_html__('Infinite Scroll', 'AZEXO'),
                    'default' => '0'
                );
            }
            $general_settings_fields[] = array(
                'id' => 'single_post_template',
                'type' => 'select',
                'title' => esc_html__('Single blog template', 'AZEXO'),
                'options' => $azexo_templates,
                'default' => 'post',
            );
            $general_settings_fields[] = array(
                'id' => 'default_post_template',
                'type' => 'select',
                'title' => esc_html__('Default blog template', 'AZEXO'),
                'options' => $azexo_templates,
                'default' => 'post',
            );
            $general_settings_fields[] = array(
                'id' => 'show_sidebar',
                'type' => 'select',
                'title' => esc_html__('Show sidebar', 'AZEXO'),
                'options' => array(
                    'hidden' => esc_html__('Hidden', 'AZEXO'),
                    'left' => esc_html__('Left side', 'AZEXO'),
                    'right' => esc_html__('Right side', 'AZEXO'),
                ),
                'default' => 'right',
            );
            $general_settings_fields[] = array(
                'id' => 'favicon',
                'type' => 'media',
                'title' => esc_html__('Favicon', 'AZEXO'),
                'subtitle' => esc_html__('Upload any media using the WordPress native uploader', 'AZEXO'),
            );
            $general_settings_fields[] = array(
                'id' => 'custom-css',
                'type' => 'ace_editor',
                'title' => esc_html__('CSS Code', 'AZEXO'),
                'subtitle' => esc_html__('Paste your CSS code here.', 'AZEXO'),
                'mode' => 'css',
                'theme' => 'monokai',
                'default' => "#header{\nmargin: 0 auto;\n}"
            );
            $general_settings_fields[] = array(
                'id' => 'custom-js',
                'type' => 'ace_editor',
                'title' => esc_html__('JS Code', 'AZEXO'),
                'subtitle' => esc_html__('Paste your JS code here.', 'AZEXO'),
                'mode' => 'javascript',
                'theme' => 'chrome',
                'default' => "jQuery(document).ready(function(){\n\n});"
            );

            $general_settings_fields[] = array(
                'id' => 'custom_dashboard_pages',
                'type' => 'select',
                'multi' => true,
                'sortable' => true,
                'title' => esc_html__('Custom dashboard pages', 'AZEXO'),
                'options' => $pages_options,
            );

            $skins = function_exists('azexo_get_skins') ? azexo_get_skins() : array(isset($options['skin']) ? $options['skin'] : get_template());

            // ACTUAL DECLARATION OF SECTIONS
            $this->sections[] = array(
                'icon' => 'el-icon-cogs',
                'title' => esc_html__('General settings', 'AZEXO'),
                'fields' => $general_settings_fields
            );

            $this->sections[] = array(
                'type' => 'divide',
            );

            $post_types = get_post_types(array('_builtin' => false, 'publicly_queryable' => true), 'objects');
            if (is_array($post_types) && !empty($post_types)) {
                $this->sections[] = array(
                    'icon' => 'el-icon-cogs',
                    'title' => esc_html__('Post types settings', 'AZEXO'),
                );
                foreach ($post_types as $slug => $post_type) {
                    $fields = array(
                        array(
                            'id' => 'single_' . $slug . '_template',
                            'type' => 'select',
                            'title' => esc_html__('Single template', 'AZEXO'),
                            'options' => $azexo_templates,
                            'default' => $slug,
                        ),
                        array(
                            'id' => 'default_' . $slug . '_template',
                            'type' => 'select',
                            'title' => esc_html__('Default template', 'AZEXO'),
                            'options' => $azexo_templates,
                            'default' => $slug,
                        ),
                        array(
                            'id' => $slug . '_show_sidebar',
                            'type' => 'select',
                            'title' => esc_html__('Show sidebar', 'AZEXO'),
                            'options' => array(
                                'hidden' => esc_html__('Hidden', 'AZEXO'),
                                'left' => esc_html__('Left side', 'AZEXO'),
                                'right' => esc_html__('Right side', 'AZEXO'),
                            ),
                            'default' => 'hidden',
                        ),
                        array(
                            'id' => $slug . '_additional_sidebar',
                            'type' => 'select',
                            'multi' => true,
                            'title' => esc_html__('Show additional sidebar', 'AZEXO'),
                            'options' => array(
                                'single' => esc_html__('Single page', 'AZEXO'),
                                'list' => esc_html__('List page', 'AZEXO'),
                            ),
                            'default' => '',
                        ),
                    );
                    if ($slug != 'product') {
                        $fields = array_merge($fields, array(array(
                                'id' => $slug . '_before_list',
                                'type' => 'select',
                                'multi' => true,
                                'sortable' => true,
                                'title' => esc_html__('Before posts list', 'AZEXO'),
                                'options' => array(
                                    'result_count' => esc_html__('Result count', 'AZEXO'),
                                    'ordering' => esc_html__('Ordering', 'AZEXO'),
                                ),
                                'default' => array(),
                            ),
                            array(
                                'id' => $slug . '_custom_sorting',
                                'type' => 'select',
                                'multi' => true,
                                'sortable' => true,
                                'title' => esc_html__('Custom sorting', 'AZEXO'),
                                'options' => array(
                                    'menu_order' => esc_html__('Default sorting', 'AZEXO'),
                                    'date' => esc_html__('Sort by newness', 'AZEXO'),
                                ),
                                'default' => array('menu_order', 'date'),
                            ),
                            array(
                                'id' => $slug . '_custom_sorting_numeric_meta_keys',
                                'type' => 'multi_text',
                                'title' => esc_html__('Custom sorting numeric meta keys', 'AZEXO'),
                            ),
                        ));
                    }
                    $this->sections[] = array(
                        'icon' => 'el-icon-cogs',
                        'subsection' => true,
                        'title' => $post_type->label,
                        'fields' => $fields
                    );
                }
            }

            $header_parts = array(
                'logo' => esc_html__('Logo', 'AZEXO'),
                'search' => esc_html__('Search', 'AZEXO'),
                'primary_menu' => esc_html__('Primary menu', 'AZEXO'),
                'secondary_menu' => esc_html__('Secondary menu', 'AZEXO'),
                'mobile_menu_button' => esc_html__('Mobile menu button', 'AZEXO'),
                'mobile_menu' => esc_html__('Mobile menu', 'AZEXO'),
            );
            $files = scandir(get_template_directory() . '/template-parts');
            if (is_array($files)) {
                foreach ($files as $file) {
                    $matches = array();
                    if (preg_match('/header\-([\w\-]+)\.php/', $file, $matches)) {
                        $header_parts[$matches[1]] = $matches[0];
                    }
                }
            }

            $this->sections[] = array(
                'icon' => 'el-icon-cogs',
                'title' => esc_html__('Templates configuration', 'AZEXO'),
                'fields' => array(
                    array(
                        'id' => 'skin',
                        'type' => 'select',
                        'title' => esc_html__('Select skin', 'AZEXO'),
                        'options' => array_combine($skins, $skins),
                    ),
                    array(
                        'id' => 'header_sidebar_fullwidth',
                        'type' => 'checkbox',
                        'title' => esc_html__('Header sidebar fullwidth', 'AZEXO'),
                        'default' => '1'
                    ),
                    array(
                        'id' => 'header_parts_fullwidth',
                        'type' => 'checkbox',
                        'title' => esc_html__('Header parts fullwidth', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'middle_sidebar_fullwidth',
                        'type' => 'checkbox',
                        'title' => esc_html__('Middle sidebar fullwidth', 'AZEXO'),
                        'default' => '1'
                    ),
                    array(
                        'id' => 'content_fullwidth',
                        'type' => 'checkbox',
                        'title' => esc_html__('Content fullwidth', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'footer_sidebar_fullwidth',
                        'type' => 'checkbox',
                        'title' => esc_html__('Footer sidebar fullwidth', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'show_page_title',
                        'type' => 'checkbox',
                        'title' => esc_html__('Show page title in templates', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'show_breadcrumbs',
                        'type' => 'checkbox',
                        'title' => esc_html__('Show breadcrumb in templates', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'header',
                        'type' => 'select',
                        'multi' => true,
                        'sortable' => true,
                        'title' => esc_html__('Header parts', 'AZEXO'),
                        'options' => $header_parts,
                        'default' => array('mobile_menu_button', 'mobile_menu', 'primary_menu'),
                    ),
                    array(
                        'id' => 'author_bio',
                        'type' => 'checkbox',
                        'title' => esc_html__('Show author bio in templates', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'post_navigation',
                        'type' => 'select',
                        'title' => esc_html__('Post navigation place', 'AZEXO'),
                        'options' => array(
                            'hidden' => esc_html__('Hidden', 'AZEXO'),
                            'before' => esc_html__('Before content', 'AZEXO'),
                            'after' => esc_html__('After content', 'AZEXO'),
                        ),
                        'default' => 'hidden',
                    ),
                    array(
                        'id' => 'post_navigation_full',
                        'type' => 'checkbox',
                        'title' => esc_html__('Post navigation full template', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'post_navigation_previous',
                        'type' => 'text',
                        'title' => esc_html__('Post navigation previous text', 'AZEXO'),
                        'default' => '',
                    ),
                    array(
                        'id' => 'post_navigation_next',
                        'type' => 'text',
                        'title' => esc_html__('Post navigation next text', 'AZEXO'),
                        'default' => '',
                    ),
                    array(
                        'id' => 'related_posts',
                        'type' => 'checkbox',
                        'title' => esc_html__('Show related posts', 'AZEXO'),
                        'default' => '0'
                    ),
                    array(
                        'id' => 'comments',
                        'type' => 'checkbox',
                        'title' => esc_html__('Show comments in templates', 'AZEXO'),
                        'default' => '1'
                    ),
                    array(
                        'id' => 'default_title',
                        'type' => 'text',
                        'title' => esc_html__('Default page title', 'AZEXO'),
                        'default' => 'Latest posts',
                    ),
                    array(
                        'id' => 'post_page_title',
                        'type' => 'select',
                        'title' => esc_html__('Post page title', 'AZEXO'),
                        'options' => $azexo_fields,
                        'default' => '',
                    ),
                    array(
                        'id' => 'strip_excerpt',
                        'type' => 'checkbox',
                        'title' => esc_html__('Strip excerpt', 'AZEXO'),
                        'default' => '1',
                    ),
                    array(
                        'id' => 'excerpt_length',
                        'type' => 'text',
                        'title' => esc_html__('Excerpt length', 'AZEXO'),
                        'default' => '15',
                    ),
                    array(
                        'id' => 'comment_excerpt_length',
                        'type' => 'text',
                        'title' => esc_html__('Comment excerpt length', 'AZEXO'),
                        'default' => '15',
                    ),
                    array(
                        'id' => 'author_avatar_size',
                        'type' => 'text',
                        'title' => esc_html__('Author avatar size', 'AZEXO'),
                        'default' => '100',
                    ),
                    array(
                        'id' => 'avatar_size',
                        'type' => 'text',
                        'title' => esc_html__('Avatar size', 'AZEXO'),
                        'default' => '60',
                    ),
                    array(
                        'id' => 'related_posts_carousel_margin',
                        'type' => 'text',
                        'title' => esc_html__('Related posts carousel margin', 'AZEXO'),
                        'default' => '0',
                    ),
                    array(
                        'id' => 'before_list_place',
                        'type' => 'select',
                        'title' => esc_html__('Before posts place', 'AZEXO'),
                        'options' => array(
                            'inside_content_area' => esc_html__('Inside content area', 'AZEXO'),
                            'before_container' => esc_html__('Before container', 'AZEXO'),
                        ),
                        'default' => 'inside_content_area',
                    ),
                    array(
                        'id' => 'google_font_families',
                        'type' => 'multi_text',
                        'title' => esc_html__('Google font families', 'AZEXO'),
                    ),
                    array(
                        'id' => 'templates',
                        'type' => 'multi_text',
                        'title' => esc_html__('Templates', 'AZEXO'),
                    ),
                    array(
                        'id' => 'meta_fields',
                        'type' => 'multi_text',
                        'title' => esc_html__('Meta fields', 'AZEXO'),
                    ),
                )
            );

            foreach ($azexo_templates as $template_slug => $template_name) {


                $places = array(
                    $template_slug . '_thumbnail' => esc_html__('Thumbnail DIV', 'AZEXO'),
                    $template_slug . '_hover' => esc_html__('Thumbnail hover DIV', 'AZEXO'),
                    $template_slug . '_extra' => esc_html__('Header extra DIV', 'AZEXO'),
                    $template_slug . '_meta' => esc_html__('Header meta DIV', 'AZEXO'),
                    $template_slug . '_header' => esc_html__('Header DIV', 'AZEXO'),
                    $template_slug . '_footer' => esc_html__('Footer DIV', 'AZEXO'),
                    $template_slug . '_data' => esc_html__('Data DIV', 'AZEXO'),
                    $template_slug . '_additions' => esc_html__('Additions DIV', 'AZEXO'),
                    $template_slug . '_next' => esc_html__('Append next', 'AZEXO'),
                );
                $post_fields = array();
                foreach ($places as $id => $name) {
                    $post_fields[] = array(
                        'id' => $id,
                        'type' => 'select',
                        'multi' => true,
                        'sortable' => true,
                        'title' => $name,
                        'options' => $azexo_fields
                    );
                }

                $this->sections[] = array(
                    'icon' => 'el-icon-cogs',
                    'title' => $template_name,
                    'subsection' => true,
                    'fields' => array_merge(array(
                        array(
                            'id' => $template_slug . '_show_thumbnail',
                            'type' => 'checkbox',
                            'title' => esc_html__('Show thumbnail/gallery/video', 'AZEXO'),
                            'default' => '1'
                        ),
                        array(
                            'id' => $template_slug . '_image_thumbnail',
                            'type' => 'checkbox',
                            'title' => esc_html__('Only image thumbnail (no gallery/video)', 'AZEXO'),
                            'default' => '0',
                            'required' => array($template_slug . '_show_thumbnail', 'equals', '1')
                        ),
                        array(
                            'id' => $template_slug . '_gallery_slider_thumbnails',
                            'type' => 'checkbox',
                            'title' => esc_html__('Show gallery slider thumbnails', 'AZEXO'),
                            'default' => '0',
                            'required' => array($template_slug . '_show_thumbnail', 'equals', '1')
                        ),
                        array(
                            'id' => $template_slug . '_gallery_slider_thumbnails_vertical',
                            'type' => 'checkbox',
                            'title' => esc_html__('Vertical gallery slider thumbnails', 'AZEXO'),
                            'default' => '0',
                            'required' => array($template_slug . '_gallery_slider_thumbnails', 'equals', '1'),
                        ),
                        array(
                            'id' => $template_slug . '_zoom',
                            'type' => 'checkbox',
                            'title' => esc_html__('Zoom image on mouse hover', 'AZEXO'),
                            'default' => '0',
                            'required' => array($template_slug . '_show_thumbnail', 'equals', '1')
                        ),
                        array(
                            'id' => $template_slug . '_lazy',
                            'type' => 'checkbox',
                            'title' => esc_html__('Lazy load images', 'AZEXO'),
                            'default' => '0',
                            'required' => array($template_slug . '_show_thumbnail', 'equals', '1')
                        ),
                        array(
                            'id' => $template_slug . '_show_carousel',
                            'type' => 'checkbox',
                            'title' => esc_html__('Show gallery as carousel', 'AZEXO'),
                            'default' => '0',
                            'required' => array(
                                array($template_slug . '_show_thumbnail', 'equals', '1'),
                                array($template_slug . '_image_thumbnail', '!=', '1')
                            )
                        ),
                        array(
                            'id' => $template_slug . '_thumbnail_size',
                            'type' => 'text',
                            'title' => esc_html__('Thumbnail size', 'AZEXO'),
                            'default' => 'large',
                            'required' => array($template_slug . '_show_thumbnail', 'equals', '1')
                        ),
                        array(
                            'id' => $template_slug . '_show_title',
                            'type' => 'checkbox',
                            'title' => esc_html__('Show title', 'AZEXO'),
                            'default' => '1'
                        ),
                        array(
                            'id' => $template_slug . '_show_content',
                            'type' => 'select',
                            'title' => esc_html__('Show content/excerpt', 'AZEXO'),
                            'options' => array(
                                'hidden' => esc_html__('Hidden', 'AZEXO'),
                                'content' => esc_html__('Show content', 'AZEXO'),
                                'excerpt' => esc_html__('Show excerpt', 'AZEXO'),
                            ),
                            'default' => 'content',
                        ),
                        array(
                            'id' => $template_slug . '_excerpt_length',
                            'type' => 'text',
                            'title' => esc_html__('Excerpt length', 'AZEXO'),
                            'default' => '15',
                            'required' => array($template_slug . '_show_content', 'equals', 'excerpt')
                        ),
                        array(
                            'id' => $template_slug . '_excerpt_words_trim',
                            'type' => 'checkbox',
                            'title' => esc_html__('Excerpt trim by words', 'AZEXO'),
                            'default' => '1',
                            'required' => array($template_slug . '_show_content', 'equals', 'excerpt')
                        ),
                        array(
                            'id' => $template_slug . '_more_inside_content',
                            'type' => 'checkbox',
                            'title' => esc_html__('Show more link inside content', 'AZEXO'),
                            'default' => '1',
                            'required' => array($template_slug . '_show_content', 'equals', 'content')
                        ),
                            ), $post_fields)
                );
            }

            $this->sections[] = array(
                'icon' => 'el-icon-cogs',
                'title' => esc_html__('Fields configuration', 'AZEXO'),
                'fields' => array()
            );

            foreach ($azexo_fields as $field_slug => $field_name) {
                $fields = array();
                if (isset($taxonomy_fields[$field_slug]) || isset($meta_fields[$field_slug])) {
                    $fields[] = array(
                        'id' => str_replace('.php', '', $field_slug) . '_image',
                        'type' => 'media',
                        'title' => esc_html__('Image', 'AZEXO'),
                        'default' => '',
                    );
                    $fields[] = array(
                        'id' => str_replace('.php', '', $field_slug) . '_hide_empty',
                        'type' => 'checkbox',
                        'title' => esc_html__('Hide empty', 'AZEXO'),
                        'default' => '0',
                    );
                }
                $fields[] = array(
                    'id' => str_replace('.php', '', $field_slug) . '_prefix',
                    'type' => 'textarea',
                    'title' => esc_html__('Prefix', 'AZEXO'),
                    'default' => '',
                );
                if (isset($taxonomy_fields[$field_slug]) || isset($meta_fields[$field_slug])) {
                    $fields[] = array(
                        'id' => str_replace('.php', '', $field_slug) . '_suffix',
                        'type' => 'textarea',
                        'title' => esc_html__('Suffix', 'AZEXO'),
                        'default' => '',
                    );
                }
                $this->sections[] = array(
                    'icon' => 'el-icon-cogs',
                    'title' => $field_name,
                    'subsection' => true,
                    'fields' => $fields,
                );
            }

            $this->sections = apply_filters('azexo_settings_sections', $this->sections);

            $this->sections[] = array(
                'type' => 'divide',
            );

            $theme_info = '<div class="redux-framework-section-desc">';
            $theme_info .= '<p class="redux-framework-theme-data description theme-uri">' . wp_kses(__('<strong>Theme URL:</strong> ', 'AZEXO'), array('strong' => array())) . '<a href="' . esc_url($this->theme->get('ThemeURI')) . '" target="_blank">' . $this->theme->get('ThemeURI') . '</a></p>';
            $theme_info .= '<p class="redux-framework-theme-data description theme-author">' . wp_kses(__('<strong>Author:</strong> ', 'AZEXO'), array('strong' => array())) . $this->theme->get('Author') . '</p>';
            $theme_info .= '<p class="redux-framework-theme-data description theme-version">' . wp_kses(__('<strong>Version:</strong> ', 'AZEXO'), array('strong' => array())) . $this->theme->get('Version') . '</p>';
            $theme_info .= '<p class="redux-framework-theme-data description theme-description">' . $this->theme->get('Description') . '</p>';
            $tabs = $this->theme->get('Tags');
            if (!empty($tabs)) {
                $theme_info .= '<p class="redux-framework-theme-data description theme-tags">' . wp_kses(__('<strong>Tags:</strong> ', 'AZEXO'), array('strong' => array())) . implode(', ', $tabs) . '</p>';
            }
            $theme_info .= '</div>';

            $this->sections[] = array(
                'title' => esc_html__('Import / Export', 'AZEXO'),
                'desc' => esc_html__('Import and Export your Redux Framework settings from file, text or URL.', 'AZEXO'),
                'icon' => 'el-icon-refresh',
                'fields' => array(
                    array(
                        'id' => 'import-export',
                        'type' => 'import_export',
                        'title' => 'Import Export',
                        'subtitle' => 'Save and restore your Redux options',
                        'full_width' => false,
                    ),
                ),
            );

            $this->sections[] = array(
                'icon' => 'el-icon-info-sign',
                'title' => esc_html__('Theme Information', 'AZEXO'),
                'fields' => array(
                    array(
                        'id' => 'raw-info',
                        'type' => 'raw',
                        'content' => $item_info,
                    )
                ),
            );
        }

        public static function getArguments() {
            return array(
                'opt_name' => AZEXO_FRAMEWORK,
                'page_slug' => '_options',
                'page_title' => 'AZEXO Options',
                'update_notice' => true,
                'admin_bar' => false,
                'menu_type' => 'menu',
                'menu_title' => 'AZEXO Options',
                'allow_sub_menu' => true,
                'page_parent_post_type' => 'your_post_type',
                'customizer' => true,
                'default_mark' => '*',
                'hints' =>
                array(
                    'icon' => 'el-icon-question-sign',
                    'icon_position' => 'right',
                    'icon_color' => 'lightgray',
                    'icon_size' => 'normal',
                    'tip_style' =>
                    array(
                        'color' => 'light',
                    ),
                    'tip_position' =>
                    array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                    ),
                    'tip_effect' =>
                    array(
                        'show' =>
                        array(
                            'duration' => '500',
                            'event' => 'mouseover',
                        ),
                        'hide' =>
                        array(
                            'duration' => '500',
                            'event' => 'mouseleave unfocus',
                        ),
                    ),
                ),
                'output' => true,
                'output_tag' => true,
                'page_icon' => 'icon-themes',
                'page_permissions' => 'manage_options',
                'save_defaults' => true,
                'show_import_export' => true,
                'transient_time' => '3600',
                'network_sites' => true,
            );
        }

        public function setArguments() {

            $this->args = AZEXO_Redux_Framework::getArguments();

            $theme = wp_get_theme();
            $this->args["display_name"] = $theme->get("Name");
            $this->args["display_version"] = $theme->get("Version");
        }

    }

    global $azexo_redux_config;
    $azexo_redux_config = new AZEXO_Redux_Framework();
}

add_action('update_option_' . AZEXO_FRAMEWORK, 'update_azexo_options', 10, 3);

function update_azexo_options($old_value, $value, $option) {
    if (isset($value['favicon']) && !empty($value['favicon']) && isset($value['favicon']['id']) && !empty($value['favicon']['id'])) {
        update_option('site_icon', $value['favicon']['id']);
    }
}
