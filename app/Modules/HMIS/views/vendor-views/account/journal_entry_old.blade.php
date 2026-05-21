@extends('layouts.vendor.app')

@section('title', 'Add New')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Add New </h1>
                <p>If you enter the purchase and sale bill, that causes double entry</p>
            </div>
            <div class="page-header-select-wrapper">
                <button type="button" class="btn btn-primary mx-1" data-toggle="modal" data-target="#journalEntryModal">
                    + Journal Entry
                </button>
                <button type="button" class="btn btn-primary mx-1" data-toggle="modal" data-target="#variationModal">
                    + Add Category
                </button>

                {{-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ledgerAccountModal">
                    + Add Leadger Account Type
                </button>
                <div class="modal fade" id="ledgerAccountModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Add Ledger Account Type</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form enctype="multipart/form-data" class="w-100"
                                action="{{ route('vendor.account.ledger_account_type.store') }}" method="post">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-row ">
                                        <label for="exampleInputEmail1">Leadger Account Type</label>
                                        <input type="text" name="name" required placeholder="Name"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div> --}}
            </div>
        </div>
        <!-- End Page Header -->


        @if (session()->has('msg'))
            <div class="alert alert-success" role="alert">
                {{ session('msg') }}
            </div>
        @endif

        <div class="row ">

        </div>
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
                        <th class="border-0">Type</th>
                        <th class="border-0">Description</th>
                        <th class="border-0">Company / Person name</th>
                        <th class="border-0">Amount</th>
                        <th class="border-0">Status</th>
                        {{-- <th class="border-0">Asset</th>
                        <th class="border-0">Category</th>
                        <th class="border-0">Payment Mode</th>
                        <th class="border-0">Bill Number / Details</th>
                        <th class="border-0">Additional Note</th>
                        <th class="border-0">Document</th>
                        <th class="border-0">GST Amount</th>
                        <th class="border-0">Purpose</th>
                        <th class="border-0">Added By</th> --}}
                        <th class="border-0">Created At</th>
                        <th class="text-center border-0">{{ translate('messages.action') }}</th>
                    </tr>
                </thead>

                <tbody id="set-rows">
                    @foreach ($account as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $lead->date }}
                                </span>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $lead->type == 'expense' ? 'Debit Note / ' : 'Credit Note / ' }}{{ ucfirst($lead->type) }}
                                </span>
                            </td>
                            <td>
                                {{ ucfirst($lead->description) }}
                            </td>
                            <td>
                                {{ ucfirst($lead->storeCustomer?->f_name . ' ' . $lead->storeCustomer?->l_name) }} <br>
                                {{ ucfirst($lead->storeCustomer?->phone) }}
                            </td>
                            <td>
                                {{ $lead->amount }}
                            </td>
                            <td>
                                @if ($lead->status == 'pending')
                                    <span class="badge badge-soft-danger ml-sm-3 form-alert cursor-pointer"
                                        data-id="category-{{ $lead['id'] }}"
                                        data-message="{{ translate('Want to mark this as paid') }}"
                                        title="{{ translate('messages.mark_as_paid') }}">
                                        {{ translate('messages.unpaid') }}
                                    </span>

                                    <form action="{{ route('vendor.account.mark-as-paid', [$lead['id']]) }}" method="get"
                                        id="category-{{ $lead['id'] }}">
                                        @csrf @method('get')
                                    </form>
                                @else
                                    <span class="badge badge-soft-success ml-sm-3">
                                        {{ translate('messages.paid') }}
                                    </span>
                                @endif
                            </td>
                            {{-- <td>
                                <img class="avatar avatar-lg mr-3 onerror-image"
                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($lead->storeAsset?->inventoryItem?->image, asset('storage/app/public/inventory-item/') . '/' . $lead->storeAsset?->inventoryItem?->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                    alt="{{ $lead->storeAsset?->inventoryItem?->item_name }} image">
                                {{ $lead->storeAsset?->inventoryItem?->item_name }}
                            </td>


                            <td>
                                {{ ucfirst($lead->category) }}
                            </td>

                            <td>
                                {{ ucfirst($lead->payment_mode) }}
                            </td>

                            <td>
                                {{ $lead->bill_numer }}
                            </td>
                            <td>
                                {{ $lead->additional_note }}
                            </td>
                            <td>
                                @if ($lead->document)
                                    <a href="{{ asset('storage/app/public/documents/' . $lead->document) }}"
                                        target="_blank">View</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                {{ $lead->gst_amount }}
                            </td>
                            <td>
                                {{ $lead->purpose }}
                            </td>
                            <td>
                                @php($uDet = _getUserDetails($lead->user_type_id, $lead->user_type))
                                {{ $lead->user_type == 'vendor' ? 'Self' : $uDet?->f_name . ' ' . $uDet?->l_name . ' (Staff)' }}
                            </td> --}}
                            <td>{{ $lead->created_at }}</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fa-solid fa-bars"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        @if ($lead->status == 'pending')
                                            <a href="javascript:;" class="dropdown-item form-alert text-success"
                                                title="{{ translate('messages.Mark as Paid') }}"> Mark as Paid
                                            </a>
                                            <form action="{{ route('vendor.account.mark-as-paid', [$lead['id']]) }}"
                                                method="get" id="category-{{ $lead['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                        @endif
                                        <a href="{{ route('vendor.account.delete', [$lead->id]) }}"
                                            class="dropdown-item  text-danger" title="{{ translate('messages.Delete') }}">
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if (count($account))
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
    </div>
    <!-- Button trigger modal -->

    <div class="modal fade" id="journalEntryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Journal Entry</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body ">
                    <form enctype="multipart/form-data" class="w-100 p-0" action="{{ route('vendor.account.save') }}"
                        method="post">
                        @csrf
                        <input type="hidden" id="staff_id" name="account_id" value="">
                        <div class="col-md-12 p-0">
                            <div class="card h-100">
                                <div class="card-body row">
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Company / Person Name, Phone <span
                                                class="text-danger">*</span></label>
                                        <select required name="customer_id" id="customer_id"
                                            class="form-control js-select2-custom ">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            <option value="add_new">Add New</option>
                                            @foreach ($customers as $cust)
                                                <option value="{{ $cust['id'] }}">
                                                    {{ $cust['f_name'] . ' ' . $cust['l_name'] . ' | ' . $cust['phone'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Asset (Optional)</label>
                                        <select name="asset_id" id="asset_id" class="form-control js-select2-custom ">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            @foreach ($assets as $asset)
                                                <option value="{{ $asset->id }}">
                                                    {{ $asset->inventoryItem?->item_name . ' | ' . $asset->inventoryItem?->brand . ' | ' . $asset->inventoryItem?->model_number }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Date <span class="text-danger">*</span></label>
                                        <input type="date" value="{{ date('Y-m-d') }}" required name="date"
                                            class="form-control">
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Type <span class="text-danger">*</span></label>
                                        <select required name="type" class="form-control js-select2-custom">
                                            <option value="income">Credit Note / Income</option>
                                            <option value="expense">Debit Note / Expense</option>
                                        </select>
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Description</i> </label>
                                        <input type="text" name="description" placeholder="Description"
                                            class="form-control">
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Category <span
                                                class="text-danger">*</span></label>
                                        <select required name="category" id="category"
                                            class="form-control js-select2-custom ">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            @foreach ($categories as $cat)
                                                <option data-value="{{ $cat['parent_id'] }}"
                                                    value="{{ $cat['name'] }}">
                                                    {{ $cat['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Amount <span class="text-danger">*</span></label>
                                        <input type="number" name="amount" required placeholder="Amount"
                                            class="form-control">
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">GST Amount </label>
                                        <input type="number" name="gst_amount" placeholder="GST Amount" step="0.001"
                                            class="form-control">
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Payment Mode <span
                                                class="text-danger">*</span></label>
                                        <select name="payment_mode" required class="form-control js-select2-custom">
                                            <option value="" selected disabled>--select--</option>
                                            <option value="bank">Bank</option>
                                            <option value="upi">UPI</option>
                                            <option value="cash">Cash</option>
                                        </select>
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Status <span class="text-danger">*</span></label>
                                        <select name="status" required class="form-control js-select2-custom">
                                            <option value="completed">Paid</option>
                                            <option value="pending">Unpaid</option>
                                        </select>
                                    </div>
                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Additional Note <i>(Optional)</i></label>
                                        <textarea name="note" placeholder="Additional Note" class="form-control"></textarea>
                                    </div>

                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Document <i>(Optional)</i></label>
                                        <input type="file" name="file" id="" class="form-control">
                                    </div>

                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Bill Number / Details</label>
                                        <input type="text" name="bill_number" placeholder="Bill Number / Details"
                                            class="form-control">
                                    </div>

                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Ledger Account Type <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group-append">
                                            <select style="pointer-events:none;" data-placeholder="Ledger Account Type"
                                                required name="ledger_account_type" id="ledger_account_type2"
                                                class="form-control js-select2-custom ">
                                                <option value=""></option>
                                                @foreach ($ledger_account_types as $type)
                                                    <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                                @endforeach
                                            </select>
                                            <select required name="subledger_account_type"
                                                data-placeholder="Subledger Account Type" id="subledger_account_type2"
                                                class="form-control js-select2-custom-tags  ">
                                                <option value=""></option>
                                                @foreach ($data['subledger_account_types'] as $type)
                                                    <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row col-md-3 col-sm-6">
                                        <label for="exampleInputEmail1">Purpose<span class="text-danger">*</span></label>
                                        <select required name="purpose" id="purpose"
                                            class="form-control js-select2-custom-tags ">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            @foreach ($puposes as $type)
                                                <option value="{{ $type['name'] }}">{{ $type['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-row col-12 d-flex justify-content-end w-100">
                                        <button class="btn  btn--primary btn-outline-primary">Save</button>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="variationModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form enctype="multipart/form-data" class="w-100" action="{{ route('vendor.account.category.store') }}"
                    method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-row ">
                            <label for="exampleInputEmail1">Name</label>
                            <input type="text" name="name" required placeholder="Name" class="form-control">
                        </div>
                        <div class="form-row">
                            <label for="exampleInputEmail1">Leadger Account Type <span
                                    class="text-danger">*</span></label>
                            <select required name="ledger_account_type" id="ledger_account_type1"
                                class="form-control js-select2-custom ">
                                <option value="">---{{ translate('messages.select') }}---</option>
                                @foreach ($ledger_account_types as $type)
                                    <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $("#category").on('change', function() {
            const selectedValue = $('#category option:selected').data('value');
            console.log(selectedValue)
            $("#ledger_account_type2").val(selectedValue).trigger('change'); // not .select2()
        });
        $("#customer_id").on('change', function() {
            if ($(this).val() == 'add_new') {
                $('#addCustomerModal').modal('show')
            }
        })
    </script>
@endpush
