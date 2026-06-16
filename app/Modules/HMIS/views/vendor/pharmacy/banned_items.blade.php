@extends('layouts.vendor.app')
@section('title', 'Pharmacy — Banned / Blocked Items')

@push('css_or_js')
    <style>
        .pill { font-size:10px; font-weight:700; padding:3px 9px; border-radius:100px; }
        .pill.govt{background:#fee2e2;color:#b91c1c}.pill.store{background:#e0e7ff;color:#4338ca}
        .pill.on{background:#dcfce7;color:#15803d}.pill.off{background:#f3f4f6;color:#6b7280}
        .banned-empty { text-align:center; color:#9aa1ab; padding:40px 16px; }
        .banned-warn { background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; margin-bottom:18px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        @include('hmis::vendor-views.partials._pharmacy_header')

        <div class="pharmacy-page-content">

            {{-- In-stock banned items warning --}}
            @if ($stockedBanned->count())
                <div class="banned-warn">
                    <div style="font-weight:700; color:#b91c1c; font-size:13px;">
                        <i class="tio-warning mr-1"></i> {{ $stockedBanned->count() }} banned/blocked item(s) are currently in your stock
                    </div>
                    <div style="font-size:12px; color:#7f1d1d; margin-top:4px;">
                        {{ $stockedBanned->pluck('item_name')->take(8)->implode(', ') }}{{ $stockedBanned->count() > 8 ? '…' : '' }}
                        — these will show a warning during walk-in sale and dispensing.
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
                    <h3 class="mb-0" style="font-size:15px; font-weight:700;">Banned / Blocked Items</h3>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        <form action="" method="get" class="input-group input-group-sm mb-0" style="max-width:240px;">
                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search item...">
                            <div class="input-group-append"><button class="btn btn-outline-secondary"><i class="tio-search"></i></button></div>
                        </form>
                        @if (hasPermission('pharmacy', 'add'))
                            <button class="btn btn-sm btn--primary" style="white-space:nowrap; font-weight:600;" data-toggle="modal" data-target="#addBannedModal">
                                <i class="tio-add mr-1"></i> Add Banned Item
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Item Name</th><th>Source</th><th>Reason</th><th>Status</th><th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($banned as $b)
                                    <tr>
                                        <td><div class="font-weight-bold">{{ $b->name }}</div></td>
                                        <td><span class="pill {{ $b->source }}">{{ strtoupper($b->source) }}</span></td>
                                        <td style="white-space:normal; max-width:360px;">{{ $b->reason ?: '—' }}</td>
                                        <td>@if ($b->status)<span class="pill on">Active</span>@else<span class="pill off">Paused</span>@endif</td>
                                        <td class="text-right">
                                            @if (hasPermission('pharmacy', 'edit'))
                                                <a class="btn btn-xs btn-outline-secondary" href="{{ route('vendor.pharmacy.banned-items.toggle', $b->id) }}">
                                                    {{ $b->status ? 'Pause' : 'Activate' }}
                                                </a>
                                            @endif
                                            @if (hasPermission('pharmacy', 'delete'))
                                                <a class="btn btn-xs btn-outline-danger" href="{{ route('vendor.pharmacy.banned-items.delete', $b->id) }}"
                                                    onclick="return confirm('Remove this item from the banned list?')"><i class="tio-delete"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="banned-empty">
                                        <div class="mt-2">No banned/blocked items yet. Click <strong>+ Add Banned Item</strong> to add medicines that should not be sold or dispensed.</div>
                                    </div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (hasPermission('pharmacy', 'add'))
        <div class="modal fade" id="addBannedModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ route('vendor.pharmacy.banned-items.save') }}" method="post">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Banned / Blocked Item</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="input-label">Medicine Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Nimesulide" required>
                                <small class="text-muted">Matching is by name (case-insensitive) against stocked / sold items.</small>
                            </div>
                            <div class="form-group">
                                <label class="input-label">Source</label>
                                <select name="source" class="form-control">
                                    <option value="store">Store-blocked</option>
                                    <option value="govt">Govt-banned</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="input-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="Optional — e.g. Banned by CDSCO, contraindicated..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white btn-sm" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn--primary btn-sm">Add to Banned List</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
