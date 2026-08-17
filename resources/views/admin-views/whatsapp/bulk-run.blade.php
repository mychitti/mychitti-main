{{-- One platform bulk send, number by number: who it went to, what they were sent, and where
     it got to at the handset. --}}
@extends('layouts.admin.app')

@section('title', translate('Bulk Send Details'))

@push('css_or_js')
<style>
    .wbrun-msg { background:#eefbf3; border:1px solid #d7f0e2; border-radius:12px; padding:12px 14px;
        font-size:13px; white-space:pre-wrap; }
    .wbrun-stat { border:1px solid #e7eaf3; border-radius:10px; padding:14px 16px; background:#fff; }
    .wbrun-stat-val { font-size:22px; font-weight:600; line-height:1.2; }
    .wbrun-stat-lbl { font-size:12px; color:#677788; }
    .wbrun-sub { font-size:12px; color:#677788; }
</style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0">
                    <span class="page-header-icon"><i class="tio-send" style="font-size:22px;"></i></span>
                    <span>{{ $run->template ?: translate('Bulk send') }}</span>
                </h1>
                <span class="wbrun-sub">
                    {{ translate('Sent') }} {{ \Carbon\Carbon::parse($run->started_at)->format('d M Y, h:i A') }} ·
                    {{ number_format($run->recipients) }} {{ $run->recipients == 1 ? translate('number') : translate('numbers') }} ·
                    {{ \App\Http\Controllers\Admin\WhatsAppBulkController::audienceLabel($run->audience) }}
                </span>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <a href="{{ route('admin.business-settings.third-party.whatsapp-bulk', ['tab' => 'history']) }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="tio-back-ui"></i> {{ translate('All sends') }}
                </a>
                <a href="{{ route('admin.business-settings.third-party.whatsapp-bulk.export', $run->run_id) }}"
                   class="btn btn-sm btn-primary">
                    <i class="tio-download"></i> {{ translate('Download CSV') }}
                </a>
            </div>
        </div>

        <div class="row mb-3" style="row-gap:12px;">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ translate('The message') }}</h5>
                        <span class="wbrun-sub">{{ $run->language ?: 'en_US' }}</span>
                    </div>
                    <div class="card-body">
                        @if ($run->body)
                            <div class="wbrun-msg">{{ $run->body }}</div>
                            <div class="wbrun-sub mt-2">
                                {{ translate('Shown as one recipient received it — names and other filled-in details differ per person.') }}
                            </div>
                        @else
                            <div class="wbrun-sub">
                                {{ translate('Template') }} <b>{{ $run->template }}</b>.
                                {{ translate('The wording was not recorded for this send.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row" style="row-gap:12px;">
                    <div class="col-sm-4">
                        <div class="wbrun-stat">
                            <div class="wbrun-stat-val text-success">{{ number_format($run->sent) }}</div>
                            <div class="wbrun-stat-lbl">{{ translate('Sent') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wbrun-stat">
                            <div class="wbrun-stat-val {{ $run->failed ? 'text-danger' : '' }}">{{ number_format($run->failed) }}</div>
                            <div class="wbrun-stat-lbl">{{ translate('Failed') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wbrun-stat">
                            <div class="wbrun-stat-val">{{ number_format($run->recipients) }}</div>
                            <div class="wbrun-stat-lbl">{{ translate('Numbers in batch') }}</div>
                        </div>
                    </div>
                </div>

                <div class="wbrun-sub mt-3 mb-2">{{ translate('What the handsets reported back') }}</div>
                <div class="row" style="row-gap:12px;">
                    <div class="col-sm-4">
                        <div class="wbrun-stat">
                            <div class="wbrun-stat-val text-success">{{ number_format($run->delivered) }}</div>
                            <div class="wbrun-stat-lbl">{{ translate('Delivered') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wbrun-stat">
                            <div class="wbrun-stat-val text-info">{{ number_format($run->read) }}</div>
                            <div class="wbrun-stat-lbl">{{ translate('Read') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="wbrun-stat">
                            <div class="wbrun-stat-val {{ $run->undelivered ? 'text-danger' : '' }}">{{ number_format($run->undelivered) }}</div>
                            <div class="wbrun-stat-lbl">{{ translate('Not delivered') }}</div>
                        </div>
                    </div>
                </div>
                <div class="wbrun-sub mt-2">
                    {{ translate('Delivered counts everyone who has it, read included.') }}
                    @if ($run->awaiting)
                        {{ number_format($run->awaiting) }}
                        {{ $run->awaiting == 1 ? translate('number has') : translate('numbers have') }}
                        {{ translate('not reported back yet — WhatsApp sends these receipts as they happen.') }}
                    @endif
                </div>

                @if ($run->queued)
                    <div class="alert alert-warning mt-3 mb-0" style="font-size:12px;">
                        {{ number_format($run->queued) }}
                        {{ $run->queued == 1 ? translate('number was') : translate('numbers were') }}
                        {{ translate('held for this batch but never came back with a result — the send was interrupted part-way, so we cannot say whether the message left. They show as Queued below.') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <h5 class="card-title mb-0">{{ translate('Numbers in this batch') }}</h5>
                <form method="get" class="d-flex align-items-center" style="gap:6px;">
                    <select name="status" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="">{{ translate('All results') }}</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ translate('Sent') }}</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>{{ translate('Delivered / read') }}</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ translate('Failed') }}</option>
                    </select>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm" style="width:190px;"
                           placeholder="{{ translate('Search name or number') }}">
                    <button class="btn btn-sm btn-outline-secondary"><i class="tio-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                @if ($rows->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="tio-search" style="font-size:34px;opacity:.4;"></i>
                        <p class="mt-2 mb-0" style="font-size:13px;">
                            {{ translate('Nothing matches that. Clear the search or the filter to see the whole batch.') }}
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Recipient') }}</th>
                                    <th>{{ translate('Number') }}</th>
                                    <th>{{ translate('Sent at') }}</th>
                                    <th>{{ translate('Result') }}</th>
                                    <th>{{ translate('Delivery') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php($delivery = strtolower((string) $row->delivery_status))
                                    @php($deliveryChip = [
                                        'read'      => ['info', translate('Read')],
                                        'delivered' => ['success', translate('Delivered')],
                                        'sent'      => ['secondary', translate('Sent')],
                                        'accepted'  => ['secondary', translate('Accepted')],
                                        'failed'    => ['danger', translate('Failed')],
                                    ][$delivery] ?? null)
                                    <tr>
                                        <td>{{ $row->name ?: translate('Customer') }}</td>
                                        <td class="text-nowrap">{{ $row->phone }}</td>
                                        <td class="text-nowrap wbrun-sub">
                                            {{ $row->sent_at ? \Carbon\Carbon::parse($row->sent_at)->format('d M, h:i A') : '—' }}
                                        </td>
                                        <td>
                                            @if ($row->send_status === 'sent')
                                                <span class="badge badge-soft-success">{{ translate('Sent') }}</span>
                                            @elseif ($row->send_status === 'failed')
                                                <span class="badge badge-soft-danger">{{ translate('Failed') }}</span>
                                                @if ($row->error)
                                                    <div class="wbrun-sub text-danger" style="white-space:normal;">{{ $row->error }}</div>
                                                @endif
                                            @else
                                                <span class="badge badge-soft-secondary">{{ ucfirst($row->send_status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($deliveryChip)
                                                <span class="badge badge-soft-{{ $deliveryChip[0] }}">{{ $deliveryChip[1] }}</span>
                                                @if ($row->delivery_at)
                                                    <span class="wbrun-sub">{{ \Carbon\Carbon::parse($row->delivery_at)->format('d M, h:i A') }}</span>
                                                @endif
                                            @else
                                                <span class="wbrun-sub">{{ translate('Awaiting update') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-2 py-2 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                        <span class="wbrun-sub">
                            {{ translate('Showing') }} {{ $rows->firstItem() }}–{{ $rows->lastItem() }}
                            {{ translate('of') }} {{ number_format($rows->total()) }}
                        </span>
                        {!! $rows->links() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
