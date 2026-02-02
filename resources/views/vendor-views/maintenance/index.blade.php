@extends('layouts.vendor.app')

@section('title', 'Monthly Maintenance')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')

    <div class="content container-fluid">
        <div class="page-header">
            <div class="w-100 d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                    </span>
                    <span>
                        Monthly Maintenance
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $records->total() }}</span>
                    </span>
                </h1>
                <div class="d-flex gap-1 flex-wrap flex-md-nowrap ">
                    <button class="btn btn--primary" data-toggle="modal" type="button" data-target="#howItWorksModal"
                        style="font-size: 21px !important;padding: 3px;"><i class="tio-info-outined"></i></button>

                    @if (hasPermission('boa_monthly_maintenance', 'add'))
                        <a type="button" style="    white-space: nowrap;" data-toggle="modal"
                            data-target="#addExpenseModal" class="btn_sm btn btn-primary mb-0">
                            <i class="tio-add-circle"></i>
                            <span class="text">New Expense</span>
                        </a>
                    @endif
                    @if (hasPermission('boa_monthly_maintenance', 'export'))
                        <a href="{{ route('vendor.account.maintenance.export') }}" class="btn btn_sm btn-outline-primary ">
                            Export
                        </a>
                    @endif
                    @if (hasPermission('boa_monthly_maintenance', 'import'))
                        <a data-toggle="modal" data-target="#importExcelModal"
                            class="btn btn-outline-primary btn_sm">Import</a>
                    @endif
                    @if (hasPermission('boa_monthly_maintenance', 'settings'))
                        <a class="btn btn-outline-dark" type="button" data-toggle="modal" data-target="#mmSettingsModal"
                            title="Settings"><span class="tio-settings nav-icon"></span>
                        </a>
                    @endif
                </div>

            </div>
        </div>
        <!-- Page Heading -->
        @if (hasPermission('boa_monthly_maintenance', 'list'))

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
                                    <th>Created At</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Amount</th>
                                    <th>Payment Day</th>
                                    <th>Duration Type</th>
                                    <th>Start Month</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach ($masters as $k => $e)
                                    <tr>
                                        <th scope="row">{{ $k + $records->firstItem() }}</th>
                                        <th scope="row">{{ $e['created_at'] }}</th>
                                        <td class="text-capitalize text-break">{{ $e['expense_type'] }}</td>
                                        <td>
                                            {{ $e['title'] }}
                                        </td>
                                        <td>
                                            {{ _price($e['amount']) }}
                                        </td>
                                        <td>{{ $e['payment_day'] }}</td>
                                        <td>{{ $e['duration_type'] }}</td>
                                        <td>{{ $e['start_month'] }}</td>
                                        <td>
                                            @if ($e['stts'] == 'No dues')
                                                <span class="badge badge-soft-success">No dues</span>
                                            @else
                                                <span class="badge badge-soft-danger">{{ $e['stts'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="d-block font-size-sm text-body">
                                                {{ Str::limit($e['notes'], 200, '...') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn p-1 dropdown-toggle" type="button"
                                                    data-toggle="dropdown" aria-expanded="false">
                                                    <img style="width: 24px; filter: contrast(0)"
                                                        src="{{ asset('storage/app/public/util/10025520.png') }}"
                                                        alt="action" />
                                                </button>
                                                <div class="dropdown-menu">
                                                    @if (hasPermission('boa_monthly_maintenance', 'edit'))
                                                        <a class="dropdown-item text-warning edit_btn"
                                                            data-id="{{ $e['id'] }}"
                                                            data-amount = "{{ $e['amount'] }}"
                                                            data-type = "{{ $e['expense_type'] }}"
                                                            data-day = "{{ $e['payment_day'] }}"
                                                            data-title = "{{ $e['title'] }}"
                                                            data-notes = "{{ $e['notes'] }}" type="button"
                                                            data-toggle="modal" data-target="#editExpenseModal"
                                                            title="Edit Monthly Maintenance"><i class="tio-edit"></i> Edit
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('boa_monthly_maintenance', 'delete'))
                                                        <a class="dropdown-item text-danger form-alert" href="javascript:"
                                                            data-id="employee-{{ $e['id'] }}"
                                                            data-message="{{ translate('messages.Want_to_delete_this_maintenance_record') }}"
                                                            title="{{ translate('messages.delete_maintenance_record') }}"><i
                                                                class="tio-delete-outlined"></i> Delete
                                                        </a>
                                                    @endif
                                                </div>
                                                @if (hasPermission('boa_monthly_maintenance', 'delete'))
                                                    <form
                                                        action="{{ route('vendor.account.maintenance.delete', [$e['id']]) }}"
                                                        method="post" id="employee-{{ $e['id'] }}">
                                                        @csrf @method('delete')
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (count($masters) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>

            <div class="d-flex mt-3 flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                    </span>
                    <span>
                        Monthly Maintenance Entries
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $records->total() }}</span>
                    </span>
                </h1>
                <div class="d-flex gap-1 flex-wrap flex-md-nowrap ">
                    <form action="">
                        <select onchange="this.form.submit()" name="status" id="status"
                            class="form-control js-select2-custom">
                            <option {{ request('status') == '' ? 'selected' : '' }} value="">All</option>
                            <option {{ request('status') == 'due' ? 'selected' : '' }} value="due">Due</option>
                            <option {{ request('status') == 'clear' ? 'selected' : '' }} value="clear">Clear</option>
                            <option {{ request('status') == 'hold' ? 'selected' : '' }} value="hold">Hold</option>
                            <option {{ request('status') == 'cancel' ? 'selected' : '' }} value="cancel">Cancel</option>
                        </select>
                    </form>
                    <form action="" class=" date-range-form">
                        @include('vendor-views/form_modals/date_range')
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                            type="button" data-toggle="modal"
                            data-target="#dateRangeModal">{{ translate($preset) }}</button>
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
                                            @if ($e['due'])
                                                <div class="price-container d-flex align-items-center"
                                                    data-id="{{ $e['id'] }}">
                                                    <span class="price-text">{{ _price($e['amount']) }}</span>
                                                    <input type="text" class="price-input form-control"
                                                        value="{{ $e['amount'] }}" style="display:none; width:100px;">
                                                    <a href="javascript:void(0);" class="edit-price"><i
                                                            class="tio-edit"></i></a>
                                                    <a href="javascript:void(0);" class="save-price"
                                                        style="    font-size: 17px;
display:none;"><i
                                                            class="tio-checkmark-square-outlined"></i></a>
                                                </div>
                                            @else
                                                {{ _price($e['amount']) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($e['due'])
                                                <span class="badge badge-soft-danger">Due</span>
                                            @else
                                                @if ($e['status'] == 'hold')
                                                    <span class="badge badge-soft-warning">Hold</span>
                                                @elseif ($e['status'] == 'cancel')
                                                    <span class="badge badge-soft-danger">Cancelled</span>
                                                @else
                                                    <span class="badge badge-soft-success">Clear</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ $e['paid_for'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($e['for_month'])->format('M Y') }}</td>
                                        <td>
                                            @if (hasPermission('boa_monthly_maintenance', 'mark_paid'))
                                                @if (auth('vendor_employee')->id() != $e['id'])
                                                    <div class="dropdown">
                                                        <button class="btn p-1 dropdown-toggle" type="button"
                                                            data-toggle="dropdown" aria-expanded="false">
                                                            <img style="width: 24px; filter: contrast(0)"
                                                                src="{{ asset('storage/app/public/util/10025520.png') }}"
                                                                alt="action" />
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            @if ($e['due'])
                                                                @if (hasPermission('boa_monthly_maintenance', 'edit'))
                                                                    <a class="dropdown-item text-primary"
                                                                        href="{{ route('vendor.account.maintenance.mark-paid', [$e['id']]) }}"
                                                                        title="Mark Paid"><i class="tio-edit"></i> Mark
                                                                        Paid
                                                                    </a>
                                                                    <a class="dropdown-item text-warning"
                                                                        href="{{ route('vendor.account.maintenance.status', [$e['id'], 'hold']) }}"
                                                                        title="Hold"><i class="tio-pause"></i> Hold
                                                                    </a>
                                                                    <a class="dropdown-item text-danger"
                                                                        href="{{ route('vendor.account.maintenance.status', [$e['id'], 'cancel']) }}"
                                                                        title="Cancel"><i class="tio-clear"></i> Cancel
                                                                    </a>
                                                                @endif
                                                            @endif

                                                        </div>
                                                    </div>
                                                @endif
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
    <div class="modal fade" id="howItWorksModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h2 id="maintenance-heading" style="margin-top:0; font-size:1.25rem;">How Monthly Maintenance Works
                    </h2>

                    <ol style="padding-left:1.2rem; margin:12px 0 16px 0; line-height:1.6;">
                        <li style="margin-bottom:12px;">
                            <h3 style="margin:0 0 6px 0; font-size:1rem;">Add Monthly Maintenance</h3>
                            <p style="margin:0;">
                                Create a monthly maintenance record and set a <strong>payment due date</strong>. The due
                                date determines when the maintenance charge will be generated.
                            </p>
                        </li>

                        <li style="margin-bottom:12px;">
                            <h3 style="margin:0 0 6px 0; font-size:1rem;">Automatic Entry on Due Date</h3>
                            <p style="margin:0;">
                                On the due date the system automatically creates a <strong>monthly maintenance
                                    entry</strong> and records it in the <em>master ledger</em> with status
                                <strong>Pending</strong>. This shows the amount is due but not yet received.
                            </p>
                        </li>

                        <li style="margin-bottom:12px;">
                            <h3 style="margin:0 0 6px 0; font-size:1rem;">Mark as Paid</h3>
                            <p style="margin:0;">
                                When the payment is received, mark the maintenance entry as <strong>Paid</strong>. The entry
                                in the master ledger is then updated to <strong>Completed</strong>, confirming the payment
                                has been recorded.
                            </p>
                        </li>
                    </ol>

                    <p style="margin-top:12px; color:#555; font-size:0.85rem;">
                        <em>Optional:</em> Monthly maintenance entries can be set to “Auto Mark as Paid” or “Manual Mark as
                        Paid”. You can configure this in the <a class="text-underline"
                            href="{{ route('vendor.account.setting.common-settings') }}">Settings Page</a>
                    </p>
                </div>

            </div>
        </div>
    </div>
    @if (hasPermission('boa_monthly_maintenance', 'import'))
        <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                        <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('vendor.account.maintenance.import') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <a href="{{ asset('storage/app/public/util/monthly_maintanance_example.xlsx') }}" download
                                class="btn btn-outline-primary mb-2">View Example</a>
                            <div class="form-group">
                                <label for="file">Upload Excel File</label>
                                <input type="file" style="height: 46px !important;" name="file"
                                    class="form-control" id="file" accept=".xlsx,.xls">
                            </div>
                            <div class="form-group w-100 ">
                                <button type="submit" class="btn btn-primary float-right">Import</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @endif
    @if (hasPermission('boa_monthly_maintenance', 'add'))

        <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add New Expense</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('vendor.account.maintenance.store') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="pos--payment-options mb-3 ">
                                <ul style="flex-wrap: nowrap;">
                                    <li>
                                        <label>
                                            <input type="radio" name="duration_type" class="duration_type"
                                                value="monthly" hidden checked>
                                            <span class="size_span">Monthly</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="duration_type" class="duration_type"
                                                value="quarterly" hidden>
                                            <span class="size_span">Quarterly</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="duration_type" class="duration_type"
                                                value="yearly" hidden>
                                            <span class="size_span">Yearly</span>
                                        </label>
                                    </li>
                                </ul>
                            </div>

                            <label for="expense_type1">Expense Type <a
                                    href="{{ route('vendor.account.setting.common-settings') }}"
                                    class="text-primary text-underline">Edit options <i class="tio-edit"></i></a></label>
                            <select name="expense_type" id="expense_type1" class="form-control js-select2-custom-tags"
                                required>
                                <option value="">Select Expense Type</option>
                                @foreach ($expense_types as $type)
                                    <option value="{{ $type->name }}">{{ $type->name }}</option>
                                @endforeach
                            </select>

                            <label for="title">Narration</label>
                            <input class="form-control" name="title" placeholder="Title" required>

                            <label for="amount">Amount</label>
                            <input class="form-control" type="number" step="0.001" name="amount"
                                placeholder="Amount" required>


                            <label for="payment_day">Payment Day <i class="tio-info-outlined"
                                    title="Select the day of the month for payment"></i></label>
                            <input type="number" name="payment_day" class="form-control" min="1" max="31"
                                required placeholder="Day (1-31)"
                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value > 31) this.value = 31; if(this.value < 1) this.value = '';">

                            <div class="start_month" style="display: none;">
                                <label for="start_month">Start Month</label>
                                <input type="number" name="start_month" class="form-control" id="start_month"
                                    placeholder="Start Month" min="1" max="12">
                            </div>

                            <label for="notes">Notes</label>
                            <textarea class="form-control" name="notes" placeholder="Notes"></textarea>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @if (hasPermission('boa_monthly_maintenance', 'settings'))

        <div class="modal fade" id="mmSettingsModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Settings</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('vendor.business-settings.config.save') }}">
                        @csrf
                        <div class="modal-body">
                            <label for="expense_type">Reminder Start (Days Before Due Date) <i class="tio-info-outlined"
                                    title="e.g. if due date is 10th, you select 2 days before, so the reminder starts on 8th"></i></label>
                            <select name="reminder_day_before" id="day_before" data-placeholder="e.g., 2 days before"
                                class="edit_type form-control js-select2-custom" required>
                                <option value=""></option>
                                @for ($i = 1; $i < 11; $i++)
                                    <option {{ $data['day_before'] == $i ? 'selected' : '' }}
                                        value="{{ $i }}">
                                        {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @if (hasPermission('boa_monthly_maintenance', 'edit'))

        <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Expense</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('vendor.account.maintenance.update') }}">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="id" class="edit_id">
                            <label for="expense_type">Expense Type</label>
                            <select name="expense_type" id="expense_type"
                                class="edit_type form-control js-select2-custom-tags" required>
                                <option value="">Select Expense Type</option>
                                @foreach ($expense_types as $type)
                                    <option value="{{ $type->name }}">{{ $type->name }}</option>
                                @endforeach
                            </select>

                            <label for="title">Narration</label>
                            <input class="form-control edit_title" name="title" placeholder="Title" required>

                            <label for="amount">Amount</label>
                            <input class="form-control edit_amount" type="number" step="0.001" name="amount"
                                placeholder="Amount" required>

                            <label for="payment_day">Payment Day</label>
                            <input type="number" name="payment_day" class="form-control edit_payment_day"
                                min="1" max="31" required placeholder="Day (1-31)"
                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value > 31) this.value = 31; if(this.value < 1) this.value = '';">


                            <label for="notes">Notes</label>
                            <textarea class="edit_notes form-control" name="notes" placeholder="Notes"></textarea>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $(".duration_type").on('change', function() {
            var selectedType = $(this).val();
            if (selectedType === 'monthly') {
                $('.start_month').hide();
            } else  {
                $('.start_month').show();
            } 
        })
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
    <script>
        $(document).ready(function() {

            // Click edit icon
            $('.edit-price').click(function() {
                var parent = $(this).closest('.price-container');
                parent.find('.price-text').hide();
                parent.find('.edit-price').hide();
                parent.find('.price-input').show().focus();
                parent.find('.save-price').show();
            });

            // Click save icon
            $('.save-price').click(function() {
                var parent = $(this).closest('.price-container');
                var newPrice = parent.find('.price-input').val();
                var id = parent.data('id');

                $.ajax({
                    url: "{{ route('vendor.account.maintenance.update-entry-price') }}", // your route
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        amount: newPrice
                    },
                    success: function(response) {
                        if (response.success) {
                            parent.find('.price-text').text('₹' + parseFloat(newPrice)
                                .toLocaleString());
                        }
                        parent.find('.price-text').show();
                        parent.find('.edit-price').show();
                        parent.find('.price-input').hide();
                        parent.find('.save-price').hide();
                    },
                    error: function(err) {
                        alert('Error saving price!');
                    }
                });
            });

        });
    </script>

    @include('vendor-views/js/date_range')
@endpush
