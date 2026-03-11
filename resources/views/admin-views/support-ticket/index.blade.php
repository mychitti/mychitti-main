@extends('layouts.admin.app')

@section('title', 'Support Tickets')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-support"></i></span>
                        <span>Support Tickets</span>
                        <span class="badge badge-soft-dark ml-2">{{ $tickets->total() }}</span>
                    </h1>
                </div>
                <div class="col-sm-auto">
                    <a href="{{ route('admin.ticket.create') }}" class="btn btn--primary">
                        <i class="tio-add"></i> Create Ticket
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3"> 
            <div class="card-header border-0">
                <div class="row w-100 align-items-center g-2">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('admin.ticket.index') }}">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search by ID, subject, name..."
                                    value="{{ $search ?? '' }}">
                                @if(isset($status))
                                    <input type="hidden" name="status" value="{{ $status }}">
                                @endif
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn--primary"><i class="tio-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8 text-right">
                        <div class="btn-group" role="group">
                            <a href="{{ route('admin.ticket.index') }}" class="btn btn-sm {{ !$status ? 'btn--primary' : 'btn-outline-primary' }}">All</a>
                            <a href="{{ route('admin.ticket.index', ['status' => 'open']) }}" class="btn btn-sm {{ $status == 'open' ? 'btn-success' : 'btn-outline-success' }}">Open</a>
                            <a href="{{ route('admin.ticket.index', ['status' => 'closed']) }}" class="btn btn-sm {{ $status == 'closed' ? 'btn-danger' : 'btn-outline-danger' }}">Closed</a>
                            <a href="{{ route('admin.ticket.index', ['status' => 'reopened']) }}" class="btn btn-sm {{ $status == 'reopened' ? 'btn-warning' : 'btn-outline-warning' }}">Reopened</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Ticket ID</th>
                            <th>Subject</th>
                            <th>Raised For</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $key => $ticket)
                            @php
                                $statusColors = ['open' => 'success', 'closed' => 'danger', 'reopened' => 'warning'];
                                $pColors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger'];
                            @endphp
                            <tr>
                                <td>{{ $tickets->firstItem() + $key }}</td>
                                <td>
                                    <a href="{{ route('admin.ticket.show', $ticket->id) }}" class="font-weight-bold text-primary">
                                        {{ $ticket->ticket_id }}
                                    </a>
                                </td>
                                <td style="max-width: 200px;">
                                    <span class="d-block text-truncate">{{ $ticket->subject }}</span>
                                </td>
                                <td>
                                    @if($ticket->vendor_id && $ticket->vendor)
                                        <span class="badge badge-soft-primary">Vendor</span>
                                        {{ $ticket->vendor->f_name ?? '' }}
                                    @elseif($ticket->user_id && $ticket->customer)
                                        <span class="badge badge-soft-info">Customer</span>
                                        {{ $ticket->customer->f_name ?? '' }}
                                    @elseif($ticket->guest_name)
                                        <span class="badge badge-soft-secondary">Guest</span>
                                        {{ $ticket->guest_name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-{{ $pColors[$ticket->priority] ?? 'secondary' }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }}">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </td>
                                <td>{{ $ticket->assignedTo ? ($ticket->assignedTo->f_name . ' ' . $ticket->assignedTo->l_name) : '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y') }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('admin.ticket.show', $ticket->id) }}" class="btn btn-sm btn-outline-info" title="View">
                                            <i class="tio-visible"></i>
                                        </a>
                                        <form action="{{ route('admin.ticket.delete', $ticket->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Delete this ticket?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="tio-delete"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <img class="mb-3" style="width: 200px;" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="No tickets">
                                    <p class="mb-0">No tickets found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer border-0">
                {{ $tickets->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
