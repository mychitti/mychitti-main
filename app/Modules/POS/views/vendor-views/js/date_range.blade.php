    <script>
        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }

        function changeStatus(quote_id, elem) {
            if ($(elem).val() === 'Convert to Bill') {
                let baseUrl = @json(route('vendor.quotation.convert-to-bill', ['id' => 'QUOTE_ID']));
                let finalUrl = baseUrl.replace('QUOTE_ID', quote_id);
                window.location.href = finalUrl;
            } else {
                document.getElementById('status-form' + quote_id).submit();
            }
        }
    </script>
    <script>
        function selectPreset(button) {
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
        }
    </script>

    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <!-- Date Range Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(function() {
            $('#daterange').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'YYYY-MM-DD'
                }
            }, function(start, end, label) {
                // This is called whenever a date is selected
                updateSelection();
            });
        });


        function updateSelection() {
            const urlParams = new URLSearchParams(window.location.search);
           if (urlParams.has('tab') && urlParams.get('tab') === 'items') {
                document.querySelector('.date-range-form2').submit();
            }else{
                document.querySelector('.date-range-form').submit();
            }
        }
    </script>
