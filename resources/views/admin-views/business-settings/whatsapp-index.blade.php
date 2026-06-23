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
            <a href="{{ route('admin.business-settings.third-party.whatsapp-report') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-chart-bar-4"></i> {{ translate('Delivery Report') }}
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ env('APP_MODE') != 'demo' ? route('admin.business-settings.third-party.whatsapp-config-update') : 'javascript:' }}" method="post">
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
                                        <input type="text" class="form-control" value="{{ url('whatsapp/webhook') }}" readonly onclick="this.select()">
                                        <small class="text-muted">{{ translate('Paste this in Meta → WhatsApp → Configuration → Webhook.') }}</small>
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
                                <div class="col-12"><hr class="my-2"><b class="text-muted" style="font-size:12px;">{{ translate('EMBEDDED SIGNUP (vendor self-connect, DoubleTick-style)') }}</b></div>
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
    </div>
@endsection
