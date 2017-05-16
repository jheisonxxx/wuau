!function($) {
    "use strict";
    if ('azh' in window) {
        $('textarea.azexo_html').each(function() {
            azh.init(this);
        });
    }
}(window.jQuery);