@extends('layouts.vendor.app')
@section('title', 'Radiology — Billing')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @php $fmt = fn($n) => \App\CentralLogics\Helpers::format_currency($n); $canAdd = hasPermission('radiology_billing', 'add'); @endphp
        <div class="layout-2col">
            <div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">💰</div> Radiology Billing — Generate Invoice</h3></div>
                    <div style="padding:14px">
                        <form method="get" class="mb-0">
                            <div class="fg"><label class="fl">Select Study</label>
                                <select class="fs" name="study" onchange="this.form.submit()">
                                    <option value="">Choose an un-invoiced study…</option>
                                    @foreach ($billable as $b)<option value="{{ $b->id }}" {{ $study && $study->id==$b->id?'selected':'' }}>{{ $b->study_no }} — {{ $b->patient->name ?? '' }} · {{ $b->study_name }} ({{ $fmt($b->price) }})</option>@endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                    @if ($study)
                        <form method="post" action="{{ route('vendor.radiology.studies.invoice', $study->id) }}">
                            @csrf
                            <div style="display:grid;grid-template-columns:1fr 80px 100px 110px;gap:8px;padding:8px 16px;font-size:10px;font-weight:700;color:var(--light);text-transform:uppercase;background:#F9FAFB;border-top:1px solid var(--border);border-bottom:1px solid var(--border)"><div>Study / Procedure</div><div style="text-align:center">Qty</div><div style="text-align:right">Rate</div><div style="text-align:right">Amount</div></div>
                            <div style="display:grid;grid-template-columns:1fr 80px 100px 110px;gap:8px;padding:9px 16px;border-bottom:1px solid #F3F4F6;font-size:12px"><div style="font-weight:500">{{ $study->study_name }} ({{ $study->modality }})</div><div style="text-align:center;color:var(--muted)">1</div><div style="text-align:right;color:var(--muted)">{{ $fmt($study->price) }}</div><div class="num" style="text-align:right;font-weight:700">{{ $fmt($study->price) }}</div></div>
                            <div style="padding:12px 16px;display:flex;flex-direction:column;gap:10px">
                                <div class="frow3">
                                    <div class="fg"><label class="fl">Radiologist Reading Fee</label><input class="fi" type="number" step="0.01" name="reading_fee" id="bReading" value="0" oninput="bRecalc()"></div>
                                    <div class="fg"><label class="fl">Insurance Provider</label><input class="fi" name="insurance_provider" placeholder="Self Pay / Star Health"></div>
                                    <div class="fg"><label class="fl">Insurance Covered</label><input class="fi" type="number" step="0.01" name="insurance_covered" id="bInsurance" value="0" oninput="bRecalc()"></div>
                                </div>
                                <div class="fg" style="max-width:200px"><label class="fl">Discount</label><input class="fi" type="number" step="0.01" name="discount" id="bDiscount" value="0" oninput="bRecalc()"></div>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:10px 16px;background:#F9FAFB;font-weight:700;font-size:13px"><span>Subtotal</span><span class="num" id="bSub" data-base="{{ $study->price }}">{{ $fmt($study->price) }}</span></div>
                            <div style="display:flex;justify-content:space-between;padding:14px 16px;background:var(--navy);color:#fff;font-weight:700;font-size:16px"><span>💳 Patient Payable</span><span class="num" id="bPayable">{{ $fmt($study->price) }}</span></div>
                            @if($canAdd)<div style="padding:14px 16px;text-align:right"><button class="btn btn-green">✓ Finalize Invoice</button></div>@endif
                        </form>
                    @else
                        <div class="empty">Select a study above to generate its invoice.</div>
                    @endif
                </div>
            </div>
            <div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">📊</div> Today's Revenue</h3></div>
                    <div class="stat-row"><span class="stat-l">Total Billed</span><span class="stat-v num">{{ $fmt($revenue['billed']) }}</span></div>
                    <div class="stat-row"><span class="stat-l">Insurance Covered</span><span class="stat-v b num">{{ $fmt($revenue['insured']) }}</span></div>
                    <div class="stat-row"><span class="stat-l">Cash / Self Pay</span><span class="stat-v g num">{{ $fmt($revenue['cash']) }}</span></div>
                    <div class="stat-row"><span class="stat-l">Invoices Today</span><span class="stat-v num">{{ $revenue['count'] }}</span></div>
                </div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltamber)">🧾</div> Recent Invoices</h3></div>
                    @forelse ($recent as $inv)
                        <div class="alert-row"><div style="flex:1"><div class="alert-title">{{ $inv->patient->name ?? '—' }} — {{ $inv->study->study_no ?? $inv->invoice_no }}</div><div class="alert-sub">{{ $inv->insurance_provider ?: 'Self Pay' }} · {{ $inv->created_at?->format('h:i A') }}</div></div><span class="num" style="color:var(--greenA);font-weight:700">{{ $fmt($inv->payable) }}</span></div>
                    @empty
                        <div class="empty" style="padding:20px">No invoices yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection

@push('script_2')
<script>
var sym="{{ \App\CentralLogics\Helpers::currency_symbol() ?? '₹' }}";
function bRecalc(){
  var base=parseFloat(document.getElementById('bSub').dataset.base||0);
  var read=parseFloat(document.getElementById('bReading').value||0);
  var ins=parseFloat(document.getElementById('bInsurance').value||0);
  var dis=parseFloat(document.getElementById('bDiscount').value||0);
  var sub=base+read;
  document.getElementById('bSub').textContent=sym+' '+sub.toFixed(2);
  document.getElementById('bPayable').textContent=sym+' '+Math.max(0,sub-ins-dis).toFixed(2);
}
</script>
@endpush
