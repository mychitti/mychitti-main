@extends('layouts.admin.app')

@section('title', 'Analytics')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/module.png') }}" alt="">
                </span>
                <span>Analytics</span>
            </h1>
        </div>
  
        {{-- Tabs --}}
        <ul class="nav nav-tabs border-0 nav--tabs nav--pills mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'stores' ? 'active' : '' }}" 
                    href="{{ route('admin.analytics.index', ['tab' => 'stores']) }}">Store Visits</a> 
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'banners' ? 'active' : '' }}"
                    href="{{ route('admin.analytics.index', ['tab' => 'banners']) }}">Banner Clicks</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'notifications' ? 'active' : '' }}"
                    href="{{ route('admin.analytics.index', ['tab' => 'notifications']) }}">Ad Clicks</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'location_views' ? 'active' : '' }}"
                    href="{{ route('admin.analytics.index', ['tab' => 'location_views']) }}">Location Views</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'phone_unmasks' ? 'active' : '' }}"
                    href="{{ route('admin.analytics.index', ['tab' => 'phone_unmasks']) }}">Phone (Call/Copy)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'shares' ? 'active' : '' }}"
                    href="{{ route('admin.analytics.index', ['tab' => 'shares']) }}">Shares</a>
            </li>
        </ul>

        {{-- Search & Filter --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <form class="row align-items-end g-2 date-range-form">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    @if (count($filterOptions) > 0)
                        <div class="col-md-2">
                            <label class="form-label mb-1">
                                {{ in_array($tab, ['stores', 'phone_unmasks', 'location_views']) ? 'Store' : ($tab == 'banners' ? 'Banner' : 'Notification') }}
                            </label>
                            <select name="ref_id" class="form-control js-select2-custom" data-placeholder="-- All --"
                                onchange="this.form.submit()">
                                <option value="">-- All --</option>
                                @foreach ($filterOptions as $opt)
                                    <option value="{{ $opt->id }}" {{ $refId == $opt->id ? 'selected' : '' }}>
                                        {{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <div class="input-group input--group"> 
                            <input type="search" name="search" value="{{ $search }}"
                                class="form-control" placeholder="{{ translate('messages.ex_:_search_user_name_or_phone') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning " type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                        @include('vendor-views/form_modals/date_range')
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    @if ($tab == 'stores')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Store</th>
                                    <th>Store Phone</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>{{ $item->store_name ?? 'Deleted Store' }}</td>
                                        <td>{{ $item->store_phone ?? '-' }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif 
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody> 
                        </table>
                    @elseif ($tab == 'banners')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Banner</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>{{ $item->banner_title ?? 'Deleted Banner' }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($tab == 'notifications')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Notification</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>{{ $item->notif_title ?? 'Deleted Notification' }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($tab == 'location_views')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Store</th>
                                    <th>Store Phone</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>{{ $item->store_name ?? 'Deleted Store' }}</td>
                                        <td>{{ $item->store_phone ?? '-' }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($tab == 'phone_unmasks')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Store</th>
                                    <th>Store Phone</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>
                                            @if ($item->screen_type == 'copy')
                                                <span class="badge badge-soft-warning">Copy</span>
                                            @else
                                                <span class="badge badge-soft-success">Call</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->store_name ?? 'Deleted Store' }}</td>
                                        <td>{{ $item->store_phone }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($tab == 'shares')
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>User</th>
                                    <th>User Phone</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['items'] as $key => $item)
                                    <tr>
                                        <td>{{ $key + $data['items']->firstItem() }}</td>
                                        <td>
                                            @if ($item->sub_type == 'store')
                                                <span class="badge badge-soft-primary">Store</span>
                                            @elseif ($item->sub_type == 'service')
                                                <span class="badge badge-soft-success">Service</span>
                                            @else
                                                <span class="text-muted">{{ $item->sub_type ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->entity_name }}</td>
                                        <td>
                                            @if ($item->f_name)
                                                {{ $item->f_name . ' ' . $item->l_name }}
                                            @elseif ($item->user_id)
                                                <span class="text-muted">Deleted User #{{ $item->user_id }}</span>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->user_phone ?? '-' }}</td>
                                        <td><code>{{ $item->ip }}</code></td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
            @if (!empty($data['items']))
                <div class="card-footer border-0 pt-0">
                    <div class="d-flex justify-content-center justify-content-sm-end">
                        {!! $data['items']->links() !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    @include('admin-views.js.date_range')
@endpush
 