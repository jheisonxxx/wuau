<?php

add_action('azh_autocomplete_azexo_posts_list_taxonomies_labels', 'azh_get_terms_labels');
add_action('azh_autocomplete_azexo_posts_list_taxonomies', 'azh_search_terms');
add_action('azh_autocomplete_azexo_posts_list_include_labels', 'azh_get_posts_labels');
add_action('azh_autocomplete_azexo_posts_list_include', 'azh_search_posts');
add_action('azh_autocomplete_azexo_posts_list_exclude_labels', 'azh_get_posts_labels');
add_action('azh_autocomplete_azexo_posts_list_exclude', 'azh_search_posts');


if (function_exists('vc_set_as_theme')) {
    vc_set_as_theme(true);
}

if (function_exists('vc_remove_param')) {
    vc_remove_param('vc_row', 'full_width');
    vc_remove_param('vc_row', 'full_height');
    vc_remove_param('vc_row', 'gap');
    vc_remove_param('vc_row', 'columns_placement');
    vc_remove_param('vc_row', 'content_placement');
    vc_remove_param('vc_row', 'equal_height');
}

if (class_exists('WPBakeryShortCode')) {

    class WPBakeryShortCode_azexo_page_title extends WPBakeryShortCode {
        
    }

    class WPBakeryShortCode_azexo_search_form extends WPBakeryShortCode {
        
    }

    class WPBakeryShortCode_azexo_template_part extends WPBakeryShortCode {
        
    }

    class WPBakeryShortCode_azexo_dashboard_links extends WPBakeryShortCode {
        
    }

    class WPBakeryShortCode_azexo_post extends WPBakeryShortCode {
        
    }

    class WPBakeryShortCode_azexo_post_field extends WPBakeryShortCode {
        
    }

    class WPBakeryShortCode_azexo_posts_list extends WPBakeryShortCode {
        
    }
    class WPBakeryShortCode_azexo_breadcrumb extends WPBakeryShortCode {
        
    }

}

add_filter('vc_autocomplete_azexo_posts_list_include_callback', 'vc_include_field_search', 10, 1); // Get suggestion(find). Must return an array
add_filter('vc_autocomplete_azexo_posts_list_include_render', 'vc_include_field_render', 10, 1); // Render exact product. Must return an array (label,value)
add_filter('vc_autocomplete_azexo_posts_list_taxonomies_callback', 'vc_autocomplete_taxonomies_field_search', 10, 1);
add_filter('vc_autocomplete_azexo_posts_list_taxonomies_render', 'vc_autocomplete_taxonomies_field_render', 10, 1);
add_filter('vc_autocomplete_azexo_posts_list_exclude_callback', 'vc_exclude_field_search', 10, 1); // Get suggestion(find). Must return an array
add_filter('vc_autocomplete_azexo_posts_list_exclude_render', 'vc_exclude_field_render', 10, 1); // Render exact product. Must return an array (label,value)


azexo_add_element(array(
    "name" => "AZEXO - Page Title",
    "base" => "azexo_page_title",
    'category' => esc_html__('AZEXO', 'AZEXO'),
    "content_element" => true,
    "controls" => "full",
    "show_settings_on_create" => false
));


azexo_add_element(array(
    "name" => "AZEXO - Search Form",
    "base" => "azexo_search_form",
    'category' => esc_html__('AZEXO', 'AZEXO'),
    "content_element" => true,
    "controls" => "full",
    "show_settings_on_create" => false
));


$template_parts = array();
$files = scandir(get_template_directory() . '/template-parts');
if (is_array($files)) {
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $template_parts[$file] = $file;
        }
    }
}

azexo_add_element(array(
    "name" => "AZEXO - Template part",
    "base" => "azexo_template_part",
    'category' => esc_html__('AZEXO', 'AZEXO'),
    "content_element" => true,
    "controls" => "full",
    "show_settings_on_create" => true,
    'params' => array(
        array(
            'type' => 'dropdown',
            'heading' => esc_html__('Template part', 'AZEXO'),
            'param_name' => 'template_part',
            'value' => array_merge(array(esc_html__('None', 'AZEXO') => ''), array_flip($template_parts)),
            'admin_label' => true
        ),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Extra class name', 'AZEXO'),
            'param_name' => 'el_class',
            'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'AZEXO'),
        ),
        array(
            'type' => 'css_editor',
            'heading' => esc_html__('Css', 'AZEXO'),
            'param_name' => 'css',
            'group' => esc_html__('Design options', 'AZEXO'),
        ),
    )
));


azexo_add_element(array(
    'name' => esc_html__('AZEXO - dashboard links widget', 'AZEXO'),
    'base' => 'azexo_dashboard_links',
    'category' => esc_html__('AZEXO', 'AZEXO'),
    'description' => esc_html__('Displays the dashboard links', 'AZEXO'),
    'show_settings_on_create' => false,
    'params' => array(
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Widget title', 'AZEXO'),
            'param_name' => 'title',
            'description' => esc_html__('What text use as a widget title. Leave blank to use default widget title.', 'AZEXO'),
            'value' => esc_html__('Dashboard', 'AZEXO'),
        ),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Extra class name', 'AZEXO'),
            'param_name' => 'el_class',
            'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'AZEXO'),
        ),
    )
));

$post_types = get_post_types(array(), 'objects');
$post_types_list = array();
if (is_array($post_types) && !empty($post_types)) {
    foreach ($post_types as $slug => $post_type) {
        if ($slug !== 'revision' && $slug !== 'nav_menu_item'/* && $slug !== 'attachment' */) {
            $post_types_list[] = array($slug, $post_type->label);
        }
    }
}
$post_types_list[] = array('custom', esc_html__('Custom query', 'AZEXO'));
$post_types_list[] = array('ids', esc_html__('List of IDs', 'AZEXO'));

global $azexo_templates;
if (!isset($azexo_templates)) {
    $azexo_templates = array();
}
azexo_add_element(array(
    "name" => "AZEXO - Post",
    "base" => "azexo_post",
    'category' => esc_html__('AZEXO', 'AZEXO'),
    "content_element" => true,
    "controls" => "full",
    "show_settings_on_create" => true,
    'params' => array(
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Post ID', 'AZEXO'),
            'param_name' => 'post_id',
            'description' => esc_html__('Post ID', 'AZEXO'),
        ),
        array(
            'type' => 'dropdown',
            'heading' => esc_html__('Post type', 'AZEXO'),
            'param_name' => 'post_type',
            'value' => $post_types_list,
            'dependency' => array(
                'element' => 'post_id',
                'is_empty' => true,
            ),
        ),
        array(
            'type' => 'dropdown',
            'heading' => esc_html__('Post template', 'AZEXO'),
            'param_name' => 'template',
            'value' => array_merge(array(esc_html__('Default', 'AZEXO') => 'post'), array_flip($azexo_templates)),
            'description' => esc_html__('Post template.', 'AZEXO'),
            'admin_label' => true
        ),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Extra class name', 'AZEXO'),
            'param_name' => 'el_class',
            'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'AZEXO'),
        ),
        array(
            'type' => 'css_editor',
            'heading' => esc_html__('Css', 'AZEXO'),
            'param_name' => 'css',
            'group' => esc_html__('Design options', 'AZEXO'),
        ),
    )
));


global $azexo_fields;
if (!isset($azexo_fields)) {
    $azexo_fields = array();
}
azexo_add_element(array(
    "name" => "AZEXO - Post field",
    "base" => "azexo_post_field",
    'category' => esc_html__('AZEXO', 'AZEXO'),
    "content_element" => true,
    "controls" => "full",
    "show_settings_on_create" => true,
    'params' => array(
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Post ID', 'AZEXO'),
            'param_name' => 'post_id',
            'description' => esc_html__('Post ID', 'AZEXO'),
        ),
        array(
            'type' => 'dropdown',
            'heading' => esc_html__('Post field', 'AZEXO'),
            'param_name' => 'field',
            'value' => array_merge(array(esc_html__('None', 'AZEXO') => ''), array_flip($azexo_fields)),
            'description' => esc_html__('Post field.', 'AZEXO'),
            'admin_label' => true
        ),
        array(
            'type' => 'checkbox',
            'heading' => esc_html__('Without field wrapper?', 'AZEXO'),
            'param_name' => 'without_wrapper',
            'value' => array(esc_html__('Yes, please', 'AZEXO') => 'yes'),
        ),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Extra class name', 'AZEXO'),
            'param_name' => 'el_class',
            'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'AZEXO'),
        ),
        array(
            'type' => 'css_editor',
            'heading' => esc_html__('Css', 'AZEXO'),
            'param_name' => 'css',
            'group' => esc_html__('Design options', 'AZEXO'),
        ),
    )
));


$taxonomies = get_taxonomies(array(), 'objects');
$taxonomy_options = array();
foreach ($taxonomies as $slug => $taxonomy) {
    $taxonomy_options[$taxonomy->label] = $slug;
}

$loop_params = array(
    array(
        'type' => 'dropdown',
        'heading' => esc_html__('Data source', 'AZEXO'),
        'param_name' => 'post_type',
        'value' => $post_types_list,
        'description' => esc_html__('Select content type for your list.', 'AZEXO')
    ),
    array(
        'type' => 'autocomplete',
        'heading' => esc_html__('Include only', 'AZEXO'),
        'param_name' => 'include',
        'description' => esc_html__('Add posts, pages, etc. by title.', 'AZEXO'),
        'settings' => array(
            'multiple' => true,
            'sortable' => true,
            'groups' => true,
        ),
        'dependency' => array(
            'element' => 'post_type',
            'value' => array('ids'),
        ),
    ),
    // Custom query tab
    array(
        'type' => 'textarea_safe',
        'heading' => esc_html__('Custom query', 'AZEXO'),
        'param_name' => 'custom_query',
        'description' => wp_kses(__('Build custom query according to <a href="http://codex.wordpress.org/Function_Reference/query_posts">WordPress Codex</a>.', 'AZEXO'), array('a' => array('href' => array()))),
        'dependency' => array(
            'element' => 'post_type',
            'value' => array('custom'),
        ),
    ),
    array(
        'type' => 'autocomplete',
        'heading' => esc_html__('Narrow data source', 'AZEXO'),
        'param_name' => 'taxonomies',
        'settings' => array(
            'multiple' => true,
            // is multiple values allowed? default false
            // 'sortable' => true, // is values are sortable? default false
            'min_length' => 1,
            // min length to start search -> default 2
            // 'no_hide' => true, // In UI after select doesn't hide an select list, default false
            'groups' => true,
            // In UI show results grouped by groups, default false
            'unique_values' => true,
            // In UI show results except selected. NB! You should manually check values in backend, default false
            'display_inline' => true,
            // In UI show results inline view, default false (each value in own line)
            'delay' => 500,
            // delay for search. default 500
            'auto_focus' => true,
        // auto focus input, default true
        // 'values' => $taxonomies_list,
        ),
        'param_holder_class' => 'vc_not-for-custom',
        'description' => esc_html__('Enter categories, tags or custom taxonomies.', 'AZEXO'),
        'dependency' => array(
            'element' => 'post_type',
            'value_not_equal_to' => array('ids', 'custom'),
        ),
    ),
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Total items', 'AZEXO'),
        'param_name' => 'max_items',
        'value' => 10, // default value
        'param_holder_class' => 'vc_not-for-custom',
        'description' => esc_html__('Set max limit for items in list or enter -1 to display all (limited to 1000).', 'AZEXO'),
        'dependency' => array(
            'element' => 'post_type',
            'value_not_equal_to' => array('ids', 'custom'),
        ),
    ),
    // Data settings
    array(
        'type' => 'dropdown',
        'heading' => esc_html__('Order by', 'AZEXO'),
        'param_name' => 'orderby',
        'value' => array(
            esc_html__('Date', 'AZEXO') => 'date',
            esc_html__('Order by post ID', 'AZEXO') => 'ID',
            esc_html__('Author', 'AZEXO') => 'author',
            esc_html__('Title', 'AZEXO') => 'title',
            esc_html__('Last modified date', 'AZEXO') => 'modified',
            esc_html__('Post/page parent ID', 'AZEXO') => 'parent',
            esc_html__('Number of comments', 'AZEXO') => 'comment_count',
            esc_html__('Menu order/Page Order', 'AZEXO') => 'menu_order',
            esc_html__('Meta value', 'AZEXO') => 'meta_value',
            esc_html__('Meta value (numeric)', 'AZEXO') => 'meta_value_num',
            esc_html__('Random order', 'AZEXO') => 'rand',
        ),
        'description' => esc_html__('Select order type. If "Meta value" or "Meta value Number" is chosen then meta key is required.', 'AZEXO'),
        'group' => esc_html__('Data Settings', 'AZEXO'),
        'param_holder_class' => 'vc_grid-data-type-not-ids',
        'dependency' => array(
            'element' => 'post_type',
            'value_not_equal_to' => array('ids', 'custom'),
        ),
    ),
    array(
        'type' => 'dropdown',
        'heading' => esc_html__('Sorting', 'AZEXO'),
        'param_name' => 'order',
        'group' => esc_html__('Data Settings', 'AZEXO'),
        'value' => array(
            esc_html__('Descending', 'AZEXO') => 'DESC',
            esc_html__('Ascending', 'AZEXO') => 'ASC',
        ),
        'param_holder_class' => 'vc_grid-data-type-not-ids',
        'description' => esc_html__('Select sorting order.', 'AZEXO'),
        'dependency' => array(
            'element' => 'post_type',
            'value_not_equal_to' => array('ids', 'custom'),
        ),
    ),
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Meta key', 'AZEXO'),
        'param_name' => 'meta_key',
        'description' => esc_html__('Input meta key for list ordering.', 'AZEXO'),
        'group' => esc_html__('Data Settings', 'AZEXO'),
        'param_holder_class' => 'vc_grid-data-type-not-ids',
        'dependency' => array(
            'element' => 'orderby',
            'value' => array('meta_value', 'meta_value_num'),
        ),
    ),
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Offset', 'AZEXO'),
        'param_name' => 'offset',
        'description' => esc_html__('Number of list elements to displace or pass over.', 'AZEXO'),
        'group' => esc_html__('Data Settings', 'AZEXO'),
        'param_holder_class' => 'vc_grid-data-type-not-ids',
        'dependency' => array(
            'element' => 'post_type',
            'value_not_equal_to' => array('ids', 'custom'),
        ),
    ),
    array(
        'type' => 'autocomplete',
        'heading' => esc_html__('Exclude', 'AZEXO'),
        'param_name' => 'exclude',
        'description' => esc_html__('Exclude posts, pages, etc. by title.', 'AZEXO'),
        'group' => esc_html__('Data Settings', 'AZEXO'),
        'settings' => array(
            'multiple' => true,
        ),
        'param_holder_class' => 'vc_grid-data-type-not-ids',
        'dependency' => array(
            'element' => 'post_type',
            'value_not_equal_to' => array('ids', 'custom'),
            'callback' => 'vc_grid_exclude_dependency_callback',
        ),
    ),
);


azexo_add_element(array(
    "name" => "AZEXO - Posts List",
    "base" => "azexo_posts_list",
    'category' => esc_html__('AZEXO', 'AZEXO'),
    "content_element" => true,
    "controls" => "full",
    "show_settings_on_create" => true,
    'params' => array_merge($loop_params, array(
        array(
            'type' => 'textfield',
            'heading' => esc_html__('List title', 'AZEXO'),
            'param_name' => 'title',
            'description' => esc_html__('Enter text which will be used as title. Leave blank if no title is needed.', 'AZEXO'),
            'admin_label' => true
        ),
        array(
            'type' => 'dropdown',
            'heading' => esc_html__('Show filter by taxonomy', 'AZEXO'),
            'param_name' => 'filter',
            'value' => array_merge(array(esc_html__('Select', 'AZEXO') => ''), $taxonomy_options),
        ),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Group by meta value', 'AZEXO'),
            'param_name' => 'group',
            'description' => esc_html__('Enter meta key which will be used for grouping posts.', 'AZEXO'),
        ),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Posts clauses filter function name', 'AZEXO'),
            'param_name' => 'posts_clauses',
            'description' => esc_html__('Function which can alter WP_Query object.', 'AZEXO')
        ),
        array(
            'type' => 'checkbox',
            'heading' => esc_html__('Only content?', 'AZEXO'),
            'param_name' => 'only_content',
            'value' => array(esc_html__('Yes, please', 'AZEXO') => 'yes')
        ),
        array(
            'type' => 'dropdown',
            'heading' => esc_html__('Post template', 'AZEXO'),
            'param_name' => 'template',
            'value' => array_merge(array(esc_html__('Default', 'AZEXO') => 'post'), array_flip($azexo_templates)),
            'description' => esc_html__('Post template.', 'AZEXO'),
            'dependency' => array(
                'element' => 'only_content',
                'is_empty' => true,
            ),
        ),
        array(
            'type' => 'checkbox',
            'heading' => esc_html__('Wrap item by DIV?', 'AZEXO'),
            'param_name' => 'item_wrapper',
            'value' => array(esc_html__('Yes, please', 'AZEXO') => 'yes'),
            'dependency' => array(
                'element' => 'carousel',
                'is_empty' => true,
            )),
        array(
            'type' => 'checkbox',
            'heading' => esc_html__('Show as carousel?', 'AZEXO'),
            'param_name' => 'carousel',
            'value' => array(esc_html__('Yes, please', 'AZEXO') => 'yes')
        ),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Carousel stage padding', 'AZEXO'),
            'param_name' => 'carousel_stagePadding',
            'value' => '0',
            'dependency' => array(
                'element' => 'carousel',
                'not_empty' => true,
            )),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Item margin', 'AZEXO'),
            'param_name' => 'item_margin',
            'value' => '0',
            'dependency' => array(
                'element' => 'carousel',
                'not_empty' => true,
            )),
        array(
            'type' => 'checkbox',
            'heading' => esc_html__('Center item?', 'AZEXO'),
            'param_name' => 'center',
            'value' => array(esc_html__('Yes, please', 'AZEXO') => 'yes'),
            'dependency' => array(
                'element' => 'carousel',
                'not_empty' => true,
            )),
        array(
            'type' => 'checkbox',
            'heading' => esc_html__('Loop carousel?', 'AZEXO'),
            'param_name' => 'loop',
            'value' => array(esc_html__('Yes, please', 'AZEXO') => 'yes'),
            'dependency' => array(
                'element' => 'carousel',
                'not_empty' => true,
            )),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Posts per carousel item', 'AZEXO'),
            'param_name' => 'posts_per_item',
            'value' => '1',
            'dependency' => array(
                'element' => 'carousel',
                'not_empty' => true,
            )),
        array(
            'type' => 'checkbox',
            'heading' => esc_html__('Full width', 'AZEXO'),
            'param_name' => 'full_width',
            'value' => array(esc_html__('Yes, please', 'AZEXO') => 'yes'),
            'dependency' => array(
                'element' => 'carousel',
                'not_empty' => true,
            )),
        array(
            'type' => 'textfield',
            'heading' => esc_html__('Extra class name', 'AZEXO'),
            'param_name' => 'el_class',
            'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'AZEXO'),
        ),
        array(
            'type' => 'css_editor',
            'heading' => esc_html__('Css', 'AZEXO'),
            'param_name' => 'css',
            'group' => esc_html__('Design options', 'AZEXO'),
        ),
    ))
));

azexo_add_element(array(
    "name" => esc_html__('AZEXO Breadcrumb', 'AZEXO'),
    "base" => "azexo_breadcrumb",
    'category' => esc_html__('AZEXO', 'AZEXO'),
    'show_settings_on_create' => false,
));


$directory_iterator = new DirectoryIterator(get_template_directory() . '/framework/icons');
foreach ($directory_iterator as $fileInfo) {
    if ($fileInfo->isFile() && $fileInfo->getExtension() == 'php') {
        require_once($fileInfo->getPathname());
    }
}