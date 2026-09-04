@extends('layouts.admin.app')

@section('title', translate('MC Vendorhub Subscriptions'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-dollar"></i></span>
                <span>{{ translate('MC Vendorhub Subscriptions') }}
                    <span class="badge badge-soft-dark ml-2">{{ $subscriptions->total() }}</span>
                </span>
            </h1>
            <p class="mb-0 text-muted">
                {{ translate('Plan subscriptions held by vendors who are not listed on MyChitti.') }}
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
                        <select name="state" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All') }}</option>
                            <option value="active" {{ $state === 'active' ? 'selected' : '' }}>
                                {{ translate('Active') }}</option>
                            <option value="expired" {{ $state === 'expired' ? 'selected' : '' }}>
                                {{ translate('Expired') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.mcvendorhub.subscriptions') }}"
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
                            <th>{{ translate('Duration') }}</th>
                            <th>{{ translate('Purchased') }}</th>
                            <th>{{ translate('Expires') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subscriptions as $key => $row)
                            @php($expired = $row->plan_expiry && \Carbon\Carbon::parse($row->plan_expiry)->isPast())
                            <tr>
                                <td>{{ $subscriptions->firstItem() + $key }}</td>
                                <td>
                                    <a href="{{ route('admin.store.view', $row->store_id) }}">{{ $row->store_name }}</a>
                                    <div class="font-size-sm text-muted">{{ $row->store_phone ?: '-' }}</div>
                                </td>
                                <td>{{ $row->plan_name ?? translate('N/A') }}</td>
                                <td>{{ $row->duration_count ? $row->duration_count . ' ' . $row->duration_type : '-' }}
                                </td>
                                <td>{{ $row->purchased_at ? \Carbon\Carbon::parse($row->purchased_at)->format('d M Y') : '-' }}
                                </td>
                                <td>{{ $row->plan_expiry ? \Carbon\Carbon::parse($row->plan_expiry)->format('d M Y') : '-' }}
                                </td>
                                <td>
                                    @if ($expired)
                                        <span class="badge badge-soft-danger">{{ translate('Expired') }}</span>
                                    @else
                                        <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <img class="w--120px mb-3"
                                        src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                                    <h5 class="mb-0">{{ translate('No subscriptions found') }}</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($subscriptions->hasPages())
                <div class="card-footer">
                    {!! $subscriptions->links() !!}
                </div>
            @endif
        </div>
    </div>
@endsection
