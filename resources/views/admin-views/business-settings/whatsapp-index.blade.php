@extends('layouts.admin.app')

@section('title', translate('WhatsApp Cloud API Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-chat" style="font-size:22px;"></i>
                </span>
                <span>{{ translate('WhatsApp Cloud API (Meta) Setup') }}</span>
            </h1>
            <div class="d-flex align-items-center" style="gap:8px;">
                <a href="{{ route('admin.business-settings.third-party.whatsapp-inbox') }}" class="btn btn-sm btn-outline-primary">
                    <i class="tio-chat"></i> {{ translate('Chats') }}
                </a>
                <a href="{{ route('admin.business-settings.third-party.whatsapp-knowledge') }}" class="btn btn-sm btn-outline-primary">
                    <i class="tio-book-opened"></i> {{ translate('Auto-Reply Knowledge') }}
                </a>
                <a href="{{ route('admin.business-settings.third-party.whatsapp-report') }}" class="btn btn-sm btn-outline-primary">
                    <i class="tio-chart-bar-4"></i> {{ translate('Delivery Report') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8" >
                <div class="card">
                    <div class="card-body">
                        <form  action="{{ env('APP_MODE') != 'demo' ? route('admin.business-settings.third-party.whatsapp-config-update') : 'javascript:' }}" method="post">
                            @csrf
                            <label class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control mb-4">
                                <span class="pr-1 d-flex align-items-center switch--label">
                                    <span class="line--limit-1">
                                        {{ (isset($config['status']) && $config['status'] == 1) ? translate('Turn OFF') : translate('Turn ON') }}
                                    </span>
                                </span>
                                <input type="checkbox" class="toggle-switch-input" name="status" value="1"
                                       {{ (isset($config['status']) && $config['status'] == 1) ? 'checked' : '' }}>
                                <span class="toggle-switch-label text p-0">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Phone Number ID') }}</label>
                                        <input type="text" class="form-control" name="phone_number_id"
                                               value="{{ $config['phone_number_id'] ?? '' }}" placeholder="e.g. 123456789012345">
                                        <small class="text-muted">{{ translate('From Meta › WhatsApp › API Setup') }}</small>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('WhatsApp Business Account ID') }}</label>
                                        <input type="text" class="form-control" name="business_account_id"
                                               value="{{ $config['business_account_id'] ?? '' }}" placeholder="Optional">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Permanent Access Token') }}</label>
                                        <textarea class="form-control" name="token" rows="3" placeholder="EAAG...">{{ $config['token'] ?? '' }}</textarea>
                                        <small class="text-muted">{{ translate('Use a permanent System User token — temporary tokens expire in 24h.') }}</small>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Graph API Version') }}</label>
                                        <input type="text" class="form-control" name="api_version"
                                               value="{{ $config['api_version'] ?? 'v21.0' }}" placeholder="v21.0">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Default Country Code') }}</label>
                                        <input type="text" class="form-control" name="default_country_code"
                                               value="{{ $config['default_country_code'] ?? '91' }}" placeholder="91">
                                        <small class="text-muted">{{ translate('Prefixed to local numbers without a country code.') }}</small>
                                    </div>
                                </div>
                                <div class="col-12"><hr class="my-2"><b class="text-muted" style="font-size:12px;">{{ translate('WEBHOOK (delivery status & replies)') }}</b></div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Callback URL') }}</label>
                                        <input type="text" class="form-control" value="https://vendor.mcvendorhub.com/whatsapp/webhook" readonly onclick="this.select()">
                                        <small class="text-muted">{{ translate('Paste this in Meta → WhatsApp → Configuration → Webhook. Served by the vendor panel — all servers share the same database.') }}</small>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Verify Token') }}</label>
                                        <input type="text" class="form-control" name="verify_token"
                                               value="{{ $config['verify_token'] ?? '' }}" placeholder="{{ translate('a secret you choose') }}">
                                        <small class="text-muted">{{ translate('Any secret string — must match what you enter in Meta. Subscribe to the "messages" field.') }}</small>
                                    </div>
                                </div>
                                <div class="col-12"><hr class="my-2"><b class="text-muted" style="font-size:12px;">{{ translate('AI AUTO-REPLY') }}</b></div>
                                <div class="col-12">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="waAutoReply" name="auto_reply" value="1"
                                               {{ (!isset($config['auto_reply']) || $config['auto_reply']) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="waAutoReply">
                                            {{ translate('Auto-reply to vendors & customers who message the MyChitti number') }}
                                            <small class="text-muted d-block">{{ translate('Answers come from your') }}
                                                <a href="{{ route('admin.business-settings.third-party.whatsapp-knowledge') }}">{{ translate('Auto-Reply Knowledge') }}</a>.
                                                {{ translate('Stays silent if no knowledge is saved.') }}</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12"><hr class="my-2"><b class="text-muted" style="font-size:12px;">{{ translate('EMBEDDED SIGNUP (vendor self-connect)') }}</b></div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Facebook App ID') }}</label>
                                        <input type="text" class="form-control" name="es_app_id"
                                               value="{{ $config['es_app_id'] ?? '' }}" placeholder="928989069726887">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('App Secret') }}</label>
                                        <input type="text" class="form-control" name="es_app_secret"
                                               value="{{ $config['es_app_secret'] ?? '' }}" placeholder="App → Settings → Basic">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Embedded Signup config_id') }}</label>
                                        <input type="text" class="form-control" name="es_config_id"
                                               value="{{ $config['es_config_id'] ?? '' }}" placeholder="{{ translate('from your ES configuration') }}">
                                    </div>
                                </div>
                                <div class="col-12"><hr class="my-2"><b class="text-muted" style="font-size:12px;">{{ translate('VENDOR LEAD NOTIFICATIONS (paid add-on)') }}</b></div>
                                <div class="col-sm-8">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Lead Notification Template') }}</label>
                                        <input type="text" class="form-control" name="lead_template"
                                               value="{{ $config['lead_template'] ?? '' }}" placeholder="e.g. vendor_lead_alert">
                                        <small class="text-muted">{{ translate('Approved template sent to vendors when a lead is assigned. Variables in order: store name, service, client name. Leave blank to send plain text (testing only).') }}</small>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Template Language') }}</label>
                                        <input type="text" class="form-control" name="lead_template_lang"
                                               value="{{ $config['lead_template_lang'] ?? 'en_US' }}" placeholder="en_US">
                                    </div>
                                </div>
                            </div>

                            <div class="btn--container justify-content-end">
                                <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}" class="btn btn--primary">{{ translate('messages.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title">{{ translate('Send Test Message') }}</h5></div>
                    <div class="card-body">
                        <form action="{{ env('APP_MODE') != 'demo' ? route('admin.business-settings.third-party.whatsapp-config-test') : 'javascript:' }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ translate('Recipient Phone') }}</label>
                                <input type="text" class="form-control" name="test_phone" placeholder="91XXXXXXXXXX" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Message') }} <span class="text-muted">({{ translate('free-form') }})</span></label>
                                <textarea class="form-control" name="test_message" rows="2" placeholder="Hello from {{ config('app.name') }}"></textarea>
                            </div>

                            <div class="border rounded p-2 mb-2" style="background:#fafbff;">
                                <small class="d-block text-muted mb-2"><b>{{ translate('OR send an approved template') }}</b> — {{ translate('required for business-initiated messages. Fill these to send a template instead of the text above.') }}</small>
                                <div class="form-group mb-2">
                                    <label class="form-label mb-1">{{ translate('Template name') }}</label>
                                    <input type="text" class="form-control" name="test_template" placeholder="e.g. order_reminder">
                                </div>
                                <div class="row">
                                    <div class="col-5">
                                        <div class="form-group mb-1">
                                            <label class="form-label mb-1">{{ translate('Language') }}</label>
                                            <input type="text" class="form-control" name="test_lang" value="en_US">
                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <div class="form-group mb-1">
                                            <label class="form-label mb-1">{{ translate('Variables') }}</label>
                                            <input type="text" class="form-control" name="test_vars" placeholder="John | KHB_3 | 12 Dec">
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">{{ translate('Pipe-separate ( | ) the body values in order — first value fills variable 1, second fills variable 2, and so on. Leave blank if the template has no variables.') }}</small>
                            </div>

                            <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}" class="btn btn--primary btn-block">{{ translate('Send Test') }}</button>
                        </form>
                        <div class="alert alert-info mt-3 mb-0" style="font-size:12px;">
                            <b>{{ translate('Note') }}:</b>
                            {{ translate('The recipient is a normal WhatsApp number (not a business account). On the Meta test number, the recipient must be added to your allowed list. Configure the webhook to see why a message failed in the Delivery Report.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="tio-receipt"></i> {{ translate('Message Templates') }}
                            <span class="text-muted" style="font-size:12px;">— {{ translate('Business Management API') }}</span>
                        </h5>
                        <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="tio-refresh"></i> {{ translate('Refresh') }}
                        </a>
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
                                                <tr><td colspan="5" class="text-center text-muted">{{ translate('No templates found. Add the Business Account ID above, then create one.') }}</td></tr>
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
