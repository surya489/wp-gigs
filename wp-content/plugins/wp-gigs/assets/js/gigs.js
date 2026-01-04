jQuery(document).ready(function($) {
    $('#wg-gig-filters-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: wgAjax.ajaxUrl,
            type: 'GET',
            data: formData + '&action=wg_filter_gigs',
            beforeSend: function() {
                $('#wg-gig-list-results').html('<p>Loading gigs...</p>');
            },
            success: function(response) {
                $('#wg-gig-list-results').html(response);
            }
        });
    });
});