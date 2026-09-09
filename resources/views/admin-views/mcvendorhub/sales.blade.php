@extends('layouts.admin.app')

@section('title', translate('MC Vendorhub Sales'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-invoice"></i></span>
                <span>{{ translate('MC Vendorhub Sales') }}
                    <span class="badge badge-soft-dark ml-2">{{ $stores->total() }}</span>
                </span>
            </h1>
            <p class="mb-0 text-muted">
                {{ translate('Everything sold to vendors who are not listed on MyChitti — plan subscription and paid add-ons.') }}
            </p>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <form class="row g-2 w-100 align-items-end">
                    <div class="col-md-6">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search store name') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.mcvendorhub.sales') }}"
                            class="btn btn-outline-secondary w-100">{{ translate('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Store') }}</th>
                            <th>{{ translate('Plan') }}</th>
                            <th>{{ translate('Plan Expires') }}</th>
                            <th>{{ translate('WhatsApp Lead Notifications') }}</th>
                            <th>{{ translate('Add-on Expires') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stores as $key => $store)
                            @php($subscription = $store->subscriptions->first())
                            @php($addon = $addons->get($store->id))
                            <tr>
                                <td>{{ $stores->firstItem() + $key }}</td>
                                <td>
                                    <a href="{{ route('admin.store.view', $store->id) }}">{{ $store->name }}</a>
                                    <div class="font-size-sm text-muted">{{ $store->phone ?: '-' }}</div>
                                </td>
                                <td>
                                    @if ($subscription)
                                        {{ $subscription->plan->title ?? translate('N/A') }}
                                    @else
                                        <span class="badge badge-soft-secondary">{{ translate('No active plan') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $subscription?->plan_expiry ? \Carbon\Carbon::parse($subscription->plan_expiry)->format('d M Y') : '-' }}
                                </td>
                                <td>
                                    @if ($addon && $addon->enabled)
                                        <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                        @if ((float) $addon->price > 0)
                                            <span class="font-size-sm text-muted">({{ _price((float) $addon->price) }}/mo)</span>
                                        @else
                                            <span class="font-size-sm text-muted">({{ translate('Retail') }})</span>
                                        @endif
                                    @else
                                        <span class="badge badge-soft-secondary">{{ translate('Not active') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $addon && $addon->active_until ? \Carbon\Carbon::parse($addon->active_until)->format('d M Y') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <img class="w--120px mb-3"
                                        src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                                    <h5 class="mb-0">{{ translate('No vendors here yet') }}</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stores->hasPages())
                <div class="card-footer">
                    {!! $stores->links() !!}
                </div>
            @endif
        </div>
    </div>
@endsection
