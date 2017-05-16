<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <title><?php esc_html_e('AZEXO HTML Library', 'azh'); ?></title>
        <meta charset="<?php bloginfo('charset'); ?>">
        <?php
        global $azh_content;
        $azh_content = false;

        if (isset($_GET['files'])) {
            $library = azh_get_library();
            $all_settings = azh_get_all_settings();
            $files = explode('|', $_GET['files']);
            foreach ($files as $file) {
                if (is_array($library['elements'])) {
                    foreach ($library['elements'] as $element_file => $name) {
                        if (strlen($element_file) - strlen($file) == strrpos($element_file, $file)) {
                            $element_width = '100%';
                            foreach ($all_settings as $dir => $settings) {
                                if (strpos($element_file, $dir) == 0) {
                                    if (isset($settings['elements-widths'])) {
                                        foreach ($settings['elements-widths'] as $element => $width) {
                                            if ($element_file == $dir . '/' . $element) {
                                                $element_width = $width;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            $azh_content .= '<div data-element="' . esc_attr(ltrim(str_replace($library['elements_dir'][$element_file], '', $element_file), '/')) . '" style="margin: auto; max-width: ' . $element_width . '">';
                            $azh_content .= azh_get_file($element_file, $library['elements_uri'][$element_file]);
                            $azh_content .= '</div>';
                            break;
                        }
                    }
                }
                if (is_array($library['sections'])) {
                    foreach ($library['sections'] as $section_file => $name) {
                        if (strlen($section_file) - strlen($file) == strrpos($section_file, $file)) {
                            $azh_content .= '<div data-section="' . esc_attr(ltrim(str_replace($library['sections_dir'][$section_file], '', $section_file), '/')) . '">';
                            $azh_content .= azh_get_file($section_file, $library['sections_uri'][$section_file]);
                            $azh_content .= '</div>';
                            break;
                        }
                    }
                }
            }
        } else {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-resizable');
            wp_enqueue_script('jquery-ui-draggable');            
            wp_enqueue_script('imagesloaded');
            wp_enqueue_script('isotope');
            wp_enqueue_script('waypoints');
            wp_enqueue_style('azexo_html_library', plugins_url('css/azexo_html_library.css', __FILE__));
            wp_enqueue_script('azexo_html_library', plugins_url('js/azexo_html_library.js', __FILE__), array('jquery'), true);
            azh_icon_font_enqueue('fontawesome');
            wp_localize_script('azexo_html_library', 'azh', azh_get_object());
        }
        wp_head();
        ?>
        <style>
            .azh-center {
                margin-top: 50vh; 
                transform: translate(0, -50%);
            }
        </style>
    </head>    
    <body>          
        <div class="az-container">
            <?php if ($azh_content): ?>
                <div class="azh-content <?php (isset($_GET['files']) && count(explode('|', $_GET['files'])) == 1) ? 'azh-center' : '' ?>">
                    <?php print $azh_content; ?>
                </div>
                <script>
                    (function($) {
                        $(function() {
                            $(window).on('resize', function() {
                                if ($(window).height() < $('.azh-content').height()) {
                                    $('.azh-content').removeClass('azh-center');
                                } else {
                                    $('.azh-content').addClass('azh-center');
                                }
                            });
                            setTimeout(function() {
                                $(window).trigger('resize');
                            });
                            
                        });
                    })(window.jQuery);
                </script>
            <?php else: ?>                
                <div id="azexo-html-library">
                    <input id="sections" type="radio" name="sections-elements" checked="" style="position: absolute; clip: rect(0, 0, 0, 0);">
                    <input id="elements" type="radio" name="sections-elements" style="position: absolute; clip: rect(0, 0, 0, 0);">
                    <div class="sections-elements">                        
                        <label for="sections"><?php esc_html_e('Sections', 'azh'); ?></label>
                        <label for="elements"><?php esc_html_e('Elements', 'azh'); ?></label>
                    </div>
                    <?php azh_meta_box(); ?>
                </div>
            <?php endif; ?>        
        </div>
        <?php wp_footer(); ?>
    </body>
</html>