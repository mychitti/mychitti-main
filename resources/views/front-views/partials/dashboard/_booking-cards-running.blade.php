{{-- Moves the hospital has asked for and not heard back on, keyed by the booking they belong to.

     The WhatsApp link is not the only way a patient should be able to answer this: a template can
     be unapproved, a message can be missed, a number can be wrong — and none of that is the
     patient's problem. They are logged in, looking at the booking in question, so the question
     belongs here too. Read once for the page rather than per card. --}}
@php
    $fvMoves = collect();
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('appointment_reschedule_requests')
            && \Illuminate\Support\Facades\Schema::hasColumn('appointments', 'service_request_id')) {

            $fvApptByRequest = \App\Models\Appointment::whereIn('service_request_id', collect($items)->pluck('service_request_id')->filter())
                ->pluck('service_request_id', 'id');

            if ($fvApptByRequest->isNotEmpty()) {
                $fvMoves = \App\Models\AppointmentRescheduleRequest::whereIn('appointment_id', $fvApptByRequest->keys())
                    ->where('status', 'pending')
                    ->orderBy('id')
                    ->get()
                    ->reject(fn($m) => $m->is_lapsed)
                    ->keyBy(fn($m) => $fvApptByRequest[$m->appointment_id]);
            }
        }
    } catch (\Throwable $e) {
        $fvMoves = collect();
    }
@endphp

@foreach ($items as $key => $serRun)
    @php $fvMove = $fvMoves[$serRun->service_request_id] ?? null; @endphp
    <div class="service-accordion-item mb-4">
        <div class="service-card-header" data-bs-toggle="collapse"
            data-bs-target="#runCollapse{{ $serRun->service_request_id }}"
            aria-expanded="{{ !$key ? 'true' : 'false' }}" role="button">
            <div class="row align-items-center g-3">
                <div class="col-md-6 col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        @if (isset($serRun->item_image))
                            <div class="service-img-box">
                                <img class="service-img"
                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                    src="{{ strpos($serRun->item_image, 'http') !== 0 ? asset('storage/app/public/product') . '/' : '' }}{{ $serRun->item_image }}"
                                    alt="{{ $serRun->item_name }}">
                            </div>
                        @endif
                        <div class="service-info-box">
                            <h5 class="service-name mb-1">{{ $serRun->item_name }}</h5>
                            <div class="service-meta-info">
                                <span class="service-id-badge">#{{ $serRun->service_request_id }}</span>
                                <span class="service-date-text">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    {{ date('d M Y, H:i', strtotime($serRun->created_at)) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="d-flex align-items-center justify-content-md-end gap-3">
                        <span class="status-pill status-{{ strtolower(str_replace(' ', '-', $serRun->current_status)) }}">
                            @if($serRun->current_status == 'Confirmation Request Sent')
                                Confirmation Request Received
                            @else
                                {{ $serRun->current_status }}
                            @endif
                        </span>
                        {{-- Confirmed, and still waiting on the patient for something. Flagged up
                             here as well as in the panel below, because the header is what shows
                             when the card is collapsed. --}}
                        @if ($fvMove)
                            <span class="status-pill" style="background:#fef3c7; color:#92400e;">
                                Reschedule requested
                            </span>
                        @endif
                        <div class="collapse-toggle-icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="runCollapse{{ $serRun->service_request_id }}"
            class="collapse {{ !$key ? 'show' : '' }}"
            data-bs-parent=".running-bookings-container">
            <div class="service-card-body">
                {{-- The one thing on this card that needs an answer, so it sits above everything
                     else on it. The booking stays on its original date until Confirm is pressed —
                     said plainly, because a patient who reads this and does nothing must not end
                     up at the clinic on the wrong day. --}}
                @if ($fvMove)
                    <div class="mb-4" style="background:#fffbeb; border:1px solid #fcd34d; border-radius:12px; padding:16px;">
                        <h6 style="margin:0 0 6px; font-weight:700; color:#92400e;">
                            {{ ucfirst($serRun->store_name ?? 'The hospital') }} has asked to move your appointment
                        </h6>
                        <div class="d-flex flex-wrap align-items-center" style="gap:14px; margin-bottom:10px;">
                            <div>
                                <div style="font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#92400e;">Currently booked</div>
                                <div style="font-weight:600; text-decoration:line-through; color:#78716c;">{{ $fvMove->currentLabel() }}</div>
                            </div>
                            <div>
                                <div style="font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#92400e;">New time proposed</div>
                                <div style="font-weight:700; color:#166534;">{{ $fvMove->proposedLabel() }}</div>
                            </div>
                        </div>

                        @if (filled($fvMove->note))
                            <p style="font-size:13px; color:#57534e; margin-bottom:10px;">{{ $fvMove->note }}</p>
                        @endif

                        {{-- The same endpoint the WhatsApp link posts to, and the same two answers.
                             Nothing here decides anything the token page would not. --}}
                        <form method="POST" action="{{ route('appointment-reschedule.respond', ['token' => $fvMove->token]) }}">
                            @csrf
                            <input type="text" name="note" class="form-control mb-2" maxlength="500"
                                   placeholder="If it doesn't suit — when would? (optional)"
                                   style="font-size:13px; max-width:420px;">
                            <div class="d-flex flex-wrap" style="gap:8px;">
                                <button type="submit" name="answer" value="accept" class="btn btn-success btn-sm">
                                    Yes, {{ $fvMove->proposedLabel() }} works
                                </button>
                                <button type="submit" name="answer" value="decline" class="btn btn-outline-secondary btn-sm">
                                    No, that time doesn't suit me
                                </button>
                            </div>
                        </form>

                        <p style="font-size:12px; color:#78716c; margin:10px 0 0;">
                            Your appointment on {{ $fvMove->currentLabel() }} stands until you confirm.
                        </p>
                    </div>
                @endif

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="detail-card">
                            <div class="detail-card-header"><i class="fas fa-info-circle me-2"></i><span>Service Details</span></div>
                            <div class="detail-card-content">
                                <div class="detail-row">
                                    <span class="detail-label">Service ID</span>
                                    <span class="detail-value">#{{ $serRun->service_request_id }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Booking Date</span>
                                    <span class="detail-value">{{ date('d M Y H:i', strtotime($serRun->created_at)) }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Current Status</span>
                                    <span class="detail-value">
                                        <span class="status-pill-sm status-{{ strtolower(str_replace(' ', '-', $serRun->current_status)) }}">
                                            {{ $serRun->current_status }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if ($serRun->assigned_to)
                            <div class="detail-card mt-3">
                                <div class="detail-card-header"><i class="fas fa-user-tie me-2"></i><span>Assigned Staff</span></div>
                                <div class="detail-card-content">
                                    <div class="profile-box">
                                        <img class="profile-avatar"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ $serRun->staff_image }}" alt="{{ $serRun->staff_name }}">
                                        <div class="profile-info">
                                            <h6 class="profile-name">{{ $serRun->staff_name }}</h6>
                                            <p class="profile-designation">{{ $serRun->staff_role }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-6">
                        @if ($serRun->store_id)
                            <div class="detail-card">
                                <div class="detail-card-header"><i class="fas fa-store me-2"></i><span>Store Information</span></div>
                                <div class="detail-card-content">
                                    <div class="profile-box">
                                        @if (isset($serRun->store_logo))
                                            <img class="profile-avatar"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ strpos($serRun->store_logo, 'http') !== 0 ? asset('storage/app/public/store') . '/' : '' }}{{ $serRun->store_logo }}"
                                                alt="{{ $serRun->store_name }}">
                                        @endif
                                        <div class="profile-info">
                                            <h6 class="profile-name">
                                                <a href="{{ route('store.details', [_selectedCity(), $serRun->store_slug]) }}" class="store-link">
                                                    {{ ucfirst($serRun->store_name) }}
                                                </a>
                                            </h6>
                                            <p class="profile-address"><i class="fas fa-map-marker-alt me-1"></i>{{ $serRun->store_address }}</p>
                                            <p class="profile-contact"><i class="fas fa-phone me-1"></i>{{ $serRun->store_phone }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if (isset($serRun->store_id))
                    @php $isHospitalStore = strtolower($serRun->store_business_type ?? '') === 'hospital'; @endphp
                    @if (!$isHospitalStore)
                        <div class="pricing-section mt-4">
                            <div class="pricing-box">
                                <span class="pricing-label">Visiting Charges</span>
                                <span class="pricing-amount">{{ \App\CentralLogics\Helpers::currency_symbol() }}{{ number_format($serRun->quoted_price, 2) }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="cta-buttons-section mt-4">
                        <div class="d-flex gap-2 action_outer_{{ $serRun->id }}">
                            @include('front-views.partials.dashboard._service-actions-element', ['acceptedReq' => $serRun])
                        </div>
                    </div>
                    @if (!$isHospitalStore)
                        <div class="actions-section mt-4">
                            <div class="row g-3">
                                <div class="col-sm-6 col-md-3">
                                    @if ($serRun->gatepass_exists)
                                        <button class="action-button action-primary w-100 gatepass-modal-btn"
                                            data-id="{{ $serRun->service_request_id }}"
                                            data-bs-toggle="modal" data-bs-target="#gatepassModal">
                                            <i class="fas fa-id-card"></i><span>Gatepass</span>
                                        </button>
                                    @else
                                        <button class="action-button action-disabled w-100" disabled>
                                            <i class="fas fa-id-card"></i><span>Gatepass</span>
                                        </button>
                                    @endif
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    @if ($serRun->quotation_exists)
                                        <button class="action-button action-primary w-100 quotation-modal-btn"
                                            data-id="{{ $serRun->service_request_id }}"
                                            data-bs-toggle="modal" data-bs-target="#quotationModal">
                                            <i class="fas fa-file-invoice"></i><span>Quotation</span>
                                        </button>
                                    @else
                                        <button class="action-button action-disabled w-100" disabled>
                                            <i class="fas fa-file-invoice"></i><span>Quotation</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endforeach
