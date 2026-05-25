@extends('layouts.admin.app')
@section('title', $ticket->ticket_no . ' — ' . Str::limit($ticket->subject, 40))

@push('css_or_js')
<style>
    .badge-open       { background: #17a2b8; }
    .badge-in_progress{ background: #fd7e14; }
    .badge-resolved   { background: #28a745; }
    .badge-closed     { background: #6c757d; }
    .badge-on_hold    { background: #adb5bd; color:#333!important; }
    .badge-urgent     { background: #dc3545; }
    .badge-high       { background: #e65c00; }
    .badge-medium     { background: #fd7e14; }
    .badge-low        { background: #28a745; }
    .reply-bubble { background: #f8f9fa; border-left: 3px solid #007bff; padding: 10px 14px; border-radius: 4px; }
    .detail-label { font-size: .75rem; font-weight: 600; color: #8c98a4; text-transform: uppercase; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ $ticket->ticket_no }}</h1>
                <p class="mb-0 text-body">{{ $ticket->subject }}</p>
                <ol class="breadcrumb breadcrumb-no-gutter mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.ticket.index') }}">{{ translate('Support Tickets') }}</a></li>
                    <li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
                </ol>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.sales-crm.ticket.edit', $ticket->id) }}" class="btn btn-secondary btn-sm">
                    <i class="tio-edit mr-1"></i>{{ translate('Edit') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Status') }}</p>
                            <span class="badge badge-{{ $ticket->status }} text-white">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        </div>
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Priority') }}</p>
                            <span class="badge badge-{{ $ticket->priority }} text-white">{{ ucfirst($ticket->priority) }}</span>
                        </div>
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Channel') }}</p>
                            <span>{{ ucfirst(str_replace('_', ' ', $ticket->channel)) }}</span>
                        </div>
                        <div class="col-6">
                            <p class="detail-label mb-1">{{ translate('Zone') }}</p>
                            <span>{{ $ticket->zone?->name ?? '—' }}</span>
                        </div>
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Contact') }}</p>
                            <div class="font-weight-bold">{{ $ticket->contact_name }}</div>
                            <div><i class="tio-call-made mr-1"></i>{{ $ticket->phone }}</div>
                            @if($ticket->email)<div><i class="tio-email-outlined mr-1"></i>{{ $ticket->email }}</div>@endif
                        </div>
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Assigned To') }}</p>
                            <span>{{ $ticket->assignedAdmin ? ($ticket->assignedAdmin->f_name . ' ' . $ticket->assignedAdmin->l_name) : '—' }}</span>
                        </div>
                        @if($ticket->salesQuery)
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Linked Query') }}</p>
                            <a href="{{ route('admin.sales-crm.query.show', $ticket->query_id) }}">{{ $ticket->salesQuery->ref_no }} — {{ $ticket->salesQuery->contact_name }}</a>
                        </div>
                        @endif
                        <div class="col-12">
                            <p class="detail-label mb-1">{{ translate('Opened') }}</p>
                            <span>{{ $ticket->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                    </div>

                    <hr>
                    <p class="detail-label mb-1">{{ translate('Update Status') }}</p>
                    <form action="{{ route('admin.sales-crm.ticket.status', $ticket->id) }}" method="POST" class="d-flex">
                        @csrf
                        <select name="status" class="form-control form-control-sm">
                            @foreach(\App\Modules\SalesCRM\Models\SupportTicket::STATUSES as $s)
                                <option value="{{ $s }}" {{ $ticket->status == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary ml-2">{{ translate('Set') }}</button>
                    </form>
                </div>
            </div>

            @if($ticket->description)
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">{{ translate('Description') }}</h6></div>
                <div class="card-body"><p class="mb-0" style="white-space:pre-wrap">{{ $ticket->description }}</p></div>
            </div>
            @endif

            @if($ticket->resolution)
            <div class="card mb-3 border-success">
                <div class="card-header py-2 bg-soft-success"><h6 class="mb-0 text-success">{{ translate('Resolution') }}</h6></div>
                <div class="card-body"><p class="mb-0" style="white-space:pre-wrap">{{ $ticket->resolution }}</p></div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0">{{ translate('Conversation') }} <span class="badge badge-soft-dark ml-1">{{ $ticket->replies->count() }}</span></h6></div>
                <div class="card-body" style="max-height:500px;overflow-y:auto">
                    @forelse($ticket->replies as $reply)
                    <div class="reply-bubble mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>{{ $reply->admin?->f_name }} {{ $reply->admin?->l_name }}</strong>
                            <small class="text-muted">{{ $reply->created_at->format('d M Y, h:i A') }}</small>
                        </div>
                        <p class="mb-0" style="white-space:pre-wrap">{{ $reply->message }}</p>
                    </div>
                    @empty
                    <p class="text-center text-muted py-3">{{ translate('No replies yet.') }}</p>
                    @endforelse
                </div>
                @if(!in_array($ticket->status, ['closed', 'resolved']))
                <div class="card-footer">
                    <form action="{{ route('admin.sales-crm.ticket.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-2">
                            <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="3" placeholder="{{ translate('Write a reply...') }}"></textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ translate('Send Reply') }}</button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
