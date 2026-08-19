@extends('layouts.vendor.app')

@section('title', 'Offers')

@push('css_or_js')
    <style>
        .offer-preview-card {
            border: 1px solid #e7eaf3;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .05);
        }
        .offer-banner {
            position: relative;
            min-height: 84px;
            background: linear-gradient(135deg, #1e3a8a, #0b1e54);
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            padding: 10px;
        }
        .offer-headline { font-size: 17px; font-weight: 800; line-height: 1.05; letter-spacing: .3px; }
        .offer-headline small { display: block; font-size: 12px; font-weight: 700; }
        .offer-banner-sub { font-size: 10px; opacity: .85; margin-top: 3px; }
        .offer-ribbon {
            position: absolute; top: 6px; right: 6px; background: #f97316; color: #fff;
            font-size: 7px; font-weight: 700; padding: 2px 5px; border-radius: 3px; transform: rotate(3deg);
        }
        .offer-preview-card .card-body { padding: 10px 12px; }
        .offer-summary-title { color: #1e40af; font-weight: 700; font-size: 12.5px; margin: 0; }
        .offer-summary dl { margin: 0; }
        .offer-summary .row-line {
            display: flex; justify-content: space-between; gap: 10px;
            padding: 3px 0; border-bottom: 1px dashed #f0f1f5; font-size: 11px;
        }
        .offer-summary .row-line:last-child { border-bottom: 0; }
        .offer-summary .k { color: #8a93a6; }
        .offer-summary .v { font-weight: 600; text-align: right; }
        .offer-day-chip {
            display: inline-block; background: #eef2ff; color: #1e40af;
            font-size: 9px; padding: 1px 5px; border-radius: 3px; margin: 1px; text-transform: capitalize;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap">
            <h1 class="page-header-title mb-0"><i class="tio-gift"></i> Offers</h1>
            <div class="d-flex" style="gap:8px;">
                @if (request('item_id'))
                    <a href="{{ route('vendor.retail-pos.offer.index') }}" class="btn btn-outline-secondary btn_sm">All Offers</a>
                @endif
                <a href="{{ route('vendor.retail-pos.offer.create', request('item_id') ? [request('item_id')] : []) }}"
                    class="btn btn-primary btn_sm"><i class="tio-add"></i> Create Offer</a>
            </div>
        </div>

        <div class="row">
            @forelse ($offers as $offer)
                @php
                    $days = (array) $offer->applicable_days;
                    $buyId = $offer->buy_product_ids[0] ?? $offer->item_id;
                    $buyName = $itemNames[$buyId] ?? ($offer->item->item_name ?? 'Product');
                    $isFree = in_array($offer->offer_type, ['buy_x_get_y_free', 'bundle_deal']) && ($offer->reward_type ?? 'free_product') === 'free_product';
                    $rewardName = $offer->reward_product_id
                        ? ($itemNames[$offer->reward_product_id] ?? ($offer->rewardProduct->item_name ?? 'item'))
                        : $buyName;
                    $rv = rtrim(rtrim((string) $offer->reward_value, '0'), '.');
                    if ($offer->offer_type === 'percent_discount') {
                        $headline = $rv . '% OFF';
                        $getText = $rv . '% off';
                    } elseif ($offer->offer_type === 'flat_discount') {
                        $headline = '₹' . $rv . ' OFF';
                        $getText = '₹' . $rv . ' off';
                    } elseif ($isFree) {
                        $headline = 'BUY ' . (int) $offer->buy_quantity . ' GET ' . (int) $offer->free_quantity;
                        $getText = (int) $offer->free_quantity . ' × ' . $rewardName . ' FREE';
                    } else {
                        $headline = 'BUY ' . (int) $offer->buy_quantity;
                        $getText = ($offer->reward_type === 'discount_percent' ? $rv . '% off' : '₹' . $rv . ' off');
                    }
                    $branchText = $offer->all_branches
                        ? 'All Branches'
                        : collect((array) $offer->branch_ids)->map(fn($b) => $branchNames[$b] ?? null)->filter()->implode(', ');
                @endphp
                <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                    <div class="offer-preview-card">
                        <div class="offer-banner"
                            @if ($offer->banner) style="background-image:linear-gradient(rgba(11,30,84,.55),rgba(11,30,84,.55)),url('{{ asset('storage/app/public/offer/' . $offer->banner) }}');" @endif>
                            <span class="offer-ribbon">LIMITED TIME OFFER!</span>
                            <div>
                                <div class="offer-headline">{{ $headline }}
                                    @if ($isFree)
                                        <small>FREE</small>
                                    @endif
                                </div>
                                <div class="offer-banner-sub">{{ $buyName }}</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="offer-summary-title">{{ $offer->offer_name }}
                                    <small class="text-muted">({{ $offer->offer_code }})</small>
                                </h5>
                                @if ($offer->status === 'published')
                                    <span class="badge badge-soft-success">Published</span>
                                @else
                                    <span class="badge badge-soft-secondary">Draft</span>
                                @endif
                            </div>

                            <div class="offer-summary">
                                <div class="row-line"><span class="k">Buy (X)</span><span class="v">{{ (int) $offer->buy_quantity }} × {{ $buyName }}</span></div>
                                <div class="row-line"><span class="k">Get (Y)</span><span class="v">{{ $getText }}</span></div>
                                <div class="row-line"><span class="k">Offer Type</span><span class="v">{{ ucwords(str_replace('_', ' ', $offer->offer_type)) }}</span></div>
                                <div class="row-line"><span class="k">Valid From</span><span class="v">{{ $offer->start_date }} {{ $offer->start_time }}</span></div>
                                <div class="row-line"><span class="k">Valid To</span><span class="v">
                                    @if ($offer->run_until_stock_out ?? false)
                                        Until stock runs out
                                        <small class="text-muted d-block">(listed end {{ $offer->end_date }} is not enforced)</small>
                                    @else
                                        {{ $offer->end_date }} {{ $offer->end_time }}
                                    @endif
                                </span></div>
                                <div class="row-line"><span class="k">Days</span><span class="v">
                                    @forelse ($days as $d)
                                        <span class="offer-day-chip">{{ $d }}</span>
                                    @empty
                                        Every day
                                    @endforelse
                                </span></div>
                                <div class="row-line"><span class="k">Branches</span><span class="v">{{ $branchText ?: '—' }}</span></div>
                                <div class="row-line"><span class="k">Customers</span><span class="v">{{ ucwords(str_replace('_', ' ', $offer->customer_type ?? 'all_customers')) }}</span></div>
                                <div class="row-line"><span class="k">Min. Bill Value</span><span class="v">₹{{ (float) $offer->min_bill_value }}</span></div>
                                <div class="row-line"><span class="k">Max Offer Value</span><span class="v">₹{{ (float) $offer->max_offer_value }}</span></div>
                                <div class="row-line"><span class="k">Max Free Qty Per Bill</span><span class="v">{{ $offer->max_free_qty_per_bill ?: '—' }}</span></div>
                                <div class="row-line"><span class="k">Total Campaign Limit</span><span class="v">{{ $offer->total_campaign_limit ? $offer->total_campaign_limit . ' Uses' : 'Unlimited' }}</span></div>
                            </div>

                            <div class="text-right mt-3">
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('vendor.retail-pos.offer.edit', [$offer->id]) }}" title="Edit">
                                    <i class="tio-edit"></i> Edit
                                </a>
                                <a class="btn btn-sm btn-outline-danger form-alert" href="javascript:;"
                                    data-id="offer-{{ $offer->id }}"
                                    data-message="{{ translate('Want to delete this offer') }}" title="Delete">
                                    <i class="tio-delete-outlined"></i> Delete
                                </a>
                                <form action="{{ route('vendor.retail-pos.offer.delete', [$offer->id]) }}" method="get"
                                    id="offer-{{ $offer->id }}"></form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-center text-muted py-5">No offers created yet.</div></div>
                </div>
            @endforelse
        </div>

        <div class="d-flex justify-content-end">
            {!! $offers->links() !!}
        </div>
    </div>
@endsection
