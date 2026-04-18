@extends('layouts.vendor.app')

@section('title', _isHospital() ? 'My Appointments' : 'Assigned Leads')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .select2-container {
            min-width: 200px !important;
        }

        /* otp element styling  */when 
        .otp-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-container p {
            margin-bottom: 20px;
            color: #666;
        }

        .otp-form {
            display: flex;
            justify-content: space-between;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>{{_isHospital() ? 'My Appointments' : 'Assigned Leads'}}  <span
                    class="badge badge-soft-dark ml-2" id="itemCount">{{ count($assignedServices) }}</span></h1>
            <div class="page-header-select-wrapper">


            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{_isHospital() ? 'My Appointments' : 'Assigned Leads'}}  </h5>

                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                    data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">Service Info</th>
                            <th class="border-0">Assigned at</th>

                            <th class="border-0">Status</th>
                            <th class="border-0">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($assignedServices as $key => $conf)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <div>
                                        <a href="" style="cursor:default;" class="table-rest-info" alt="view store">
                                            <img class="img--60 circle"
                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                src="{{ asset('storage/app/public/product') }}/{{ $conf->item_image }}">
                                            <div class="info">
                                                <div class="text--title">
                                                    {{ $conf->item_name }}
                                                </div>

                                            </div>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ _formatted_datetime($conf->assigned_at) }}
                                    </span>
                                </td>

                                <td class="col-2">
                                    @if (hasPermission('leads_manage', 'status_change'))
                                        @if ($conf->accepted_by_staff)
                                            @if ($conf->current_status == 'Completed')
                                                <span class='text-success'>Completed</span>
                                            @elseif($conf->current_status == 'Cancelled')
                                                <span class='text-danger'>Cancelled</span>
                                            @else
                                                {{-- @if (hasPermission('leads_manage', 'change_status')) --}}
                                                <select name="module_id" data-value="{{ $conf->id }}"
                                                    class="form-control js-select2-custom"
                                                    onchange="changeStatus(this.value, {{ $conf->service_id }})"
                                                    title="Change Status">
                                                    <option></option>
                                                    @foreach ($default_statuses as $st)
                                                        @php $jobInfo = _getWhere('vendor_emp_jobs', ['service_id'=> $conf->service_id])[0] @endphp
                                                        <option value="{{ $st->id }}"
                                                            {{ $jobInfo->status == $st->id ? 'selected' : '' }}>
                                                            {{ $st->status }}</option>
                                                    @endforeach
                                                    @foreach ($statuses as $st)
                                                        @php $jobInfo = _getWhere('vendor_emp_jobs', ['service_id'=> $conf->service_id])[0] @endphp
                                                        <option value="{{ $st->id }}"
                                                            {{ $jobInfo->status == $st->id ? 'selected' : '' }}>
                                                            {{ $st->status }}</option>
                                                    @endforeach
                                                </select>
                                                {{-- @endif --}}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    @endif
                                </td>

                                <td>

                                    @if ($conf->accepted_by_staff == 1)
                                        <div class="btn--container justify-content-center">
                                            <a href="{{ route('vendor.service.lead-details', [$conf->service_id]) }}"
                                                class="btn action-btn btn-sm btn--danger btn-outline-danger"
                                                title="{{ translate('messages.view') }}"><i
                                                    class="tio-visible-outlined"></i>
                                            </a>
                                            <a href="{{ route('vendor.service.gatepass-details', [$conf->service_id]) }}"
                                                style="padding: 0px 20px !important; width: fit-content;"
                                                class="btn action-btn btn-sm btn--warning btn-outline-warning"
                                                title="{{ translate('messages.view') }}">Gatepass
                                            </a>
                                            <a href="{{ route('vendor.service.quotations', [$conf->service_id]) }}"
                                                style="padding: 0px 20px !important; width: fit-content;"
                                                class="btn action-btn btn-sm btn--primary btn-outline-primary"
                                                title="{{ translate('messages.view') }}">Quotation
                                            </a>
                                        </div>
                                    @else
                                        <div class="btn--container">
                                            <a href="{{ route('vendor.service.task', [$conf->service_id, 'accept', $conf->id]) }}"
                                                style="padding: 0px 20px !important; width: fit-content;"
                                                class="btn action-btn btn-sm  btn-outline-success">Accept
                                            </a>
                                            <a href="{{ route('vendor.service.task', [$conf->service_id, 'reject', $conf->id]) }}"
                                                style="padding: 0px 20px !important; width: fit-content;"
                                                class="btn action-btn btn-sm btn--danger btn-outline-danger">Reject
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            <button type="button"
                                class="invisible btn btn-primary start_job_btn start btn_{{ $conf->service_id }}"
                                data-value="{{ $conf->id }}" data-toggle="modal"
                                data-target="#jobModal_{{ $conf->id }}">Start Job</button>

                            <div class="modal fade modal_{{ $conf->service_id }}" id="jobModal_{{ $conf->id }}"
                                tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="container py-5">
                                                <h2 class="text-center">Enter OTP</h2>
                                                <div class="p-5 bg-light rounded" style="max-width: 550px; margin: 0 auto;">
                                                    <div class="row ">
                                                        <form class="otpForm" style="margin: 0 auto;"
                                                            action="{{ route('vendor.lead.job-otp-verify2') }}"
                                                            method="post">
                                                            <p>OTP has been sent to customer. Please verify.</p>
                                                            @csrf
                                                            <input type="hidden" name="status"
                                                                id="sttas_{{ $conf->service_id }}" value="">
                                                            <input type="hidden" name="acc_id" id=""
                                                                value="{{ $conf ? $conf->id : 0 }}">
                                                            <input type="hidden" name="phone" id="ver_phone"
                                                                value="{{ $conf->phone }}">
                                                            <input type="hidden" name="action" id="job_action"
                                                                value="">
                                                            <div class="d-flex justify-content-center w-100">
                                                                <input type="text" maxlength="1" class="otp-input"
                                                                    name="otp[]" />
                                                                <input type="text" maxlength="1" class="otp-input"
                                                                    name="otp[]" />
                                                                <input type="text" maxlength="1" class="otp-input"
                                                                    name="otp[]" />
                                                                <input type="text" maxlength="1" class="otp-input"
                                                                    name="otp[]" />
                                                            </div>
                                                            <input type="hidden" value="1" name="ajax" />
                                                            <button type="submit"
                                                                class="btn btn-lg btn-block btn--primary mt-3">Submit</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>

                <hr>

                <div class="page-area">
                </div>
                @if (!count($assignedServices))
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
        function cancelLead(serviceId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to cancel this lead?',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.service.cancel') }}',
                        data: {
                            service_id: serviceId
                        },
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            if (data.status) {
                                toastr.success(data.message);
                            } else {
                                toastr.error(data.message);
                            }
                            setTimeout(() => {

                                window.location.reload()
                            }, 1000)
                        },
                        complete: function() {
                            $('#loading').hide();
                        },
                    });
                }
            })
        }

        function sendOtp(status, service_id) {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('vendor.lead.change-job-status') }}",
                data: {
                    service_id: service_id,
                    status: status
                },
                success: function(data) {},
            });
        }

        function verifyOtp() {

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
                        toastr.success(data.msg);
                        setTimeout(() => {
                            window.location.reload();
                        }, 500)
                    } else {
                        toastr.error(data.msg);

                    }
                },
                complete: function() {
                    $('#loading').hide()
                }
            });

        })

        function changeStatus(status, service_id) {
            if (status == 12 || status == 14) {
                $(".btn_" + service_id).click()
                $('#sttas_' + service_id).val(status)
                if (sendOtp(status, service_id)) {
                    if (verifyOtp()) {
                        updateStatus(status, service_id);
                    }
                };

            } else {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to change status?",
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: 'No',
                    confirmButtonText: 'Yes',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        updateStatus(status, service_id);
                    }
                })
            }
        }

        function updateStatus(status, service_id) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.service.change-status') }}',
                data: {
                    status: status,
                    service_id: service_id
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    if (data.status) {
                        toastr.success(data.message);
                    } else {
                        toastr.error(data.message);

                    }
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
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
        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });
    </script>
@endpush
