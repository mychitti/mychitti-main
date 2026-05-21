@extends('layouts.vendor.app')

@section('title', translate('Quotation List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @media (max-width:550px) {

            .mini-date {
                width: 30px;
                padding: 5px;
            }
        }

        .word-wrap {
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Quotation<span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($quotes) }}</span></h1>

        </div>
        <!-- End Page Header -->
        <!-- Transaction Information -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <form action="" class="d-flex">
                        <input type="date" value="{{ $from }}" name="from" onchange="this.form.submit()"
                            class="form-control mx-1 mini-date">
                        <input type="date" value="{{ $to }}" name="to" onchange="this.form.submit()"
                            class="form-control mx-1 mini-date">
                        <select class="form-control mx-1" name="status" onchange="this.form.submit()">
                            <option {{ $status == 'All' ? 'selected' : '' }} value="All">All</option>
                            <option {{ $status == 'New' ? 'selected' : '' }} value="New">New</option>
                            <option {{ $status == 'Accepted' ? 'selected' : '' }} value="Accepted">Accepted</option>
                            <option {{ $status == 'Declined' ? 'selected' : '' }} value="Declined">Declined</option>
                            <option {{ $status == 'Completed' ? 'selected' : '' }} value="Completed">Completed</option>
                        </select>
                        {{-- <button class="btn btn-primary"><i class="tio-filter-outlined"></i></button> --}}
                    </form>
                    <small class="px-2">
                        From {{ $from }} to {{ $to }}
                    </small>
                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->
            @if (hasPermission('quotaiton_manage', 'list'))

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
                                <th class="border-0">Quotation Number</th>
                                <th class="border-0">Subject</th>
                                <th class="border-0">Client Info</th>
                                <th class="text-uppercase border-0">Status</th>
                                <th class="text-uppercase border-0">Created at</th>
                                <th class="text-center border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($quotes as $quote)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $quote->quotation_id }}</td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ $quote->subject }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="" style="width: 225px;    white-space: normal;">
                                            <a href="#" class="table-rest-info" alt="view store">
                                                <div class="info">
                                                    <div class="text--title ">
                                                        {{ $quote->storeCustomer?->f_name . ' ' . $quote->storeCustomer?->l_name }}
                                                    </div>
                                                    <div class="font-light">
                                                        {{ $quote->client_mobile }}
                                                    </div>
                                                    <div class="font-light">
                                                        {{ $quote->client_email }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>


                                    <td>
                                        @if ($quote->status == 'Converted to Bill')
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                Converted to Bill
                                            </span>
                                        @else
                                            @if (hasPermission('quotaiton_manage', 'status_change'))
                                                <form id="status-form{{ $quote->id }}" method="post"
                                                    action="{{ route('vendor.quotation.status-change') }}">
                                                    @csrf
                                                    <input type="hidden" name="lead_id" value="{{ $quote->id }}">
                                                    <select name="status" class="form-control js-select2-custom"
                                                        onchange="changeStatus({{ $quote->id }}, this)">
                                                        <option value="New"
                                                            {{ $quote->status == 'New' ? 'selected' : '' }}>New
                                                        </option>
                                                        <option value="Accepted"
                                                            {{ $quote->status == 'Accepted' ? 'selected' : '' }}>
                                                            Accepted</option>
                                                        <option value="Declined"
                                                            {{ $quote->status == 'Declined' ? 'selected' : '' }}>
                                                            Declined</option>
                                                        @if (hasPermission('quotation_manage', 'convert_to_bill'))
                                                            <option value="Convert to Bill"
                                                                {{ $quote->status == 'Convert to Bill' ? 'selected' : '' }}>
                                                                Convert to
                                                                Bill
                                                            </option>
                                                        @endif
                                                    </select>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $quote->created_at }}</td>
                                    <td>

                                        <div class="btn--container justify-content-center">
                                            @if (hasPermission('quotaiton_manage', 'pdf') && $quote->quote_detail?->pdf)
                                                <a href="javascript:void(0);"
                                                    onclick="openInvoicePopup('{{ asset('storage/app/public/invoice/' . $quote->quote_detail->pdf) }}')"
                                                    class="btn action-btn btn--primary btn-outline-primary"
                                                    title="{{ translate('messages.view') }}">
                                                    PDF
                                                </a>
                                            @else
                                            @endif
                                            @if (hasPermission('quotaiton_manage', 'edit') && $quote->status != 'Converted to Bill')
                                                <a class="btn action-btn btn--warning btn-outline-warning"
                                                    href="{{ route('vendor.quotation.manage', [$quote->id]) }}"
                                                    title="{{ translate('messages.edit') }}"><i class="tio-edit"></i>
                                                </a>
                                            @endif
                                            @if (hasPermission('quotaiton_manage', 'delete'))
                                                <a class="btn action-btn btn--danger btn-outline-danger"
                                                    href="{{ route('vendor.quotation.delete', [$quote->id]) }}"
                                                    title="{{ translate('messages.delete') }} Quote"><i
                                                        class="tio-delete-outlined"></i>
                                                </a>
                                            @endif

                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($quotes))
                        <hr>
                        {!! $quotes->links() !!}
                    @else
                        <div class="page-area">
                        </div>
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
                <!-- End Table -->
            @endif
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function openInvoicePopup(url) {
            window.open(
                url,
                'InvoicePreview',
                'width=800,height=600,scrollbars=no,resizable=yes'
            );
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
@endpush
