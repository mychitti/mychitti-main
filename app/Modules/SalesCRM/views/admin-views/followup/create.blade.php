@extends('layouts.admin.app')
@section('title', translate('Schedule Follow-up'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('Schedule Follow-up') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.followup.index') }}">{{ translate('Follow-ups') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Add') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.sales-crm.followup.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">{{ translate('Linked Query') }}</label>
                            <select name="query_id" class="form-control">
                                <option value="">{{ translate('None (standalone follow-up)') }}</option>
                                @foreach($queries as $q)
                                    <option value="{{ $q->id }}" {{ (old('query_id', $selectedQuery?->id) == $q->id) ? 'selected' : '' }}>
                                        {{ $q->ref_no }} — {{ $q->contact_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('Title / Task') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="{{ translate('e.g. Call back to discuss pricing') }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Due Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', date('Y-m-d')) }}" required>
                                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Time (optional)') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" style="background:#377dff;border-color:#377dff;color:#fff;">
                                            <i class="tio-time"></i>
                                        </span>
                                    </div>
                                    <input type="text" id="due_time_picker" name="due_time"
                                        class="form-control" placeholder="-- : -- AM/PM"
                                        value="{{ old('due_time') }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="{{ translate('Any additional notes...') }}">{{ old('notes') }}</textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.sales-crm.followup.index') }}" class="btn btn-secondary mr-2">{{ translate('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ translate('Schedule') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    flatpickr('#due_time_picker', {
        enableTime: true,
        noCalendar: true,
        time_24hr: false,
        dateFormat: 'H:i',
        altInput: true,
        altFormat: 'h:i K',
        minuteIncrement: 5,
    });
</script>
@endpush

