<script>
    $('.category_select').on('change', function() {
        console.log('fsdf')
        var cat_id = $(this).val()
        var dataid = $(this).attr('data-id');
        $(".select_subcategory_" + dataid)
            .val(null)
            .trigger('change');
        //fetchsubcategory
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.get({
            url: "{{ route('fetch-subcategory') }}",
            data: {
                cat_id: cat_id
            },
            success: function(data) {
                console.log(data)
                if (data) {
                    if (data.categories.length) {
                        var html = '';
                        data.categories.forEach(element => {
                            html += '<option value="' + element.id + '">' + element.name +
                                '</option>';
                        });
                        $(".subcategory_" + dataid).show()
                        $(".select_subcategory_" + dataid).html(html)

                    } else {
                        $(".subcategory_" + dataid).hide()
                        $(".select_subcategory_" + dataid).html('')
                    }
                }
            },
        });
    })
</script>
