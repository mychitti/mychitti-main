@extends('layouts.vendor.app')

@section('title', 'Lead Settings')

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between">
            <h1 class="page-header-title">Lead Settings</h1>
            <button type="button" class="btn btn-outline-dark btn-sm" data-toggle="modal" data-target="#leadsGuideModal">
                ðŸ“‹ How it Works
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('vendor.service.lead-settings.update') }}" method="POST">
                    @csrf

                    @if ($store_data->module_id == 6)
                        @php
                            $hasDedicatedSub = \App\CentralLogics\Helpers::store_has_active_lead_subscription($store_data->id, 'dedicated');
                            $hasSharedSub    = \App\CentralLogics\Helpers::store_has_active_lead_subscription($store_data->id, 'shared');
                        @endphp
                        <div class="p-3 bg-light rounded mb-4" style="max-width:600px;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1">Dedicated Leads</h5>
                                    <small class="text-muted d-block mb-1">
                                        When enabled, enquiries from your store page come only to you.
                                    </small>
                                    @if ($hasDedicatedSub)
                                        <span class="badge badge-success" style="font-size:11px;">Active Dedicated Subscription â€” no per-lead charge</span>
                                        <br><a href="{{ route('vendor.service.lead-subscription') }}" class="text-primary" style="font-size:12px;">Manage Subscription â†’</a>
                                    @elseif ($hasSharedSub)
                                        <small class="text-muted">Your current plan covers shared leads. Enabling dedicated leads will charge per lead from your wallet.</small>
                                        <br><a href="{{ route('vendor.service.lead-subscription') }}" class="text-primary" style="font-size:12px;">Upgrade to Dedicated Subscription â†’</a>
                                    @else
                                        <small class="text-muted">No active subscription â€” all lead charges (shared &amp; dedicated) will be deducted from your wallet on each accepted lead.</small>
                                        <br><a href="{{ route('vendor.service.lead-subscription') }}" class="text-primary" style="font-size:12px;">Get a Leads Subscription â†’</a>
                                    @endif
                                </div>
                                <label class="toggle-switch toggle-switch-sm ml-3">
                                    <input type="checkbox" name="dedicated_leads" value="1"
                                        class="toggle-switch-input"
                                        {{ $store_data->dedicated_leads ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="p-3 bg-light rounded mb-4" style="max-width:600px;">
                        <h5 class="mb-1">Visiting Charge</h5>
                        <small class="text-muted d-block mb-2">
                            Your default visiting charge (₹). This amount is pre-filled and sent to the customer in the
                            Confirmation Request.
                        </small>
                        <input type="number" step="0.01" min="0" name="lead_visiting_charge" class="form-control"
                            style="max-width:220px;" placeholder="e.g. 200"
                            value="{{ $storeConfig->lead_visiting_charge ?? '' }}">
                    </div>


                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>

        @include('vendor-views.service._whatsapp_lead_addon')

        @if ($store_data->module_id == 6)
            <div class="card mt-3">
                <div class="card-body" style="max-width:680px;">
                    <h6 class="font-weight-bold mb-3">How Lead Charges Work</h6>
                            <div class="d-flex flex-column" style="gap:10px;">

                                <div class="p-3 rounded border-left border-warning bg-white" style="border-left:4px solid #ffc107!important;">
                                    <div class="font-weight-bold mb-1" style="font-size:13px;">ðŸ”“ No Subscription</div>
                                    <div style="font-size:12px;color:#555;">Every accepted lead (shared or dedicated) is charged from your wallet based on your zone's lead charges. You need a minimum wallet balance to receive leads.</div>
                                </div>

                                <div class="p-3 rounded border-left bg-white" style="border-left:4px solid #17a2b8!important;">
                                    <div class="font-weight-bold mb-1" style="font-size:13px;">ðŸ“¦ Shared Leads Plan</div>
                                    <div style="font-size:12px;color:#555;">Shared leads are covered â€” no wallet deduction when you accept a shared lead. However, if you enable Dedicated Leads, those are still charged per lead from your wallet.</div>
                                </div>

                                <div class="p-3 rounded border-left bg-white" style="border-left:4px solid #28a745!important;">
                                    <div class="font-weight-bold mb-1" style="font-size:13px;">â­ Dedicated Leads Plan</div>
                                    <div style="font-size:12px;color:#555;">All leads â€” shared and dedicated â€” are covered. No wallet deduction on accepted leads. Enquiries from your store page come exclusively to you.</div>
                                </div>

                            </div>
                </div>
            </div>
        @endif
    </div>

    {{-- â”€â”€ How It Works modal â”€â”€ --}}
    <div class="modal fade" id="leadsGuideModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;">
                <div class="modal-header" style="background:#18181b;border:none;padding:22px 28px;">
                    <div>
                        <h5 class="modal-title" style="color:#fff;font-weight:800;font-size:18px;">
                            ðŸ“‹ How Leads Work
                        </h5>
                        <small style="color:#a1a1aa;font-size:12px;">Step-by-step guide to managing your leads</small>
                    </div>
                    <button type="button" class="close" style="color:#fff;opacity:1;" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:28px;background:#f7f7f7;max-height:65vh;overflow-y:auto;">
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        @php
                            $steps = [
                                ['icon'=>'ðŸ“²','title'=>'Customer Submits a Request','desc'=>'A customer finds your service on the app and submits an enquiry. The request is sent to you and nearby vendors simultaneously.'],
                                ['icon'=>'â±ï¸','title'=>'New Lead Arrives (Time-Limited)','desc'=>'You receive the lead as a "New" card. You must accept it before the timer expires â€” missed leads cannot be recovered.'],
                                ['icon'=>'âœ…','title'=>'Accept the Lead','desc'=>'Click Accept Lead. The customer\'s contact details are revealed and the lead moves to "Accepted" status.'],
                                ['icon'=>'ðŸ’¬','title'=>'Send Confirmation Request','desc'=>'Open the lead card, enter your visiting charges, and send a Confirmation Request. The customer gets a notification with your quote.'],
                                ['icon'=>'ðŸ¤','title'=>'Customer Confirms','desc'=>'Once the customer accepts your quote, the lead becomes "Confirmed". You are now committed to deliver the service.'],
                                ['icon'=>'ðŸ‘·','title'=>'Assign Staff or Handle Yourself','desc'=>'Assign the job to a staff member or handle it personally. The assigned person receives a notification to accept the job.'],
                                ['icon'=>'ðŸ”‘','title'=>'Start Job (OTP)','desc'=>'When the technician arrives, they start the job by entering the OTP shared by the customer.'],
                                ['icon'=>'âœ”ï¸','title'=>'Complete Job (OTP)','desc'=>'After the work is done, mark it "Completed" using the OTP the customer shares to confirm completion.'],
                                ['icon'=>'ðŸ§¾','title'=>'Generate Bill','desc'=>'Once completed, generate the invoice from the lead card. You can edit the bill as many times as needed.'],
                            ];
                        @endphp
                        @foreach ($steps as $i => $step)
                            <div style="display:flex;gap:14px;align-items:flex-start;background:#fff;border-radius:10px;padding:14px 16px;border:1px solid #e4e4e7;">
                                <div style="width:34px;height:34px;border-radius:50%;background:#18181b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;">
                                    {{ $i + 1 }}
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:800;color:#18181b;margin-bottom:3px;">{{ $step['icon'] }} {{ $step['title'] }}</div>
                                    <div style="font-size:12px;color:#52525b;line-height:1.6;">{{ $step['desc'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer" style="background:#f7f7f7;border-top:1px solid #e4e4e7;padding:16px 28px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
