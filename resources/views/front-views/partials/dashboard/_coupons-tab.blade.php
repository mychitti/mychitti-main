<style>
    .coupons-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
        padding: 20px;
    }

    .coupon-card {
        position: relative;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.2s;
    }

    .coupon-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .coupon-top {
        background: linear-gradient(135deg, #81c408 0%, #a6d54eff 100%);
        padding: 16px;
        position: relative;
    }

    .coupon-top::after {
        content: '';
        position: absolute;
        bottom: -6px;
        left: 0;
        right: 0;
        height: 12px;
        background: radial-gradient(circle at 6px, transparent 6px, white 6px);
        background-size: 12px 12px;
        background-position: 0 0;
    }

    .coupon-name {
        color: white;
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .coupon-code-box {
        background: white;
        color: #81c408;
        padding: 6px 12px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 16px;
        font-weight: 700;
        display: inline-block;
        letter-spacing: 1px;
    }

    .coupon-bottom {
        padding: 16px;
    }

    .coupon-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .info-item {
        text-align: center;
    }

    .info-label {
        font-size: 13px;
        color: #9ca3af;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 14px;
        color: #1f2937;
        font-weight: 600;
    }

    .discount-value {
        font-size: 20px;
        color: #81c408;
    }

    .coupon-meta {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #393e46ff;
        padding-top: 12px;
        border-top: 1px dashed #e5e7eb;
    }
</style>
<div class="tab-pane fade" id="v-pills-coupons" role="tabpanel" aria-labelledby="v-pills-coupons-tab">
    <div class="container tab_inner">

        <div class="af-container-p9x2">
            <h2>Coupons for you </h2>
<div class="row">
@if($coupons->isEmpty())
No coupons available at the moment.
@else 
            @foreach ($coupons as $coupon)
                <div class="coupon-card col-md-6 p-0">
                    <div class="coupon-top">
                        <div class="coupon-name">{{ ucfirst($coupon->title) }}</div>
                        <div class="coupon-code-box">{{ $coupon->code }}</div>
                    </div>

                    <div class="coupon-bottom">
                        <div class="coupon-info">
                            <div class="info-item">
                                <div class="info-label">Discount</div>
                                <div class="info-value discount-value">
                                    {{ $coupon->discount_type == 'amount' ? \App\CentralLogics\Helpers::currency_symbol() : '' }}{{ $coupon->discount }}{{ $coupon->discount_type == 'percent' ? '%' : '' }}
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Minimum Purchase</div>
                                <div class="info-value">{{ $coupon->min_purchase }}</div>
                            </div>
                            @if($coupon->discount_type == 'percent')
                            <div class="info-item">
                                <div class="info-label">Max Off</div>
                                <div class="info-value">{{ $coupon->max_discount }}</div>
                            </div>
                            @endif
                        </div>

                        <div class="coupon-meta">
                            <span>{{ date('M d', strtotime($coupon->start_date)) }} -
                                {{ date('M d', strtotime($coupon->expire_date)) }}</span>
                            <span>{{ $coupon->total_uses }}/{{ $coupon->limit }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
            @endif
</div>
        </div>
    </div>
</div>
