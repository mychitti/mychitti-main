@extends('layouts.vendor.app')

@section('title', ($direction === 'send' ? 'Send' : 'Receive') . ' Notification Settings')

@php
    $activeTab = request('tab', 'whatsapp');
    if (!isset($channels[$activeTab])) {
        $activeTab = array_key_first($channels);
    }
    $current = $channels[$activeTab];
@endphp

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h1 class="page-header-title">
                <i class="{{ $direction === 'send' ? 'tio-send' : 'tio-notifications-on-outlined' }}"></i>
                {{ $direction === 'send' ? 'Send Notifications' : 'Receive Notifications' }}
            </h1>
            <div>
                <a href="{{ route('vendor.notification-settings', ['direction' => 'send']) }}"
                   class="btn btn-sm {{ $direction === 'send' ? 'btn--primary' : 'btn-outline-primary' }}">
                    <i class="tio-send"></i> Send
                </a>
                <a href="{{ route('vendor.notification-settings', ['direction' => 'receive']) }}"
                   class="btn btn-sm {{ $direction === 'receive' ? 'btn--primary' : 'btn-outline-primary' }}">
                    <i class="tio-notifications"></i> Receive
                </a>
            </div>
        </div>

        <p class="text-muted" style="font-size:13px;">
            @if ($direction === 'send')
                Choose which automatic messages your store sends to customers, per action and channel.
            @else
                Choose which alerts you receive when something happens in your store, per action and channel.
            @endif
        </p>

        @if (!$waConnected && $activeTab === 'whatsapp')
            <div class="alert alert-warning" style="font-size:13px;">
                <i class="tio-info-outined"></i>
                <b>WhatsApp is not connected.</b> The toggles below are saved, but nothing sends until you
                <a href="{{ route('vendor.whatsapp.connect') }}" class="alert-link">connect your number</a>.
            </div>
        @endif

        <div class="card">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs px-3 pt-2" style="gap:4px;">
                    @foreach ($channels as $chKey => $ch)
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === $chKey ? 'active' : '' }}"
                               href="{{ route('vendor.notification-settings', ['direction' => $direction, 'tab' => $chKey]) }}">
                                <i class="{{ $ch['icon'] }}"></i> {{ $ch['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body">
                @forelse ($current['items'] as $key => $item)
                    <div class="d-flex justify-content-between align-items-start py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="gap:12px;">
                        <div style="min-width:0;">
                            <b style="font-size:14px;">{{ $item['label'] }}</b>
                            <small class="text-muted d-block" style="font-size:12px;">{{ $item['desc'] }}</small>
                            @if ($current['group'] === 'whatsapp_receive' && $key === 'lead_notify' && $leadFeature && !$leadFeature['paid_active'])
                                <small class="d-block text-warning" style="font-size:11px;">
                                    <i class="tio-info-outined"></i> The paid Lead Notifications add-on is not active — this alert won't send even when on.
                                </small>
                            @endif

                            {{-- Timing is part of the setting for these two: a feedback request is
                                 worth little asked at the desk, and a follow-up reminder is worth
                                 little sent months ahead. --}}
                            @if ($key === 'hmis_feedback')
                                <form action="{{ route('vendor.notification-settings.timing') }}" method="post"
                                      class="d-flex align-items-center flex-wrap mt-1 mb-0" style="gap:6px;">
                                    @csrf
                                    <input type="hidden" name="setting" value="feedback">
                                    <label class="mb-0 text-muted" style="font-size:12px;">Ask</label>
                                    <input type="number" name="value" class="form-control form-control-sm" style="width:70px;"
                                           min="0" max="720" value="{{ $feedbackDelay }}">
                                    <span class="text-muted" style="font-size:12px;">hour(s) after the visit</span>
                                    <button type="submit" class="btn btn-sm btn-outline-primary py-0">Save</button>
                                    <small class="text-muted d-block w-100" style="font-size:11px;">
                                        0 sends it immediately. Anything landing between 9pm and 8am waits for 10am.
                                    </small>
                                </form>
                            @endif
                            @if ($key === 'hmis_followup')
                                <form action="{{ route('vendor.notification-settings.timing') }}" method="post"
                                      class="d-flex align-items-center flex-wrap mt-1 mb-0" style="gap:6px;">
                                    @csrf
                                    <input type="hidden" name="setting" value="followup">
                                    <label class="mb-0 text-muted" style="font-size:12px;">Remind</label>
                                    <input type="number" name="value" class="form-control form-control-sm" style="width:70px;"
                                           min="0" max="30" value="{{ $followupLead }}">
                                    <span class="text-muted" style="font-size:12px;">day(s) before the visit</span>
                                    <button type="submit" class="btn btn-sm btn-outline-primary py-0">Save</button>
                                    <small class="text-muted d-block w-100" style="font-size:11px;">
                                        0 confirms the booking straight away instead. If the visit is sooner than this,
                                        the confirmation goes out at booking so nobody is missed.
                                    </small>
                                </form>
                            @endif

                            {{-- An action whose template Meta hasn't approved is a switch wired to
                                 nothing. Say so on the row, not only when they flip it. --}}
                            @if (!empty($item['template_status']) && $item['template_status'] !== 'APPROVED')
                                @if ($item['template_status'] === 'MISSING')
                                    <small class="d-block text-warning" style="font-size:11px;">
                                        <i class="tio-warning"></i>
                                        The <code>{{ $item['template'] }}</code> template isn't on your WhatsApp account yet, so this won't send.
                                        <a href="{{ route('vendor.whatsapp.templates') }}#tplPresets">Add it in one click</a> from the ready-made list.
                                    </small>
                                @elseif ($item['template_status'] === 'PENDING')
                                    <small class="d-block text-info" style="font-size:11px;">
                                        <i class="tio-time"></i>
                                        <code>{{ $item['template'] }}</code> is with Meta for review — sending starts automatically once it's approved.
                                    </small>
                                @else
                                    <small class="d-block text-danger" style="font-size:11px;">
                                        <i class="tio-clear-circle"></i>
                                        <code>{{ $item['template'] }}</code> is {{ $item['template_status'] }} at Meta, so this won't send.
                                        <a href="{{ route('vendor.whatsapp.templates') }}">Fix it under Message Templates</a>.
                                    </small>
                                @endif
                            @endif
                        </div>
                        <form action="{{ route('vendor.notification-settings.toggle') }}" method="post" class="mb-0 flex-shrink-0">
                            @csrf
                            <input type="hidden" name="group" value="{{ $current['group'] }}">
                            <input type="hidden" name="key" value="{{ $key }}">
                            <input type="hidden" name="enabled" value="{{ $item['enabled'] ? 0 : 1 }}">
                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                    title="{{ $item['enabled'] ? 'Turn off' : 'Turn on' }}">
                                <span class="badge badge-{{ $item['enabled'] ? 'success' : 'secondary' }} px-3 py-2">
                                    {{ $item['enabled'] ? 'ON' : 'OFF' }}
                                </span>
                            </button>
                        </form>
                    </div>
                @empty
                    @if (!($direction === 'send' && $activeTab === 'whatsapp'))
                        <div class="text-center text-muted py-4" style="font-size:13px;">
                            <i class="{{ $current['icon'] }}" style="font-size:28px;"></i>
                            <p class="mb-0 mt-2">No automatic {{ $current['label'] }} {{ $direction === 'send' ? 'messages to customers' : 'alerts' }} yet.</p>
                        </div>
                    @endif
                @endforelse

                @if ($direction === 'send' && $activeTab === 'whatsapp')
                    {{-- Appointment reminder: on/off IS the hours value (stores.wa_appt_reminder). --}}
                    <div class="d-flex justify-content-between align-items-start py-2 {{ count($current['items']) ? 'border-top' : '' }}" style="gap:12px;">
                        <div style="min-width:0;">
                            <b style="font-size:14px;">Appointment reminder</b>
                            <small class="text-muted d-block" style="font-size:12px;">
                                Reminder to the patient before their scheduled appointment (needs your approved appointment_reminder template).
                            </small>
                            <form action="{{ route('vendor.whatsapp.templates.reminder-schedule') }}" method="post"
                                  class="d-flex align-items-center flex-wrap mt-1 mb-0" style="gap:6px;">
                                @csrf
                                <label class="mb-0 text-muted" style="font-size:12px;">Send</label>
                                <input type="number" name="hours" class="form-control form-control-sm" style="width:70px;"
                                       min="0" max="168" value="{{ $apptReminder }}">
                                <span class="text-muted" style="font-size:12px;">hour(s) before</span>
                                <button type="submit" class="btn btn-sm btn-outline-primary py-0">Save</button>
                                <small class="text-muted d-block w-100" style="font-size:11px;">Set 0 to turn off. Max 168 (7 days).</small>
                            </form>
                        </div>
                        <span class="badge badge-{{ $apptReminder > 0 ? 'success' : 'secondary' }} px-3 py-2 flex-shrink-0">
                            {{ $apptReminder > 0 ? 'ON' : 'OFF' }}
                        </span>
                    </div>

                    {{-- Service recall: the toggle above turns it on, this decides how long the
                         store waits. Blank means never, so the sweep skips this store entirely. --}}
                    <div class="d-flex justify-content-between align-items-start py-2 border-top" style="gap:12px;">
                        <div style="min-width:0;">
                            <b style="font-size:14px;">Service due again — how long to wait</b>
                            <small class="text-muted d-block" style="font-size:12px;">
                                Counted from the day a service request is completed. Switch the message itself
                                on with the "Service due again" toggle above.
                            </small>
                            <form action="{{ route('vendor.notification-settings.service-recall') }}" method="post"
                                  class="d-flex align-items-center flex-wrap mt-1 mb-0" style="gap:6px;">
                                @csrf
                                <label class="mb-0 text-muted" style="font-size:12px;">Invite back after</label>
                                <input type="number" name="days" class="form-control form-control-sm" style="width:80px;"
                                       min="0" max="1095" placeholder="—" value="{{ $serviceRecallDays }}">
                                <span class="text-muted" style="font-size:12px;">day(s)</span>
                                <button type="submit" class="btn btn-sm btn-outline-primary py-0">Save</button>
                                <small class="text-muted d-block w-100" style="font-size:11px;">
                                    e.g. 180 for six months, 365 for a year. Leave blank or 0 to never chase.
                                </small>
                            </form>
                        </div>
                        <span class="badge badge-{{ (int) $serviceRecallDays > 0 ? 'success' : 'secondary' }} px-3 py-2 flex-shrink-0">
                            {{ (int) $serviceRecallDays > 0 ? 'ON' : 'OFF' }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <div class="alert alert-info mt-3" style="font-size:12px;">
            <i class="tio-info-outined"></i>
            Turning an alert off stops the message only — records still appear in your panel
            (leads list, orders list, notification bell) as usual. Bulk campaigns and one-off sends are not affected;
            they are always started by you.
        </div>
    </div>
@endsection
