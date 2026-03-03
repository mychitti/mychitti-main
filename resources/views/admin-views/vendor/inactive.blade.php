@extends('layouts.admin.app')

@section('title', 'Inactive Vendors')

@push('css_or_js')
<style>
.inactive-badge { background:#fff3cd;color:#856404;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600; }
.days-inactive  { font-size:12px;color:#e74a3b;font-weight:600; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm">
                <h1 class="page-header-title">
                    <i class="tio-time-clock-outlined mr-1 text-warning"></i>
                    Inactive Vendors
                    <span class="badge badge-warning ml-2">{{ $vendors->total() }}</span>
                </h1>
                <p class="page-header-text text-muted">
                    Vendors who have not logged in for more than <strong>{{ $months }} months</strong>
                    (since {{ $threshold->format('d M Y') }}).
                </p>
            </div>
            <div class="col-auto">
                {{-- Threshold setting quick-edit --}}
                <form action="{{ route('admin.business-settings.update-setup') }}" method="POST" class="d-inline-flex align-items-center gap-2">
                    @csrf
                    <label class="mb-0 small font-weight-bold">Threshold (months):</label>
                    <input type="hidden" name="vendor_inactive_months" value="{{ $months }}">
                    <input type="number" name="vendor_inactive_months" value="{{ $months }}" min="1" max="36"
                           class="form-control form-control-sm" style="width:70px">
                    <button type="submit" class="btn btn-sm btn-outline-primary ml-1">Save</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Vendor / Store</th>
                            <th>Phone</th>
                            <th>Last Login</th>
                            <th>Inactive For</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $i => $store)
                        @php
                            $vendor      = $store->vendor;
                            $lastLogin   = $vendor?->last_login_at;
                            $inactiveDays = $lastLogin
                                ? $lastLogin->diffInDays(now())
                                : ($vendor ? $vendor->created_at->diffInDays(now()) : '—');
                        @endphp
                        <tr>
                            <td>{{ $vendors->firstItem() + $i }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($store->logo)
                                        <img src="{{ asset('storage/app/public/store/'.$store->logo) }}"
                                             class="rounded" width="38" height="38" style="object-fit:cover">
                                    @else
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                             style="width:38px;height:38px;font-size:18px">🏪</div>
                                    @endif
                                    <div>
                                        <div class="font-weight-bold">{{ $store->name }}</div>
                                        <div class="small text-muted">{{ $vendor?->f_name }} {{ $vendor?->l_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $store->phone }}</td>
                            <td>
                                @if($lastLogin)
                                    {{ $lastLogin->format('d M Y') }}
                                    <div class="small text-muted">{{ $lastLogin->diffForHumans() }}</div>
                                @else
                                    <span class="text-muted">Never logged in</span>
                                @endif
                            </td>
                            <td>
                                <span class="days-inactive">{{ $inactiveDays }} days</span>
                            </td>
                            <td class="text-right">
                                {{-- Notify --}}
                                <form action="{{ route('admin.store.notify-inactive', $store->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                            title="Send warning notification to vendor">
                                        <i class="tio-notifications-on-outlined"></i> Notify
                                    </button>
                                </form>
                                {{-- Delete --}}
                                <form action="{{ route('admin.store.delete-inactive', $store->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this vendor account permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete vendor account">
                                        <i class="tio-delete-outlined"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="tio-checkmark-circle-outlined" style="font-size:2rem"></i>
                                <p class="mt-2">No inactive vendors found for this threshold.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vendors->hasPages())
        <div class="card-footer d-flex justify-content-center">
            {{ $vendors->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
