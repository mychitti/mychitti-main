@extends('layouts.admin.app')

@section('title', translate('WhatsApp Vendor Billing'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-shopping-cart" style="font-size:22px;"></i>
                </span>
                <span>{{ translate('WhatsApp Vendor Billing') }}</span>
            </h1>
            <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-settings"></i> {{ translate('WhatsApp Setup') }}
            </a>
        </div>

        <div class="card">
            <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center">
                <p class="mb-0 text-muted" style="font-size:13px;">
                    {{ translate('Enable a WhatsApp plan, settle the onboarding fee, add template slots or top up AI tokens for any vendor — billed to the store, or granted retail with no bill.') }}
                </p>
                <form method="get" class="d-flex" style="gap:8px;">
                    <select name="status" class="form-control form-control-sm" style="width:auto;">
                        <option value="">{{ translate('All stores') }}</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>{{ translate('Plan active') }}</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>{{ translate('No active plan') }}</option>
                        <option value="onboarded" {{ $status === 'onboarded' ? 'selected' : '' }}>{{ translate('Onboarding paid') }}</option>
                    </select>
                    <input type="search" name="search" value="{{ $search ?? '' }}" class="form-control form-control-sm"
                        placeholder="{{ translate('Search store…') }}">
                    <button class="btn btn-sm btn--primary">{{ translate('Search') }}</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-align-middle table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Store') }}</th>
                                <th class="text-center">{{ translate('WhatsApp') }}</th>
                                <th>{{ translate('Plan') }}</th>
                                <th>{{ translate('Valid till') }}</th>
                                <th class="text-center">{{ translate('Onboarding') }}</th>
                                <th class="text-center">{{ translate('Extra templates') }}</th>
                                <th class="text-right">{{ translate('Wallet') }}</th>
                                <th class="text-right">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stores as $store)
                                @php
                                    $live = $store->current_period_end && $store->current_period_end >= now()->toDateString();
                                    $planLabel = $store->plan && isset($plans[$store->plan]) ? $plans[$store->plan]['label'] : null;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.show', $store->id) }}">{{ $store->name }}</a>
                                        <div class="text-muted" style="font-size:11px;">{{ $store->business_type ?: '—' }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if ($store->wa_enabled)
                                            <span class="badge badge-soft-success">{{ translate('Connected') }}</span>
                                        @else
                                            <span class="badge badge-soft-secondary">{{ translate('Not connected') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($planLabel)
                                            {{ $planLabel }}
                                            @if ($store->account_manager)
                                                <span class="badge badge-soft-info">{{ translate('AM') }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($live)
                                            <span class="badge badge-soft-success">{{ \Carbon\Carbon::parse($store->current_period_end)->format('d M Y') }}</span>
                                        @elseif ($store->current_period_end)
                                            <span class="badge badge-soft-danger">{{ translate('Expired') }} {{ \Carbon\Carbon::parse($store->current_period_end)->format('d M Y') }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($store->setup_fee_paid)
                                            <i class="tio-checkmark-circle text-success"></i>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ (int) ($store->extra_template_slots ?? 0) ?: '—' }}</td>
                                    <td class="text-right">{{ _price($store->wallet_balance ?? 0) }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.show', $store->id) }}"
                                            class="btn btn-sm btn--primary">{{ translate('Manage') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">{{ translate('No stores found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($stores->hasPages())
                <div class="card-footer">
                    {!! $stores->links() !!}
                </div>
            @endif
        </div>
    </div>
@endsection
