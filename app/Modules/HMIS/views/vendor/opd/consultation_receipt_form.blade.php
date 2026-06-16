@extends('layouts.vendor.app')
@section('title', 'OP Consultation Fee')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-receipt" style="font-size:22px;"></i></span>
            Collect OP Consultation Fee
        </h1>
        <a href="{{ route('vendor.opd.show', $visit->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-back-ui mr-1"></i> Back to Visit
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="alert alert-soft-info py-2" style="font-size:13px;">
                This OP will be valid for <strong>{{ $allowed }}</strong> consultation(s) for
                <strong>{{ $validityDays }}</strong> day(s). Follow-up visits within this window are free.
            </div>

            <form action="{{ route('vendor.opd.consultation-receipt.store', $visit->id) }}" method="POST">
                @csrf

                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-user mr-1"></i>
                            {{ $visit->patient?->name }}
                            <span class="text-muted">({{ $visit->patient?->patient_uid }})</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2" style="font-size:13px;">
                            <div class="col-6 text-muted">Doctor</div>
                            <div class="col-6 text-right font-weight-bold">
                                Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}
                            </div>
                            <div class="col-6 text-muted">Token</div>
                            <div class="col-6 text-right font-weight-bold">{{ $visit->token_number }}</div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Consultation Fee <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount', $defaultFee) }}" oninput="recalc()">
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label">Concession</label>
                            <input type="number" step="0.01" min="0" name="concession" id="concession"
                                   class="form-control" value="{{ old('concession', 0) }}" oninput="recalc()">
                        </div>

                        <div class="form-group {{ in_array(old('payment_mode'), ['Card','UPI','Online','Wallet']) ? '' : 'mb-0' }}">
                            <label class="input-label">Mode of Payment <span class="text-danger">*</span></label>
                            <select name="payment_mode" id="opdPayMode" class="form-control" onchange="opdSyncTxn()">
                                @foreach (['Cash', 'Card', 'UPI', 'Online', 'Wallet'] as $m)
                                    <option value="{{ $m }}" {{ old('payment_mode') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-0" id="opdTxnWrap" style="display:none;">
                            <label class="input-label">Transaction ID <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" id="opdTxnId" class="form-control"
                                   value="{{ old('transaction_id') }}" placeholder="UPI / card / online reference">
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted" style="font-size:12px;">Payable: </span>
                            <strong id="payable" style="font-size:15px; color:#059669;">{{ \App\CentralLogics\Helpers::format_currency($defaultFee) }}</strong>
                        </div>
                        <button type="submit" class="btn btn--primary">
                            <i class="tio-print mr-1"></i> Generate Receipt
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function recalc() {
        const amt  = parseFloat(document.getElementById('amount').value) || 0;
        const conc = parseFloat(document.getElementById('concession').value) || 0;
        const pay  = Math.max(0, amt - conc);
        document.getElementById('payable').textContent =
            '{{ \App\CentralLogics\Helpers::currency_symbol() }}' + pay.toFixed(2);
    }

    // Require a transaction ID for any non-cash payment mode.
    function opdSyncTxn() {
        const mode = (document.getElementById('opdPayMode').value || '').toLowerCase();
        const online = mode !== 'cash';
        const wrap = document.getElementById('opdTxnWrap');
        const txn = document.getElementById('opdTxnId');
        wrap.style.display = online ? '' : 'none';
        txn.required = online;
        if (!online) txn.value = '';
    }
    document.addEventListener('DOMContentLoaded', opdSyncTxn);
</script>
@endpush
