  <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    <script>
        $('.dlt-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).attr('data-id')
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('vendor.gallery.delete') }}",
                data: {
                    id: id
                },
                success: function(data) {
                    toastr.success("Image Deleted Successfully.");
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                },
                complete: function() {
                    $('#loading').hide()
                }
            });

        });
        $("#check_all").on('change', function() {
            if ($(this).prop('checked') == true) {
                $("#check_all_label").text('Deselect All');
                $(".check_select").prop('checked', true)

            } else {
                $("#check_all_label").text('Select All');
                $(".check_select").prop('checked', false)

            }
        })
        $("#action").on('change', function() {
            if ($(this).val() == 'delete') {

                Swal.fire({
                    title: '{{ translate('messages.Are you sure?') }}',
                    text: 'You want to delete selected gallery',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.no') }}',
                    confirmButtonText: '{{ translate('messages.Yes') }}',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        $("#bulk_form").submit()
                    }
                })
            }

        })
        lightGallery(document.querySelector('.lightgallery'), {
            selector: '.gallery-item',
            download: false,
            thumbnail: true
        });
    </script>