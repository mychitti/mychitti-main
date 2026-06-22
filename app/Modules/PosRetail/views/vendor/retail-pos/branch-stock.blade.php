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
                <div class="sub">Stock available at each branch — added only via Stock Transfer (Gatepass)</div>
            </div>
            @if (auth('vendor')->check() || hasPermission('pos_branch_stock', 'edit'))
                <a href="{{ route('vendor.retail-pos.gatepass') }}" class="rp-btn p">➕ Transfer Stock</a>
            @endif
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
            <div class="rp-card">
                <div class="hd">
                    <span class="accent">Stock at this branch</span>
                </div>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead><tr><th>Item</th><th>SKU</th><th class="text-right">Store total</th><th class="text-right" width="160">Branch stock</th></tr></thead>
                        <tbody>
                            @forelse ($items as $it)
                                <tr>
                                    <td><b>{{ $it->item_name }}</b></td>
                                    <td class="text-muted">{{ $it->sku_id }}</td>
                                    <td class="text-right text-muted">{{ rtrim(rtrim(number_format((float) $it->stock, 2), '0'), '.') }}</td>
                                    <td class="text-right"><b>{{ isset($branchStock[$it->id]) ? rtrim(rtrim(number_format((float) $branchStock[$it->id], 3, '.', ''), '0'), '.') : '0' }}</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="rp-empty">No products.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
