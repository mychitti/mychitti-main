@extends('layouts.vendor.app')
@section('title', 'Laundry — Hotel Challans')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between">
            <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-file-text"></i></span> Hotel Challans
            </h1>
            <a href="{{ route('vendor.laundry.challans.create') }}" class="btn btn--primary"><i class="tio-add"></i> New
                Challan</a>
        </div>

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row">
                    <form class=" col-md-3 ">
                        <select name="hotel_id" class="form-control js-select2-custom" onchange="this.form.submit()">
                            <option value="">All Hotels</option>
                            @foreach ($hotels as $h)
                                <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>
                                    {{ $h->f_name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <form class="col-md-2 ">
                        <select name="status" class="form-control js-select2-custom" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ $status == 'partial' ? 'selected' : '' }}>Partial Return</option>
                            <option value="returned" {{ $status == 'returned' ? 'selected' : '' }}>Returned</option>
                        </select>
                    </form>
                    <form class="col-md-2 date-range-form">
                        <button style="width:100%;white-space:nowrap" class="btn btn-outline-warning" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">
                            {{ translate($preset) }}
                        </button>
                        @include('vendor-views/form_modals/date_range')
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Challan No</th>
                                <th>Hotel</th>
                                <th>Outward Date</th>
                                <th>Expected Inward</th>
                                <th>Inward Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($challans as $key => $challan)
                                <tr>
                                    <td>{{ $key + $challans->firstItem() }}</td>
                                    <td><strong>{{ $challan->challan_no }}</strong></td>
                                    <td>{{ $challan->hotel->f_name ?? '-' }}</td>
                                    <td>{{ $challan->outward_date->format('d M Y') }}</td>
                                    <td>{{ $challan->expected_inward_date ? $challan->expected_inward_date->format('d M Y') : '-' }}
                                    </td>
                                    <td>{{ $challan->inward_date ? $challan->inward_date->format('d M Y') : '-' }}</td>
                                    <td>
                                        @if ($challan->status == 'pending')
                                            <span class="badge badge-soft-warning">Pending</span>
                                        @elseif($challan->status == 'partial')
                                            <span class="badge badge-soft-info">Partial Return</span>
                                        @else
                                            <span class="badge badge-soft-success">Returned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.laundry.challans.show', $challan->id) }}"
                                            class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="tio-visible"></i>
                                        </a>
                                        @if (in_array($challan->status, ['pending', 'partial']))
                                            <a href="{{ route('vendor.laundry.challans.receive', $challan->id) }}"
                                                class="btn btn-sm btn-outline-success" title="Receive">
                                                <i class="tio-checkmark-circle"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('vendor.laundry.challans.print', $challan->id) }}"
                                            target="_blank" class="btn btn-sm btn-outline-secondary" title="Print">
                                            <i class="tio-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">No challans found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($challans->hasPages())
                <div class="card-footer border-0 pt-0">
                    <div class="d-flex justify-content-end">{!! $challans->links() !!}</div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
