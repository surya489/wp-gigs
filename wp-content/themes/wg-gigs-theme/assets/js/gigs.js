jQuery(function ($) {
    $('.wg-gig-filters').on('submit', function (e) {
        e.preventDefault();

        var formData = $(this).serialize() + '&action=wg_filter_gigs';

        $.ajax({
            url: wg_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function (response) {
                $('.wg-gig-list-wrapper').html(
                    $(response).find('.wg-gig-list-wrapper').html()
                );
            },
            error: function() {
                alert('Something went wrong. Please try again.');
            }
        });
    });
});