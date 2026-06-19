@extends('layouts.vendor.app')

@section('title', 'Branch Stock')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Branch Stock</h1>
                <div class="sub">Stock available at each branch</div>
            </div>
            <form method="get" class="rp-filter">
                <select name="branch" class="rp-input" onchange="this.form.submit()">
                    @forelse ($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @empty
                        <option value="">No branches</option>
                    @endforelse
                </select>
                <input type="text" name="q" value="{{ $search }}" class="rp-input" placeholder="Search item / SKU">
                <button class="rp-btn o">Search</button>
            </form>
        </div>

        @if (!$branchId)
            <div class="rp-card"><div class="rp-empty">Create a branch first (Branches &amp; Counters).</div></div>
        @else
            @php $canEditStock = hasPermission('pos_branch_stock', 'edit'); @endphp
            <form method="post" action="{{ route('vendor.retail-pos.branch-stock.save') }}">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <div class="rp-card">
                    <div class="hd">
                        <span class="accent">Stock at this branch</span>
                        @if ($canEditStock)
                            <button class="rp-btn p sm">Save Stock</button>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="rp-table">
                            <thead><tr><th>Item</th><th>SKU</th><th class="text-right">Store total</th><th width="170">Branch stock</th></tr></thead>
                            <tbody>
                                @forelse ($items as $it)
                                    <tr>
                                        <td><b>{{ $it->item_name }}</b></td>
                                        <td class="text-muted">{{ $it->sku_id }}</td>
                                        <td class="text-right text-muted">{{ rtrim(rtrim(number_format((float) $it->stock, 2), '0'), '.') }}</td>
                                        <td>
                                            <input type="number" step="0.001" name="stock[{{ $it->id }}]"
                                                value="{{ isset($branchStock[$it->id]) ? rtrim(rtrim(number_format((float) $branchStock[$it->id], 3, '.', ''), '0'), '.') : '' }}"
                                                class="rp-input" style="width:150px" placeholder="0" {{ $canEditStock ? '' : 'readonly' }}>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><div class="rp-empty">No products.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (count($items) && $canEditStock)
                        <div class="bd text-right"><button class="rp-btn p">Save Stock</button></div>
                    @endif
                </div>
            </form>
        @endif
    </div>
@endsection
