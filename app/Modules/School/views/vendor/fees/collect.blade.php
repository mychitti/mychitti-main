@extends('layouts.vendor.app')
@section('title', 'Collect Fee')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-money-vs mr-1"></i> Collect Fee</h1>
        <a href="{{ route('vendor.school.fees.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <div class="card mb-3"><div class="card-body d-flex flex-wrap" style="gap:24px;">
        <div><span class="text-muted">Student</span><br><strong>{{ $student->name }}</strong></div>
        <div><span class="text-muted">Admission No</span><br><strong>{{ $student->admission_no }}</strong></div>
        <div><span class="text-muted">Class</span><br><strong>{{ $student->currentEnrollment?->schoolClass?->name ?? '—' }}</strong></div>
    </div></div>

    <form action="{{ route('vendor.school.fees.collect.store', $student->id) }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card"><div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Fee Items</h6>
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="addRow()">+ Add line</button>
                </div>
                <div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-borderless table-align-middle card-table mb-0" id="feeRows">
                        <thead class="thead-light"><tr><th>Fee Head</th><th style="width:180px;">Amount</th><th style="width:50px;"></th></tr></thead>
                        <tbody>
                        @forelse($items as $it)
                            <tr>
                                <td><input name="name[]" class="form-control form-control-sm" value="{{ $it['name'] ?? $it['head_name'] ?? '' }}" required>
                                    <input type="hidden" name="gst_percent[]" value="{{ $it['gst_percent'] ?? 0 }}"></td>
                                <td><input type="number" step="0.01" min="0" name="amount[]" class="form-control form-control-sm amt" value="{{ $it['amount'] ?? 0 }}" oninput="recalc()"></td>
                                <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="this.closest('tr').remove();recalc()">×</button></td>
                            </tr>
                        @empty
                            <tr>
                                <td><input name="name[]" class="form-control form-control-sm" placeholder="Tuition" required>
                                    <input type="hidden" name="gst_percent[]" value="0"></td>
                                <td><input type="number" step="0.01" min="0" name="amount[]" class="form-control form-control-sm amt" value="0" oninput="recalc()"></td>
                                <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="this.closest('tr').remove();recalc()">×</button></td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div></div></div>
            </div>

            <div class="col-lg-4">
                <div class="card"><div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Total</span><strong id="totalTxt">0</strong></div>
                    <div class="form-group">
                        <label class="input-label d-flex justify-content-between">
                            <span>Concession</span>
                            @if(!empty($auto['schemes']))<small class="text-success">Scholarship applied</small>@endif
                        </label>
                        <input type="number" step="0.01" min="0" name="concession" id="concession" class="form-control" value="{{ $auto['amount'] ?? 0 }}" oninput="recalc()">
                        @if(!empty($auto['schemes']))
                            <small class="text-muted d-block mt-1">
                                @foreach($auto['schemes'] as $sc){{ $sc['name'] }} ({{ \App\CentralLogics\Helpers::format_currency($sc['amount']) }}){{ !$loop->last ? ', ' : '' }}@endforeach
                            </small>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Net Payable</span><strong id="netTxt">0</strong></div>
                    <div class="form-group"><label class="input-label">Amount Paid</label>
                        <input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid" class="form-control" oninput="recalcDue()"></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Due</span><strong id="dueTxt" class="text-danger">0</strong></div>
                    <div class="form-group"><label class="input-label">Payment Mode *</label>
                        <select name="payment_mode" class="form-control js-select2-custom">
                            @foreach(['Cash','UPI','Card','Bank Transfer','Cheque','Wallet'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                        </select></div>
                    <button class="btn btn--primary btn-block"><i class="tio-receipt"></i> Collect & Receipt</button>
                </div></div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    const sym = '{{ \App\CentralLogics\Helpers::currency_symbol() }}';
    function rows(){ return [...document.querySelectorAll('#feeRows .amt')]; }
    function total(){ return rows().reduce((s,i)=>s+(parseFloat(i.value)||0),0); }
    function recalc(){
        const t = total();
        const c = parseFloat(document.getElementById('concession').value)||0;
        const net = Math.max(0, t-c);
        document.getElementById('totalTxt').textContent = sym+t.toFixed(2);
        document.getElementById('netTxt').textContent = sym+net.toFixed(2);
        const paidEl = document.getElementById('amount_paid');
        if(!paidEl.dataset.touched) paidEl.value = net.toFixed(2);
        recalcDue();
    }
    function recalcDue(){
        const c = parseFloat(document.getElementById('concession').value)||0;
        const net = Math.max(0, total()-c);
        const paid = parseFloat(document.getElementById('amount_paid').value)||0;
        document.getElementById('dueTxt').textContent = sym+Math.max(0,(net-paid)).toFixed(2);
    }
    document.getElementById('amount_paid').addEventListener('input', e=>e.target.dataset.touched=1);
    function addRow(){
        const tb = document.querySelector('#feeRows tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><input name="name[]" class="form-control form-control-sm" required><input type="hidden" name="gst_percent[]" value="0"></td>`+
            `<td><input type="number" step="0.01" min="0" name="amount[]" class="form-control form-control-sm amt" value="0" oninput="recalc()"></td>`+
            `<td><button type="button" class="btn btn-xs btn-outline-danger" onclick="this.closest('tr').remove();recalc()">×</button></td>`;
        tb.appendChild(tr);
    }
    recalc();
</script>
@endpush
