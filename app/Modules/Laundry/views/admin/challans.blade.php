@extends('layouts.admin.app')
@section('title', 'Laundry — Hotel Challans')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-file-text"></i></span> Hotel Challans
        </h1>
        <a href="{{ route('admin.laundry.orders') }}" class="btn btn-outline-secondary">Walk-in Orders</a>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="row align-items-end g-2" method="GET">
                <div class="col-md-2">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Challan no / store">
                </div>
                <div class="col-md-2">
                    <select name="store_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Stores</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending"  {{ $status == 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="partial"  {{ $status == 'partial'  ? 'selected' : '' }}>Partial Return</option>
                        <option value="returned" {{ $status == 'returned' ? 'selected' : '' }}>Returned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn--secondary w-100"><i class="tio-search"></i></button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('admin.laundry.challans') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
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
                            <th>Store</th>
                            <th>Hotel</th>
                            <th>Outward Date</th>
                            <th>Inward Date</th>
                            <th>Status</th>
                            <th>Balance Items</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($challans as $key => $challan)
                        <tr>
                            <td>{{ $key + $challans->firstItem() }}</td>
                            <td><strong>{{ $challan->challan_no }}</strong></td>
                            <td>{{ $challan->store_name }}</td>
                            <td>{{ $challan->hotel->f_name ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($challan->outward_date)->format('d M Y') }}</td>
                            <td>{{ $challan->inward_date ? \Carbon\Carbon::parse($challan->inward_date)->format('d M Y') : '-' }}</td>
                            <td>
                                @if($challan->status == 'pending')
                                    <span class="badge badge-soft-warning">Pending</span>
                                @elseif($challan->status == 'partial')
                                    <span class="badge badge-soft-info">Partial</span>
                                @else
                                    <span class="badge badge-soft-success">Returned</span>
                                @endif
                            </td>
                            <td>
                                @php $balance = $challan->items ? $challan->items->sum('balance') : 0; @endphp
                                @if($balance > 0)
                                    <span class="text-danger font-weight-bold">{{ $balance }}</span>
                                @else
                                    <span class="text-success">0</span>
                                @endif
                            </td>
                            <td>
                                @if($challan->invoice_id)
                                    <span class="badge badge-soft-success">{{ $challan->invoice_id }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-4">No challans found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($challans->hasPages())
        <div class="card-footer border-0 pt-0">
            <div class="d-flex justify-content-end">{!! $challans->links() !!}</div>
        </div>
        @endif
    </div>
</div>
@endsection
