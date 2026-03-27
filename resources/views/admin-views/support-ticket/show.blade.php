@extends('layouts.admin.app')

@section('title', 'Ticket ' . $ticket->ticket_id)

@push('css_or_js')
<style>
    .tk-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .tk-info-item label { font-size: 12px; color: #8c98a4; margin-bottom: 2px; display: block; }
    .tk-info-item span { font-weight: 600; font-size: 14px; } 
    .tk-reply { padding: 16px; border-radius: 8px; margin-bottom: 12px; }
    .tk-reply-admin { background: #e8f4fd; border-left: 3px solid #0d6efd; }
    .tk-reply-other { background: #f8f9fa; border-left: 3px solid #6c757d; }
    .tk-reply-meta { font-size: 12px; color: #8c98a4; margin-bottom: 6px; }
    .tk-reply-msg { font-size: 14px; white-space: pre-wrap; }
    .tk-description { background: #f8f9fa; padding: 16px; border-radius: 8px; white-space: pre-wrap; font-size: 14px; }
    .tk-status-open { color: #28a745; } .tk-status-closed { color: #dc3545; } .tk-status-reopened { color: #ffc107; }
</style>
@endpush

@section('content') 
    <div class="content container-fluid"> 
        <div class="page-header"> 
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0"> 
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-support"></i></span>
                        <span>{{ $ticket->ticket_id }}</span>
                        @php
                            $statusColors = ['open' => 'success', 'closed' => 'danger', 'reopened' => 'warning'];
                        @endphp
                        <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }} ml-2" style="font-size: 14px;">
                            {{ ucfirst($ticket->status) }}
                        </span>
                    </h1>
                </div>
                <div class="col-sm-auto d-flex gap-2">
                    @if(hasPermission('support_ticket', 'status'))
                    @if($ticket->status != 'closed')
                        <form action="{{ route('admin.ticket.status', $ticket->id) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="closed">
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Close this ticket?')">
                                <i class="tio-clear"></i> Close Ticket
                            </button>
                        </form>
                    @endif
                    @if($ticket->status == 'closed')
                        <form action="{{ route('admin.ticket.status', $ticket->id) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="reopened">
                            <button class="btn btn-warning btn-sm">
                                <i class="tio-refresh"></i> Reopen
                            </button>
                        </form>
                    @endif
                    @endif
                    <a href="{{ route('admin.ticket.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="tio-back-ui"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-8">
                {{-- Ticket Details --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ $ticket->subject }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="tk-description">{{ $ticket->description }}</div>
                    </div>
                </div>

                {{-- Replies --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Conversation ({{ $ticket->replies->count() }})</h5>
                    </div>
                    <div class="card-body">
                        @forelse($ticket->replies as $reply)
                            <div class="tk-reply {{ $reply->replied_by ? 'tk-reply-admin' : 'tk-reply-other' }}">
                                <div class="tk-reply-meta">
                                    <strong>
                                        @if($reply->repliedBy)
                                            {{ $reply->repliedBy->f_name }} {{ $reply->repliedBy->l_name }}
                                        @else
                                            System
                                        @endif
                                    </strong>
                                    &middot; {{ \Carbon\Carbon::parse($reply->created_at)->format('d M Y, h:i A') }}
                                </div>
                                <div class="tk-reply-msg">{{ $reply->message }}</div>
                            </div>
                        @empty
                            <p class="text-muted text-center py-3">No replies yet.</p>
                        @endforelse

                        @if(hasPermission('support_ticket', 'reply'))
                        <hr>
                        <form action="{{ route('admin.ticket.reply', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label class="input-label">Add Reply</label>
                                <textarea name="message" class="form-control" rows="3" placeholder="Type your reply..." required></textarea>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="btn btn--primary">
                                    <i class="tio-send"></i> Send Reply
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                {{-- Ticket Info --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Ticket Info</h5></div>
                    <div class="card-body">
                        <div class="tk-info-grid">
                            <div class="tk-info-item">
                                <label>Department</label>
                                <span>{{ $ticket->department }}</span>
                            </div>
                            <div class="tk-info-item">
                                <label>Priority</label>
                                @php $pColors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger']; @endphp
                                <span class="badge badge-soft-{{ $pColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span>
                            </div>
                            <div class="tk-info-item">
                                <label>Created</label>
                                <span>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="tk-info-item">
                                <label>Last Updated</label>
                                <span>{{ \Carbon\Carbon::parse($ticket->updated_at)->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Raised For --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Raised For</h5></div>
                    <div class="card-body">
                        @if($ticket->vendor_id && $ticket->vendor)
                            <div class="tk-info-item mb-2">
                                <label>Vendor</label>
                                <span>{{ $ticket->vendor->f_name }} {{ $ticket->vendor->l_name }}</span>
                            </div>
                            @if($ticket->vendor->phone)
                                <div class="tk-info-item mb-2">
                                    <label>Phone</label>
                                    <span>{{ $ticket->vendor->phone }}</span>
                                </div>
                            @endif
                            @if($ticket->vendor->email)
                                <div class="tk-info-item">
                                    <label>Email</label>
                                    <span>{{ $ticket->vendor->email }}</span>
                                </div>
                            @endif
                        @elseif($ticket->guest_name || $ticket->guest_phone || $ticket->guest_email)
                            <div class="tk-info-item mb-2">
                                <label>Name</label>
                                <span>{{ $ticket->guest_name ?: '-' }}</span>
                            </div>
                            <div class="tk-info-item mb-2">
                                <label>Phone</label>
                                <span>{{ $ticket->guest_phone ?: '-' }}</span>
                            </div>
                            <div class="tk-info-item">
                                <label>Email</label>
                                <span>{{ $ticket->guest_email ?: '-' }}</span>
                            </div>
                        @else
                            <p class="text-muted mb-0">No contact info</p>
                        @endif
                    </div>
                </div>

                {{-- Assign Staff --}}
                @if(hasPermission('support_ticket', 'assign'))
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Assign Staff</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.ticket.assign', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <select name="assigned_to" class="form-control">
                                    <option value="">Unassigned</option>
                                    @foreach($staff as $s)
                                        <option value="{{ $s->id }}" {{ $ticket->assigned_to == $s->id ? 'selected' : '' }}>
                                            {{ $s->f_name }} {{ $s->l_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn--primary btn-sm btn-block">Update Assignment</button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection
