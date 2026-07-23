@extends('layouts.vendor.app')

@section('title', 'WhatsApp Templates')

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-receipt"></i> WhatsApp Message Templates</h1>
            <div>
                <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-refresh"></i> Refresh</a>
                <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn-outline-primary"><i class="tio-chat"></i> Connection</a>
            </div>
        </div>

        @if (!$connected)
            <div class="alert alert-warning">
                <b>Not connected.</b> Connect your WhatsApp number first to manage templates.
                <a href="{{ route('vendor.whatsapp.connect') }}" class="alert-link">Connect now →</a>
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
            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Your Templates</h5></div>
                        <div class="card-body">
                            @if ($templateError)
                                <div class="alert alert-warning" style="font-size:13px;">{{ $templateError }}</div>
                            @endif
                            <div class="table-responsive">
                                <table class="table table-borderless table-thead-bordered table-align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Language</th>
                                            <th>Status</th>
                                            <th class="text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($templates as $tpl)
                                            @php
                                                $st = strtoupper($tpl['status'] ?? '');
                                                $bodyText = ''; $btnText = ''; $btnUrl = '';
                                                foreach (($tpl['components'] ?? []) as $cmp) {
                                                    if (($cmp['type'] ?? '') === 'BODY') {
                                                        $bodyText = $cmp['text'] ?? '';
                                                    } elseif (($cmp['type'] ?? '') === 'BUTTONS') {
                                                        foreach (($cmp['buttons'] ?? []) as $b) {
                                                            if (($b['type'] ?? '') === 'URL') { $btnText = $b['text'] ?? ''; $btnUrl = $b['url'] ?? ''; break; }
                                                        }
                                                    }
                                                }
                                                $editable = in_array($st, ['APPROVED', 'REJECTED', 'PAUSED']);
                                            @endphp
                                            <tr>
                                                <td><b>{{ $tpl['name'] ?? '' }}</b></td>
                                                <td>{{ $tpl['category'] ?? '' }}</td>
                                                <td>{{ $tpl['language'] ?? '' }}</td>
                                                <td>
                                                    <span class="badge badge-soft-{{ $st == 'APPROVED' ? 'success' : ($st == 'REJECTED' ? 'danger' : 'warning') }}">{{ $st ?: '—' }}</span>
                                                    @if ($st === 'PENDING')
                                                        <small class="text-muted d-block" style="font-size:11px;">Under review — up to 24 hours</small>
                                                    @endif
                                                </td>
                                                <td class="text-right text-nowrap">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary wa-tpl-view"
                                                            data-name="{{ $tpl['name'] ?? '' }}" data-category="{{ $tpl['category'] ?? '' }}"
                                                            data-language="{{ $tpl['language'] ?? '' }}" data-status="{{ $st }}"
                                                            data-body="{{ $bodyText }}" data-btntext="{{ $btnText }}" data-btnurl="{{ $btnUrl }}">
                                                        <i class="tio-visible-outlined"></i>
                                                    </button>
                                                    @if ($editable)
                                                        <button type="button" class="btn btn-sm btn-outline-primary wa-tpl-edit"
                                                                data-id="{{ $tpl['id'] ?? '' }}" data-name="{{ $tpl['name'] ?? '' }}"
                                                                data-category="{{ $tpl['category'] ?? '' }}" data-body="{{ $bodyText }}"
                                                                data-btntext="{{ $btnText }}" data-btnurl="{{ $btnUrl }}">
                                                            <i class="tio-edit"></i>
                                                        </button>
                                                    @endif
                                                    <form action="{{ route('vendor.whatsapp.templates.delete') }}" method="post" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                                        @csrf
                                                        <input type="hidden" name="name" value="{{ $tpl['name'] ?? '' }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">No templates yet. Create one on the right.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    @if ($presets->isNotEmpty())
                        <div class="card mb-3">
                            <div class="card-header"><h5 class="card-title mb-0"><i class="tio-star-outlined"></i> Suggested Templates</h5></div>
                            <div class="card-body">
                                <p class="text-muted mb-3" style="font-size:13px;">
                                    Ready-made templates from MyChitti. One click submits it to Meta on
                                    <b>your</b> WhatsApp account — once approved, you can send with it.
                                </p>
                                @foreach ($presets as $preset)
                                    <div class="border rounded p-2 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <b>{{ $preset->title }}</b>
                                                <code style="font-size:11px;">{{ $preset->name }}</code>
                                                <span class="badge badge-soft-secondary ml-1">{{ $preset->category }}</span>
                                            </div>
                                            @if ($preset->waba_status === 'APPROVED')
                                                <span class="badge badge-soft-success">APPROVED</span>
                                            @elseif ($preset->waba_status === 'PENDING')
                                                <span class="badge badge-soft-warning" title="Meta is reviewing this template — up to 24 hours.">PENDING</span>
                                            @elseif ($preset->waba_status)
                                                <span class="badge badge-soft-danger">{{ $preset->waba_status }}</span>
                                            @else
                                                <form action="{{ route('vendor.whatsapp.templates.use-preset') }}" method="post" class="mb-0">
                                                    @csrf
                                                    <input type="hidden" name="preset_id" value="{{ $preset->id }}">
                                                    <button type="submit" class="btn btn-sm btn--primary">Use this template</button>
                                                </form>
                                            @endif
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:12px;white-space:pre-wrap;">{{ $preset->body }}</div>
                                        @if ($preset->footer)
                                            <small class="text-muted d-block mt-1" style="font-size:11px;font-style:italic;">{{ $preset->footer }}</small>
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
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Create Template</h5></div>
                        <div class="card-body">
                            <div class="alert alert-info" style="font-size:13px;">
                                <i class="tio-info-outined"></i>
                                Meta reviews every new template. Approval usually takes a few minutes but
                                <b>can take up to 24 hours</b>. You can’t send with a template until its
                                status here shows <b>APPROVED</b>.
                            </div>
                            <form action="{{ route('vendor.whatsapp.templates.create') }}" method="post">
                                @csrf
                                <div class="form-group">
                                    <label class="form-label">Template Name</label>
                                    <input type="text" class="form-control" name="tpl_name" placeholder="order_reminder" required>
                                    <small class="text-muted">Lowercase letters, numbers and underscores only.</small>
                                </div>
                                <div class="row">
                                    <div class="col-7">
                                        <div class="form-group">
                                            <label class="form-label">Category</label>
                                            <select class="form-control" name="tpl_category" required>
                                                <option value="UTILITY">UTILITY</option>
                                                <option value="MARKETING">MARKETING</option>
                                                <option value="AUTHENTICATION">AUTHENTICATION</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label class="form-label">Language</label>
                                            <input type="text" class="form-control" name="tpl_lang" value="en_US" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Body</label>
                                    <textarea class="form-control wa-tpl-body" name="tpl_body" rows="3" placeholder="Hi @{{1}}, your order @{{2}} is confirmed." required>{{ old('tpl_body') }}</textarea>
                                    <small class="text-muted">Use @{{1}}, @{{2}} for variables. Meta does not allow the message to start or end with a variable — always have text on both ends.</small>
                                    <div class="invalid-feedback wa-tpl-body-error"></div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Example Values</label>
                                    <input type="text" class="form-control" name="tpl_example" placeholder="John | KHB_3">
                                    <small class="text-muted">Pipe-separate ( | ) sample values for each variable. Required by Meta when the body has variables.</small>
                                </div>
                                <div class="border-top pt-2 mb-2">
                                    <small class="text-muted d-block mb-2"><b>Call-to-action button</b> — optional URL button shown under the message.</small>
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="form-group mb-1">
                                                <label class="form-label mb-1">Button Text</label>
                                                <input type="text" class="form-control" name="tpl_btn_text" placeholder="View Leads">
                                            </div>
                                        </div>
                                        <div class="col-7">
                                            <div class="form-group mb-1">
                                                <label class="form-label mb-1">Button URL</label>
                                                <input type="url" class="form-control" name="tpl_btn_url" value="{{ rtrim(config('app.vendor_panel_url'), '/') . '/service/leads' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn--primary btn-block">Submit to Meta</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
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
                    <label class="form-label text-muted">Body</label>
                    <div class="border rounded p-2 mb-2" style="white-space:pre-wrap;background:#f8f9fa;" id="wavBody"></div>
                    <div id="wavButtonWrap" style="display:none;">
                        <label class="form-label text-muted">Button</label>
                        <div class="border rounded p-2 text-primary"><i class="tio-link"></i> <span id="wavBtnText"></span> — <small id="wavBtnUrl" class="text-muted"></small></div>
                    </div>
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
                            <small class="text-muted">Meta does not allow the message to start or end with a variable — always have text on both ends.</small>
                            <div class="invalid-feedback wa-tpl-body-error"></div>
                            <small class="text-muted">Use @{{1}}, @{{2}} for variables.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Example Values</label>
                            <input type="text" class="form-control" name="tpl_example" placeholder="John | KHB_3">
                            <small class="text-muted">Required by Meta when the body has variables.</small>
                        </div>
                        <div class="row">
                            <div class="col-5">
                                <div class="form-group mb-1">
                                    <label class="form-label mb-1">Button Text</label>
                                    <input type="text" class="form-control" name="tpl_btn_text" id="waeBtnText">
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="form-group mb-1">
                                    <label class="form-label mb-1">Button URL</label>
                                    <input type="url" class="form-control" name="tpl_btn_url" id="waeBtnUrl">
                                </div>
                            </div>
                        </div>
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
    $(document).on('click', '.wa-tpl-view', function () {
        var d = $(this).data();
        $('#wavName').text(d.name); $('#wavCategory').text(d.category);
        $('#wavLanguage').text(d.language); $('#wavStatus').text(d.status);
        $('#wavBody').text(d.body || '');
        if (d.btnurl) { $('#wavButtonWrap').show(); $('#wavBtnText').text(d.btntext); $('#wavBtnUrl').text(d.btnurl); }
        else { $('#wavButtonWrap').hide(); }
        $('#waTplViewModal').modal('show');
    });
    $(document).on('click', '.wa-tpl-edit', function () {
        var d = $(this).data();
        $('#waeId').val(d.id); $('#waeName').text(d.name);
        $('#waeCategory').val((d.category || 'UTILITY'));
        $('#waeBody').val(d.body || '');
        $('#waeBtnText').val(d.btntext || ''); $('#waeBtnUrl').val(d.btnurl || '');
        $('#waTplEditModal').modal('show');
    });

    // Meta rejects a body that starts or ends with a variable (error_subcode 2388299).
    // Block the submit here so the vendor isn't bounced by a raw Graph error.
    var VAR_LEAD = new RegExp('^\\{\\{\\s*\\d+\\s*\\}\\}');
    var VAR_TRAIL = new RegExp('\\{\\{\\s*\\d+\\s*\\}\\}$');

    function waBodyError(body) {
        body = $.trim(body);
        if (!body) return null;
        if (VAR_LEAD.test(body)) return 'The message can’t start with a variable. Put some text before it.';
        if (VAR_TRAIL.test(body)) return 'The message can’t end with a variable. Add some text after it.';
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
