@extends('layouts.vendor.app')

@section('title', 'Service Details')

@push('css_or_js')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .add_more_btn {
            display: block;
        }

        .add_more_btn_mobile {
            display: none;
        }

        /* General Styles for Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            text-align: left;
            padding: 8px;
        }

        .table tbody td {
            padding: 8px;
        }

        /* Ensure Input Fields Take Full Width */
        .table tbody td input {
            width: 100%;
            box-sizing: border-box;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .add_more_btn {
                display: none;
            }

            .add_more_btn_mobile {
                display: block;
            }

            /* Make Table Scrollable */
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                /* Prevent wrapping of table rows */
            }

            /* Adjust Input Field Widths */
            .table tbody td input {
                font-size: 14px;
                padding: 6px;
            }

            /* Adjust Button Size */
            .btn {
                font-size: 12px;
                padding: 6px;
            }
        }

        @media (max-width: 850px) {

            /* Stack Table Rows (Optional Alternative) */
            .table {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .table thead {
                display: none;
                /* Hide headers on smaller screens */
            }

            .table tbody tr {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 0.5rem;
                border: 1px solid #ddd;
                padding: 8px;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }

            .table tbody td::before {
                content: attr(data-label);
                /* Dynamically add labels for accessibility */
                flex: 1;
                font-weight: bold;
                text-transform: capitalize;
            }

            .table tbody td input {
                /* flex: 2; */
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">


        <!-- Resturent Card Wrapper -->
        <div class="row">
            <div class="col-md-7">
                <h4 class="resturant-card" style="background-color:#f0f0f0">{{ $serviceDetails->item_name }}</h4>
                @php   $gp = $allGatepasses; @endphp
                @if (
                    ($serviceDetails->current_status == 'Cancelled' || $serviceDetails->current_status == 'Completed') &&
                        !_gatepassExist($serviceDetails->service_id))
                    No gatepass found..
                @elseif(hasPermission('leads_gatepass', 'veiw') && _gatepassExist($serviceDetails->service_id) && $gp->approved == 1)
                    <h3>Current Gatepass</h3>

                    <div class="col-12 mb-1">
                        <div class="resturant-card card--bg-1 position-relative">
                            <h5 class="title" style="font-size:1.1rem;">
                                {{ !$gp->approved ? 'Approval Pending' : ($gp->approved == 1 ? 'Approved' : 'Rejected') }}
                            </h5>
                            <span class="subtitle">Items : </span>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Image</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($gpItems as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->title }}</td>
                                            <td> {{ $item->description }}</td>
                                            <td><a target="_blank"
                                                    href="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}"
                                                    style="cursor:default;" class="table-rest-info" alt="Gatepass image">
                                                    <img style=" cursor:zoom-in;"
                                                        onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                        src="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}">
                                                </a></td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif(hasPermission('leads_gatepass', 'edit') && _gatepassExist($serviceDetails->service_id))
                    <form class="w-100" id="quote_form" enctype="multipart/form-data"
                        action="{{ route('vendor.service.gatepass-update') }}" method="post">
                        @csrf
                        <h5 class="title" style="font-size:1.1rem;">
                            {{ !$gp->approved ? 'Approval Pending' : ($gp->approved == 1 ? 'Approved' : 'Rejected') }}</h5>
                        @if ($gp->approved == 2)
                            <input type="hidden" name="gp_status" value="rejected">
                        @else
                            <input type="hidden" name="gp_status" value="new">
                        @endif
                        <input type="hidden" id="service_id" name="service_id" value="{{ $serviceDetails->service_id }}">

                        <h4 class="my-3">Edit Gatepass</h4>
                        <table class="table">
                            <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn_mobile"
                                    onclick="addMoreRow()">Add More</button></th>

                            <thead class="" style=" background: #75b8b8; color: white;">
                                <tr>
                                    <th scope="col">Ttitle</th>
                                    <th scope="col">Image<span class="text-danger">*</span></th>
                                    <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn"
                                            onclick="addMoreRow()">Add More</button></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                                @foreach ($gpItems as $key => $item)
                                    <tr class="existing_item_row row_1" data-id="1">
                                        <input type="hidden" name="ids[]" value="{{ $item->id }}">
                                        <td><input type="text" name="title[]" value="{{ $item->title }}"
                                                placeholder="Title" class="form-control"></td>
                                        <td><input type="file" name="existing_image[{{ $key }}][]" multiple
                                                accept="image/*" class="form-control image-input" data-id="1">
                                            <div class="image-error text-danger mt-1" style="font-size:14px;"></div>
                                        </td>

                                        <td><a href="{{ route('vendor.service.delete-gatepass-item', [$item->id]) }}"
                                                class="btn action-btn btn--danger btn-outline-danger"><i
                                                    class="tio-delete-outlined"></i></a></td>
                                    </tr>
                                    <tr class=" row_1">
                                        <td colspan="2">
                                            <textarea type="number" name="desc[]" placeholder="Description" class="form-control">{{ $item->description }}</textarea>
                                        </td>
                                        <td>
                                            @if ($item->image)
                                                @if (is_array(json_decode($item->image)))
                                                    <input type="hidden" value="{{ count(json_decode($item->image)) }}"
                                                        id="image_count_{{ $key + 1 }}">
                                                    <div class="d-flex lightgallery">
                                                        @foreach (json_decode($item->image) as $key => $value)
                                                            <a target="_blank"
                                                                href="{{ asset('storage/app/public/gatepass') }}/{{ $value }}"
                                                                style="cursor:default;" class="table-rest-info"
                                                                alt="Gatepass image">
                                                                <img style="width: 40px; height: 40px; cursor:zoom-in;"
                                                                    onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                                    src="{{ asset('storage/app/public/gatepass') }}/{{ $value }}">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <input type="hidden" value="1"
                                                        id="image_count_{{ $key }}">
                                                    <div class="lightgallery">
                                                        <a target="_blank"
                                                            href="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}"
                                                            style="cursor:default;" class="table-rest-info"
                                                            alt="Gatepass image">
                                                            <img style="width: 80px; height: 80px; cursor:zoom-in;"
                                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                                src="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}">
                                                        </a>
                                                    </div>
                                                @endif
                                            @endif

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="form-row  col-12 my-2">
                            <button type="button"
                                class="btn  btn--primary btn-outline-primary submit_btn">Update</button>
                        </div>

                    </form>
                @elseif(hasPermission('leads_gatepass', 'add'))

                    <form class="w-100" id="quote_form" enctype="multipart/form-data"
                        action="{{ route('vendor.service.gatepass-add') }}" method="post">
                        @csrf
                        <input type="hidden" id="service_id" name="service_id"
                            value="{{ $serviceDetails->service_id }}">
                        <input type="hidden" id="acc_id" name="acc_id" value="{{ $serviceDetails->id }}">

                        <h4 class="m-3 mb-0">Create Gatepass</h4>
                        <table class="table">
                            <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn_mobile"
                                    onclick="addMoreRow()">Add More</button></th>

                            <thead class="" style=" background: #75b8b8; color: white;">
                                <tr>
                                    <th scope="col">Ttitle</th>
                                    <th scope="col">Image<span class="text-danger">*</span></th>
                                    <th scope="col"><button type="button" class="btn btn-dark btn-sm add_more_btn"
                                            onclick="addMoreRow()">Add More</button></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                                <tr class="item_row row_1" data-id="1">
                                    <td><input type="text" name="title[]" placeholder="Title" class="form-control">
                                    </td>
                                    <td>
                                        <input type="file" name="image[0][]" multiple required class="form-control"
                                            accept="image/*,android/allowCamera">
                                    </td>

                                    <td><button type="button" onclick="deleteRow(1)"
                                            class="btn action-btn btn--danger btn-outline-danger"><i
                                                class="tio-delete-outlined"></i></button></td>
                                </tr>
                                <tr class=" row_1">
                                    <td colspan="2">
                                        <textarea type="number" name="desc[]" placeholder="Description" class="form-control"></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="form-row  col-12 my-2">
                            <button type="button"
                                class="btn  btn--primary btn-outline-primary submit_btn">Create</button>
                        </div>

                    </form>
                @endif
            </div>
            <div class="col-md-5" style=" height: 90vh; overflow: auto;">
                <h3>Customer Details</h3>
                @php $custDet = _customerDetByserviceId($serviceDetails->service_id); @endphp
                <div class="col-12 mb-1">
                    <div class="resturant-card card--bg-3 position-relative">
                        <div class="mb-2">
                            <h5 class="title" style="font-size:1.1rem; display:inline;">
                                {{ $custDet->f_name . ' ' . $custDet->l_name }}</h5>
                        </div>
                        <div class="subtitle mb-1">{{ $custDet->email }}</div>
                        <div class="subtitle">{{ $custDet->phone }}</div>
                        <div class=""><i>Customer since: {{ _monthNYear($custDet->created_at) }}</i></div>
                    </div>
                </div>

            </div>

        </div>
        <!-- Resturent Card Wrapper -->


        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">

            </div>
            <!-- End Header -->

            <!-- Table -->
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        $(document).on('click', '.submit_btn', function() {
            var $itemRows = $('.item_row');
            var $existingItemRows = $('.existing_item_row');
            if ($itemRows.length == 0 && $existingItemRows == 0) {
                toastr.error('Please add atleast one item');
            } else {
                $('#quote_form').submit();
            }
        })

        function deleteRow(rowId) {
            $('.row_' + rowId).remove()
        }

        function addMoreRow() {

            var $lastItemRow = $('.item_row').last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }

            var html = `<tr class="item_row row_` + dataId + `" data-id="` + dataId + `">
                    <input type="hidden" name = "new_item[]" value = '' >  
                      <td><input type="text" name="title[]" placeholder="Title" class="form-control"></td>
                      <td><input type="file" name="image[` + (dataId - 1) +
                `][]" multiple required accept="image/*" class="form-control image-input" data-id="` + (dataId + 1) + `"><div class="image-error text-danger mt-1" style="font-size:14px;"></div></td>
                      <td><button type="button" onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>
                    <tr class=" row_` + dataId + `" >
                        <td colspan="2"><textarea type="number" name="desc[]" placeholder="Description" class="form-control"></textarea></td>
                    </tr>`;

            $('.rows_parent').append(html)
        }

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
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script>
        $('#search-form').on('submit', function() {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.store.search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.total);
                    $('.page-area').hide();
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    <script>
        document.querySelectorAll('.lightgallery').forEach(gallery => {
            lightGallery(gallery, {
                plugins: [lgVideo], // Add lgVideo plugin
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                thumbWidth: 80,
                thumbHeight: "auto",
                videojs: true // Enable video support
            });
        });

        // IMAGES VALIDATION 
        document.addEventListener('change', function(e) {
            if (e.target.matches('.image-input')) {

                const input = e.target;
                const files = input.files;
                const errorBox = input.nextElementSibling; // the div after input
                var dataId = input.getAttribute('data-id');
                // console.log(dataId)
                var existingFilesCount = $('#image_count_' + dataId).val()

                var totalFilesCount = (existingFilesCount != undefined) ? files.length + existingFilesCount : files
                    .length;
                // Clear previous error
                errorBox.textContent = "";
                input.style.border = "1px solid #ededed";
                const imgCountLimit =
                    {{ \App\Models\BusinessSetting::where('key', 'gatepass_max_image')->value('value') ?? 4 }};
                const imgSizeLimit =
                    {{ \App\Models\BusinessSetting::where('key', 'gatepass_image_max_size')->value('value') ?? 10 }};



                // Max 4 photos
                if (totalFilesCount > imgCountLimit) {
                    errorBox.textContent = "You can upload a maximum of " + imgCountLimit + " photos.";
                    input.value = "";
                    input.style.border = "2px solid red";
                    return;
                }

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    if (!file.type.startsWith("image/")) {
                        errorBox.textContent = "Only image files are allowed.";
                        input.style.border = "2px solid red";
                        input.value = "";
                        return;
                    }

                    if (file.size > imgSizeLimit * 1024 * 1024) {
                        errorBox.textContent = `"${file.name}" is larger than ` + imgSizeLimit + `MB.`;
                        input.style.border = "2px solid red";
                        input.value = "";
                        return;
                    }
                }
            }
        });
    </script>
@endpush
