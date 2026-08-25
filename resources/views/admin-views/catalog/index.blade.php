@extends('layouts.admin.app')
@section('title', 'Shared Item Pool')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .cp-table { min-width: 940px; }
        .cp-thumb {
            width:38px; height:38px; border-radius:8px; object-fit:cover; background:#f1f5f9;
            display:inline-flex; align-items:center; justify-content:center; color:#97a4af; flex-shrink:0;
        }
        .cp-stat { border-radius:10px; padding:14px 18px; background:#fff; border:1px solid #e7eaf3; }
        .cp-stat .v { font-size:22px; font-weight:700; color:#1e2022; line-height:1.1; }
        .cp-stat .l { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#8c98a4; margin-top:3px; }
        @media (max-width: 767px) {
            .cp-filters { flex-wrap: wrap; gap: 8px; }
            .cp-filters .form-control { width: 100% !important; max-width: 100% !important; }
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-book-opened"></i></span>
                Shared Item Pool
            </h1>
            <span class="text-muted" style="font-size:12px;">
                One curated record per product. Every store adopts from here instead of typing its own
                version, so a correction made once reaches all of them.
            </span>
        </div>
        <div class="d-flex" style="gap:8px;">
            <a href="{{ route('admin.catalog.suggestions') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-inbox"></i> Suggestions
                @if($counts['pending'])<span class="badge badge-danger ml-1">{{ $counts['pending'] }}</span>@endif
            </a>
            <button class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#cpImportModal">
                <i class="tio-upload-on-cloud"></i> Import
            </button>
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#cpAddModal">
                <i class="tio-add"></i> Add item
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-4 mb-2"><div class="cp-stat"><div class="v">{{ number_format($counts['total']) }}</div><div class="l">In the pool</div></div></div>
        <div class="col-sm-4 mb-2"><div class="cp-stat"><div class="v">{{ number_format($counts['adopted']) }}</div><div class="l">Adopted by a store</div></div></div>
        <div class="col-sm-4 mb-2"><div class="cp-stat"><div class="v">{{ number_format($counts['pending']) }}</div><div class="l">Waiting for review</div></div></div>
    </div>

    <form method="get" class="card mb-3">
        <input type="hidden" name="domain" value="{{ $domain }}">
        <div class="card-body py-2 d-flex align-items-center cp-filters" style="gap:10px;">
            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                   style="max-width:280px;" placeholder="Search name or brand...">
            <select name="form" class="form-control form-control-sm" style="max-width:170px;" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach($forms as $f)
                    <option value="{{ $f }}" {{ $form === $f ? 'selected' : '' }}>{{ $f }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">Search</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-align-middle mb-0 cp-table" style="font-size:13px">
                <thead class="thead-light">
                    <tr>
                        <th style="width:54px;"></th>
                        <th>Name</th>
                        <th style="width:150px;">Brand</th>
                        <th style="width:110px;">Strength</th>
                        <th style="width:130px;">Type</th>
                        <th style="width:110px;">Used by</th>
                        <th style="width:150px;"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $c)
                    <tr class="{{ $c->status === 'retired' ? 'text-muted' : '' }}">
                        <td>
                            @if($c->image_url)
                                <img src="{{ $c->image_url }}" class="cp-thumb" alt="">
                            @else
                                <span class="cp-thumb"><i class="tio-image"></i></span>
                            @endif
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ $c->name }}</div>
                            @if($c->status === 'retired')<span class="badge badge-soft-secondary">Retired</span>@endif
                        </td>
                        <td>{{ $c->brand ?: '—' }}</td>
                        <td>{{ $c->strength_text ?: '—' }}</td>
                        <td>@if($c->form)<span class="badge badge-soft-info">{{ $c->form }}</span>@else — @endif</td>
                        <td>{{ $c->usage_count }} {{ $c->usage_count == 1 ? 'store' : 'stores' }}</td>
                        <td class="text-right">
                            <button class="btn btn-sm btn-outline-primary cp-edit"
                                    data-item='{{ json_encode(["id" => $c->id, "name" => $c->name, "brand" => $c->brand, "strength" => $c->strength_text, "form" => $c->form, "retired" => $c->status === "retired", "label" => $c->label, "usage" => $c->usage_count]) }}'>Edit</button>
                            <a href="{{ route('admin.catalog.delete', $c->id) }}" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('{{ $c->usage_count ? 'This is used by ' . $c->usage_count . ' store(s) — it will be retired rather than deleted. Continue?' : 'Remove this from the pool?' }}')">
                                <i class="tio-delete-outlined"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        Nothing in the pool yet. Import a list, or approve what stores have already asked for.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{!! $items->links() !!}</div>
</div>

{{-- ── Add ─────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="cpAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add to the pool</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="post" action="{{ route('admin.catalog.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="domain" value="{{ $domain }}">
            <div class="modal-body">
                <div class="form-group"><label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Pantoprazole" required></div>
                <div class="form-row">
                    <div class="form-group col-6"><label>Brand</label>
                        <input type="text" name="brand" class="form-control" placeholder="Pan-40"></div>
                    <div class="form-group col-6"><label>Strength</label>
                        <input type="text" name="strength_text" class="form-control" placeholder="40 mg"></div>
                </div>
                <div class="form-group"><label>Type</label>
                    <select name="form" class="form-control">
                        <option value="">— Select —</option>
                        @foreach($forms as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                    </select></div>
                <div class="form-group mb-0"><label>Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <small class="text-muted">Shown to every store that adopts it, unless they upload their own.</small>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
        </form>
    </div></div>
</div>

{{-- ── Edit / merge ────────────────────────────────────────────────── --}}
<div class="modal fade" id="cpEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit pooled item</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form id="cpEditForm" method="post" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="alert alert-soft-info" style="font-size:12.5px;" id="cpEditNote"></div>
                <div class="form-group"><label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="cp_name" class="form-control" required></div>
                <div class="form-row">
                    <div class="form-group col-6"><label>Brand</label>
                        <input type="text" name="brand" id="cp_brand" class="form-control"></div>
                    <div class="form-group col-6"><label>Strength</label>
                        <input type="text" name="strength_text" id="cp_strength" class="form-control"></div>
                </div>
                <div class="form-group"><label>Type</label>
                    <select name="form" id="cp_form" class="form-control">
                        <option value="">— Select —</option>
                        @foreach($forms as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                    </select></div>
                <div class="form-group"><label>Replace image</label>
                    <input type="file" name="image" class="form-control" accept="image/*"></div>
                <label class="d-flex align-items-center mb-0" style="gap:6px; font-size:13px;">
                    <input type="checkbox" name="retired" id="cp_retired" value="1">
                    Retired — stores keep what they have, but it is hidden from the catalog
                </label>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Save changes</button></div>
        </form>

        <div class="modal-footer d-block border-top pt-3">
            <form id="cpMergeForm" method="post" class="d-flex align-items-center" style="gap:8px;">
                @csrf
                <span class="text-muted" style="font-size:12px; white-space:nowrap;">Duplicate? Merge into id</span>
                <input type="number" name="target_id" class="form-control form-control-sm" style="width:110px;" placeholder="e.g. 841" required>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Merge this record into the one you entered? Every store using it will be moved across.')">
                    Merge
                </button>
            </form>
        </div>
    </div></div>
</div>

{{-- ── Import ──────────────────────────────────────────────────────── --}}
<div class="modal fade" id="cpImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Import into the pool</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="post" action="{{ route('admin.catalog.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="domain" value="{{ $domain }}">
            <div class="modal-body">
                <div class="form-group">
                    <label>File (CSV or Excel) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt,.xls,.xlsx" required>
                </div>
                <div class="alert alert-soft-info mb-0" style="font-size:12.5px;">
                    Columns: <strong>name, brand, strength, type</strong> — any other column is ignored,
                    so a stock sheet can be uploaded as it is. <code>medicine_name</code>,
                    <code>brand_example</code> and <code>dosage_form</code> are recognised too.<br>
                    Rows already in the pool are left alone — re-importing a corrected file is safe,
                    and a file listing the same medicine twice still produces one record.
                    <a href="{{ asset('public/assets/catalog_pool_format.csv') }}" download class="d-inline-block mt-2">
                        <i class="tio-download-to"></i> Download a sample sheet
                    </a>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Import</button></div>
        </form>
    </div></div>
</div>
@endsection

@push('script_2')
<script>
    (function () {
        const updateUrl = "{{ route('admin.catalog.update', ['id' => '__ID__']) }}";
        const mergeUrl  = "{{ route('admin.catalog.merge', ['id' => '__ID__']) }}";

        document.querySelectorAll('.cp-edit').forEach(btn => btn.addEventListener('click', function () {
            const d = JSON.parse(this.dataset.item);
            document.getElementById('cpEditForm').action  = updateUrl.replace('__ID__', d.id);
            document.getElementById('cpMergeForm').action = mergeUrl.replace('__ID__', d.id);
            document.getElementById('cp_name').value      = d.name || '';
            document.getElementById('cp_brand').value     = d.brand || '';
            document.getElementById('cp_strength').value  = d.strength || '';
            document.getElementById('cp_form').value      = d.form || '';
            document.getElementById('cp_retired').checked = !!d.retired;
            document.getElementById('cpEditNote').textContent =
                'id ' + d.id + ' · ' + d.label + ' — used by ' + d.usage + ' store(s). '
                + 'Saving corrects their copies too, unless they renamed it themselves.';
            $('#cpEditModal').modal('show');
        }));
    })();
</script>
@endpush
