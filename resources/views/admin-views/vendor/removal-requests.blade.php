@extends('layouts.admin.app')

@section('title', 'Store Removal Requests')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-delete"></i> Store Removal Requests</h1>
        </div>
 
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-tabs mb-3 border-0 nav--tabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $status == 'pending' ? 'active' : '' }}" href="{{ route('admin.store.removal-requests', ['status' => 'pending']) }}">
                            Pending
                            @php($pc = \Illuminate\Support\Facades\DB::table('store_removal_requests')->where('status','pending')->count())
                            @if($pc > 0) <span class="badge badge-danger ml-1">{{ $pc }}</span> @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status == 'approved' ? 'active' : '' }}" href="{{ route('admin.store.removal-requests', ['status' => 'approved']) }}">Approved</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status == 'rejected' ? 'active' : '' }}" href="{{ route('admin.store.removal-requests', ['status' => 'rejected']) }}">Rejected</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status == 'all' ? 'active' : '' }}" href="{{ route('admin.store.removal-requests', ['status' => 'all']) }}">All</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-header-title">Requests ({{ $requests->total() }})</h5>
            </div>
            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Requested By</th>
                            <th>Contact</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th>Status</th>
                            @if($status == 'pending')
                            <th class="text-center">Action</th>
                            @else
                            <th>Admin Remarks</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $key => $req)
                        <tr>
                            <td>{{ $requests->firstItem() + $key }}</td>
                            <td>
                                <strong>{{ $req->store_name }}</strong>
                                <br><small class="text-muted">ID: {{ $req->store_id }}</small>
                            </td>
                            <td>{{ $req->name }}</td>
                            <td>
                                <small>{{ $req->email }}</small>
                                <br><small>{{ $req->phone }}</small>
                            </td>
                            <td style="max-width: 250px; white-space: normal;">{{ Str::limit($req->reason, 80) }}</td>
                            <td><small>{{ \Carbon\Carbon::parse($req->created_at)->format('d M Y, h:i A') }}</small></td>
                            <td>
                                @if($req->status == 'pending')
                                    <span class="badge badge-soft-warning">Pending</span>
                                @elseif($req->status == 'approved')
                                    <span class="badge badge-soft-success">Approved</span>
                                @else
                                    <span class="badge badge-soft-danger">Rejected</span>
                                @endif
                            </td>
                            @if($status == 'pending')
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#approveModal{{ $req->id }}" title="Approve">
                                        <i class="tio-checkmark-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#rejectModal{{ $req->id }}" title="Reject">
                                        <i class="tio-clear-circle"></i>
                                    </button>
                                </div>

                                {{-- Approve Modal --}}
                                <div class="modal fade" id="approveModal{{ $req->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.store.removal-request-action', $req->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="approved">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Approve Removal - {{ $req->store_name }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                </div>
                                                <div class="modal-body text-left">
                                                    <div class="alert alert-warning py-2">
                                                        <small><i class="tio-info"></i> Approving this will <strong>deactivate</strong> the store.</small>
                                                    </div>
                                                    <p class="mb-2"><strong>Reason given:</strong></p>
                                                    <p class="text-muted" style="white-space: normal;">{{ $req->reason }}</p>
                                                    <div class="form-group mt-3">
                                                        <label>Admin Remarks (optional)</label>
                                                        <textarea name="admin_remarks" class="form-control" rows="2" placeholder="Add any notes..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success btn-sm">Approve & Deactivate Store</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Reject Modal --}}
                                <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.store.removal-request-action', $req->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Removal - {{ $req->store_name }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                </div>
                                                <div class="modal-body text-left">
                                                    <p class="mb-2"><strong>Reason given:</strong></p>
                                                    <p class="text-muted" style="white-space: normal;">{{ $req->reason }}</p>
                                                    <div class="form-group mt-3">
                                                        <label>Admin Remarks (optional)</label>
                                                        <textarea name="admin_remarks" class="form-control" rows="2" placeholder="Reason for rejection..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger btn-sm">Reject Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            @else
                            <td style="max-width: 200px; white-space: normal;">
                                <small class="text-muted">{{ $req->admin_remarks ?? '-' }}</small>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No removal requests found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->count() > 0)
            <div class="card-footer border-0">
                {!! $requests->links() !!}
            </div>
            @endif
        </div>
    </div>
@endsection
