@extends('layouts.admin.app')
@section('title', 'Brand Pool')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .bp-stat { border-radius:10px; padding:14px 18px; background:#fff; border:1px solid #e7eaf3; }
        .bp-stat .v { font-size:22px; font-weight:700; color:#1e2022; line-height:1.1; }
        .bp-stat .l { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#8c98a4; margin-top:3px; }
        .bp-badge-active { background:#e6f9f0; color:#16a34a; border:1px solid #bbf0d4; font-weight:600; font-size:12px; padding:2px 10px; border-radius:6px; }
        .bp-badge-inactive { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-weight:600; font-size:12px; padding:2px 10px; border-radius:6px; }
        .bp-cats { display:flex; flex-wrap:wrap; gap:4px; }
        .bp-cat { font-size:11px; background:#f1f5fb; color:#334155; padding:2px 8px; border-radius:5px; border:1px solid #e2e8f2; }
        @media (max-width: 767px) {
            .bp-filters { flex-wrap: wrap; gap: 8px; }
            .bp-filters .form-control { width: 100% !important; max-width: 100% !important; }
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-label"></i></span>
                Brand Pool
            </h1>
            <span class="text-muted" style="font-size:12px;">
                Centralised brand directory. Brands show on SEO service pages and vendors pick from them when adding inventory items.
            </span>
        </div>
        <div class="d-flex" style="gap:8px;">
            <button class="btn btn-sm text-white" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border:none; font-weight:600;" data-toggle="modal" data-target="#bpAiModal">
                <i class="tio-magic-wand"></i> Add with AI
            </button>
            <button class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#bpImportModal">
                <i class="tio-upload-on-cloud"></i> Import
            </button>
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#bpAddModal">
                <i class="tio-add"></i> Add brand
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-3 mb-2"><div class="bp-stat"><div class="v">{{ number_format($counts['total']) }}</div><div class="l">Total brands</div></div></div>
        <div class="col-sm-3 mb-2"><div class="bp-stat"><div class="v">{{ number_format($counts['active']) }}</div><div class="l">Active</div></div></div>
        <div class="col-sm-3 mb-2"><div class="bp-stat"><div class="v">{{ number_format($counts['inactive']) }}</div><div class="l">Inactive</div></div></div>
        <div class="col-sm-3 mb-2"><div class="bp-stat"><div class="v">{{ number_format($counts['in_use']) }}</div><div class="l">Used by items</div></div></div>
    </div>

    <form method="get" class="card mb-3">
        <div class="card-body py-2 d-flex align-items-center bp-filters" style="gap:10px;">
            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                   style="max-width:280px;" placeholder="Search brand name...">
            <select name="status" class="form-control form-control-sm" style="max-width:160px;" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button class="btn btn-sm btn-primary">Search</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-align-middle mb-0" style="font-size:13px">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Categories / Services</th>
                        <th style="width:90px;">Status</th>
                        <th style="width:90px;">Used by</th>
                        <th style="width:150px;"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($brands as $b)
                    <tr>
                        <td>{{ $b->id }}</td>
                        <td class="font-weight-bold">{{ $b->name }}</td>
                        <td class="text-muted">{{ $b->slug }}</td>
                        <td>
                            <div class="bp-cats">
                                @if(isset($b->services) && $b->services->isNotEmpty())
                                    @foreach($b->services as $srv)
                                        <span class="bp-cat" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;" title="Service (Module 6)">
                                            <i class="tio-wrench mr-1"></i>{{ $srv->name }}
                                        </span>
                                    @endforeach
                                @endif
                                @foreach($b->categories as $cat)
                                    <span class="bp-cat" title="Category">
                                        <i class="tio-folder-outlined mr-1"></i>{{ $cat->name }}
                                    </span>
                                @endforeach
                                @if($b->categories->isEmpty() && (!isset($b->services) || $b->services->isEmpty()))
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.brand-pool.toggle-status', $b->id) }}"
                               class="{{ $b->status === 'active' ? 'bp-badge-active' : 'bp-badge-inactive' }}"
                               style="text-decoration:none;">
                                {{ ucfirst($b->status) }}
                            </a>
                        </td>
                        <td>{{ $b->usage_count }} {{ $b->usage_count == 1 ? 'item' : 'items' }}</td>
                        <td class="text-right">
                            <button class="btn btn-sm btn-outline-primary bp-edit"
                                    data-brand='{{ json_encode([
                                        "id" => $b->id,
                                        "name" => $b->name,
                                        "targets" => array_merge(
                                            $b->categories->map(fn($c) => "cat_" . $c->id)->all(),
                                            (isset($b->services) ? $b->services->map(fn($s) => "srv_" . $s->id)->all() : [])
                                        )
                                    ]) }}'>Edit</button>
                            <a href="{{ route('admin.brand-pool.delete', $b->id) }}" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('{{ $b->usage_count ? 'Used by ' . $b->usage_count . ' item(s) — it will be deactivated instead. Continue?' : 'Remove this brand from the pool?' }}')">
                                <i class="tio-delete-outlined"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        No brands in the pool yet. Add one or import from a file.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{!! $brands->links() !!}</div>
</div>

{{-- ── Add ─────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="bpAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add brand to pool</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="post" action="{{ route('admin.brand-pool.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group"><label>Brand name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. LG, Samsung, Haier" required></div>
                <div class="form-group mb-0"><label>Service / Category</label>
                    <select name="targets[]" class="form-control js-select2-custom" multiple>
                        <optgroup label="🛠 Services (Module 6)">
                            @foreach($services as $srv)
                                <option value="srv_{{ $srv->id }}">🔧 {{ $srv->name }} (Service)</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="📁 Primary Categories">
                            @foreach($categories as $cat)
                                <option value="cat_{{ $cat->id }}">📁 {{ $cat->name }} (Category)</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <small class="text-muted">Select specific services (e.g. AC Repair) or primary categories.</small>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
        </form>
    </div></div>
</div>

{{-- ── Edit ────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="bpEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit brand</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form id="bpEditForm" method="post">
            @csrf
            <div class="modal-body">
                <div class="form-group"><label>Brand name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="bp_name" class="form-control" required></div>
                <div class="form-group mb-0"><label>Service / Category</label>
                    <select name="targets[]" id="bp_targets" class="form-control js-select2-custom" multiple>
                        <optgroup label="🛠 Services (Module 6)">
                            @foreach($services as $srv)
                                <option value="srv_{{ $srv->id }}">🔧 {{ $srv->name }} (Service)</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="📁 Primary Categories">
                            @foreach($categories as $cat)
                                <option value="cat_{{ $cat->id }}">📁 {{ $cat->name }} (Category)</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <small class="text-muted">Select specific services (e.g. AC Repair) or primary categories.</small>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Save changes</button></div>
        </form>
    </div></div>
</div>

{{-- ── Import ──────────────────────────────────────────────────────── --}}
<div class="modal fade" id="bpImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Import brands</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="post" action="{{ route('admin.brand-pool.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>File (CSV or Excel) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt,.xls,.xlsx" required>
                </div>
                <div class="alert alert-soft-info mb-0" style="font-size:12.5px;">
                    Columns: <strong>name</strong> (required), <strong>service / category</strong> (optional) —
                    any other column is ignored.<br>
                    Matches against service names (e.g. AC Repair) and category names automatically.<br>
                    Duplicate brand names are skipped — re-importing is safe.
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Import</button></div>
        </form>
    </div></div>
</div>
{{-- ── AI Generate Modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="bpAiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff;">
                <h5 class="modal-title text-white">
                    <i class="tio-magic-wand mr-1"></i> Add Brands with AI
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-soft-info" style="font-size:13px;">
                    <i class="tio-info-outined mr-1"></i>
                    AI automatically discovers, curates, and validates recognized brand names for your services (e.g. AC Repair) or product categories, then lets you review and add them in 1-click.
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Service / Category</label>
                        <select id="ai_category_select" class="form-control js-select2-custom">
                            <option value="">-- Choose Service or Category (Optional) --</option>
                            <optgroup label="🛠 Services (Module 6)">
                                @foreach($services as $srv)
                                    <option value="srv_{{ $srv->id }}" data-name="{{ $srv->name }}" data-type="service">🔧 {{ $srv->name }} (Service)</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="📁 Primary Categories">
                                @foreach($categories as $cat)
                                    <option value="cat_{{ $cat->id }}" data-name="{{ $cat->name }}" data-type="category">📁 {{ $cat->name }} (Category)</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <small class="text-muted">e.g. AC Repair, Washing Machine, Electronics</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Number of Brands</label>
                        <select id="ai_count_select" class="form-control">
                            <option value="10">10 brands</option>
                            <option value="15" selected>15 brands</option>
                            <option value="25">25 brands</option>
                            <option value="40">40 brands</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Custom Prompt / Instructions (Optional)</label>
                    <textarea id="ai_prompt_input" class="form-control" rows="2"
                              placeholder="e.g. Include top recognized Indian and global brands for AC Repair and Installation..."></textarea>
                </div>

                <div class="text-right mb-3">
                    <button type="button" id="btn_ai_generate" class="btn text-white" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); font-weight: 600;">
                        <span class="spinner-border spinner-border-sm mr-1 d-none" id="ai_gen_spinner"></span>
                        <i class="tio-magic-wand mr-1" id="ai_gen_icon"></i> Generate Brands with AI
                    </button>
                </div>

                <div id="ai_results_container" class="d-none border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap:8px;">
                        <h6 class="font-weight-bold mb-0">Generated Brands (<span id="ai_results_count">0</span>)</h6>
                        <div>
                            <button type="button" class="btn btn-xs btn-outline-success mr-1" id="btn_select_all_new">Select New Only</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary mr-1" id="btn_select_all">Select All</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary" id="btn_deselect_all">Deselect All</button>
                        </div>
                    </div>

                    <div class="p-2 border rounded bg-light" style="max-height: 250px; overflow-y: auto;" id="ai_brand_list">
                        <!-- Brand checkboxes rendered dynamically -->
                    </div>

                    <div class="form-group mt-3">
                        <label class="font-weight-bold">Link selected brands to service / category:</label>
                        <select id="ai_target_categories" class="form-control js-select2-custom" multiple>
                            <optgroup label="🛠 Services (Module 6)">
                                @foreach($services as $srv)
                                    <option value="srv_{{ $srv->id }}">🔧 {{ $srv->name }} (Service)</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="📁 Primary Categories">
                                @foreach($categories as $cat)
                                    <option value="cat_{{ $cat->id }}">📁 {{ $cat->name }} (Category)</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <small class="text-muted">Brands will be linked to these services and categories.</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span id="ai_selected_summary" class="text-muted font-size-sm">0 brand(s) selected</span>
                        <button type="button" id="btn_ai_save" class="btn btn-success font-weight-bold" disabled>
                            <span class="spinner-border spinner-border-sm mr-1 d-none" id="ai_save_spinner"></span>
                            <i class="tio-checkmark-circle mr-1"></i> Add Selected Brands to Pool
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    (function () {
        const updateUrl = "{{ route('admin.brand-pool.update', ['id' => '__ID__']) }}";

        document.querySelectorAll('.bp-edit').forEach(btn => btn.addEventListener('click', function () {
            const d = JSON.parse(this.dataset.brand);
            document.getElementById('bpEditForm').action = updateUrl.replace('__ID__', d.id);
            document.getElementById('bp_name').value = d.name || '';

            // Set selected categories / services in the Select2.
            const sel = $('#bp_targets');
            sel.val(d.targets || []).trigger('change');

            $('#bpEditModal').modal('show');
        }));

        // AI Brand Generator Logic
        const aiGenUrl = "{{ route('admin.brand-pool.ai-generate') }}";
        const aiSaveUrl = "{{ route('admin.brand-pool.ai-save') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        const btnGen = document.getElementById('btn_ai_generate');
        const genSpinner = document.getElementById('ai_gen_spinner');
        const genIcon = document.getElementById('ai_gen_icon');
        const resultsContainer = document.getElementById('ai_results_container');
        const brandList = document.getElementById('ai_brand_list');
        const resultsCount = document.getElementById('ai_results_count');
        const btnSave = document.getElementById('btn_ai_save');
        const saveSpinner = document.getElementById('ai_save_spinner');
        const selectedSummary = document.getElementById('ai_selected_summary');

        // Sync category/service select to target categories in modal
        $('#ai_category_select').on('change', function () {
            const val = $(this).val();
            if (val) {
                const targetSel = $('#ai_target_categories');
                const curr = targetSel.val() || [];
                if (!curr.includes(val)) {
                    curr.push(val);
                    targetSel.val(curr).trigger('change');
                }
            }
        });

        function updateSelectionSummary() {
            const checked = document.querySelectorAll('.ai-brand-check:checked');
            selectedSummary.textContent = checked.length + ' brand(s) selected';
            btnSave.disabled = checked.length === 0;
        }

        btnGen.addEventListener('click', function () {
            const targetVal = document.getElementById('ai_category_select').value;
            const prompt = document.getElementById('ai_prompt_input').value.trim();
            const count = document.getElementById('ai_count_select').value;

            if (!targetVal && !prompt) {
                alert('Please choose a service or category, or type a prompt for the AI.');
                return;
            }

            btnGen.disabled = true;
            genSpinner.classList.remove('d-none');
            genIcon.classList.add('d-none');
            resultsContainer.classList.add('d-none');
            brandList.innerHTML = '';

            fetch(aiGenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    target: targetVal,
                    prompt: prompt,
                    count: count
                })
            })
            .then(res => res.json())
            .then(data => {
                btnGen.disabled = false;
                genSpinner.classList.add('d-none');
                genIcon.classList.remove('d-none');

                if (!data.success || !data.brands || data.brands.length === 0) {
                    alert(data.message || 'No brands could be generated. Please try a different service or prompt.');
                    return;
                }

                resultsCount.textContent = data.brands.length;
                let html = '<div class="row no-gutters">';

                data.brands.forEach((b, idx) => {
                    const isNew = !b.exists;
                    const checkedAttr = isNew ? 'checked' : '';
                    const badge = isNew
                        ? '<span class="badge badge-soft-success ml-1 font-size-xs">New</span>'
                        : '<span class="badge badge-soft-secondary ml-1 font-size-xs">In Pool</span>';

                    html += `
                        <div class="col-sm-6 col-md-4 p-1">
                            <label class="d-flex align-items-center p-2 rounded border bg-white mb-0" style="cursor: pointer; user-select: none;">
                                <input type="checkbox" class="ai-brand-check mr-2" value="${b.name}" data-new="${isNew ? '1' : '0'}" ${checkedAttr}>
                                <span class="text-truncate font-weight-bold" style="font-size: 13px;">${b.name}</span>
                                ${badge}
                            </label>
                        </div>
                    `;
                });
                html += '</div>';
                brandList.innerHTML = html;
                resultsContainer.classList.remove('d-none');

                document.querySelectorAll('.ai-brand-check').forEach(cb => {
                    cb.addEventListener('change', updateSelectionSummary);
                });
                updateSelectionSummary();
            })
            .catch(err => {
                btnGen.disabled = false;
                genSpinner.classList.add('d-none');
                genIcon.classList.remove('d-none');
                alert('An error occurred while generating brands: ' + err.message);
            });
        });

        // Quick select buttons
        document.getElementById('btn_select_all_new').addEventListener('click', function () {
            document.querySelectorAll('.ai-brand-check').forEach(cb => {
                cb.checked = (cb.dataset.new === '1');
            });
            updateSelectionSummary();
        });

        document.getElementById('btn_select_all').addEventListener('click', function () {
            document.querySelectorAll('.ai-brand-check').forEach(cb => {
                cb.checked = true;
            });
            updateSelectionSummary();
        });

        document.getElementById('btn_deselect_all').addEventListener('click', function () {
            document.querySelectorAll('.ai-brand-check').forEach(cb => {
                cb.checked = false;
            });
            updateSelectionSummary();
        });

        // Save selected brands to pool
        btnSave.addEventListener('click', function () {
            const selectedBrands = Array.from(document.querySelectorAll('.ai-brand-check:checked')).map(cb => cb.value);
            const targetCats = $('#ai_target_categories').val() || [];

            if (selectedBrands.length === 0) {
                alert('Please select at least one brand to add.');
                return;
            }

            btnSave.disabled = true;
            saveSpinner.classList.remove('d-none');

            fetch(aiSaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    brands: selectedBrands,
                    targets: targetCats
                })
            })
            .then(res => res.json())
            .then(data => {
                btnSave.disabled = false;
                saveSpinner.classList.add('d-none');

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to save brands.');
                }
            })
            .catch(err => {
                btnSave.disabled = false;
                saveSpinner.classList.add('d-none');
                alert('Error saving brands: ' + err.message);
            });
        });

    })();
</script>
@endpush
