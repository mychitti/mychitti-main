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
                <div class="alert {{ $r['success'] ? 'alert-success' : 'alert-danger' }}">
                    <h6 class="mb-2">
                        @if ($r['success'])
                            <i class="tio-checkmark-circle"></i> Template created via Business Management API
                        @else
                            <i class="tio-clear-circle"></i> Template create failed
                        @endif
                    </h6>
                    <div style="font-size:13px;">
                        <div><b>Endpoint:</b> <code class="text-white">{{ $r['endpoint'] }}</code></div>
                        @if ($r['success'])
                            <div><b>Template ID:</b> <code>{{ $r['id'] ?? '—' }}</code></div>
                        @else
                            <div><b>Error:</b> {{ $r['error'] }}</div>
                        @endif
                    </div>
                    @if (!empty($r['response']))
                            <summary style="cursor:pointer;font-size:12px;">Raw API response</summary>
                            <pre class="mt-2 mb-0 p-2" style="background:#0d1117;color:#c9d1d9;border-radius:6px;font-size:12px;overflow:auto;max-height:240px;">{{ json_encode($r['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                </div>
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
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Create Template</h5></div>
                        <div class="card-body">
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
                        <div class="alert alert-info" style="font-size:12px;">Editing re-submits the template to Meta for review. Name and language cannot be changed.</div>
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
