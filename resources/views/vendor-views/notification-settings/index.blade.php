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
