    @php($ob = '{' . '{')
    @php($cb = '}' . '}')

        {{-- Choosing a template is a property of one message, so the everyday version of this now
             lives on the message itself under Send Notifications. What stays here is the repair
             bench: roles whose bound template was deleted at Meta, name suggestions around the
             30-day name lock, and the variable-count detail behind a rejected choice. --}}
        <div class="alert alert-info d-flex align-items-center flex-wrap" style="font-size:13px; gap:8px;">
            <span class="mr-auto">
                {{ translate('Switching a message to your own template is now done on the message itself.') }}
                {{ translate('This page is for repairs — a template Meta deleted, or one that will not fit.') }}
            </span>
            <a href="{{ route('vendor.notification-settings', 'send') }}" class="btn btn-sm btn--primary">
                {{ translate('Open Send Notifications') }}
            </a>
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

        {{-- Nineteen cards in one column is a wall. The roles arrive already ordered by group, so
             a heading whenever the group changes is enough to make it scannable. --}}
        @php($shownGroup = null)

        @foreach ($roles as $role)
            @php($resolved = $role['resolved'])
            @if (($role['group'] ?? null) && $role['group'] !== $shownGroup)
                @php($shownGroup = $role['group'])
                <h6 class="text-muted text-uppercase mt-4 mb-2" style="font-size:11px;letter-spacing:.06em;">
                    {{ translate($role['group']) }}
                </h6>
            @endif
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

                    {{-- The variable count is filtered for automatically; a media header is not, and
                         Meta rejects the whole message when the payload does not match the shape the
                         template was approved with. Say so rather than let it fail at send. --}}
                    @if (!empty($role['header']))
                        <div class="alert alert-warning py-2 mb-2" style="font-size:11px;">
                            {{ translate('This message carries a file, so the template you pick must also have a') }}
                            <b>{{ $role['header'] }}</b> {{ translate('header — a body-only template will be refused by WhatsApp when the message is sent.') }}
                        </div>
                    @endif

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
