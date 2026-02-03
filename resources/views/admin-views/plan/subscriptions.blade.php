@extends('layouts.admin.app')

@section('title', 'Subscription Stores')

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
                            Stores Subscription
                        </span>
                    </h1>
                </div>
                <!-- Button trigger modal -->
                <a href="{{ route('admin.plan.module-store') }}" class="btn btn-primary">
                    Add Plan for Store
                </a>
                {{-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#planModal">
                    Add Plan for Store
                </button> --}}

                <!-- Modal -->
                <div class="modal fade" id="editDateModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Edit Date</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action ="{{ route('admin.plan.edit-plan-store') }}" method="post">
                                    @csrf
                                    <label class="form-check-label" for="flexRadioDefault2">Start Date</label>
                                    <input class="form-control edit_start_date" type="date" name="start_date"
                                        id="">
                                    <label class="form-check-label" for="flexRadioDefault3">Expiry Date</label>
                                    <input class="form-control edit_end_date" type="date" name="expiry_date"
                                        id="">
<input type="hidden" name="store_id" class="edit_store_id" value="">
                                    <button class="btn btn-primary">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- <div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Add Plan for Store</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action ="{{ route('admin.plan.buy-plan-store') }}" method="post">
                                    @csrf
                                    <label class="form-check-label" for="flexRadioDefault2">Store</label>
                                    <select required name="v_id" class="form-control js-select2-custom ">
                                        <option value=""></option>
                                        @foreach ($stores as $store)
                                            <option value="{{ $store->id }}">
                                                {{ $store->phone . ' | ' . $store->name . ' | ID:' . $store->id }}</option>
                                        @endforeach
                                    </select>
                                    <label class="form-check-label" for="flexRadioDefault3">Plan</label>
                                    <select required name="plan_select" class="form-control js-select2-custom ">
                                        <option value=""></option>
                                        @foreach ($plans as $plan)
                                            @if ($plan->price_variations)
                                                @foreach (json_decode($plan->price_variations, true) as $key2 => $variation)
                                                    <option value="{{ $plan->id }}-{{ $variation['id'] }}">
                                                        {{ $plan->title . ' | ' . $variation['duration'] }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="my-3 d-flex gap-2 align-items-center">
                                        <input type="radio" value="1" name="billing" class="billing_status"
                                            id="billing" checked>
                                        <label for="billing" class="mb-0">Billing</label>
                                        <input type="radio" value="0" name="billing" class="billing_status"
                                            id="retail">
                                        <label for="retail" class="mb-0">Retail</label>
                                    </div>
                                    <div class=" invoice_date_inp mb-2" style="width: fit-content;">
                                        <label for="invoice_date" class="mb-0">Invoice date</label>
                                        <input type="date" class="form-control" name="invoice_date" id="invoice_date">
                                    </div>
                                    <button class="btn btn-primary">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper justify-content-end">

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
                            <th class="border-0">Store Information</th>
                            <th class="border-0">Modules</th>
                            <th class="border-0 ">Last Purchased At</th>
                            <th class="border-0 ">Expiry</th>
                            <th class="border-0 ">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($stores as $store)
                            <tr>
                                <td>{{ $loop->iteration + $stores->firstItem() - 1 }}</td>

                                {{-- Store Info --}}
                                <td>
                                    <a href="{{ route('admin.store.view', $store->id) }}" class="table-rest-info">
                                        <img class="img--60 circle"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $store->logo,
                                                asset('storage/app/public/store/' . $store->logo),
                                                asset('public/assets/admin/img/160x160/img1.jpg'),
                                                'store/',
                                            ) }}">
                                        <div class="info">
                                            <div class="text--title">{{ Str::limit($store->name, 20) }}</div>
                                            <div class="font-light">ID: {{ $store->id }}</div>
                                        </div>
                                    </a>
                                </td>

                                {{-- All Plan Details --}}
                                <td style="white-space: normal;">
                                    <span class="badge badge-outline-secondary">{{ count($store->subscriptions) }}</span>
                                    <a href="{{ route('admin.plan.store-modules', [$store->id]) }}"
                                        class="badge badge-primary">View Modules</a>
                                </td>

                                {{-- First subscription created date --}}
                                <td>{{ $store->subscriptions->sortByDesc('created_at')->first()->created_at }}</td>

                                {{-- Latest expiry among all plans --}}
                                <td>
                                    @php $firstStartDate = $store->subscriptions->min('created_at'); @endphp
                                    @php $latestExpiry = $store->subscriptions->max('plan_expiry'); @endphp

                                    @if (strtotime($latestExpiry) < time())
                                        <span class="badge badge-danger">Expired</span>
                                    @else
                                        {{ $latestExpiry }}
                                    @endif

                                </td>
                                <td>
                                    <div class="btn--container">
                                        <a data-toggle="modal" data-target="#editDateModal" type="button"
                                            class="action-btn btn btn--primary btn-outline-primary edit_btn"
                                            data-id="{{ $store->id }}" data-sdate="{{ $firstStartDate }}"
                                            data-edate="{{ $latestExpiry }}"><i class="tio-edit"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

                <div class="page-area">
                    {!! $stores->links() !!}
                </div>
                @if (count($stores) === 0)
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
        $(".edit_btn").on('click', function() {
            var store_id = $(this).attr('data-id')
            var start_date = $(this).attr('data-sdate')
            var end_date = $(this).attr('data-edate')

            let startDateValue = start_date.split(' ')[0];
            let endDateValue = end_date.split(' ')[0];

            $(".edit_start_date").val(startDateValue)
            $(".edit_end_date").val(endDateValue)
            $('.edit_store_id').val(store_id)
        })
        $(".billing_status").on('change', function() {
            console.log('fsd')
            console.log($(this).val())
            if ($(this).val() == '1') {
                $(".invoice_date_inp").show();
            } else {
                $(".invoice_date_inp").hide();
            }
        });

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
                url: '{{ url('/') }}/admin/store/get-stores',
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
