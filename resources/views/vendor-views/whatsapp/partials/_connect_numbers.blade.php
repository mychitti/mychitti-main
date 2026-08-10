
        @if (!count($numbers))
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="tio-android-phone" style="font-size:38px; color:#bdc5d1;"></i>
                    <h4 class="mt-3 mb-1">{{ translate('No number connected yet') }}</h4>
                    <p class="text-muted" style="font-size:13px;">
                        {{ translate('Connect your first WhatsApp Business number to start sending.') }}
                    </p>
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-primary">
                        <i class="tio-add-circle mr-1"></i> {{ translate('Connect a number') }}
                    </a>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                {{ translate('Connected numbers') }}
                                <span class="wn-slot ml-1">
                                    {{ count($numbers) }}@if ($limit > 0) / {{ $limit }} @endif
                                </span>
                            </h5>
                            @if ($limit > 0 && count($numbers) >= $limit)
                                <span class="badge badge-soft-warning">{{ translate('Limit reached') }}</span>
                            @else
                                <a href="{{ route('vendor.whatsapp.connect', ['tab' => 'connection']) }}" class="btn btn-sm btn-primary">
                                    <i class="tio-add-circle mr-1"></i> {{ translate('Add number') }}
                                </a>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                @include('vendor-views.whatsapp.partials._number_cap_note')
                            </div>
                            @foreach ($numbers as $number)
                                <div class="card wn-card mb-3 {{ $number->is_default ? 'wn-default' : '' }}">
                                    <div class="card-body py-3">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                                            <div>
                                                <span class="wn-num">{{ $number->display_phone ?: translate('Number') . ' #' . $number->id }}</span>
                                                @if ($number->is_default)
                                                    <span class="badge badge-soft-success ml-1">{{ translate('Default') }}</span>
                                                @endif
                                                @if ($number->status !== 'active')
                                                    <span class="badge badge-soft-danger ml-1">{{ translate('Inactive') }}</span>
                                                @endif
                                                <div class="wn-meta mt-1">
                                                    @if ($number->label)
                                                        <strong>{{ $number->label }}</strong> ·
                                                    @endif
                                                    @if ($number->verified_name)
                                                        {{ $number->verified_name }} ·
                                                    @endif
                                                    {{ translate('WABA') }} {{ $number->business_account_id ?: '—' }}
                                                </div>
                                            </div>
                                            <div class="text-right mt-2 mt-sm-0">
                                                @if (!$number->is_default)
                                                    <form action="{{ route('vendor.whatsapp.numbers.default') }}" method="post" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="number_id" value="{{ $number->id }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            {{ translate('Make default') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('vendor.whatsapp.disconnect') }}" method="post" class="d-inline"
                                                    onsubmit="return confirm('{{ translate('Disconnect this number? Any message type set to use it will fall back to your default number.') }}');">
                                                    @csrf
                                                    <input type="hidden" name="number_id" value="{{ $number->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        {{ translate('Disconnect') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <form action="{{ route('vendor.whatsapp.numbers.label') }}" method="post" class="mt-3">
                                            @csrf
                                            <input type="hidden" name="number_id" value="{{ $number->id }}">
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="label" class="form-control" maxlength="120"
                                                    value="{{ $number->label }}"
                                                    placeholder="{{ translate('Name this number, e.g. Front desk') }}">
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-outline-secondary">{{ translate('Save') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ translate('Which number sends what') }}</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted" style="font-size:12.5px;">
                                {{ translate('Leave anything on Default and it will follow whichever number is your default, including after you change it.') }}
                            </p>

                            @foreach ($purposes as $key => $meta)
                                @php($bound = $bindings[$key]->wa_number_id ?? null)
                                <form action="{{ route('vendor.whatsapp.numbers.bind') }}" method="post" class="mb-3">
                                    @csrf
                                    <input type="hidden" name="purpose" value="{{ $key }}">
                                    <label class="mb-1" style="font-size:13px; font-weight:600;">{{ translate($meta['label']) }}</label>
                                    <div class="wn-meta mb-1">{{ translate($meta['blurb']) }}</div>
                                    <div class="input-group input-group-sm">
                                        <select name="number_id" class="form-control">
                                            <option value="">{{ translate('Default number') }}</option>
                                            @foreach ($numbers as $number)
                                                @if ($number->status === 'active')
                                                    <option value="{{ $number->id }}" {{ $bound == $number->id ? 'selected' : '' }}>
                                                        {{ $number->display_phone ?: translate('Number') . ' #' . $number->id }}{{ $number->label ? ' — ' . $number->label : '' }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-outline-primary">{{ translate('Set') }}</button>
                                        </div>
                                    </div>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
