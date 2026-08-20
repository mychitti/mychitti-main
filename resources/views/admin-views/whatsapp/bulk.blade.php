{{-- Bulk WhatsApp from the MyChitti platform number: the composer and the record of what
     previous batches did, on one page. Vendors have their own version of this on their own
     WABA — nothing here touches theirs. --}}
@extends('layouts.admin.app')

@section('title', translate('WhatsApp Bulk Message'))

@push('css_or_js')
<style>
    .wbulk-pill { font-size:13px; padding:6px 14px; }
    .wbulk-list { max-height:280px; overflow-y:auto; }
    .wbulk-row { display:flex; align-items:center; gap:10px; padding:8px 14px; margin:0; cursor:pointer; border-bottom:1px solid #f2f4f8; font-size:13px; }
    .wbulk-row:hover { background:#f8f9fb; }
    .wbulk-note { font-size:11.5px; }
    .wbulk-stat { border:1px solid #e7eaf3; border-radius:10px; padding:14px 16px; background:#fff; height:100%; }
    .wbulk-stat-val { font-size:22px; font-weight:600; line-height:1.2; }
    .wbulk-stat-lbl { font-size:12px; color:#677788; }
    .wbulk-preview { white-space:pre-wrap; font-size:13px; }
</style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0">
                    <span class="page-header-icon"><i class="tio-send" style="font-size:22px;"></i></span>
                    <span>{{ translate('WhatsApp Bulk Message') }}</span>
                </h1>
                <small class="text-muted">
                    {{ translate('Send one approved template from the MyChitti number to vendors, customers or a list of numbers.') }}
                </small>
            </div>
            <a href="{{ route('admin.business-settings.third-party.whatsapp-templates') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-receipt"></i> {{ translate('Manage templates') }}
            </a>
        </div>

        @if (!$connected)
            <div class="alert alert-warning">
                <b>{{ translate('Not configured.') }}</b>
                {{ translate('Set up the MyChitti WhatsApp number in') }}
                <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="alert-link">{{ translate('WhatsApp API') }}</a>
                {{ translate('before sending anything.') }}
            </div>
        @else
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'compose' ? 'active' : '' }}" data-toggle="tab" href="#wbCompose" role="tab">
                        <i class="tio-send"></i> {{ translate('Send a message') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'history' ? 'active' : '' }}" data-toggle="tab" href="#wbHistory" role="tab">
                        <i class="tio-history"></i> {{ translate('History') }}
                        <span class="badge badge-soft-secondary ml-1">{{ number_format($totals->runs ?? 0) }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- ------------------------------------------------------------------ compose --}}
                <div class="tab-pane fade {{ $tab === 'compose' ? 'show active' : '' }}" id="wbCompose" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="tio-send"></i> {{ translate('Compose') }}</h5>
                                </div>
                                <div class="card-body">
                                    @if ($templateError)
                                        <div class="alert alert-warning" style="font-size:13px;">
                                            {{ translate('Could not load the platform templates:') }} {{ $templateError }}
                                        </div>
                                    @endif

                                    @if (empty($templates))
                                        <p class="text-muted mb-2">
                                            {{ translate('There are no approved templates on the MyChitti WhatsApp account yet. WhatsApp only allows business-initiated messages using a template Meta has approved.') }}
                                        </p>
                                        <a href="{{ route('admin.business-settings.third-party.whatsapp-templates') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="tio-receipt"></i> {{ translate('Create a template') }}
                                        </a>
                                    @else
                                        <div class="form-group">
                                            <label class="font-weight-bold" style="font-size:13px;">{{ translate('Template') }}</label>
                                            <select id="wb-template" class="form-control">
                                                <option value="">— {{ translate('Select a template') }} —</option>
                                                @foreach ($templates as $i => $t)
                                                    <option value="{{ $i }}" @if ($t['unsupported']) disabled @endif>
                                                        {{ $t['name'] }} ({{ $t['language'] }})@if ($t['unsupported']) — {{ translate('not supported here,') }} {{ $t['unsupported'] }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- A template approved with a file at the top needs that file on every
                                             message, or Graph rejects the whole send with error 132012. --}}
                                        <div id="wb-media" class="form-group" style="display:none;">
                                            <label class="font-weight-bold" style="font-size:13px;">
                                                <span id="wb-media-label">{{ translate('Image') }}</span>
                                                {{ translate('for this template') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" id="wb-media-file" class="form-control-file" accept="image/jpeg,image/png">
                                            <small class="form-text text-muted wbulk-note">
                                                {{ translate('Max 5 MB. The same file goes to everyone in this send.') }}
                                            </small>
                                            <div id="wb-media-status" class="mt-2" style="font-size:12px;"></div>
                                            <img id="wb-media-preview" src="" alt="" class="mt-2 rounded border"
                                                 style="display:none;max-height:120px;max-width:100%;">
                                        </div>

                                        <div id="wb-preview" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                                            <div class="text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">
                                                {{ translate('Message preview') }}
                                            </div>
                                            <div id="wb-preview-body" class="wbulk-preview"></div>
                                        </div>

                                        <div id="wb-vars" class="mb-3"></div>

                                        <hr>

                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label class="font-weight-bold mb-0" style="font-size:13px;">{{ translate('Who receives it') }}</label>
                                            <span id="wb-selected-count" class="badge badge-soft-secondary">0 {{ translate('selected') }}</span>
                                        </div>

                                        <ul class="nav nav-pills mb-3" style="gap:6px;">
                                            <li class="nav-item">
                                                <a href="javascript:;" class="nav-link active wbulk-pill wb-aud" data-aud="vendors">
                                                    {{ translate('Vendors') }}
                                                    <span class="badge badge-soft-light ml-1">{{ number_format($counts['vendors']) }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="javascript:;" class="nav-link wbulk-pill wb-aud" data-aud="customers">
                                                    {{ translate('Customers') }}
                                                    <span class="badge badge-soft-light ml-1">{{ number_format($counts['customers']) }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="javascript:;" class="nav-link wbulk-pill wb-aud" data-aud="manual">
                                                    {{ translate('Pasted numbers') }}
                                                </a>
                                            </li>
                                        </ul>

                                        <div id="wb-pane-list">
                                            <div class="form-row mb-2">
                                                <div class="col-md-4 mb-2">
                                                    <select id="wb-zone" class="form-control form-control-sm">
                                                        <option value="">{{ translate('All zones') }}</option>
                                                        @foreach ($zones as $zone)
                                                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-2" id="wb-status-wrap">
                                                    <select id="wb-status" class="form-control form-control-sm">
                                                        <option value="active">{{ translate('Active stores only') }}</option>
                                                        <option value="all">{{ translate('All stores') }}</option>
                                                    </select>
                                                </div>
                                                {{-- The trade a vendor signed up under. Store-only, like the status
                                                     filter beside it — customers have no category. --}}
                                                <div class="col-md-4 mb-2" id="wb-category-wrap">
                                                    <select id="wb-category" class="form-control form-control-sm">
                                                        <option value="">{{ translate('All categories') }}</option>
                                                        @foreach ($categories ?? [] as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->stores }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <input id="wb-search" type="text" class="form-control form-control-sm"
                                                           placeholder="{{ translate('Search name or phone…') }}">
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center mb-2" style="gap:8px;">
                                                <button id="wb-select-page" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">
                                                    {{ translate('Select all shown') }}
                                                </button>
                                                <button id="wb-clear" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">
                                                    {{ translate('Clear') }}
                                                </button>
                                                <span id="wb-total" class="text-muted ml-auto" style="font-size:12px;"></span>
                                            </div>

                                            <div id="wb-list" class="border rounded wbulk-list">
                                                <div class="text-muted text-center p-3" style="font-size:13px;">{{ translate('Loading…') }}</div>
                                            </div>

                                            {{-- The way a 90,000-person send is addressed: by size, not by ticking
                                                 90,000 boxes. The server walks the audience batch by batch and
                                                 claims each number as it goes. --}}
                                            <div class="custom-control custom-checkbox mt-3">
                                                <input type="checkbox" class="custom-control-input" id="wb-all">
                                                <label class="custom-control-label" for="wb-all" style="font-size:13px;">
                                                    <span id="wb-all-label">{{ translate('Send to everyone matching these filters') }}</span>
                                                </label>
                                            </div>
                                            <div id="wb-all-box" class="form-inline mt-2" style="display:none;gap:8px;">
                                                <label class="mr-2 mb-0" style="font-size:12px;">{{ translate('Send at most') }}</label>
                                                <input id="wb-all-limit" type="number" min="1" class="form-control form-control-sm" style="max-width:140px;" value="0">
                                                <small class="text-muted ml-2 wbulk-note">
                                                    {{ translate('Leave as-is to reach the whole filtered audience.') }}
                                                </small>
                                            </div>
                                        </div>

                                        <div id="wb-pane-manual" style="display:none;">
                                            <label style="font-size:12px;" class="mb-1">{{ translate('Phone numbers') }}</label>
                                            <textarea id="wb-numbers" class="form-control" rows="6"
                                                      placeholder="9876543210&#10;9876501234&#10;+91 98765 00000"></textarea>
                                            <small class="form-text text-muted wbulk-note">
                                                {{ translate('One per line, or separated by commas. Numbers without a country code are treated as Indian numbers. Anyone who has opted out is skipped automatically.') }}
                                            </small>
                                            <div id="wb-numbers-count" class="mt-1" style="font-size:12px;"></div>
                                        </div>

                                        <div class="custom-control custom-checkbox mt-3">
                                            <input type="checkbox" class="custom-control-input" id="wb-skip" checked>
                                            <label class="custom-control-label" for="wb-skip" style="font-size:13px;">
                                                {{ translate('Skip anyone the platform already messaged in the last 30 days') }}
                                            </label>
                                        </div>
                                        <small class="text-muted d-block wbulk-note mb-3">
                                            {{ translate('Keeps a repeated or resumed send from reaching the same people twice. Applies to the vendor and customer audiences.') }}
                                        </small>

                                        <div id="wb-summary" class="border rounded p-2 mb-3" style="display:none;font-size:12px;"></div>

                                        <div class="d-flex align-items-center" style="gap:12px;">
                                            <button id="wb-send" class="btn btn-primary" disabled>{{ translate('Send') }}</button>
                                            {{-- The send runs on the server, so it outlives this page —
                                                 which is exactly why it needs a way to be called off. --}}
                                            <span id="wb-stop-wrap" style="display:none;">
                                                <button id="wb-stop" class="btn btn-outline-danger btn-sm" type="button">{{ translate('Stop') }}</button>
                                            </span>
                                            <div id="wb-progress" class="flex-grow-1" style="display:none;">
                                                <div class="progress" style="height:6px;">
                                                    <div id="wb-progress-bar" class="progress-bar bg-success" style="width:0%;"></div>
                                                </div>
                                                <small id="wb-progress-text" class="text-muted"></small>
                                            </div>
                                        </div>

                                        <div id="wb-results" class="mt-3" style="display:none;"></div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ translate('Reachable now') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span style="font-size:13px;">{{ translate('Vendors') }}</span>
                                        <b>{{ number_format($counts['vendors']) }}</b>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span style="font-size:13px;">{{ translate('Customers') }}</span>
                                        <b>{{ number_format($counts['customers']) }}</b>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span style="font-size:13px;">{{ translate('Opted out') }}</span>
                                        <b class="text-danger">{{ number_format($optOutCount) }}</b>
                                    </div>
                                    <small class="text-muted d-block mt-2 wbulk-note">
                                        {{ translate('Counts exclude everyone who replied STOP or switched WhatsApp off in their account, and are refreshed every 10 minutes.') }}
                                    </small>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ translate('Before you send') }}</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="pl-3 mb-0 text-muted" style="font-size:12.5px;line-height:1.7;">
                                        <li>{{ translate('This sends from the MyChitti platform number, not from any vendor account.') }}</li>
                                        <li>{{ translate('Only templates Meta has approved can be sent — anything else is refused at their end.') }}</li>
                                        <li>{{ translate('Anyone who replies STOP is excluded from this and every future send, automatically.') }}</li>
                                        <li>{{ translate('A broken run can be sent again safely: everyone already messaged in it is skipped, not messaged twice.') }}</li>
                                        <li>{{ translate('Marketing blasts to people who did not ask for them cost the number its quality rating.') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ------------------------------------------------------------------ history --}}
                <div class="tab-pane fade {{ $tab === 'history' ? 'show active' : '' }}" id="wbHistory" role="tabpanel">
                    <div class="row mb-3" style="row-gap:12px;">
                        <div class="col-sm-6 col-lg-3">
                            <div class="wbulk-stat">
                                <div class="wbulk-stat-val">{{ number_format($totals->runs ?? 0) }}</div>
                                <div class="wbulk-stat-lbl">{{ translate('Batches sent') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="wbulk-stat">
                                <div class="wbulk-stat-val">{{ number_format($totals->recipients ?? 0) }}</div>
                                <div class="wbulk-stat-lbl">{{ translate('Numbers messaged') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="wbulk-stat">
                                <div class="wbulk-stat-val">{{ number_format($totals->last30 ?? 0) }}</div>
                                <div class="wbulk-stat-lbl">{{ translate('In the last 30 days') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="wbulk-stat">
                                <div class="wbulk-stat-val {{ ($totals->failed ?? 0) ? 'text-danger' : '' }}">
                                    {{ number_format($totals->failed ?? 0) }}
                                </div>
                                <div class="wbulk-stat-lbl">{{ translate('Failed to send') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ translate('Past sends') }}</h5>
                        </div>
                        <div class="card-body">
                            @if ($runs->isEmpty())
                                <div class="text-center text-muted py-5">
                                    <i class="tio-send-outlined" style="font-size:38px;opacity:.4;"></i>
                                    <p class="mt-2 mb-0" style="font-size:13px;">
                                        {{ translate('Nothing sent yet. Every number a batch goes to is listed here afterwards.') }}
                                    </p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-borderless table-thead-bordered table-align-middle">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>{{ translate('Sent') }}</th>
                                                <th>{{ translate('Message') }}</th>
                                                <th>{{ translate('Audience') }}</th>
                                                <th class="text-center">{{ translate('Numbers') }}</th>
                                                <th class="text-center">{{ translate('Sent') }}</th>
                                                <th class="text-center">{{ translate('Failed') }}</th>
                                                <th class="text-right">&nbsp;</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($runs as $run)
                                                <tr>
                                                    <td class="text-nowrap">
                                                        <b>{{ \Carbon\Carbon::parse($run->started_at)->format('d M Y') }}</b>
                                                        <div class="text-muted" style="font-size:12px;">
                                                            {{ \Carbon\Carbon::parse($run->started_at)->format('h:i A') }}
                                                            @if ($run->finished_at && $run->finished_at !== $run->started_at)
                                                                – {{ \Carbon\Carbon::parse($run->finished_at)->format('h:i A') }}
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td style="max-width:340px;">
                                                        <b>{{ $run->template ?: translate('Template') }}</b>
                                                        @if ($run->body)
                                                            <div class="text-muted" style="font-size:12px;white-space:normal;">
                                                                {{ \Illuminate\Support\Str::limit($run->body, 110) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="text-nowrap">
                                                        {{ \App\Http\Controllers\Admin\WhatsAppBulkController::audienceLabel($run->audience) }}
                                                    </td>
                                                    <td class="text-center font-weight-bold">{{ number_format($run->recipients) }}</td>
                                                    <td class="text-center text-success font-weight-bold">{{ number_format($run->sent) }}</td>
                                                    <td class="text-center {{ $run->failed ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                                        {{ number_format($run->failed) }}
                                                    </td>
                                                    <td class="text-right text-nowrap">
                                                        <a href="{{ route('admin.business-settings.third-party.whatsapp-bulk.run', $run->run_id) }}"
                                                           class="btn btn-sm btn-outline-secondary">{{ translate('View numbers') }}</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-2 py-2">{!! $runs->links() !!}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('script_2')
    <script>
        // Keep the open tab in the URL so a reload or a shared link comes back to the same pane.
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var map = { '#wbCompose': 'compose', '#wbHistory': 'history' };
            var tab = map[$(e.target).attr('href')];
            if (tab && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }
        });
    </script>
@endpush

@if ($connected && !empty($templates))
    @push('script_2')
        @include('admin-views.whatsapp.partials._bulk_js')
    @endpush
@endif
