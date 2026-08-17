{{--
    Hospital Booking Modal — self-contained partial.
    Include in every store template: @include('front-views.partials._hospital_booking_modal')
    Requires $store to be in scope. Guards on business_type == 'hospital' and doctors existing.
    Intercepts bookService() calls for hospital stores, adds doctor/date/slot selection,
    then POSTs to book-service with preferred_doctor_id / preferred_date / preferred_slot_id / preferred_time.
--}}
@php
    $hbDoctors = collect();
    if (isset($store) && strtolower($store->business_type ?? '') === 'hospital') {
        $hbDoctors = \App\Models\DoctorProfile::where('store_id', $store->id)
            ->with('employee', 'services')
            ->whereHas('slots', fn($s) => $s->where('is_active', 1))
            ->get();
    }
@endphp
@if ($hbDoctors->isNotEmpty())

    <div class="modal fade" id="hospitalBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
            <div class="modal-content" style="border-radius:16px; overflow:hidden;">

                <div class="modal-header"
                    style="background:linear-gradient(135deg,#eff6ff,#f0fdf4); border-bottom:1px solid #bfdbfe;">
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:22px;">🩺</span>
                        <div>
                            <h5 class="modal-title mb-0" style="font-weight:700; color:#1e3a5f;">Book Appointment</h5>
                            <p class="mb-0" style="font-size:12px; color:#4b7098;">Select a doctor and preferred slot</p>
                        </div>
                    </div>
{{-- Close X button --}}
<button type="button" class="btn btn-transparent p-0 m-0"
    onclick="$('#hospitalBookingModal').modal('hide')"
    style="font-size:1.4rem;">&times;</button>
                </div>

                <div class="modal-body p-3">

                    {{-- Step 1: Doctor --}}
                    <div class="hb-section mb-3">
                        <div class="hb-label">1. Select Doctor <span class="text-danger">*</span></div>
                        <div class="hb-doctor-grid" id="hbDoctorGrid">
                            @foreach ($hbDoctors as $d)
                                @php $dName = 'Dr. ' . ($d->employee?->f_name ?? '') . ' ' . ($d->employee?->l_name ?? ''); @endphp
                                <div class="hb-doctor-card" data-id="{{ $d->id }}"
                                    data-services="{{ $d->services->pluck('item_id')->implode(',') }}"
                                    onclick="hbSelectDoctor({{ $d->id }}, this)">
                                    <div class="hb-avatar">{{ strtoupper(substr($d->employee?->f_name ?? 'D', 0, 1)) }}</div>
                                    <div class="hb-doc-info">
                                        <p class="hb-doc-name">{{ trim($dName) }}</p>
                                        <p class="hb-doc-spec">{{ $d->specialization }}</p>
                                        @if ($d->consultation_fee > 0)
                                            <p class="hb-doc-fee">Appointment Fee : ₹{{ number_format($d->consultation_fee, 0) }}</p>
                                        @else
                                            <p class="hb-doc-fee" style="color:#7c3aed;">Free</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-danger small mt-1 d-none" id="hbDoctorErr">Please select a doctor</div>
                    </div>

                    {{-- Step 2: Date --}}
                    <div class="hb-section mb-3">
                        <div class="hb-label">2. Preferred Date <span class="text-danger">*</span></div>
                        <input type="date" id="hbDate" class="form-control" style="max-width:220px;"
                            value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" onchange="hbLoadSlots()">
                        <div class="text-danger small mt-1 d-none" id="hbDateErr">Please select a date</div>
                    </div>

                    {{-- Step 3: Slot --}}
                    <div class="hb-section mb-3" id="hbSlotSection" style="display:none;">
                        <div class="hb-label">3. Pick a Slot <span class="text-muted" style="font-weight:400;">(optional)</span></div>
                        <div id="hbSlotGrid" class="hb-slot-grid">
                            <span class="text-muted small">Loading slots...</span>
                        </div>
                        <div class="hb-started-note" id="hbStartedNote">
                            This session has already started. You can still book it — you'll be seen
                            from the time you book, not from the printed start time.
                        </div>
                    </div>

                    {{-- Step 4: Appointment for? --}}
                    <div class="hb-section mb-3">
                        <div class="hb-label">4. Appointment for?</div>
                        <div class="hb-for-toggle">
                            <label class="hb-for-option hb-for-selected" id="hbForMyselfLabel">
                                <input type="radio" name="hb_for" value="myself" checked onchange="hbToggleFor('myself')">
                                <span class="hb-for-icon">👤</span>
                                <span>Myself</span>
                            </label>
                            <label class="hb-for-option" id="hbForOtherLabel">
                                <input type="radio" name="hb_for" value="other" onchange="hbToggleFor('other')">
                                <span class="hb-for-icon">👥</span>
                                <span>Someone else</span>
                            </label>
                        </div>
                        <div id="hbPatientFields" style="display:none; margin-top:12px;">
                            <div class="row g-2">
                                <div class="col-7">
                                    <label class="form-label mb-1" style="font-size:12px; font-weight:600;">Patient name <span class="text-danger">*</span></label>
                                    <input type="text" id="hbPatientName" class="form-control form-control-sm" placeholder="Full name">
                                    <div class="text-danger small d-none" id="hbPatientNameErr">Required</div>
                                </div>
                                <div class="col-5">
                                    <label class="form-label mb-1" style="font-size:12px; font-weight:600;">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" id="hbPatientPhone" class="form-control form-control-sm" placeholder="Mobile no.">
                                    <div class="text-danger small d-none" id="hbPatientPhoneErr">Required</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step 5: Reason --}}
                    <div class="hb-section mb-1">
                        <div class="hb-label">5. Reason <span class="text-muted" style="font-weight:400;">(optional)</span></div>
                        <textarea id="hbReason" class="form-control" rows="2" name="reason"
                            placeholder="Chief complaint or reason for visit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="$('#hospitalBookingModal').modal('hide')">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="hbConfirmBtn" onclick="hbConfirm()">
                        Confirm &amp; Send Enquiry
                    </button>
                </div>

            </div>
        </div>
    </div>

    <style>
        .hb-label {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            color: #111;
        }
        .hb-section {
            background: #f9fafb;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .hb-doctor-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .hb-doctor-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            cursor: pointer;
            flex: 1 1 200px;
            min-width: 180px;
            max-width: 260px;
            transition: border-color .15s;
        }
        .hb-doctor-card:hover {
            border-color: #93c5fd;
        }
        .hb-doctor-card.hb-selected {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .hb-doctor-card.card-hidden {
            display: none !important;
        }
        .hb-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .hb-doc-info {
            flex: 1;
            min-width: 0;
        }
        .hb-doc-name {
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .hb-doc-spec {
            font-size: 11px;
            color: #6b7280;
            margin: 0 0 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .hb-doc-fee {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            margin: 0;
        }
        .hb-slot-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 40px;
        }
        .hb-slot-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 14px;
            cursor: pointer;
            text-align: center;
            min-width: 130px;
            background: #fff;
            transition: all .15s;
            user-select: none;
        }
        .hb-slot-card:hover:not(.hb-slot-full) {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .hb-slot-card.hb-slot-selected {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
        }
        .hb-slot-card.hb-slot-selected .hb-slot-avail {
            color: rgba(255, 255, 255, .75);
        }
        .hb-slot-card.hb-slot-full {
            background: #f9fafb;
            border-color: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        /* Already under way: bookable, but visibly not a normal on-time slot. */
        .hb-slot-card.hb-slot-started {
            border-color: #fcd34d;
            background: #fffbeb;
        }
        .hb-slot-card.hb-slot-started .hb-slot-avail {
            color: #b45309;
            font-weight: 600;
        }
        .hb-slot-card.hb-slot-started.hb-slot-selected {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
        }
        .hb-slot-card.hb-slot-started.hb-slot-selected .hb-slot-avail {
            color: rgba(255, 255, 255, .8);
        }
        .hb-started-note {
            display: none;
            margin-top: 8px;
            font-size: 11px;
            line-height: 1.5;
            color: #92400e;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 7px 10px;
        }
        .hb-slot-time {
            font-weight: 700;
            font-size: 12px;
        }
        .hb-slot-avail {
            font-size: 10px;
            color: #6b7280;
            margin-top: 1px;
        }
        .hb-no-slots {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }
        .hb-next-avail-link {
            font-size: 12px;
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .hb-next-avail-link:hover {
            text-decoration: underline;
        }
        .hb-for-toggle {
            display: flex;
            gap: 10px;
        }
        .hb-for-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            background: #fff;
            transition: border-color .15s, background .15s;
            flex: 1;
            user-select: none;
        }
        .hb-for-option input[type="radio"] {
            display: none;
        }
        .hb-for-option:hover {
            border-color: #93c5fd;
        }
        .hb-for-option.hb-for-selected {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1e40af;
        }
        .hb-for-icon {
            font-size: 16px;
        }
        @media (max-width: 480px) {
            .hb-doctor-card {
                max-width: 100%;
                flex: 1 1 100%;
            }
        }
    </style>

    <script>
        // ── Hospital booking state ──────────────────────────────────────────────────
        window._hospitalBookingEnabled = true;
        window._hbSlotsUrl = "{{ route('front.appointment.slots') }}";

        window._hbPending = {
            serviceId: null,
            elem: null,
            storeId: null
        };

        var _hbDoctorId  = null;
        var _hbSlotId    = null;
        var _hbSlotTime  = null;
        var _hbFor       = 'myself';

        // ── Doctor selection ────────────────────────────────────────────────────────
        function hbSelectDoctor(id, el) {
            document.querySelectorAll('.hb-doctor-card').forEach(c => c.classList.remove('hb-selected'));
            el.classList.add('hb-selected');
            _hbDoctorId = id;
            document.getElementById('hbDoctorErr').classList.add('d-none');
            hbLoadSlots();
        }

        // ── Filter doctors by service ───────────────────────────────────────────────
        function hbFilterDoctors(serviceId) {
            var cards = document.querySelectorAll('.hb-doctor-card');
            var anyVisible = false;

            cards.forEach(function(card) {
                var services = card.dataset.services ? card.dataset.services.split(',') : [];
                var show = !serviceId || services.indexOf(String(serviceId)) !== -1;
                card.classList.toggle('card-hidden', !show);
                if (show) anyVisible = true;
            });

            // Fallback: if no doctor matches, show all and notify
            if (!anyVisible) {
                cards.forEach(function(card) {
                    card.classList.remove('card-hidden');
                });
                document.getElementById('hbSlotGrid').innerHTML =
                    '<span class="text-muted small">No doctors assigned to this service. Showing all doctors.</span>';
            }

            // If previously selected doctor is now hidden, deselect it
            if (_hbDoctorId) {
                var selectedCard = document.querySelector('.hb-doctor-card[data-id="' + _hbDoctorId + '"]');
                if (selectedCard && selectedCard.classList.contains('card-hidden')) {
                    selectedCard.classList.remove('hb-selected');
                    _hbDoctorId = null;
                    document.getElementById('hbSlotSection').style.display = 'none';
                }
            }
        }

        // ── Load slots ──────────────────────────────────────────────────────────────
        function hbLoadSlots() {
            var date = document.getElementById('hbDate').value;
            if (!_hbDoctorId || !date) return;

            var section = document.getElementById('hbSlotSection');
            var grid    = document.getElementById('hbSlotGrid');
            section.style.display = 'block';
            grid.innerHTML = '<span class="text-muted small">Loading slots...</span>';
            _hbSlotId   = null;
            _hbSlotTime = null;

            fetch(window._hbSlotsUrl + '?doctor_profile_id=' + _hbDoctorId + '&date=' + date)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var slots   = data.slots || [];
                    var noSlots = slots.length === 0;
                    var allFull = data.all_full;

                    if (noSlots || allFull) {
                        var msg = noSlots
                            ? 'No slots available on this day.'
                            : 'All slots are fully booked for this day.';
                        var nextHtml = '';
                        if (data.next_available) {
                            var parts = data.next_available.split('-');
                            var label = new Date(parts[0], parts[1] - 1, parts[2])
                                .toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
                            nextHtml = '<a href="#" class="hb-next-avail-link" onclick="hbJumpToDate(\'' + data.next_available + '\'); return false;">'
                                + 'Next available: ' + label + ' →</a>';
                        }
                        grid.innerHTML = '<div class="hb-no-slots"><span class="text-muted small">' + msg + '</span>' + nextHtml + '</div>';
                        return;
                    }

                    // A session already under way is still bookable — clinics take patients into a
                    // running slot — but it must not look like an appointment at the printed start
                    // time. The customer is seen from when they book, which is what the server
                    // records and what their reminder will say.
                    var isToday = date === hbTodayStr();
                    var nowMins = new Date().getHours() * 60 + new Date().getMinutes();

                    var html = '';
                    slots.forEach(function(s) {
                        var full    = s.available <= 0;
                        var started = isToday && !full && hbMins(s.slot_start) <= nowMins;
                        html += '<div class="hb-slot-card ' + (full ? 'hb-slot-full' : (started ? 'hb-slot-started' : '')) + '"'
                            + ' data-slot-id="' + s.id + '" data-start="' + s.slot_start + '"'
                            + ' data-started="' + (started ? '1' : '') + '"'
                            + (full ? '' : ' onclick="hbSelectSlot(this)"') + '>'
                            + '<div class="hb-slot-time">' + hbFmt(s.slot_start) + ' – ' + hbFmt(s.slot_end) + '</div>'
                            + '<div class="hb-slot-avail">'
                            + (full ? 'Full' : (started ? 'Started · ' + s.available + ' left' : s.available + '/' + s.max_patients + ' avail'))
                            + '</div>'
                            + '</div>';
                    });
                    grid.innerHTML = html;
                    hbSetStartedNote(false);
                })
                .catch(function() {
                    grid.innerHTML = '<span class="text-danger small">Could not load slots.</span>';
                });
        }

        // ── Slot selection ──────────────────────────────────────────────────────────
        function hbSelectSlot(el) {
            var id    = el.dataset.slotId;
            var start = el.dataset.start;
            if (_hbSlotId === id) {
                document.querySelectorAll('.hb-slot-card').forEach(function(c) { c.classList.remove('hb-slot-selected'); });
                _hbSlotId   = null;
                _hbSlotTime = null;
                hbSetStartedNote(false);
                return;
            }
            document.querySelectorAll('.hb-slot-card').forEach(function(c) { c.classList.remove('hb-slot-selected'); });
            el.classList.add('hb-slot-selected');
            _hbSlotId   = id;
            _hbSlotTime = start ? start.substring(0, 5) : null;
            hbSetStartedNote(el.dataset.started === '1');
        }

        // Says plainly that the printed start time is not the time they are being given. Without
        // it the confirmation and the WhatsApp reminder would name a time the customer never saw.
        function hbSetStartedNote(show) {
            var note = document.getElementById('hbStartedNote');
            if (note) note.style.display = show ? 'block' : 'none';
        }

        function hbTodayStr() {
            var d = new Date();
            return d.getFullYear() + '-'
                + String(d.getMonth() + 1).padStart(2, '0') + '-'
                + String(d.getDate()).padStart(2, '0');
        }

        function hbMins(t) {
            if (!t) return 0;
            var p = t.split(':');
            return parseInt(p[0], 10) * 60 + parseInt(p[1] || '0', 10);
        }

        // ── Helpers ─────────────────────────────────────────────────────────────────
        function hbFmt(t) {
            if (!t) return '';
            var parts = t.split(':');
            var hr = parseInt(parts[0]);
            return (hr > 12 ? hr - 12 : (hr || 12)) + ':' + parts[1] + ' ' + (hr >= 12 ? 'PM' : 'AM');
        }

        function hbJumpToDate(dateStr) {
            document.getElementById('hbDate').value = dateStr;
            hbLoadSlots();
        }

        // ── Appointment for toggle ──────────────────────────────────────────────────
        function hbToggleFor(val) {
            _hbFor = val;
            document.getElementById('hbForMyselfLabel').classList.toggle('hb-for-selected', val === 'myself');
            document.getElementById('hbForOtherLabel').classList.toggle('hb-for-selected', val === 'other');
            document.getElementById('hbPatientFields').style.display = val === 'other' ? 'block' : 'none';
            if (val === 'myself') {
                document.getElementById('hbPatientNameErr').classList.add('d-none');
                document.getElementById('hbPatientPhoneErr').classList.add('d-none');
            }
        }

        // ── Confirm & submit ────────────────────────────────────────────────────────
        function hbConfirm() {
            if (!_hbDoctorId) {
                document.getElementById('hbDoctorErr').classList.remove('d-none');
                return;
            }
            if (!document.getElementById('hbDate').value) {
                document.getElementById('hbDateErr').classList.remove('d-none');
                return;
            }
            if (_hbFor === 'other') {
                var nameOk  = document.getElementById('hbPatientName').value.trim();
                var phoneOk = document.getElementById('hbPatientPhone').value.trim();
                if (!nameOk)  document.getElementById('hbPatientNameErr').classList.remove('d-none');
                if (!phoneOk) document.getElementById('hbPatientPhoneErr').classList.remove('d-none');
                if (!nameOk || !phoneOk) return;
            }

            var btn = document.getElementById('hbConfirmBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

            $.post({
                url: "{{ route('book-service') }}",
                data: {
                    serviceId:          window._hbPending.serviceId,
                    storeId:            window._hbPending.storeId,
                    preferred_doctor_id: _hbDoctorId,
                    preferred_date:     document.getElementById('hbDate').value,
                    preferred_slot_id:  _hbSlotId   || null,
                    preferred_time:     _hbSlotTime || null,
                    requirements:       document.getElementById('hbReason').value || null,
                    patient_for:        _hbFor,
                    patient_name:       _hbFor === 'other' ? document.getElementById('hbPatientName').value.trim()  : null,
                    patient_phone:      _hbFor === 'other' ? document.getElementById('hbPatientPhone').value.trim() : null,
                },
                success: function(data) {
                    $('#hospitalBookingModal').modal('hide');
                    _hbReset();
                    if (data.message === 'phone_missing') {
                        phoneModal.show();
                        $(".service_enquiry").val(window._hbPending.serviceId);
                        $(".storeId").val(window._hbPending.storeId);
                    } else {
                        toasterNotification(data.message);
                        if (window._hbPending.elem) $(window._hbPending.elem).html('&check; Enquiry Sent');
                    }
                },
                error: function() {
                    toasterNotification('Something went wrong. Please try again.');
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm & Send Enquiry';
                    if (window._hbPending.elem) {
                        setTimeout(function() {
                            $(window._hbPending.elem).html('<i class="fas fa-user-cog"></i> Enquiry Now');
                        }, 1500);
                    }
                }
            });
        }

        // ── Bootstrap modal event: filter doctors on open ───────────────────────────
        $('#hospitalBookingModal').on('show.bs.modal', function() {
            hbFilterDoctors(window._hbPending ? window._hbPending.serviceId : null);
        });

        // ── Reset state ─────────────────────────────────────────────────────────────
        function _hbReset() {
            _hbDoctorId = null;
            _hbSlotId   = null;
            _hbSlotTime = null;
            _hbFor      = 'myself';
            document.querySelectorAll('.hb-doctor-card').forEach(function(c) { c.classList.remove('hb-selected', 'card-hidden'); });
            document.querySelectorAll('.hb-slot-card').forEach(function(c) { c.classList.remove('hb-slot-selected'); });
            document.getElementById('hbSlotSection').style.display = 'none';
            document.getElementById('hbReason').value = '';
            document.getElementById('hbDate').value   = '{{ date('Y-m-d') }}';
            document.querySelector('input[name="hb_for"][value="myself"]').checked = true;
            hbToggleFor('myself');
        }
    </script>

@else
    <script>
        console.log('{{ strtolower($store->business_type ?? '') }}');
    </script>
@endif