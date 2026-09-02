@extends('layouts.vendor.app')

@section('title', 'Bulk Send Details')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    <style>
        .wa-msg {
            background:#eefbf3; border:1px solid #d7f0e2; border-radius:12px; padding:12px 14px;
            font-size:13px; white-space:pre-wrap; color:var(--wa-ink);
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid wa-page">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-send"></i> {{ $run->template ?: 'Bulk send' }}</h1>
                <span class="wa-sub">
                    Sent {{ \Carbon\Carbon::parse($run->started_at)->format('d M Y, h:i A') }} ·
                    {{ number_format($run->recipients) }} {{ $run->recipients == 1 ? 'number' : 'numbers' }} ·
                    {{ $run->audience === 'platform' ? 'MyChitti customers' : 'My customers' }}
                </span>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <a href="{{ route('vendor.whatsapp.bulk.history') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="tio-back-ui"></i> All sends
                </a>
                @if (hasPermission('whatsapp_bulk', 'export'))
                    <a href="{{ route('vendor.whatsapp.bulk.history.export', $run->run_id) }}" class="btn btn-sm btn--primary">
                        <i class="tio-download"></i> Download CSV
                    </a>
                @endif
            </div>
        </div>

        <div class="row" style="row-gap:12px;">
            <div class="col-lg-5 wa-col">
                <div class="wa-card h-100">
                    <div class="wa-card-h">
                        The message
                        <span class="wa-sub">{{ $run->language ?: 'en_US' }}</span>
                    </div>
                    <div class="wa-card-b">
                        @if ($run->body)
                            <div class="wa-msg">{{ $run->body }}</div>
                            <div class="wa-sub mt-2">
                                Shown as one recipient received it — names and other filled-in details differ per person.
                            </div>
                        @else
                            <div class="wa-sub">
                                Template <b>{{ $run->template }}</b>. The wording wasn’t recorded for this send —
                                sends made from now on keep a copy of the message text.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-7 wa-col">
                <div class="row" style="row-gap:12px;">
                    <div class="col-sm-4">
                        <div class="wa-stat">
                            <div>
                                <div class="wa-stat-val text-success">{{ number_format($run->sent) }}</div>
                                <div class="wa-stat-lbl">Sent</div>
                            </div>
                            <div class="wa-stat-ico" style="background:#eefbf3;color:#128c7e;"><i class="tio-checkmark-circle"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wa-stat">
                            <div>
                                <div class="wa-stat-val {{ $run->failed ? 'text-danger' : '' }}">{{ number_format($run->failed) }}</div>
                                <div class="wa-stat-lbl">Failed</div>
                            </div>
                            <div class="wa-stat-ico" style="background:#fdecec;color:#dc2626;"><i class="tio-warning"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wa-stat">
                            <div>
                                <div class="wa-stat-val">{{ number_format($run->recipients) }}</div>
                                <div class="wa-stat-lbl">Numbers in batch</div>
                            </div>
                            <div class="wa-stat-ico" style="background:#eef7ff;color:#2563eb;"><i class="tio-user-big"></i></div>
                        </div>
                    </div>
                </div>

                <div class="wa-sub mt-3 mb-2">What the handsets reported back</div>
                <div class="row" style="row-gap:12px;">
                    <div class="col-sm-4">
                        <div class="wa-stat">
                            <div>
                                <div class="wa-stat-val text-success">{{ number_format($run->delivered) }}</div>
                                <div class="wa-stat-lbl">Delivered</div>
                            </div>
                            <div class="wa-stat-ico" style="background:#eefbf3;color:#128c7e;"><i class="tio-done-all"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wa-stat">
                            <div>
                                <div class="wa-stat-val text-info">{{ number_format($run->read) }}</div>
                                <div class="wa-stat-lbl">Read</div>
                            </div>
                            <div class="wa-stat-ico" style="background:#eef7ff;color:#2563eb;"><i class="tio-visible"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wa-stat">
                            <div>
                                <div class="wa-stat-val {{ $run->undelivered ? 'text-danger' : '' }}">{{ number_format($run->undelivered) }}</div>
                                <div class="wa-stat-lbl">Not delivered</div>
                            </div>
                            <div class="wa-stat-ico" style="background:#fdecec;color:#dc2626;"><i class="tio-clear-circle"></i></div>
                        </div>
                    </div>
                </div>
                <div class="wa-sub mt-2">
                    Delivered counts everyone who has it, read included.
                    @if ($run->awaiting)
                        {{ number_format($run->awaiting) }}
                        {{ $run->awaiting == 1 ? 'number has' : 'numbers have' }}
                        not reported back yet — WhatsApp sends these receipts as they happen.
                    @endif
                </div>

                @if ($run->queued)
                    <div class="alert alert-warning mt-3 mb-0" style="font-size:12px;">
                        {{ number_format($run->queued) }} {{ $run->queued == 1 ? 'number was' : 'numbers were' }}
                        held for this batch but never came back with a result — the send was interrupted part-way,
                        so we can’t say whether the message left. They show as <b>Queued</b> below.
                    </div>
                @endif

                @if ($masked)
                    <div class="alert alert-light mt-3 mb-0" style="font-size:12px;">
                        These are MyChitti customers, not your own contacts — their numbers are shown part-hidden.
                        Your own customers’ full numbers always show.
                    </div>
                @endif
            </div>
        </div>

        <div class="wa-card">
            <div class="wa-card-h flex-wrap" style="gap:8px;">
                <span>Numbers in this batch</span>
                <form method="get" class="d-flex align-items-center" style="gap:6px;">
                    <select name="status" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="">All results</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered / read</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm" style="width:190px;"
                           placeholder="{{ $masked ? 'Search name' : 'Search name or number' }}">
                    <button class="btn btn-sm btn-outline-secondary"><i class="tio-search"></i></button>
                </form>
            </div>
            <div class="wa-card-b flush">
                @if ($rows->isEmpty())
                    <div class="wa-empty">
                        <i class="tio-search"></i>
                        <div class="wa-empty-t">Nothing matches that</div>
                        <div class="wa-empty-s">Clear the search or the filter to see the whole batch.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table wa-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Number</th>
                                    <th>Sent at</th>
                                    <th>Result</th>
                                    <th>Delivery</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php
                                        $delivery = strtolower((string) $row->delivery_status);
                                        $deliveryChip = [
                                            'read'      => ['info', 'Read'],
                                            'delivered' => ['success', 'Delivered'],
                                            'sent'      => ['secondary', 'Sent'],
                                            'accepted'  => ['secondary', 'Accepted'],
                                            'failed'    => ['danger', 'Failed'],
                                        ][$delivery] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $row->name ?: 'Customer' }}</td>
                                        <td class="text-nowrap">{{ $row->phone }}</td>
                                        <td class="text-nowrap wa-sub">
                                            {{ $row->sent_at ? \Carbon\Carbon::parse($row->sent_at)->format('d M, h:i A') : '—' }}
                                        </td>
                                        <td>
                                            @if ($row->send_status === 'sent')
                                                <span class="wa-chip badge-soft-success">Sent</span>
                                            @elseif ($row->send_status === 'failed')
                                                <span class="wa-chip badge-soft-danger">Failed</span>
                                                @if ($row->error)
                                                    <div class="wa-sub text-danger" style="white-space:normal;">{{ $row->error }}</div>
                                                @endif
                                            @else
                                                <span class="wa-chip badge-soft-secondary">{{ ucfirst($row->send_status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($deliveryChip)
                                                <span class="wa-chip badge-soft-{{ $deliveryChip[0] }}">{{ $deliveryChip[1] }}</span>
                                                @if ($row->delivery_at)
                                                    <span class="wa-sub">{{ \Carbon\Carbon::parse($row->delivery_at)->format('d M, h:i A') }}</span>
                                                @endif
                                            @else
                                                <span class="wa-sub">Awaiting update</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                        <span class="wa-sub">
                            Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ number_format($rows->total()) }}
                        </span>
                        {!! $rows->links() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
