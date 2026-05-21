@extends('layouts.vendor.app')

@section('title', 'Master Ledger Entries')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Master Ledger Entries <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($ledger_entries) }}</span></h1>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Accounts</h5>
                    <form action="javascript:" id="search-form" class="search-form">
                        <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                placeholder="Search Here" aria-label="{{ translate('messages.search') }}" required>
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>

                    <!-- End Unfold -->
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
                            <th class="border-0">Date</th>
                            <th class="border-0">Account</th>
                            <th class="border-0">Debit</th>
                            <th class="border-0">Credit</th>
                            <th class="border-0">Narration</th>
                            <th class="border-0">Voucher No.</th>
                            <th class="border-0">Status</th>
                            <th class="text-center border-0">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($ledger_entries as $entry)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ $entry->created_at }}
                                    </span>
                                </td>
                                <td>
                                    {{ $entry->account?->name }}
                                </td>

                                <td>
                                    {{ $entry->debit }}
                                </td>
                                <td>
                                    {{ $entry->credit }}
                                </td>
                                </td>
                                <td>
                                    {{ $entry->credit }}
                                </td>
                                <td>
                                    {{ $entry->credit }}
                                </td>

                                <td>

                                    @if ($entry->status == 'pending')
                                        <span class="badge badge-soft-warning ml-sm-3">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($entry->status == 'rejected')
                                        <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.rejected') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.approved') }}
                                        </span>
                                    @endif
                                </td>
                               
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger"
                                            href="{{ route('vendor.account.delete', [$entry->id]) }}"
                                            title="{{ translate('messages.delete') }} Account"><i
                                                class="tio-delete-outlined"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($ledger_entries))
                    <hr>
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
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
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
@endpush
