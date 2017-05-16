(function($) {
    "use strict";
    var $window = $(window);
    var $body = $('body');
    var $document = $(document);
    azh.parse_query_string = function(a) {
        if (a == "")
            return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
            var p = a[i].split('=');
            if (p.length != 2)
                continue;
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
    };
    $.QueryString = azh.parse_query_string(window.location.search.substr(1).split('&'));
    $(function() {
        function makeid() {
            var text = "";
            var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 5; i++)
                text += possible.charAt(Math.floor(Math.random() * possible.length));
            return text;
        }
        function remove_azh_meta() {
            if ($('body.post-type-page').length) {
                $('#list-table input[value="azh"]').each(function() {
                    $(this).parent().find('input.deletemeta').click();
                });
            }
        }
        function add_azh_meta() {
            if ($('body.post-type-page').length) {
                if ($('#list-table input[value="azh"]').closest('tr[id]:visible').length == 0) {
                    $('#metakeyinput').val('azh');
                    $('#metavalue').val('azh');
                    $('#newmeta-submit').click();
                }
            }
        }
        azh.azh_show_hide = function() {
            setTimeout(function() {
                if ($('#wp-content-wrap, #wp-customize-posts-content-wrap').is('.tmce-active')) {
                    $('#wp-content-editor-container #content, #wp-customize-posts-content-editor-container #customize-posts-content').show();
                }
            }, 0);
            setTimeout(function() {
                if ($('#wp-content-wrap, #wp-customize-posts-content-wrap').is('.html-active')) {
                    if (azh.edit) {
                        $('#wp-content-editor-container .azh-switcher, #wp-customize-posts-content-editor-container .azh-switcher').css('left', '0');
                        //$('#wp-content-editor-container .azh-switcher, #wp-customize-posts-content-editor-container .azh-switcher').show();
                        $('#wp-content-editor-container .azexo-html-editor, #wp-customize-posts-content-editor-container .azexo-html-editor').show();
                        $('#wp-content-editor-container #content, #wp-customize-posts-content-editor-container #customize-posts-content').hide();
                        $('#ed_toolbar, #qt_customize-posts-content_toolbar').hide();
                        $('#wp-content-media-buttons, #wp-customize-posts-content-media-buttons').hide();
                        add_azh_meta();
                    } else {
                        $('#wp-content-editor-container .azh-switcher, #wp-customize-posts-content-editor-container .azh-switcher').css('left', '110px');
                        $('#wp-content-editor-container .azexo-html-editor, #wp-customize-posts-content-editor-container .azexo-html-editor').hide();
                        $('#wp-content-editor-container #content, #wp-customize-posts-content-editor-container #customize-posts-content').show();
                        $('#ed_toolbar, #qt_customize-posts-content_toolbar').show();
                        $('#wp-content-media-buttons, #wp-customize-posts-content-media-buttons').show();
                        remove_azh_meta();
                    }
                } else {
                    $('#wp-content-editor-container .azh-switcher, #wp-customize-posts-content-editor-container .azh-switcher').css('left', '110px');
                    $('#wp-content-editor-container .azexo-html-editor, #wp-customize-posts-content-editor-container .azexo-html-editor').hide();
                    $('#wp-content-editor-container .mce-tinymce, #wp-customize-posts-content-editor-container .mce-tinymce').show();
                    $('#ed_toolbar, #qt_customize-posts-content_toolbar').show();
                    $('#wp-content-media-buttons, #wp-customize-posts-content-media-buttons').show();
                    remove_azh_meta();
                }
            }, 0);
        }
        window.azh = $.extend({}, window.azh);
        azh.icon_select_dialog = function(callback, type) {
            function show_icons() {
                var keyword = $search.val().toLowerCase();
                $icons.empty();
                for (var key in azh.icons[type]) {
                    if (azh.icons[type][key].toLowerCase().indexOf(keyword) >= 0) {
                        $('<span class="' + key + '"></span>').appendTo($icons).on('click', {icon: icon}, function(event) {
                            callback.call(event.data.icon, $(this).attr('class'));
                        });
                    }
                }
            }
            var icon = this;
            var icon_class = '';
            var $dialog = $('<div class="azh-icon-select-dialog"></div>').appendTo('body');
            var $controls = $('<div class="type-search"></div>').appendTo($dialog);
            var $search = $('<input type="text"/>').appendTo($controls).on('change keyup', function() {
                show_icons();
            });
            var $icons = $('<div class="azh-icons"></div>').appendTo($dialog);
            show_icons();
            return $dialog;
        };
        azh.open_icon_select_dialog = function(event, icon_class, callback) {
            function show_icons() {
                var type = $types.find('option:selected').val();
                var keyword = $(search).val().toLowerCase();
                $icons.empty();
                for (var key in azh.icons[type]) {
                    if (azh.icons[type][key].toLowerCase().indexOf(keyword) >= 0) {
                        $('<span class="' + key + '"></span>').appendTo($icons).on('click', {icon: icon}, function(event) {
                            $dialog.remove();
                            $backdrop.remove();
                            $document.off('click.azh-dialog');
                            if ('post_edit_frame' in azh) {
                                azh.post_edit_frame.hide();
                            }
                            callback.call(event.data.icon, $(this).attr('class'));
                        });
                    }
                }
            }
            var current_type = false;
            for (var type in azh.icons) {
                var pattern = new RegExp('(' + Object.keys(azh.icons[type]).join('|') + ')', 'i');
                var match = pattern.exec(icon_class);
                if (match) {
                    current_type = type;
                    break;
                }
            }
            var $dialog_body = $body;
            var $dialog_document = $document;
            var $dialog_window = $window;
            if ('post_edit_frame' in azh) {
                if (azh.post_edit_frame) {
                    $dialog_body = azh.post_edit_frame.contents().find('body');
                    $dialog_window = $(azh.post_edit_frame.get(0).contentWindow);
                    $dialog_document = $(azh.post_edit_frame.get(0).contentDocument || azh.post_edit_frame.get(0).contentWindow.document);
                    $dialog_body.find('> *').hide();
                    $dialog_body.find('#wpwrap > *').hide();
                    $dialog_body.find('#wpwrap').show();
                    $dialog_body.css('background-color', 'transparent');
                    azh.post_edit_frame.show();
                } else {
                    alert('');
                    return;
                }
            }
            var dialog_window = $dialog_window.get(0);

            var icon = this;
            $dialog_body.find('.azh-icon-select-dialog').remove();
            $dialog_body.find('.azh-backdrop').remove();
            var $backdrop = $('<div class="azh-backdrop"></div>').appendTo($dialog_body);
            var $dialog = $('<div class="azh-icon-select-dialog"></div>').appendTo($dialog_body);
            var $controls = $('<div class="type-search"></div>').appendTo($dialog);
            var $types = $('<select></select>').appendTo($controls).on('change', function() {
                show_icons();
            });
            var search = $('<input type="text"/>').appendTo($controls).on('change keyup', function() {
                show_icons();
            });
            for (var type in azh.icons) {
                var option = $('<option value="' + type + '">' + type + '</option>').appendTo($types);
            }
            var $icons = $('<div class="azh-icons"></div>').appendTo($dialog);
            $types.val(current_type);
            show_icons();
            $document.on('click.azh-dialog', {icon: icon}, function(event) {
                if (!$(event.target).closest('.azh-icon-select-dialog').length) {
                    $dialog.remove();
                    $backdrop.remove();
                    $document.off('click.azh-dialog');
                    if ('post_edit_frame' in azh) {
                        azh.post_edit_frame.hide();
                    }
                    callback.call(event.data.icon, icon_class);
                }
            });
            event.stopPropagation();
        };
        azh.get_image_url = function(id, callback) {
            var attachment = wp.media.model.Attachment.get(id);
            attachment.fetch().done(function() {
                callback(attachment.attributes.url);
            });
            ;
        };
        azh.open_image_select_dialog = function(event, callback, multiple) {
            var $dialog_body = $body;
            var $dialog_document = $document;
            var $dialog_window = $window;
            if ('post_edit_frame' in azh) {
                if (azh.post_edit_frame) {
                    $dialog_body = azh.post_edit_frame.contents().find('body');
                    $dialog_window = $(azh.post_edit_frame.get(0).contentWindow);
                    $dialog_document = $(azh.post_edit_frame.get(0).contentDocument || azh.post_edit_frame.get(0).contentWindow.document);
                    $dialog_body.find('> *').hide();
                    $dialog_body.find('#wpwrap > *').hide();
                    $dialog_body.find('#wpwrap').show();
                    $dialog_body.css('background-color', 'transparent');
                    azh.post_edit_frame.show();
                } else {
                    alert('');
                    return;
                }
            }
            var dialog_window = $dialog_window.get(0);

            var image = this;
            multiple = (typeof multiple == 'undefined' ? false : multiple);
            // check for media manager instance
            if (dialog_window.wp.media.frames.azh_frame) {
                dialog_window.wp.media.frames.azh_frame.image = image;
                dialog_window.wp.media.frames.azh_frame.callback = callback;
                dialog_window.wp.media.frames.azh_frame.options.multiple = multiple;
                dialog_window.wp.media.frames.azh_frame.open();
                return;
            }
            // configuration of the media manager new instance            
            dialog_window.wp.media.frames.azh_frame = dialog_window.wp.media({
                multiple: multiple,
                library: {
                    type: 'image'
                }
            });
            dialog_window.wp.media.frames.azh_frame.image = image;
            dialog_window.wp.media.frames.azh_frame.callback = callback;
            // Function used for the image selection and media manager closing            
            var azh_media_set_image = function() {
                var selection = dialog_window.wp.media.frames.azh_frame.state().get('selection');
                // no selection
                if (!selection) {
                    return;
                }
                // iterate through selected elements
                if (dialog_window.wp.media.frames.azh_frame.options.multiple) {
                    dialog_window.wp.media.frames.azh_frame.callback.call(dialog_window.wp.media.frames.azh_frame.image, selection.map(function(attachment) {
                        return {url: attachment.attributes.url, id: attachment.attributes.id};
                    }));
                } else {
                    selection.each(function(attachment) {
                        dialog_window.wp.media.frames.azh_frame.callback.call(dialog_window.wp.media.frames.azh_frame.image, attachment.attributes.url, attachment.attributes.id);
                    });
                }
            };
//            if (selected) {
//                dialog_window.wp.media.frames.azh_frame.on('open', function() {
//                    var selection = dialog_window.wp.media.frames.azh_frame.state().get('selection');
//                    if (selection) {
//                        $(selected).each(function() {
//                            selection.add(dialog_window.wp.media.attachment(this));
//                        });
//                    }
//                });
//            }
            // closing event for media manger
            dialog_window.wp.media.frames.azh_frame.on('close', function() {
                if ('post_edit_frame' in azh) {
                    azh.post_edit_frame.hide();
                }
            });
            // image selection event
            dialog_window.wp.media.frames.azh_frame.on('select', azh_media_set_image);
            // showing media manager
            dialog_window.wp.media.frames.azh_frame.open();
        }
        azh.open_link_select_dialog = function(event, callback, url, target, text) {
            var $dialog_body = $body;
            var $dialog_document = $document;
            var $dialog_window = $window;
            if ('post_edit_frame' in azh) {
                if (azh.post_edit_frame) {
                    $dialog_body = azh.post_edit_frame.contents().find('body');
                    $dialog_window = $(azh.post_edit_frame.get(0).contentWindow);
                    $dialog_document = $(azh.post_edit_frame.get(0).contentDocument || azh.post_edit_frame.get(0).contentWindow.document);
                    $dialog_body.find('> *').hide();
                    $dialog_body.find('#wpwrap > *').hide();
                    $dialog_body.find('#wpwrap').show();
                    $dialog_body.css('background-color', 'transparent');
                    azh.post_edit_frame.show();
                } else {
                    alert('');
                    return;
                }
            }
            var dialog_window = $dialog_window.get(0);
            url = (typeof url == 'undefined' ? '' : url);
            target = (typeof target == 'undefined' ? '' : target);
            text = (typeof text == 'undefined' ? '' : text);
            var link = this;
            if ($(link).data('url')) {
                url = $(link).data('url');
            }
            var original = dialog_window.wpLink.htmlUpdate;
            $document.on('wplink-close.azh', function() {
                if ('post_edit_frame' in azh) {
                    azh.post_edit_frame.hide();
                }
                dialog_window.wpLink.htmlUpdate = original;
                $dialog_body.find('#wp-link-cancel').off('click.azh');
                $(input).remove();
                $document.off('wplink-close.azh');
            });
            dialog_window.wpLink.htmlUpdate = function() {
                var attrs = dialog_window.wpLink.getAttrs();
                if (!attrs.href) {
                    return;
                }
                callback.call(link, attrs.href, attrs.target, $dialog_body.find('#wp-link-text').val());
                dialog_window.wpLink.close('noReset');
            };
            $dialog_body.find('#wp-link-cancel').on('click.azh', function(event) {
                dialog_window.wpLink.close('noReset');
                event.preventDefault ? event.preventDefault() : event.returnValue = false;
                event.stopPropagation();
                return false;
            });
            dialog_window.wpActiveEditor = true;
            var id = makeid();
            var input = $('<input id="' + id + '" />').appendTo($dialog_body).hide();
            dialog_window.wpLink.open(id);
            $dialog_body.find('#wp-link-url').val(url);
            $dialog_body.find('#wp-link-target').val(target);
            $dialog_body.find('#wp-link-text').val(text);
        };
        azh.get_rich_text_editor = function(textarea) {
            function init_textarea_html($element) {
                var $wp_link = $("#wp-link");
                $wp_link.parent().hasClass("wp-dialog") && $wp_link.wpdialog("destroy");
                $element.val($(textarea).val());
                try {
                    _.isUndefined(tinyMCEPreInit.qtInit[textfield_id]) && (window.tinyMCEPreInit.qtInit[textfield_id] = _.extend({}, window.tinyMCEPreInit.qtInit[window.wpActiveEditor], {
                        id: textfield_id
                    }));
                    window.tinyMCEPreInit && window.tinyMCEPreInit.mceInit[window.wpActiveEditor] && (window.tinyMCEPreInit.mceInit[textfield_id] = _.extend({}, window.tinyMCEPreInit.mceInit[window.wpActiveEditor], {
                        resize: "vertical",
                        height: 200,
                        id: textfield_id,
                        setup: function(ed) {
                            "undefined" != typeof ed.on ? ed.on("init", function(ed) {
                                ed.target.focus(), window.wpActiveEditor = textfield_id
                            }) : ed.onInit.add(function(ed) {
                                ed.focus(), window.wpActiveEditor = textfield_id
                            })
                            ed.on('change', function(e) {
                                $(textarea).val(ed.getContent());
                                $(textarea).trigger('change');
                            });
                        }
                    }), window.tinyMCEPreInit.mceInit[textfield_id].plugins = window.tinyMCEPreInit.mceInit[textfield_id].plugins.replace(/,?wpfullscreen/, ""), window.tinyMCEPreInit.mceInit[textfield_id].wp_autoresize_on = !1);
                    quicktags(window.tinyMCEPreInit.qtInit[textfield_id]);
                    QTags._buttonsInit();
                    window.tinymce && (window.switchEditors && window.switchEditors.go(textfield_id, "tmce"), "4" === tinymce.majorVersion && tinymce.execCommand("mceAddEditor", !0, textfield_id));
                    window.wpActiveEditor = textfield_id
                    setUserSetting('editor', 'html');
                } catch (e) {
                }
            }
            var textfield_id = makeid();
            $.ajax({
                type: 'POST',
                url: window.ajaxurl,
                data: {
                    action: 'azh_get_wp_editor',
                    id: textfield_id,
                },
                cache: false,
            }).done(function(data) {
                $(textarea).hide();
                $(textarea).after(data);
                init_textarea_html($('#' + textfield_id));
                $('#' + textfield_id).on('change', function() {
                    $(textarea).val($(this).val());
                    $(textarea).trigger('change');
                });
            });
        }
        if (window === window.parent) {
            if ($('#wp-content-editor-container #content').length) {
                var edit = true;
                if ($('body.post-type-azh_widget').length == 0) {
                    if ($('#list-table input[value="azh"]').length == 0) {
                        edit = false;
                    }
                }
                if ($('#azh').length && $('#wp-content-wrap, #wp-customize-posts-content-wrap').is('.html-active')) {
                    edit = true;
                }
                azh.init($('#wp-content-editor-container #content'), edit);
                azh.azh_show_hide();
                if ($('#wp-content-wrap, #wp-customize-posts-content-wrap').is('.tmce-active')) {
                    $('.azh-switcher').text(azh.switch_to_customizer);
                }
                $('#wp-content-editor-container .azh-switcher').on('click', function() {
                    if ($('#wp-content-wrap, #wp-customize-posts-content-wrap').is('.tmce-active')) {
                        $('#content-html').click();
                    } else {
                        if ($('#wp-content-wrap, #wp-customize-posts-content-wrap').is('.html-active')) {
                            azh.azh_show_hide();
                        }
                    }
                });
                $('#content-tmce').on('click', azh.azh_show_hide);
                $('#content-html').on('click', azh.azh_show_hide);
            }
            if ('post' in $.QueryString && 'action' in $.QueryString && 'occurrence' in $.QueryString && ('section' in $.QueryString || 'element' in $.QueryString)) {
                $(document).one('azh-store', function() {
                    setTimeout(function() {
                        var occurrence = 0;
                        if ('section' in $.QueryString) {
                            $('.azh-group-title:contains("' + $.QueryString['section'] + '")').each(function() {
                                if ($(this).text() == $.QueryString['section'] && $.QueryString['occurrence'] == occurrence.toString()) {
                                    var section = $(this).closest('.azh-section');
                                    if ($(section).length) {
                                        $('body, html').stop().animate({
                                            'scrollTop': $(section).offset().top - $(window).height() / 2 + $(section).height() / 2
                                        }, 300);
                                        setTimeout(function() {
                                            $('<div class="azh-overlay"></div>').appendTo('body');
                                            azh.focus('.azh-overlay', 0);
                                            setTimeout(function() {
                                                $('.azh-overlay').remove();
                                                azh.focus(section, 300);
                                            }, 0);
                                        }, 300);
                                    }
                                }
                                occurrence++;
                            });
                        }
                        if ('element' in $.QueryString) {
                            $('.azh-element-title:contains("' + $.QueryString['element'] + '")').each(function() {
                                if ($(this).text() == $.QueryString['element'] && $.QueryString['occurrence'] == occurrence.toString()) {
                                    var element = $(this).closest('.azh-element-wrapper');
                                    $(element).parents('.azh-section-collapsed').each(function() {
                                        $(this).find('> .azh-controls .azh-section-expand').click();
                                    });
                                    $(element).parents('.azh-element-collapsed').each(function() {
                                        $(this).find('> .azh-controls .azh-element-expand').click();
                                    });
                                    if ($(element).length) {
                                        $('body, html').stop().animate({
                                            'scrollTop': $(element).offset().top - $(window).height() / 2 + $(element).height() / 2
                                        }, 300);
                                        setTimeout(function() {
                                            $('<div class="azh-overlay"></div>').appendTo('body');
                                            azh.focus('.azh-overlay', 0);
                                            setTimeout(function() {
                                                $('.azh-overlay').remove();
                                                azh.focus(element, 300);
                                            }, 0);
                                        }, 300);
                                    }
                                }
                                occurrence++;
                            });
                        }
                    }, 100);
                });
            }
            if ($("#postbox-container-1").length && $('body.post-type-page').length &&  $('#list-table input[value="azh"]').length) {
                $('<a href="' + azh.edit_post_frontend_link + '" class="azh-frontend-builder">' + azh.i18n.edit_frontend_builder + '</a>').prependTo("#postbox-container-1");
            }
        }
    });
    if ('azh' in $.QueryString && $.QueryString['azh'] == 'customize' && azh.edit_post_link !== '') {
        azh.post_edit_frame = false;
        $(window).on('load', function() {
            $body = $('body');
            azh.post_edit_frame = $('<iframe src="' + azh.edit_post_link + '"></iframe>').appendTo($body);
            azh.post_edit_frame.css('border', '0');
            azh.post_edit_frame.css('position', 'fixed');
            azh.post_edit_frame.css('left', '0');
            azh.post_edit_frame.css('top', '0');
            azh.post_edit_frame.css('z-index', '9999999');
            azh.post_edit_frame.css('height', '100%');
            azh.post_edit_frame.css('width', '100%');
            azh.post_edit_frame.hide();
        });
    }
    if (window !== window.parent) {
        $(window.document).on('click', function(event, data) {
            window.parent.jQuery(window.parent.document).trigger(event, data);
        });
        $(window.document).on('wplink-close', function(event, data) {
            window.parent.jQuery(window.parent.document).trigger(event, data);
        });
    }
})(window.jQuery);