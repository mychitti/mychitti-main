@extends('layouts.admin.app')

@section('title', 'Subscription Plans')

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
                            Subscription Plan List <span class="badge badge-soft-dark ml-2"
                                id="foodCount">{{ count($allPlans) }}</span>
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
                            <th class="border-0">Title</th>
                            {{-- <th class="border-0">Price</th> --}}
                            {{-- <th class="border-0">Discount (%)</th> --}}
                            <th class="border-0">Variations</th>
                            <th class="border-0">Features</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0 ">Created At</th>
                            <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($allPlans as $key => $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $item->title }}
                                </td>
                                {{-- <td>
                                {{\App\CentralLogics\Helpers::format_currency($item->price)}}
                            </td> --}}
                                {{-- <td>
                                    {{ $item->discount }}%
                                </td> --}}
                                <td>
                                    @if ($item->price_variations && is_array(json_decode($item->price_variations)))
                                        @foreach (json_decode($item->price_variations) as $key => $value)
                                     <span style="    min-width: 164px;" class="badge badge-soft-success">   <b>Price: </b> {{ $value->price }},<b>Discount: </b> {{ $value->discount?? 0 }}%, <b>Duration: </b> {{$value->duration}} </span><br>
                                        @endforeach
                                    @else
                                        {{ $item->duration_count . ' ' . $item->duration_type }}
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex row">
                                        @if ($item->leads_manage)
                                            <span class="badge badge-light">Leads Manage </span>
                                        @endif
                                        @if ($item->projects_manage)
                                            <span class="badge badge-light">Projects Manage</span>
                                        @endif
                                        @if ($item->quotaiton_manage)
                                            <span class="badge badge-light">Quotation Manage</span>
                                        @endif
                                        @if ($item->salary_manage)
                                            <span class="badge badge-light">Salary Manage</span>
                                        @endif
                                        @if ($item->staff_manage)
                                            <span class="badge badge-light">Staff Manage</span>
                                        @endif
                                        @if ($item->att_manage)
                                            <span class="badge badge-light">Attendance Manage</span>
                                        @endif
                                        @if ($item->leave_manage)
                                            <span class="badge badge-light">Leaves Manage</span>
                                        @endif
                                        @if ($item->account_manage)
                                            <span class="badge badge-light">Accounts Manage</span>
                                        @endif
                                        @if ($item->service_leads)
                                            <span class="badge badge-light">Service Leads</span>
                                        @endif
                                        @if ($item->inventory_manage)
                                            <span class="badge badge-light">Inventory Manage</span>
                                        @endif
                                        @if ($item->advanced_leads_manage)
                                            <span class="badge badge-light">Advanced Leads Manage</span>
                                        @endif
                                        @if ($item->hr_manage)
                                            <span class="badge badge-light">HR Manage</span>
                                        @endif
                                        @if ($item->billing)
                                            <span class="badge badge-light">Advanced Billing</span>
                                        @endif
                                        @if ($item->task_manage)
                                            <span class="badge badge-light">Task Management</span>
                                        @endif
                                    </div>

                                </td>

                                <td>
                                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{ $item->id }}">
                                        <input type="checkbox"
                                            onclick="location.href='{{ route('admin.plan.status', [$item->id, $item->status ? 0 : 1]) }}'"class="toggle-switch-input"
                                            id="stocksCheckbox{{ $item->id }}" {{ $item->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label mx-auto">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>

                                <td>
                                    {{ $item->created_at }}
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('admin.plan.edit', [$item['id']]) }}"
                                            title="{{ translate('messages.edit') }} Plan"><i class="tio-edit"></i>
                                        </a>
                                        @if ($item['id'] != 6)
                                            <a class="btn  action-btn btn--danger btn-outline-danger" href="javascript:"
                                                onclick="status_form_alert('food-{{ $item['id'] }}','Want to delete this plan?' )"
                                                title="{{ translate('messages.delete') }} Plan"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('admin.plan.delete', [$item['id']]) }}" method="post"
                                                id="food-{{ $item['id'] }}">
                                                @csrf @method('delete')
                                            </form>
                                        @else
                                            <a style="width:100px; pointer-events:none;"
                                                class="btn action-btn btn--primary btn-outline-primary"
                                                title="{{ translate('messages.edit') }} Plan">Non Removable
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if (count($allPlans) === 0)
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
