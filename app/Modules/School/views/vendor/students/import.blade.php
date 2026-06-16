@extends('layouts.vendor.app')
@section('title', 'Import Students')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-upload mr-1"></i> Bulk Import Students</h1>
        <a href="{{ route('vendor.school.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    @if(session('import_result'))
        @php $res = session('import_result'); @endphp
        <div class="alert" style="background:#dcfce7;border:none;color:#15803d;border-radius:12px;">
            <i class="tio-checkmark-circle"></i> <b>{{ $res['created'] }}</b> student(s) imported successfully.
            @if(count($res['errors'])) <b>{{ count($res['errors']) }}</b> row(s) skipped. @endif
        </div>
        @if(count($res['errors']))
            <div class="card mb-3"><div class="card-header py-3"><i class="tio-warning mr-1 text-danger"></i> Skipped Rows</div>
                <div class="card-body" style="max-height:240px; overflow:auto;">
                    <ul class="mb-0" style="font-size:13px;">
                        @foreach($res['errors'] as $err)<li class="text-danger">{{ $err }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3"><div class="card-header py-3"><i class="tio-upload mr-1 text-primary"></i> Upload CSV</div>
                <form action="{{ route('vendor.school.students.import.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        @if(school_active_branch())
                            <div class="alert mb-3" style="background:#eef2ff;border:none;color:#3730a3;border-radius:10px;font-size:13px;">
                                <i class="tio-city mr-1"></i> Students will be imported into the active branch: <b>{{ school_active_branch()->name }}</b>.
                            </div>
                        @endif
                        <div class="form-group mb-0">
                            <label class="input-label">CSV File *</label>
                            <input type="file" name="file" class="form-control-file" accept=".csv,text/csv" required>
                            @error('file')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            <small class="text-muted d-block mt-2">Max 5 MB. Use the template so the column headers match.</small>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <a href="{{ route('vendor.school.students.import.template') }}" class="btn btn-outline-primary btn-sm"><i class="tio-download"></i> Download Template</a>
                        <button class="btn btn--primary"><i class="tio-upload"></i> Import Students</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card"><div class="card-header py-3"><i class="tio-info-outined mr-1 text-primary"></i> How it works</div>
                <div class="card-body" style="font-size:13px;">
                    <ul class="pl-3 mb-2">
                        <li><b>first_name</b> and <b>dob</b> are required; everything else is optional.</li>
                        <li><b>admission_no</b> blank → auto-generated; if given it must be unique.</li>
                        <li><b>class</b> / <b>section</b> / <b>session</b> are matched by name to your Academic Setup (create them first).</li>
                        <li><b>dob</b> format: YYYY-MM-DD (required) · <b>gender</b>: male / female / other.</li>
                        <li>Rows with an unknown class/section are skipped and listed in the report.</li>
                    </ul>
                    <div class="text-muted">Columns:</div>
                    <div>
                        @foreach($columns as $c)<code class="d-inline-block mb-1 mr-1" style="background:#eef2ff;color:#4338ca;padding:2px 6px;border-radius:4px;font-size:11px;">{{ $c }}</code>@endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
