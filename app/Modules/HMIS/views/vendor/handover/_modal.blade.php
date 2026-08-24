{{-- The counter form for one physical exchange, shared by the OPD consultation screen and the
     laboratory worklist. Rendered once per page; the job it is about is set when it opens.

     The order of the panels is the order the checks are worth doing in. The expectation banner
     comes first and costs nothing — a report cannot arrive from a lab that was never sent
     anything, and that question is answered before anyone is asked to write a name down. The code
     comes second, and only on the way in. The signature comes last, because it is the thing being
     filed rather than the thing being checked.

     $hoSubjectType : 'opd_lab_work' | 'lab_order' --}}
@php
    $hoSubjectType = $hoSubjectType ?? 'opd_lab_work';

    // Route templates rather than hand-built paths, so this keeps working if the group is ever
    // moved or prefixed. Both sentinels are values the route constraints actually accept — a
    // placeholder like __ID__ would be a landmine the day URL generation starts validating them.
    $hoSentinelId = 999999999;
    $hoUrls = [
        'start'  => route('vendor.handover.start',  ['type' => 'opd_lab_work', 'id' => $hoSentinelId]),
        'otp'    => route('vendor.handover.otp',    ['type' => 'opd_lab_work', 'id' => $hoSentinelId]),
        'store'  => route('vendor.handover.store',  ['type' => 'opd_lab_work', 'id' => $hoSentinelId]),
        'verify' => route('vendor.handover.verify', ['handover' => $hoSentinelId]),
    ];
@endphp

<div class="modal fade" id="hoModal" tabindex="-1" role="dialog" aria-labelledby="hoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" id="hoForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="direction" id="hoDirection" value="out">
                <input type="hidden" name="handover_id" id="hoHandoverId" value="">
                <input type="hidden" name="signature" id="hoSignature" value="">

                <div class="modal-header py-2">
                    <h5 class="modal-title" id="hoModalTitle" style="font-size:15px; font-weight:700;">Record handover</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                        <div style="min-width:0;">
                            <div class="text-dark" style="font-weight:700; font-size:13.5px;" id="hoTitle">—</div>
                            <div class="text-muted" style="font-size:11.5px;">
                                <span id="hoDirectionLabel">Collected from us</span>
                                · <span id="hoLabName">—</span>
                            </div>
                        </div>
                        <span class="badge badge-soft-secondary" id="hoDirectionBadge" style="font-weight:600;">Going out</span>
                    </div>

                    {{-- The cheap control, and the one that catches most of what this feature exists
                         to catch. Never blocks on its own: the counter sometimes knows something the
                         record does not, so an override is possible but has to be written down. --}}
                    <div class="alert alert-danger py-2 px-3" id="hoUnexpected" style="display:none; font-size:12px;">
                        <div style="font-weight:700;">
                            <i class="tio-warning"></i> This does not match the record
                        </div>
                        <div id="hoUnexpectedReason" class="mt-1"></div>
                        <div class="form-group mb-0 mt-2">
                            <label class="input-label" style="font-size:11px;">Why are you recording it anyway? <span class="text-danger">*</span></label>
                            <input type="text" name="override_reason" id="hoOverride" class="form-control form-control-sm"
                                   maxlength="255" placeholder="e.g. lab re-sent it after a remake we did not log">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label class="input-label" style="font-size:12px;">
                                Who is here <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="person_name" id="hoPerson" class="form-control form-control-sm"
                                   list="hoRunners" required maxlength="150" autocomplete="off"
                                   placeholder="Name of the person at the counter">
                            <datalist id="hoRunners"></datalist>
                            {{-- A name this lab has never sent before. Not a block — labs hire, and
                                 the first honest delivery from a new runner looks exactly like this
                                 — but it is the moment to look at an ID card rather than wave it by. --}}
                            <small class="text-warning d-block mt-1" id="hoNewRunner" style="display:none !important; font-size:11px;">
                                <i class="tio-info-outined"></i> First time this lab has sent this person. Check their ID.
                            </small>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="input-label" style="font-size:12px;">Their phone</label>
                            <input type="text" name="person_phone" class="form-control form-control-sm" maxlength="40">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="input-label" style="font-size:12px;">ID shown</label>
                            <input type="text" name="person_id_ref" class="form-control form-control-sm" maxlength="80"
                                   placeholder="Employee no., Aadhaar last 4">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label class="input-label" style="font-size:12px;">What is changing hands</label>
                            <input type="text" name="purpose" id="hoPurpose" class="form-control form-control-sm"
                                   list="hoPurposes" maxlength="120">
                            <datalist id="hoPurposes"></datalist>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="input-label" style="font-size:12px;">Items</label>
                            <input type="number" name="item_count" class="form-control form-control-sm" min="1" max="999" value="1">
                        </div>
                        <div class="form-group col-md-5">
                            <label class="input-label" style="font-size:12px;">Packet / description</label>
                            <input type="text" name="item_note" class="form-control form-control-sm" maxlength="255"
                                   placeholder="Sealed pouch, 2 tubes, sealed envelope">
                        </div>
                    </div>

                    {{-- Only on the way in. The code goes to the lab's own saved number, never to a
                         number typed on this form — that inversion is the whole point, and it is
                         enforced on the server, so nothing here can weaken it. --}}
                    <div id="hoVerifyBlock" style="display:none;">
                        <div class="border rounded p-2 mb-2" style="background:#f8fafc;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div style="font-size:12px; min-width:0;">
                                    <span style="font-weight:700;">Verify with the lab</span>
                                    <div class="text-muted" style="font-size:11px;">
                                        We send a code to <span id="hoLabPhone" style="font-weight:600;">the lab's own number</span> —
                                        not to this visitor. They must get it from their office.
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="hoSendOtp" style="font-size:12px;">
                                    Send code
                                </button>
                            </div>

                            <div class="form-row mt-2" id="hoOtpRow" style="display:none;">
                                <div class="form-group col-md-4 mb-1">
                                    <input type="text" id="hoOtpCode" class="form-control form-control-sm"
                                           inputmode="numeric" maxlength="6" autocomplete="off" placeholder="6-digit code">
                                </div>
                                <div class="col-md-8 mb-1">
                                    <button type="button" class="btn btn--primary btn-sm" id="hoVerifyOtp" style="font-size:12px;">Verify</button>
                                    <span class="ml-2" id="hoOtpMsg" style="font-size:11.5px;"></span>
                                </div>
                            </div>

                            <div class="mt-2" id="hoVerified" style="display:none;">
                                <span class="badge" style="color:#166534; background:#dcfce7; font-weight:600;">
                                    <i class="tio-checkmark-circle"></i> Verified with the lab
                                </span>
                            </div>
                        </div>

                        {{-- The escape hatch, and why it exists: a hard block does not stop the
                             delivery happening, it only stops it being written down. An unconfirmed
                             arrival is recorded and stays visibly unconfirmed until someone rings
                             the lab — which is a far better record than nothing at all. --}}
                        <div class="alert alert-warning py-2 px-3" id="hoProvisional" style="font-size:11.5px;">
                            <i class="tio-info-outined"></i>
                            Not verified. You can still record it — it will be filed as
                            <strong>not yet confirmed</strong>, the report stays out of the patient's
                            results until someone vouches for it, and the lab is messaged either way.
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="input-label" style="font-size:12px;">Signature</label>
                            <div class="border rounded" style="background:#fff;">
                                <canvas id="hoPad" style="width:100%; height:110px; display:block; touch-action:none; cursor:crosshair;"></canvas>
                            </div>
                            <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="hoClearPad" style="font-size:11px;">Clear</button>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="input-label" style="font-size:12px;">Photo (person, ID, or the packet)</label>
                            <input type="file" name="photo" class="form-control-file" accept="image/*" capture="environment"
                                   style="font-size:12px;">
                            <label class="input-label mt-2" style="font-size:12px;">Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm" maxlength="2000">
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary btn-sm" id="hoSubmit">Record handover</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const SUBJECT = @json($hoSubjectType);
    const URLS    = @json($hoUrls);
    const SENT_ID = @json((string) $hoSentinelId);
    const TOKEN   = document.querySelector('meta[name="csrf-token"]')?.content
                 || document.querySelector('#hoForm input[name="_token"]')?.value;

    const url = (key, type, id) => URLS[key].replace('opd_lab_work', type).replace(SENT_ID, id);

    // Suggested wording for what is in the packet. Only a suggestion: the box stays free text,
    // because a courier turning up with something nobody predicted is exactly the case worth
    // being able to describe rather than forcing into the nearest wrong option.
    const PURPOSES = {
        opd_lab_work: {
            out: ['Impression', 'Trial work back to lab', 'Remake', 'Shade tab', 'Model'],
            in:  ['Finished work', 'Trial / jaw', 'Remake returned', 'Model returned'],
        },
        lab_order: {
            out: ['Blood samples', 'Urine sample', 'Swab', 'Biopsy / histopath', 'Mixed samples'],
            in:  ['Printed report', 'Report + slides', 'Slides returned', 'Corrected report'],
        },
    };

    let state = { type: null, id: null, direction: 'out', runners: [], verified: false };

    const $ = id => document.getElementById(id);

    function fill(list, values) {
        list.innerHTML = '';
        (values || []).forEach(v => {
            const o = document.createElement('option');
            o.value = v;
            list.appendChild(o);
        });
    }

    // Opened from a button on a job row. Everything shown is fetched fresh rather than rendered
    // into the page per job: the expectation check has to reflect the record as it is now, not as
    // it was when the page was loaded, and a tab left open all afternoon is the normal case here.
    window.hoOpen = function (subjectId, direction, subjectType) {
        state = { type: subjectType || SUBJECT, id: subjectId, direction: direction, runners: [], verified: false };

        $('hoForm').reset();
        $('hoSignature').value = '';
        $('hoHandoverId').value = '';
        $('hoDirection').value = direction;
        $('hoOtpRow').style.display = 'none';
        $('hoVerified').style.display = 'none';
        $('hoProvisional').style.display = '';
        $('hoOtpMsg').textContent = '';
        $('hoUnexpected').style.display = 'none';
        $('hoNewRunner').style.display = 'none';
        clearPad();

        const inbound = direction === 'in';
        $('hoVerifyBlock').style.display = inbound ? '' : 'none';
        $('hoDirectionBadge').textContent = inbound ? 'Coming in' : 'Going out';
        $('hoDirectionLabel').textContent = inbound ? 'Delivered to us' : 'Collected from us';
        $('hoSubmit').textContent = inbound ? 'Record delivery' : 'Record collection';
        fill($('hoPurposes'), (PURPOSES[state.type] || {})[direction]);

        $('hoForm').action = url('store', state.type, subjectId);

        fetch(url('start', state.type, subjectId) + '?direction=' + direction, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(d => {
                if (!d || !d.success) return;

                $('hoTitle').textContent = d.title || '—';
                $('hoLabName').textContent = (d.lab && d.lab.name) || 'No lab on this job';
                $('hoLabPhone').textContent = (d.lab && d.lab.masked) || 'no number saved';
                state.runners = d.runners || [];
                fill($('hoRunners'), state.runners);

                if (!d.expected) {
                    $('hoUnexpected').style.display = '';
                    $('hoUnexpectedReason').textContent = d.reason || '';
                }

                // Nothing to verify against. Said plainly rather than leaving a Send code button
                // that fails when pressed — the fix is on the job, not on this form.
                if (!d.lab || !d.lab.phone) {
                    $('hoSendOtp').disabled = true;
                    $('hoSendOtp').textContent = 'No lab number';
                }
            })
            .catch(() => {});

        window.jQuery && jQuery('#hoModal').modal('show');
    };

    $('hoPerson').addEventListener('input', function () {
        const name = this.value.trim().toLowerCase();
        const known = state.runners.some(r => String(r).trim().toLowerCase() === name);
        $('hoNewRunner').style.display = (name.length > 2 && !known && state.runners.length) ? 'block' : 'none';
    });

    $('hoSendOtp').addEventListener('click', function () {
        const person = $('hoPerson').value.trim();
        if (!person) {
            $('hoOtpMsg').textContent = 'Type who is here first.';
            $('hoOtpMsg').className = 'ml-2 text-danger';
            $('hoOtpRow').style.display = '';
            return;
        }

        this.disabled = true;
        this.textContent = 'Sending…';

        const body = new FormData();
        body.append('_token', TOKEN);
        body.append('direction', state.direction);
        body.append('person_name', person);
        body.append('purpose', $('hoPurpose').value);
        if ($('hoHandoverId').value) body.append('handover_id', $('hoHandoverId').value);

        fetch(url('otp', state.type, state.id), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body,
        })
            .then(r => r.json())
            .then(d => {
                $('hoSendOtp').disabled = false;
                $('hoSendOtp').textContent = 'Resend code';
                $('hoOtpRow').style.display = '';
                $('hoOtpMsg').textContent = d.message || '';
                $('hoOtpMsg').className = 'ml-2 ' + (d.success ? 'text-muted' : 'text-danger');
                if (d.success && d.handover_id) $('hoHandoverId').value = d.handover_id;
            })
            .catch(() => {
                $('hoSendOtp').disabled = false;
                $('hoSendOtp').textContent = 'Send code';
                $('hoOtpMsg').textContent = 'Could not send. Record it as unconfirmed instead.';
                $('hoOtpMsg').className = 'ml-2 text-danger';
            });
    });

    $('hoVerifyOtp').addEventListener('click', function () {
        const id = $('hoHandoverId').value;
        if (!id) return;

        const body = new FormData();
        body.append('_token', TOKEN);
        body.append('code', $('hoOtpCode').value.trim());

        fetch(URLS.verify.replace(SENT_ID, id), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body,
        })
            .then(r => r.json())
            .then(d => {
                $('hoOtpMsg').textContent = d.message || '';
                $('hoOtpMsg').className = 'ml-2 ' + (d.success ? 'text-success' : 'text-danger');
                if (d.success) {
                    state.verified = true;
                    $('hoVerified').style.display = '';
                    $('hoOtpRow').style.display = 'none';
                    $('hoProvisional').style.display = 'none';
                }
            })
            .catch(() => {});
    });

    // ── Signature pad ─────────────────────────────────────────────────────────
    // Hand-rolled rather than a library: it is a few dozen lines, it has to work on a counter
    // tablet with no internet, and pulling a CDN script onto a clinical screen for this would be
    // the heaviest part of the whole feature.
    const pad = $('hoPad');
    const ctx = pad.getContext('2d');
    let drawing = false, dirty = false;

    function sizePad() {
        const ratio = window.devicePixelRatio || 1;
        const rect = pad.getBoundingClientRect();
        if (!rect.width) return;
        pad.width = rect.width * ratio;
        pad.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0f172a';
    }

    function clearPad() {
        ctx.clearRect(0, 0, pad.width, pad.height);
        dirty = false;
        $('hoSignature').value = '';
    }

    function point(e) {
        const rect = pad.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - rect.left, y: t.clientY - rect.top };
    }

    function start(e) { drawing = true; dirty = true; const p = point(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
    function move(e) { if (!drawing) return; const p = point(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
    function end() { drawing = false; }

    pad.addEventListener('mousedown', start);
    pad.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    pad.addEventListener('touchstart', start, { passive: false });
    pad.addEventListener('touchmove', move, { passive: false });
    pad.addEventListener('touchend', end);
    $('hoClearPad').addEventListener('click', clearPad);

    // Sized when the modal is actually visible: a canvas measured while its container is display:none
    // comes back zero-wide and silently swallows every stroke drawn on it afterwards.
    window.jQuery && jQuery('#hoModal').on('shown.bs.modal', sizePad);

    $('hoForm').addEventListener('submit', function () {
        if (dirty) $('hoSignature').value = pad.toDataURL('image/png');
        $('hoSubmit').disabled = true;
    });
})();
</script>
