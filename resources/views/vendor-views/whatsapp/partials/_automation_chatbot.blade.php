<div class="wc">
        @php
            $planMeta = $plans[$currentPlan];
            $endsOn   = $subscription && $subscription->current_period_end
                ? \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y')
                : null;
        @endphp


        {{-- ── Is the agent live? ────────────────────────────────────── --}}
        <div class="wc-card">
            <div class="wc-card-b">
                @if ($agentActive)
                    <div class="wc-live">
                        <span class="wc-live-i"><i class="tio-android"></i></span>
                        <div>
                            <div class="wc-live-t">
                                Your AI Agent is answering customers
                                <span class="wc-chip wc-chip-ok ml-2"><i class="tio-checkmark-circle"></i> Live</span>
                            </div>
                            <div class="wc-sub mt-1">
                                On <b>{{ $planMeta['label'] }}</b>@if ($endsOn), active until {{ $endsOn }}@endif.
                                It answers from your
                                <a href="{{ route('vendor.whatsapp.automation', ['tab' => 'knowledge']) }}">Auto-Reply Knowledge</a>
                                and handles leads and appointments — within the limits you set below.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="wc-live off">
                        <span class="wc-live-i"><i class="tio-remove-circle"></i></span>
                        <div>
                            <div class="wc-live-t">
                                {{ $hasPlan ? 'No chatbot on ' . $planMeta['label'] : 'No plan yet, so no chatbot' }}
                                <span class="wc-chip wc-chip-off ml-2">Off</span>
                            </div>
                            <div class="wc-sub mt-1">
                                Incoming messages go straight to your team in the
                                @if (hasAnyModulePermission(['whatsapp_inbox']))<a href="{{ route('vendor.whatsapp.inbox') }}">Inbox</a>@else<b>Inbox</b>@endif — nothing is answered
                                automatically. Move to <b>AI Agent Starter</b> or <b>Pro</b> on
                                @if (hasAnyModulePermission(['whatsapp_billing']))<a href="{{ route('vendor.whatsapp.billing') }}">Plan &amp; Billing</a>@else<b>Plan &amp; Billing</b>@endif to switch it on.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Permissions ───────────────────────────────────────────── --}}
        <div class="wc-card">
            <div class="wc-card-h">
                <div>
                    <h2 class="wc-card-t">What your AI Agent may do</h2>
                    <span class="wc-sub">Tick only what you are comfortable with — anything unticked never reaches the bot at all.</span>
                </div>
            </div>
            <div class="wc-card-b">
                @if (!$agentActive)
                    <div class="wc-empty">
                        <i class="tio-lock-outlined"></i>
                        <div style="font-weight:650;color:var(--wc-ink-2);font-size:13px;">Nothing to configure yet</div>
                        <div class="wc-sub mt-1" style="max-width:340px;margin:0 auto;">
                            These settings apply once you are on a plan that includes the AI Agent.
                        </div>
                    </div>
                @else
                    @if (hasPermission('whatsapp_automation', 'edit'))
                    <form action="{{ route('vendor.whatsapp.bot.shares') }}" method="post">
                        @csrf
                        @foreach ($shareItems as $key => $meta)
                            <label class="wc-item {{ $key === 'booking' ? 'is-action' : '' }}">
                                <input type="checkbox" name="items[]" value="{{ $key }}"
                                       {{ !empty($shares[$key]) ? 'checked' : '' }}>
                                <span>
                                    <span class="wc-item-l">{{ $meta['label'] }}</span>
                                    <span class="wc-item-d">{{ $meta['desc'] }}</span>
                                </span>
                            </label>
                        @endforeach

                        <div class="wc-note mt-3">
                            The agent can only repeat what it has been shown. An unticked item is never put in
                            front of the model, so it cannot be leaked by a cleverly worded question — the bot
                            simply tells the customer your team will follow up.
                        </div>

                        <button type="submit" class="btn btn--primary mt-3">
                            <i class="tio-save"></i> Save settings
                        </button>
                    </form>
                    @endif
                @endif
            </div>
        </div>
</div>
