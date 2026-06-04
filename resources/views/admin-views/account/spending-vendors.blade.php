@extends('layouts.admin.app')

@section('title', translate('Vendors Who Spent'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-poi"></i></span>
                <span>{{ translate('Vendors Who Spent') }}</span>
            </h1>
            <div class="d-flex align-items-center" style="gap:10px;">
                @if (isset($preset) && $preset != 'all_time')
                    <span class="badge badge-soft-success font-weight-bold px-3 py-2" style="border-radius:20px;">
                        <i class="tio-date-range mr-1"></i>{{ $from }} &mdash; {{ $to }}
                    </span>
                @endif
                <form action="" method="GET" class="date-range-form mb-0">
                    @include('admin-views.form_modals.date_range')
                    <button class="btn btn-outline-primary" type="button" data-toggle="modal" data-target="#dateRangeModal"
                        style="border-radius:8px; height:42px; display:flex; align-items:center; gap:6px;">
                        <i class="tio-date-range"></i> {{ translate($preset) }}
                    </button>
                </form>
                <a href="{{ route('admin.account.revenue', ['date_range' => $preset]) }}" class="btn btn-outline-secondary" style="border-radius:8px; height:42px;">
                    <i class="tio-back-ui mr-1"></i> {{ translate('Back') }}
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 py-2">
                <div class="search--button-wrapper d-flex flex-wrap justify-content-between align-items-center w-100">
                    <h3 class="card-title d-flex align-items-center gap-2 mb-0">
                        {{ translate('Vendors') }}
                        <span class="badge badge-soft-secondary">{{ count($vendors) }}</span>
                    </h3>
                    <form action="" method="GET" class="search-form theme-style d-flex">
                        <input type="hidden" name="date_range" value="{{ $preset }}">
                        <div class="input--group input-group input-group-merge input-group-flush">
                            <input name="search" type="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search by store name or ID') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:70px;">{{ translate('sl') }}</th>
                                <th>{{ translate('Vendor / Store') }}</th>
                                <th class="text-right">{{ translate('Amount Spent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendors as $key => $v)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        @if (Route::has('admin.store.view'))
                                            <a href="{{ route('admin.store.view', $v['id']) }}" class="font-weight-bold text-dark">{{ $v['name'] }}</a>
                                        @else
                                            <span class="font-weight-bold text-dark">{{ $v['name'] }}</span>
                                        @endif
                                        <span class="text-muted" style="font-size:11px;"> (ID: {{ $v['id'] }})</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="font-weight-bold text-success">{{ \App\CentralLogics\Helpers::format_currency($v['amount']) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5">
                                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" class="mb-3" style="width:80px;" alt="public">
                                        <h5 class="text-muted">{{ translate('no_data_found') }}</h5>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($vendors) > 0)
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-right font-weight-bold text-dark">{{ translate('Total') }}</td>
                                    <td class="text-right font-weight-bold text-success">{{ \App\CentralLogics\Helpers::format_currency($total_spend) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('admin-views.js.date_range')
@endpush
