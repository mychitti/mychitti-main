@php
    $school = $store?->name ?? 'School';
    $issued = \Carbon\Carbon::parse($certificate->issue_date)->format('d/m/Y');
@endphp
<style>
    .cl-frame { border:3px double #1f2937; padding:34px 40px; font-family:"DejaVu Serif", Georgia, serif; color:#1a1a1a; }
    .cl-head { text-align:center; border-bottom:2px solid #1f2937; padding-bottom:12px; }
    .cl-head .name { font-size:26px; font-weight:800; letter-spacing:.5px; }
    .cl-head .addr { font-size:12px; color:#374151; margin-top:3px; }
    .cl-meta { font-size:12px; color:#374151; margin:14px 2px 0; }
    .cl-title { text-align:center; font-size:19px; font-weight:800; text-transform:uppercase; letter-spacing:1px; margin:18px 0 22px; text-decoration:underline; }
    .cl-body { font-size:15px; line-height:2.0; text-align:justify; margin:0 6px; }
    .cl-sign td { padding-top:4px; font-size:13px; text-align:center; }
</style>
<div class="cl-frame">
    <div class="cl-head">
        <div class="name">{{ $school }}</div>
        @if($branch ?? null)<div style="font-weight:700; font-size:13px;">{{ $branch->name }}</div>@endif
        <div class="addr">
            @php $addr = (($branch->address ?? null) ?: $store?->address); @endphp
            @if($addr){{ $addr }}<br>@endif
            @if($store?->phone)Phone: {{ $store->phone }}@endif @if($store?->email) · {{ $store->email }}@endif
        </div>
    </div>

    <table width="100%" class="cl-meta"><tr>
        <td style="text-align:left;"><b>Serial No:</b> {{ $certificate->serial_no }}</td>
        <td style="text-align:right;"><b>Date:</b> {{ $issued }}</td>
    </tr></table>

    <div class="cl-title">{{ $certificate->typeLabel() }}</div>

    <div class="cl-body">{!! nl2br(e($certificate->body)) !!}</div>

    <table width="100%" class="cl-sign" style="margin-top:64px;"><tr>
        <td style="border-top:1px solid #1f2937; width:40%;">Class Teacher</td>
        <td style="width:20%;"></td>
        <td style="border-top:1px solid #1f2937; width:40%;">Principal / Authorised Signatory</td>
    </tr></table>
</div>
