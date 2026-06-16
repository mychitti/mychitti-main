@php
    $school = $store?->name ?? 'School';
    $issued = \Carbon\Carbon::parse($certificate->issue_date)->format('d/m/Y');
@endphp
<style>
    .el-outer { border:2px solid #b8860b; padding:6px; background:#fffdf6; }
    .el-frame { border:1px solid #b8860b; padding:36px 44px; font-family:"DejaVu Serif", Georgia, serif; color:#2b2b2b; }
    .el-flourish { text-align:center; color:#b8860b; font-size:22px; letter-spacing:6px; margin-bottom:4px; }
    .el-head { text-align:center; }
    .el-head .name { font-size:28px; font-weight:800; color:#7a5c08; letter-spacing:1px; }
    .el-rule { border:0; border-top:1px solid #b8860b; width:60%; margin:8px auto 4px; }
    .el-head .addr { font-size:12px; color:#6b6357; }
    .el-title { text-align:center; font-size:20px; font-weight:800; color:#7a5c08; text-transform:uppercase; letter-spacing:3px; margin:22px 0; }
    .el-title-sub { display:block; font-size:11px; letter-spacing:2px; color:#b8860b; margin-top:4px; }
    .el-meta { font-size:12px; color:#6b6357; margin:0 2px 6px; }
    .el-body { font-size:15px; line-height:2.05; text-align:justify; margin:0 10px; }
    .el-sign td { padding-top:4px; font-size:13px; text-align:center; color:#2b2b2b; }
    .el-seal { color:#b8860b; font-size:12px; text-align:center; letter-spacing:2px; margin-top:10px; }
</style>
<div class="el-outer"><div class="el-frame">
    <div class="el-flourish">&#10086; &mdash;&mdash;&mdash; &#10087;</div>
    <div class="el-head">
        <div class="name">{{ $school }}</div>
        @if($branch ?? null)<div style="font-weight:700; color:#7a5c08; font-size:13px;">{{ $branch->name }}</div>@endif
        <hr class="el-rule">
        <div class="addr">
            @php $addr = (($branch->address ?? null) ?: $store?->address); @endphp
            @if($addr){{ $addr }} @endif
            @if($store?->phone) · Phone: {{ $store->phone }}@endif
        </div>
    </div>

    <div class="el-title">{{ $certificate->typeLabel() }}<span class="el-title-sub">&#10148; awarded with honour &#10148;</span></div>

    <table width="100%" class="el-meta"><tr>
        <td style="text-align:left;"><b>Serial No:</b> {{ $certificate->serial_no }}</td>
        <td style="text-align:right;"><b>Date:</b> {{ $issued }}</td>
    </tr></table>

    <div class="el-body">{!! nl2br(e($certificate->body)) !!}</div>

    <table width="100%" class="el-sign" style="margin-top:60px;"><tr>
        <td style="border-top:1px solid #b8860b; width:40%;">Class Teacher</td>
        <td style="width:20%;"></td>
        <td style="border-top:1px solid #b8860b; width:40%;">Principal / Authorised Signatory</td>
    </tr></table>

    <div class="el-seal">&#9733; &nbsp; OFFICIAL SEAL &nbsp; &#9733;</div>
</div></div>
