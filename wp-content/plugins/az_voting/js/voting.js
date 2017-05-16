(function($) {
    'use strict';
    $(document).on('click', '.voting-button', function() {
        var button = $(this);
        var vote = button.attr('data-vote');
        var post_id = button.attr('data-post-id');
        var security = button.attr('data-nonce');
        var iscomment = button.attr('data-iscomment');
        var allbuttons = $(button).closest('.voting-wrapper');
        var loader = allbuttons.find('.voting-loader');
        if (post_id !== '') {
            $.ajax({
                type: 'POST',
                url: azpv.ajaxurl,
                data: {
                    action: 'process_vote',
                    vote: vote,
                    post_id: post_id,
                    nonce: security,
                    is_comment: iscomment
                },
                beforeSend: function() {
                    allbuttons.addClass('loading');
                    loader.html('<span class="loader">' + azpv.loading + '</span>');
                },
                success: function(response) {
                    allbuttons.removeClass('loading');
                    allbuttons.find('.voting-votes').html(response.formated_votes);
                    allbuttons.removeClass('up');
                    allbuttons.removeClass('down');
                    allbuttons.addClass(response.status);
                    $('.up-voting-' + post_id).html(Math.round(((response.count > 0 ? response.up_count / response.count : 0)) * 100) + '%');
                    $('.voting-count-' + post_id).html(response.formated_count);
                    loader.empty();
                }
            });

        }
        return false;
    });
})(jQuery);
