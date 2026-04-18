@if (!empty($data['has_appointment']) && $data['has_appointment'])
    @php
        $doctors = \App\Models\DoctorProfile::where('store_id', $store->id)
            ->with('employee')
            ->where(function ($q) {
                $q->whereHas('slots', fn($s) => $s->where('is_active', 1));
            })
            ->get();
    @endphp
    @if ($doctors->isNotEmpty())

        {{-- ════════════════════════════════════════════
     APPOINTMENT BOOKING SECTION
     Included in all store templates conditionally
     ══════════════════════════════════════════ --}}

        <div id="appointment-section" class="appt-section-wrap">
            <div class="appt-inner">

                {{-- Header --}}
                <div class="appt-header">
                    <div class="appt-header-left">
                        <span class="appt-icon">🩺</span>
                        <div>
                            <h3 class="appt-title">Our Doctors</h3>
                            <p class="appt-subtitle">Consult our doctors — pick a slot that works for you</p>
                        </div>
                    </div>
                    {{-- <a href="{{ route('front.appointment.book', [request()->segment(1), $store->slug]) }}"
                        class="appt-btn-full">
                        View All Doctors & Book
                    </a> --}}
                </div>

                {{-- Doctor Cards --}}
                <div class="appt-doctors-row">
                    @foreach ($doctors->take(4) as $d)
                        @php
                            $name = 'Dr. ' . ($d->employee?->f_name ?? '') . ' ' . ($d->employee?->l_name ?? '');
                            $initial = strtoupper(substr($d->employee?->f_name ?? 'D', 0, 1));
                        @endphp
                        <a class="appt-doctor-card"
                            href="javascript:;">
                            <div class="appt-doc-avatar">{{ $initial }}</div>
                            <div class="appt-doc-info">
                                <p class="appt-doc-name">{{ trim($name) }}</p>
                                <p class="appt-doc-spec">{{ $d->specialization }}</p>
                                {{-- @if ($d->consultation_fee > 0)
                                    <p class="appt-doc-fee">₹{{ number_format($d->consultation_fee, 0) }}</p>
                                @else
                                    <p class="appt-doc-fee free">Free</p>
                                @endif --}}
                            </div>
                            {{-- <span class="appt-book-badge">Book →</span> --}}
                        </a>
                    @endforeach

                    @if ($doctors->count() > 4)
                        <a class="appt-doctor-card appt-more-card"
                            href="{{ route('front.appointment.book', [request()->segment(1), $store->slug]) }}">
                            <div class="appt-doc-avatar" style="background:#f3f4f6; color:#6b7280;">
                                +{{ $doctors->count() - 4 }}
                            </div>
                            <div class="appt-doc-info">
                                <p class="appt-doc-name">More Doctors</p>
                                <p class="appt-doc-spec">View all available</p>
                            </div>
                            <span class="appt-book-badge">View →</span>
                        </a>
                    @endif
                </div>

            </div>
        </div>

        <style>
            .appt-section-wrap {
                margin: 32px 0;
                font-family: inherit;
            }

            .appt-inner {
                background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
                border: 1px solid #bfdbfe;
                border-radius: 18px;
                padding: 24px 28px;
            }

            .appt-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                flex-wrap: wrap;
                gap: 12px;
            }

            .appt-header-left {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .appt-icon {
                font-size: 28px;
                line-height: 1;
            }

            .appt-title {
                font-size: 18px;
                font-weight: 700;
                color: #1e3a5f;
                margin: 0 0 2px;
            }

            .appt-subtitle {
                font-size: 13px;
                color: #4b7098;
                margin: 0;
            }

            .appt-btn-full {
                background: #2563eb;
                color: #fff !important;
                padding: 10px 20px;
                border-radius: 10px;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none !important;
                white-space: nowrap;
                transition: background .15s;
            }

            .appt-btn-full:hover {
                background: #1d4ed8;
            }

            /* Doctor cards row */
            .appt-doctors-row {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }

            .appt-doctor-card {
                display: flex;
                align-items: center;
                gap: 12px;
                background: #fff;
                border: 1.5px solid #e0e7ff;
                border-radius: 14px;
                padding: 12px 16px;
                flex: 1 1 200px;
                min-width: 200px;
                max-width: 280px;
                text-decoration: none !important;
                color: inherit !important;
                transition: border-color .15s, box-shadow .15s;
                position: relative;
            }

            .appt-doctor-card:hover {
                border-color: #2563eb;
                box-shadow: 0 4px 14px rgba(37, 99, 235, .12);
            }

            .appt-doc-avatar {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background: #dbeafe;
                color: #2563eb;
                font-size: 18px;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .appt-doc-info {
                flex: 1;
                min-width: 0;
            }

            .appt-doc-name {
                font-size: 13px;
                font-weight: 700;
                color: #111;
                margin: 0 0 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .appt-doc-spec {
                font-size: 11px;
                color: #6b7280;
                margin: 0 0 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .appt-doc-fee {
                font-size: 12px;
                font-weight: 700;
                color: #059669;
                margin: 0;
            }

            .appt-doc-fee.free {
                color: #7c3aed;
            }

            .appt-book-badge {
                font-size: 11px;
                color: #2563eb;
                font-weight: 600;
                white-space: nowrap;
            }

            .appt-more-card .appt-doc-avatar {
                background: #f3f4f6;
                color: #6b7280;
                font-size: 14px;
            }

            @media (max-width: 600px) {
                .appt-inner {
                    padding: 18px 16px;
                }

                .appt-doctor-card {
                    max-width: 100%;
                    flex: 1 1 100%;
                }

                .appt-btn-full {
                    width: 100%;
                    text-align: center;
                }

                .appt-header {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }
        </style>

    @endif
@endif
