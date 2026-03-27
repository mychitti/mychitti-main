@extends('layouts.admin.app')

@section('title', 'Journal Entry Request Form')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .label_lg {
            font-size: 14px !important;
        }

        /* Journal Entry Modal */
        .vendor-header {
            background: white;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            margin-bottom: 20px;
        }

        .vendor-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
            {{-- text-align: center; --}}
        }

        .vendor-address {
            color: #666;
            font-size: 12px;
            {{-- text-align: center; --}} margin: 5px 0 0 0;
            line-height: 1.3;
        }

        .logo-container {
            {{-- position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%); --}}
        }

        .company-logo {
            {{-- width: 60px;
            height: 60px; --}} background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }

        .form-container {
            background: white;
            padding: 30px;
        }

        .card {
            border: none;
            box-shadow: none;
        }

        .form-control,
        .form-control {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .company-logo img {
            width: 100px;
        }

        .req_form_header {
            padding: 0 20px;
        }

        .nowrap_lable {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .header-title {
                font-size: 1.5rem;
            }

            .header-icon {
                font-size: 2rem;
            }

            .nowrap_lable {
                white-space: normal;
            }

            .form-container {
                padding: 20px;
            }

            .company-logo img {
                width: 53px;
            }

            .req_form_header {
                padding: 0 10px;
            }

            .fomr_heading {
                font-size: 16px !important;
            }
        }

        .page_container {
            max-width: 1000px;
            margin: 0 auto;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class=" d-flex row">
            @if (hasPermission('apporval_form_journal_entry', 'add'))
                <div class="    col-md-7">
                    <div class="card shadow rounded p-2">
                        <!-- Vendor Header Section -->
                        <div class="vendor-header pb-2 mb-0 ">
                            <div
                                class="req_form_header container position-relative d-flex justify-content-between align-items-center">
                                <div class="store_content">
                                    <h2 class="vendor-name">
                                        {{ \App\Models\BusinessSetting::where('key', 'business_name')->first()?->value }}
                                    </h2>
                                    <p class="vendor-address">
                                        {{ \App\Models\BusinessSetting::where('key', 'address')->first()?->value }}<br>
                                        GST NO:
                                        {{ \App\Models\BusinessSetting::where('key', 'gst_number')->first()?->value }}
                                    </p>
                                </div>
                                <div class="logo-container">
                                    <div class="company-logo">
                                        <img src="{{ asset('storage/business/') . \App\Models\BusinessSetting::where('key', 'logo')->first()?->value }}"
                                            alt="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form
                            action="{{ $edit ? route('admin.account.request-form.journal-entry.update') : route('admin.account.request-form.journal-entry.store') }}"
                            method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="rf_id" value="{{ $edit ? $edit->id : '' }}">
                            <div class="vendor-header py-0 mb-0 ">
                                <div class="row px-2 py-1">
                                    <div class="col-md-3 ">
                                    </div>
                                    <div class="col-md-6  d-flex align-items-center justify-content-center">
                                        <h1 style="text-align: center;" class="mb-0 fomr_heading">Journal Entry Request Form
                                        </h1>

                                    </div>
                                    <div class="col-md-3  d-flex justify-content-end">

                                    </div>
                                </div>

                            </div>
                            <div class="vendor-header py-1 mb-0">
                                <div class="row px-2">
                                    <div class="col-md-5 ">
                                        <div class="d-flex my-2 gap-2 align-items-center">
                                            <label class="form-label label_lg nowrap_lable">Requested By</label>
                                            <select name="requested_by" id=""
                                                data-placeholder="Select Requested By" class="js-select2-custom">
                                                <option value=""></option>
                                                @foreach ($employees as $key => $value)
                                                    <option
                                                        {{ $edit && $edit->requested_by == $value->id ? 'selected' : '' }}
                                                        value="{{ $value->id }}">
                                                        {{ $value->f_name . ' ' . $value->l_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        {{-- <div class="d-flex my-2 gap-2 align-items-center">
                                <label class="form-label " style="white-space: nowrap">Department</label>
                                <select name="req_by_department" id="" data-placeholder="Select Department"
                                    class="js-select2-custom">
                                    <option value=""></option>
                                    @foreach ($departments as $key => $value)
                                        <option value="{{ $value->id }}">{{ $value->title }}</option>
                                    @endforeach
                                </select>
                            </div> --}}
                                    </div>
                                    <div class="col-md-3">

                                    </div>
                                    <div class="col-md-4 ">
                                        <div class="d-flex my-2 gap-2 align-items-center">
                                            <label class="form-label label_lg" style="white-space: nowrap">Date</label>
                                            <input type="date"
                                                value="{{ $edit && $edit->date ? $edit->date : date('Y-m-d') }}"
                                                name="date" class="form-control" id="">
                                        </div>
                                        <div class="d-flex my-2 gap-2 align-items-center justify-content-end">
                                            <label class="form-label mb-0  label_lg" style="white-space: nowrap">Request
                                                No.</label>
                                            {{ $edit && $edit->request_number ? $edit->request_number : $request_number }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="vendor-header py-1 mb-0">
                                <div class="row px-2">
                                    <div class="col-md-5 ">
                                        <div class="d-flex my-2 gap-2 align-items-center">
                                            <label class="form-label label_lg" style="white-space: nowrap">Requested
                                                To</label>
                                            <select name="request_to" id="" data-placeholder="Select Requested To"
                                                class="js-select2-custom">
                                                <option value=""></option>
                                                @foreach ($employees as $key => $value)
                                                    <option
                                                        {{ $edit && $edit->request_to == $value->id ? 'selected' : '' }}
                                                        value="{{ $value->id }}">
                                                        {{ $value->f_name . ' ' . $value->l_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 ">

                                    </div>
                                    <div class="col-md-4  ">
                                        <div class="d-flex my-2 gap-2 align-items-center">
                                            <label for="" class="label_lg">Status</label>
                                            Pending

                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="vendor-header py-1 mb-0">
                                <div class="row px-2">
                                    <div class="col-md-6 ">
                                        <div class="d-flex my-2 gap-2 align-items-center">
                                            <label class="form-label label_lg " style="white-space: nowrap">Ledger
                                                Type</label>
                                            <select name="ledger_type" id="" data-placeholder="Select Ledger Type"
                                                class="js-select2-custom">
                                                <option value=""></option>
                                                @foreach ($ledger_types as $key => $value)
                                                    <option
                                                        {{ $edit && $edit->ledger_type == $value->id ? 'selected' : '' }}
                                                        value="{{ $value->id }}">{{ $value->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="d-flex my-2 gap-2 align-items-center">
                                            <label class="form-label label_lg"
                                                style="white-space: nowrap">Description</label>
                                            <textarea name="description" placeholder="Start typing..." class="form-control" id="">{{ $edit?->description }}</textarea>
                                        </div>
                                        @if ($edit && $edit->doc_file)
                                            <a class="text-decoration-underline"
                                                href="{{ asset('storage/app/public/store/documents/' . $edit->doc_file) }}"
                                                target="_blank"> View Document</a>
                                        @endif
                                        <div class="d-flex my-2 gap-2 align-items-center">
                                            <label class="form-label label_lg">Supporting Documents
                                                Ref. </label>
                                            <input type="file" name="doc_file" class="form-control" id="">
                                        </div>
                                        <div class="d-flex mt-2 gap-2 align-items-center    ">
                                            <label class="form-label label_lg" style="white-space: nowrap">Amount in
                                                Words</label>
                                            <input type="text" readonly name="" class="form-control"
                                                placeholder="Auto Filled" id="amount_in_words">
                                        </div>
                                    </div>
                                    <div class="col-md-2"></div>
                                    <div class="col-md-4 my-2">
                                        <div class="d-flex h-100 align-items-end gap-2">
                                            <label class="form-label label_lg" style="white-space: nowrap">Amount </label>
                                            <input type="number" step="0.001" value="{{ $edit?->amount }}"
                                                placeholder="Ex: 1200" name="amount" class="form-control"
                                                id="amount_inp">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Submit Button -->
                            <div class="col-12">
                                <div class="d-flex justify-content-end m-3 mr-5">
                                    <button type="submit" class="btn btn-primary">
                                        Submit
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            @if (hasPermission('apporval_form_journal_entry', 'edit'))
                @if (!$edit)
                    <div class=" col-md-5 ">
                        <div class="card shadow rounded p-2">
                            <h3 class="my-2">My Requests</h3>
                            <div class="row">
                                @forelse($data['submitted_req'] as $req)
                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow-sm">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    Request #: {{ $req->request_number }}
                                                </h5>
                                                <p class="card-text">
                                                    <strong>Date:</strong>
                                                    {{ \Carbon\Carbon::parse($req->date)->format('d M, Y') }}<br>
                                                    <strong>Requested By:</strong>
                                                    {{ $req->requestedBy?->f_name . ' ' . $req->requestedBy?->l_name }}<br>
                                                    <strong>Requested To:</strong>
                                                    {{ $req->requestedTo?->f_name . ' ' . $req->requestedTo?->l_name }}<br>
                                                    <strong>Amount:</strong> ₹{{ number_format($req->amount, 2) }}<br>
                                                    <strong>Status:</strong>
                                                    @if ($req->status == 'pending')
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    @elseif($req->status == 'approved')
                                                        <span class="badge bg-success ">Approved</span>
                                                    @elseif($req->status == 'rejected')
                                                        <span class="badge bg-danger text-white">Rejected</span>
                                                        @if (hasPermission('apporval_form_journal_entry', 'edit'))
                                                            @if ($req->resubmit <= $permitted_resubmit)
                                                                <a style="padding: 0 10px ; width: fit-content;"
                                                                    class="my-2 btn action-btn btn--primary btn-outline-primary edit_n_resubmit"
                                                                    href="{{ route('admin.account.request-form.journal-entry.index', [$req->id]) }}"
                                                                    title="{{ translate('messages.edit') }}"><i
                                                                        class="tio-edit"></i>
                                                                    Edit &
                                                                    Resubmit
                                                                </a>
                                                            @endif
                                                        @endif
                                                    @else
                                                        <span
                                                            class="badge bg-primary text-white">{{ ucfirst($req->status) }}</span>
                                                    @endif
                                                </p>
                                                @if ($req->doc_file)
                                                    <a href="{{ asset('storage/app/public/store/documents/' . $req->doc_file) }}"
                                                        target="_blank" class="btn btn-sm btn-primary">View Document</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-center text-muted">No requests found.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>



    </div>
@endsection

@push('script_2')
    <script>
        $("#amount_inp").on("input", function() {
            let val = this.value;

            // Take only integer part for words (ignore decimals safely)
            let intPart = val.split('.')[0];

            if (intPart && !isNaN(intPart)) {
                $("#amount_in_words").val(numberToWords(intPart));
            } else {
                $("#amount_in_words").val('');
            }
        });


        $(document).ready(function() {
            var val = $("#amount_inp").val();
            $("#amount_in_words").val(numberToWords(val));
        });


        function numberToWords(num) {
            var a = [
                '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen',
                'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
            ];
            var b = [
                '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty',
                'Sixty', 'Seventy', 'Eighty', 'Ninety'
            ];

            function inWords(n) {
                if ((n = n.toString()).length > 9) return 'Overflow';
                let num = ('000000000' + n).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
                if (!num) return;
                let str = '';
                str += (num[1] != 0) ? (a[Number(num[1])] || b[num[1][0]] + ' ' + a[num[1][1]]) + ' Crore ' : '';
                str += (num[2] != 0) ? (a[Number(num[2])] || b[num[2][0]] + ' ' + a[num[2][1]]) + ' Lakh ' : '';
                str += (num[3] != 0) ? (a[Number(num[3])] || b[num[3][0]] + ' ' + a[num[3][1]]) + ' Thousand ' : '';
                str += (num[4] != 0) ? (a[Number(num[4])] || b[num[4][0]] + ' ' + a[num[4][1]]) + ' Hundred ' : '';
                str += (num[5] != 0) ? ((str != '') ? 'and ' : '') +
                    (a[Number(num[5])] || b[num[5][0]] + ' ' + a[num[5][1]]) + ' ' : '';
                return str.trim();
            }

            return inWords(num) + ' Rupees Only';
        }
    </script>
@endpush
