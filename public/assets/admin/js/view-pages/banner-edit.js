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
    if(order_type=='item_wise')
    {
        $('#store_wise').hide();
        $('#item_wise').show();
        $('#default').hide();
        $('#module_wise').hide();
    }
    else if(order_type=='store_wise')
    {
        $('#store_wise').show();
        $('#item_wise').hide();
        $('#default').hide();
        $('#module_wise').hide();
    }
    else if(order_type=='default')
    {
        $('#default').show();
        $('#store_wise').hide();
        $('#item_wise').hide();
        $('#module_wise').hide();
    }
    else if(order_type=='module_wise')
    {
        $('#module_wise').show();
        $('#default').hide();
        $('#store_wise').hide();
        $('#item_wise').hide();
    }
    else if(order_type=='category_wise')
    {
        $('#module_wise').hide();
        $('#default').hide();
        $('#store_wise').hide();
        $('#item_wise').hide();
        $('#category_wise').show();
    }
    else{
        $('#module_wise').hide();
        $('#item_wise').hide();
        $('#store_wise').hide();
        $('#default').hide();
    }
}

$("#customFileEg1").change(function () {
    console.log('fssd')
    readURL(this);
});

$('#reset_btn').click(function(){
    location.reload(true);
})
