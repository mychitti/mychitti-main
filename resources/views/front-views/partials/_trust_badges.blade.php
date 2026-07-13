@once
    <style>
        .mc-trust-badges { display:flex; flex-wrap:wrap; gap:4px; margin-top:4px; }
        .mc-trust-badge { font-size:11px; line-height:1; padding:3px 7px; border-radius:12px;
            background:#eef7ee; color:#2e7d32; white-space:nowrap; }
        .mc-trust-badge i { font-size:11px; }
        .mc-trust-gst { background:#e8f0fe; color:#1a56db; }
        .mc-trust-trusted { background:#fff4e5; color:#b45309; }
        .mc-trust-popular { background:#f3e8ff; color:#7e22ce; }
    </style>
@endonce 
@php $__badges = store_trust_badges($store); @endphp
@if(!empty($__badges))
    <div class="mc-trust-badges">
        @foreach($__badges as $__b)
            <span class="mc-trust-badge mc-trust-{{ $__b['key'] }}">
                <i class="tio-verified"></i> {{ $__b['label'] }}
            </span>
        @endforeach
    </div>
@endif
 