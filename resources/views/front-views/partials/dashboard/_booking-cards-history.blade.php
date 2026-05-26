@foreach ($items as $key => $serRun)
    @php $reviewStatus = _reviewStatus($serRun->id); @endphp
    <div class="service-accordion-item mb-4">
        <div class="service-card-header" data-bs-toggle="collapse"
            data-bs-target="#histCollapse{{ $serRun->service_request_id }}"
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
                            {{ $serRun->current_status }}
                        </span>
                        <div class="collapse-toggle-icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="histCollapse{{ $serRun->service_request_id }}"
            class="collapse {{ !$key ? 'show' : '' }}"
            data-bs-parent=".history-bookings-container">
            <div class="service-card-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="detail-card service_info_{{ $serRun->id }}">
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

                            @if ($reviewStatus['status'] === 'exists')
                                @php $rev = $reviewStatus['review'] ?? null; $store = App\Models\Store::find($serRun->store_id); @endphp
                                <div class="detail-card">
                                    <div class="detail-card-header"><i class="fas fa-star me-2"></i><span>Rating & Review</span></div>
                                    <div class="detail-card-content">
                                        <div class="d-flex border rounded my-2 p-2">
                                            <div class="d-flex flex-column w-100">
                                                <div class="d-flex justify-content-between review_info">
                                                    <div>
                                                        <p class="mb-2 date_time">{{ _formatted_datetime($rev->created_at) }}</p>
                                                        <div class="d-flex">
                                                            @for ($i = 1; $i < 6; $i++)
                                                                <i class="rating_star fa fa-star {{ $rev->rating >= $i ? 'text-secondary' : '' }}"></i>
                                                            @endfor
                                                        </div>
                                                        <p class="text-dark">{{ $rev->comment }}</p>
                                                    </div>
                                                    @if ($rev->attachment)
                                                        @php $attachments = (array) $rev->attachment; @endphp
                                                        @if (!empty($attachments))
                                                            <div class="d-flex">
                                                                @foreach ($attachments as $img)
                                                                    <a target="_blank" class="mx-1"
                                                                        href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                                                        <img loading="lazy" class="rounded" style="width: 55px;"
                                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                            alt="review">
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                                @if ($rev->reply)
                                                    <div class="d-flex border rounded p-2">
                                                        <img loading="lazy"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                            class="img-fluid rounded m-2 reply_img" alt="{{ $store->name }}">
                                                        <div>
                                                            <p class="mb-0 date_time">{{ _formatted_datetime($rev->replied_at) }}</p>
                                                            <p class="text-dark">{{ $rev->reply }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @php $coupon = _serviceCoupon($serRun->id) @endphp
                            @if ($coupon)
                                <div class="detail-card">
                                    <div class="detail-card-header"><i class="fas fa-tag me-2"></i><span>Coupon</span></div>
                                    <div class="detail-card-content">
                                        <div class="coupon-card p-0">
                                            <div class="coupon-top">
                                                <div class="coupon-name">{{ ucfirst($coupon->title) }}</div>
                                                <div class="coupon-code-box">{{ $coupon->code }}</div>
                                            </div>
                                            <div class="coupon-bottom">
                                                <div class="coupon-info">
                                                    <div class="info-item">
                                                        <div class="info-label">Discount</div>
                                                        <div class="info-value discount-value">
                                                            {{ $coupon->discount_type == 'amount' ? \App\CentralLogics\Helpers::currency_symbol() : '' }}{{ $coupon->discount }}{{ $coupon->discount_type == 'percent' ? '%' : '' }}
                                                        </div>
                                                    </div>
                                                    <div class="info-item">
                                                        <div class="info-label">Minimum Purchase</div>
                                                        <div class="info-value">{{ $coupon->min_purchase }}</div>
                                                    </div>
                                                </div>
                                                <div class="coupon-meta">
                                                    <span>{{ date('M d', strtotime($coupon->start_date)) }} - {{ date('M d', strtotime($coupon->expire_date)) }}</span>
                                                    <span>{{ $coupon->total_uses }}/{{ $coupon->limit }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
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
                    <div class="actions-section mt-4">
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                @if ($serRun->invoice)
                                    <a href="{{ $serRun->invoice }}" target="_blank" class="action-button action-primary w-100">
                                        <i class="fas fa-receipt"></i><span>Invoice</span>
                                    </a>
                                @else
                                    <button class="action-button action-disabled w-100" disabled>
                                        <i class="fas fa-receipt"></i><span>Invoice</span>
                                    </button>
                                @endif
                            </div>
                            <div class="col-sm-6 col-md-3">
                                @if ($reviewStatus['status'] === true)
                                    <button type="button"
                                        class="action-button action-primary w-100 service_review_btn"
                                        data-id="{{ $serRun->id }}"
                                        data-store_id="{{ $serRun->store_id }}"
                                        data-service_name="{{ $serRun->item_name }}"
                                        data-service_date="{{ date('Y-m-d', strtotime($serRun->created_at)) }}"
                                        data-bs-toggle="modal" data-bs-target="#serviceReviewModal">
                                        <i class="fas fa-star"></i><span>Review</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach
