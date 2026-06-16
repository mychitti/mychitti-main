@extends('layouts.vendor.app')
@section('title', 'Pharmacy — Banned / Blocked Items')

@push('css_or_js')
    <style>
        .pill { font-size:10px; font-weight:700; padding:3px 9px; border-radius:100px; }
        .pill.govt{background:#fee2e2;color:#b91c1c}.pill.store{background:#e0e7ff;color:#4338ca}
        .banned-empty { text-align:center; color:#9aa1ab; padding:40px 16px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        @include('hmis::vendor-views.partials._pharmacy_header')

        <div class="pharmacy-page-content">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
                    <h3 class="mb-0" style="font-size:15px; font-weight:700;">Banned / Blocked Medicines</h3>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        <form action="" method="get" class="input-group input-group-sm mb-0" style="max-width:240px;">
                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search banned item...">
                            <div class="input-group-append"><button class="btn btn-outline-secondary"><i class="tio-search"></i></button></div>
                        </form>
                        @if (hasPermission('pharmacy', 'edit'))
                            <button class="btn btn-sm btn--primary" style="white-space:nowrap; font-weight:600;" data-toggle="modal" data-target="#banItemModal">
                                <i class="tio-add mr-1"></i> Ban a Medicine
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Medicine</th><th>Source</th><th>Reason</th><th>Stock</th><th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($banned as $b)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $b->item_name }}</div>
                                            <div class="text-muted" style="font-size:11px;">{{ $b->brand }}{{ $b->sku_id ? ' · ' . $b->sku_id : '' }}</div>
                                        </td>
                                        <td><span class="pill {{ $b->banned_source ?: 'store' }}">{{ strtoupper($b->banned_source ?: 'store') }}</span></td>
                                        <td style="white-space:normal; max-width:360px;">{{ $b->banned_reason ?: '—' }}</td>
                                        <td><strong>{{ (int) $b->stock }}</strong></td>
                                        <td class="text-right">
                                            @if (hasPermission('pharmacy', 'edit'))
                                                <a class="btn btn-xs btn-outline-secondary" href="{{ route('vendor.pharmacy.banned-items.delete', $b->id) }}"
                                                    onclick="return confirm('Remove {{ $b->item_name }} from the banned list?')">Un-ban</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="banned-empty">
                                        <div class="mt-2">No banned/blocked medicines. Click <strong>Ban a Medicine</strong> to flag one from your pharmacy.</div>
                                    </div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (hasPermission('pharmacy', 'edit'))
        <div class="modal fade" id="banItemModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ route('vendor.pharmacy.banned-items.save') }}" method="post">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Ban / Block a Medicine</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            {{-- Path 1: select an existing medicine from the pharmacy --}}
                            <div class="form-group">
                                <label class="input-label">Select from pharmacy</label>
                                <select name="item_id" id="banExistingSelect" class="form-control">
                                    <option value="">— Choose an existing medicine —</option>
                                    @foreach ($available as $a)
                                        <option value="{{ $a->id }}">{{ $a->item_name }}{{ $a->brand ? ' · ' . $a->brand : '' }}{{ $a->sku_id ? ' (' . $a->sku_id . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-center text-muted my-2" style="font-size:11px; font-weight:700;">— OR ADD A NEW MEDICINE DIRECTLY —</div>

                            {{-- Path 2: add a brand-new medicine straight into inventory as banned --}}
                            <div id="banNewFields">
                                <div class="form-group">
                                    <label class="input-label">Medicine Name</label>
                                    <input type="text" name="item_name" id="banNewName" class="form-control" placeholder="e.g. Nimesulide">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-6"><label class="input-label">Brand</label><input type="text" name="brand" class="form-control"></div>
                                    <div class="form-group col-6"><label class="input-label">SKU / Code</label><input type="text" name="sku_id" class="form-control"></div>
                                </div>
                                <div class="form-group">
                                    <label class="input-label">Unit</label>
                                    <input type="text" name="unit" class="form-control" placeholder="Tablet, Strip, ml… (optional)">
                                </div>
                            </div>

                            <hr>
                            <div class="form-group">
                                <label class="input-label">Source</label>
                                <select name="source" class="form-control">
                                    <option value="store">Store-blocked</option>
                                    <option value="govt">Govt-banned</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="input-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="Optional — e.g. Banned by CDSCO, contraindicated..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white btn-sm" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn--primary btn-sm">Mark as Banned</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script_2')
    <script>
        (function () {
            const sel = document.getElementById('banExistingSelect');
            const newWrap = document.getElementById('banNewFields');
            const newName = document.getElementById('banNewName');
            if (!sel) return;
            const form = sel.closest('form');

            sel.addEventListener('change', function () {
                const picked = !!this.value;
                newWrap.style.opacity = picked ? '0.4' : '1';
                newWrap.querySelectorAll('input').forEach(i => i.disabled = picked);
            });

            form.addEventListener('submit', function (e) {
                if (!sel.value && !(newName.value || '').trim()) {
                    e.preventDefault();
                    alert('Select an existing medicine or enter a new medicine name.');
                }
            });
        })();
    </script>
@endpush
