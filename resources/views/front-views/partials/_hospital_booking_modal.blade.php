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
                            <p class="mb-0" style="font-size:12px; color:#4b7098;">Select a doctor and preferred slot
                            </p>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal"
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
                                    <div class="hb-avatar">{{ strtoupper(substr($d->employee?->f_name ?? 'D', 0, 1)) }}
                                    </div>
                                    <div class="hb-doc-info">
                                        <p class="hb-doc-name">{{ trim($dName) }}</p>
                                        <p class="hb-doc-spec">{{ $d->specialization }}</p>
                                        @if ($d->consultation_fee > 0)
                                            <p class="hb-doc-fee">₹{{ number_format($d->consultation_fee, 0) }}</p>
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
                        <div class="hb-label">3. Pick a Slot <span class="text-muted"
                                style="font-weight:400;">(optional)</span></div>
                        <div id="hbSlotGrid" class="hb-slot-grid">
                            <span class="text-muted small">Loading slots...</span>
                        </div>
                    </div>

                    {{-- Step 4: Reason --}}
                    <div class="hb-section mb-1">
                        <div class="hb-label">4. Reason <span class="text-muted"
                                style="font-weight:400;">(optional)</span></div>
                        <textarea id="hbReason" class="form-control" rows="2" name="reason" placeholder="Chief complaint or reason for visit..."></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
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

        .hb-slot-time {
            font-weight: 700;
            font-size: 12px;
        }

        .hb-slot-avail {
            font-size: 10px;
            color: #6b7280;
            margin-top: 1px;
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
        window._hbAutoSelectId = null;

        window._hbPending = {
            serviceId: null,
            elem: null,
            storeId: null
        };
        var _hbDoctorId = null;
        var _hbSlotId = null;
        var _hbSlotTime = null;

        function hbSelectDoctor(id, el) {
            document.querySelectorAll('.hb-doctor-card').forEach(c => c.classList.remove('hb-selected'));
            el.classList.add('hb-selected');
            _hbDoctorId = id;
            document.getElementById('hbDoctorErr').classList.add('d-none');
            hbLoadSlots();
        }

        function hbLoadSlots() {
            const date = document.getElementById('hbDate').value;
            if (!_hbDoctorId || !date) return;

            const section = document.getElementById('hbSlotSection');
            const grid = document.getElementById('hbSlotGrid');
            section.style.display = 'block';
            grid.innerHTML = '<span class="text-muted small">Loading slots...</span>';
            _hbSlotId = null;
            _hbSlotTime = null;

            fetch(`${window._hbSlotsUrl}?doctor_profile_id=${_hbDoctorId}&date=${date}`)
                .then(r => r.json())
                .then(slots => {
                    if (!slots.length) {
                        grid.innerHTML = '<span class="text-muted small">No slots configured for this day.</span>';
                        return;
                    }
                    let html = '';
                    slots.forEach(s => {
                        const full = s.available <= 0;
                        html += `<div class="hb-slot-card ${full ? 'hb-slot-full' : ''}"
                    data-slot-id="${s.id}" data-start="${s.slot_start}"
                    ${full ? '' : 'onclick="hbSelectSlot(this)"'}>
                    <div class="hb-slot-time">${hbFmt(s.slot_start)} – ${hbFmt(s.slot_end)}</div>
                    <div class="hb-slot-avail">${full ? 'Full' : s.available + '/' + s.max_patients + ' avail'}</div>
                </div>`;
                    });
                    grid.innerHTML = html;
                })
                .catch(() => {
                    grid.innerHTML = '<span class="text-danger small">Could not load slots.</span>';
                });
        }

        function hbSelectSlot(el) {
            const id = el.dataset.slotId;
            const start = el.dataset.start;
            if (_hbSlotId === id) {
                document.querySelectorAll('.hb-slot-card').forEach(c => c.classList.remove('hb-slot-selected'));
                _hbSlotId = null;
                _hbSlotTime = null;
                return;
            }
            document.querySelectorAll('.hb-slot-card').forEach(c => c.classList.remove('hb-slot-selected'));
            el.classList.add('hb-slot-selected');
            _hbSlotId = id;
            _hbSlotTime = start ? start.substring(0, 5) : null;
        }

        function hbFmt(t) {
            if (!t) return '';
            const [h, m] = t.split(':');
            const hr = parseInt(h);
            return `${hr > 12 ? hr - 12 : (hr || 12)}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
        }

        function hbConfirm() {
            // Validate doctor
            if (!_hbDoctorId) {
                document.getElementById('hbDoctorErr').classList.remove('d-none');
                return;
            }
            if (!document.getElementById('hbDate').value) {
                document.getElementById('hbDateErr').classList.remove('d-none');
                return;
            }

            const btn = document.getElementById('hbConfirmBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.post({
                url: "{{ route('book-service') }}",
                data: {
                    serviceId: window._hbPending.serviceId,
                    storeId: window._hbPending.storeId,
                    preferred_doctor_id: _hbDoctorId,
                    preferred_date: document.getElementById('hbDate').value,
                    preferred_slot_id: _hbSlotId || null,
                    preferred_time: _hbSlotTime || null,
                    requirements: document.getElementById('hbReason').value || null,
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
                        setTimeout(() => {
                            $(window._hbPending.elem).html(
                                '<i class="fas fa-user-cog"></i> Enquiry Now');
                        }, 1500);
                    }
                }
            });
        }

        function hbFilterDoctors(serviceId) {
            var cards = document.querySelectorAll('.hb-doctor-card');
            var anyVisible = false;
            cards.forEach(function(card) {
                var services = card.dataset.services ? card.dataset.services.split(',') : [];
                // Show all if no services assigned to any doctor, or if this doctor offers the service
                var show = !serviceId || services.length === 0 || services.indexOf(String(serviceId)) !== -1;
                card.style.display = show ? '' : 'none';
                if (show) anyVisible = true;
            });
            // Fallback: if no doctor matches, show all
            if (!anyVisible) {
                cards.forEach(function(card) { card.style.display = ''; });
            }
        }

        $('#hospitalBookingModal').on('show.bs.modal', function() {
            hbFilterDoctors(window._hbPending ? window._hbPending.serviceId : null);
        });

        function _hbReset() {
            _hbDoctorId = null;
            _hbSlotId = null;
            _hbSlotTime = null;
            document.querySelectorAll('.hb-doctor-card').forEach(c => c.classList.remove('hb-selected'));
            document.querySelectorAll('.hb-slot-card').forEach(c => c.classList.remove('hb-slot-selected'));
            document.getElementById('hbSlotSection').style.display = 'none';
            document.getElementById('hbReason').value = '';
            document.getElementById('hbDate').value = '{{ date('Y-m-d') }}';
        }

        // Signal to layout.blade.php bookService() that this is a hospital store
        // (the interception logic lives in layout.blade.php so execution order is guaranteed)
    </script>
@else 
<script>
console.log('{{strtolower($store->business_type ?? '')}}') 
</script>
@endif {{-- $hbDoctors->isNotEmpty() --}}
