@extends('layouts.admin.app')

@section('title', translate('WhatsApp Template Presets'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-receipt" style="font-size:22px;"></i>
                </span>
                <span>{{ translate('WhatsApp Template Presets') }}</span>
            </h1>
            <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-settings"></i> {{ translate('WhatsApp Setup') }}
            </a>
        </div>

        <div class="alert alert-info" style="font-size:13px;">
            <i class="tio-info-outined"></i>
            {{ translate('Presets are ready-made templates vendors can pick from. Nothing is sent to Meta from here — when a vendor selects a preset, it is submitted to that vendor\'s own WhatsApp Business Account for Meta review.') }}
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">{{ translate('Presets') }}</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-align-middle table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('Title') }}</th>
                                        <th>{{ translate('Template Name') }}</th>
                                        <th>{{ translate('Category') }}</th>
                                        <th class="text-center">{{ translate('Visible') }}</th>
                                        <th class="text-right">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($presets as $preset)
                                        <tr>
                                            <td>
                                                <b>{{ $preset->title }}</b>
                                                <small class="text-muted d-block text-truncate" style="max-width:260px;">{{ $preset->body }}</small>
                                            </td>
                                            <td><code>{{ $preset->name }}</code></td>
                                            <td><span class="badge badge-soft-secondary">{{ $preset->category }}</span></td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.business-settings.third-party.whatsapp-preset-toggle') }}" method="post" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $preset->id }}">
                                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="{{ $preset->active ? translate('Hide from vendors') : translate('Show to vendors') }}">
                                                        <span class="badge badge-soft-{{ $preset->active ? 'success' : 'secondary' }}">{{ $preset->active ? translate('Visible') : translate('Hidden') }}</span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-right text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary wa-preset-edit"
                                                        data-id="{{ $preset->id }}" data-title="{{ $preset->title }}"
                                                        data-name="{{ $preset->name }}" data-category="{{ $preset->category }}"
                                                        data-language="{{ $preset->language }}" data-header="{{ $preset->header }}"
                                                        data-body="{{ $preset->body }}" data-footer="{{ $preset->footer }}"
                                                        data-example="{{ $preset->example }}" data-btntext="{{ $preset->btn_text }}"
                                                        data-btnurl="{{ $preset->btn_url }}"
                                                        data-btnphone="{{ $preset->btn_phone ?? '' }}"
                                                        data-btnphonetext="{{ $preset->btn_phone_text ?? '' }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <form action="{{ route('admin.business-settings.third-party.whatsapp-preset-delete') }}" method="post" class="d-inline"
                                                      onsubmit="return confirm('{{ translate('Delete this preset? Templates vendors already submitted are not affected.') }}');">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $preset->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-4 text-muted">{{ translate('No presets yet. Create one on the right.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">{{ translate('Add Preset') }}</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.business-settings.third-party.whatsapp-preset-save') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ translate('Title') }}</label>
                                <input type="text" class="form-control" name="title" value="{{ old('title') }}" placeholder="{{ translate('Welcome Message') }}" required>
                                <small class="text-muted">{{ translate('What the vendor sees in the list.') }}</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Template Name') }}</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="customer_welcome" required>
                                <small class="text-muted">{{ translate('Lowercase letters, numbers and underscores only — this becomes the template name on the vendor\'s WABA.') }}</small>
                            </div>
                            <div class="row">
                                <div class="col-7">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Category') }}</label>
                                        <select class="form-control" name="category" required>
                                            <option value="UTILITY">UTILITY</option>
                                            <option value="MARKETING">MARKETING</option>
                                            <option value="AUTHENTICATION">AUTHENTICATION</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Language') }}</label>
                                        <input type="text" class="form-control" name="language" value="{{ old('language', 'en_US') }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Header') }} <small class="text-muted">({{ translate('optional') }})</small></label>
                                <input type="text" class="form-control" name="header" value="{{ old('header') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Body') }}</label>
                                <textarea class="form-control wa-tpl-body" name="body" rows="4" placeholder="Hi @{{customer_name}}, welcome to @{{1}}!" required>{{ old('body') }}</textarea>
                                <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;">
                                    <small class="text-muted">{{ translate('Insert') }}:</small>
                                    @foreach (\App\Services\WhatsAppService::TEMPLATE_VARIABLES as $key => $meta)
                                        <button type="button" class="btn btn-sm btn-outline-primary wa-var-insert" data-var="{{ $key }}">
                                            <i class="tio-add"></i> {{ translate($meta['label']) }}
                                        </button>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-1">
                                    {{ translate('The buttons above insert a variable filled in per recipient when the message is sent — no example value is needed for those. Use') }}
                                    @{{1}}, @{{2}} {{ translate('for variables the vendor fills in, but do not mix the two styles. The message must not start or end with a variable.') }}
                                </small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Footer') }} <small class="text-muted">({{ translate('optional') }})</small></label>
                                <input type="text" class="form-control" name="footer" value="{{ old('footer') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Example Values') }}</label>
                                <input type="text" class="form-control" name="example" value="{{ old('example') }}" placeholder="Ramesh | Krishna Hospital">
                                <small class="text-muted">{{ translate('Pipe-separate ( | ) one sample value per variable. Required by Meta when the body has variables.') }}</small>
                            </div>
                            <div class="row">
                                <div class="col-5">
                                    <div class="form-group mb-1">
                                        <label class="form-label mb-1">{{ translate('Button Text') }}</label>
                                        <input type="text" class="form-control" name="btn_text" value="{{ old('btn_text') }}">
                                    </div>
                                </div>
                                <div class="col-7">
                                    <div class="form-group mb-1">
                                        <label class="form-label mb-1">{{ translate('Button URL') }}</label>
                                        <input type="url" class="form-control" name="btn_url" value="{{ old('btn_url') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-5">
                                    <div class="form-group mb-1">
                                        <label class="form-label mb-1">{{ translate('Call Button') }}</label>
                                        <select class="form-control wa-preset-call-mode" name="btn_phone_mode">
                                            <option value="none" {{ old('btn_phone_mode') === 'none' ? 'selected' : '' }}>{{ translate('None') }}</option>
                                            <option value="store" {{ old('btn_phone_mode') === 'store' ? 'selected' : '' }}>{{ translate("Vendor's own number") }}</option>
                                            <option value="fixed" {{ old('btn_phone_mode') === 'fixed' ? 'selected' : '' }}>{{ translate('A fixed number') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group mb-1 wa-preset-call-field" style="display:none;">
                                        <label class="form-label mb-1">{{ translate('Call Label') }}</label>
                                        <input type="text" class="form-control" name="btn_phone_text" maxlength="25"
                                               value="{{ old('btn_phone_text') }}" placeholder="Call now">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group mb-1 wa-preset-call-number" style="display:none;">
                                        <label class="form-label mb-1">{{ translate('Number') }}</label>
                                        <input type="tel" class="form-control" name="btn_phone"
                                               value="{{ old('btn_phone') }}" placeholder="+91 98765 43210">
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ translate("Vendor's own number puts each store's own phone on the template they submit — a fixed number rings the same line for every vendor's customers.") }}
                            </small>
                            <button type="submit" class="btn btn--primary btn-block mt-2">{{ translate('Save Preset') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="waPresetEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.business-settings.third-party.whatsapp-preset-save') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" id="wapId">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Edit Preset') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('Title') }}</label>
                                    <input type="text" class="form-control" name="title" id="wapTitle" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('Template Name') }}</label>
                                    <input type="text" class="form-control" name="name" id="wapName" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('Category') }}</label>
                                    <select class="form-control" name="category" id="wapCategory">
                                        <option value="UTILITY">UTILITY</option>
                                        <option value="MARKETING">MARKETING</option>
                                        <option value="AUTHENTICATION">AUTHENTICATION</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ translate('Language') }}</label>
                                    <input type="text" class="form-control" name="language" id="wapLanguage" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Header') }}</label>
                            <input type="text" class="form-control" name="header" id="wapHeader">
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Body') }}</label>
                            <textarea class="form-control wa-tpl-body" name="body" id="wapBody" rows="4" required></textarea>
                            <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;">
                                <small class="text-muted">{{ translate('Insert') }}:</small>
                                @foreach (\App\Services\WhatsAppService::TEMPLATE_VARIABLES as $key => $meta)
                                    <button type="button" class="btn btn-sm btn-outline-primary wa-var-insert" data-var="{{ $key }}">
                                        <i class="tio-add"></i> {{ translate($meta['label']) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Footer') }}</label>
                            <input type="text" class="form-control" name="footer" id="wapFooter">
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Example Values') }}</label>
                            <input type="text" class="form-control" name="example" id="wapExample">
                        </div>
                        <div class="row">
                            <div class="col-5">
                                <div class="form-group mb-1">
                                    <label class="form-label mb-1">{{ translate('Button Text') }}</label>
                                    <input type="text" class="form-control" name="btn_text" id="wapBtnText">
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="form-group mb-1">
                                    <label class="form-label mb-1">{{ translate('Button URL') }}</label>
                                    <input type="url" class="form-control" name="btn_url" id="wapBtnUrl">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-5">
                                <div class="form-group mb-1">
                                    <label class="form-label mb-1">{{ translate('Call Button') }}</label>
                                    <select class="form-control wa-preset-call-mode" name="btn_phone_mode" id="wapCallMode">
                                        <option value="none">{{ translate('None') }}</option>
                                        <option value="store">{{ translate("Vendor's own number") }}</option>
                                        <option value="fixed">{{ translate('A fixed number') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group mb-1 wa-preset-call-field" style="display:none;">
                                    <label class="form-label mb-1">{{ translate('Call Label') }}</label>
                                    <input type="text" class="form-control" name="btn_phone_text" id="wapCallText"
                                           maxlength="25" placeholder="Call now">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group mb-1 wa-preset-call-number" style="display:none;">
                                    <label class="form-label mb-1">{{ translate('Number') }}</label>
                                    <input type="tel" class="form-control" name="btn_phone" id="wapCallNumber"
                                           placeholder="+91 98765 43210">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    var STORE_PHONE_TOKEN = '{{ \App\Services\WhatsAppService::STORE_PHONE_TOKEN }}';

    // A label applies to any call button; a number only to the fixed kind.
    $(document).on('change', '.wa-preset-call-mode', function () {
        var mode = $(this).val();
        var $scope = $(this).closest('form');
        $scope.find('.wa-preset-call-field').toggle(mode !== 'none');
        $scope.find('.wa-preset-call-number').toggle(mode === 'fixed');
    });
    $('.wa-preset-call-mode').trigger('change');

    $(document).on('click', '.wa-preset-edit', function () {
        var d = $(this).data();
        $('#wapId').val(d.id); $('#wapTitle').val(d.title); $('#wapName').val(d.name);
        $('#wapCategory').val(d.category || 'UTILITY'); $('#wapLanguage').val(d.language || 'en_US');
        $('#wapHeader').val(d.header || ''); $('#wapBody').val(d.body || '');
        $('#wapFooter').val(d.footer || ''); $('#wapExample').val(d.example || '');
        $('#wapBtnText').val(d.btntext || ''); $('#wapBtnUrl').val(d.btnurl || '');

        // btn_phone holds either the vendor-number token or a literal number — the mode is read
        // back off which one it is, so the admin never sees the token itself.
        var phone = String(d.btnphone || '');
        var mode = phone === STORE_PHONE_TOKEN ? 'store' : (phone ? 'fixed' : 'none');
        $('#wapCallMode').val(mode).trigger('change');
        $('#wapCallText').val(d.btnphonetext || '');
        $('#wapCallNumber').val(mode === 'fixed' ? phone : '');

        $('#waPresetEditModal').modal('show');
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
