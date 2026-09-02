@extends('layouts.vendor.app')
@section('title', 'Laboratory — Test Catalog')

@push('css_or_js')
<style>
    /* Eight columns of editable fields; without a floor the table squeezes the test name
       down to a few letters instead of letting the wrapper scroll. */
    .labcat-table { min-width: 940px; }

    @media (max-width: 767px) {
        .content.container-fluid { padding: 0.75rem; }
        .labcat-head { flex-wrap: wrap; gap: 8px; }
        .labcat-head form { width: 100%; }
        .labcat-search { max-width: 100% !important; width: 100%; }
        .labcat-table td .btn-xs { margin-bottom: 2px; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    @php
        $canAdd  = hasPermission('lab_catalog', 'add');
        $canEdit = hasPermission('lab_catalog', 'edit');
        $canDel  = hasPermission('lab_catalog', 'delete');
    @endphp

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-lab" style="font-size:22px;"></i></span>
                Test Catalog
            </h1>
            <span class="text-muted" style="font-size:12px;">
                What each test costs and how long it takes. Ordering a test opens with the price from here —
                use <strong>Parameters</strong> on a row to set its reference ranges.
            </span>
        </div>
        <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
            {{-- Bulk in, bulk out. Same column layout both ways, so a catalog can be exported,
                 edited in a spreadsheet and put back — which is how a lab of two hundred tests
                 actually maintains its prices. --}}
            <a href="{{ route('vendor.lab.catalog.export') }}" class="btn btn-sm btn-outline-success">
                <i class="tio-download"></i> Export
            </a>
            @if($canAdd)
                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#labImportModal">
                    <i class="tio-upload"></i> Import
                </button>
            @endif
            <a href="{{ route('vendor.lab.worklist') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-folder"></i> Test Worklist
            </a>
        </div>
    </div>

    @if($canAdd)
    {{-- Import, with the file's shape stated on the same screen as the upload box. A template
         download rather than a page of instructions: the file itself is the documentation. --}}
    <div class="modal fade" id="labImportModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post" action="{{ route('vendor.lab.catalog.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header py-2">
                        <h5 class="modal-title" style="font-size:15px;">Import test catalog</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted" style="font-size:12.5px;">
                            One row per <strong>parameter</strong>, with the test's own columns repeated down its
                            rows. Rows sharing a code are one test. A test that measures nothing — a fee line —
                            is a single row with the parameter columns left blank.
                        </p>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm mb-0" style="font-size:11px;">
                                <thead class="thead-light">
                                    <tr>
                                        @foreach(\App\Modules\HMIS\Controllers\Vendor\LabController::CATALOG_COLUMNS as $col)
                                            <th class="border-0" style="white-space:nowrap;">{{ $col }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-muted">
                                        <td>Complete Blood Count</td><td>CBC</td><td>Haematology</td><td>EDTA Blood</td>
                                        <td>250</td><td>Same day</td><td>Yes</td>
                                        <td>Haemoglobin</td><td>g/dL</td><td>13</td><td>17</td><td>13 – 17 g/dL</td><td>7</td><td>20</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group">
                            <label class="input-label" style="font-size:12px;">Spreadsheet <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                            <small class="text-muted">.xlsx, .xls or .csv — up to 5 MB.</small>
                        </div>

                        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:12px;">
                            A test already in your catalog with the same <strong>code</strong> (or name, where there is
                            no code) is <strong>updated</strong>, not duplicated — and its parameters are replaced by
                            what the file says. Nothing is deleted.
                        </div>
                    </div>
                    <div class="modal-footer py-2 d-flex justify-content-between">
                        <a href="{{ route('vendor.lab.catalog.template') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="tio-download"></i> Sample template
                        </a>
                        <div>
                            <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn--primary btn-sm">Import</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        {{-- ── Add a test ──────────────────────────────────────────── --}}
        @if($canAdd)
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-header py-2 bg-light">
                    <h6 class="mb-0 font-weight-bold" style="font-size:13px">Add a test</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('vendor.lab.catalog.store') }}">
                        @csrf
                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px">Test / panel name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-sm"
                                   placeholder="e.g. Lipid Profile" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Code</label>
                                    <input type="text" name="code" value="{{ old('code') }}" class="form-control form-control-sm"
                                           placeholder="LIPID">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="input-label" style="font-size:12px">TAT</label>
                                    <input type="text" name="tat_text" value="{{ old('tat_text') }}"
                                           class="form-control form-control-sm" placeholder="1–2 hours">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px">Department</label>
                            <input type="text" name="department" value="{{ old('department') }}"
                                   class="form-control form-control-sm" placeholder="Biochemistry, Haematology...">
                        </div>
                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px">Sample type</label>
                            <input type="text" name="sample_type" value="{{ old('sample_type') }}"
                                   class="form-control form-control-sm" placeholder="Venous Blood, Urine...">
                        </div>
                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px">
                                Price ({{ \App\CentralLogics\Helpers::currency_symbol() ?? '₹' }}) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}"
                                   class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="d-flex align-items-center mb-0" style="gap:6px; font-size:12px; font-weight:600;">
                                <input type="checkbox" name="is_active" value="1" checked> Available for ordering
                            </label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="tio-add"></i> Add to catalog
                        </button>
                        <div class="text-muted mt-2" style="font-size:11px">
                            The test starts with one parameter named after it — open <strong>Parameters</strong>
                            on its row to add analytes and reference ranges.
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Priced tests ────────────────────────────────────────── --}}
        <div class="{{ $canAdd ? 'col-lg-8' : 'col-12' }}">
            <div class="card">
                <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center labcat-head">
                    <h6 class="mb-0 font-weight-bold" style="font-size:13px">
                        Priced tests <span class="text-muted">({{ $tests->count() }})</span>
                    </h6>
                    <form method="get" class="mb-0">
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-control form-control-sm labcat-search"
                               style="max-width:220px" placeholder="Search test / code...">
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-align-middle mb-0 labcat-table" style="font-size:13px">
                        <thead class="bg-light">
                            <tr>
                                <th>Test</th>
                                <th style="width:110px">Code</th>
                                <th style="width:140px">Department</th>
                                <th style="width:130px">Sample</th>
                                <th style="width:100px">TAT</th>
                                <th style="width:105px">Price</th>
                                <th style="width:70px">Active</th>
                                <th style="width:150px"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($tests as $t)
                            {{-- The row's form lives in a cell and the inputs point at it by id:
                                 a <form> wrapping <td>s is invalid, and the browser hoists it out
                                 of the table, leaving the row unable to submit. The row posts no
                                 parameter rows, so saving here leaves the reference ranges alone. --}}
                            <tr>
                                <td>
                                    <form id="labcat{{ $t->id }}" method="post" action="{{ route('vendor.lab.catalog.update', $t->id) }}">@csrf</form>
                                    <input type="text" name="name" form="labcat{{ $t->id }}" value="{{ $t->name }}"
                                           class="form-control form-control-sm" {{ $canEdit ? '' : 'readonly' }} required>
                                </td>
                                <td>
                                    <input type="text" name="code" form="labcat{{ $t->id }}" value="{{ $t->code }}"
                                           class="form-control form-control-sm" placeholder="—" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="text" name="department" form="labcat{{ $t->id }}" value="{{ $t->department }}"
                                           class="form-control form-control-sm" placeholder="—" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="text" name="sample_type" form="labcat{{ $t->id }}" value="{{ $t->sample_type }}"
                                           class="form-control form-control-sm" placeholder="—" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="text" name="tat_text" form="labcat{{ $t->id }}" value="{{ $t->tat_text }}"
                                           class="form-control form-control-sm" placeholder="—" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="number" name="price" form="labcat{{ $t->id }}" step="0.01" min="0" value="{{ $t->price }}"
                                           class="form-control form-control-sm" {{ $canEdit ? '' : 'readonly' }} required>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="is_active" form="labcat{{ $t->id }}" value="1"
                                           @if($t->is_active) checked @endif {{ $canEdit ? '' : 'disabled' }}>
                                </td>
                                <td class="text-right">
                                    @if($canEdit)
                                        <button type="submit" form="labcat{{ $t->id }}" class="btn btn-xs btn-primary">Save</button>
                                        <a href="{{ route('vendor.lab.catalog.edit', $t->id) }}" class="btn btn-xs btn-outline-secondary"
                                           title="Analytes and reference ranges">
                                            Parameters <span class="badge badge-soft-info ml-1">{{ $t->parameters_count }}</span>
                                        </a>
                                    @endif
                                    @if($canDel)
                                        <a href="{{ route('vendor.lab.catalog.delete', $t->id) }}"
                                           class="btn btn-xs btn-outline-danger"
                                           onclick="return confirm('Remove {{ addslashes($t->name) }} from the catalog? Tests already ordered keep their price.')">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Nothing priced yet. @if($canAdd)Add a test on the left.@endif
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
