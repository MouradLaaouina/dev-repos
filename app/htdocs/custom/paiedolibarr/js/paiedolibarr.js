$(window).on('load', function() {
    $(".dsdatepickerdate").datepicker("destroy");
    $('.dsdatepickerdate').removeClass('hasDatepicker');
    $("input.dsdatepickerdate").datepicker({
        dateFormat: "dd/mm/yy"
    });    
    $('.select_paiedolibarrmodel').select2();
});
