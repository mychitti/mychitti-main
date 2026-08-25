@extends('layouts.vendor.app')

@section('title', 'WhatsApp Templates')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    <style>
        .wa-pv { background:#d9fdd3; border-radius:10px; padding:10px 12px; font-size:13px; line-height:1.55; }
        .wa-pv-hdr { font-weight:700; margin-bottom:6px; }
        .wa-pv-img { display:block; max-width:100%; border-radius:8px; margin-bottom:4px; }
        .wa-pv-media { display:flex; align-items:center; gap:8px; background:rgba(0,0,0,.05);
            border-radius:8px; padding:10px 12px; margin-bottom:8px; font-size:12px; color:#5a6472; }
        .wa-pv-note { display:block; margin-bottom:8px; font-size:11px; color:#667085; }
        .wa-pv-body { white-space:pre-wrap; word-break:break-word; }
        .wa-pv-ftr { margin-top:8px; font-size:11px; font-style:italic; color:#667085; }
        .wa-pv-btns { margin-top:8px; padding-top:8px; border-top:1px solid rgba(0,0,0,.08);
            display:flex; flex-direction:column; gap:6px; }
        .wa-pv-btn { background:#fff; border:1px solid #e6e9ef; border-radius:8px; padding:7px 10px;
            text-align:center; color:#128c7e; font-weight:600; font-size:12.5px; }
        .wa-pv-btn small { display:block; font-weight:400; color:var(--wa-mute); word-break:break-all; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid wa-page">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-receipt"></i> Message Templates</h1>
                <span class="wa-sub">WhatsApp only delivers business-initiated messages from a template Meta has approved.</span>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                @if ($connected)
                    <span class="wa-chip badge-soft-{{ $quota['used'] >= $quota['allowance'] ? 'danger' : 'secondary' }}">
                        {{ $quota['used'] }} / {{ $quota['allowance'] }} slots used
                    </span>
                @endif
                <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-refresh"></i> Refresh</a>
                <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn-outline-primary"><i class="tio-chat"></i> Connection</a>
            </div>
        </div>

        @if (!$connected)
            <div class="wa-card">
                <div class="wa-empty">
                    <i class="tio-receipt-outlined"></i>
                    <div class="wa-empty-t">Templates need a connected number</div>
                    <div class="wa-empty-s mb-3">Templates are approved against your own WhatsApp Business Account.</div>
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary">Connect WhatsApp</a>
                </div>
            </div>
        @else
            @if (session('wa_create_result'))
                @php $r = session('wa_create_result'); @endphp
                @if ($r['success'])
                    <div class="alert alert-success d-flex align-items-start" style="gap:12px;">
                        <i class="tio-checkmark-circle" style="font-size:24px;line-height:1.2;"></i>
                        <div>
                            <h6 class="mb-1">Template submitted for review</h6>
                            <div style="font-size:13px;">
                                Meta is now reviewing your template. Approval usually takes a few minutes but can
                                take up to 24 hours — you can send with it once its status shows <b>APPROVED</b>.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger d-flex align-items-start" style="gap:12px;">
                        <i class="tio-clear-circle" style="font-size:24px;line-height:1.2;"></i>
                        <div style="min-width:0;flex:1;">
                            <h6 class="mb-1">Template could not be submitted</h6>
                            <div style="font-size:13px;">{{ $r['error'] }}</div>
                        </div>
                    </div>
                @endif
            @endif
            {{-- Four distinct jobs — reviewing what exists, picking a ready-made one, writing a
                 new one, and clearing out old ones. Tabs keep each one whole. --}}
            <ul class="nav wa-tabs mb-3" role="tablist" style="background:#fff;border:1px solid var(--wa-line);border-radius:14px;">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tplList" role="tab">
                        <i class="tio-folder-outlined"></i> Your templates
                        <span class="wa-chip badge-soft-secondary ml-1">{{ count($templates) }}</span>
                    </a>
                </li>
                @if ($presets->isNotEmpty())
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tplPresets" role="tab">
                            <i class="tio-star-outlined"></i> Suggested
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tplCreate" role="tab">
                        <i class="tio-add-circle-outlined"></i> Create new
                    </a>
                </li>
                @if (!empty($trashed))
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tplTrash" role="tab">
                            <i class="tio-delete-outlined"></i> Trash
                            <span class="wa-chip badge-soft-secondary ml-1">{{ count($trashed) }}</span>
                        </a>
                    </li>
                @endif
            </ul>

            <div class="tab-content">
            <div class="tab-pane fade show active" id="tplList" role="tabpanel">
                    <div class="wa-card">
                        @if ($templateError)
                            <div class="wa-card-b pb-0">
                                <div class="alert alert-warning mb-0" style="font-size:13px;">{{ $templateError }}</div>
                            </div>
                        @endif
                            <div class="table-responsive">
                                <table class="table wa-table">
                                    <thead>
                                        <tr>
                                            <th>Template</th>
                                            <th>Category</th>
                                            <th>Language</th>
                                            <th>Status</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($templates as $tpl)
                                            @php
                                                $st = strtoupper($tpl['status'] ?? '');
                                                $bodyText = ''; $btnText = ''; $btnUrl = ''; $btnPhone = ''; $btnPhoneText = '';
                                                foreach (($tpl['components'] ?? []) as $cmp) {
                                                    if (($cmp['type'] ?? '') === 'BODY') {
                                                        $bodyText = $cmp['text'] ?? '';
                                                    } elseif (($cmp['type'] ?? '') === 'BUTTONS') {
                                                        // First of each kind: Meta allows only one call button, and the
                                                        // edit modal has one row to put each of them back into.
                                                        foreach (($cmp['buttons'] ?? []) as $b) {
                                                            $bType = $b['type'] ?? '';
                                                            if ($bType === 'URL' && $btnUrl === '') {
                                                                $btnText = $b['text'] ?? ''; $btnUrl = $b['url'] ?? '';
                                                            } elseif ($bType === 'PHONE_NUMBER' && $btnPhone === '') {
                                                                $btnPhoneText = $b['text'] ?? ''; $btnPhone = $b['phone_number'] ?? '';
                                                            }
                                                        }
                                                    }
                                                }
                                                $editable = in_array($st, ['APPROVED', 'REJECTED', 'PAUSED']);
                                            @endphp
                                            <tr>
                                                <td style="max-width:340px;">
                                                    <b>{{ $tpl['name'] ?? '' }}</b>
                                                    {{-- The body is what the vendor actually recognises a
                                                         template by — the name is just a slug. --}}
                                                    <div class="text-muted" style="font-size:12px;line-height:1.5;">
                                                        {{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $bodyText), 90) }}
                                                    </div>
                                                </td>
                                                <td><span class="wa-chip badge-soft-secondary">{{ $tpl['category'] ?? '' }}</span></td>
                                                <td class="text-muted">{{ $tpl['language'] ?? '' }}</td>
                                                <td>
                                                    <span class="wa-chip badge-soft-{{ $st == 'APPROVED' ? 'success' : ($st == 'REJECTED' ? 'danger' : 'warning') }}">{{ $st ?: '—' }}</span>
                                                    @if ($st === 'PENDING')
                                                        <small class="text-muted d-block mt-1" style="font-size:11px;">Under review — up to 24h</small>
                                                    @endif
                                                </td>
                                                <td class="text-right text-nowrap">
                                                    {{-- The whole component list, not the handful the edit
                                                         modal can put back: the preview has to show the
                                                         message Meta approved, header and buttons included. --}}
                                                    <button type="button" class="btn btn-sm btn-outline-secondary wa-tpl-view"
                                                            data-name="{{ $tpl['name'] ?? '' }}" data-category="{{ $tpl['category'] ?? '' }}"
                                                            data-language="{{ $tpl['language'] ?? '' }}" data-status="{{ $st }}"
                                                            data-components="{{ json_encode($tpl['components'] ?? []) }}">
                                                        <i class="tio-visible-outlined"></i>
                                                    </button>
                                                    @if ($editable)
                                                        <button type="button" class="btn btn-sm btn-outline-primary wa-tpl-edit"
                                                                data-id="{{ $tpl['id'] ?? '' }}" data-name="{{ $tpl['name'] ?? '' }}"
                                                                data-category="{{ $tpl['category'] ?? '' }}" data-body="{{ $bodyText }}"
                                                                data-btntext="{{ $btnText }}" data-btnurl="{{ $btnUrl }}"
                                                                data-btnphone="{{ $btnPhone }}" data-btnphonetext="{{ $btnPhoneText }}">
                                                            <i class="tio-edit"></i>
                                                        </button>
                                                    @endif
                                                    {{-- Two very different outcomes hide behind one
                                                         icon, so the choice is made explicitly. --}}
                                                    <button type="button" class="btn btn-sm btn-outline-danger wa-tpl-delete"
                                                            data-name="{{ $tpl['name'] ?? '' }}"
                                                            data-language="{{ $tpl['language'] ?? 'en_US' }}"
                                                            title="Delete">
                                                        <i class="tio-delete"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5">
                                                    <div class="wa-empty">
                                                        <i class="tio-receipt-outlined"></i>
                                                        <div class="wa-empty-t">No templates yet</div>
                                                        <div class="wa-empty-s">Pick a ready-made one from <b>Suggested</b>, or write your own under <b>Create new</b>.</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                    </div>
            </div>

            {{-- ── Trash ─────────────────────────────────────────────── --}}
            @if (!empty($trashed))
                    <div class="tab-pane fade" id="tplTrash" role="tabpanel">
                        <div class="wa-card">
                                <div class="wa-card-b">
                                    <div class="alert alert-info mb-3" style="font-size:12.5px;">
                                        These are hidden from your template list and from bulk sending, but they still
                                        exist at Meta and <b>still use a template slot</b>. Restore is instant — no new
                                        review — because nothing was deleted. Deleting permanently removes it from Meta
                                        and frees the slot, and cannot be undone.
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table wa-table">
                                            <tbody>
                                                @foreach ($trashed as $tpl)
                                                    <tr>
                                                        <td>
                                                            <b>{{ $tpl['name'] ?? '' }}</b>
                                                            <small class="text-muted d-block">{{ $tpl['language'] ?? '' }} · {{ $tpl['category'] ?? '' }}</small>
                                                        </td>
                                                        <td class="text-right text-nowrap">
                                                            <form action="{{ route('vendor.whatsapp.templates.restore') }}" method="post" class="d-inline">
                                                                @csrf
                                                                <input type="hidden" name="name" value="{{ $tpl['name'] ?? '' }}">
                                                                <input type="hidden" name="language" value="{{ $tpl['language'] ?? 'en_US' }}">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                                    <i class="tio-refresh"></i> Restore
                                                                </button>
                                                            </form>
                                                            <form action="{{ route('vendor.whatsapp.templates.delete') }}" method="post" class="d-inline"
                                                                  onsubmit="return confirm('Permanently delete “{{ $tpl['name'] ?? '' }}” from Meta? This cannot be undone — recreating it means a fresh review.');">
                                                                @csrf
                                                                <input type="hidden" name="name" value="{{ $tpl['name'] ?? '' }}">
                                                                <input type="hidden" name="language" value="{{ $tpl['language'] ?? 'en_US' }}">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="tio-delete"></i> Delete permanently
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                        </div>
                    </div>
            @endif

            {{-- ── Suggested presets ─────────────────────────────────── --}}
            @if ($presets->isNotEmpty())
                    <div class="tab-pane fade" id="tplPresets" role="tabpanel">
                        <div class="wa-card">
                            <div class="wa-card-h">Ready-made templates</div>
                            <div class="wa-card-b">
                                <p class="wa-sub mb-3">
                                    Written and pre-tested by MyChitti. One click submits it to Meta on
                                    <b>your</b> WhatsApp account — once approved, you can send with it.
                                </p>
                                <div class="row">
                                @foreach ($presets as $preset)
                                    <div class="col-md-6 wa-col">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start" style="gap:10px;">
                                            <div style="min-width:0;">
                                                <b style="font-size:14px;">{{ $preset->title }}</b>
                                                <div class="wa-sub"><code style="font-size:11px;">{{ $preset->name }}</code> · {{ $preset->category }}</div>
                                            </div>
                                            @if ($preset->waba_status === 'APPROVED')
                                                <span class="wa-chip badge-soft-success">APPROVED</span>
                                            @elseif ($preset->waba_status === 'PENDING')
                                                <span class="wa-chip badge-soft-warning" title="Meta is reviewing this template — up to 24 hours.">PENDING</span>
                                            @elseif ($preset->waba_status)
                                                <span class="wa-chip badge-soft-danger">{{ $preset->waba_status }}</span>
                                            @endif
                                        </div>

                                        {{-- Shown the way the customer will see it, so the vendor is
                                             choosing a message rather than a row in a list. --}}
                                        <div class="mt-2 p-2 rounded" style="background:#d9fdd3;font-size:12.5px;line-height:1.5;white-space:pre-wrap;">{{ $preset->body }}
                                            @if ($preset->footer)<div class="text-muted mt-1" style="font-size:11px;font-style:italic;">{{ $preset->footer }}</div>@endif
                                        </div>

                                        {{-- Buttons are half of what some templates do — a feedback
                                             request previewed without its answers reads as a
                                             question nobody can answer. --}}
                                        @php
                                            $presetReplies = array_filter(array_map('trim', explode(',', (string) ($preset->btn_replies ?? ''))));
                                        @endphp
                                        @if (!empty($presetReplies) || ($preset->btn_text && $preset->btn_url))
                                            <div class="d-flex flex-wrap mt-1" style="gap:4px;">
                                                @foreach ($presetReplies as $reply)
                                                    <span class="wa-chip badge-soft-secondary" style="background:#fff;border:1px solid #e6e9ef;color:#128c7e;">{{ $reply }}</span>
                                                @endforeach
                                                @if ($preset->btn_text && $preset->btn_url)
                                                    <span class="wa-chip badge-soft-secondary" style="background:#fff;border:1px solid #e6e9ef;color:#128c7e;">
                                                        <i class="tio-link"></i> {{ $preset->btn_text }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        @if (!empty($preset->used_for))
                                            <div class="wa-sub mt-2">{{ $preset->used_for }}</div>
                                        @endif

                                        @if (!$preset->waba_status)
                                            <form action="{{ route('vendor.whatsapp.templates.use-preset') }}" method="post" class="mb-0 mt-2">
                                                @csrf
                                                <input type="hidden" name="preset_id" value="{{ $preset->id }}">
                                                <button type="submit" class="btn btn-sm btn--primary btn-block">Use this template</button>
                                            </form>
                                        @elseif (!in_array($preset->waba_status, ['APPROVED', 'PENDING']))
                                            {{-- Meta refuses a second template of the same name while the refused
                                                 one still exists, so "use it again" is not offered — deleting the
                                                 old one is the actual next step and there is nothing else to try. --}}
                                            <small class="d-block mt-2 text-danger" style="font-size:11px;">
                                                <i class="tio-info-outined"></i>
                                                Meta refused this one. Delete it under <b>Your templates</b> first — then
                                                this card offers it again, with the corrected wording.
                                            </small>
                                        @endif
                                        @if ($preset->name === \App\Services\WhatsAppService::DEFAULT_WELCOME_TEMPLATE)
                                            <small class="d-block mt-1 text-info" style="font-size:11px;">
                                                <i class="tio-flash"></i> Once approved, this is sent automatically to every new customer you add.
                                            </small>
                                        @endif
                                        @if ($preset->name === \App\Services\WhatsAppService::DEFAULT_APPT_REMINDER_TEMPLATE)
                                            <small class="d-block mt-1 text-info" style="font-size:11px;">
                                                <i class="tio-flash"></i> Once approved, reminders are sent automatically for your scheduled appointments.
                                            </small>
                                            <form action="{{ route('vendor.whatsapp.templates.reminder-schedule') }}" method="post"
                                                  class="d-flex align-items-center flex-wrap mt-2" style="gap:6px;">
                                                @csrf
                                                <label class="mb-0 text-muted" style="font-size:12px;">Send reminder</label>
                                                <input type="number" name="hours" class="form-control form-control-sm" style="width:70px;"
                                                       min="0" max="168" value="{{ $apptReminder }}">
                                                <span class="text-muted" style="font-size:12px;">hour(s) before the appointment</span>
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                                <small class="text-muted d-block w-100" style="font-size:11px;">
                                                    {{ $apptReminder > 0 ? 'Currently: about ' . $apptReminder . ' hour(s) before.' : 'Currently off — set the hours and save to enable.' }}
                                                    Set 0 to turn off. Max 168 (7 days).
                                                </small>
                                            </form>
                                        @endif
                                    </div>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
            @endif

            {{-- ── Create ────────────────────────────────────────────── --}}
            @php $quotaFull = $quota['used'] >= $quota['allowance']; @endphp
            <div class="tab-pane fade" id="tplCreate" role="tabpanel">
                <div class="row">
                <div class="col-lg-8">
                    <div class="wa-card">
                        <div class="wa-card-h">Write a new template</div>
                        <div class="wa-card-b">
                            {{-- The server refuses over-quota submissions anyway; saying so up front
                                 beats letting the vendor fill the whole form and then bounce. --}}
                            @if ($quotaFull)
                                <div class="alert alert-warning" style="font-size:13px;">
                                    <b>You've used all {{ $quota['allowance'] }} template slots.</b>
                                    {{ $quota['included'] }} come with your plan.
                                    To add another, either delete a template you no longer use, or buy an extra
                                    slot for {{ _price($quota['slot_fee']) }} one-time.
                                    <form action="{{ route('vendor.whatsapp.billing.template-slot') }}" method="post" class="mb-0 mt-2">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn--primary">
                                            <i class="tio-add"></i> Buy a slot for {{ _price($quota['slot_fee']) }}
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="alert alert-info" style="font-size:13px;">
                                    <i class="tio-info-outined"></i>
                                    Meta reviews every new template. Approval usually takes a few minutes but
                                    <b>can take up to 24 hours</b>. You can’t send with a template until its
                                    status here shows <b>APPROVED</b>.
                                    <span class="d-block mt-1">
                                        {{ $quota['allowance'] - $quota['used'] }} of {{ $quota['allowance'] }}
                                        {{ $quota['allowance'] - $quota['used'] == 1 ? 'slot' : 'slots' }} left.
                                    </span>
                                </div>
                            @endif

                            <form action="{{ route('vendor.whatsapp.templates.create') }}" method="post"
                                  enctype="multipart/form-data" @if ($quotaFull) style="opacity:.55;pointer-events:none;" aria-hidden="true" @endif>
                                @csrf
                                <fieldset @if ($quotaFull) disabled @endif style="border:0;padding:0;margin:0;min-width:0;">
                                <div class="form-group">
                                    <label class="form-label">Template Name</label>
                                    <input type="text" class="form-control" name="tpl_name" placeholder="order_reminder" required>
                                    <small class="text-muted">
                                        Lowercase letters, numbers and underscores only.
                                        If you deleted a template, WhatsApp reserves that name for
                                        {{ \App\Services\WhatsAppService::TEMPLATE_NAME_LOCK_DAYS }} days —
                                        re-use it before then and it will be rejected, so add a suffix
                                        like <code>_v2</code> or <code>_{{ now()->year }}</code>.
                                    </small>
                                </div>
                                {{-- Templates are created in English (US) only, so there is nothing to
                                     choose — the language is set by the controller, not posted. --}}
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select class="form-control" name="tpl_category" required>
                                        <option value="UTILITY">UTILITY</option>
                                        <option value="MARKETING">MARKETING</option>
                                        <option value="AUTHENTICATION">AUTHENTICATION</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Body</label>
                                    <textarea class="form-control wa-tpl-body" name="tpl_body" rows="3" placeholder="Hi @{{customer_name}}, your order @{{1}} is confirmed." required>{{ old('tpl_body') }}</textarea>
                                    <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;">
                                        <small class="text-muted">Insert:</small>
                                        @foreach (\App\Services\WhatsAppService::TEMPLATE_VARIABLES as $key => $meta)
                                            <button type="button" class="btn btn-xs btn-outline-primary wa-var-insert" data-var="{{ $key }}">
                                                <i class="tio-add"></i> {{ $meta['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        The buttons above insert a variable MyChitti fills in per recipient — you never
                                        type a value for those. Use @{{1}}, @{{2}} for your own variables, but don’t mix
                                        the two styles in one message. Meta does not allow the message to start or end
                                        with a variable — always have text on both ends.
                                    </small>
                                    <div class="invalid-feedback wa-tpl-body-error"></div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Example Values</label>
                                    <input type="text" class="form-control" name="tpl_example" placeholder="John | KHB_3">
                                    <small class="text-muted">Pipe-separate ( | ) sample values for each variable. Required by Meta when the body has variables.</small>
                                </div>
                                {{-- Header. Meta allows one per template: either a line of text or a
                                     single media file shown above the message. --}}
                                <div class="border-top pt-3 mb-2">
                                    <label class="form-label"><b>Header</b> <span class="text-muted">(optional)</span></label>
                                    <select class="form-control mb-2" name="tpl_header_format" id="tplHeaderFormat">
                                        <option value="">None</option>
                                        <option value="TEXT">Text</option>
                                        <option value="IMAGE">Image</option>
                                        <option value="DOCUMENT">Document (PDF)</option>
                                        <option value="VIDEO">Video</option>
                                    </select>

                                    <div id="tplHeaderText" style="display:none;">
                                        <input type="text" class="form-control" name="tpl_header" maxlength="60"
                                               placeholder="🔔 Your order is on its way">
                                        <small class="text-muted d-block">Bold line above the message. Max 60 characters.</small>
                                    </div>

                                    <div id="tplHeaderMedia" style="display:none;">
                                        <input type="file" class="form-control-file" name="tpl_header_file"
                                               accept="image/jpeg,image/png,application/pdf,video/mp4">
                                        <small class="text-muted d-block mt-1">
                                            This exact file is submitted to Meta as the sample and is what every recipient
                                            sees. JPG or PNG up to 5&nbsp;MB, PDF up to 100&nbsp;MB, MP4 up to 16&nbsp;MB.
                                        </small>
                                    </div>
                                </div>

                                {{-- Buttons. Up to two, each either a link or a quick reply. --}}
                                <div class="border-top pt-3 mb-2">
                                    <label class="form-label"><b>Buttons</b> <span class="text-muted">(optional, up to 2)</span></label>

                                    {{-- The pair almost every campaign wants. Fills both slots as quick
                                         replies so the vendor does not have to know what that means. --}}
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-success tpl-btn-preset"
                                                data-yes="Interested" data-no="Not interested">
                                            <i class="tio-add-circle-outlined"></i> Interested / Not interested
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ml-1 tpl-btn-clear">
                                            Clear
                                        </button>
                                        <small class="text-muted d-block mt-1">
                                            One tap and the customer's answer lands in your
                                            <a href="{{ route('vendor.whatsapp.inbox') }}" target="_blank">Inbox</a>
                                            as a reply — no typing, and it opens the 24-hour window so you can message
                                            them freely.
                                        </small>
                                    </div>

                                    @for ($b = 0; $b < 2; $b++)
                                        <div class="row align-items-end mb-2">
                                            <div class="col-4">
                                                <label class="form-label mb-1" style="font-size:12px;">Type</label>
                                                <select class="form-control form-control-sm tpl-btn-type" name="tpl_btn[{{ $b }}][type]">
                                                    <option value="">—</option>
                                                    <option value="URL">Link</option>
                                                    <option value="PHONE_NUMBER">Call now</option>
                                                    <option value="QUICK_REPLY">Quick reply</option>
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-1" style="font-size:12px;">Label</label>
                                                <input type="text" class="form-control form-control-sm" maxlength="25"
                                                       name="tpl_btn[{{ $b }}][text]" placeholder="{{ $b === 0 ? 'Book now' : 'Not now' }}">
                                            </div>
                                            <div class="col-5 tpl-btn-url-wrap" style="display:none;">
                                                <label class="form-label mb-1" style="font-size:12px;">URL</label>
                                                <input type="url" class="form-control form-control-sm" name="tpl_btn[{{ $b }}][url]"
                                                       placeholder="https://example.com/book">
                                            </div>
                                            <div class="col-5 tpl-btn-phone-wrap" style="display:none;">
                                                <label class="form-label mb-1" style="font-size:12px;">Phone number</label>
                                                <input type="tel" class="form-control form-control-sm tpl-btn-phone"
                                                       name="tpl_btn[{{ $b }}][phone]" data-default="{{ $storePhone ?? '' }}"
                                                       placeholder="+91 98765 43210">
                                            </div>
                                        </div>
                                    @endfor
                                    <small class="text-muted d-block">
                                        A <b>link</b> button opens a web page; <b>call now</b> dials a number straight from
                                        the chat; a <b>quick reply</b> sends its label back to you as a message, which is
                                        how a customer answers without typing.
                                    </small>
                                    <small class="text-muted d-block mt-1">
                                        Include the country code on a call number, and use at most one call button per
                                        template — Meta rejects the rest.
                                    </small>
                                </div>

                                <button type="submit" class="btn btn--primary btn-block btn-lg" style="font-size:14px;">
                                    <i class="tio-send"></i> Submit to Meta for review
                                </button>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="wa-card">
                        <div class="wa-card-h">How review works</div>
                        <div class="wa-card-b">
                            <ul class="pl-3 mb-0 wa-sub" style="line-height:1.7;">
                                <li>Meta reviews every template. Usually minutes, <b>up to 24 hours</b>.</li>
                                <li>You can't send with it until the status shows <b>APPROVED</b>.</li>
                                <li>A rejected template can be edited and resubmitted — it doesn't cost a slot.</li>
                                <li>Marketing templates are held to a stricter bar than utility ones.</li>
                            </ul>
                            <div class="wa-note mt-3">
                                <b>Naming.</b> Lowercase letters, numbers and underscores only, and a deleted name
                                can't be reused for about 30 days — so pick something you'll keep.
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            </div>
        @endif
    </div>

    {{-- Delete choice. Trash and permanent delete look identical from the row but do very
         different things — one is reversible, the other reaches Meta and frees the slot. --}}
    <div class="modal fade" id="waTplDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete <span id="waDelName" class="text-danger"></span>?</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    {{-- The safe, reversible option is the loud one. The irreversible one is
                         available but quiet — weight should follow consequence, not severity. --}}
                    <div class="border rounded p-3 mb-3" style="border-color:#bbf7d0 !important;background:#f0fdf4;">
                        <div class="d-flex align-items-start" style="gap:10px;">
                            <i class="tio-delete-outlined" style="font-size:22px;color:#16a34a;"></i>
                            <div>
                                <b style="font-size:15px;color:#15803d;">Move to trash</b>
                                <span class="badge badge-soft-success ml-1" style="font-size:10px;">Recommended</span>
                                <div class="text-muted mt-1" style="font-size:12.5px;">
                                    Hides it from your list and from bulk sending. It stays approved at Meta, so
                                    <b>it still uses one of your {{ $quota['allowance'] }} slots</b> — and you can
                                    restore it instantly, with no new review.
                                </div>
                            </div>
                        </div>
                        <form action="{{ route('vendor.whatsapp.templates.trash') }}" method="post" class="mt-3 mb-0">
                            @csrf
                            <input type="hidden" name="name" class="wa-del-name">
                            <input type="hidden" name="language" class="wa-del-lang">
                            <button type="submit" class="btn btn-success btn-block btn-lg" style="font-size:14px;">
                                <i class="tio-delete-outlined"></i> Move to trash
                            </button>
                        </form>
                    </div>

                    <div class="border rounded p-3">
                        <div class="d-flex align-items-start" style="gap:10px;">
                            <i class="tio-warning" style="font-size:18px;color:#94a3b8;"></i>
                            <div>
                                <b style="font-size:13.5px;">Delete permanently</b>
                                <div class="text-muted" style="font-size:12px;">
                                    Removes it from Meta and frees the slot. <b>This cannot be undone</b>, and Meta
                                    blocks reusing the same template name for about 30 days.
                                </div>
                            </div>
                        </div>
                        <form action="{{ route('vendor.whatsapp.templates.delete') }}" method="post" class="mt-2 mb-0"
                              onsubmit="return confirm('Permanently delete this template from Meta? This cannot be undone.');">
                            @csrf
                            <input type="hidden" name="name" class="wa-del-name">
                            <input type="hidden" name="language" class="wa-del-lang">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="tio-delete"></i> Delete permanently
                            </button>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white btn-sm" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="waTplViewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Template Preview</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm mb-3">
                        <tr><td class="text-muted">Name</td><td><b id="wavName"></b></td></tr>
                        <tr><td class="text-muted">Category</td><td id="wavCategory"></td></tr>
                        <tr><td class="text-muted">Language</td><td id="wavLanguage"></td></tr>
                        <tr><td class="text-muted">Status</td><td id="wavStatus"></td></tr>
                    </table>
                    {{-- Shown the way the customer will see it. A template previewed as body-only
                         reads as a different message from the one that actually goes out. --}}
                    <label class="form-label text-muted">Preview</label>
                    <div class="wa-pv" id="wavPreview"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="waTplEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('vendor.whatsapp.templates.update') }}" method="post">
                    @csrf
                    <input type="hidden" name="tpl_id" id="waeId">
                    <input type="hidden" name="tpl_name" id="waeNameInput">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Template — <span id="waeName"></span></h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" style="font-size:12px;">Editing re-submits the template to Meta for review, which can take up to 24 hours — the template can’t be used again until it is APPROVED. Name and language cannot be changed.</div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select class="form-control" name="tpl_category" id="waeCategory">
                                <option value="UTILITY">UTILITY</option>
                                <option value="MARKETING">MARKETING</option>
                                <option value="AUTHENTICATION">AUTHENTICATION</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Body</label>
                            <textarea class="form-control wa-tpl-body" name="tpl_body" id="waeBody" rows="4" required></textarea>
                            <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;">
                                <small class="text-muted">Insert:</small>
                                @foreach (\App\Services\WhatsAppService::TEMPLATE_VARIABLES as $key => $meta)
                                    <button type="button" class="btn btn-xs btn-outline-primary wa-var-insert" data-var="{{ $key }}">
                                        <i class="tio-add"></i> {{ $meta['label'] }}
                                    </button>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1">
                                Filled in per recipient. Use @{{1}}, @{{2}} for your own variables — not both styles in
                                one message. Meta does not allow the message to start or end with a variable.
                            </small>
                            <div class="invalid-feedback wa-tpl-body-error"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Example Values</label>
                            <input type="text" class="form-control" name="tpl_example" placeholder="John | KHB_3">
                            <small class="text-muted">Required by Meta when the body has variables.</small>
                        </div>
                        {{-- Same two rows as the create form, so editing can add quick replies too.
                             The legacy single-URL fields below still post for older callers. --}}
                        <label class="form-label mb-1"><b>Buttons</b> <span class="text-muted">(up to 2)</span></label>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-success tpl-btn-preset"
                                    data-yes="Interested" data-no="Not interested">
                                <i class="tio-add-circle-outlined"></i> Interested / Not interested
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1 tpl-btn-clear">Clear</button>
                        </div>
                        @for ($b = 0; $b < 2; $b++)
                            <div class="row align-items-end mb-2">
                                <div class="col-4">
                                    <label class="form-label mb-1" style="font-size:12px;">Type</label>
                                    <select class="form-control form-control-sm tpl-btn-type" name="tpl_btn[{{ $b }}][type]">
                                        <option value="">—</option>
                                        <option value="URL">Link</option>
                                        <option value="PHONE_NUMBER">Call now</option>
                                        <option value="QUICK_REPLY">Quick reply</option>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <label class="form-label mb-1" style="font-size:12px;">Label</label>
                                    <input type="text" class="form-control form-control-sm" maxlength="25"
                                           name="tpl_btn[{{ $b }}][text]">
                                </div>
                                <div class="col-5 tpl-btn-url-wrap" style="display:none;">
                                    <label class="form-label mb-1" style="font-size:12px;">URL</label>
                                    <input type="url" class="form-control form-control-sm" name="tpl_btn[{{ $b }}][url]"
                                           placeholder="https://example.com/book">
                                </div>
                                <div class="col-5 tpl-btn-phone-wrap" style="display:none;">
                                    <label class="form-label mb-1" style="font-size:12px;">Phone number</label>
                                    <input type="tel" class="form-control form-control-sm tpl-btn-phone"
                                           name="tpl_btn[{{ $b }}][phone]" data-default="{{ $storePhone ?? '' }}"
                                           placeholder="+91 98765 43210">
                                </div>
                            </div>
                        @endfor
                        <input type="hidden" name="tpl_btn_text" id="waeBtnText">
                        <input type="hidden" name="tpl_btn_url" id="waeBtnUrl">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn--primary">Save & Re-submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    // Open the tab named in the URL hash, so a link elsewhere in the panel can point at
    // "Suggested" rather than dropping the vendor on the default tab to hunt for it. The hash is
    // kept in sync as tabs are clicked, which also makes a tab bookmarkable and survives a reload
    // after submitting a template.
    (function () {
        var hash = window.location.hash;
        if (hash && $('.wa-tabs a[href="' + hash + '"]').length) {
            $('.wa-tabs a[href="' + hash + '"]').tab('show');
        }
        $('.wa-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (history.replaceState) {
                history.replaceState(null, '', e.target.getAttribute('href'));
            }
        });
    })();

    // The picture an image-header template is sent with — the store's own, chosen server-side.
    // Null when the store has none, and the preview then says the header is filled in at send time
    // rather than showing a broken image.
    var WAV_HEADER_IMAGE = @json($headerImage ?? null);

    // Every button kind Meta can approve, drawn as the customer sees it. An unknown type still
    // gets a row — a preview that silently drops a button is worse than one that names it oddly.
    function wavButtonRow(b) {
        var type = String(b.type || '').toUpperCase();
        var icons = {
            URL: 'tio-link', PHONE_NUMBER: 'tio-call-talking', QUICK_REPLY: 'tio-reply',
            COPY_CODE: 'tio-copy', FLOW: 'tio-carousel-horizontal', CATALOG: 'tio-shopping-cart'
        };
        var sub = type === 'URL' ? (b.url || '')
            : type === 'PHONE_NUMBER' ? (b.phone_number || '')
            : type === 'COPY_CODE' ? (b.example || '')
            : '';

        var $row = $('<div class="wa-pv-btn"></div>')
            .append($('<i></i>').addClass(icons[type] || 'tio-more-horizontal'))
            .append(' ')
            .append($('<span></span>').text(b.text || type.replace(/_/g, ' ').toLowerCase()));
        if (sub) {
            $row.append($('<small></small>').text(sub));
        }
        return $row;
    }

    $(document).on('click', '.wa-tpl-view', function () {
        var d = $(this).data();
        $('#wavName').text(d.name); $('#wavCategory').text(d.category);
        $('#wavLanguage').text(d.language); $('#wavStatus').text(d.status);

        // jQuery parses the JSON attribute itself, but a malformed one must not take the modal
        // down with it — an unreadable component list is previewed as an empty message.
        var comps = d.components;
        if (typeof comps === 'string') {
            try { comps = JSON.parse(comps); } catch (e) { comps = []; }
        }
        if (!$.isArray(comps)) { comps = []; }

        var header = null, body = '', footer = '', buttons = [];
        $.each(comps, function (i, c) {
            var t = String(c.type || '').toUpperCase();
            if (t === 'HEADER') { header = c; }
            else if (t === 'BODY') { body = c.text || ''; }
            else if (t === 'FOOTER') { footer = c.text || ''; }
            else if (t === 'BUTTONS') { buttons = buttons.concat(c.buttons || []); }
        });

        var $pv = $('#wavPreview').empty();

        if (header) {
            var fmt = String(header.format || 'TEXT').toUpperCase();
            if (fmt === 'TEXT') {
                $pv.append($('<div class="wa-pv-hdr"></div>').text(header.text || ''));
            } else if (fmt === 'IMAGE' && WAV_HEADER_IMAGE) {
                $pv.append($('<img class="wa-pv-img" alt="Header image">').attr('src', WAV_HEADER_IMAGE));
                $pv.append($('<small class="wa-pv-note"></small>')
                    .text('Image header — your store picture is used for automatic messages.'));
            } else {
                var micon = fmt === 'VIDEO' ? 'tio-video-camera'
                    : fmt === 'DOCUMENT' ? 'tio-file-text'
                    : fmt === 'LOCATION' ? 'tio-poi' : 'tio-image';
                $pv.append($('<div class="wa-pv-media"></div>')
                    .append($('<i></i>').addClass(micon))
                    .append($('<span></span>').text(
                        fmt.charAt(0) + fmt.slice(1).toLowerCase() + ' header — added when the message is sent'
                    )));
            }
        }

        $pv.append($('<div class="wa-pv-body"></div>').text(body));
        if (footer) {
            $pv.append($('<div class="wa-pv-ftr"></div>').text(footer));
        }
        if (buttons.length) {
            var $btns = $('<div class="wa-pv-btns"></div>');
            $.each(buttons, function (i, b) { $btns.append(wavButtonRow(b)); });
            $pv.append($btns);
        }

        $('#waTplViewModal').modal('show');
    });
    $(document).on('click', '.wa-tpl-edit', function () {
        var d = $(this).data();
        $('#waeId').val(d.id); $('#waeName').text(d.name); $('#waeNameInput').val(d.name);
        $('#waeCategory').val((d.category || 'UTILITY'));
        $('#waeBody').val(d.body || '');
        $('#waeBtnText').val(d.btntext || ''); $('#waeBtnUrl').val(d.btnurl || '');

        // Show the template's existing buttons in the editable rows, so saving does not silently
        // drop them. Link and call buttons survive the round trip; quick replies still don't —
        // the list view carries no labels for them.
        var $rows = $('#waTplEditModal').find('.tpl-btn-type');
        $rows.val('').trigger('change');
        $('#waTplEditModal').find('input[name^="tpl_btn"]').val('');

        var slot = 0;
        if (d.btntext) {
            $rows.eq(slot).val(d.btnurl ? 'URL' : 'QUICK_REPLY').trigger('change');
            var $row = $rows.eq(slot).closest('.row');
            $row.find('input[name$="[text]"]').val(d.btntext);
            $row.find('input[name$="[url]"]').val(d.btnurl || '');
            slot++;
        }
        if (d.btnphone && slot < $rows.length) {
            $rows.eq(slot).val('PHONE_NUMBER').trigger('change');
            var $prow = $rows.eq(slot).closest('.row');
            $prow.find('input[name$="[text]"]').val(d.btnphonetext || 'Call now');
            $prow.find('input[name$="[phone]"]').val(d.btnphone);
        }

        $('#waTplEditModal').modal('show');
    });

    // Built by concatenation so Blade never sees a literal double-brace in this script.
    var OPEN = '{' + '{', CLOSE = '}' + '}';

    // Drops the named variable in at the cursor. Mirrors the server rule that a body is
    // either named or numbered — never both.
    $(document).on('click', '.wa-var-insert', function () {
        var $body = $(this).closest('.form-group').find('.wa-tpl-body');
        if (!$body.length) return;

        var el = $body[0];
        var text = el.value || '';
        var at = typeof el.selectionStart === 'number' ? el.selectionStart : text.length;
        var end = typeof el.selectionEnd === 'number' ? el.selectionEnd : at;
        var chunk = OPEN + $(this).data('var') + CLOSE;

        el.value = text.slice(0, at) + chunk + text.slice(end);
        el.focus();
        el.selectionStart = el.selectionEnd = at + chunk.length;
        $body.trigger('input');
    });

    $(document).on('click', '.wa-tpl-delete', function () {
        var d = $(this).data();
        $('#waDelName').text(d.name || '');
        $('.wa-del-name').val(d.name || '');
        $('.wa-del-lang').val(d.language || 'en_US');
        $('#waTplDeleteModal').modal('show');
    });

    // Header: text and media are mutually exclusive, so only one field set is ever shown.
    $(document).on('change', '#tplHeaderFormat', function () {
        var v = $(this).val();
        $('#tplHeaderText').toggle(v === 'TEXT');
        $('#tplHeaderMedia').toggle(v === 'IMAGE' || v === 'DOCUMENT' || v === 'VIDEO');
    });

    // Each button type needs a different second field: a link needs a URL, a call button needs a
    // number, a quick reply needs neither — it just sends its own label back.
    $(document).on('change', '.tpl-btn-type', function () {
        var type = $(this).val();
        var $row = $(this).closest('.row');
        $row.find('.tpl-btn-url-wrap').toggle(type === 'URL');
        $row.find('.tpl-btn-phone-wrap').toggle(type === 'PHONE_NUMBER');

        // Offer the store's own number the first time a call button is picked. Only when the
        // field is still empty, so it never overwrites a number the vendor typed or one loaded
        // from the template being edited.
        if (type === 'PHONE_NUMBER') {
            var $phone = $row.find('.tpl-btn-phone');
            if (!$phone.val()) {
                $phone.val($phone.data('default') || '');
            }
        }
    });

    // One-click Interested / Not interested. Writes into the same two button rows the vendor
    // could fill by hand, so what gets submitted to Meta is identical either way.
    //
    // Scoped to the surrounding form: the create panel and the edit modal both carry these rows,
    // and an unscoped selector would fill all four at once.
    $(document).on('click', '.tpl-btn-preset', function () {
        var labels = [$(this).data('yes'), $(this).data('no')];
        $(this).closest('form').find('.tpl-btn-type').each(function (i) {
            if (i > 1) return;
            $(this).val('QUICK_REPLY').trigger('change');
            var $row = $(this).closest('.row');
            $row.find('input[name^="tpl_btn"][name$="[text]"]').val(labels[i]);
            $row.find('input[name^="tpl_btn"][name$="[url]"]').val('');
            $row.find('input[name^="tpl_btn"][name$="[phone]"]').val('');
        });
    });

    $(document).on('click', '.tpl-btn-clear', function () {
        var $form = $(this).closest('form');
        $form.find('.tpl-btn-type').val('').trigger('change');
        $form.find('input[name^="tpl_btn"]').val('');
        // The edit modal's legacy hidden pair is the fallback the server reads when no row is
        // filled — leaving it behind would silently put the old link button back on save.
        $form.find('#waeBtnText, #waeBtnUrl').val('');
    });

    // Meta rejects a body that starts or ends with a variable (error_subcode 2388299).
    // Block the submit here so the vendor isn't bounced by a raw Graph error.
    var VAR_LEAD = new RegExp('^\\{\\{\\s*[a-z0-9_]+\\s*\\}\\}', 'i');
    var VAR_TRAIL = new RegExp('\\{\\{\\s*[a-z0-9_]+\\s*\\}\\}$', 'i');
    var VAR_NAMED = new RegExp('\\{\\{\\s*[a-z][a-z0-9_]*\\s*\\}\\}', 'i');
    var VAR_NUMBER = new RegExp('\\{\\{\\s*\\d+\\s*\\}\\}');

    function waBodyError(body) {
        body = $.trim(body);
        if (!body) return null;
        if (VAR_LEAD.test(body)) return 'The message can’t start with a variable. Put some text before it.';
        if (VAR_TRAIL.test(body)) return 'The message can’t end with a variable. Add some text after it.';
        if (VAR_NAMED.test(body) && VAR_NUMBER.test(body)) {
            return 'Use either the named variables or numbered ones like ' + OPEN + '1' + CLOSE + ' — Meta does not allow both in one message.';
        }
        return null;
    }

    $(document).on('input', '.wa-tpl-body', function () {
        var err = waBodyError($(this).val());
        $(this).toggleClass('is-invalid', !!err);
        $(this).closest('.form-group').find('.wa-tpl-body-error').text(err || '');
    });

    $(document).on('submit', 'form', function (e) {
        var $body = $(this).find('.wa-tpl-body');
        if (!$body.length) return;
        var err = waBodyError($body.val());
        if (err) {
            e.preventDefault();
            $body.addClass('is-invalid').focus();
            $body.closest('.form-group').find('.wa-tpl-body-error').text(err);
        }
    });
</script>
@endpush
