@extends('layouts.vendor.app')

@section('title', 'Monthly Maintenance')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')

    <div class="content container-fluid">

        <div class="d-flex mt-3 flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-2">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                </span>
                <span>
                    Monthly Maintenance Requests
                    <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $records->total() }}</span>
                </span>
            </h1>
            <div class="d-flex gap-1 flex-wrap flex-md-nowrap ">
                <form action="" class=" date-range-form">
                    @include('vendor-views/form_modals/date_range')
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                </form>
                <form action="" class="input-group" style="max-width: 270px;">
                    <input type="text" value="{{ request('search') ?? '' }}" name="search" class="form-control"
                        placeholder="Type, Payment Day or Title" aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">
                            <i class="tio-search"></i>
                        </button>
                    </div>
                </form>

            </div>
        </div>
        @if (hasPermission('rmf_maintenance_requests', 'list'))
            <div class="card">
                <div class="card-header py-2 justify-content-end border-0">
                    <div class="search--button-wrapper justify-content-end">

                        <!-- Unfold -->

                        <!-- End Unfold -->
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="datatable"
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Paid On</th>
                                    <th>Month</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach ($records as $k => $e)
                                    <tr>
                                        <th scope="row">{{ $k + $records->firstItem() }}</th>
                                        <td class="text-capitalize text-break">{{ $e['expense_type'] }}</td>
                                        <td>
                                            {{ $e['title'] }}
                                        </td>

                                        <td>
                                            {{ _price($e['amount']) }}
                                        </td>
                                        <td>
                                            @if ($e['due'])
                                                <span class="badge badge-soft-danger">Due</span>
                                            @else
                                                <span class="badge badge-soft-success">Clear</span>
                                            @endif
                                        </td>
                                        <td>{{ $e['paid_for'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($e['for_month'])->format('M Y') }}</td>
                                        <td>
                                            @if (auth('vendor_employee')->id() != $e['id'])
                                                <div class="btn--container ">
                                                    @if ($e['due'])
                                                        <a href="{{ route('vendor.account.maintenance.mark-paid', [$e['id']]) }}"
                                                            style="width:fit-content; padding: 0.5rem 1rem !important;"
                                                            class="btn action-btn btn--primary btn-outline-primary "
                                                            title="Mark Paid">Mark Paid
                                                        </a>
                                                    @endif

                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (count($records) !== 0)
                    <div class="card-footer">
                        <div class="page-area">
                            <table>
                                <tfoot>
                                    {!! $records->links() !!}
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
                @if (count($records) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        @endif
    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $(".edit_btn").on('click', function() {
            var id = $(this).attr('data-id')
            var title = $(this).attr('data-title')
            var amount = $(this).attr('data-amount')
            var type = $(this).attr('data-type')
            var notes = $(this).attr('data-notes')
            var payment_day = $(this).attr('data-day')
            console.log(type)

            $(".edit_id").val(id)
            $(".edit_amount").val(amount)
            $(".edit_title").val(title)
            $(".edit_notes").val(notes)
            $(".edit_payment_day").val(payment_day)

            var valueToSelect = type;

            var newOption = new Option(valueToSelect, valueToSelect, true, true);
            $(".edit_type").append(newOption).trigger('change');

        })
    </script>
    @include('vendor-views/js/date_range')
@endpush
