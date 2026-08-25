@extends('layouts.vendor.app')
@section('title', 'Treatment Catalog')

@push('css_or_js')
<style>
    /* The row is five editable columns wide; without a floor the table squeezes the
       treatment name down to two letters instead of letting the wrapper scroll. */
    .txcat-table { min-width: 620px; }

    @media (max-width: 767px) {
        .content.container-fluid { padding: 0.75rem; }
        .txcat-head { flex-wrap: wrap; gap: 8px; }
        .txcat-head form { width: 100%; }
        .txcat-search { max-width: 100% !important; width: 100%; }
        .txcat-table td .btn-xs { margin-bottom: 2px; }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-money" style="font-size:22px;"></i></span>
                Treatment Catalog
            </h1>
            <span class="text-muted" style="font-size:12px;">
                What each treatment costs. The amount box on a consultation opens with the price from here,
                and pricing a treatment during a consultation adds it to this list.
            </span>
        </div>
        <a href="{{ route('vendor.opd.terms') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-label"></i> Clinical Terms
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-header py-2 bg-light">
                    <h6 class="mb-0 font-weight-bold" style="font-size:13px">Add a treatment</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('vendor.opd.treatment-catalog.store') }}">
                        @csrf
                        <div class="form-group">
                            <label class="input-label" style="font-size:12px">Treatment <span class="text-danger">*</span></label>
                            {{-- Same list the consultation offers. Typing a name the list has never
                                 seen is allowed and adds it to the list for next time. --}}
                            <select name="term" id="catTermSelect" class="form-control form-control-sm" required>
                                <option value=""></option>
                                @foreach($treatmentOptions as $term)
                                    <option value="{{ $term }}">{{ $term }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pick from the list, or type a new one and press Enter.</small>
                        </div>
                        <div class="row">
                            <div class="col-6 form-group">
                                <label class="input-label" style="font-size:12px">Price ({{ \App\CentralLogics\Helpers::currency_symbol() ?: '₹' }}) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm" placeholder="0.00" required>
                            </div>
                            <div class="col-6 form-group">
                                <label class="input-label" style="font-size:12px">Usual discount</label>
                                <input type="number" name="discount" step="0.01" min="0" class="form-control form-control-sm" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="d-flex align-items-center mb-0" style="gap:6px; font-size:12px; font-weight:600;">
                                <input type="checkbox" name="is_active" value="1" checked> Offer this price
                            </label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="tio-add"></i> Add to catalog
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center txcat-head">
                    <h6 class="mb-0 font-weight-bold" style="font-size:13px">
                        Priced treatments <span class="text-muted">({{ $treatments->count() }})</span>
                    </h6>
                    <form method="get" class="mb-0">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm txcat-search"
                               style="max-width:220px" placeholder="Search treatment...">
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-align-middle mb-0 txcat-table" style="font-size:13px">
                        <thead class="bg-light">
                            <tr>
                                <th>Treatment</th>
                                <th style="width:130px">Price</th>
                                <th style="width:130px">Usual discount</th>
                                <th style="width:90px">Offered</th>
                                <th style="width:120px"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($treatments as $row)
                            {{-- The row's form lives in a cell and the inputs point at it by id:
                                 a <form> wrapping <td>s is invalid, and the browser hoists it out
                                 of the table, leaving the row unable to submit. --}}
                            <tr>
                                <td>
                                    <form id="txcat{{ $row->id }}" method="post" action="{{ route('vendor.opd.treatment-catalog.update', $row->id) }}">@csrf</form>
                                    <input type="text" name="term" form="txcat{{ $row->id }}" value="{{ $row->term }}" class="form-control form-control-sm" required>
                                </td>
                                <td>
                                    <input type="number" name="amount" form="txcat{{ $row->id }}" step="0.01" min="0" value="{{ $row->amount }}" class="form-control form-control-sm" required>
                                </td>
                                <td>
                                    <input type="number" name="discount" form="txcat{{ $row->id }}" step="0.01" min="0" value="{{ $row->discount }}" class="form-control form-control-sm" placeholder="—">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="is_active" form="txcat{{ $row->id }}" value="1" @if($row->is_active) checked @endif>
                                </td>
                                <td class="text-right">
                                    <button type="submit" form="txcat{{ $row->id }}" class="btn btn-xs btn-primary">Save</button>
                                    <a href="{{ route('vendor.opd.treatment-catalog.delete', $row->id) }}"
                                       class="btn btn-xs btn-outline-danger"
                                       onclick="return confirm('Remove {{ addslashes($row->term) }} from the catalog? Treatments already priced on a visit keep their amount.')">
                                        <i class="tio-delete-outlined"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Nothing priced yet. Add a treatment on the left, or put an amount against one
                                    during a consultation and it will appear here.
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

@push('script_2')
<script>
    $(function () {
        // tags:true so a treatment this hospital has never recorded can still be priced; the
        // controller remembers it as a clinical term, so the consultation box offers it after.
        $('#catTermSelect').select2({
            tags: true,
            width: '100%',
            placeholder: 'Select or type a treatment…'
        });
    });
</script>
@endpush
