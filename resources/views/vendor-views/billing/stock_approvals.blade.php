@extends('layouts.vendor.app')

@section('title', 'Stock Return Approvals')

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap">
            <h1 class="page-header-title mb-0"><i class="tio-checkmark-circle"></i> Stock Return Approvals</h1>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn_sm">Back</a>
        </div>

        <p class="text-muted">When a staff member deletes an invoice, the stock is returned to inventory only after the
            vendor approves it here.</p>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Items to restore</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approvals as $key => $ap)
                            <tr>
                                <td>{{ $approvals->firstItem() + $key }}</td>
                                <td>
                                    <b>{{ $ap->invoice_ref }}</b>
                                    <div class="text-muted" style="font-size:11px;">{{ ucfirst($ap->invoice_type) }}</div>
                                </td>
                                <td>
                                    @foreach ((array) $ap->items as $it)
                                        <div style="font-size:12px;">{{ $it['name'] ?? ('Item #' . ($it['inv_id'] ?? '')) }}
                                            — <b>{{ $it['qty'] ?? 0 }}</b>{{ !empty($it['unit']) ? ' ' . $it['unit'] : '' }}
                                        </div>
                                    @endforeach
                                </td>
                                <td>{{ $ap->requested_by_name ?: '—' }}</td>
                                <td>
                                    @if ($ap->status === 'pending')
                                        <span class="badge badge-soft-warning">Pending</span>
                                    @elseif ($ap->status === 'approved')
                                        <span class="badge badge-soft-success">Approved</span>
                                    @else
                                        <span class="badge badge-soft-danger">Rejected</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($ap->status === 'pending' && auth('vendor')->check())
                                        <a href="{{ route('vendor.invoice.stock-approvals.approve', [$ap->id]) }}"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Approve and return this stock to inventory?')">
                                            <i class="tio-checkmark-circle"></i> Approve
                                        </a>
                                        <a href="{{ route('vendor.invoice.stock-approvals.reject', [$ap->id]) }}"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Reject this stock return?')">
                                            <i class="tio-clear-circle"></i> Reject
                                        </a>
                                    @elseif ($ap->status === 'pending')
                                        <span class="text-muted" style="font-size:11px;">Awaiting owner</span>
                                    @else
                                        <span class="text-muted" style="font-size:11px;">{{ optional($ap->decided_at)->format('d M Y, h:i A') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No stock-return requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($approvals instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="card-footer">{!! $approvals->links() !!}</div>
            @endif
        </div>
    </div>
@endsection
