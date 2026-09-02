@extends('layouts.vendor.app')

@section('title', 'Send Notification Settings')

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
                <i class="tio-send"></i>
                Send Notifications
            </h1>
            <div>
                {{-- "Is it actually working?" is the question this page can't answer on its own. --}}
                <a href="{{ route('vendor.whatsapp.message-log') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="tio-history"></i> Message log
                </a>
            </div>
        </div>

        <p class="text-muted" style="font-size:13px;">
            Choose which automatic messages your store sends to customers, per action and channel.
        </p>

        {{-- Twenty switches that physically cannot fire are worse than two setup steps: the vendor
             turns them all on, nothing arrives, and they conclude the feature is broken. Until the
             account can send at all, this page IS the two steps. --}}
        @php($needsSetup = $activeTab === 'whatsapp' && (!$waState['connected'] || !$waState['subscription']))

        @if ($needsSetup)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-1" style="font-size:15px;">Two steps before any of this can send</h5>
                    <p class="text-muted" style="font-size:13px;">
                        WhatsApp only delivers business messages from your own connected number, on an active plan.
                        Your settings below are saved either way — they just can't fire yet.
                    </p>

                    <div class="d-flex align-items-center py-2 border-top" style="gap:10px;">
                        <span class="badge badge-{{ $waState['connected'] ? 'success' : 'secondary' }} px-2 py-1">1</span>
                        <div style="min-width:0; flex:1;">
                            <b style="font-size:13px;">Connect your WhatsApp number</b>
                            <small class="text-muted d-block" style="font-size:12px;">
                                {{ $waState['connected'] ? 'Done — your own number is linked.' : 'Messages send from your business number, not a shared one.' }}
                            </small>
                        </div>
                        @if (!$waState['connected'])
                            <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary flex-shrink-0">Connect</a>
                        @else
                            <i class="tio-checkmark-circle text-success flex-shrink-0"></i>
                        @endif
                    </div>

                    <div class="d-flex align-items-center py-2 border-top" style="gap:10px;">
                        <span class="badge badge-{{ $waState['subscription'] ? 'success' : 'secondary' }} px-2 py-1">2</span>
                        <div style="min-width:0; flex:1;">
                            <b style="font-size:13px;">Activate your WhatsApp plan</b>
                            <small class="text-muted d-block" style="font-size:12px;">
                                {{ $waState['subscription'] ? 'Done — your plan is active.' : 'Meta charges per conversation, so the plan has to be running.' }}
                            </small>
                        </div>
                        @if (!$waState['subscription'])
                            <a href="{{ route('vendor.whatsapp.billing') }}" class="btn btn-sm btn--primary flex-shrink-0">Activate</a>
                        @else
                            <i class="tio-checkmark-circle text-success flex-shrink-0"></i>
                        @endif
                    </div>
                </div>
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
                                @if (hasPermission('whatsapp_automation', 'edit'))
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
                            @endif
                            @if ($key === 'hmis_followup')
                                @if (hasPermission('whatsapp_automation', 'edit'))
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
                            @endif

                            {{-- The whole chain in one line: which template will actually go out,
                                 whether Meta will accept it, and the one thing that fixes it if
                                 not. A vendor used to have to visit three more screens to learn
                                 this, which is why toggles got flipped and nothing ever sent. --}}
                            @php($state = $item['readiness'] ?? null)
                            {{-- Either the row's own blocking problem, or — when it is simply
                                 switched off — the problem that would greet them if they switched
                                 it on. Same wording either way. --}}
                            @php($settled = [\App\Services\MessageReadiness::LIVE, \App\Services\MessageReadiness::OFF])
                            @php($note = $state['warning'] ?? null)
                            @php($note = $note ?: (($state && !in_array($state['state'], $settled, true)) ? $state : null))
                            @if ($note)
                                <small class="d-block text-{{ $note['tone'] === 'warning' ? 'warning' : ($note['tone'] === 'info' ? 'info' : 'danger') }}" style="font-size:11px;">
                                    <i class="tio-warning"></i> {{ $note['reason'] }}
                                    @if ($note['action'] && $note['url'])
                                        <a href="{{ $note['url'] }}">{{ $note['action'] }}</a>
                                    @endif
                                </small>
                            @endif

                            {{-- Swapping in your own template used to live on its own page under
                                 Automation. It is a property of this one message, so it belongs on
                                 this row — and the vendor never has to learn what a "role" is. --}}
                            @if ($state && $state['role'] && $waState['connected'])
                                <details class="mt-1">
                                    <summary class="text-muted" style="font-size:11px; cursor:pointer;">
                                        Template: <code>{{ $state['template'] }}</code>{{ $state['bound'] ? ' (yours)' : '' }}
                                    </summary>
                                    @if (hasPermission('whatsapp_automation', 'edit'))
                                    <form action="{{ route('vendor.whatsapp.template-roles.save') }}" method="post"
                                          class="d-flex align-items-center flex-wrap mt-1 mb-0" style="gap:6px;">
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $state['role'] }}">
                                        <select name="template" class="form-control form-control-sm" style="max-width:230px;">
                                            <option value="">Suggested ({{ $state['suggested'] }})</option>
                                            @foreach ($approved as $tplName)
                                                <option value="{{ $tplName }}" {{ strtolower($tplName) === strtolower($state['template']) && $state['bound'] ? 'selected' : '' }}>
                                                    {{ $tplName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary py-0">Use this</button>
                                        <small class="text-muted d-block w-100" style="font-size:11px;">
                                            Only templates with exactly the values this message sends are accepted — you'll be told if one doesn't fit.
                                        </small>
                                    </form>
                                    @endif
                                </details>
                            @endif
                        </div>
                        <div class="d-flex align-items-center flex-shrink-0" style="gap:8px;">
                            @if ($state)
                                <span class="badge badge-soft-{{ $state['tone'] }} px-2 py-1" style="font-size:11px;"
                                      title="{{ $state['reason'] }}">{{ $state['chip'] }}</span>
                            @endif
                            @if (hasPermission('whatsapp_automation', 'edit'))
                            <form action="{{ route('vendor.notification-settings.toggle') }}" method="post" class="mb-0">
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
                            @endif
                        </div>
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
                            @if (hasPermission('whatsapp_templates', 'edit'))
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
                            @endif
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
                            @if (hasPermission('whatsapp_automation', 'edit'))
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
                            @endif
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
