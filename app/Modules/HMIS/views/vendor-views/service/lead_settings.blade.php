@extends('layouts.vendor.app')

@section('title', 'Lead Settings')

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between">
            <h1 class="page-header-title">Lead Settings</h1>
            <button type="button" class="btn btn-outline-dark btn-sm" data-toggle="modal" data-target="#leadsGuideModal">
                📋 How it Works
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('vendor.service.lead-settings.update') }}" method="POST">
                    @csrf

                    @if ($store_data->module_id == 6)
                        @php $hasDedicatedSub = \App\CentralLogics\Helpers::store_has_active_lead_subscription($store_data->id, 'dedicated'); @endphp
                        <div class="p-3 bg-light rounded mb-4 col-6">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1">Dedicated Leads</h5>
                                    <small class="text-muted">
                                        When enabled, enquiries made from your store page will come only to you instead of being distributed to other vendors.
                                        @if (!$hasDedicatedSub)
                                            <br><span class="text-danger">Requires an active Dedicated Lead subscription.</span>
                                        @endif
                                    </small>
                                </div>
                                <label class="toggle-switch toggle-switch-sm {{ !$hasDedicatedSub ? 'disabled' : '' }}">
                                    <input type="checkbox" name="dedicated_leads" value="1"
                                        class="toggle-switch-input"
                                        {{ $store_data->dedicated_leads && $hasDedicatedSub ? 'checked' : '' }}
                                        {{ !$hasDedicatedSub ? 'disabled' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── How It Works modal ── --}}
    <div class="modal fade" id="leadsGuideModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;">
                <div class="modal-header" style="background:#18181b;border:none;padding:22px 28px;">
                    <div>
                        <h5 class="modal-title" style="color:#fff;font-weight:800;font-size:18px;">
                            📋 How Leads Work
                        </h5>
                        <small style="color:#a1a1aa;font-size:12px;">Step-by-step guide to managing your leads</small>
                    </div>
                    <button type="button" class="close" style="color:#fff;opacity:1;" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:28px;background:#f7f7f7;max-height:65vh;overflow-y:auto;">
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        @php
                            $steps = [
                                ['icon'=>'📲','title'=>'Customer Submits a Request','desc'=>'A customer finds your service on the app and submits an enquiry. The request is sent to you and nearby vendors simultaneously.'],
                                ['icon'=>'⏱️','title'=>'New Lead Arrives (Time-Limited)','desc'=>'You receive the lead as a "New" card. You must accept it before the timer expires — missed leads cannot be recovered.'],
                                ['icon'=>'✅','title'=>'Accept the Lead','desc'=>'Click Accept Lead. The customer\'s contact details are revealed and the lead moves to "Accepted" status.'],
                                ['icon'=>'💬','title'=>'Send Confirmation Request','desc'=>'Open the lead card, enter your visiting charges, and send a Confirmation Request. The customer gets a notification with your quote.'],
                                ['icon'=>'🤝','title'=>'Customer Confirms','desc'=>'Once the customer accepts your quote, the lead becomes "Confirmed". You are now committed to deliver the service.'],
                                ['icon'=>'👷','title'=>'Assign Staff or Handle Yourself','desc'=>'Assign the job to a staff member or handle it personally. The assigned person receives a notification to accept the job.'],
                                ['icon'=>'🔑','title'=>'Start Job (OTP)','desc'=>'When the technician arrives, they start the job by entering the OTP shared by the customer.'],
                                ['icon'=>'✔️','title'=>'Complete Job (OTP)','desc'=>'After the work is done, mark it "Completed" using the OTP the customer shares to confirm completion.'],
                                ['icon'=>'🧾','title'=>'Generate Bill','desc'=>'Once completed, generate the invoice from the lead card. You can edit the bill as many times as needed.'],
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
