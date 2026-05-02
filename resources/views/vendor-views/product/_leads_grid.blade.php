@php $statusCounts = ['new' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0, 'missed' => 0]; @endphp

@if (hasPermission('leads_manage', 'list'))
    @if (count($product) === 0)
        <div class="lp-empty">
            <div class="icon">🔍</div>
            <h4>No leads found</h4>
            <p>Try adjusting your filters.</p>
        </div>
    @else
        <div class="lp-grid">
            @foreach ($product as $key => $lead)
                @include('vendor-views.product._lead_card')
            @endforeach
        </div>
    @endif
@endif

{{-- Pass updated count to parent page --}}
<script>
    if (typeof updateLeadCount === 'function') updateLeadCount({{ count($product) }});
</script>
