@php
    // Shared HR page header — same blue "HR Management" banner + tab navbar on every HR page.
    // Pass an optional $heroSubtitle for page context (e.g. the employee name on a manage page).
    $heroSubtitle = $heroSubtitle ?? 'Staff, attendance, leave, salary, shifts and reports — all in one place.';
@endphp
<style>
    .hrhero {
        background: linear-gradient(100deg, #1d4ed8 0%, #2563eb 45%, #4f46e5 100%);
        color: #fff; padding: 20px 26px; border-radius: 14px; margin-bottom: 16px;
        box-shadow: 0 8px 24px rgba(37, 99, 235, .22);
    }
    .hrhero h1 { color: #fff; font-weight: 700; font-size: 22px; margin: 0; }
    .hrhero .hrhero-sub { color: rgba(255, 255, 255, .82); font-size: 13px; margin-top: 3px; }
</style>
<div class="hrhero">
    <h1><i class="tio-user-outlined mr-2"></i> HR Management</h1>
    <div class="hrhero-sub">{{ $heroSubtitle }}</div>
</div>
@include('vendor-views.partials._hr_nav')
