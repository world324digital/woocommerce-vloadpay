jQuery(function ($) {
    $('form.checkout').on('click', '#quick_pay', function () {
        $('#is_quick_pay').val('1');
        $('#place_order').trigger('click');
        $('#is_quick_pay').val('0');
    });
});