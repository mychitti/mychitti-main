@php
    $school = $store?->name ?? 'School';
    $issued = \Carbon\Carbon::parse($certificate->issue_date)->format('d/m/Y');
@endphp
<style>
    .mo-frame { border:1px solid #e5e7eb; font-family:"DejaVu Sans", Arial, sans-serif; color:#1f2937; }
    .mo-band { background:#4f46e5; color:#fff; padding:22px 36px; }
    .mo-band .name { font-size:25px; font-weight:800; letter-spacing:.3px; }
    .mo-band .addr { font-size:11px; color:#dfe1fb; margin-top:3px; }
    .mo-inner { padding:30px 40px 40px; }
    .mo-meta { font-size:12px; color:#6b7280; }
    .mo-title { font-size:21px; font-weight:800; color:#4f46e5; text-transform:uppercase; letter-spacing:1px; margin:14px 0 6px; }
    .mo-accent { width:64px; height:4px; background:#4f46e5; border-radius:2px; margin-bottom:20px; }
    .mo-body { font-size:15px; line-height:2.0; text-align:justify; color:#374151; }
    .mo-sign td { padding-top:4px; font-size:13px; text-align:center; color:#1f2937; }
</style>
<div class="mo-frame">
    <div class="mo-band">
        <table width="100%"><tr>
            <td>
                <div class="name">{{ $school }}</div>
                @if($branch ?? null)<div style="font-weight:700; font-size:12.5px; color:#eef2ff;">{{ $branch->name }}</div>@endif
                <div class="addr">
                    @php $addr = (($branch->address ?? null) ?: $store?->address); @endphp
                    @if($addr){{ $addr }}@endif
                    @if($store?->phone) · {{ $store->phone }}@endif @if($store?->email) · {{ $store->email }}@endif
                </div>
            </td>
            <td style="text-align:right; font-size:12px; color:#dfe1fb;">
                <div>Serial: {{ $certificate->serial_no }}</div>
                <div>Date: {{ $issued }}</div>
            </td>
        </tr></table>
    </div>

    <div class="mo-inner">
        <div class="mo-title">{{ $certificate->typeLabel() }}</div>
        <div class="mo-accent"></div>

        <div class="mo-body">{!! nl2br(e($certificate->body)) !!}</div>

        <table width="100%" class="mo-sign" style="margin-top:64px;"><tr>
            <td style="border-top:1px solid #9ca3af; width:40%;">Class Teacher</td>
            <td style="width:20%;"></td>
            <td style="border-top:1px solid #9ca3af; width:40%;">Principal / Authorised Signatory</td>
        </tr></table>
    </div>
</div>
