@extends('layouts.vendor.app')

@section('title', 'Bank Account Detail')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <style>
        .bank-th-amount {
            text-align: center;
        }

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

        .bank-container {
            margin: 0 auto;
        }

        .bank-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 9px 15px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .bank-account-info {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: center;
        }

        .bank-account-details h1 {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .bank-account-number {
            color: #718096;
            font-size: 1.1rem;
            font-family: 'Monaco', 'Menlo', monospace;
            margin-bottom: 16px;
        }

        .bank-balance-card {
            background: linear-gradient(135deg, #4fd1c7, #3182ce);
            color: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(79, 209, 199, 0.3);
        }

        .bank-balance-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 4px;
        }

        .bank-balance-amount {
            font-size: 2.5rem;
            font-weight: 800;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .bank-main-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .bank-transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .bank-transactions-title {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .bank-filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bank-filter-btn {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #4a5568;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .bank-filter-btn:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        .bank-filter-btn.bank-active {
            background: #3182ce;
            color: white;
            border-color: #3182ce;
        }

        .bank-table-container {
            overflow-x: auto;
            border-radius: 12px;
            background: white;
        }

        .bank-transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }


        .bank-transaction-row {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .bank-transaction-row:hover {
            background: linear-gradient(90deg, rgba(79, 209, 199, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
            transform: translateX(4px);
            box-shadow: -4px 0 0 rgba(79, 209, 199, 0.3);
        }

        .bank-transaction-row:last-child {
            border-bottom: none;
        }

        .bank-transactions-table td {
            padding: 20px 12px;
            vertical-align: top;
        }

        .bank-td-type {
            text-align: center;
            width: 80px;
        }

        .bank-td-description {
            width: 30%;
        }

        .bank-td-date {
            color: #718096;
            font-weight: 500;
            {{-- width: 15%; --}}
        }

        .bank-td-status {
            text-align: center;
        }

        .bank-td-amount {
            font-family: 'Monaco', 'Menlo', monospace;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .bank-td-amount.bank-credit {
            color: #38a169;
        }

        .bank-td-amount.bank-debit {
            color: #e53e3e;
        }

        @media (max-width: 768px) {
            .bank-table-container {
                font-size: 0.85rem;
            }

            .bank-transactions-table th,
            .bank-transactions-table td {
                padding: 12px 8px;
            }

            .bank-th-description,
            .bank-td-description {
                width: 30%;
            }

        }

        .bank-transaction-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .bank-transaction-icon.bank-credit {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .bank-transaction-icon.bank-debit {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
        }

        .bank-transaction-details {
            min-width: 0;
        }

        .bank-transaction-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .bank-transaction-subtitle {
            color: #718096;
            font-size: 0.9rem;
        }

        .bank-transaction-date {
            color: #a0aec0;
            font-size: 0.85rem;
            text-align: right;
        }

        .bank-transaction-amount {
            font-family: 'Monaco', 'Menlo', monospace;
            font-weight: 600;
            font-size: 1.1rem;
            text-align: right;
        }

        .bank-transaction-amount.bank-credit {
            color: #38a169;
        }

        .bank-transaction-amount.bank-debit {
            color: #e53e3e;
        }

        .bank-quick-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .bank-action-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .bank-action-btn.bank-primary {
            background: linear-gradient(135deg, #3182ce, #2c5282);
            color: white;
            box-shadow: 0 4px 12px rgba(49, 130, 206, 0.3);
        }

        .bank-action-btn.bank-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(49, 130, 206, 0.4);
        }

        .bank-action-btn.bank-secondary {
            background: white;
            padding: 6px 24px;
            color: #00aa6d;
            height: 39px;
            border: 2px solid #00aa6d;
        }

        .bank-action-btn.bank-secondary:hover {
            background: #00aa6d;
            color: white;
        }

        @media (max-width: 768px) {
            .bank-account-info {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .bank-balance-card {
                text-align: center;
            }

            .bank-transaction-item {
                grid-template-columns: 1fr;
                gap: 12px;
                text-align: center;
            }

            .bank-transaction-date,
            .bank-transaction-amount {
                text-align: center;
            }

            .bank-filters {
                justify-content: center;
            }
        }

        .bank-loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: bank-shimmer 1.5s infinite;
        }

        @keyframes bank-shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .bank-status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bank-status-badge.bank-pending {
            background: #fef5e7;
            color: #d69e2e;
        }

        .bank-status-badge.bank-completed {
            background: #f0fff4;
            color: #38a169;
        }

        .file_item {
            padding: 10px !important;
            border: 2px dashed #6fc96f;
            border-radius: 11px;
            margin: 4px;
            border-bottom: 2px dashed #6fc96f !important;
            background: #f3fff3;
        }

        .bank-th-amount,
        .bank-td-amount {
            text-align: center !important;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Bank Account Detail</h1>
            </div>
            <div class="page-header-select-wrapper">
                <button type="button" class="btn btn-outline-primary mx-1" data-toggle="modal"
                    data-target="#financialYearModal">
                    <i class="tio-calendar"></i> Financial Year {{ $year }}
                </button>
                <button type="button" class="btn btn-primary mx-1" data-toggle="modal" data-target="#importedFilesModal">
                    <i class="tio-document-outlined"></i> Uploaded Files
                </button>
                <div class="modal fade" id="importedFilesModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Uploaded Files</h5>
                                <button type="button" class="close account_modal_close_btn" data-dismiss="modal"
                                    aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">

                                @foreach ($files as $key => $value)
                                    <div
                                        class="file_item d-flex justify-content-between align-items-center border-bottom py-2">
                                        <a href="{{ asset('storage/app/public/store/banking/' . $value->pdf) }}">
                                            <i class="tio-file-text-outlined"></i> {{ $value->pdf }}
                                        </a>
                                        <span>{{ $value->created_at }}</span>
                                        <div>
                                            <div class="btn--container justify-content-center">

                                                <a href="{{ asset('storage/app/public/store/banking/' . $value->pdf) }}"
                                                    class="btn action-btn btn-outline-success"><i
                                                        class="tio-visible"></i></a>
                                                @if (hasPermission('banking_bank_accounts', 'transaction_file_delete'))
                                                    <a class="btn action-btn btn-outline-danger form-alert"
                                                        href="javascript:" data-id="attribute-{{ $value['id'] }}"
                                                        data-message="{{ translate('Want to delete this transactions file. All its transactions will also be deleted.') }}"
                                                        title="{{ translate('messages.delete') }}"><i
                                                            class="tio-delete"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            @if (hasPermission('banking_bank_accounts', 'transaction_file_delete'))
                                                <form
                                                    action="{{ route('vendor.account.banking.bank-account.delete-file', [$value['id']]) }}"
                                                    method="get" id="attribute-{{ $value['id'] }}">
                                                    @csrf @method('get')
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>


                {{--  @include('vendor-views.form_modals.import_transactions') --}}

            </div>
        </div>
        <!-- End Page Header -->




        <div class="bank-container">
            <div class="bank-header shadow">
                <div class="bank-account-info">
                    <div class="bank-account-details">
                        <h2> @php
                            $baseDir = storage_path('app/public/bank_logos');
                            $folderName = basename($account->bank_name);
                            $logoUrl = asset('storage/app/public/bank_logos/' . $folderName . '/logo.png');
                        @endphp
                            <img src="{{ $logoUrl }}" width="100" style="margin: 15px 0 ;" alt=""><br>

                            {{ $account->bank_name }}
                        </h2>
                        <div class="bank-account-number">{{ $account->account_number }}</div>
                    </div>
                    <div class="card p-3 text-center " style="min-width: 200px;">
                        <h4>Credit</h4>
                        <h3 class="text-success">+{{ _price($data['credit']) }}</h3>
                    </div>
                    <div class="card p-3 text-center" style="min-width: 200px;">
                        <h4>Debit</h4>
                        <h3 class="text-danger">-{{ _price($data['debit']) }}</h3>
                    </div>
                    <div class="card p-3 text-center" style="min-width: 200px;">
                        <h4>Variation Amount</h4>
                        @if ($data['debit'] > $data['credit'])
                            <h3 class="text-danger">
                                {{ _price($data['debit'] - $data['credit']) }}
                            </h3>
                        @else
                            <h3 class="text-success">
                                {{ _price($data['credit'] - $data['debit']) }}
                            </h3>
                        @endif
                    </div>
                    <div class="bank-balance-card">
                        <table>
                            <tr>
                                <td style="text-align:start;">Account Number:</td>
                                <td>{{ $account->account_number }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:start;">Account Holder Name:</td>
                                <td>{{ $account->account_holder_name }}</td>
                            </tr>
                            <tr>
                                <td style="text-align:start;">IFSC :</td>
                                <td>{{ $account->ifsc_code }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bank-main-content">
                <div class="bank-transactions-header">
                    <h2 class="bank-transactions-title">Transactions</h2>
                    <div class="bank-filters">
                        <form action="">
                            <label>
                                <input type="radio" name="filter" value="all" hidden
                                    {{ request('filter', 'all') == 'all' ? 'checked' : '' }}>
                                <button type="button"
                                    class="bank-filter-btn {{ request('filter', 'all') == 'all' ? ' bank-active' : '' }}"
                                    onclick="this.previousElementSibling.checked=true; this.form.submit();">All</button>
                            </label>
                            <label>
                                <input type="radio" name="filter" value="credit" hidden
                                    {{ request('filter', 'all') == 'credit' ? 'checked' : '' }}>
                                <button type="button"
                                    class="bank-filter-btn {{ request('filter', 'all') == 'credit' ? ' bank-active' : '' }}"
                                    onclick="this.previousElementSibling.checked=true; this.form.submit();">Credits</button>
                            </label>
                            <label>
                                <input type="radio" name="filter" value="debit" hidden
                                    {{ request('filter', 'all') == 'debit' ? 'checked' : '' }}>
                                <button type="button"
                                    class="bank-filter-btn {{ request('filter', 'all') == 'debit' ? ' bank-active' : '' }}"
                                    onclick="this.previousElementSibling.checked=true; this.form.submit();">Debits</button>
                            </label>
                        </form>

                        <button class="bank-action-btn bank-secondary" type="button" data-toggle="modal"
                            data-target="#calendarModal">Calendar</button>
                        {{-- <button class="bank-action-btn bank-secondary">Export Transactions</button> --}}

                    </div>
                </div>

                <div class="bank-table-container">
                    <table class="table bank-transactions-table">
                        <thead>
                            <tr>
                                <th class="bank-th-type">sl</th>
                                <th class="bank-th-date">Date</th>
                                <th class="bank-th-date">Transaction Id</th>
                                <th class="bank-th-description">Narration</th>
                                <th class="bank-th-date">Value Date</th>
                                <th class="bank-th-amount">Withdrwal Amt.</th>
                                <th class="bank-th-amount">Credit Amt.</th>
                                <th class="bank-th-amount">Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $key => $txn)
                                @php
                                    $type = $txn->type;
                                @endphp
                                <tr class="bank-transaction-row" data-type="{{ $type }}"
                                    data-status="completed">
                                    <td class="bank-td-type">
                                        {{ $key + $transactions->firstItem() }}
                                    </td>
                                    <td class="bank-td-date">{{ $txn->txn_date }}</td>

                                    <td class="bank-td-date">
                                        <div class="bank-transaction-title">{{ $txn->txn_id }}</div>
                                    </td>
                                    <td class="bank-td-description">
                                        <div class="bank-transaction-subtitle">{{ $txn->particulars }}</div>
                                    </td>
                                    <td class="bank-td-date">{{ $txn->value_date }}</td>
                                    @if ($type == 'debit')
                                        <td class="bank-td-amount bank-{{ $type }}">
                                            <span
                                                class="text-center">{{ $type == 'credit' ? '+' : '-' }}{{ _price($txn->amount) }}</span>
                                        </td>
                                    @else
                                        <td></td>
                                    @endif
                                    @if ($type == 'credit')
                                        <td class="bank-td-amount bank-{{ $type }}">
                                            <span
                                                class="text-center">{{ $type == 'credit' ? '+' : '-' }}{{ _price($txn->amount) }}</span>
                                        </td>
                                    @else
                                        <td></td>
                                    @endif
                                    <td class="bank-td-status">
                                        <span
                                            class="bank-status-badge bank-pending">{{ _price($txn->closing_balance) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($transactions) !== 0)
                    <hr>
                @endif
                <div class="page-area">
                    {!! $transactions->links() !!}
                </div>
                @if (count($transactions) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif

            </div>
        </div>
    </div>
    @include('vendor-views.form_modals.financial_year_select')

    @include('vendor-views.form_modals.pos_calendar')

@endsection

@push('script_2')
    @include('vendor-views/js/date_range')

    @include('vendor-views.salespoint.calendar-js')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $('#importTxnModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var id = button.data('id'); // Extract info from data-* attributes
            var html = $('.folder-content_' + id).clone(); // Clone instead of move
            var modal = $(this);
            $(".bank_account_inp").val(id);
            modal.find('.bank_info').html(html);
        });
        // Add loading animation effect
        document.addEventListener('DOMContentLoaded', function() {
            const transactionItems = document.querySelectorAll('.bank-transaction-row');
            transactionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Add click effects to action buttons
        document.querySelectorAll('.bank-action-btn').forEach(button => {
            button.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
@endpush
