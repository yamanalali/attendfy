$(function () {
    // Change the menu nav
    var url = baseUrl + "/shifts/add"; // Change the url base on page
    if (typePage == 'update') {
        $('ul.nav-sidebar').find('a.nav-link').filter(function () {
            return this.href == url;
        }).addClass('active');

        $('ul.nav-sidebar').find('a.nav-link').filter(function () {
            return this.href == url;
        }).parent().parent().parent().addClass('menu-open');

        $('ul.nav-sidebar').find('a.nav-link').filter(function () {
            return this.href == url;
        }).parent().parent().parent().find('a.nav-item').addClass('active');
    }

    $('.timepicker').timepicker({
        timeFormat: 'HH:mm',
        dynamic: false,
        dropdown: true,
        scrollbar: true,
        interval: 1
    });

    $('.timepicker_minutes').timepicker({
        timeFormat: 'HH:mm',
        dynamic: false,
        dropdown: true,
        scrollbar: true,
        interval: 1,
        maxHour: 0,
        maxMinutes: 60,
    });
});
