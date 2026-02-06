<style>
    .coupons-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
        padding: 20px;
    }

  
</style>
<div class="tab-pane fade show active" id="v-pills-coupons" role="tabpanel" aria-labelledby="v-pills-coupons-tab">
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
