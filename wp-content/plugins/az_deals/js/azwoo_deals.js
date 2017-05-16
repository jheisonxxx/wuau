(function($) {
    "use strict";
    $(function() {
        if ('select2' in $.fn) {
            $('select[name="location"]').select2();
        }
    });
})(jQuery);