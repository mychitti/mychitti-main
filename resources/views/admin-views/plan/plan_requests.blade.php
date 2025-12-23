@extends('layouts.admin.app')

@section('title', 'Subscription Plan Requests')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .check-mark {
            font-weight: bold;
            font-size: 1.2rem;
            color: #00aa6d;
        }

        td .badge {
            border: 1px solid #eaeaea;
            margin: 2px;
            font-size: 10px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-9 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--22" alt="">
                        </span>
                        <span>
                            Subscription Plan Requests <span class="badge badge-soft-dark ml-2"
                                id="foodCount">{{ count($allPlanRequests) }}</span>
                        </span>
                    </h1>
                </div>


            </div>

        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper justify-content-end">
                    <form class="search-form">
                        {{-- @csrf --}}
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch" name="search" type="search" class="form-control h--40px"
                                placeholder="Search Plan Title" aria-label="{{ translate('messages.search_here') }}">
                            <button type="submit" class="btn btn--secondary h--40px"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    <!-- Unfold -->


                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom" id="table-div">
                <table id="datatable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">Source</th>
                            <th class="border-0">Company Name</th>
                            <th class="border-0">Contact Name</th>
                            {{-- <th class="border-0">Price</th> --}}
                            {{-- <th class="border-0">Discount (%)</th> --}}
                            <th class="border-0">Phone</th>
                            <th class="border-0">Email</th>
                            <th class="border-0 text-center">Features</th>
                            <th class="border-0 text-center">Additional Requirements</th>
                            <th class="border-0 text-center">Business Type</th>
                            <th class="border-0 ">Status</th>
                            <th class="border-0 ">Requested At</th>
                            <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($allPlanRequests as $key => $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{$item->store ? 'Mychitti Vendor Panel' : 'MC Vendor Hub' }}</td>
                                <td>
                                    {{ $item->store?->name ?? $item->company_name }}
                                </td>
                                <td>
                                    {{ $item->store?->name ?? $item->contact_name }}
                                </td>

                                <td>
                                    {{ $item->store?->phone ?? $item->phone }}
                                </td>

                                <td>
                                    {{ $item->store?->email ?? $item->email }}
                                </td>

                                <td>
                                    <div class="d-flex row">
                                        @foreach (json_decode($item->features) as $key => $value)
                                            <span class="badge badge-light">{{ $value }} </span>
                                        @endforeach

                                    </div>

                                </td>
                                <td>
                                    {{ $item->additional_requirements }}
                                </td>
                                <td>
                                    {{ $item->business_type }}
                                </td>
                                <td>
                                    {{ $item->status ? 'Created ' : 'Pending' }}
                                </td>
                                <td>
                                    {{ $item->created_at }}
                                </td>

                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a style="width: fit-content; padding: 0 10px !important; " class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('admin.plan.add-new', ['customized', $item->id]) }}"
                                            title="{{ translate('messages.create') }} Plan"><i class="tio-edit"></i> Create
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if (count($allPlanRequests) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function status_form_alert(id, message) {
            // e.preventDefault();
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
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
                    $('#' + id).submit()
                }
            })
        }
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
                select: {
                    style: 'multi',
                    classMap: {
                        checkAll: '#datatableCheckAll',
                        counter: '#datatableCounter',
                        counterInfo: '#datatableCounterInfo'
                    }
                },
                language: {
                    zeroRecords: '<div class="text-center p-4">' +
                        '<img class="w-7rem mb-3" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="Image Description">' +

                        '</div>'
                }
            });

            $('#datatableSearch').on('mouseup', function(e) {
                var $input = $(this),
                    oldValue = $input.val();

                if (oldValue == "") return;

                setTimeout(function() {
                    var newValue = $input.val();

                    if (newValue == "") {
                        // Gotcha
                        datatable.search('').draw();
                    }
                }, 1);
            });

            $('#toggleColumn_index').change(function(e) {
                datatable.columns(0).visible(e.target.checked)
            })
            $('#toggleColumn_name').change(function(e) {
                datatable.columns(1).visible(e.target.checked)
            })

            $('#toggleColumn_type').change(function(e) {
                datatable.columns(2).visible(e.target.checked)
            })

            $('#toggleColumn_vendor').change(function(e) {
                datatable.columns(3).visible(e.target.checked)
            })

            $('#toggleColumn_status').change(function(e) {
                datatable.columns(5).visible(e.target.checked)
            })
            $('#toggleColumn_price').change(function(e) {
                datatable.columns(4).visible(e.target.checked)
            })
            $('#toggleColumn_action').change(function(e) {
                datatable.columns(6).visible(e.target.checked)
            })

            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $('#store').select2({
            ajax: {
                url: '{{ url('/') }}/store/get-stores',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        module_id: {{ Config::get('module.current_module_id') }},
                        page: params.page
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

        $('#category').select2({
            ajax: {
                url: '{{ route('admin.category.get-all') }}',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        all: true,
                        module_id: {{ Config::get('module.current_module_id') }},
                        page: params.page
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

        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.item.search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#set-rows').html(data.view);
                    $('.page-area').hide();
                    $('#foodCount').html(data.count);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
