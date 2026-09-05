@php
    $storeId = \App\CentralLogics\Helpers::get_store_id();
    $vendorId = \App\CentralLogics\Helpers::get_vendor_id();

    $waStore = $storeId ? \Illuminate\Support\Facades\DB::table('stores')
        ->where('id', $storeId)
        ->select('wa_enabled', 'wa_phone_number_id', 'wa_business_account_id')
        ->first() : null;

    // Check if WABA is connected: either wa_enabled flag is on, or an active subscription exists
    $isWabaConnected = (bool) ($waStore && $waStore->wa_enabled);

    // Also check wa_subscriptions table if wa_enabled is not set
    if (!$isWabaConnected && $storeId && \Illuminate\Support\Facades\Schema::hasTable('wa_subscriptions')) {
        $activeSub = \Illuminate\Support\Facades\DB::table('wa_subscriptions')
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where('current_period_end', '>=', now()->toDateString())
            ->first();
        if ($activeSub) {
            $isWabaConnected = true;
            // Sync wa_enabled flag back to stores table
            \Illuminate\Support\Facades\DB::table('stores')->where('id', $storeId)->update(['wa_enabled' => 1]);
        }
    }

    $vendorWallet = $vendorId ? \App\Models\StoreWallet::where('vendor_id', $vendorId)->first() : null;
    $walletBalance = (float) ($vendorWallet?->total_earning ?? 0);
@endphp

{{-- Modal to activate WhatsApp Monthly Plan (₹200/month from wallet) --}}
<div class="modal fade" id="activateWaPlanModal" tabindex="-1" role="dialog" aria-labelledby="activateWaPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white; border: none; padding: 18px 24px;">
                <h5 class="modal-title text-white d-flex align-items-center gap-2 mb-0" id="activateWaPlanModalLabel" style="font-weight: 700; font-size: 18px;">
                    <i class="tio-whatsapp" style="font-size: 24px;"></i> Activate WhatsApp Monthly Plan
                </h5>
                <button type="button" class="close text-white opacity-80" data-dismiss="modal" aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div style="width: 60px; height: 60px; background: #e7fceb; color: #25D366; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 12px;">
                        <i class="tio-whatsapp"></i>
                    </div>
                    <h6 class="font-weight-bold" style="color: #1e293b; font-size: 16px;">WhatsApp WABA Not Connected</h6>
                    <p class="text-muted" style="font-size: 13px; margin-bottom: 0;">
                        Your WhatsApp Business Account (WABA) is not connected. Activate the WhatsApp Monthly Plan to send prescriptions, reports, and receipts directly to patients on WhatsApp.
                    </p>
                </div>

                <div class="card border-0 mb-3" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted" style="font-size: 13px;">Plan Fee:</span>
                            <span class="font-weight-bold text-dark" style="font-size: 15px;">₹200.00 / month</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted" style="font-size: 13px;">Deduction Method:</span>
                            <span class="badge badge-soft-info" style="font-size: 12px; font-weight: 600;">Store Wallet</span>
                        </div>
                        <hr class="my-2" style="border-color: #cbd5e1;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold" style="font-size: 13px; color: #475569;">Your Wallet Balance:</span>
                            <span class="font-weight-bold" id="waModalWalletBalanceDisplay" style="font-size: 16px; color: #0f172a;">
                                ₹{{ number_format($walletBalance, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div id="waPlanModalAlert" class="alert alert-danger d-none" role="alert" style="font-size: 13px; border-radius: 6px;"></div>
            </div>
            <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 24px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm font-weight-bold" id="btnConfirmActivateWaPlan" style="background-color: #25D366; border-color: #25D366; padding: 8px 18px;">
                    <i class="tio-wallet mr-1"></i> Deduct ₹200 &amp; Activate Plan
                </button>
                <a href="{{ route('vendor.wallet.index') }}" id="btnRechargeWalletLink" class="btn btn-warning btn-sm font-weight-bold d-none">
                    <i class="tio-wallet mr-1"></i> Recharge Wallet
                </a>
            </div>
        </div>
    </div>
</div>

@push('script_2')
<script>
window.isWabaConnected = @json($isWabaConnected);
window.vendorWalletBalance = @json($walletBalance);

let pendingWaCallback = null;

function openActivateWhatsAppModal(onSuccessCallback) {
    pendingWaCallback = onSuccessCallback || null;
    $('#waPlanModalAlert').addClass('d-none').html('');
    $('#btnRechargeWalletLink').addClass('d-none');
    $('#btnConfirmActivateWaPlan').removeClass('d-none').prop('disabled', false).html('<i class="tio-wallet mr-1"></i> Deduct ₹200 &amp; Activate Plan');
    $('#activateWaPlanModal').modal('show');
}

$(document).on('click', '#btnConfirmActivateWaPlan', function() {
    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Activating...');
    $('#waPlanModalAlert').addClass('d-none').html('');

    $.ajax({
        url: "{{ route('vendor.hmis-whatsapp.activate-monthly-plan') }}",
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(res) {
            if (res.success) {
                window.isWabaConnected = true;
                if (res.wallet_balance !== undefined) {
                    $('#waModalWalletBalanceDisplay').text('₹' + parseFloat(res.wallet_balance).toFixed(2));
                }
                $('#activateWaPlanModal').modal('hide');
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message || 'WhatsApp Monthly Plan activated successfully!');
                } else {
                    alert(res.message || 'WhatsApp Monthly Plan activated successfully!');
                }

                if (typeof pendingWaCallback === 'function') {
                    const cb = pendingWaCallback;
                    pendingWaCallback = null;
                    cb();
                }
            } else {
                $btn.prop('disabled', false).html('<i class="tio-wallet mr-1"></i> Deduct ₹200 &amp; Activate Plan');
                let alertHtml = res.message || 'Failed to activate plan.';
                if (res.recharge_url) {
                    $('#btnConfirmActivateWaPlan').addClass('d-none');
                    $('#btnRechargeWalletLink').removeClass('d-none');
                }
                $('#waPlanModalAlert').removeClass('d-none').html(alertHtml);
            }
        },
        error: function(xhr) {
            $btn.prop('disabled', false).html('<i class="tio-wallet mr-1"></i> Deduct ₹200 &amp; Activate Plan');
            let err = xhr.responseJSON?.message || 'An error occurred while activating the plan. Please try again.';
            $('#waPlanModalAlert').removeClass('d-none').html(err);
        }
    });
});

$(document).ready(function() {
    // Intercept forms or buttons requiring WABA connection
    $(document).on('click', '.btn-finalize-whatsapp', function(e) {
        if (!window.isWabaConnected) {
            e.preventDefault();
            e.stopPropagation();
            const btn = this;
            const form = btn.closest('form');

            openActivateWhatsAppModal(function() {
                if (form) {
                    if (!form.querySelector('input[name="finalize_and_whatsapp"]')) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'finalize_and_whatsapp';
                        input.value = '1';
                        form.appendChild(input);
                    }
                    if (!form.querySelector('input[name="finalize"]')) {
                        const finInput = document.createElement('input');
                        finInput.type = 'hidden';
                        finInput.name = 'finalize';
                        finInput.value = '1';
                        form.appendChild(finInput);
                    }
                    form.submit();
                }
            });
            return false;
        }
    });

    $(document).on('submit', '.wa-send-pdf-form', function(e) {
        if (!window.isWabaConnected) {
            e.preventDefault();
            const form = this;
            openActivateWhatsAppModal(function() {
                form.submit();
            });
            return false;
        }
    });
});
</script>
@endpush
