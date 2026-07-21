<div class="tab-pane fade show active" id="v-pills-notifications" role="tabpanel">
    <div class="container tab_inner">
        <div class="af-container-p9x2">
            <h2>Notifications</h2>
            <p class="af-subtitle-p9x2">
                Choose how MyChitti and the businesses you deal with can reach you. Turn anything
                off and it stops straight away.
            </p>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('dashboard.notification-preferences') }}" method="post">
                @csrf

                @foreach (\App\Models\UserNotificationPreference::CHANNELS as $key => $channel)
                    <div class="af-security-box-p9x2 mb-3">
                        <div class="d-flex align-items-center justify-content-between" style="gap:16px;">
                            <div>
                                <h4 class="mb-1">{{ $channel['label'] }}</h4>
                                <p class="mb-0" style="font-size:13px;color:#666;">{{ $channel['desc'] }}</p>
                            </div>
                            <div class="form-check form-switch" style="flex-shrink:0;">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       style="width:46px;height:24px;cursor:pointer;"
                                       id="notif-{{ $key }}" name="{{ $key }}" value="1"
                                       {{ ($notification_prefs[$key] ?? true) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="af-security-box-p9x2 mb-3" style="background:#f8f9fa;">
                    <p class="mb-0" style="font-size:13px;color:#666;">
                        <b>Always sent:</b> login and password-reset codes, and messages about a booking
                        or order you have placed. These keep your account working and cannot be turned off.
                    </p>
                </div>

                <div class="af-btn-row-p9x2 mt-3">
                    <button type="submit" class="af-action-btn-p9x2">Save preferences</button>
                </div>
            </form>
        </div>
    </div>
</div>
