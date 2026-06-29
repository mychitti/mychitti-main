@extends('layouts.admin.app')

@section('title', translate('WhatsApp Lead Notifications'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-chat" style="font-size:22px;"></i>
                </span>
                <span>{{ translate('WhatsApp Lead Notifications') }}</span>
            </h1>
            <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-settings"></i> {{ translate('WhatsApp Setup') }}
            </a>
        </div>

        <div class="card">
            <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center">
                <p class="mb-0 text-muted" style="font-size:13px;">
                    {{ translate('Enable WhatsApp lead notifications per vendor. Billing generates a bill (charges the vendor wallet ₹ monthly); Retail enables it without generating a bill. Delivery requires the vendor to have connected WhatsApp.') }}
                </p>
                <form method="get" class="d-flex" style="gap:8px;">
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
                                <th>{{ translate('Type') }}</th>
                                <th class="text-center">{{ translate('WhatsApp') }}</th>
                                <th class="text-right">{{ translate('Wallet') }}</th>
                                <th>{{ translate('Lead Notifications') }}</th>
                                <th class="text-right">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stores as $store)
                                @php
                                    $live = $store->feat_enabled && $store->feat_active_until && $store->feat_active_until >= now()->toDateString();
                                @endphp
                                <tr>
                                    <td>{{ $store->name }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ $store->business_type ?: '—' }}</span></td>
                                    <td class="text-center">
                                        @if ($store->wa_enabled)
                                            <span class="badge badge-soft-success">{{ translate('Connected') }}</span>
                                        @else
                                            <span class="badge badge-soft-danger" title="{{ translate('Vendor has not connected WhatsApp; messages will not be delivered until they do.') }}">{{ translate('Not connected') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ _price($store->wallet_balance ?? 0) }}</td>
                                    <td>
                                        @if ($live)
                                            <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                        @else
                                            <span class="badge badge-soft-secondary">{{ translate('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-lead-notifications-toggle') }}"
                                            class="d-flex justify-content-end align-items-center" style="gap:6px;">
                                            @csrf
                                            <input type="hidden" name="store_id" value="{{ $store->id }}">
                                            <input type="hidden" name="action" value="enable">
                                            <select name="mode" class="form-control form-control-sm" style="width:auto;">
                                                <option value="retail">{{ translate('Retail — no bill') }}</option>
                                                <option value="billing">{{ translate('Billing — generate bill ₹200/mo') }}</option>
                                            </select>
                                            <button class="btn btn-sm btn--primary">{{ $live ? translate('Renew / Update') : translate('Enable') }}</button>
                                        </form>
                                        @if ($live)
                                            <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-lead-notifications-toggle') }}"
                                                class="text-right mt-1" onsubmit="return confirm('{{ translate('Disable lead notifications for this store?') }}');">
                                                @csrf
                                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                                <input type="hidden" name="action" value="disable">
                                                <button class="btn btn-sm btn-outline-danger">{{ translate('Disable') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">{{ translate('No stores found.') }}</td>
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
