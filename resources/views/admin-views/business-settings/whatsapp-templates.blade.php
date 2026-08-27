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
                                {{-- Templates the platform's own automations need. Each one is
                                     filled in by a job that supplies its variables by position, so
                                     the body has to keep exactly this shape — hence a one-click
                                     prefill rather than a name in the docs someone retypes. --}}
                                @php
                                    $platformTemplates = [
                                        [
                                            'name'     => config('services.whatsapp.daily_report_template', 'daily_report'),
                                            'label'    => translate('Daily hospital report'),
                                            'blurb'    => translate('The end-of-day summary hospitals switch on under Hospital Settings. Sent from this number, not theirs.'),
                                            'category' => 'UTILITY',
                                            'body'     => "Daily summary for {{1}} — {{2}}\n\nMoney: {{3}}\n\nActivity: {{4}}\n\nChange what this includes, or switch it off, under Hospital Settings.",
                                            'example'  => 'City Hospital | 12 Feb 2026 | Lab income: 4,250.00 · Total income: 18,400.00 (up 3,100.00 on yesterday) · Money still owed: 22,900.00 | New enquiries: 4 · New patients: 2 · Booked for tomorrow: 11',
                                        ],
                                        [
                                            'name'     => \App\Services\WhatsAppService::DEFAULT_ADDON_LOW_BALANCE_TEMPLATE,
                                            'label'    => translate('Add-on renewal failed'),
                                            'blurb'    => translate('Warns a vendor that their wallet could not cover the monthly Lead Notifications renewal. Sent by the daily renewal run, which retries for a week before giving up.'),
                                            'category' => 'UTILITY',
                                            'header'   => 'Add-on renewal failed',
                                            'footer'   => 'MyChitti',
                                            // {{3}} and {{4}} arrive already formatted by _price(), symbol included —
                                            // so no currency sign belongs in front of them here.
                                            'body'     => "Hello {{1}}, we could not renew your {{2}} add-on on MyChitti because your wallet balance is too low.\n\nAmount due: {{3}}\nWallet balance: {{4}}\n\nTop up your wallet to keep receiving these alerts on WhatsApp. We will try again tomorrow.",
                                            'example'  => 'Khb Service Center | Lead Notifications | ₹200.00 | ₹45.00',
                                        ],
                                    ];
                                @endphp

                                <div class="border rounded p-3 mb-3" style="background:#f0fdf4;">
                                    <h6 class="mb-2" style="font-size:14px;">{{ translate('Suggested Templates') }}</h6>
                                    <p class="text-muted mb-3" style="font-size:12px;">
                                        {{ translate('Needed by the platform\'s own automations. Click one to fill the form below, then submit it to Meta.') }}
                                    </p>
                                    @foreach($platformTemplates as $t)
                                        @php
                                            // Whether it is already on the WABA, read from the list
                                            // this page has already fetched.
                                            $existing = collect($templates)->firstWhere('name', $t['name']);
                                            $status   = $existing['status'] ?? null;
                                        @endphp
                                        <div class="d-flex align-items-start justify-content-between mb-2" style="gap:10px;">
                                            <div>
                                                <div style="font-size:13px; font-weight:600;">
                                                    {{ $t['label'] }}
                                                    <code style="font-size:11px;">{{ $t['name'] }}</code>
                                                    @if($status)
                                                        <span class="badge badge-soft-{{ $status === 'APPROVED' ? 'success' : ($status === 'REJECTED' ? 'danger' : 'warning') }}">{{ $status }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size:11.5px;">{{ $t['blurb'] }}</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary wa-suggest"
                                                    style="white-space:nowrap;"
                                                    data-name="{{ $t['name'] }}"
                                                    data-category="{{ $t['category'] }}"
                                                    data-body="{{ $t['body'] }}"
                                                    data-example="{{ $t['example'] }}"
                                                    data-header="{{ $t['header'] ?? '' }}"
                                                    data-footer="{{ $t['footer'] ?? '' }}">
                                                {{ $status ? translate('Recreate') : translate('Use this') }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="border rounded p-3" style="background:#fafbff;">
                                    <h6 class="mb-3">{{ translate('Create Template') }}</h6>
                                    <form action="{{ env('APP_MODE') != 'demo' ? route('admin.business-settings.third-party.whatsapp-template-create') : 'javascript:' }}" method="post" enctype="multipart/form-data">
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
                                        {{-- A header is either words or a file, never both. Meta wants a sample
                                             of the file at creation time and sends the real one with each message,
                                             so what is uploaded here is only what the reviewer sees. --}}
                                        <div class="form-group">
                                            <label class="form-label">{{ translate('Header') }} <span class="text-muted">({{ translate('optional') }})</span></label>
                                            <select class="form-control mb-2" name="tpl_header_format" id="waHeaderFormat">
                                                <option value="TEXT">{{ translate('Text') }}</option>
                                                <option value="IMAGE">{{ translate('Image') }}</option>
                                                <option value="DOCUMENT">{{ translate('Document (PDF)') }}</option>
                                                <option value="VIDEO">{{ translate('Video') }}</option>
                                            </select>

                                            <div id="waHeaderTextWrap">
                                                <input type="text" class="form-control" name="tpl_header" maxlength="60" placeholder="🔔 New Lead on MyChitti!">
                                                <small class="text-muted">{{ translate('Bold title at the top. Max 60 chars. Leave blank for no header.') }}</small>
                                            </div>

                                            <div id="waHeaderFileWrap" class="d-none">
                                                <input type="file" class="form-control-file" name="tpl_header_file"
                                                       accept=".jpg,.jpeg,.png,.pdf,.mp4">
                                                <small class="text-muted d-block mt-1">
                                                    {{ translate('Sample file for Meta to review — JPG, PNG, PDF or MP4, up to 16 MB. Each message carries its own file in its place.') }}
                                                </small>
                                            </div>
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
                                        {{-- Buttons. Up to two, each a link, a call button or a quick reply —
                                             the same set the vendor composer offers. --}}
                                        <div class="border-top pt-2 mb-2">
                                            <label class="form-label"><b>{{ translate('Buttons') }}</b>
                                                <span class="text-muted">{{ translate('(optional, up to 2)') }}</span></label>

                                            {{-- The pair almost every campaign wants. Fills both slots as quick
                                                 replies so nobody has to know what that means. --}}
                                            <div class="mb-2">
                                                <button type="button" class="btn btn-sm btn-outline-success tpl-btn-preset"
                                                        data-yes="{{ translate('Interested') }}" data-no="{{ translate('Not interested') }}">
                                                    <i class="tio-add-circle-outlined"></i> {{ translate('Interested / Not interested') }}
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary ml-1 tpl-btn-clear">
                                                    {{ translate('Clear') }}
                                                </button>
                                                <small class="text-muted d-block mt-1">
                                                    {{ translate("One tap and the customer's answer lands in your") }}
                                                    <a href="{{ route('admin.business-settings.third-party.whatsapp-inbox') }}" target="_blank">{{ translate('Inbox') }}</a>
                                                    {{ translate('as a reply — no typing, and it opens the 24-hour window so you can message them freely.') }}
                                                </small>
                                            </div>

                                            @for ($b = 0; $b < 2; $b++)
                                                <div class="row align-items-end mb-2">
                                                    <div class="col-4">
                                                        <label class="form-label mb-1" style="font-size:12px;">{{ translate('Type') }}</label>
                                                        <select class="form-control form-control-sm tpl-btn-type" name="tpl_btn[{{ $b }}][type]">
                                                            <option value="">—</option>
                                                            <option value="URL">{{ translate('Link') }}</option>
                                                            <option value="PHONE_NUMBER">{{ translate('Call now') }}</option>
                                                            <option value="QUICK_REPLY">{{ translate('Quick reply') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <label class="form-label mb-1" style="font-size:12px;">{{ translate('Label') }}</label>
                                                        <input type="text" class="form-control form-control-sm" maxlength="25"
                                                               name="tpl_btn[{{ $b }}][text]" placeholder="{{ $b === 0 ? translate('Book now') : translate('Not now') }}">
                                                    </div>
                                                    <div class="col-5 tpl-btn-url-wrap" style="display:none;">
                                                        <label class="form-label mb-1" style="font-size:12px;">{{ translate('URL') }}</label>
                                                        <input type="url" class="form-control form-control-sm" name="tpl_btn[{{ $b }}][url]"
                                                               placeholder="{{ rtrim(config('app.vendor_panel_url'), '/') . '/service/leads' }}">
                                                    </div>
                                                    <div class="col-5 tpl-btn-phone-wrap" style="display:none;">
                                                        <label class="form-label mb-1" style="font-size:12px;">{{ translate('Phone number') }}</label>
                                                        <input type="tel" class="form-control form-control-sm tpl-btn-phone"
                                                               name="tpl_btn[{{ $b }}][phone]"
                                                               data-default="{{ data_get(\App\CentralLogics\Helpers::get_business_settings('whatsapp_config'), 'display_phone_number', '') }}"
                                                               placeholder="+91 98765 43210">
                                                    </div>
                                                </div>
                                            @endfor
                                            <small class="text-muted d-block">
                                                {{ translate('A link button opens a web page; call now dials a number straight from the chat; a quick reply sends its label back to you as a message, which is how a customer answers without typing.') }}
                                            </small>
                                            <small class="text-muted d-block mt-1">
                                                {{ translate('Include the country code on a call number, and use at most one call button per template — Meta rejects the rest.') }}
                                            </small>
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

    // Each button type needs a different second field: a link needs a URL, a call button needs a
    // number, a quick reply needs neither — it just sends its own label back.
    $(document).on('change', '.tpl-btn-type', function () {
        var type = $(this).val();
        var $row = $(this).closest('.row');
        $row.find('.tpl-btn-url-wrap').toggle(type === 'URL');
        $row.find('.tpl-btn-phone-wrap').toggle(type === 'PHONE_NUMBER');

        // Offer the platform's own number the first time a call button is picked, and only while
        // the field is still empty, so a number typed by hand is never overwritten.
        if (type === 'PHONE_NUMBER') {
            var $phone = $row.find('.tpl-btn-phone');
            if (!$phone.val()) {
                $phone.val($phone.data('default') || '');
            }
        }
    });

    // One-click Interested / Not interested. Writes into the same two rows that can be filled by
    // hand, so what reaches Meta is identical either way. Scoped to the surrounding form.
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
        // The edit modal's legacy pair is what the server falls back to when no row is filled;
        // leaving it behind would silently restore the old link button on save.
        $form.find('#waeBtnText, #waeBtnUrl').val('');
    });
</script>
@endpush

@push('script_2')
<script>
    (function () {
        var sel = document.getElementById('waHeaderFormat');
        if (!sel) { return; }
        var textWrap = document.getElementById('waHeaderTextWrap');
        var fileWrap = document.getElementById('waHeaderFileWrap');
        sel.addEventListener('change', function () {
            var isText = sel.value === 'TEXT';
            textWrap.classList.toggle('d-none', !isText);
            fileWrap.classList.toggle('d-none', isText);
        });
    })();
</script>
@endpush

@push('script_2')
<script>
    // Fill the Create Template form from a suggested one. The body shape is fixed by the job
    // that sends it, so this is the only supported way to get the variables in the right order.
    document.querySelectorAll('.wa-suggest').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.querySelector('form[action*="whatsapp-template-create"]');
            if (!form) return;

            var set = function (name, value) {
                var el = form.querySelector('[name="' + name + '"]');
                if (el) el.value = value;
            };

            set('tpl_name', this.dataset.name);
            set('tpl_category', this.dataset.category);
            set('tpl_body', this.dataset.body);
            set('tpl_example', this.dataset.example);

            // Header and footer are static decoration — the sending job supplies body variables
            // only — but they still have to reach Meta at submission, and a half-filled form is
            // how a suggested template ends up on the WABA looking nothing like the real one.
            set('tpl_header', this.dataset.header || '');
            set('tpl_footer', this.dataset.footer || '');
            if (this.dataset.header) {
                set('tpl_header_format', 'TEXT');
                var fmt = form.querySelector('[name="tpl_header_format"]');
                if (fmt) fmt.dispatchEvent(new Event('change', { bubbles: true }));
            }

            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var body = form.querySelector('[name="tpl_body"]');
            if (body) body.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
</script>
@endpush
