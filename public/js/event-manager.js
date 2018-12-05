;
(function ($) {
  var
    date_end_show = $('.acf-field[data-name="date_end_show"]'),
    date_end_show_label = date_end_show.find('label'),
    date_end_show_input = date_end_show.find('input[type="checkbox"]'),
    date_end = $('.acf-field[data-name="date_end"], .acf-field[data-name="time_end_hour"], .acf-field[data-name="time_end_minutes"]');

  date_end_show_label.on('click', function () {
    if (date_end_show_input.is(':checked')) {
      date_end.each(function () {
        $(this).show();
      });
    } else {
      date_end.each(function () {
        $(this).hide();
      });
    }
  });

  $('.acf-postbox select').each(function () {
    $(this).find('option').each(function () {
      var option = $(this);
      option.text(option.val());
    });
  });

}(jQuery));