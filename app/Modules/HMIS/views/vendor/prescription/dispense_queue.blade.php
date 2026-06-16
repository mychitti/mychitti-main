@extends('layouts.vendor.app')
@section('title', 'Pharmacy Dispense Queue')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        /* ── Workspace Grid ── */
        .pharmacy-workspace { 
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            padding: 20px;
            box-sizing: border-box;
        }
        .main-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .sidebar-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* ── Queue Table Card ── */
        .queue-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        .queue-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .queue-card-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .queue-card-header .actions {
            display: flex;
            gap: 8px;
        }
        .btn-header-action {
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            padding: 6px 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none !important;
        }
        .btn-header-action.outline {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            color: #475569;
        }
        .btn-header-action.outline:hover {
            background-color: #f8fafc;
        }
        .btn-header-action.solid-blue {
            background-color: #2563eb;
            color: #ffffff !important;
            border: none;
        }
        .btn-header-action.solid-blue:hover {
            background-color: #1d4ed8;
        }

        .queue-table {
            width: 100%;
            border-collapse: collapse;
        }
        .queue-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .queue-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 13px;
        }
        .queue-table tr:hover td {
            background-color: #f8fafc;
        }

        /* ── Custom circle avatar for Token ── */
        .token-circle {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 12px;
        }
        .token-circle.bg-0 { background-color: #e0f2fe; color: #0369a1; }
        .token-circle.bg-1 { background-color: #d1fae5; color: #047857; }
        .token-circle.bg-2 { background-color: #fce7f3; color: #be185d; }
        .token-circle.bg-3 { background-color: #ffedd5; color: #c2410c; }
        .token-circle.bg-4 { background-color: #f3e8ff; color: #6b21a8; }
        .token-circle.bg-5 { background-color: #ccfbf1; color: #0f766e; }

        /* ── Status pill badges ── */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-status.dispensing { background-color: #eff6ff; color: #1e40af; }
        .badge-status.waiting { background-color: #fffbeb; color: #92400e; }
        .badge-status.urgent { background-color: #fef2f2; color: #991b1b; }
        .badge-status.partial { background-color: #faf5ff; color: #6b21a8; }
        .badge-status.dispensed { background-color: #ecfdf5; color: #065f46; }

        /* ── Action buttons ── */
        .btn-action {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none !important;
        }
        .btn-action.done { background-color: #10b981; color: #ffffff !important; }
        .btn-action.done:hover { background-color: #059669; }
        .btn-action.dispense { background-color: #3b82f6; color: #ffffff !important; }
        .btn-action.dispense:hover { background-color: #2563eb; }
        .btn-action.priority { background-color: #ef4444; color: #ffffff !important; }
        .btn-action.priority:hover { background-color: #dc2626; }
        .btn-action.review { background-color: #ffffff; border-color: #cbd5e1; color: #475569 !important; }
        .btn-action.review:hover { background-color: #f1f5f9; }
        .btn-action.receipt { background-color: #ffffff; border-color: #cbd5e1; color: #475569 !important; }
        .btn-action.receipt:hover { background-color: #f1f5f9; }

        /* ── Sidebar Panels ── */
        .sidebar-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        .sidebar-panel-header {
            padding: 10px 16px;
            background-color: #fff5f5; /* Light red/pink accent for alerts */
            border-bottom: 1px solid #fee2e2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sidebar-panel-header.neutral {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .sidebar-panel-header h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sidebar-panel-header h4.alert-title {
            color: #991b1b;
        }
        .sidebar-panel-body {
            padding: 14px;
        }

        /* ── Urgent Alerts List ── */
        .alert-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .alert-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 5px;
            flex-shrink: 0;
        }
        .alert-dot.red { background-color: #ef4444; }
        .alert-dot.orange { background-color: #f97316; }
        .alert-dot.purple { background-color: #9333ea; }
        .alert-content {
            font-size: 12px;
            color: #1e293b;
            line-height: 1.4;
        }
        .alert-content strong {
            font-weight: 600;
            color: #0f172a;
        }
        .alert-content span.subtext {
            display: block;
            font-size: 11px;
            color: #64748b;
        }
        .btn-emergency-po {
            background-color: #dc2626;
            color: #ffffff !important;
            width: 100%;
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            display: block;
            margin-top: 14px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none !important;
        }
        .btn-emergency-po:hover {
            background-color: #b91c1c;
        }

        /* ── Today's Stats List ── */
        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .stats-item {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            border-bottom: 1px dashed #f1f5f9;
            padding-bottom: 6px;
        }
        .stats-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .stats-item .lbl {
            color: #64748b;
        }
        .stats-item .val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .pharmacy-workspace {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush 
  
@php 
    $storeId = \App\CentralLogics\Helpers::get_store_id();

    // 1. Queue Today
    $todayPrescriptions = \App\Models\Prescription::where('store_id', $storeId)
        ->where('is_finalized', true)
        ->whereDate('created_at', today())
        ->get();
    $queueTodayCount = $todayPrescriptions->count();
    $pendingCount = $todayPrescriptions->filter(function($rx) {
        return $rx->items->where('dispensed', false)->count() > 0;
    })->count();

    // 2. Dispensed Today
    $dispensedTodayCount = $todayPrescriptions->filter(function($rx) {
        $total = $rx->items->count();
        $dispensed = $rx->items->where('dispensed', true)->count();
        return $total > 0 && $dispensed === $total;
    })->count();

    // 8. Urgent Alerts list (out of stock + low stock + expiring soon)
    // Out of Stock (up to 2)
    $alertOutOfStock = \App\Models\InventoryItem::where('store_id', $storeId)
        ->where('item_type', 'product')
        ->where('stock', '<=', 0)
        ->limit(2)
        ->get()
        ->map(function($item) {
            return [
                'type' => 'out_of_stock',
                'name' => $item->item_name,
                'detail' => ' — OUT OF STOCK',
                'subtext' => 'Reorder immediately',
                'color' => 'red'
            ];
        });

    // Low Stock (up to 2)
    $alertLowStock = \App\Models\InventoryItem::where('store_id', $storeId)
        ->where('item_type', 'product')
        ->where('stock', '>', 0)
        ->where('stock', '<', 5)
        ->limit(2)
        ->get()
        ->map(function($item) {
            return [
                'type' => 'low_stock',
                'name' => $item->item_name,
                'detail' => ' — Only ' . $item->stock . ' tabs left',
                'subtext' => 'Below reorder level',
                'color' => 'orange'
            ];
        });

    // Expiring Soon (up to 2)
    $alertExpiring = \App\Models\ItemEntry::where('store_id', $storeId)
        ->whereNotNull('expiry_date')
        ->whereDate('expiry_date', '>=', today())
        ->whereDate('expiry_date', '<=', today()->addDays(90))
        ->whereHas('item', function($q) {
            $q->where('stock', '>', 0);
        })
        ->with('item')
        ->limit(2)
        ->get()
        ->map(function($entry) {
            return [
                'type' => 'expiring',
                'name' => $entry->item?->item_name ?? 'Medicine',
                'detail' => ' — Expiring ' . \Carbon\Carbon::parse($entry->expiry_date)->format('M Y'),
                'subtext' => ($entry->quantity ?? 0) . ' units left - use first',
                'color' => 'purple'
            ];
        });

    $allAlerts = $alertOutOfStock->concat($alertLowStock)->concat($alertExpiring)->take(5);
@endphp

@section('content')
<div class="content container-fluid">
 
    @include('hmis::vendor-views.partials._pharmacy_header')

    {{-- 4. WORKSPACE GRID --}}
    <div class="pharmacy-workspace">
        {{-- Left main column --}}
        <div class="main-column">
            {{-- Inline Filter bar --}}
            <form method="GET" class="card p-3 d-flex flex-wrap gap-2 flex-row align-items-center mb-0 date-range-form" style="border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                @include('vendor-views/form_modals/date_range')
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#dateRangeModal" style="border-radius: 8px; font-weight: 500; height: 38px;">
                    <i class="tio-calendar"></i> {{ translate($preset) }}
                </button>
                <div class="position-relative flex-grow-1" style="min-width: 220px;">
                    <i class="tio-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="text" name="patient" class="form-control form-control-sm pl-5" style="border-radius: 8px; border-color: #cbd5e1; height: 38px;"
                        placeholder="Search Patient Name or UID..." value="{{ request('patient') }}">
                </div>
                <div>
                    <select name="filter" class="form-control form-control-sm" style="border-radius: 8px; border-color: #cbd5e1; height: 38px; min-width: 160px; -webkit-appearance: listbox;">
                        <option value="pending" {{ request('filter','pending') === 'pending' ? 'selected' : '' }}>Pending dispense</option>
                        <option value="all"     {{ request('filter') === 'all' ? 'selected' : '' }}>All finalized</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn--primary px-3" style="border-radius: 8px; height: 38px; font-weight: 600;">Filter</button>
                <a href="{{ route('vendor.prescription.dispense.queue') }}" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 8px; height: 38px; display: inline-flex; align-items: center;">Reset</a>
            </form>

            @if(hasPermission('pharmacy_dispense_queue', 'list'))
            <div class="queue-card">
                <div class="queue-card-header">
                    <h3>
                        <i class="tio-medicine" style="color: #3b82f6; font-size: 18px;"></i>
                        Today's Dispense Queue
                    </h3>
                    <div class="actions">
                        <a href="javascript:location.reload();" class="btn-header-action outline">
                            <i class="tio-refresh"></i> Refresh
                        </a>
                        <a href="{{ route('vendor.pharmacy.walkin') }}" class="btn-header-action solid-blue">
                            + Walk-in Sale
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">TOKEN</th>
                                <th>PATIENT</th>
                                <th>PRESCRIPTION</th>
                                <th>MEDICINES</th>
                                <th>STATUS</th>
                                <th>WAIT</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prescriptions as $index => $rx)
                                @php
                                    $total      = $rx->items->count();
                                    $dispensed  = $rx->items->where('dispensed', true)->count();
                                    $allDone    = $total > 0 && $dispensed === $total;

                                    // Calc token circle color class index
                                    $colorIndex = $index % 6;

                                    // Wait time in minutes
                                    $waitTime = $allDone ? '—' : \Carbon\Carbon::parse($rx->created_at)->diffInMinutes(now()) . ' min';

                                    // Token number from appointment or prescription id fallback
                                    $tokenNo = $rx->appointment?->token?->token_number ? 'T-' . str_pad($rx->appointment->token->token_number, 2, '0', STR_PAD_LEFT) : 'T-' . str_pad($rx->id, 2, '0', STR_PAD_LEFT);

                                    // Medicines stock status
                                    $outOfStockInRx = $rx->items->filter(function($i) {
                                        return !$i->inventory_item_id || ($i->inventoryItem && $i->inventoryItem->stock <= 0);
                                    })->count();

                                    $medNames = $rx->items->take(2)->pluck('medicine_name')->implode(', ');
                                    if ($rx->items->count() > 2) {
                                        $medNames .= '...';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="token-circle bg-{{ $colorIndex }}">
                                            {{ $tokenNo }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a;">{{ $rx->patient?->name }}</div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            {{ $rx->patient?->patient_uid }} — {{ $rx->appointment_id ? 'OPD' : 'IPD' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #2563eb; font-family: monospace;">
                                            RX-{{ $rx->created_at->format('Y-m') }}-{{ str_pad($rx->id, 4, '0', STR_PAD_LEFT) }}
                                        </div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            Dr. {{ $rx->doctorProfile?->employee?->f_name }} {{ $rx->doctorProfile?->employee?->l_name }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #334155;">{{ $total }} medicine(s)</div>
                                        @if($outOfStockInRx > 0)
                                            <div style="font-size: 11px; color: #ef4444; font-weight: 600;">
                                                {{ $outOfStockInRx }} out of stock
                                            </div>
                                        @else
                                            <div style="font-size: 11px; color: #64748b;">
                                                {{ $medNames }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($allDone)
                                            <span class="badge-status dispensed">✓ Dispensed</span>
                                        @elseif($dispensed > 0)
                                            <span class="badge-status partial">• Partial ({{ $dispensed }}/{{ $total }})</span>
                                        @elseif($outOfStockInRx > 0)
                                            <span class="badge-status urgent">• URGENT</span>
                                        @elseif($index === 0)
                                            <span class="badge-status dispensing">• Dispensing</span>
                                        @else
                                            <span class="badge-status waiting">Waiting</span>
                                        @endif
                                    </td>
                                    <td style="font-weight: 600; color: #475569;">
                                        {{ $waitTime }}
                                    </td>
                                    <td>
                                        @if (hasPermission('pharmacy_dispense_queue', 'dispense'))
                                            @if($allDone)
                                                <a href="{{ route('vendor.prescription.dispense.show', $rx->id) }}" class="btn-action receipt">
                                                    Receipt
                                                </a>
                                            @elseif($dispensed > 0)
                                                <a href="{{ route('vendor.prescription.dispense.show', $rx->id) }}" class="btn-action review">
                                                    Review
                                                </a>
                                            @elseif($outOfStockInRx > 0)
                                                <a href="{{ route('vendor.prescription.dispense.show', $rx->id) }}" class="btn-action priority">
                                                    Priority
                                                </a>
                                            @else
                                                <a href="{{ route('vendor.prescription.dispense.show', $rx->id) }}" class="btn-action dispense">
                                                    Dispense
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="tio-document-text d-block mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                                        No prescriptions found in dispense queue.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($prescriptions->hasPages())
                    <div class="card-footer px-4 py-3 border-top">
                        {{ $prescriptions->links() }}
                    </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Right sidebar column --}}
        <div class="sidebar-column">
            {{-- Panel 1: Urgent Alerts --}}
            <div class="sidebar-panel">
                <div class="sidebar-panel-header">
                    <h4 class="alert-title">
                        <i class="tio-warning mr-1"></i> Urgent Alerts
                    </h4>
                    <span class="badge badge-soft-danger font-size-11 font-weight-700">{{ $allAlerts->count() }} alerts</span>
                </div>
                <div class="sidebar-panel-body">
                    <div class="alert-list">
                        @forelse($allAlerts as $alert)
                            <div class="alert-item">
                                <span class="alert-dot {{ $alert['color'] }}"></span>
                                <div class="alert-content">
                                    <strong>{{ $alert['name'] }}</strong>{{ $alert['detail'] }}
                                    <span class="subtext">{{ $alert['subtext'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted font-size-12 py-3">
                                No urgent inventory alerts.
                            </div>
                        @endforelse
                    </div>

                    <a href="{{ route('vendor.inventory.purchase.orders') }}" class="btn-emergency-po">
                        Raise Emergency PO
                    </a>
                </div>
            </div>

            {{-- Panel 2: Today's Stats --}}
            <div class="sidebar-panel">
                <div class="sidebar-panel-header neutral">
                    <h4>
                        <i class="tio-chart-bar-4 mr-1"></i> Today's Stats
                    </h4>
                </div>
                <div class="sidebar-panel-body">
                    <div class="stats-list">
                        <div class="stats-item">
                            <span class="lbl">Total Tokens</span>
                            <span class="val">{{ $queueTodayCount }}</span>
                        </div>
                        <div class="stats-item">
                            <span class="lbl">Dispensed</span>
                            <span class="val" style="color: #10b981;">{{ $dispensedTodayCount }}</span>
                        </div>
                        <div class="stats-item">
                            <span class="lbl">Pending</span>
                            <span class="val" style="color: #ef4444;">{{ $pendingCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
