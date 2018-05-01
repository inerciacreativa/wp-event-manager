;
(function ($) {

    $('#acf-date_allday .label label').on('click', function () {
        var checkbox = $(this).parent().next().find('input[type="checkbox"]');

        checkbox.click();
    });

    $('.acf_postbox select').each(function () {
        $(this).find('option').each(function () {
            var option = $(this);
            option.text(option.val());
        });
    });

}(jQuery));