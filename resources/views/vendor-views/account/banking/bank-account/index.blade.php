@extends('layouts.vendor.app')

@section('title', 'Bank Accounts')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .year-card {
            cursor: pointer;
            transition: 0.2s;
            border: 1px solid #e2e2e2 !important;
            width: 168px !important;
        }

        .year-card:hover {
            transform: scale(1.05);
            border: 2px solid #007bff;
        }

        .year-card.active {
            border: 2px solid #28a745 !important;
            background: #e6ffea;
        }

        .form-row {
            margin-top: 6px;
        }

        .folder_img {
            width: 100%;
            filter: brightness(1.1);
        }


        /* Content overlay inside folder */
        .folder-content {
            top: 22px;
            left: -4px;
            right: 8px;
            bottom: 8px;
            border-radius: 3px;
            padding: 13px;
            z-index: 1;
            width: 100%;
        }

        /* Bank name styling */
        .bank-title {
            font-size: 12px;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info lines */
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 12px;
            line-height: 1.3;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #000;
            font-family: monospace;
        }

        .amount-green {
            color: #27ae60;
            font-weight: bold;
        }

        .amount-red {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Upload section */
        .upload-section {
            width: fit-content;
            margin: 0 auto;
            margin-top: 8px;
            text-align: center;
            padding: 6px;
            {{-- background: rgba(52, 152, 219, 0.1);
            border: 1px dashed #3498db; --}} border-radius: 3px;
        }

        .upload-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s;
        }

        .upload-btn:hover {
            background: #2980b9;
        }

        /* Folder reflection effect */
        .folder-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 50%;
            bottom: 50%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .folder-shape {
                max-width: 250px;
            }

            .folder-main {
                height: 180px;
            }

            .bank-title {
                font-size: 14px;
            }

            .info-row {
                font-size: 11px;
            }
        }
    </style>
    <style>
        .folder {
            margin: 16px;
            position: relative;
            width: 309px;
            height: fit-content;
        }

        .folder-back {
            position: absolute;
            top: -26px;
            left: 0px;
            z-index: -1;
            width: 52%;
            height: 34px;
            background: linear-gradient(180deg, #ffb347 0%, #ff9020 100%);
            border-radius: 15px 15px 0 0;
        }

        .folder-back-extension {
            position: absolute;
            top: -16px;
            z-index: -1;
            left: 159px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ffb347 0%, #ff9020 100%);
            border-radius: 0 28px 0 0;
        }

        .folder-cutout {
            position: absolute;
            top: -14px;
            left: 51%;
            width: 10px;
            height: 10px;
        }

        .folder-cutout::before {
            content: '';
            position: absolute;
            top: -4px;
            left: 3px;
            width: 41px;
            height: 18px;
            background: white;
            border-radius: 0 0 0 61px;
        }

        .folder-main {
            /* position: absolute; */
            top: 110px;
            left: 14px;
            width: 100%;
            min-height: 219px;
            height: 100%;
            background: linear-gradient(180deg, #ffeb3b 0%, #fdd835 100%);
            border-radius: 16px;
            box-shadow: 0 10px 14px rgb(0 0 0 / 12%);
            display: flex;
            align-items: center;

        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Bank Accounts</h1>
            </div>
            <div class="page-header-select-wrapper">

                {{-- <button type="button" class="btn btn-primary mx-1" data-toggle="modal" data-target="#importTxnModal">
                    <i class="tio-upload-outlined"></i> Import Transactions
                </button> --}}
                <button type="button" class="btn btn-outline-primary mx-1" data-toggle="modal"
                    data-target="#financialYearModal">
                    <i class="tio-calendar"></i> Financial Year {{ $year }}
                </button>
                @include('vendor-views.form_modals.import_transactions')
                @if(hasPermission('banking_bank_accounts', 'add'))
                <button type="button" class="btn btn-primary mx-1" data-toggle="modal" data-target="#accountModal">
                    <i class="tio-add-circle-outlined"></i> Add Bank Account
                </button>
                @include('vendor-views.form_modals.add_store_bank_account')
                @endif

            </div>
        </div>
        <!-- End Page Header -->
                @if(hasPermission('banking_bank_accounts', 'list'))

        <div class="row my-3  ">
            @foreach ($accounts as $account)
            @php $account_last_txn = _accountLastTxn($account->id, $fyStart, $fyEnd);@endphp
                <div class="folder">
                    <div class="folder-back"></div>
                    <div class="folder-back-extension"></div>
                    <div class="folder-cutout"></div>
                    <div class="folder-main">
                        <div class="folder-content ">
                            {{-- <input type="hidden" class="bank_id_inp" name="bank_id" value=""> --}}
                            <div class="dropdown position-absolute" style="    right: 10px;top: 8px; z-index: 10;">
                                <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                    aria-expanded="false">
                                    <img style="width: 24px; filter: contrast(0)"
                                        src="{{ asset('storage/app/public/util/10025520.png') }}" alt="action" />
                                </button>
                                <style>
                                    .dropdown-menu.show {
                                        top: -15px !important;
                                        left: -97px !important;

                                    }
                                </style>
                                <div class="dropdown-menu">
                                    <a class=" dropdown-item text-warning  edit-btn edit_btn" data-id ="{{ $account->id }}"
                                        data-account_number ="{{ $account->account_number }}"
                                        data-account_holder_name ="{{ $account->account_holder_name }}"
                                        data-ifsc_code ="{{ $account->ifsc_code }}"
                                        data-minimum_balance ="{{ $account->minimum_balance }}"
                                        data-opening_balance ="{{ $account->opening_balance }}"
                                        data-bank_name ="{{ $account->bank_name }}" data-toggle="modal"
                                        data-target="#editAccountModal"><i class="tio-edit"></i> Edit</a>

                                    <a class=" dropdown-item text-danger form-alert" href="javascript:"
                                        data-id="attribute-{{ $account['id'] }}"
                                        data-message="{{ translate('Want to delete this bank account. All its transactions will also be deleted.') }}"
                                        title="{{ translate('messages.delete') }}"><i class="tio-delete"></i> Delete</a>

                                    <form
                                        action="{{ route('vendor.account.banking.bank-account.delete', [$account['id']]) }}"
                                        method="get" id="attribute-{{ $account['id'] }}">
                                        @csrf @method('get')
                                    </form>
                                </div>
                            </div>

                            <a href="{{ hasPermission('banking_bank_accounts', 'view')  ? route('vendor.account.banking.bank-account.detail', $account->id) : '#' }}"
                                class="folder-content_{{ $account->id }}">
                                <div class="bank-title"> @php
                                    $baseDir = storage_path('app/public/bank_logos');
                                    $folderName = basename($account->bank_name);
                                    $logoUrl = asset('storage/app/public/bank_logos/' . $folderName . '/logo.png');
                                @endphp
                                    <img src="{{ $logoUrl }}" style="width: 135px;margin: 3px 0 ;" alt=""><br>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">AC/ NO:</span>
                                    <span class="info-value"> {{ $account->account_number }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Account holder Name</span>
                                    <span class="info-value">{{ $account->account_holder_name }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Last up dated</span>
                                    <span class="info-value">{{ $account_last_txn?->created_at }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Deposit Amt-</span>
                                    <span
                                        class="info-value amount-green">{{ $account_last_txn?->type == 'credit' ? $account_last_txn?->amount : 0 }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Withdrawal Amt-</span>
                                    <span
                                        class="info-value amount-red">{{ $account_last_txn?->type == 'debit' ? $account_last_txn?->amount : 0 }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Balance Amt-</span>
                                    <span class="info-value amount-green">{{ $account_last_txn?->closing_balance }}</span>
                                </div>
                            </a>
                            <a href="javascript:;" class="folder-content_hidden_{{ $account->id }}"
                                style="display: none;">
                                <div class="bank-title"> @php
                                    $baseDir = storage_path('app/public/bank_logos');
                                    $folderName = basename($account->bank_name);
                                    $logoUrl = asset('storage/app/public/bank_logos/' . $folderName . '/logo.png');
                                @endphp
                                    <img src="{{ $logoUrl }}" width="100" style="margin: 3px 0 ;"
                                        alt=""><br>

                                </div>

                                <div class="info-row">
                                    <span class="info-label">AC/ NO:</span>
                                    <span class="info-value"> {{ $account->account_number }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Account holder Name</span>
                                    <span class="info-value">{{ $account->account_holder_name }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Last up dated</span>
                                    <span class="info-value">{{ $account_last_txn?->created_at }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Deposit Amt-</span>
                                    <span class="info-value amount-green">{{ $account_last_txn?->credit_amount }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Withdrawal Amt-</span>
                                    <span class="info-value amount-red">{{ $account_last_txn?->debit_amount }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Balance Amt-</span>
                                    <span class="info-value amount-green">{{$account_last_txn?->closing_balance }}</span>
                                </div>
                            </a>
                            <div class="upload-section">
                            @if(hasPermission('banking_bank_accounts', 'upload_transactions'))
                                <button class="upload-btn" data-id="{{ $account->id }}" type="button"
                                    data-target="#importTxnModal" data-toggle="modal">
                                    📁 Upload Transaction file
                                </button>
                            @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            @if (count($accounts) >= 2)
                <div class="folder">
                    <div class="folder-back"></div>
                    <div class="folder-back-extension"></div>
                    <div class="folder-cutout"></div>
                    <a href="{{ hasPermission('banking_bank_accounts', 'view')  ? route('vendor.account.banking.bank-account.detail-main') : '#' }}" class="folder-main">
                        <div class="folder-content ">
                            {{-- <input type="hidden" class="bank_id_inp" name="bank_id" value=""> --}}
                            <div class="folder-content_0 ">

                                <div class="bank-title">
                                    Main Bank {{ \App\CentralLogics\Helpers::get_store_data()?->name ?? '' }}</div>

                                <div class="info-row">
                                    <span class="info-label">Last up dated</span>
                                    <span class="info-value">{{ $account_last_txn?->created_at }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Deposit Amt-</span>
                                    <span
                                        class="info-value amount-green">{{ $account_last_txn?->type == 'credit' ? $account_last_txn?->amount : 0 }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Withdrawal Amt-</span>
                                    <span
                                        class="info-value amount-red">{{ $account_last_txn?->type == 'debit' ? $account_last_txn?->amount : 0 }}</span>
                                </div>

                                <div class="info-row"> 
                                    <span class="info-label">Bala nce Amt-</span>
                                    <span class="info-value amount-green">{{ $account_last_txn?->closing_balance }}</span>
                                </div>
                            </div>

                            <span href="javascript:;" class="folder-content_hidden_0" style="display: none;">


                                <div class="bank-title">
                                    Main Bank {{ \App\CentralLogics\Helpers::get_store_data()?->name ?? '' }}</div>



                                <div class="info-row">
                                    <span class="info-label">Last up dated</span>
                                    <span class="info-value">{{ $account_last_txn?->created_at }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Deposit Amt-</span>
                                    <span
                                        class="info-value amount-green">{{ $account_last_txn?->type == 'credit' ? $account_last_txn?->amount : 0 }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Withdrawal Amt-</span>
                                    <span
                                        class="info-value amount-red">{{ $account_last_txn?->type == 'debit' ? $account_last_txn?->amount : 0 }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Balance Amt-</span>
                                    <span class="info-value amount-green">{{ $account_last_txn?->closing_balance }}</span>
                                </div>
                            </span>

                            {{-- <div class="upload-section">
                                <button class="upload-btn" data-id="0" type="button"
                                    data-target="#importTxnModal" data-toggle="modal">
                                    📁 Upload Transaction file
                                </button>
                            </div> --}}
                        </div>
                    </a>
                </div>
            @endif
        </div>
        @endif

    </div>
                @if(hasPermission('banking_bank_accounts', 'edit'))

    <!-- Button trigger modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Edit Bank Account</h5>
                    <button type="button" class="close account_modal_close_btn" data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form class="customer_add_form" enctype="multipart/form-data" id="edit_form" method="post"
                        action="{{ route('vendor.account.banking.bank-account.store') }}">
                        @csrf
                        <input type="hidden" name="edit_id" class="edit_id">
                        <div class="col-md-12 p-2 mb-3 row">
                            <div class="col-md-6 flex-grow-1 mx-auto p-2 mb-1">
                                @php
                                    $baseDir = storage_path('app/public/bank_logos');
                                    $folders = array_filter(glob($baseDir . '/*'), 'is_dir');
                                @endphp
                                <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                    for="">
                                    <span>Select Bank<span class="text-danger">*</span></span>
                                </label>
                                <select id="bank-select1" required data-placeholder="Select Bank" name="bank_name"
                                    class="form-control bank-select ac_bank_name">
                                    <option value=""></option>
                                    @foreach ($folders as $folder)
                                        @php
                                            $folderName = basename($folder);
                                            $logoUrl = asset(
                                                'storage/app/public/bank_logos/' . $folderName . '/logo.png',
                                            );
                                        @endphp
                                        <option value="{{ $folderName }}" data-logo="{{ $logoUrl }}">
                                            {{ strtoupper($folderName) }}</option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="col-md-6 p-2 mb-3">
                                <div class="form-group mb-0 ">
                                    <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                        for="">
                                        <span>Account Holder Name<span class="text-danger">*</span></span>
                                    </label>
                                    <input required name="account_holder_name" type="text"
                                        placeholder="Ex: Meenu Rathore" class="form-control ac_account_holder_name">
                                </div>
                            </div>
                            <div class="col-md-6 p-2 mb-3">
                                <div class="form-group mb-0 ">
                                    <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                        for="">
                                        <span>Bank Account Number<span class="text-danger">*</span></span>
                                    </label>
                                    <input required name="account_number" type="text"
                                        placeholder="Ex: 9999444433337777" class="form-control ac_account_number">
                                </div>
                            </div>
                            <div class="col-md-6 p-2 mb-3">
                                <div class="form-group mb-0 ">
                                    <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                        for="">
                                        <span>Bank IFSC Code<span class="text-danger">*</span></span>
                                    </label>
                                    <input required name="ifsc_code" type="text" placeholder="Ex: ICICI0001234"
                                        class="form-control ac_ifsc_code">
                                </div>
                            </div>
                             {{-- <div class="col-md-6 p-2 mb-1">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Opening Balance</span>
                                  </label>
                                  <input  name="opening_balance" type="text" placeholder="Ex: 120020"
                                      class="form-control ac_opening_balance">
                              </div>
                          </div> --}}
                          <div class="col-md-6 p-2 mb-1">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Minimum Balance</span>
                                  </label>
                                  <input  name="minimum_balance" type="text" placeholder="Ex: 5000"
                                      class="form-control ac_minimum_balance">
                              </div>
                          </div>


                            <div class="col-12" style="display: flex;align-items: end;justify-content: end;">
                                <button type="button" class="btn btn-secondary mx-2"
                                    data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    @include('vendor-views.form_modals.financial_year_select')



@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $('.bank-select').select2({
            templateResult: function(data) {
                if (!data.id) return data.text; // placeholder
                var logo = $(data.element).data('logo');
                var $result = $(
                    '<span><img src="' + logo +
                    '" style="width:100px; height:auto; margin-right:10px;" /><br><small>' + data.text +
                    '</small></span>'
                );
                return $result;
            },
            templateSelection: function(data) {
                if (!data.id) return data.text;
                var logo = $(data.element).data('logo');
                var $selection = $(
                    '<span><img src="' + logo + '" style="width:20px; height:auto; margin-right:5px;" />' +
                    data.text + '</span>'
                );
                return $selection;
            }
        });

        $(document).off('show.bs.modal', '#importTxnModal').on('show.bs.modal', '#importTxnModal', function(event) {
            console.log("Modal show fired");

            var button = $(event.relatedTarget);
            var id = button.data('id');

            var html = $('.folder-content_hidden_' + id).html();
            console.log("Cloned element:", html);

            var modal = $(this);
            $(".bank_account_inp").val(id);

            modal.find('.bank_info').html(html);
        });



        var routeTemplate = "{{ route('vendor.account.banking.bank-account.update', ['__ID__']) }}";
        $(".edit_btn").on('click', function() {
            var id = $(this).attr('data-id')
            var account_number = $(this).attr('data-account_number')
            var account_holder_name = $(this).attr('data-account_holder_name')
            var ifsc_code = $(this).attr('data-ifsc_code')
            var bank_name = $(this).attr('data-bank_name')
            var minimum_balance = $(this).attr('data-minimum_balance')
           // var opening_balance = $(this).attr('data-opening_balance')
            $('.edit_id').val(id);
            console.log(bank_name)
            $('.ac_bank_name').val(bank_name).trigger('change');
            $('.ac_account_number').val(account_number);
            $('.ac_account_holder_name').val(account_holder_name);
            $('.ac_ifsc_code').val(ifsc_code);
            // $('.ac_opening_balance').val(opening_balance);
            $('.ac_minimum_balance').val(minimum_balance);

            var url = routeTemplate.replace('__ID__', id);
            $('#edit_form').attr('action', url);
        })
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
