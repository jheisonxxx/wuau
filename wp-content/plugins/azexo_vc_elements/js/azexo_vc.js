(function($) {
    "use strict";

    function initAzexoCarousel() {
        if ('owlCarousel' in $.fn) {
            $('.carousel-wrapper .carousel').each(function() {
                var carousel = this;
                if ($(carousel).data('owlCarousel') == undefined) {

                    while ($(carousel).find('> div:not(.item)').length) {
                        $(carousel).find('> div:not(.item)').slice(0, $(carousel).data('contents-per-item')).wrapAll('<div class="item" />');
                    }

                    var r = $(carousel).data('responsive');
                    if (typeof r !== 'object') {
                        r = window[r];
                    }
                    $(carousel).show();
                    var hold = false;
                    $(carousel).owlCarousel({
                        responsive: r,
                        center: ($(carousel).data('center') == 'yes'),
                        margin: $(carousel).data('margin'),
                        loop: ($(carousel).data('loop') == 'yes'),
                        autoplay: ($(carousel).data('autoplay') == 'yes'),
                        autoplayHoverPause: true,
                        nav: true,
                        navText: ['', '']
                    }).on('translate.owl.carousel', function(event) {
                        if (!hold) {
                            var item = $(carousel).data('owlCarousel')._items[event.item.index];
                            $(item).find('.triggerable.active').click();
                        }
                    }).on('translated.owl.carousel', function(event) {
                        if (!hold) {
                            var item = $(carousel).data('owlCarousel')._items[event.item.index];
                            $(item).find('.triggerable:not(.active)').click();
                        }
                        try {
                            BackgroundCheck.refresh($(carousel).find('.owl-controls .owl-prev, .owl-controls .owl-next'));
                        } catch (e) {
                        }
                    });
                    setTimeout(function() {
                        var item = $(carousel).data('owlCarousel')._items[$(carousel).data('owlCarousel')._current];
                        $(item).find('.triggerable').click();

                        $(carousel).find('.triggerable').on('click', function() {
                            hold = true;
                            var item = $(this).closest('.owl-item');
                            var index = $(carousel).find('.owl-item').index(item);
                            $(carousel).data('owlCarousel').to($(carousel).data('owlCarousel').relative(index));
                            hold = false;
                        });
                    }, 0);
                    try {
                        BackgroundCheck.init({
                            targets: $(carousel).find('.owl-controls .owl-prev, .owl-controls .owl-next'),
                            images: $(carousel).find('.item .image')
                        });
                    } catch (e) {
                    }
                }
            });
        }
    }
    function initAzexoFilters() {
        $('.filters-wrapper > .filters-header > .filters > .filter').on('click', function() {
            $(this).closest('.filters').find('.filter').removeClass('active');
            $(this).closest('.filters').find('.filter').each(function() {
                if ($(this).data('selector')) {
                    $(this).closest('.filters-wrapper').find('> .filterable ' + $(this).data('selector')).removeClass('showed');
                }
            });
            $(this).addClass('active');
            if ($(this).data('selector')) {
                $(this).closest('.filters-wrapper').find('> .filterable ' + $(this).data('selector')).addClass('showed');
            }
            if ('masonry' in $.fn) {
                var container = $(this).closest('.filters-wrapper').find('> .filterable');
                if ($(container).is('.masonry')) {
                    //$(container).masonry('layout');
                    $(container).data('masonry')._resetLayout();
                    var items = $(container).data('masonry')._itemize($(container).find('> .showed'));
                    $(container).masonry('layoutItems', items, false);
                    $(container).masonry('once', 'layoutComplete', function(event) {
                        console.log(event);
                    });
                }
            }
        });
        $('.filters-wrapper > .filters-header > .filters > .filter').each(function() {
            if ($(this).data('selector')) {
                $(this).closest('.filters-wrapper').find('> .filterable ' + $(this).data('selector')).addClass('showed');
            }
        });
    }
    function initMasonry() {
        if ('masonry' in $.fn) {
            $('.masonry').each(function() {
                var container = this;
                var containerWidth = $(container).width();

                var r = $(container).data('responsive');
                if (typeof r !== 'object') {
                    r = window[r];
                }
                var items = 0;
                for (var width in r) {
                    if (containerWidth > width) {
                        items = r[width].items;
                    }
                }
                var columnWidth = containerWidth / parseInt(items, 10);
                $(container).find($(container).data('selector')).addClass('showed');
                $(container).closest('.filters-wrapper').find('.filters .filter[data-selector="> *"]').addClass('active');
                setTimeout(function() {
                    $(container).masonry({
                        itemSelector: $(container).data('selector'),
                        gutter: parseInt($(container).data('gutter'), 10),
                        columnWidth: columnWidth
                    });
                }, 0);
            });
        }
    }
    $(function() {
        initAzexoCarousel();
        initAzexoFilters();
        initMasonry();
        if (document.documentElement.clientWidth > 768) {
            if (typeof scrollReveal === 'function') {
                window.scrollReveal = new scrollReveal();
            }
        }
        $(window).on('resize', function() {
            initMasonry();
        });
        if ('tabs' in $.fn) {
            $('.azexo-tabs').each(function() {
                if (!$(this).tabs('instance')) {
                    $(this).tabs();
                }
            });
        }
        if ('accordion' in $.fn) {
            $('.azexo-accordion').each(function() {
                if (!$(this).accordion('instance')) {
                    $(this).accordion({
                        header: ".accordion-section > h3",
                        autoHeight: false,
                        heightStyle: "content",
                        active: $(this).data('active-section'),
                        collapsible: $(this).data('collapsible'),
                        navigation: true,
                        animate: 200
                    });
                }
            });
        }
    });
})(jQuery);