"use strict";
function getRequest(route, id) {
    $.get({
        url: route,
        dataType: 'json',
        success: function (data) {
            $('#' + id).empty().append(data.options);
        },
    });
}
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            $('#viewer').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
$('#banner_type').on('change', function () {
    let order_type = $(this).val();
    banner_type_change(order_type);
})
function banner_type_change(order_type) {
    $('#store_wise, #item_wise, #default, #module_wise, #category_wise').css('display', 'none');

    if (order_type === 'self') {
        $('#customer_wise, #price_field, #gst_field').css('display', 'none');
        return;
    }

    $('#price_field, #gst_field').css('display', 'block');

    if (order_type === 'store_wise') {
        $('#store_wise').css('display', 'block');
        $('#customer_wise').css('display', 'none');
    } else {
        $('#customer_wise').css('display', 'block');
        if (order_type === 'item_wise')          $('#item_wise').css('display', 'block');
        else if (order_type === 'default')       $('#default').css('display', 'block');
        else if (order_type === 'module_wise')   $('#module_wise').css('display', 'block');
        else if (order_type === 'category_wise') $('#category_wise').css('display', 'block');
    }
}

$("#customFileEg1").change(function () {
    console.log('fssd')
    readURL(this);
});

$('#reset_btn').click(function(){
    location.reload(true);
})
