@extends('layouts.vendor.app')
@section('title', 'Laboratory — Lab Billing')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        @php $fmt = fn($n) => \App\CentralLogics\Helpers::format_currency($n); @endphp
        <div class="layout-2col">
            <div>
                <div class="lcard">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">💰</div> Lab Billing — Generate Invoice</h3></div>
                    <div style="padding:14px">
                        <form method="get" class="frow2 mb-0">
                            <div class="fg"><label class="fl">Select Lab Order</label>
                                <select class="fs" name="order" onchange="this.form.submit()">
                                    <option value="">Choose an un-invoiced order...</option>
                                    @foreach ($billable as $b)<option value="{{ $b->id }}" {{ $order && $order->id == $b->id ? 'selected' : '' }}>{{ $b->order_no }} — {{ $b->patient->name ?? '' }} ({{ $fmt($b->total_amount) }})</option>@endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    @if ($order)
                        @php $sub = $order->items->sum('price'); @endphp
                        <form method="post" action="{{ route('vendor.lab.orders.invoice', $order->id) }}">
                            @csrf
                            <div style="display:grid;grid-template-columns:1fr 80px 100px 110px;gap:8px;padding:8px 16px;font-size:10px;font-weight:700;color:var(--light);text-transform:uppercase;background:#F9FAFB;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
                                <div>Test Name</div><div style="text-align:center">Qty</div><div style="text-align:right">Rate</div><div style="text-align:right">Amount</div>
                            </div>
                            @foreach ($order->items as $it)
                                <div style="display:grid;grid-template-columns:1fr 80px 100px 110px;gap:8px;padding:9px 16px;border-bottom:1px solid #F3F4F6;font-size:12px">
                                    <div style="font-weight:500">{{ $it->test_name }}</div><div style="text-align:center;color:var(--muted)">1</div>
                                    <div style="text-align:right;color:var(--muted)">{{ $fmt($it->price) }}</div><div class="num" style="text-align:right;font-weight:700">{{ $fmt($it->price) }}</div>
                                </div>
                            @endforeach
                            <div style="padding:14px 16px;display:flex;flex-direction:column;gap:10px">
                                <div class="frow3">
                                    <div class="fg"><label class="fl">Insurance Provider</label><input class="fi" name="insurance_provider" placeholder="e.g. Star Health / Self Pay"></div>
                                    <div class="fg"><label class="fl">Insurance Covered ({{ \App\CentralLogics\Helpers::currency_symbol() ?? '₹' }})</label><input class="fi" type="number" step="0.01" name="insurance_covered" id="bInsurance" value="0" oninput="bRecalc()"></div>
                                    <div class="fg"><label class="fl">Discount</label><input class="fi" type="number" step="0.01" name="discount" id="bDiscount" value="0" oninput="bRecalc()"></div>
                                </div>
                                <div class="frow2">
                                    <div class="fg"><label class="fl">Payment Mode</label>
                                        <select class="fs" name="payment_mode" id="bPayMode" onchange="bSyncTxn()">
                                            @foreach (['Cash','Online','Card','UPI'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="fg" id="bTxnWrap" style="display:none"><label class="fl">Transaction ID *</label><input class="fi" name="transaction_id" id="bTxnId" placeholder="UPI / card / online ref"></div>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:10px 16px;background:#F9FAFB;font-weight:700;font-size:13px"><span>Subtotal</span><span class="num" id="bSub" data-sub="{{ $sub }}">{{ $fmt($sub) }}</span></div>
                            <div style="display:flex;justify-content:space-between;padding:14px 16px;background:var(--navy);color:#fff;font-weight:700;font-size:16px"><span>💳 Patient Payable</span><span class="num" id="bPayable">{{ $fmt($sub) }}</span></div>
                            @if (hasPermission('lab_billing', 'add'))<div style="padding:14px 16px;text-align:right"><button class="btn btn-green">✓ Finalize Invoice</button></div>@endif
                        </form>
                    @else
                        <div class="empty">Select an order above to generate its invoice.</div>
                    @endif
                </div>
            </div>
            <div>
                <div class="lcard">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">📊</div> Today's Lab Revenue</h3></div>
                    <div class="stat-row"><span class="stat-l">Total Billed</span><span class="stat-v num">{{ $fmt($revenue['billed']) }}</span></div>
                    <div class="stat-row"><span class="stat-l">Insurance Covered</span><span class="stat-v b num">{{ $fmt($revenue['insured']) }}</span></div>
                    <div class="stat-row"><span class="stat-l">Cash / Self Pay</span><span class="stat-v g num">{{ $fmt($revenue['cash']) }}</span></div>
                    <div class="stat-row"><span class="stat-l">Invoices Today</span><span class="stat-v num">{{ $revenue['count'] }}</span></div>
                </div>
                <div class="lcard">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltamber)">🧾</div> Recent Lab Invoices</h3></div>
                    @forelse ($recent as $inv)
                        <div class="alert-row"><div style="flex:1"><div class="alert-title">{{ $inv->patient->name ?? '—' }} — {{ $inv->order->order_no ?? $inv->invoice_no }}</div><div class="alert-sub">{{ $inv->insurance_provider ?: 'Self Pay' }} · {{ $inv->created_at?->format('h:i A') }}</div></div><div style="text-align:right"><span class="num" style="color:var(--greenA);font-weight:700">{{ $fmt($inv->payable) }}</span>@if ($inv->invoice_no)<div><a href="{{ route('vendor.lab.invoices.view', $inv->invoice_no) }}" target="_blank" class="btn btn-outline btn-xs" style="margin-top:4px">View</a></div>@endif</div></div>
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
  var sub=parseFloat(document.getElementById('bSub').dataset.sub||0);
  var ins=parseFloat(document.getElementById('bInsurance').value||0);
  var dis=parseFloat(document.getElementById('bDiscount').value||0);
  var pay=Math.max(0,sub-ins-dis);
  document.getElementById('bPayable').textContent=sym+' '+pay.toFixed(2);
}
function bSyncTxn(){
  var sel=document.getElementById('bPayMode'); if(!sel) return;
  var online=(sel.value||'').toLowerCase()!=='cash';
  var wrap=document.getElementById('bTxnWrap'), txn=document.getElementById('bTxnId');
  wrap.style.display=online?'':'none'; txn.required=online; if(!online) txn.value='';
}
document.addEventListener('DOMContentLoaded', bSyncTxn);
</script>
@endpush
