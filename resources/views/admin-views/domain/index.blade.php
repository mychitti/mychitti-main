@extends('layouts.admin.app')
@section('title', 'Custom Domain Management')

@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-globe" style="font-size:22px;"></i></span>
            Custom Domain Management
        </h1>
    </div>

    {{-- Stats Row --}}
    <div class="row mb-4">
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div style="font-size:28px; font-weight:700; color:#377dff;">{{ $stats['stores_with_domain'] }}</div>
                <div class="text-muted" style="font-size:12px;">Active Domains</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div style="font-size:28px; font-weight:700; color:#00c9a7;">{{ $stats['total_active'] }}</div>
                <div class="text-muted" style="font-size:12px;">Paid Activations</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div style="font-size:28px; font-weight:700; color:#e8534a;">₹{{ number_format($stats['total_revenue'], 2) }}</div>
                <div class="text-muted" style="font-size:12px;">Total Revenue</div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    @php $tab = session('tab', request('tab', 'stores')); @endphp
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'stores' ? 'active' : '' }}" href="{{ route('admin.custom-domain.index', ['tab' => 'stores']) }}">
                <i class="tio-store mr-1"></i> Store Domains
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('admin.custom-domain.index', ['tab' => 'history']) }}">
                <i class="tio-history mr-1"></i> Activation History
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'price' ? 'active' : '' }}" href="{{ route('admin.custom-domain.index', ['tab' => 'price']) }}">
                <i class="tio-dollar mr-1"></i> Price Setup
            </a>
        </li>
    </ul>

    {{-- ══ TAB: STORE DOMAINS ══════════════════════════════════════════ --}}
    @if($tab === 'stores')
    <div class="card">
        <div class="card-header py-2 border-0">
            <form class="d-flex align-items-center gap-2" method="GET">
                <input type="hidden" name="tab" value="stores">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="form-control form-control-sm" style="width:220px;" placeholder="Search store...">
                <select name="domain_filter" onchange="this.form.submit()" class="form-control form-control-sm" style="width:auto;">
                    <option value="active" {{ request('domain_filter') === 'active' ? 'selected' : '' }}>Has Domain</option>
                    <option value="none"   {{ request('domain_filter') === 'none'   ? 'selected' : '' }}>No Domain</option>
                </select>
                @if(request('search') || request('domain_filter'))
                    <a href="{{ route('admin.custom-domain.index', ['tab' => 'stores']) }}" class="btn btn-outline-secondary "><i class="tio-refresh"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:13px;">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Vendor</th>
                            <th>Custom Domain</th>
                            <th>Status</th>
                            <th>Activated On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stores as $i => $store)
                        @php
                            $purchase = \App\Models\DomainPurchase::where('store_id', $store->id)
                                ->where('status', 'active')->latest()->first();
                        @endphp
                        <tr>
                            <td>{{ $stores->firstItem() + $i }}</td>
                            <td>
                                <strong>{{ $store->name }}</strong><br>
                                <span class="text-muted" style="font-size:11px;">#{{ $store->id }}</span>
                            </td>
                            <td style="font-size:12px;">
                                {{ $store->vendor?->f_name }} {{ $store->vendor?->l_name }}<br>
                                <span class="text-muted">{{ $store->vendor?->email }}</span>
                            </td>
                            <td>
                                @if($store->domain)
                                    <a href="https://{{ $store->domain }}" target="_blank" style="font-size:12px;">
                                        {{ $store->domain }} <i class="tio-open-in-new" style="font-size:10px;"></i>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($store->domain)
                                    <span class="badge badge-soft-success">Active</span>
                                @else
                                    <span class="badge badge-soft-secondary">None</span>
                                @endif
                            </td>
                            <td style="font-size:12px;">
                                {{ $purchase?->activated_at?->format('d M Y') ?? '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No stores found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($stores->hasPages())
            <div class="px-3 py-2" style="border-top:1px solid #e7eaf3;">
                {{ $stores->appends(request()->except('page'))->links() }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ══ TAB: ACTIVATION HISTORY ════════════════════════════════════ --}}
    @if($tab === 'history')
    <div class="card">
        <div class="card-header py-2 border-0">
            <form class="d-flex align-items-center flex-wrap gap-2" method="GET">
                <input type="hidden" name="tab" value="history">
                <input type="text" name="store_id" value="{{ request('store_id') }}"
                    class="form-control form-control-sm" style="width:100px;" placeholder="Store ID">
                <select name="hist_status" class="form-control form-control-sm" style="width:auto;">
                    <option value="">All Status</option>
                    <option value="active"   {{ request('hist_status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="removed"  {{ request('hist_status') === 'removed'  ? 'selected' : '' }}>Removed</option>
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="width:140px;">
                <input type="date" name="to"   value="{{ request('to') }}"   class="form-control form-control-sm" style="width:140px;">
                <button type="submit" class="btn btn--primary btn-sm"><i class="tio-filter-list"></i> Filter</button>
                @if(request('store_id') || request('hist_status') || request('from') || request('to'))
                    <a href="{{ route('admin.custom-domain.index', ['tab' => 'history']) }}" class="btn btn-outline-secondary btn-sm"><i class="tio-refresh"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:13px;">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Domain</th>
                            <th>Charge</th>
                            <th>GST %</th>
                            <th>GST Amt</th>
                            <th>Total Paid</th>
                            <th>Transaction</th>
                            <th>Status</th>
                            <th>Activated</th>
                            <th>Removed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $i => $record)
                        <tr>
                            <td>{{ $history->firstItem() + $i }}</td>
                            <td>
                                <strong>{{ $record->store?->name ?? '—' }}</strong><br>
                                <span class="text-muted" style="font-size:11px;">#{{ $record->store_id }}</span>
                            </td>
                            <td style="font-size:12px;">{{ $record->domain }}</td>
                            <td>{{ $record->charge > 0 ? '₹'.number_format($record->charge, 2) : 'Free' }}</td>
                            <td>{{ $record->gst_percent > 0 ? $record->gst_percent.'%' : '—' }}</td>
                            <td>{{ $record->gst_amount > 0 ? '₹'.number_format($record->gst_amount, 2) : '—' }}</td>
                            <td><strong>{{ $record->total_amount > 0 ? '₹'.number_format($record->total_amount, 2) : 'Free' }}</strong></td>
                            <td style="font-size:11px; color:#677788;">{{ $record->transaction_id ?? '—' }}</td>
                            <td>
                                @if($record->status === 'active')
                                    <span class="badge badge-soft-success">Active</span>
                                @else
                                    <span class="badge badge-soft-danger">Removed</span>
                                @endif
                            </td>
                            <td style="font-size:12px;">{{ $record->activated_at?->format('d M Y') ?? '—' }}</td>
                            <td style="font-size:12px;">{{ $record->removed_at?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">No domain purchase history found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($history->hasPages())
            <div class="px-3 py-2" style="border-top:1px solid #e7eaf3;">
                {{ $history->appends(request()->except('page'))->links() }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ══ TAB: PRICE SETUP ════════════════════════════════════════════ --}}
    @if($tab === 'price')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Custom Domain Pricing</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.custom-domain.update-price') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label class="input-label">Domain Activation Charge (₹)
                                <small class="text-muted">— set 0 to offer for free</small>
                            </label>
                            <input type="number" name="charge" class="form-control"
                                value="{{ $priceSettings['charge'] }}" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label class="input-label">GST (%)</label>
                            <input type="number" name="gst_percent" class="form-control"
                                value="{{ $priceSettings['gst_percent'] }}" min="0" max="100" step="0.01" placeholder="0">
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="gstInclude"
                                    name="gst_include" {{ $priceSettings['gst_include'] ? 'checked' : '' }}>
                                <label class="custom-control-label" for="gstInclude">
                                    GST included in price above
                                    <small class="d-block text-muted">ON = GST is part of the charge. OFF = GST added on top.</small>
                                </label>
                            </div>
                        </div>

                        @php
                            $charge   = $priceSettings['charge'];
                            $gstPct   = $priceSettings['gst_percent'];
                            $gstIncl  = $priceSettings['gst_include'];
                            $total    = $charge > 0 ? ($gstIncl ? $charge : round($charge * (1 + $gstPct / 100), 2)) : 0;
                            $gstAmt   = $charge > 0 && $gstPct > 0
                                ? ($gstIncl ? round($charge - $charge / (1 + $gstPct / 100), 2) : round($charge * $gstPct / 100, 2))
                                : 0;
                        @endphp
                        @if($charge > 0)
                        <div class="alert alert-soft-info mb-3" style="font-size:13px;">
                            <strong>Current pricing preview:</strong><br>
                            Base charge: ₹{{ number_format($charge, 2) }}<br>
                            GST ({{ $gstPct }}%): ₹{{ number_format($gstAmt, 2) }}<br>
                            <strong>Total charged to vendor: ₹{{ number_format($total, 2) }}</strong>
                        </div>
                        @else
                        <div class="alert alert-soft-warning mb-3" style="font-size:13px;">
                            Custom domain is currently <strong>free</strong> for all stores.
                        </div>
                        @endif

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn--primary">Save Pricing</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
