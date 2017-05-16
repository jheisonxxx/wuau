(function($) {
    $(document).ready(function() {

        $('input#_voucher_option').change(function() {

            if ($(this).is(':checked')) {
                $('.show_if_voucherable').show();
            } else {
                $('.show_if_voucherable').hide();
            }

            if ($('.AZV_tab').is('.active')) {
                $('ul.wc-tabs li:visible').eq(0).find('a').click();
            }

        }).change();
    });
})(jQuery);