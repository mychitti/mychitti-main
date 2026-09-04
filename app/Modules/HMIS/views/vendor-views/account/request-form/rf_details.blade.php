@extends('layouts.vendor.app')

@section('title', 'Request Form Details')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .sth-timeline-container {
            {{-- max-width: 800px; --}} {{-- margin: 40px auto; --}} padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .sth-timeline-header {
            margin-bottom: 30px;
        }

        .sth-timeline-header h2 {
            margin: 0 0 8px 0;
            color: #1a1a1a;
            font-size: 24px;
        }

        .sth-timeline-header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .sth-timeline {
            position: relative;
            padding-left: 40px;
        }

        .sth-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .sth-timeline-item {
            position: relative;
            padding-bottom: 40px;
        }

        .sth-timeline-item:last-child {
            padding-bottom: 0;
        }

        .sth-timeline-marker {
            position: absolute;
            left: -33px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #e0e0e0;
        }

        .sth-timeline-marker.sth-status-closed {
            background: #22c55e;
            box-shadow: 0 0 0 2px #22c55e;
        }

        .sth-timeline-marker.sth-status-verify {
            background: #5ec522ff;
            box-shadow: 0 0 0 2px #5ec522ff;
        }

        .sth-timeline-marker.sth-status-resubmit {
            background: #2284c5ff;
            box-shadow: 0 0 0 2px #2284c5ff;
        }

        .sth-timeline-marker.sth-status-open {
            background: #3b82f6;
            box-shadow: 0 0 0 2px #3b82f6;
        }

        .sth-timeline-marker.sth-status-pending {
            background: #f59e0b;
            box-shadow: 0 0 0 2px #f59e0b;
        }

        .sth-timeline-marker.sth-status-in-progress,
        .sth-timeline-marker.sth-status-in_progress {
            background: #8b5cf6;
            box-shadow: 0 0 0 2px #8b5cf6;
        }

        .sth-timeline-marker.sth-status-rejected {
            background: #ef4444;
            box-shadow: 0 0 0 2px #ef4444;
        }

        .sth-timeline-content {
            background: #fff;
            border: 1px solid #c8d2e0;
            border-radius: 8px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.2s;
        }

        .sth-timeline-content:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sth-timeline-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .sth-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .sth-status-badge.sth-status-closed {
            background: #dcfce7;
            color: #166534;
        }

        .sth-status-badge.sth-status-open {
            background: #dbeafe;
            color: #1e40af;
        }

        .sth-status-badge.sth-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .sth-status-badge.sth-status-in-progress,
        .sth-status-badge.sth-status-in_progress {
            background: #ede9fe;
            color: #5b21b6;
        }

        .sth-status-badge.sth-status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .sth-timeline-date {
            color: #6b7280;
            font-size: 13px;
        }

        .sth-timeline-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f3f4f6;
        }

        .sth-detail-item {
            margin: 10px 0;
            display: flex;
            flex-direction: column;
        }

        .sth-detail-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #9ca3af;
            font-weight: 600;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .sth-detail-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }

        .sth-detail-value.sth-null {
            color: #9ca3af;
            font-style: italic;
        }

        .status_lg {
            font-size: 18px;

        }

        .main_details .sth-detail-item {
            background: white;
            padding: 10px;
            border-radius: 10px;
        }

        .main_section {
            position: sticky;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        {{-- <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Request Form Details</h1>
            </div>
            <div class="page-header-select-wrapper">

            </div>
        </div> --}}
        <!-- End Page Header -->
        <div class="row">
            <div class="sth-timeline-container col-md-6">
                <div class="sth-timeline-header">
                    <h2>Request Form Details</h2>
                    <p>Request Form ID: {{ $rf->request_number ?? '' }}</p>
                </div>

                <div class="">

                    <div class="sth-timeline-item main_section">

                        <div class="sth-timeline-content" style="background: #e7fffd">
                            <div class="sth-timeline-header-row">
                                <span> <span class="sth-status-badge status_lg sth-status-{{ $rf->status }}">
                                        {{ $rf->status }}
                                    </span></span>
                                <span class="sth-timeline-date">
                                    <b>Created At</b> <br>
                                    {{ \Carbon\Carbon::parse($rf->created_at)->format('M d, Y H:i:s') }}
                                </span>
                            </div>
                            <div class="sth-timeline-details main_details">
                                <div class="sth-detail-item">
                                    <span class="sth-detail-label">Request Number</span>
                                    <span class="sth-detail-value">#{{ $rf->request_number }}</span>
                                </div>
                                <div class="sth-detail-item">
                                    <span class="sth-detail-label">Requested By</span>
                                    <span class="sth-detail-value">
                                        {{ $rf->requestedBy?->f_name . ' ' . $rf->requestedBy?->l_name }}
                                    </span>
                                </div>
                                <div class="sth-detail-item">
                                    <span class="sth-detail-label">Requested To</span>
                                    <span class="sth-detail-value {{ $rf->request_to ? '' : 'sth-null' }}">
                                        {{ $rf->requestedTo?->f_name . ' ' . $rf->requestedTo?->l_name }}
                                    </span>
                                </div>

                                <div class="sth-detail-item">
                                    <span class="sth-detail-label">Created By</span>
                                    <span class="sth-detail-value">
                                        {{ $rf->created_by_type == 'vendor' ? $vendor?->f_name . ' ' . $vendor?->l_name : $rf->createdBy?->f_name . ' ' . $rf->createdBy?->l_name }}
                                    </span>
                                </div>
                                @if ($rf->date)
                                    <div class="sth-detail-item">
                                        <span class="sth-detail-label">Date</span>
                                        <span class="sth-detail-value">
                                            {{ \Carbon\Carbon::parse($rf->date)->format('M d, Y') }}
                                        </span>
                                    </div>
                                @endif
                                <div class="sth-detail-item">
                                    <span class="sth-detail-label">Created At</span>
                                    <span class="sth-detail-value">
                                        {{ \Carbon\Carbon::parse($rf->created_at)->format('M d, Y H:i:s') }}
                                    </span>
                                </div>
                                @if ($rf->doc_file)
                                    <div class="sth-detail-item">
                                        <span class="sth-detail-label">Supporting Documents Ref.</span>
                                        <span class="sth-detail-value">
                                            <a href="{{ asset('storage/app/public/store/documents/' . $rf->doc_file) }}"
                                                target="_blank" class="doc-link">📎 {{ $rf->doc_file }}</a>
                                        </span>
                                    </div>
                                @endif
                                @if ($rf->description)
                                    <div class="sth-detail-item">
                                        <span class="sth-detail-label">Description</span>
                                        <span class="sth-detail-value">
                                            {{ $rf->description }}
                                        </span>
                                    </div>
                                @endif
                                <div class="sth-detail-item">
                                    <span class="sth-detail-label">Amount</span>
                                    <span class="sth-detail-value">
                                        {{ _price($rf->amount) }} <br>
                                        <small>{{ _convertNumberToWords($rf->amount) }}</small>
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="sth-timeline-container col-md-6">
                <div class="sth-timeline-header">
                    <h2>Status Update History</h2>
                    <p></p>
                </div>

                <div class="sth-timeline">
                    @forelse($statusHistory as $item)
                        <div class="sth-timeline-item">
                            <div
                                class="sth-timeline-marker sth-status-{{ Str::lower(Str::replace(' ', '-', $item->status)) }}">
                            </div>
                            <div class="sth-timeline-content">
                                <div class="sth-timeline-header-row">
                                    <span
                                        class="sth-status-badge sth-status-{{ Str::lower(Str::replace(' ', '-', $item->status)) }}">
                                        {{ $item->status }}
                                    </span>
                                    <span class="sth-timeline-date">
                                        {{ \Carbon\Carbon::parse($item->updated_at)->format('M d, Y H:i:s') }}
                                    </span>
                                </div>
                                <div class="sth-timeline-details">

                                    <div class="sth-detail-item">
                                        <span class="sth-detail-label">Updated By</span>
                                        <span class="sth-detail-value">
                                            {{ $item->updated_by == 0 ? $vendor?->f_name . ' ' . $vendor?->l_name : $item->updatedBy->f_name . ' ' . $item->updatedBy->l_name . ' ' ?? 'User ' . $item->updated_by }}
                                        </span>
                                    </div>
                                    <div class="sth-detail-item">
                                        <span class="sth-detail-label">Sent To</span>
                                        <span class="sth-detail-value {{ $item->sent_to ? '' : 'sth-null' }}">
                                            @if ($item->sent_to)
                                                {{ $item->sentTo->f_name . ' ' . $item->sentTo->l_name ?? 'User ' . $item->sent_to }}
                                            @else
                                                Not assigned
                                            @endif
                                        </span>
                                    </div>
                                    <div class="sth-detail-item">
                                        <span class="sth-detail-label">Updated At</span>
                                        <span class="sth-detail-value">
                                            {{ \Carbon\Carbon::parse($item->created_at)->format('M d, Y H:i:s') }}
                                        </span>
                                    </div>

                                </div>
                                <div class="sth-detail-item">
                                    <span class="sth-detail-label">Remarks</span>
                                    <span class="sth-detail-value">
                                        {{ $item->remark }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sth-timeline-item">
                            <div class="sth-timeline-content">
                                <p style="text-align: center; color: #9ca3af; margin: 0;">No status history available</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>


    @endsection

    @push('script_2')
    @endpush
