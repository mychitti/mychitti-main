@php
    $heroTitle    = $heroTitle    ?? 'Attendance & Leave';
    $heroSubtitle = $heroSubtitle ?? '';
    $heroBadge    = $heroBadge    ?? null;
    $heroIcon     = $heroIcon     ?? 'tio-event';
    $heroBackUrl  = $heroBackUrl  ?? route('vendor.attendance.all');
    $heroBackText = $heroBackText ?? 'Back to Attendance & Leave';
@endphp
<div style="background:linear-gradient(100deg,#1d4ed8 0%,#2563eb 45%,#4f46e5 100%);border-radius:14px;color:#fff;
            padding:20px 24px;box-shadow:0 8px 24px rgba(37,99,235,.22);margin-bottom:18px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:14px;">
        <div>
            <h1 style="color:#fff;font-weight:700;font-size:21px;margin:0;">
                <i class="{{ $heroIcon }} mr-2"></i> {{ $heroTitle }}
                @if ($heroBadge)
                    <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;font-size:13px;
                          font-weight:600;margin-left:8px;vertical-align:middle;">{{ $heroBadge }}</span>
                @endif
            </h1>
            @if ($heroSubtitle)
                <div style="color:rgba(255,255,255,.82);font-size:13px;margin-top:3px;">{{ $heroSubtitle }}</div>
            @endif
        </div>
        <a href="{{ $heroBackUrl }}" class="btn btn-light btn-sm" style="font-weight:600;">
            <i class="tio-arrow-backward mr-1"></i> {{ $heroBackText }}
        </a>
    </div>
</div>
