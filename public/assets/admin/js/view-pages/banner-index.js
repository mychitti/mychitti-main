"use strict";
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            $('#viewer').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

$("#customFileEg1").change(function () {
    readURL(this);
});
function readURL2(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            $('#viewer2').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

$("#customFileEg12").change(function () {
    readURL2(this);
});

var zone_id = [];

$(document).on('ready', function () {
    $('#zone').on('change', function(){
        if($(this).val())
        {
            zone_id = $(this).val();
            get_items();
        }
        else
        {
            zone_id = [];
        }
    });

    // INITIALIZATION OF DATATABLES
    // =======================================================
    var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'), {
        select: {
            style: 'multi',
            classMap: {
                checkAll: '#datatableCheckAll',
                counter: '#datatableCounter',
                counterInfo: '#datatableCounterInfo'
            }
        },
    });

    $('#datatableSearch').on('mouseup', function (e) {
        var $input = $(this),
            oldValue = $input.val();

        if (oldValue == "") return;

        setTimeout(function(){
            var newValue = $input.val();

            if (newValue == ""){
                // Gotcha
                datatable.search('').draw();
            }
        }, 1);
    });

    // INITIALIZATION OF SELECT2
    // =======================================================
    $('.js-select2-custom').each(function () {
        var select2 = $.HSCore.components.HSSelect2.init($(this));
    });
});

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

$(function () {
    banner_type_change($('#banner_type').val() || 'store_wise');
});

$('#banner_type').on('change', function () {
    banner_type_change($(this).val());
})