@php
    $sbt = null; $sslug = null;
    if (isset($store)) {
        $sbt   = is_array($store) ? ($store['business_type'] ?? null) : ($store->business_type ?? null);
        $sslug = is_array($store) ? ($store['slug'] ?? null) : ($store->slug ?? null);
    }
@endphp
@if($sbt && strtolower($sbt) === 'school' && $sslug && !request()->is('*/admission'))
    {{-- <a href="{{ url()->current() }}/admission" class="school-adm-cta">
        <i class="fas fa-user-graduate"></i> Apply for Admission
    </a> --}}
    <style>
        .school-adm-cta{
            position:fixed; right:20px; bottom:24px; z-index:1050;
            display:inline-flex; align-items:center; gap:8px;
            padding:13px 20px; border-radius:40px; text-decoration:none;
            background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff !important;
            font-weight:600; font-size:14px; box-shadow:0 8px 22px rgba(79,70,229,.4);
            transition:.18s;
        }
        .school-adm-cta:hover{ transform:translateY(-2px); box-shadow:0 12px 28px rgba(79,70,229,.5); color:#fff; }
        @media (max-width:480px){ .school-adm-cta{ right:14px; bottom:16px; padding:11px 16px; font-size:13px; } }
    </style>
@endif
