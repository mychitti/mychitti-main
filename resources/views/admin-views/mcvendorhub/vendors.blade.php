@extends('layouts.admin.app')

@section('title', translate('MC Vendorhub Vendors'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-shop"></i></span>
                <span>{{ translate('MC Vendorhub Vendors') }}
                    <span class="badge badge-soft-dark ml-2">{{ $vendors->total() }}</span>
                </span>
            </h1>
            <p class="mb-0 text-muted">
                {{ translate('Stores that turned off "List my store on MyChitti". They keep the vendor panel and their own store page, but do not appear on the MyChitti customer site or app.') }}
            </p>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <form class="row g-2 w-100 align-items-end">
                    <div class="col-md-6">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search store name, phone or email') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All Statuses') }}</option>
                            <option value="1" {{ $status === '1' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                            <option value="0" {{ $status === '0' ? 'selected' : '' }}>{{ translate('Inactive') }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.mcvendorhub.vendors') }}"
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
                            <th>{{ translate('Owner') }}</th>
                            <th>{{ translate('Contact') }}</th>
                            <th>{{ translate('Zone') }}</th>
                            <th>{{ translate('Joined') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vendors as $key => $vendor)
                            <tr>
                                <td>{{ $vendors->firstItem() + $key }}</td>
                                <td>
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-sm mr-2">
                                            <img class="avatar-img onerror-image"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($vendor->logo, asset('storage/app/public/store/') . '/' . $vendor->logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'store/') }}"
                                                alt="{{ $vendor->name }}">
                                        </div>
                                        <div class="media-body">
                                            <a href="{{ route('admin.store.view', $vendor->id) }}">{{ $vendor->name }}</a>
                                            <div class="font-size-sm text-muted">
                                                {{ $vendor->module?->module_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $vendor->vendor?->f_name }} {{ $vendor->vendor?->l_name }}</td>
                                <td>
                                    <div>{{ $vendor->phone ?: '-' }}</div>
                                    <div class="font-size-sm text-muted">{{ $vendor->email ?: '-' }}</div>
                                </td>
                                <td>{{ $vendor->zone?->name ?? '-' }}</td>
                                <td>{{ $vendor->created_at?->format('d M Y') }}</td>
                                <td>
                                    @if ($vendor->status)
                                        <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                    @else
                                        <span class="badge badge-soft-danger">{{ translate('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center flex-nowrap"
                                        style="gap: .375rem;">
                                        <a href="{{ route('admin.store.view', $vendor->id) }}"
                                            class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                            title="{{ translate('View') }}"><i class="tio-visible"></i></a>
                                        <a href="{{ route('admin.mcvendorhub.vendors.listing-toggle', $vendor->id) }}"
                                            class="btn btn-sm btn-outline-secondary text-nowrap"
                                            title="{{ translate('Move back to MyChitti') }}">
                                            {{ translate('List on MyChitti') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <img class="w--120px mb-3"
                                        src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                                    <h5>{{ translate('No vendors here yet') }}</h5>
                                    <p class="text-muted mb-0">
                                        {{ translate('A store appears here once its vendor turns off "List my store on MyChitti" in Store Setup.') }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($vendors->hasPages())
                <div class="card-footer">
                    {!! $vendors->links() !!}
                </div>
            @endif
        </div>
    </div>
@endsection
