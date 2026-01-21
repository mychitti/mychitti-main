<script>
    $(".file_download").on('click', function() {
        console.log('file_download')
        // send-otp
        var id = $(this).attr('data-id');
        $(".file_id").val(id)
        sendOtp('file_auth', 'admin', id);
    })

    function sendOtp(type, to, id) {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post({
            url: "{{ route('admin.send-otp') }}",
            data: {
                type: type,
                to: to,
                id: id
            },
            success: function(data) {},
        });
    }


    $(".otpForm").on('submit', function(e) {
        e.preventDefault();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post({
            url: $(this).attr('action'),
            data: $(this).serialize(), // Serializing form data

            success: function(data) {
                console.log(data)
                if (data.status) {

                    $('#authForFileModal').modal('hide');

                    window.location.href = data.download_url;

                    toastr.success(data.msg);
                    
                } else {
                    toastr.error(data.msg);
                }
            },
            complete: function() {
                $('#loading').hide()
            }
        });

    })
</script>
