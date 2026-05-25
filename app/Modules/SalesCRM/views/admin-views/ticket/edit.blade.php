@extends('layouts.admin.app')
@section('title', translate('Edit Ticket') . ' — ' . $ticket->ticket_no)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('Edit Ticket') }}: {{ $ticket->ticket_no }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.ticket.index') }}">{{ translate('Support Tickets') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.ticket.show', $ticket->id) }}">{{ $ticket->ticket_no }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Edit') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.sales-crm.ticket.update', $ticket->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row">
                            <div class="col-12 form-group">
                                <label class="input-label">{{ translate('Subject') }} <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $ticket->subject) }}" required>
                                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Contact Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $ticket->contact_name) }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Phone') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $ticket->phone) }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Email') }}</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $ticket->email) }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Channel') }}</label>
                                <select name="channel" class="form-control" required>
                                    @foreach(\App\Modules\SalesCRM\Models\SupportTicket::CHANNELS as $c)
                                        <option value="{{ $c }}" {{ old('channel', $ticket->channel) == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="input-label">{{ translate('Priority') }}</label>
                                <select name="priority" class="form-control">
                                    @foreach(\App\Modules\SalesCRM\Models\SupportTicket::PRIORITIES as $p)
                                        <option value="{{ $p }}" {{ old('priority', $ticket->priority) == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="input-label">{{ translate('Status') }}</label>
                                <select name="status" class="form-control">
                                    @foreach(\App\Modules\SalesCRM\Models\SupportTicket::STATUSES as $s)
                                        <option value="{{ $s }}" {{ old('status', $ticket->status) == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="input-label">{{ translate('Zone / City') }}</label>
                                <select name="zone_id" class="form-control">
                                    <option value="">{{ translate('Select Zone') }}</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ old('zone_id', $ticket->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Assign To') }}</label>
                                <select name="assigned_admin_id" class="form-control">
                                    <option value="">{{ translate('Unassigned') }}</option>
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}" {{ old('assigned_admin_id', $ticket->assigned_admin_id) == $admin->id ? 'selected' : '' }}>{{ $admin->f_name }} {{ $admin->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Linked Query') }}</label>
                                <select name="query_id" class="form-control">
                                    <option value="">{{ translate('None') }}</option>
                                    @foreach($queries as $q)
                                        <option value="{{ $q->id }}" {{ old('query_id', $ticket->query_id) == $q->id ? 'selected' : '' }}>{{ $q->ref_no }} — {{ $q->contact_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 form-group">
                                <label class="input-label">{{ translate('Description') }}</label>
                                <textarea name="description" class="form-control" rows="4">{{ old('description', $ticket->description) }}</textarea>
                            </div>
                            <div class="col-12 form-group">
                                <label class="input-label">{{ translate('Resolution Notes') }}</label>
                                <textarea name="resolution" class="form-control" rows="3" placeholder="{{ translate('Fill when resolving the ticket...') }}">{{ old('resolution', $ticket->resolution) }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.sales-crm.ticket.show', $ticket->id) }}" class="btn btn-secondary mr-2">{{ translate('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
