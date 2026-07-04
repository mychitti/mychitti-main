<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pay Slip — {{ $salary->salary_month }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 0; padding: 24px; }
        .ps-wrap { max-width: 760px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 24px; }
        .ps-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f3460; padding-bottom: 12px; }
        .ps-store h2 { margin: 0; font-size: 20px; color: #0f3460; }
        .ps-store small { color: #666; }
        .ps-logo img { width: 70px; }
        .ps-title { text-align: center; font-size: 18px; font-weight: 700; margin: 16px 0 4px; color: #0f3460; letter-spacing: .5px; }
        .ps-sub { text-align: center; color: #666; font-size: 13px; margin-bottom: 16px; }
        .ps-meta { display: flex; flex-wrap: wrap; gap: 6px 32px; font-size: 13px; margin-bottom: 16px; }
        .ps-meta div b { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        .ps-cols { display: flex; gap: 16px; }
        .ps-cols > div { flex: 1; }
        .ps-cols h4 { margin: 0 0 6px; font-size: 13px; color: #0f3460; }
        .ps-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dashed #eee; font-size: 13px; }
        .ps-tot { display: flex; justify-content: space-between; padding: 8px 0; font-weight: 700; border-top: 2px solid #ddd; margin-top: 6px; }
        .ps-net { background: #eef3ff; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #0f3460; margin-top: 16px; }
        .ps-actions { max-width: 760px; margin: 14px auto 0; text-align: right; }
        .ps-actions button { background: #0f3460; color: #fff; border: 0; border-radius: 6px; padding: 8px 18px; cursor: pointer; }
        @media print { .ps-actions { display: none; } body { padding: 0; } .ps-wrap { border: 0; } }
    </style>
</head>
@php
    $emp = $salary->employee;
    $empName = $emp ? trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? '')) : 'Employee';
    $allow = json_decode($salary->allowance ?? '[]', true) ?: [];
    $gross = (float) $salary->payable_salary + (float) $salary->allowance_amount + (float) $salary->bonus_incentives;
    $fmt = fn($n) => '₹ ' . number_format((float) $n, 2);
@endphp
<body onload="window.print()">
    <div class="ps-wrap">
        <div class="ps-head">
            <div class="ps-store">
                <h2>{{ $store->name ?? 'Store' }}</h2>
                <small>{{ $store->address ?? '' }}</small><br>
                @if (!empty($store->gst_number))<small>GST: {{ $store->gst_number }}</small>@endif
            </div>
            @if (!empty($store->logo))
                <div class="ps-logo"><img src="{{ asset('storage/app/public/store/' . $store->logo) }}" alt=""></div>
            @endif
        </div>

        <div class="ps-title">PAY SLIP</div>
        <div class="ps-sub">For {{ \Carbon\Carbon::parse(($salary->salary_month ?? now()) . '-01')->format('F Y') }}</div>

        <div class="ps-meta">
            <div><b>Employee:</b> {{ $empName }}</div>
            <div><b>Designation:</b> {{ $emp->designation ?? '—' }}</div>
            <div><b>Pay Month:</b> {{ $salary->salary_month }}</div>
            <div><b>Base Salary:</b> {{ $fmt($salary->base_salary) }}</div>
        </div>

        <div class="ps-cols">
            <div>
                <h4>Earnings</h4>
                <div class="ps-row"><span>Payable Salary</span><span>{{ $fmt($salary->payable_salary) }}</span></div>
                @foreach ($allow as $a)
                    <div class="ps-row"><span>{{ $a['title'] ?? 'Allowance' }}</span><span>{{ $fmt($a['amount'] ?? 0) }}</span></div>
                @endforeach
                @if ((float) $salary->bonus_incentives > 0)
                    <div class="ps-row"><span>Bonus / Incentives</span><span>{{ $fmt($salary->bonus_incentives) }}</span></div>
                @endif
                <div class="ps-tot"><span>Gross Earnings</span><span>{{ $fmt($gross) }}</span></div>
            </div>
            <div>
                <h4>Deductions</h4>
                @if ((float) $salary->epf > 0)<div class="ps-row"><span>EPF (Provident Fund)</span><span>{{ $fmt($salary->epf) }}</span></div>@endif
                @if ((float) $salary->esi > 0)<div class="ps-row"><span>ESI</span><span>{{ $fmt($salary->esi) }}</span></div>@endif
                @if ((float) $salary->professional_tax > 0)<div class="ps-row"><span>Professional Tax</span><span>{{ $fmt($salary->professional_tax) }}</span></div>@endif
                @if ((float) $salary->tds > 0)<div class="ps-row"><span>TDS (Income Tax)</span><span>{{ $fmt($salary->tds) }}</span></div>@endif
                @if ((float) $salary->deductions > 0)<div class="ps-row"><span>Other Deductions</span><span>{{ $fmt($salary->deductions) }}</span></div>@endif
                @if ((float) $salary->advance_payment_deductions > 0)<div class="ps-row"><span>Advance Recovery</span><span>{{ $fmt($salary->advance_payment_deductions) }}</span></div>@endif
                @php $totDed = (float) $salary->epf + (float) $salary->esi + (float) $salary->professional_tax + (float) $salary->tds + (float) $salary->deductions + (float) $salary->advance_payment_deductions; @endphp
                <div class="ps-tot"><span>Total Deductions</span><span>{{ $fmt($totDed) }}</span></div>
            </div>
        </div>

        <div class="ps-net"><span>Net Pay</span><span>{{ $fmt($salary->total_payable) }}</span></div>
        <p style="text-align:center;color:#999;font-size:11px;margin-top:18px;">This is a system-generated pay slip and does not require a signature.</p>
    </div>
    <div class="ps-actions"><button onclick="window.print()">Print</button></div>
</body>
</html>
