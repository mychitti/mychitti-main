@extends('layouts.admin.app')

@section('title', translate('WhatsApp Templates'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-receipt" style="font-size:22px;"></i>
                </span>
                <span>{{ translate('WhatsApp Templates') }}</span>
            </h1>
            <div class="d-flex align-items-center" style="gap:8px;">
                <a href="{{ route('admin.business-settings.third-party.whatsapp-templates') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="tio-refresh"></i> {{ translate('Refresh') }}
                </a>
                <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="btn btn-sm btn-outline-primary">
                    <i class="tio-settings"></i> {{ translate('API Setup') }}
                </a>
            </div>
        </div>

        <div class="alert alert-soft-info d-flex align-items-start" style="gap:10px;">
            <i class="tio-info-outined" style="font-size:18px;line-height:1.4;"></i>
            <div>
                {{ translate('These are the templates on the platform\'s own WhatsApp Business Account — the ones MyChitti sends from (OTPs, lead alerts, the vendor test message). Vendors have their own separate templates on their own accounts, managed from their own panel.') }}
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="tio-receipt"></i> {{ translate('Message Templates') }}
                            <span class="text-muted" style="font-size:12px;">— {{ translate('Business Management API') }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(session('wa_create_result'))
                            @php $r = session('wa_create_result'); @endphp
                            @if ($r['success'])
                                <div class="alert alert-success d-flex align-items-start" style="gap:12px;">
                                    <i class="tio-checkmark-circle" style="font-size:24px;line-height:1.2;"></i>
                                    <div>
                                        <h6 class="mb-1">{{ translate('Template submitted for review') }}</h6>
                                        <div style="font-size:13px;">
                                            {{ translate('Meta is now reviewing the template. Approval usually takes a few minutes but can take up to 24 hours — it can be used once its status shows APPROVED.') }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-danger d-flex align-items-start" style="gap:12px;">
                                    <i class="tio-clear-circle" style="font-size:24px;line-height:1.2;"></i>
                                    <div style="min-width:0;flex:1;">
                                        <h6 class="mb-1">{{ translate('Template could not be submitted') }}</h6>
                                        <div style="font-size:13px;">{{ $r['error'] }}</div>
                                    </div>
                                </div>
                            @endif
                        @endif
                        <div class="row">
                            <div class="col-lg-7">
                                @if(isset($templateError) && $templateError)
                                    <div class="alert alert-warning" style="font-size:13px;">{{ $templateError }}</div>
                                @endif
                                <div class="table-responsive">
                                    <table class="table table-borderless table-thead-bordered table-align-middle">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>{{ translate('Name') }}</th>
                                                <th>{{ translate('Category') }}</th>
                                                <th>{{ translate('Language') }}</th>
                                                <th>{{ translate('Status') }}</th>
                                                <th class="text-right">{{ translate('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($templates ?? [] as $tpl)
                                                @php
                                                    $st = strtoupper($tpl['status'] ?? '');
                                                    $bodyText = ''; $btnText = ''; $btnUrl = ''; $headerText = ''; $footerText = '';
                                                    foreach (($tpl['components'] ?? []) as $cmp) {
                                                        $type = $cmp['type'] ?? '';
                                                        if ($type === 'BODY') {
                                                            $bodyText = $cmp['text'] ?? '';
                                                        } elseif ($type === 'HEADER' && ($cmp['format'] ?? '') === 'TEXT') {
                                                            $headerText = $cmp['text'] ?? '';
                                                        } elseif ($type === 'FOOTER') {
                                                            $footerText = $cmp['text'] ?? '';
                                                        } elseif ($type === 'BUTTONS') {
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
                                                                data-header="{{ $headerText }}" data-footer="{{ $footerText }}"
                                                                data-body="{{ $bodyText }}" data-btntext="{{ $btnText }}" data-btnurl="{{ $btnUrl }}">
                                                            <i class="tio-visible-outlined"></i>
                                                        </button>
                                                        @if($editable)
                                                            <button type="button" class="btn btn-sm btn-outline-primary wa-tpl-edit"
                                                                    data-id="{{ $tpl['id'] ?? '' }}" data-name="{{ $tpl['name'] ?? '' }}"
                                                                    data-category="{{ $tpl['category'] ?? '' }}" data-body="{{ $bodyText }}"
                                                                    data-header="{{ $headerText }}" data-footer="{{ $footerText }}"
                                                                    data-btntext="{{ $btnText }}" data-btnurl="{{ $btnUrl }}">
                                                                <i class="tio-edit"></i>
                                                            </button>
                                                        @endif
                                                        <form action="{{ env('APP_MODE') != 'demo' ? route('admin.business-settings.third-party.whatsapp-template-delete') : 'javascript:' }}" method="post" class="d-inline" onsubmit="return confirm('{{ translate('Delete this template?') }}');">
                                                            @csrf
                                                            <input type="hidden" name="name" value="{{ $tpl['name'] ?? '' }}">
                                                            <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center text-muted">{{ translate('No templates found. Add the Business Account ID under API Setup, then create one.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="border rounded p-3" style="background:#fafbff;">
                                    <h6 class="mb-3">{{ translate('Create Template') }}</h6>
                                    <form action="{{ env('APP_MODE') != 'demo' ? route('admin.business-settings.third-party.whatsapp-template-create') : 'javascript:' }}" method="post">
                                        @csrf
                                        <div class="form-group">
                                            <label class="form-label">{{ translate('Template Name') }}</label>
                                            <input type="text" class="form-control" name="tpl_name" placeholder="order_reminder" required>
                                            <small class="text-muted">{{ translate('Lowercase letters, numbers and underscores only.') }}</small>
                                        </div>
                                        <div class="row">
                                            {{-- English (US) only — the language is set by the
                                                 controller, so there is nothing to choose here. --}}
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label class="form-label">{{ translate('Category') }}</label>
                                                    <select class="form-control" name="tpl_category" required>
                                                        <option value="UTILITY">UTILITY</option>
                                                        <option value="MARKETING">MARKETING</option>
                                                        <option value="AUTHENTICATION">AUTHENTICATION</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">{{ translate('Header') }} <span class="text-muted">({{ translate('optional') }})</span></label>
                                            <input type="text" class="form-control" name="tpl_header" maxlength="60" placeholder="🔔 New Lead on MyChitti!">
                                            <small class="text-muted">{{ translate('Bold title at the top. Max 60 chars.') }}</small>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">{{ translate('Body') }}</label>
                                            <textarea class="form-control wa-tpl-body" name="tpl_body" rows="4" placeholder="Hi @{{customer_name}}, your order @{{1}} is confirmed." required></textarea>
                                            <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;">
                                                <small class="text-muted">{{ translate('Insert') }}:</small>
                                                @foreach (\App\Services\WhatsAppService::TEMPLATE_VARIABLES as $key => $meta)
                                                    <button type="button" class="btn btn-sm btn-outline-primary wa-var-insert" data-var="{{ $key }}">
                                                        <i class="tio-add"></i> {{ translate($meta['label']) }}
                                                    </button>
                                                @endforeach
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                {{ translate('The buttons above insert a variable filled in per recipient at send time — no example value needed. Use') }}
                                                @{{1}}, @{{2}} {{ translate('for your own variables, but do not mix the two styles. Emojis and *bold* / _italic_ allowed.') }}
                                            </small>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">{{ translate('Footer') }} <span class="text-muted">({{ translate('optional') }})</span></label>
                                            <input type="text" class="form-control" name="tpl_footer" maxlength="60" placeholder="— Team MyChitti">
                                            <small class="text-muted">{{ translate('Small grey text at the bottom. No variables. Max 60 chars.') }}</small>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">{{ translate('Example Values') }}</label>
                                            <input type="text" class="form-control" name="tpl_example" placeholder="John | KHB_3">
                                            <small class="text-muted">{{ translate('Pipe-separate ( | ) sample values for each variable. Required by Meta when the body has variables.') }}</small>
                                        </div>
                                        <div class="border-top pt-2 mb-2">
                                            <small class="text-muted d-block mb-2"><b>{{ translate('Call-to-action button') }}</b> — {{ translate('optional URL button shown under the message.') }}</small>
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="form-group mb-1">
                                                        <label class="form-label mb-1">{{ translate('Button Text') }}</label>
                                                        <input type="text" class="form-control" name="tpl_btn_text" placeholder="View Leads">
                                                    </div>
                                                </div>
                                                <div class="col-7">
                                                    <div class="form-group mb-1">
                                                        <label class="form-label mb-1">{{ translate('Button URL') }}</label>
                                                        <input type="url" class="form-control" name="tpl_btn_url" value="{{ rtrim(config('app.vendor_panel_url'), '/') . '/service/leads' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}" class="btn btn--primary btn-block">{{ translate('Submit to Meta') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="waTplViewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Template Preview') }}</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm mb-3">
                        <tr><td class="text-muted">{{ translate('Name') }}</td><td><b id="wavName"></b></td></tr>
                        <tr><td class="text-muted">{{ translate('Category') }}</td><td id="wavCategory"></td></tr>
                        <tr><td class="text-muted">{{ translate('Language') }}</td><td id="wavLanguage"></td></tr>
                        <tr><td class="text-muted">{{ translate('Status') }}</td><td id="wavStatus"></td></tr>
                    </table>
                    <div id="wavHeaderWrap" style="display:none;">
                        <label class="form-label text-muted">{{ translate('Header') }}</label>
                        <div class="border rounded p-2 mb-2 font-weight-bold" id="wavHeader"></div>
                    </div>
                    <label class="form-label text-muted">{{ translate('Body') }}</label>
                    <div class="border rounded p-2 mb-2" style="white-space:pre-wrap;background:#f8f9fa;" id="wavBody"></div>
                    <div id="wavFooterWrap" style="display:none;">
                        <label class="form-label text-muted">{{ translate('Footer') }}</label>
                        <div class="border rounded p-2 mb-2 text-muted" id="wavFooter"></div>
                    </div>
                    <div id="wavButtonWrap" style="display:none;">
                        <label class="form-label text-muted">{{ translate('Button') }}</label>
                        <div class="border rounded p-2 text-primary"><i class="tio-link"></i> <span id="wavBtnText"></span> — <small id="wavBtnUrl" class="text-muted"></small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="waTplEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ env('APP_MODE') != 'demo' ? route('admin.business-settings.third-party.whatsapp-template-update') : 'javascript:' }}" method="post">
                    @csrf
                    <input type="hidden" name="tpl_id" id="waeId">
                    <input type="hidden" name="tpl_name" id="waeNameInput">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Edit Template') }} — <span id="waeName"></span></h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" style="font-size:12px;">{{ translate('Editing re-submits the template to Meta for review. Name and language cannot be changed.') }}</div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Category') }}</label>
                            <select class="form-control" name="tpl_category" id="waeCategory">
                                <option value="UTILITY">UTILITY</option>
                                <option value="MARKETING">MARKETING</option>
                                <option value="AUTHENTICATION">AUTHENTICATION</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Header') }} <span class="text-muted">({{ translate('optional') }})</span></label>
                            <input type="text" class="form-control" name="tpl_header" id="waeHeader" maxlength="60">
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Body') }}</label>
                            <textarea class="form-control wa-tpl-body" name="tpl_body" id="waeBody" rows="4" required></textarea>
                            <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;">
                                <small class="text-muted">{{ translate('Insert') }}:</small>
                                @foreach (\App\Services\WhatsAppService::TEMPLATE_VARIABLES as $key => $meta)
                                    <button type="button" class="btn btn-sm btn-outline-primary wa-var-insert" data-var="{{ $key }}">
                                        <i class="tio-add"></i> {{ translate($meta['label']) }}
                                    </button>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1">{{ translate('Use') }} @{{1}}, @{{2}} {{ translate('for variables — do not mix them with the named ones above.') }}</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Footer') }} <span class="text-muted">({{ translate('optional') }})</span></label>
                            <input type="text" class="form-control" name="tpl_footer" id="waeFooter" maxlength="60">
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Example Values') }}</label>
                            <input type="text" class="form-control" name="tpl_example" placeholder="John | KHB_3">
                            <small class="text-muted">{{ translate('Required by Meta when the body has variables.') }}</small>
                        </div>
                        <div class="row">
                            <div class="col-5">
                                <div class="form-group mb-1">
                                    <label class="form-label mb-1">{{ translate('Button Text') }}</label>
                                    <input type="text" class="form-control" name="tpl_btn_text" id="waeBtnText">
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="form-group mb-1">
                                    <label class="form-label mb-1">{{ translate('Button URL') }}</label>
                                    <input type="url" class="form-control" name="tpl_btn_url" id="waeBtnUrl">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}" class="btn btn--primary">{{ translate('Save & Re-submit') }}</button>
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
        if (d.header) { $('#wavHeaderWrap').show(); $('#wavHeader').text(d.header); } else { $('#wavHeaderWrap').hide(); }
        if (d.footer) { $('#wavFooterWrap').show(); $('#wavFooter').text(d.footer); } else { $('#wavFooterWrap').hide(); }
        if (d.btnurl) { $('#wavButtonWrap').show(); $('#wavBtnText').text(d.btntext); $('#wavBtnUrl').text(d.btnurl); }
        else { $('#wavButtonWrap').hide(); }
        $('#waTplViewModal').modal('show');
    });
    $(document).on('click', '.wa-tpl-edit', function () {
        var d = $(this).data();
        $('#waeId').val(d.id); $('#waeName').text(d.name); $('#waeNameInput').val(d.name);
        $('#waeCategory').val((d.category || 'UTILITY'));
        $('#waeHeader').val(d.header || ''); $('#waeFooter').val(d.footer || '');
        $('#waeBody').val(d.body || '');
        $('#waeBtnText').val(d.btntext || ''); $('#waeBtnUrl').val(d.btnurl || '');
        $('#waTplEditModal').modal('show');
    });

    // Built by concatenation so Blade never sees a literal double-brace in this script.
    var OPEN = '{' + '{', CLOSE = '}' + '}';

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
    });
</script>
@endpush
