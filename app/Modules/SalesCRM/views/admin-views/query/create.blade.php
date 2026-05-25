@extends('layouts.admin.app')
@section('title', translate('Add Sales Query'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('Add Sales Query') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales-crm.query.index') }}">{{ translate('Sales Queries') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Add') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.sales-crm.query.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Contact Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror" value="{{ old('contact_name') }}" required>
                                @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Phone') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Email') }}</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Company / Business') }}</label>
                                <input type="text" name="company" class="form-control" value="{{ old('company') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Zone / City') }}</label>
                                <select name="zone_id" id="zone_id" class="form-control">
                                    <option value="">{{ translate('Select Zone') }}</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Assign To') }}</label>
                                <select name="assigned_admin_id" id="assigned_admin_id" class="form-control">
                                    <option value="">{{ translate('Unassigned') }}</option>
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}" {{ old('assigned_admin_id') == $admin->id ? 'selected' : '' }}>{{ $admin->f_name }} {{ $admin->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Source') }} <span class="text-danger">*</span></label>
                                <select name="source" class="form-control @error('source') is-invalid @enderror" required>
                                    @foreach(\App\Modules\SalesCRM\Models\SalesQuery::SOURCES as $s)
                                        <option value="{{ $s }}" {{ old('source') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Priority') }} <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    @foreach(\App\Modules\SalesCRM\Models\SalesQuery::PRIORITIES as $p)
                                        <option value="{{ $p }}" {{ old('priority', 'medium') == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Module / Service') }}</label>
                                <select name="sub_module" id="sub_module" class="form-control">
                                    <option value=""></option>
                                    @foreach($subModules as $sm)
                                        <option value="{{ $sm->name }}" {{ old('sub_module') == $sm->name ? 'selected' : '' }}>{{ $sm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 form-group">
                                <label class="input-label">{{ translate('Description / Requirements') }}</label>
                                <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.sales-crm.query.index') }}" class="btn btn-secondary mr-2">{{ translate('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ translate('Save Query') }}</button>
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
    $(document).ready(function () {
        $('#zone_id').select2({ placeholder: '{{ translate('Select Zone') }}', allowClear: true, width: '100%' });
        $('#assigned_admin_id').select2({ placeholder: '{{ translate('Unassigned') }}', allowClear: true, width: '100%' });
        $('#sub_module').select2({
            placeholder: '{{ translate('Select or type to add new') }}',
            allowClear: true,
            width: '100%',
            tags: true,
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') return null;
                return { id: term, text: term, newTag: true };
            }
        });
    });
</script>
@endpush
