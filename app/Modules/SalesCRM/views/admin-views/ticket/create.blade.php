@extends('layouts.admin.app')
@section('title', translate('New Support Ticket'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('New Support Ticket') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.ticket.index') }}">{{ translate('Support Tickets') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('New') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if(isset($fromQuery) && $fromQuery)
            <div class="alert alert-soft-info d-flex align-items-center mb-3" style="font-size:.875rem;">
                <i class="tio-link mr-2" style="font-size:1.1rem;"></i>
                This ticket will be linked to query <strong class="ml-1 mr-1">{{ $fromQuery->ref_no }}</strong> — {{ $fromQuery->contact_name }}
            </div>
            @endif
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.sales-crm.ticket.store') }}" method="POST">
                        @csrf
                        @if(isset($fromQuery) && $fromQuery)
                            <input type="hidden" name="from_query" value="{{ $fromQuery->id }}">
                        @endif
                        <div class="row">
                            <div class="col-12 form-group">
                                <label class="input-label">{{ translate('Subject') }} <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required>
                                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Contact Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror"
                                    value="{{ old('contact_name', isset($fromQuery) ? $fromQuery->contact_name : '') }}" required>
                                @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Phone') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', isset($fromQuery) ? $fromQuery->phone : '') }}" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Email') }}</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', isset($fromQuery) ? $fromQuery->email : '') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Channel') }} <span class="text-danger">*</span></label>
                                <select name="channel" class="form-control" required>
                                    @foreach(\App\Modules\SalesCRM\Models\SupportTicket::CHANNELS as $c)
                                        <option value="{{ $c }}" {{ old('channel') == $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Zone / City') }}</label>
                                <select name="zone_id" class="form-control">
                                    <option value="">{{ translate('Select Zone') }}</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Priority') }} <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    @foreach(\App\Modules\SalesCRM\Models\SupportTicket::PRIORITIES as $p)
                                        <option value="{{ $p }}" {{ old('priority', 'medium') == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Assign To') }}</label>
                                <select name="assigned_admin_id" class="form-control">
                                    <option value="">{{ translate('Unassigned') }}</option>
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}" {{ old('assigned_admin_id') == $admin->id ? 'selected' : '' }}>{{ $admin->f_name }} {{ $admin->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Linked Query (optional)') }}</label>
                                <select name="query_id" class="form-control" {{ isset($fromQuery) && $fromQuery ? 'readonly' : '' }}>
                                    <option value="">{{ translate('None') }}</option>
                                    @foreach($queries as $q)
                                        @php $qId = is_array($q) ? $q['id'] : $q->id; $qRef = is_array($q) ? $q['ref_no'] : $q->ref_no; $qName = is_array($q) ? $q['contact_name'] : $q->contact_name; @endphp
                                        <option value="{{ $qId }}" {{ old('query_id', isset($fromQuery) ? $fromQuery->id : '') == $qId ? 'selected' : '' }}>{{ $qRef }} — {{ $qName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 form-group">
                                <label class="input-label">{{ translate('Description') }}</label>
                                <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ isset($fromQuery) && $fromQuery ? route('admin.sales-crm.query.show', $fromQuery->id) : route('admin.sales-crm.ticket.index') }}"
                               class="btn btn-secondary mr-2">{{ translate('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ translate('Create Ticket') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
