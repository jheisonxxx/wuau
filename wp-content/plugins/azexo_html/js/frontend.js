(function($) {
    "use strict";
    var $window = $(window);
    var $body = $('body');
    window.azh = $.extend({}, window.azh);
    function fullWidthSection($wrapper) {
        $wrapper.find('[data-full-width="true"]').each(function(key, item) {
            var $el = $(this);
            var fixed = false;
            $el.parents().andSelf().each(function() {
                if ($(this).css('position') === 'fixed') {
                    fixed = true;
                    return false;
                }
            });
            if (!fixed) {
                var $el_full = $("<div></div>");
                $el.after($el_full);
                $el.css({
                    left: 0,
                    width: 0
                });
                var el_margin_left = parseInt($el.css("margin-left"), 10);
                var el_margin_right = parseInt($el.css("margin-right"), 10);
                var offset = 0 - $el_full.offset().left - el_margin_left;
                var width = $body.prop("clientWidth");
                var container_width = $el_full.width();
                if ($el.css({
                    position: "relative",
                    left: offset,
                    "box-sizing": "border-box",
                    width: width
                }), !$el.data("stretch-content")) {
                    var padding = -1 * offset;
                    if (padding < 0) {
                        padding = 0;
                    }
                    var paddingRight = width - padding - container_width + el_margin_left + el_margin_right;
                    if (paddingRight < 0) {
                        paddingRight = 0;
                    }
                    $el.css({
                        "padding-left": padding + "px",
                        "padding-right": paddingRight + "px"
                    });
                }
                $el.addClass('az-full-width');
                $el.animate({
                    opacity: 1
                }, 400);
                $el.trigger("az-full-width", {
                    container_width: container_width
                });
                $window.trigger("az-full-width", {
                    element: $el,
                    container_width: container_width
                });
                $el.find('.az-container').css('width', container_width);
                $el_full.remove();
            }
        });
        $wrapper.find('[data-full-width="false"]').each(function(key, item) {
            var $el = $(this);
            var $el_full = $("<div></div>");
            $el.after($el_full);
            var container_width = $el_full.width();
            $el.find('.az-container').css('width', container_width);
            $el_full.remove();
            $el.css({
                visibility: "visible",
                opacity: 1
            });
        });
    }
    window.azh.frontend_init = function($wrapper) {
        $window.trigger("az-frontend-before-init", {
            wrapper: $wrapper
        });
        fullWidthSection($wrapper);
        if ('tabs' in $.fn) {
            $wrapper.find('.azexo-tabs').each(function() {
                var $this = $(this);
                if (!$this.tabs('instance')) {
                    $this.tabs();
                }
            });
        }
        if ('accordion' in $.fn) {
            $wrapper.find('.azexo-accordion').each(function() {
                var $this = $(this);
                if (!$this.accordion('instance')) {
                    $this.accordion({
                        header: ".accordion-section > h3",
                        autoHeight: false,
                        heightStyle: "content",
                        active: $this.data('active-section'),
                        collapsible: $this.data('collapsible'),
                        navigation: true,
                        animate: 200
                    });
                }
            });
        }
        $window.trigger("az-frontend-init", {
            wrapper: $wrapper
        });
        $window.trigger("az-frontend-after-init", {
            wrapper: $wrapper
        });
    };
    $(function() {
        $window.off("resize.az-fullWidthSection").on("resize.az-fullWidthSection", function() {
            fullWidthSection($body);
        });
        azh.frontend_init($body);
    });
})(jQuery);