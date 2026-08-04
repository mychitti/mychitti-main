@extends('layouts.vendor.app')

@section('title', translate('Automatic Message Templates'))

@push('css_or_js')
    <style>
        .tr-card { border-left: 3px solid #e7eaf3; }
        .tr-card.tr-broken { border-left-color: #de4437; }
        .tr-card.tr-bound { border-left-color: #00c9a7; }
        .tr-slot { font-size: 11px; background: #f8fafd; border: 1px solid #e7eaf3; border-radius: 3px; padding: 1px 6px; }
    </style>
@endpush

@section('content')
    {{-- Built by concatenation: a literal pair of braces sitting next to each other in the
         source is read by Blade as an echo and breaks the whole file. --}}
    @php($ob = '{' . '{')
    @php($cb = '}' . '}')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-message" style="font-size:22px;"></i></span>
                <span>{{ translate('Automatic Message Templates') }}</span>
            </h1>
            <p class="text-muted mb-0" style="font-size:13px;">
                {{ translate('Some messages are sent for you automatically. Tell us which of your approved templates each one should use — if you deleted a suggested template and wrote your own, pick it here.') }}
            </p>
        </div>

        @if (!$connected)
            <div class="alert alert-warning">
                {{ translate('Connect your WhatsApp number first — until then there are no templates to choose from.') }}
                <a href="{{ route('vendor.whatsapp.connect') }}">{{ translate('Connect WhatsApp') }}</a>
            </div>
        @elseif ($listError)
            <div class="alert alert-danger" style="font-size:13px;">
                {{ translate('Could not read your templates from WhatsApp') }} — {{ $listError }}
            </div>
        @endif

        @foreach ($roles as $role)
            @php($resolved = $role['resolved'])
            <div class="card mb-3 tr-card {{ !$resolved ? 'tr-broken' : ($role['current'] ? 'tr-bound' : '') }}">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="mb-1">{{ translate($role['label']) }}</h5>
                            <p class="text-muted mb-1" style="font-size:12px;">{{ translate($role['blurb']) }}</p>
                        </div>
                        <div class="text-right">
                            @if (!$resolved)
                                <span class="badge badge-soft-danger">{{ translate('Not working') }}</span>
                            @elseif ($role['current'])
                                <span class="badge badge-soft-success">{{ translate('Your template') }}</span>
                            @else
                                <span class="badge badge-soft-secondary">{{ translate('Suggested template') }}</span>
                            @endif
                        </div>
                    </div>

                    @if (!$resolved)
                        <div class="alert alert-danger py-2" style="font-size:12px;">
                            <b>{{ translate('These messages are not being sent.') }}</b>
                            {{ translate('The suggested template') }} <code>{{ $role['default'] }}</code>
                            {{ translate('is not on your WhatsApp account, and you have not chosen a replacement.') }}
                            @if ($role['missing'])
                                <br>{{ translate('Last attempt') }}: {{ \Carbon\Carbon::parse($role['missing'])->diffForHumans() }}.
                            @endif
                        </div>

                        <div class="alert alert-info py-2" style="font-size:12px;">
                            <b>{{ translate('Re-creating it with the same name will not work yet.') }}</b>
                            {{ translate('When a template is deleted, WhatsApp reserves its name for') }}
                            {{ $lockDays }} {{ translate('days — so') }} <code>{{ $role['default'] }}</code>
                            {{ translate('cannot be used again until that period is over.') }}
                            @if (!empty($role['suggested']))
                                <br class="mb-1">
                                {{ translate('Create a template under a different name instead, for example') }}:
                                @foreach ($role['suggested'] as $suggestion)
                                    <code>{{ $suggestion }}</code>@if (!$loop->last), @endif
                                @endforeach
                                <br>
                                {{ translate('Then come back here and pick it — the name does not matter, only that it takes the values listed below.') }}
                            @endif
                            <br>
                            <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn--primary mt-2">
                                {{ translate('Create a template') }}
                            </a>
                        </div>
                    @else
                        <p class="mb-2" style="font-size:12px;">
                            {{ translate('Currently sending') }}
                            <code>{{ $resolved['name'] }}</code>
                            <span class="text-muted">({{ $resolved['language'] }})</span>
                        </p>
                    @endif

                    <div class="mb-2">
                        <span class="text-muted" style="font-size:11px;">{{ translate('This message fills') }} {{ $role['need'] }} {{ translate('value(s), in this order') }}:</span>
                        @foreach ($role['params'] as $i => $param)
                            <span class="tr-slot">{{ $ob . ($i + 1) . $cb }} {{ translate($param) }}</span>
                        @endforeach
                    </div>

                    <form method="post" action="{{ route('vendor.whatsapp.template-roles.save') }}" class="form-row align-items-end">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role['key'] }}">
                        <div class="form-group col-md-8 mb-2">
                            <label class="input-label mb-1" style="font-size:12px;">{{ translate('Use this template') }}</label>
                            <select name="template" class="form-control form-control-sm js-tr-select"
                                data-role="{{ $role['key'] }}" {{ empty($role['options']) ? 'disabled' : '' }}>
                                <option value="">{{ translate('— Use the suggested template —') }} ({{ $role['default'] }})</option>
                                @foreach ($role['options'] as $option)
                                    <option value="{{ $option['name'] }}" data-lang="{{ $option['language'] }}"
                                        {{ $role['current'] === $option['name'] ? 'selected' : '' }}>
                                        {{ $option['name'] }} ({{ $option['language'] }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="language" class="js-tr-lang" value="">
                            @if (empty($role['options']) && $connected)
                                <small class="form-text text-danger" style="font-size:11px;">
                                    {{ translate('None of your approved templates take exactly') }} {{ $role['need'] }}
                                    {{ translate('value(s), so none can be used here.') }}
                                    <a href="{{ route('vendor.whatsapp.templates') }}">{{ translate('Create one') }}</a>.
                                </small>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-2">
                            <button class="btn btn-sm btn--primary btn-block" {{ empty($role['options']) ? 'disabled' : '' }}>
                                {{ translate('Save') }}
                            </button>
                        </div>
                    </form>

                    @if (!empty($role['rejected']))
                        <details class="mt-1">
                            <summary class="text-muted" style="font-size:11px;cursor:pointer;">
                                {{ count($role['rejected']) }} {{ translate('of your templates cannot be used here') }}
                            </summary>
                            <div class="mt-2">
                                @foreach ($role['rejected'] as $bad)
                                    <div style="font-size:11px;" class="text-muted">
                                        <code>{{ $bad['name'] }}</code> — {{ translate('takes') }} {{ $bad['vars'] }},
                                        {{ translate('needs') }} {{ $role['need'] }}
                                    </div>
                                @endforeach
                                <small class="text-muted d-block mt-1" style="font-size:11px;">
                                    {{ translate('A template can only be used here if it takes exactly the values listed above, in the same order — otherwise the wrong wording lands in the wrong place.') }}
                                </small>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('script_2')
    <script>
        // Carry the chosen template's language along with its name — Meta stores a template per
        // language, and sending the wrong one is answered with error 132001.
        document.querySelectorAll('.js-tr-select').forEach(function (select) {
            var sync = function () {
                var opt = select.options[select.selectedIndex];
                var lang = opt ? opt.getAttribute('data-lang') : '';
                var field = select.form.querySelector('.js-tr-lang');
                if (field) { field.value = lang || ''; }
            };
            sync();
            select.addEventListener('change', sync);
        });
    </script>
@endpush
