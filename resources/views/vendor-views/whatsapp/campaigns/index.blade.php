@extends('layouts.vendor.app')

@section('title', 'WhatsApp Campaign Series')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-send"></i> Campaign Series</h1>
                <span class="wa-sub">
                    Send an offer with <b>Interested</b> / <b>Not interested</b> buttons, then follow up only with the
                    people who didn’t say no.
                </span>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="tio-chat"></i> One-off message
                </a>
                @if ($connected)
                    <a href="{{ route('vendor.whatsapp.campaigns.create') }}" class="btn btn-sm btn--primary">
                        <i class="tio-add"></i> New campaign
                    </a>
                @endif
            </div>
        </div>

        @if (!$connected)
            <div class="wa-card">
                <div class="wa-empty">
                    <i class="tio-send-outlined"></i>
                    <div class="wa-empty-t">Campaigns need your own WhatsApp number</div>
                    <div class="wa-empty-s mb-3">
                        A series goes out from your own WhatsApp Business number, using your own approved templates.
                    </div>
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary">Connect WhatsApp</a>
                </div>
            </div>
        @else
            @if (!$active)
                <div class="alert alert-warning" style="font-size:13px;">
                    Your WhatsApp subscription isn’t active, so nothing will send. You can still build a campaign —
                    <a href="{{ route('vendor.whatsapp.billing') }}">activate your plan</a> to start it.
                </div>
            @endif

            <div class="wa-card mb-3">
                <div class="wa-card-h">
                    How a series works
                    <span class="wa-sub">Automatic — you build it once</span>
                </div>
                <div class="wa-card-b">
                    <div class="row" style="row-gap:12px;">
                        <div class="col-md-3">
                            <div class="wa-eyebrow mb-1">Step 1</div>
                            <div style="font-size:13px;">
                                Your offer goes to the whole audience, with quick-reply buttons on the template.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="wa-eyebrow mb-1">They answer</div>
                            <div style="font-size:13px;">
                                A tap on <b>Not interested</b> drops that person from the rest of the series, for good.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="wa-eyebrow mb-1">Step 2 onwards</div>
                            <div style="font-size:13px;">
                                Goes to the interested <em>and</em> the silent — no reply is not a refusal.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="wa-eyebrow mb-1">Tracking</div>
                            <div style="font-size:13px;">
                                Every step shows sent, delivered, read and what came back, per person.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wa-card">
                <div class="wa-card-h">
                    Your campaigns
                    <span class="wa-sub">{{ $campaigns->total() }} total</span>
                </div>
                <div class="wa-card-b flush">
                    @if ($campaigns->isEmpty())
                        <div class="wa-empty">
                            <i class="tio-send-outlined"></i>
                            <div class="wa-empty-t">No campaigns yet</div>
                            <div class="wa-empty-s mb-3">Build your first series — an offer, then two follow-ups.</div>
                            <a href="{{ route('vendor.whatsapp.campaigns.create') }}" class="btn btn-sm btn--primary">
                                Create a campaign
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table wa-table">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Audience</th>
                                        <th class="text-center">Interested</th>
                                        <th class="text-center">Not interested</th>
                                        <th class="text-center">No reply</th>
                                        <th>Next step</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($campaigns as $campaign)
                                        @php
                                            $s = $summaries[$campaign->id];
                                            $next = $nextSteps[$campaign->id] ?? null;
                                            $chip = [
                                                'draft'     => 'secondary',
                                                'running'   => 'success',
                                                'paused'    => 'warning',
                                                'completed' => 'info',
                                                'cancelled' => 'danger',
                                            ][$campaign->status] ?? 'secondary';
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('vendor.whatsapp.campaigns.show', $campaign->id) }}"
                                                   class="font-weight-bold">{{ $campaign->name }}</a>
                                                <div class="wa-sub">
                                                    <span class="wa-chip badge-soft-{{ $chip }}">{{ ucfirst($campaign->status) }}</span>
                                                    {{ $s['recipients'] }} {{ $s['recipients'] == 1 ? 'person' : 'people' }} ·
                                                    {{ $s['sent'] }} sent
                                                </div>
                                                @if ($campaign->last_error)
                                                    <div class="wa-sub text-warning">{{ $campaign->last_error }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $campaign->audience === 'platform' ? 'MyChitti customers' : 'My customers' }}
                                            </td>
                                            <td class="text-center text-success font-weight-bold">{{ $s['interested'] }}</td>
                                            <td class="text-center text-danger font-weight-bold">{{ $s['not_interested'] }}</td>
                                            <td class="text-center">{{ $s['silent'] }}</td>
                                            <td>
                                                @if ($next)
                                                    Step {{ $next->step_no }} —
                                                    @if ($next->due_at)
                                                        {{ \Carbon\Carbon::parse($next->due_at)->isPast() ? 'due now' : \Carbon\Carbon::parse($next->due_at)->diffForHumans() }}
                                                    @else
                                                        waiting for you to start
                                                    @endif
                                                @else
                                                    <span class="wa-sub">Series finished</span>
                                                @endif
                                            </td>
                                            <td class="text-right text-nowrap">
                                                <a href="{{ route('vendor.whatsapp.campaigns.show', $campaign->id) }}"
                                                   class="btn btn-sm btn-outline-secondary">Open</a>
                                                @if (in_array($campaign->status, ['draft', 'paused']))
                                                    <form action="{{ route('vendor.whatsapp.campaigns.start', $campaign->id) }}"
                                                          method="post" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-sm btn--primary">
                                                            {{ $campaign->status === 'paused' ? 'Resume' : 'Start' }}
                                                        </button>
                                                    </form>
                                                @elseif ($campaign->status === 'running')
                                                    <form action="{{ route('vendor.whatsapp.campaigns.pause', $campaign->id) }}"
                                                          method="post" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-warning">Pause</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-3 py-2">{!! $campaigns->links() !!}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
