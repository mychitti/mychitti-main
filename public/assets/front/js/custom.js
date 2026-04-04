// console.log("script loaded");
$(".formSubmit").on("submit", function (e) {
    e.preventDefault();

    var formData = new FormData($(this)[0]);
    var profile_form = false;
    if ($(this).hasClass("profile_form")) {
        profile_form = true;
    }

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.post({
        url: $(this).attr("action"),
        processData: false,
        contentType: false,
        async: false,
        cache: false,
        data: formData,
        beforeSend: function () {},
        success: function (data) {
            if (profile_form) {
                window.location.reload();
            }
            try {
                data = JSON.parse(data);
            } catch (error) {
                console.error("JSON Parse Error:", error);
            }
            console.log(data);
            if (data.errors && data.errors.length > 0) {
                toasterNotification(data.errors[0].message);
            } else {
                $(".contactForm").trigger("reset");

                if ($(this).hasClass("addressForm")) {
                    toasterNotification(data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    toasterNotification(data.message);
                }

                $(".btn-close").click();
                $("#coupon_elem").show();
                var html = `<div class="bg-light">
                                    <span>
                                        Voucher applied : Off20 
                                    </span> 
                                    <button class="btn btn-primary" onclick="removeCoupon()">Remove</button>
                                </div>`;
                $(".voucher_div").html(html);
                $(".coupon_amount").text(data.coupon.discount);
                $("#total_amount").text(
                    Number($("#total_amount").text()) - data.coupon.discount
                );
                $("#order_amount").val(
                    Number($("#total_amount").text()) - data.coupon.discount
                );
                $("#coupon_code").val(data.coupon.code);
            }
        },
        complete: function (data) {
            console.log(data.status);
            if (data.status == 403) {
                toasterNotification("Some error occured");
            }
        },
    });
});

$(".addressForm").on("submit", function (e) {
    e.preventDefault();

    var formData = new FormData($(this)[0]);

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $.post({
        url: $(this).attr("action"),
        processData: false,
        contentType: false,
        async: false,
        cache: false,
        data: formData,
        beforeSend: function () {},
        success: function (data) {
            console.log(data);
            if (data.errors && data.errors.length > 0) {
                toasterNotification(data.errors[0].message);
            } else {
                toasterNotification(data.message);
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        },
        complete: function (data) {
            console.log(data.status);
            if (data.status == 403) {
                toasterNotification("Some error occured");
            }
        },
    });
});

function removeCoupon() {
    $("#coupon_elem").hide();
    var html = `<a data-bs-toggle="modal" class="btn btn-outline-primary w-100" data-bs-target="#couponModal" type="button">Add Voucher</a>`;
    $(".voucher_div").html(html);
    $("#coupon_code").val("");
    $("#total_amount").text(
        Number($("#total_amount").text()) + Number($(".coupon_amount").text())
    );
    $("#order_amount").val(
        Number($("#total_amount").text()) + Number($(".coupon_amount").text())
    );
    $(".coupon_amount").text("");
}

function toasterNotification(msg) {
    if (!msg) return;
    $("#toast").text(msg);
    $("#toast").addClass("show");
    setTimeout(function () {
        $("#toast").removeClass("show");
    }, 3000);
}

// custom carousel
