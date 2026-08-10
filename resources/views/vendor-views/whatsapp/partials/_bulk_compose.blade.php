                <div class="row">
                <div class="col-lg-9">
                    <div class="wa-card">
                        {{-- Audience counts live in the header, not the picker below: the picker is
                             hidden until an approved template exists, and the vendor still needs to
                             see who they could reach while deciding whether to make one. --}}
                        <div class="wa-card-h">
                            <span><i class="tio-send"></i> Bulk message</span>
                            <div class="d-flex flex-wrap align-items-center" style="gap:6px;">
                                <span class="wa-chip badge-soft-info">
                                    {{ $clientCount }} of your {{ $clientCount == 1 ? 'customer' : 'customers' }}
                                </span>
                                <span class="wa-chip badge-soft-primary">
                                    {{ $platformUserCount }} MyChitti {{ $platformUserCount == 1 ? 'user' : 'users' }}
                                </span>
                                <a href="{{ route('vendor.whatsapp.bulk.history') }}"
                                   class="btn btn-xs btn-outline-secondary" style="font-size:11px;">
                                    <i class="tio-history"></i> Past sends
                                </a>
                            </div>
                        </div>
                        <div class="wa-card-b">
                            @if ($templateError)
                                <div class="alert alert-warning" style="font-size:13px;">
                                    Couldn’t load your templates: {{ $templateError }}
                                </div>
                            @endif

                            @if (empty($templates))
                                <p class="text-muted mb-2">
                                    You could reach <b>{{ $clientCount + $platformUserCount }}</b> people —
                                    {{ $clientCount }} of your own {{ $clientCount == 1 ? 'customer' : 'customers' }}
                                    and {{ $platformUserCount }} MyChitti {{ $platformUserCount == 1 ? 'user' : 'users' }}
                                    — but you have no approved message templates yet. WhatsApp only allows
                                    business-initiated messages using a template Meta has approved.
                                </p>
                                <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-receipt"></i> Create a Template
                                </a>
                            @else
                                <div class="form-group">
                                    <label class="font-weight-bold" style="font-size:13px;">Template</label>
                                    <select id="wb-template" class="form-control">
                                        <option value="">— Select a template —</option>
                                        @foreach ($templates as $i => $t)
                                            <option value="{{ $i }}" @if ($t['unsupported']) disabled @endif>
                                                {{ $t['name'] }} ({{ $t['language'] }})@if (($t['status'] ?? 'APPROVED') !== 'APPROVED') — {{ $t['status'] }}, Meta will reject the send @elseif ($t['unsupported']) — not supported here, {{ $t['unsupported'] }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (\App\Http\Controllers\Vendor\WhatsAppController::BULK_SHOW_UNAPPROVED)
                                    <div class="alert alert-warning" style="font-size:12px;">
                                        Testing mode — templates that Meta hasn’t approved yet are listed too.
                                        Picking one lets you check the composer, but the send itself will come
                                        back as failed until the template’s status is APPROVED.
                                    </div>
                                @endif

                                {{-- Media-header templates carry a file at the top of every message.
                                     Meta fetches it themselves, so it is uploaded here and sent as
                                     a public link — without it the send fails with error 132012. --}}
                                <div id="wb-media" class="form-group" style="display:none;">
                                    <label class="font-weight-bold" style="font-size:13px;">
                                        <span id="wb-media-label">Image</span> for this template
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" id="wb-media-file" class="form-control-file" accept="image/jpeg,image/png">
                                    <small class="form-text text-muted" style="font-size:11px;">
                                        This template was approved with a <span class="wb-media-kind">image</span> at the top, so every
                                        message needs one. Max 5 MB. The same file goes to everyone in this send.
                                    </small>
                                    <div id="wb-media-status" class="mt-2" style="font-size:12px;"></div>
                                    <img id="wb-media-preview" src="" alt="" class="mt-2 rounded border"
                                        style="display:none;max-height:120px;max-width:100%;">
                                </div>

                                <div id="wb-preview" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                                    <div class="text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Message preview</div>
                                    <div id="wb-preview-body" style="font-size:13px;white-space:pre-wrap;"></div>
                                </div>

                                <div id="wb-vars" class="mb-3"></div>

                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="font-weight-bold mb-0" style="font-size:13px;">Recipients</label>
                                        <span id="wb-selected-count" class="badge badge-soft-secondary">0 selected</span>
                                    </div>

                                    {{-- Two audiences, one send. The pills only switch which picker is on
                                         screen — both selections go out together under one Send, so a vendor
                                         never has to run the same campaign twice. --}}
                                    <ul class="nav nav-pills mb-2" style="gap:6px;">
                                        <li class="nav-item">
                                            <a href="javascript:;" class="nav-link active wb-mode" data-mode="clients" style="font-size:13px;padding:6px 14px;">
                                                My customers <span id="wb-pill-clients" class="badge badge-soft-light ml-1">{{ $clientCount }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="javascript:;" class="nav-link wb-mode" data-mode="platform" style="font-size:13px;padding:6px 14px;">
                                                MyChitti users <span id="wb-pill-platform" class="badge badge-soft-light ml-1">{{ $platformUserCount }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                    <small class="text-muted d-block mb-3">
                                        Pick from both tabs if you like — one <b>Send</b> covers everything you’ve chosen.
                                    </small>

                                    <div id="wb-pane-clients">
                                        <div class="d-flex mb-2" style="gap:8px;">
                                            <input id="wb-search" type="text" class="form-control form-control-sm"
                                                   placeholder="Search customers by name or phone…">
                                            <button id="wb-select-all" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Select all</button>
                                            <button id="wb-clear" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Clear</button>
                                        </div>
                                        <div id="wb-clients" class="border rounded" style="max-height:260px;overflow-y:auto;">
                                            <div class="text-muted text-center p-3" style="font-size:13px;">Loading clients…</div>
                                        </div>
                                        <small id="wb-truncated" class="text-muted" style="display:none;"></small>
                                        {{-- Same note as the MyChitti tab carries. Each tab has to state its
                                             own rate: the two are priced differently, and a vendor picking
                                             customers here should not have to open the other tab to find out
                                             what this one costs. --}}
                                        <small class="d-block mt-2" style="font-size:11px;">
                                            <b>{{ _price($rates['own']) }}</b> per customer, GST included
                                            <span class="text-muted">
                                                — against {{ _price($rates['platform']) }} to reach a MyChitti user.
                                            </span>
                                        </small>
                                    </div>

                                    <div id="wb-pane-platform" style="display:none;">
                                        <div class="border rounded p-3">
                                            <p class="text-muted mb-2" style="font-size:13px;">
                                                MyChitti users in your city. You choose how many to reach — their phone
                                                numbers stay private and are never shown to you.
                                            </p>
                                            @if ($platformUserCount == 0)
                                                <div class="alert alert-info mb-0" style="font-size:13px;">
                                                    No MyChitti users match your city yet, so there is nobody to reach
                                                    here right now. This grows as customers in your area start using
                                                    MyChitti — your own customers are unaffected.
                                                </div>
                                            @else
                                                <label style="font-size:12px;" class="mb-1">How many users to message</label>
                                                <input id="wb-platform-count" type="number" class="form-control form-control-sm"
                                                       style="max-width:200px;" min="0" max="{{ $platformUserCount }}"
                                                       value="0">
                                                <small class="text-muted d-block mt-1">
                                                    {{-- Starts at zero on purpose: this box is now added to whatever is
                                                         ticked under My customers, and reaching strangers costs more per
                                                         message. Nobody here is messaged unless the vendor asks for them. --}}
                                                    Leave this at <b>0</b> to message only your own customers.
                                                    Maximum {{ $platformUserCount }} available now. Anyone who has already
                                                    received {{ \App\Http\Controllers\Vendor\WhatsAppController::NEARBY_MONTHLY_CAP }}
                                                    offers from any business this month is excluded automatically, so the
                                                    number moves as other vendors send.
                                                </small>
                                                {{-- The rate belongs next to the box that spends it. Reaching a MyChitti
                                                     user costs more than messaging your own customer, and the vendor was
                                                     typing a count with no price in front of them. --}}
                                                <small class="d-block mt-1" style="font-size:11px;">
                                                    <b>{{ _price($rates['platform']) }}</b> per MyChitti user, GST included
                                                    <span class="text-muted">
                                                        — against {{ _price($rates['own']) }} for your own customers.
                                                    </span>
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <small class="text-muted d-block mb-3">
                                    Anyone who replies <b>STOP</b> is removed automatically and excluded from every
                                    future send.@if (($optOutCount ?? 0) > 0) {{ $optOutCount }} {{ $optOutCount == 1 ? 'person has' : 'people have' }} opted out and {{ $optOutCount == 1 ? 'is' : 'are' }} already excluded from the counts above.@endif
                                    Keeping unwanted messages down protects your number's WhatsApp quality rating.
                                </small>

                                {{-- What this one Send actually covers, priced before it is pressed —
                                     the two audiences bill at different rates. --}}
                                <div id="wb-summary" class="border rounded p-2 mb-3" style="display:none;font-size:12px;"></div>

                                <div class="d-flex align-items-center" style="gap:12px;">
                                    <button id="wb-send" class="btn btn--primary" disabled>Send</button>
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

                <div class="col-lg-3">
                    <div class="wa-card">
                        <div class="wa-card-h">Before you send</div>
                        <div class="wa-card-b">
                            <ul class="pl-3 mb-0 wa-sub" style="line-height:1.7;">
                                <li>WhatsApp only allows business-initiated messages from a template Meta has approved.</li>
                                <li>Anyone who replies <b>STOP</b> is excluded from this and every future send, automatically.</li>
                                <li>MyChitti users never expose their number to you — results come back masked.</li>
                                <li>Keeping unwanted messages down protects your number's quality rating.</li>
                            </ul>
                            <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn-outline-primary btn-block mt-3">
                                <i class="tio-receipt"></i> Manage templates
                            </a>
                        </div>
                    </div>
                </div>
                </div>
