@if(!_isStoreVerified($store))
<style>
    .crb-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        background: linear-gradient(135deg, #fff8e1 0%, #ffffff 100%);
        border-top: 2px solid #ffc107;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        padding: 12px 0;
        transition: transform 0.3s ease;
    } 
    .crb-bar.crb-hidden { transform: translateY(100%); }
    .crb-bar .crb-inner {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
        max-width: 900px;
        margin: 0 auto;
        padding: 0 16px;
    }
    .crb-bar .crb-icon {
        width: 36px;
        height: 36px;
        background: #fff3cd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .crb-bar .crb-icon i { color: #e6a800; font-size: 16px; }
    .crb-bar .crb-text { 
        font-size: 13px;
        color: #000000;
        line-height: 1.4;
    }
    .crb-bar .crb-text strong { color: #333; }
    .crb-bar .crb-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .crb-bar .btn-claim {
        background: #e8b31a;
        color: #fff;
        border: none;
        padding: 7px 18px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }
    .crb-bar .btn-claim:hover { background: #d9a91b; color:#fff; }
    .crb-bar .btn-remove {
        background: transparent;
        padding: 7px 18px;
        border-radius: 20px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid #dc3545; color: #dc3545; 
    }
    .crb-bar .btn-remove:hover { background: #dc3545; color:#fff; }

    .crb-bar .crb-close {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #aaa;
        font-size: 18px;
        cursor: pointer;
        padding: 4px 8px;
        line-height: 1;
    }
    .crb-bar .crb-close:hover { color: #333; }

    /* Claim modal styles */
    .crb-steps { counter-reset: crb-step; list-style: none; padding: 0; margin: 20px 0 0; }
    .crb-steps li {
        counter-increment: crb-step;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        color: #555;
    }
    .crb-steps li:last-child { border-bottom: none; }
    .crb-steps li::before {
        content: counter(crb-step);
        background: #e8b31a;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .crb-modal-header {
        background: linear-gradient(135deg, #e8b31a, #ecc241);
        color: white;
        border-radius: 8px 8px 0 0;
        padding: 20px 24px;
    }
    .crb-modal-header .btn-close { filter: brightness(0) invert(1); }
    .crb-modal-header h5 { margin: 0; font-weight: 600; }
    .crb-vendor-link {
        display: inline-block;
        background: #e8b31a;
        color: white;
        padding: 10px 28px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 500;
        transition: background 0.2s;
    }
    .crb-vendor-link:hover { background: #e8b31a; color: white; text-decoration: none; }

    /* Remove modal */
    .crb-remove-header {
        background: linear-gradient(135deg, #dc3545, #e35d6a);
        color: white;
        border-radius: 8px 8px 0 0;
        padding: 20px 24px;
    }
    .crb-remove-header .btn-close { filter: brightness(0) invert(1); }
    .crb-remove-header h5 { margin: 0; font-weight: 600; }

    @media (max-width: 576px) {
        .crb-bar .crb-inner {
            flex-direction: column;
            text-align: center;
            gap: 8px;
        }
        .crb-bar .crb-icon { display: none; }
        .crb-bar .crb-actions { justify-content: center; }
        .crb-bar .crb-close { top: 8px; transform: none; }
    }
</style>

@if(session('success'))
<div class="modal fade show" id="crbSuccessModal" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 text-center" style="border-radius: 12px;">
            <div class="modal-body py-4 px-4">
                <div style="width:56px;height:56px;background:#d4edda;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                    <i class="fa fa-check" style="color:#28a745;font-size:24px;"></i>
                </div>
                <h6 class="mb-2">Request Submitted!</h6>
                <p class="text-muted mb-3" style="font-size:13px;">{{ session('success') }}</p>
                <button class="btn btn-sm px-4" style="background:#28a745;color:#fff;border-radius:20px;" onclick="document.getElementById('crbSuccessModal').remove()">OK</button>
            </div>
        </div>
    </div>
</div>
@endif 

{{-- Sticky bottom bar --}}
<div class="crb-bar" id="crbBar">
    <button class="crb-close" onclick="document.getElementById('crbBar').classList.add('crb-hidden')" title="Dismiss">&times;</button>
    <div class="crb-inner">
        <div class="crb-icon">
            <i class="fa fa-shield-alt"></i>
        </div>
        <div class="crb-text">
            <strong>Unverified Business</strong> &mdash; Is this your business? Claim it and get a verified badge.
        </div>
        <div class="crb-actions">
            <button class="btn-claim" data-bs-toggle="modal" data-bs-target="#claimBusinessModal">
                <i class="fa fa-check-circle"></i> Claim Business
            </button>
            <button class="btn-remove" data-bs-toggle="modal" data-bs-target="#removeBusinessModal">
                <i class="fa fa-flag"></i> Remove Listing
            </button>
        </div>
    </div>
</div>

{{-- Claim Business Modal --}}
<div class="modal fade" id="claimBusinessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 10px; overflow: hidden;">
            <div class="crb-modal-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5  class="text-white"><i class="fa fa-building me-2"></i> Claim This Business</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <p class="mb-0 mt-1" style="font-size: 13px; opacity: 0.9;">Verify ownership of <strong>{{ $store['name'] }}</strong></p>
            </div>
            <div class="modal-body px-4 py-4">
                <p class="text-muted mb-2" style="font-size: 14px;">
                    Follow these steps to verify your business:
                </p>
                <ol class="crb-steps">
                    <li>
                    <p>Login to the Vendor Panel at 
                        <a href="https://vendor.mcvendorhub.com/login" target="_blank" class="fw-bold text-primary">vendor.mcvendorhub.com</a> <br>(you can use "Login with OTP")</p>
                    </li>
                    <li>Go to <strong>My Business -> Store Settings</strong> </li>
                    <li>Upload required documents ( GST Document, ID proof document, etc.)</li>
                    <li>Submit for verification &mdash; our team will review it</li>
                </ol>
                <div class="text-center mt-4 pt-2" style="border-top: 1px solid #f0f0f0;">
                    <p class="text-muted mb-3" style="font-size: 12px;">
                        <i class="fa fa-info-circle"></i> After verification, your business will display a <strong>Verified Badge</strong>
                    </p>
                    <a href="https://vendor.mcvendorhub.com/login" target="_blank" class="crb-vendor-link">
                        <i class="fa fa-external-link-alt me-1"></i> Go to Vendor Panel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Remove Business Modal --}}
<div class="modal fade" id="removeBusinessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 10px; overflow: hidden;">
            <div class="crb-remove-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="text-white"><i class="fa fa-flag me-2"></i> Remove This Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <p class="mb-0 mt-1" style="font-size: 13px; opacity: 0.9;">Request removal of this business listing</p>
            </div>
            <form action="{{ route('store.removal-request') }}" method="POST">
                @csrf
                <input type="hidden" name="store_id" value="{{ $store['id'] }}">
                <div class="modal-body px-4 py-4">
                    <div class="mb-3">
                        <label class="form-label fw-500" style="font-size: 13px;">Your Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required placeholder="Full name">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-500" style="font-size: 13px;">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" required placeholder="your@email.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-500" style="font-size: 13px;">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control form-control-sm" required placeholder="Phone number">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-500" style="font-size: 13px;">Reason for Removal <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control form-control-sm" rows="3" required placeholder="Please explain why this listing should be removed..."></textarea>
                    </div>
                    <p class="text-muted mt-2 mb-0" style="font-size: 11px;">
                        <i class="fa fa-info-circle"></i> Our admin team will review your request and take appropriate action.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4">
                        <i class="fa fa-paper-plane me-1"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
