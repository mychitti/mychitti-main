@extends('layouts.vendor.app')
@section('title', 'Batch & Expiry Tracking')

@section('content') 
<div class="content container-fluid">
    @include('hmis::vendor-views.partials._pharmacy_header')
    <div class="pharmacy-page-content">
 
    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="card text-center p-3 border-danger">
                <div class="text-danger" style="font-size:2rem; font-weight:700;">{{ $expiredCount }}</div>
                <div class="text-muted small">Expired Batches</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3 border-warning">
                <div class="text-warning" style="font-size:2rem; font-weight:700;">{{ $expiringSoonCount }}</div>
                <div class="text-muted small">Expiring Within 90 Days</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3">
                <div style="font-size:2rem; font-weight:700;">{{ $totalBatches }}</div>
                <div class="text-muted small">Total Tracked Batches</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
            <h3 class="mb-0">Batch & Expiry Records</h3>
            <form action="" class="d-flex gap-2 flex-wrap align-items-center ml-auto">
                <select name="filter" onchange="this.form.submit()" class="form-control form-control-sm" style="min-width:180px; height: 35px;">
                    <option value="all"          {{ $filter === 'all'          ? 'selected' : '' }}>All Batches</option>
                    <option value="expired"      {{ $filter === 'expired'      ? 'selected' : '' }}>Expired</option>
                    <option value="expiring_soon"{{ $filter === 'expiring_soon'? 'selected' : '' }}>Expiring Soon (90 days)</option>
                    <option value="active"       {{ $filter === 'active'       ? 'selected' : '' }}>Active / No Expiry</option>
                </select>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th>SKU</th>
                        <th>Batch No.</th>
                        <th>Bill No.</th>
                        <th>Entry Date</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        @php
                            $expStatus = 'active';
                            $badgeClass = 'badge-soft-success';
                            $statusLabel = 'Active';
                            if ($batch->expiry_date) {
                                $exp = \Carbon\Carbon::parse($batch->expiry_date);
                                if ($exp->isPast()) {
                                    $expStatus = 'expired';
                                    $badgeClass = 'badge-soft-danger';
                                    $statusLabel = 'Expired';
                                } elseif ($exp->diffInDays(now()) <= 90) {
                                    $expStatus = 'expiring_soon';
                                    $badgeClass = 'badge-soft-warning';
                                    $statusLabel = 'Expiring Soon';
                                }
                            } else {
                                $badgeClass = 'badge-soft-secondary';
                                $statusLabel = 'No Expiry';
                            }
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $batch->item_name }}</td>
                            <td><span class="badge badge-soft-dark">{{ $batch->sku_id }}</span></td>
                            <td><strong>{{ $batch->batch_number }}</strong></td>
                            <td>{{ $batch->bill_number ?? '—' }}</td>
                            <td>{{ $batch->date }}</td>
                            <td>{{ $batch->quantity }}</td>
                            <td>
                                @if($batch->expiry_date)
                                    {{ \Carbon\Carbon::parse($batch->expiry_date)->format('d M Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" height="60" alt="">
                                <p class="mt-2 text-muted">No batch records found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-3 pb-3">
            {{ $batches->appends(request()->query())->links() }}
        </div>
    </div>

    </div>
</div>
@endsection
