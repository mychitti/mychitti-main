    <script>
        "use strict";
        $('#reset_btn').click(function() {
            $('#viewer').attr('src', '{{ asset('/public/assets/admin/img/upload-4.png') }}');
        })
    </script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/banner-index.js"></script>
    <script>
        "use strict";
        var module_id = {{ Config::get('module.current_module_id') }};

        function get_items() {
            var nurl = '{{ url('/') }}/item/get-items?module_id=' + module_id;

            if (!Array.isArray(zone_id)) {
                nurl += '&zone_id=' + zone_id;
            }

            $.get({
                url: nurl,
                dataType: 'json',
                success: function(data) {
                    $('#choice_item').empty().append(data.options);
                }
            });
        }

        $(document).on('ready', function() {

            module_id = {{ Config::get('module.current_module_id') }};
            get_items();

            $('.js-data-example-ajax').select2({
                ajax: {
                    url: '{{ url('/') }}/store/get-stores',
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            zone_ids: [zone_id],
                            page: params.page,
                            module_id: module_id
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    __port: function(params, success, failure) {
                        var $request = $.ajax(params);

                        $request.then(success);
                        $request.fail(failure);

                        return $request;
                    }
                }
            });

        });

        $('#banner_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('admin.banner.store') }}",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success('{{ translate('messages.banner_added_successfully') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            location.href = '{{ route('admin.banner.add-new') }}';
                        }, 2000);
                    }
                }
            });
        });



        $('#reset_btn').click(function() {
            $('#module_select').val(null).trigger('change');
            $('#zone').val(null).trigger('change');
            $('#store_id').val(null).trigger('change');
            $('#choice_item').val(null).trigger('change');
            $('#viewer').attr('src', '{{ asset('public/assets/admin/img/900x400/img1.jpg') }}');
        })

        $("#banner_type").on('change', function() {
            if ($(this).val() == 'store_wise') {
                $("#store_wise").show();
                $("#customer_wise").hide();
            } else {
                $("#store_wise").hide();
                $("#customer_wise").show();
            }
        })
    </script>
