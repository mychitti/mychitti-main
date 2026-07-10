@extends('layouts.vendor.app')
@section('title', 'Notification Preferences')

@section('content')
<div class="content container-fluid school-page"> 
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-header-title mb-0"><i class="tio-notifications mr-1"></i> Notification Preferences</h1>
            <p class="text-muted mb-0 mt-1" style="font-size:13px;">Choose which notification channels to use for each school action.</p>
        </div>
        <a href="{{ route('vendor.school.settings.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-chevron-left"></i> Back to Settings</a>
    </div>

    <form action="{{ route('vendor.school.settings.notification-preferences.save') }}" method="POST" id="notifPrefForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="tio-tune mr-1 text-primary"></i> Channel Configuration</span>
                <div class="d-flex align-items-center" style="gap:24px;font-size:12px;">
                    <span class="text-muted font-weight-bold" style="min-width:54px;">Select All:</span>
                    <label class="notif-col-header" data-channel="whatsapp" title="Toggle all WhatsApp">
                        <input type="checkbox" class="select-all-channel" data-channel="whatsapp"> <span class="notif-channel-icon notif-wa"><i class="fa fa-whatsapp"></i></span>
                    </label>
                    <label class="notif-col-header" data-channel="sms" title="Toggle all SMS">
                        <input type="checkbox" class="select-all-channel" data-channel="sms"> <span class="notif-channel-icon notif-sms"><i class="fa fa-comment"></i></span>
                    </label>
                    <label class="notif-col-header" data-channel="push_notification" title="Toggle all Push">
                        <input type="checkbox" class="select-all-channel" data-channel="push_notification"> <span class="notif-channel-icon notif-push"><i class="fa fa-bell"></i></span>
                    </label>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0" id="notifTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="min-width:280px;">Action / Event</th>
                                <th class="text-center" style="width:100px;">
                                    <span class="notif-channel-icon notif-wa"><i class="fa fa-whatsapp"></i></span><br>
                                    <small class="text-muted">WhatsApp</small>
                                </th>
                                <th class="text-center" style="width:100px;">
                                    <span class="notif-channel-icon notif-sms"><i class="fa fa-comment"></i></span><br>
                                    <small class="text-muted">SMS</small>
                                </th>
                                <th class="text-center" style="width:100px;">
                                    <span class="notif-channel-icon notif-push"><i class="fa fa-bell"></i></span><br>
                                    <small class="text-muted">Push</small>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($actions as $key => $meta)
                                <tr class="notif-row">
                                    <td>
                                        <div class="d-flex align-items-center" style="gap:12px;">
                                            <div class="notif-action-icon">
                                                <i class="{{ $meta['icon'] }}"></i>
                                            </div>
                                            <div>
                                                <span class="font-weight-bold d-block">{{ $meta['label'] }}</span>
                                                <small class="text-muted" style="font-size:11px;line-height:1.3;">{{ $meta['desc'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <label class="notif-toggle">
                                            <input type="hidden" name="prefs[{{ $key }}][whatsapp]" value="0">
                                            <input type="checkbox" name="prefs[{{ $key }}][whatsapp]" value="1"
                                                class="notif-check channel-whatsapp"
                                                @checked($prefs[$key]['whatsapp'] ?? false)>
                                            <span class="notif-slider notif-slider-wa"></span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="notif-toggle">
                                            <input type="hidden" name="prefs[{{ $key }}][sms]" value="0">
                                            <input type="checkbox" name="prefs[{{ $key }}][sms]" value="1"
                                                class="notif-check channel-sms"
                                                @checked($prefs[$key]['sms'] ?? false)>
                                            <span class="notif-slider notif-slider-sms"></span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="notif-toggle">
                                            <input type="hidden" name="prefs[{{ $key }}][push_notification]" value="0">
                                            <input type="checkbox" name="prefs[{{ $key }}][push_notification]" value="1"
                                                class="notif-check channel-push_notification"
                                                @checked($prefs[$key]['push_notification'] ?? false)>
                                            <span class="notif-slider notif-slider-push"></span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn--primary px-4"><i class="tio-save mr-1"></i> Save Preferences</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body py-3">
            <div class="d-flex align-items-start" style="gap:12px;">
                <i class="tio-info text-info" style="font-size:22px;margin-top:2px;"></i>
                <div>
                    <span class="font-weight-bold d-block mb-1">How it works</span>
                    <small class="text-muted" style="line-height:1.5;">
                        Enable the channels you want for each action. When the action occurs (e.g. a student is marked absent),
                        the system will automatically send notifications through the enabled channels.<br>
                        <b>WhatsApp</b> requires WhatsApp Business API setup. <b>SMS</b> uses your configured SMS gateway.
                        <b>Push Notifications</b> are sent to the parent's mobile app.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css_or_js')
<style>
    /* Toggle switch */
    .notif-toggle { position:relative; display:inline-block; width:44px; height:24px; margin:0; cursor:pointer; }
    .notif-toggle input { opacity:0; width:0; height:0; }
    .notif-slider { position:absolute; top:0; left:0; right:0; bottom:0; background:#dde1e6; border-radius:24px; transition:.25s; }
    .notif-slider:before { content:''; position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.25s; box-shadow:0 1px 3px rgba(0,0,0,.15); }
    .notif-toggle input:checked + .notif-slider-wa  { background:#25d366; }
    .notif-toggle input:checked + .notif-slider-sms { background:#3b82f6; }
    .notif-toggle input:checked + .notif-slider-push{ background:#f59e0b; }
    .notif-toggle input:checked + .notif-slider:before { transform:translateX(20px); }

    /* Channel icons */
    .notif-channel-icon { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; font-size:14px; color:#fff; }
    .notif-wa   { background:#25d366; }
    .notif-sms  { background:#3b82f6; }
    .notif-push { background:#f59e0b; }

    /* Action icon */
    .notif-action-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#eef2ff,#e0e7ff); display:flex; align-items:center; justify-content:center; font-size:17px; color:#4f46e5; flex-shrink:0; }

    /* Row hover */
    .notif-row { transition:background .15s; }
    .notif-row:hover { background:#f8fafc; }

    /* Col header */
    .notif-col-header { display:inline-flex; align-items:center; gap:6px; cursor:pointer; margin:0; }
    .notif-col-header input { margin:0; }
</style>
@endpush

@push('script_2')
<script>
    // Select-all per channel
    document.querySelectorAll('.select-all-channel').forEach(function(cb) {
        var channel = cb.dataset.channel;
        // Init state
        var allChecks = document.querySelectorAll('.channel-' + channel);
        cb.checked = Array.from(allChecks).every(function(c) { return c.checked; });

        cb.addEventListener('change', function() {
            allChecks.forEach(function(c) { c.checked = cb.checked; });
        });
    });

    // Update select-all when individual toggles change
    document.querySelectorAll('.notif-check').forEach(function(check) {
        check.addEventListener('change', function() {
            ['whatsapp', 'sms', 'push_notification'].forEach(function(ch) {
                var all = document.querySelectorAll('.channel-' + ch);
                var selectAll = document.querySelector('.select-all-channel[data-channel="' + ch + '"]');
                if (selectAll) selectAll.checked = Array.from(all).every(function(c) { return c.checked; });
            });
        });
    });
</script>
@endpush
