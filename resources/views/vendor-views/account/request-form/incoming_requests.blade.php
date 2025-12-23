@extends('layouts.vendor.app')

@section('title', 'Incoming Requests')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        {{-- .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }


        thead th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f8faff;
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody td {
            padding: 20px 15px;
            color: #1e293b;
            font-size: 14px;
        } --}} .req-number {
            font-weight: 700;
            color: #667eea;
            font-size: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fecaca;
            color: #991b1b;
        }

        .amount {
            font-weight: 700;
            font-size: 16px;
            color: #667eea;
        }

        .type-badge {
            display: inline-block;
            padding: 5px 12px;
            background: #f1f5f9;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
        }

        .doc-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }

        .doc-link:hover {
            text-decoration: underline;
        }

        .dept-info {
            font-size: 13px;
            color: #64748b;
        }

        .date-time {
            font-size: 13px;
            color: #64748b;
            white-space: nowrap;
        }

        .store-id {
            display: inline-block;
            padding: 4px 10px;
            background: #e0e7ff;
            border-radius: 6px;
            font-weight: 600;
            color: #4338ca;
            font-size: 13px;
        }

        .description {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .footer {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 40px;
            margin-top: 2px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .header {
                padding: 25px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            thead th,
            tbody td {
                padding: 15px 10px;
                font-size: 12px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Incoming Requests</h1>
            </div>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->
        <!-- Button trigger modal -->

        <!-- Modal -->

        <div class="table-container">
            <div class="table-wrapper">
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
                                <th>Sl.</th>
                                <th>Request #</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Date</th>
                                {{-- <th>Request From</th>
                                <th>Request To</th> --}}
                                <th>Origin</th>
                                <th>Forwarded To</th>
                                <th>Description</th>
                                <th>Document</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['submitted_req'] as $key => $req)
                                @php $created_by = _getCreatedBy($req->created_by, $req->created_by_type);@endphp
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td><span class="req-number">#{{ $req->request_number }}</span></td>
                                    <td><span class="type-badge">{{ translate($req->type) }}</span></td>
                                    <td>
                                        <span
                                            class="status-badge status-{{ $req->status }}">{{ ucfirst($req->status) }}</span>
                                    </td>
                                    <td><span class="amount">{{ _price($req->amount) }}</span></td>
                                    <td class="date-time">{{ $req->date }}</td>

                                    <td class="dept-info">
                                        <b>Requested By : </b>
                                        {{ $req->requestedBy?->f_name . ' ' . $req->requestedBy?->l_name }}
                                        <br>
                                        <b>Requested To : </b>
                                        {{ $req->requestedTo?->f_name . ' ' . $req->requestedTo?->l_name }} <br>
                                        <b>Credit Account : </b>
                                        {{ $req->creditAccount?->name }} <br>
                                        <b>Debit Account : </b>
                                        {{ $req->debitAccount?->name }} <br>
                                    </td>
                                    <td class="dept-info">
                                        {{ $req->forwardedTo?->f_name . ' ' . $req->forwardedTo?->l_name }}</td>
                                    {{-- <td>31</td> --}}
                                    <td><span class="description"
                                            title="{{ $req->description }}">{{ $req->description }}</span></td>
                                    <td>
                                        @if ($req->doc_file)
                                            <a href="{{ asset('storage/app/public/store/documents/' . $req->doc_file) }}"
                                                target="_blank" class="doc-link">📎 {{ $req->doc_file }}</a>
                                        @endif
                                    </td>
                                    <td class="date-time">{{ $created_by }}</td>
                                    <td class="date-time">{{ $req->created_at }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <img style="width: 24px; filter: contrast(0)"
                                                    src="{{ asset('storage/app/public/util/10025520.png') }}"
                                                    alt="action" />
                                            </button>
                                            <div class="dropdown-menu">
                                                <a href="{{ route('vendor.account.request-form.incoming_rf_details', [$req->id]) }}"
                                                    title="{{ translate('messages.view') }}"
                                                    class="dropdown-item text-warning close_btn">
                                                    <i class="tio-visible"></i> View
                                                </a>
                                                @if ($req->status != 'rejected' && $req->status != 'closed')
                                                    @php $permissions = _formWiseRulePermissions($req->type);@endphp
                                                    @if (in_array('close', $permissions))
                                                        <a data-toggle="modal"
                                                            data-target="#requestCloseModal{{ $req->id }}"
                                                            title="{{ translate('messages.close_request') }}"
                                                            class="dropdown-item text-success close_btn">
                                                            <i class="tio-checkmark-circle-outlined"></i> Close
                                                        </a>
                                                    @endif
                                                    @if (in_array('reject', $permissions))
                                                        <a href="javascript:" data-id="category-{{ $req['id'] }}"
                                                            data-message="{{ translate('Want to reject this request') }}"
                                                            title="{{ translate('messages.reject_request') }}"
                                                            class="dropdown-item text-danger form-alert">
                                                            <i class="tio-clear-circle-outlined"></i> Reject
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.account.request-form.incoming-requests-reject', [$req->id]) }}"
                                                            method="get" id="category-{{ $req['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                    @if (in_array('approve', $permissions))
                                                        <a data-toggle="modal"
                                                            data-target="#forwardRequestModal{{ $req->id }}"
                                                            data-status = "approve" data-id="{{ $req->id }}"
                                                            title="{{ translate('messages.approve_request') }}"
                                                            class="dropdown-item text-success forward_btn">
                                                            <i class="tio-checkmark-circle-outlined"></i> Approve
                                                        </a>
                                                    @endif

                                                    @if (in_array('verify', $permissions))
                                                        <a data-toggle="modal"
                                                            data-target="#forwardRequestModal{{ $req->id }}"
                                                            data-status = "verify" data-id="{{ $req->id }}"
                                                            title="{{ translate('messages.verify_request') }}"
                                                            class="dropdown-item text-primary forward_btn">
                                                            <i class="tio-checkmark-circle-outlined"></i> Verify
                                                        </a>
                                                    @endif
                                                    @if (in_array('review', $permissions))
                                                        <a data-toggle="modal"
                                                            data-target="#forwardRequestModal{{ $req->id }}"
                                                            data-status = "review" data-id="{{ $req->id }}"
                                                            title="{{ translate('messages.review_request') }}"
                                                            class="dropdown-item text-warning forward_btn">
                                                            <i class="tio-visible"></i> Review
                                                        </a>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <!-- Modal -->
                                        @if ($req->status != 'rejected' && $req->status != 'closed')
                                            <div class="modal fade" id="requestCloseModal{{ $req->id }}"
                                                tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">Approve Request
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form
                                                            action="{{ route('vendor.account.request-form.incoming-requests-close') }}"
                                                            method='post'>
                                                            @csrf
                                                            <div class="modal-body">
                                                                <input type="hidden" name="request_id"
                                                                    value="{{ $req->id }}">
                                                                <div class="">


                                                                    <label class="form-label label_lg"
                                                                        style="white-space: nowrap">Select Debit Account
                                                                    </label><br>

                                                                    <select name="account_id" id=""
                                                                        class="js-select2-custom"
                                                                        data-placeholder="Select Debit Account">
                                                                        <option value=""></option>
                                                                        @if ($req->credit_account_id)
                                                                            <option selected
                                                                                value="{{ $req->debit_account_id }}">
                                                                                {{ $req->debitAccount?->name }}</option>
                                                                        @endif

                                                                        @foreach ($data['expense_accounts'] as $key => $value)
                                                                            <option value="{{ $value->id }}">
                                                                                {{ $value->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-dismiss="modal">Cancel</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary">Approve</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="forwardRequestModal{{ $req->id }}"
                                                tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">Send Request To
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form
                                                            action="{{ route('vendor.account.request-form.incoming-requests-forward') }}"
                                                            method='post'>
                                                            @csrf
                                                            <div class="modal-body">
                                                                <input type="hidden" class="fwd_id_inp"
                                                                    name="request_id" value="{{ $req->id }}">
                                                                <input type="hidden" class="fwd_status_inp"
                                                                    name="request_status" value="">
                                                                <div class="">
                                                                    <label class="form-label label_lg"
                                                                        style="white-space: nowrap">Send To
                                                                    </label><br>
                                                                    <select name="sent_to" id=""
                                                                        class="js-select2-custom"
                                                                        data-placeholder="Select Employee">
                                                                        <option value=""></option>
                                                                        @php $send_to = _sendToPermission($req->type);@endphp
                                                                        @foreach ($send_to as $key => $value)
                                                                            <option value="{{ $value->id }}">
                                                                                {{ $value->f_name . ' ' . $value->l_name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="">
                                                                    <label class="form-label label_lg"
                                                                        style="white-space: nowrap">Remarks (optional)
                                                                    </label><br>
                                                                    <textarea name="remark" id="" placeholder="Start typing..." class="form-control"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-dismiss="modal">Cancel</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary">Send</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    @endsection

    @push('script_2')
        <script>
            $(".forward_btn").on('click', function() {
                let id = $(this).attr('data-id')
                let status = $(this).attr('data-status')

                $(".fwd_id_inp").val(id)
                $(".fwd_status_inp").val(status)


            })
        </script>
    @endpush
