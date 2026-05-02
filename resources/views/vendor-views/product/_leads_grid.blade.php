@php
    $statusCounts = ['new' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0, 'missed' => 0];
@endphp

@if (hasPermission('leads_manage', 'list'))
    @if (count($product) === 0)
        <div class="lp-empty">
            <div class="icon">🔍</div>
            <h4>No leads found</h4>
            <p>Try adjusting your filters.</p>
        </div>
    @else
        <div class="lp-grid">
            @foreach ($product as $lead)
                @php
                    $needsAccept = $lead->status === 'new'
                        && (!isset($lead->additional_status) || $lead->additional_status !== 'missed')
                        && !_acceptedReq($lead->id)
                        && hasPermission('leads_manage', 'accept');
                @endphp
                @if ($needsAccept)
                    <div class="lp-new-outer">
                        <div class="lp-new-tag">
                            <span class="lp-pulse"></span> New — Needs Acceptance
                        </div>
                        @include('vendor-views.product._lead_card')
                    </div>
                @else
                    @include('vendor-views.product._lead_card')
                @endif
            @endforeach
        </div>
    @endif
@endif

<script>
    if (typeof updateLeadCount === 'function') updateLeadCount({{ count($product) }});
</script>

<style>
    /* Outer wrapper becomes the grid item, contains tag + card */
    .lp-new-outer {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    /* Override overflow:hidden on #lead-wrap- inside the highlight outer */
    .lp-new-outer [id^="lead-wrap-"] {
        overflow: visible;
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    /* Tag strip */
    .lp-new-tag {
        background: #fff7e6;
        color: #92680a;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: 5px 14px;
        border-radius: 10px 10px 0 0;
        border: 2px solid #f5a623;
        border-bottom: none;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    /* Card inside the highlighted outer */
    .lp-new-outer .lead-card {
        border: 2px solid #f5a623 !important;
        border-top: none !important;
        border-radius: 0 0 12px 12px !important;
        height: 100%;
    }
    .lp-pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #f5a623;
        flex-shrink: 0;
        position: relative;
        display: inline-block;
    }
    .lp-pulse::after {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: 50%;
        border: 2px solid #f5a623;
        animation: lp-pulse 1.6s ease-out infinite;
        opacity: 0;
    }
    @keyframes lp-pulse {
        0%   { transform: scale(.5); opacity: .9; }
        100% { transform: scale(2);  opacity: 0;  }
    }
</style>
