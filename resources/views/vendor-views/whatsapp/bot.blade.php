@extends('layouts.vendor.app')

@section('title', 'WhatsApp Chatbot')

@push('css_or_js')
    <style>
        .wc-card { border:1px solid #eef0f4; border-radius:14px; background:#fff; overflow:hidden; margin-bottom:16px; }
        .wc-card-h { padding:16px 20px; border-bottom:1px solid #f1f3f7; font-weight:700; font-size:14px; color:#1e293b; }
        .wc-card-b { padding:20px; }
        .wc-opt { border:2px solid #eef0f4; border-radius:12px; padding:18px; height:100%; cursor:pointer; transition:border-color .15s; }
        .wc-opt:hover { border-color:#cfd6e4; }
        .wc-opt.is-on { border-color:#25d366; background:#f6fffa; }
        .wc-opt h6 { font-weight:700; margin-bottom:6px; }
        .wc-sub { font-size:12px; color:#8a94a6; }
        .wc-item { display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:1px dashed #eef0f4; }
        .wc-item:last-child { border-bottom:0; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <h1 class="page-header-title mb-0"><i class="tio-android"></i> WhatsApp Chatbot</h1>
            <a href="{{ route('vendor.whatsapp.billing') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-wallet"></i> Plan & Billing
            </a>
        </div>

        {{-- ── Which bot answers ── --}}
        <div class="wc-card">
            <div class="wc-card-h">Which chatbot answers your customers</div>
            <div class="wc-card-b">
                <form action="{{ route('vendor.whatsapp.bot.mode') }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="wc-opt {{ $mode === 'knowledge' ? 'is-on' : '' }} d-block mb-0">
                                <input type="radio" name="mode" value="knowledge" {{ $mode === 'knowledge' ? 'checked' : '' }}>
                                <h6 class="d-inline ml-1">Normal chatbot</h6>
                                <div class="wc-sub mt-2">
                                    Answers customer questions from your Auto-Reply Knowledge — services, timings,
                                    prices, policies. It never touches leads, appointments or records.
                                </div>
                                <div class="wc-sub mt-2"><b>Included</b> with your WhatsApp plan. Runs on chatbot tokens.</div>
                            </label>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="wc-opt {{ $mode === 'agent' ? 'is-on' : '' }} d-block mb-0">
                                <input type="radio" name="mode" value="agent" {{ $mode === 'agent' ? 'checked' : '' }}>
                                <h6 class="d-inline ml-1">AI Agent</h6>
                                <div class="wc-sub mt-2">
                                    Everything the normal chatbot does, plus lead and appointment management —
                                    it books, reschedules and reports status, and shares exactly what you allow below.
                                </div>
                                <div class="wc-sub mt-2">
                                    @if ($agentActive)
                                        <span class="badge badge-soft-success">Plan active</span>
                                        @if ($agentSub) until {{ \Carbon\Carbon::parse($agentSub->current_period_end)->format('d M Y') }} @endif
                                    @else
                                        <b>Needs a plan:</b>
                                        @foreach ($agentPlans as $key => $plan)
                                            {{ $plan['label'] }} {{ _price($plan['price']) }} + {{ $gst }}% GST
                                            ({{ number_format($plan['tokens'] / 1000000, 0) }}M tokens){{ !$loop->last ? ' · ' : '' }}
                                        @endforeach
                                    @endif
                                </div>
                            </label>
                        </div>
                    </div>

                    @if (!$agentActive)
                        <div class="alert alert-info mb-3" style="font-size:13px;">
                            <i class="tio-info-outined"></i>
                            Pick an AI Agent plan on
                            <a href="{{ route('vendor.whatsapp.billing') }}" class="alert-link">Plan & Billing</a>
                            before switching. Without a live plan the normal chatbot keeps answering.
                        </div>
                    @endif

                    <button type="submit" class="btn btn--primary">Save chatbot type</button>
                </form>
            </div>
        </div>

        {{-- ── What the agent may send ── --}}
        <div class="wc-card">
            <div class="wc-card-h">What the AI Agent may send customers about their lead / appointment</div>
            <div class="wc-card-b">
                <p class="wc-sub mb-3">
                    Tick only what you are comfortable sending over WhatsApp. Anything unticked is never
                    given to the bot at all — it cannot share what it has not been shown, and it will tell
                    the customer your team will follow up instead.
                </p>
                <form action="{{ route('vendor.whatsapp.bot.shares') }}" method="post">
                    @csrf
                    @foreach ($shareItems as $key => $meta)
                        <label class="wc-item mb-0">
                            <input type="checkbox" name="items[]" value="{{ $key }}" class="mt-1"
                                   {{ !empty($shares[$key]) ? 'checked' : '' }}>
                            <span>
                                <b>{{ $meta['label'] }}</b>
                                <span class="wc-sub d-block">{{ $meta['desc'] }}</span>
                            </span>
                        </label>
                    @endforeach
                    <button type="submit" class="btn btn--primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    // Highlight the selected chatbot card as the vendor clicks.
    $(document).on('change', 'input[name="mode"]', function () {
        $('.wc-opt').removeClass('is-on');
        $(this).closest('.wc-opt').addClass('is-on');
    });
</script>
@endpush
