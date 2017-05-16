(function($) {
    $(function() {
        function edit_links_refresh() {
            function show_edit_link(element) {
                $($(element).data('edit-link-control')).css({
                    "top": $(element).offset().top,
                    "left": $(element).offset().left,
                    "width": $(element).outerWidth(),
                    "height": $(element).outerHeight(),
                }).show();
            }
            function hide_edit_link(element) {
                $($(element).data('edit-link-control')).hide();
            }
            function is_visible(element) {
                var visible = true;
                if ($(window).width() < $(element).offset().left + $(element).outerWidth()) {
                    visible = false;
                }
                if (!$(element).is(":visible")) {
                    visible = false;
                }
                $(element).parents().each(function() {
                    var parent = this;

                    var elements = $(parent).data('elements-with-vc-widget');
                    if (!elements) {
                        elements = [];
                    }
                    elements = elements.concat($(element).get());
                    elements = $.unique(elements);
                    $(parent).data('elements-with-vc-widget', elements);

                    if ($(parent).css("display") == 'none' || $(parent).css("opacity") == '0' || $(parent).css("visibility") == 'hidden') {
                        visible = false;
                        $(parent).off('click.vc-widgets mouseenter.vc-widgets mouseleave.vc-widgets').on('click.vc-widgets mouseenter.vc-widgets mouseleave.vc-widgets', function() {
                            var elements = $(parent).data('elements-with-vc-widget');
                            $(elements).each(function() {
                                if (is_visible(this)) {
                                    show_edit_link(this);
                                } else {
                                    hide_edit_link(this);
                                }
                            });
                        });
                    }
                });
                return visible;
            }
            for (var id in vc_widgets.edit) {
                $('#' + id).each(function() {
                    if (!$(this).data('edit-link-control')) {
                        var control = $('<div class="vc-edit-link"><a href="' + vc_widgets.edit[id] + '" target="_blank">' + vc_widgets.edit_button + '</a></div>').appendTo('body').hide().css({
                            "top": "0",
                            "left": "0",
                            "width": "0",
                            "height": "0",
                            "z-index": "9999999",
                            "pointer-events": "none",
                            "position": "absolute"
                        });
                        control.find('a').css({
                            "display": "inline-block",
                            "padding": "5px 10px",
                            "color": "black",
                            "font-weight": "bold",
                            "background-color": "white",
                            "box-shadow": "0px 5px 5px rgba(0, 0, 0, 0.1)",
                            "pointer-events": "all"
                        }).on('mouseenter', function() {
                            $(this).parent().css("background-color", "rgba(0, 255, 0, 0.1)");
                        }).on('mouseleave', function() {
                            $(this).parent().css("background-color", "transparent");
                        });
                        $(this).data('edit-link-control', control);
                    }
                    if (is_visible(this)) {
                        show_edit_link(this);
                    } else {
                        hide_edit_link(this);
                    }
                });
            }
        }
        if ('vc_widgets' in window && 'edit' in vc_widgets) {
            $(window).on('resize.vc-widgets scroll.vc-widgets', _.throttle(function() {
                edit_links_refresh();
            }, 1000));
            setTimeout(function() {
                edit_links_refresh();
            }, 100);
        }
        $('#wp-admin-bar-edit-links').addClass('vc-edit-links-active');
        $('#wp-admin-bar-edit-links').off('click.vc-widgets').on('click.vc-widgets', function(event) {
            if ($(this).is('.vc-edit-links-active')) {
                $('body > div.vc-edit-link[style] > a[href][style][target]').each(function() {
                    if ($(this).is(':visible')) {
                        $(this).data('visible', true);
                        $(this).hide();
                    }
                });
                $('body > div.vc-edit-link[style] > a[href][style][target]').hide();
                $(this).removeClass('vc-edit-links-active');
                $(this).css('opacity', '0.4');
                $(window).off('resize.vc-widgets scroll.vc-widgets');
            } else {
                $('body > div.vc-edit-link[style] > a[href][style][target]').each(function() {
                    if ($(this).data('visible')) {
                        $(this).show();
                    }
                });
                $(this).addClass('vc-edit-links-active');
                $(this).css('opacity', '1');
                $(window).on('resize.vc-widgets scroll.vc-widgets', function() {
                    edit_links_refresh();
                });
            }
            event.preventDefault();
        });

    });
})(window.jQuery);