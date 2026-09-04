@extends('layouts.vendor.app')

@section('title', 'Add New')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <style>
        .je-main-container {
            {{-- max-width: 1200px; --}} margin: 0 auto;
        }

        .je-header-section {
            margin-bottom: 24px;
        }

        .je-title-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .je-icon-book {
            width: 32px;
            height: 32px;
            color: #2563eb;
        }

        .je-main-title {
            font-size: 30px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .je-subtitle {
            color: #6b7280;
            margin: 0;
            font-size: 14px;
        }

        .je-search-wrapper {
            margin-bottom: 24px;
            position: relative;
        }

        .je-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #9ca3af;
        }

        .je-search-input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            box-sizing: border-box;
        }

        .je-search-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .je-table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .je-table {
            width: 100%;
            border-collapse: collapse;
        }

        .je-table-head {
            background-color: #f9fafb;
            border-bottom: 1px solid #c8d2e0;
        }

        .je-table-head th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        .je-th-expand {
            {{-- width: 48px; --}}
        }

        .je-th-date {
            {{-- width: 120px; --}}
        }

        .je-th-reference {
            {{-- width: 140px; --}}
        }

        .je-th-amount {
            {{-- width: 140px; --}} {{-- text-align: right; --}}
        }

        .je-table-row {
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .je-table-row:hover {
            background-color: #f9fafb;
        }

        .je-table-row td {
            padding: 16px;
            font-size: 14px;
        }

        .je-expand-cell {
            text-align: center;
        }

        .je-chevron-icon {
            width: 20px;
            height: 20px;
            color: #9ca3af;
            transition: transform 0.2s;
        }

        .je-chevron-icon.je-rotated {
            transform: rotate(180deg);
        }

        .je-date-text {
            color: #6b7280;
            font-size: 13px;
        }

        .je-reference-badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #dbeafe;
            color: #1e40af;
            font-size: 12px;
            font-weight: 500;
            border-radius: 4px;
        }

        .je-description-text {
            color: #111827;
        }

        .je-amount-text {
            {{-- text-align: right; --}} font-weight: 600;
            color: #111827;
        }

        .je-expanded-row {
            background-color: #f9fafb;
            display: none;
        }

        .je-expanded-cell {
            padding: 16px !important;
        }

        .je-detail-container {
            margin-left: 32px;
        }

        .je-detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .je-detail-table thead th {
            padding: 8px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #6b7280;
        }

        .je-detail-col-debit,
        .je-detail-col-credit {
            width: 140px;
            text-align: right;
        }

        .je-detail-table tbody td {
            padding: 8px 12px;
            font-size: 13px;
            color: #374151;
        }

        .je-detail-amount {
            {{-- font-family: 'Courier New', monospace; --}} color: #111827;
        }

        .je-empty-row td {
            padding: 48px;
            text-align: center;
            color: #6b7280;
        }

        .je-summary-container {
            margin-top: 24px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .je-summary-label {
            color: #6b7280;
            font-weight: 500;
        }

        .je-summary-value {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .je-hidden {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Journal Entries</h1>
            <div class="page-header-select-wrapper d-flex gap-2 flex-wrap">
                <form action="" class="input-group" style="max-width: 270px;">
                    <input type="text" value="{{ request('search') ?? '' }}" name="search" class="form-control"
                        placeholder="Search By Voucher or Description">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">
                            <i class="tio-search"></i>
                        </button>
                    </div>
                </form>

                <form action="" class=" date-range-form">
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    {{-- date range modal --}}
                    @include('vendor-views/form_modals/date_range')
                </form>
                {{-- @if (hasPermission('boa_journal_entry', 'import'))
                    <button data-toggle="modal" data-target="#importExcelModal"
                        class=" btn_sm btn btn-outline-primary">Import
                    </button>
                @endif --}}
                {{-- @if (hasPermission('boa_journal_entry', 'export'))
                    <a class="btn btn-outline-primary  btn_sm " href="{{ route('vendor.account.journal-entry.export') }}">
                        Export </a>
                @endif --}}
            </div>
        </div>
        <!-- End Page Header -->
                @if (hasPermission('boa_journal_entry', 'list'))

        <div class="je-main-container">
            <div class="je-table-container">
                <table class="je-table">
                    <thead class="je-table-head">
                        <tr>
                            <th class="je-th-expand"></th>
                            <th class="je-th-date">Date</th>
                            <th class="je-th-reference">Voucher Number</th>
                            <th>Description</th>
                            <th class="je-th-amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="jeTableBody">
                        @forelse($journalEntries as $entry)
                            <tr class="je-table-row je-searchable" onclick="jeToggleExpand({{ $entry->id }})">
                                <td class="je-expand-cell">
                                    <svg class="je-chevron-icon je-chevron-{{ $entry->id }}" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </td>
                                <td class="je-date-text">{{ \Carbon\Carbon::parse($entry->voucher_date)->format('M d, Y') }}
                                </td>
                                <td>
                                    <span class="je-reference-badge">{{ $entry->voucher_number }}</span>
                                </td>
                                <td class="je-description-text">{{ $entry->narration }}</td>
                                <td class="je-amount-text">{{ _price($entry->total_amount) }}</td>
                            </tr>
                            <tr class="je-expanded-row je-detail-row-{{ $entry->id }} je-searchable-detail">
                                <td colspan="5" class="je-expanded-cell">
                                    <div class="je-detail-container">
                                        <table class="je-detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th class="je-detail-col-debit">Debit</th>
                                                    <th class="je-detail-col-credit">Credit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($entry->ledgerEntries as $line)
                                                    @php $store_user = _userByLedgerAccountId($line->account?->id);@endphp
                                                    <tr>
                                                        <td>
                                                            @if ($store_user)
                                                                <a
                                                                    href="{{ route('vendor.customer.view', [$store_user->id]) }}">{{ $line->account?->name }}</a>
                                                            @else
                                                                {{ $line->account?->name }}
                                                            @endif
                                                        </td>
                                                        <td class="je-detail-amount" style="text-align: left;">
                                                            {{ $line->debit > 0 ? _price($line->debit) : '' }}
                                                        </td>
                                                        <td class="je-detail-amount" style="text-align: left;">
                                                            {{ $line->credit > 0 ? _price($line->credit) : '' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="je-empty-row">
                                <td colspan="5">No entries found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


        </div>
        @endif

    </div>
                @if (hasPermission('boa_journal_entry', 'import'))

    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                    <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('vendor.account.journal-entry.import') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <a href="{{ asset('storage/app/public/util/journal_entry_example.xlsx') }}" download
                            class="btn btn-outline-primary mb-2">View Example</a>
                        <div class="form-group">
                            <label for="file">Upload Excel File</label>
                            <input type="file" style="height: 46px !important;" name="file" class="form-control"
                                id="file" accept=".xlsx,.xls">
                        </div>
                        <div class="form-group w-100 ">
                            <button type="submit" class="btn btn-primary float-right">Import</button>
                        </div>
                    </form>
                </div>
                <div class="p-3">
                    <h4>How It Works</h4>
                    <ol>
                        <li><span class="text-danger"> Do not edit or delete column headings.</span></li>
                        <li><strong>Date</strong><br> Entry date in <code>dd-mm-yy</code> format</li>
                        <li><strong>Credit Account Id, Debit Account Id</strong><br> Ids can be found in <code>Accounts
                                Management > Settings > Chart of Accounts</code> </li>
                        <li><strong>Required Fields : </strong> <code>Credit Account Id</code>, <code>Debit Account
                                Id</code>, <code>Amount</code></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('script_2')
    <script>
        // Toggle expand/collapse for detail rows
        function jeToggleExpand(id) {
            var detailRow = document.querySelector('.je-detail-row-' + id);
            var chevron = document.querySelector('.je-chevron-' + id);

            if (detailRow.style.display === 'none' || detailRow.style.display === '') {
                detailRow.style.display = 'table-row';
                chevron.classList.add('je-rotated');
            } else {
                detailRow.style.display = 'none';
                chevron.classList.remove('je-rotated');
            }
        }

        // Search functionality
        document.getElementById('jeSearchInput').addEventListener('input', function(e) {
            var searchTerm = e.target.value.toLowerCase();
            var rows = document.querySelectorAll('.je-searchable');
            var visibleCount = 0;

            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                var nextRow = row.nextElementSibling;

                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                    // Also hide the detail row if main row is hidden
                    if (nextRow && nextRow.classList.contains('je-searchable-detail')) {
                        nextRow.style.display = 'none';
                    }
                }
            });

            document.getElementById('jeTotalCount').textContent = visibleCount;

            // Handle empty state
            var tbody = document.getElementById('jeTableBody');
            var emptyRow = tbody.querySelector('.je-empty-row');

            if (visibleCount === 0 && !emptyRow) {
                tbody.innerHTML =
                    '<tr class="je-empty-row je-search-empty"><td colspan="5">No entries found matching your search</td></tr>';
            } else if (visibleCount > 0) {
                var searchEmpty = tbody.querySelector('.je-search-empty');
                if (searchEmpty) {
                    searchEmpty.remove();
                }
            }
        });
    </script>
    @include('vendor-views/js/date_range')
@endpush
