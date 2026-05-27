@extends('layouts.vendor.app')
@section('title', 'Lead Subscription')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">Lead Subscription</h1>
    </div>

    {{-- Active subscriptions --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">My Subscriptions</h5></div>
        <div class="card-body p-0">
            @if ($subscriptions->isEmpty())
                <p class="p-3 text-muted mb-0">No active subscriptions.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Type</th>
                                <th>Plan</th>
                                <th>Starts</th>
                                <th>Expires</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptions as $sub)
                            <tr>
                                <td><span class="badge badge-soft-{{ $sub->type === 'dedicated' ? 'primary' : 'success' }}">{{ ucfirst($sub->type) }}</span></td>
                                <td>{{ $sub->plan->name ?? 'Manual Grant' }}</td>
                                <td>{{ \Carbon\Carbon::parse($sub->starts_at)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($sub->expires_at)->format('d M Y') }}</td>
                                <td>
                                    @if ($sub->expires_at >= now()->toDateString())
                                        <span class="badge badge-soft-success">Active</span>
                                    @else
                                        <span class="badge badge-soft-danger">Expired</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Available plans --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Available Plans </h5></div>
        <div class="card-body">
            @if ($plans->isEmpty())
                <p class="text-muted">No plans available at the moment.</p>
            @else
                <div class="row">
                    @foreach ($plans as $plan)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <span class="badge badge-soft-{{ $plan->type === 'dedicated' ? 'primary' : 'success' }} mb-2">{{ ucfirst($plan->type) }}</span>
                                <h5 class="mb-1">{{ $plan->name }}</h5>
                                <p class="text-muted mb-1">{{ $plan->duration_days }} days</p>
                                <h4 class="text-dark mb-3">{{ _price($plan->price) }}</h4>
                                <form action="{{ route('vendor.service.lead-subscription.buy') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <button type="submit" class="btn btn--primary btn-sm btn-block"
                                        {{ $walletBalance < $plan->price ? 'disabled' : '' }}>
                                        Buy
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
