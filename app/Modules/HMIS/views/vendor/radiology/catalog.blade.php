@extends('layouts.vendor.app')
@section('title', 'Radiology — Scan Catalog')

@push('css_or_js')
<style>
    /* Seven editable columns; without a floor the table squeezes the scan name down to
       a few letters instead of letting the wrapper scroll. */
    .radcat-table { min-width: 860px; }

    @media (max-width: 767px) {
        .content.container-fluid { padding: 0.75rem; }
        .radcat-head { flex-wrap: wrap; gap: 8px; }
        .radcat-head form { width: 100%; }
        .radcat-search { max-width: 100% !important; width: 100%; }
        .radcat-table td .btn-xs { margin-bottom: 2px; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    @php
        $rxOwner   = auth('vendor')->check();
        $canAdd    = $rxOwner || hasPermission('radiology_catalog', 'add');
        $canEdit   = $rxOwner || hasPermission('radiology_catalog', 'edit');
        $canDel    = $rxOwner || hasPermission('radiology_catalog', 'delete');
        $modalities = ['X-Ray', 'CT Scan', 'MRI', 'Ultrasound', 'ECG', 'Mammography', 'PET'];
    @endphp

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-image" style="font-size:22px;"></i></span>
                Scan Catalog
            </h1>
            <span class="text-muted" style="font-size:12px;">
                What each scan costs and how long it takes. Ordering a scan opens with the price from here.
            </span>
        </div>
        <a href="{{ route('vendor.radiology.worklist') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-folder"></i> Study Worklist
        </a>
    </div>

    <div class="row">
        {{-- ── Add a scan ──────────────────────────────────────────── --}}
        @if($canAdd)
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-header py-2 bg-light">
                    <h6 class="mb-0 font-weight-bold" style="font-size:13px">Add a scan</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('vendor.radiology.catalog.store') }}">
                        @csrf
                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px">Scan name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-sm"
                                   placeholder="e.g. Chest X-Ray PA View" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px">Modality <span class="text-danger">*</span></label>
                            <select name="modality" class="form-control form-control-sm" required>
                                @foreach($modalities as $m)
                                    <option value="{{ $m }}" {{ old('modality') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="input-label" style="font-size:12px">Body part</label>
                                    <input type="text" name="body_part" value="{{ old('body_part') }}"
                                           class="form-control form-control-sm" placeholder="e.g. Chest">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="input-label" style="font-size:12px">TAT</label>
                                    <input type="text" name="tat_text" value="{{ old('tat_text') }}"
                                           class="form-control form-control-sm" placeholder="e.g. 45 min">
                                </div>
                            </div>
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
                                <input type="checkbox" name="is_active" value="1" checked> Available for booking
                            </label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="tio-add"></i> Add to catalog
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Priced scans ────────────────────────────────────────── --}}
        <div class="{{ $canAdd ? 'col-lg-8' : 'col-12' }}">
            <div class="card">
                <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center radcat-head">
                    <h6 class="mb-0 font-weight-bold" style="font-size:13px">
                        Priced scans <span class="text-muted">({{ $tests->count() }})</span>
                    </h6>
                    <form method="get" class="mb-0">
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-control form-control-sm radcat-search"
                               style="max-width:220px" placeholder="Search scan / modality...">
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-align-middle mb-0 radcat-table" style="font-size:13px">
                        <thead class="bg-light">
                            <tr>
                                <th>Scan</th>
                                <th style="width:130px">Modality</th>
                                <th style="width:120px">Body part</th>
                                <th style="width:100px">TAT</th>
                                <th style="width:110px">Price</th>
                                <th style="width:80px">Active</th>
                                <th style="width:110px"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($tests as $t)
                            {{-- The row's form lives in a cell and the inputs point at it by id:
                                 a <form> wrapping <td>s is invalid, and the browser hoists it out
                                 of the table, leaving the row unable to submit. --}}
                            <tr>
                                <td>
                                    <form id="radcat{{ $t->id }}" method="post" action="{{ route('vendor.radiology.catalog.update', $t->id) }}">@csrf</form>
                                    <input type="text" name="name" form="radcat{{ $t->id }}" value="{{ $t->name }}"
                                           class="form-control form-control-sm" {{ $canEdit ? '' : 'readonly' }} required>
                                </td>
                                <td>
                                    <select name="modality" form="radcat{{ $t->id }}" class="form-control form-control-sm" {{ $canEdit ? '' : 'disabled' }} required>
                                        @foreach($modalities as $m)
                                            <option value="{{ $m }}" {{ $t->modality === $m ? 'selected' : '' }}>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="body_part" form="radcat{{ $t->id }}" value="{{ $t->body_part }}"
                                           class="form-control form-control-sm" placeholder="—" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="text" name="tat_text" form="radcat{{ $t->id }}" value="{{ $t->tat_text }}"
                                           class="form-control form-control-sm" placeholder="—" {{ $canEdit ? '' : 'readonly' }}>
                                </td>
                                <td>
                                    <input type="number" name="price" form="radcat{{ $t->id }}" step="0.01" min="0" value="{{ $t->price }}"
                                           class="form-control form-control-sm" {{ $canEdit ? '' : 'readonly' }} required>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="is_active" form="radcat{{ $t->id }}" value="1"
                                           @if($t->is_active) checked @endif {{ $canEdit ? '' : 'disabled' }}>
                                </td>
                                <td class="text-right">
                                    @if($canEdit)
                                        <button type="submit" form="radcat{{ $t->id }}" class="btn btn-xs btn-primary">Save</button>
                                    @endif
                                    @if($canDel)
                                        <a href="{{ route('vendor.radiology.catalog.delete', $t->id) }}"
                                           class="btn btn-xs btn-outline-danger"
                                           onclick="return confirm('Remove {{ addslashes($t->name) }} from the catalog? Scans already ordered keep their price.')">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nothing priced yet. @if($canAdd)Add a scan on the left.@endif
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
