@extends('layouts.admin.app')

@section('title', $store->name . ' - Monetization')

@section('content')
<div class="content container-fluid">
    @include('admin-views.vendor.view.partials._header', ['store' => $store])

    <!-- Stats Cards -->
    <div class="row g-2 mb-3 mt-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body py-3 px-3">
                <div style="font-size:24px;font-weight:700;color:#28a745">{{ \App\CentralLogics\Helpers::format_currency($totalRecharges) }}</div>
                <small class="text-muted">Total Recharges</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body py-3 px-3">
                <div style="font-size:24px;font-weight:700;color:#dc3545">{{ \App\CentralLogics\Helpers::format_currency($totalSpends) }}</div>
                <small class="text-muted">Total Spends</small>
            </div>
        </div> 
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body py-3 px-3">
                <div style="font-size:24px;font-weight:700;color:#6f42c1">{{ \App\CentralLogics\Helpers::format_currency($totalSubscriptions) }}</div>
                <small class="text-muted">Subscription Purchases</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body py-3 px-3">
                <div style="font-size:24px;font-weight:700;color:#007bff">{{ \App\CentralLogics\Helpers::format_currency($wallet->balance ?? 0) }}</div>
                <small class="text-muted">Current Wallet Balance</small>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-0" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#recharges-tab">
                Recharges <span class="badge badge-soft-success ml-1">{{ $recharges->total() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#spends-tab">
                Spends <span class="badge badge-soft-danger ml-1">{{ $spends->total() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#subscriptions-tab">
                Subscriptions <span class="badge badge-soft-info ml-1">{{ $subscriptions->total() }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Recharges -->
        <div class="tab-pane fade show active" id="recharges-tab">
            <div class="card border-top-0 rounded-top-0">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Amount</th>
                                <th>Balance After</th>
                                <th>Method</th>
                                <th>By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recharges as $i => $r)
                            <tr>
                                <td>{{ $recharges->firstItem() + $i }}</td>
                                <td class="text-success font-weight-bold">+{{ \App\CentralLogics\Helpers::format_currency($r->amount) }}</td>
                                <td>{{ \App\CentralLogics\Helpers::format_currency($r->current_balance) }}</td>
                                <td>{{ ucfirst($r->method ?? '-') }}</td>
                                <td>{{ ucfirst($r->created_by ?? 'admin') }}</td>
                                <td>{{ $r->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No recharges found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0 py-2">
                    {{ $recharges->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        <!-- Spends -->
        <div class="tab-pane fade" id="spends-tab">
            <div class="card border-top-0 rounded-top-0">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Balance After</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($spends as $i => $s)
                            <tr>
                                <td>{{ $spends->firstItem() + $i }}</td>
                                <td class="text-danger font-weight-bold">-{{ \App\CentralLogics\Helpers::format_currency($s->amount) }}</td>
                                <td>{{ $s->reason ?? '-' }}</td>
                                <td>{{ \App\CentralLogics\Helpers::format_currency($s->current_balance) }}</td>
                                <td>{{ $s->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No spends found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0 py-2">
                    {{ $spends->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        <!-- Subscriptions -->
        <div class="tab-pane fade" id="subscriptions-tab">
            <div class="card border-top-0 rounded-top-0">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Plan</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Expiry</th>
                                <th>Purchased</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscriptions as $i => $sub)
                            <tr>
                                <td>{{ $subscriptions->firstItem() + $i }}</td>
                                <td>{{ $sub->plan->title ?? 'N/A' }}</td>
                                <td>{{ $sub->duration_count }} {{ ucfirst($sub->duration_type ?? '') }}</td>
                                <td class="text-info font-weight-bold">{{ \App\CentralLogics\Helpers::format_currency($sub->purchased_at) }}</td>
                                <td>
                                    @if($sub->plan_expiry)
                                        <span class="{{ \Carbon\Carbon::parse($sub->plan_expiry)->isPast() ? 'text-danger' : 'text-success' }}">
                                            {{ \Carbon\Carbon::parse($sub->plan_expiry)->format('d M Y') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $sub->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No subscriptions found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0 py-2">
                    {{ $subscriptions->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
