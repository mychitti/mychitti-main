<div class="tab-pane fade show active" id="v-pills-service" role="tabpanel" aria-labelledby="v-pills-service-tab">
    <div class="container tab_inner">
        <h3 class="text-primary  my-2">Bookings</h3>
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="col-6 nav-link active" id="nav-Running-service-tab" data-bs-toggle="tab"
                    data-bs-target="#nav-Running-service" type="button" role="tab"
                    aria-controls="nav-Running-service" aria-selected="true">Running Bookings</button>
                <button class="col-6 nav-link" id="nav-History-service-tab" data-bs-toggle="tab"
                    data-bs-target="#nav-History-service" type="button" role="tab"
                    aria-controls="nav-History-service" aria-selected="false">Booking History</button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active " id="nav-Running-service" role="tabpanel"
                aria-labelledby="nav-Running-service-tab">
                <div class="accordion" id="accordionExample">
                    @if (count($services['running']))
                        <div class="service-history-container">
                            @foreach ($services['running'] as $key => $serRun)
                                <div class="service-accordion-item mb-4">
                                    <!-- Service Card Header -->
                                    <div class="service-card-header" data-bs-toggle="collapse"
                                        data-bs-target="#serviceCollapse{{ $key }}"
                                        aria-expanded="{{ !$key ? 'true' : 'false' }}" role="button">
                                        <div class="row align-items-center g-3">
                                            <!-- Service Image & Info -->
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
                                                            <span
                                                                class="service-id-badge">#{{ $serRun->service_request_id }}</span>
                                                            <span class="service-date-text">
                                                                <i class="far fa-calendar-alt me-1"></i>
                                                                {{ date('d M Y, H:i', strtotime($serRun->created_at)) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status & Toggle -->
                                            <div class="col-md-6 col-lg-5">
                                                <div class="d-flex align-items-center justify-content-md-end gap-3">
                                                    <span
                                                        class="status-pill status-{{ strtolower(str_replace(' ', '-', $serRun->current_status)) }}">
                                                        {{ $serRun->current_status }}
                                                    </span>
                                                    <div class="collapse-toggle-icon">
                                                        <i class="fas fa-chevron-down"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Service Card Body (Collapsible) -->
                                    <div id="serviceCollapse{{ $key }}"
                                        class="collapse {{ !$key ? 'show' : '' }}"
                                        data-bs-parent=".service-history-container">
                                        <div class="service-card-body">
                                            <div class="row g-4">
                                                <!-- Left Column -->
                                                <div class="col-lg-6">
                                                    <!-- Service Details Card -->
                                                    <div class="detail-card">
                                                        <div class="detail-card-header">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            <span>Service Details</span>
                                                        </div>
                                                        <div class="detail-card-content">
                                                            <div class="detail-row">
                                                                <span class="detail-label">Service ID</span>
                                                                <span
                                                                    class="detail-value">#{{ $serRun->service_request_id }}</span>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span class="detail-label">Booking Date</span>
                                                                <span
                                                                    class="detail-value">{{ date('d M Y  H:i', strtotime($serRun->created_at)) }}</span>
                                                            </div>
                                                            <div class="detail-row">
                                                                <span class="detail-label">Current Status</span>
                                                                <span class="detail-value">
                                                                    <span
                                                                        class="status-pill-sm status-{{ strtolower(str_replace(' ', '-', $serRun->current_status)) }}">
                                                                        {{ $serRun->current_status }}
                                                                    </span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Staff Details Card -->
                                                    @if ($serRun->assigned_to)
                                                        <div class="detail-card mt-3">
                                                            <div class="detail-card-header">
                                                                <i class="fas fa-user-tie me-2"></i>
                                                                <span>Assigned Staff</span>
                                                            </div>
                                                            <div class="detail-card-content">
                                                                <div class="profile-box">
                                                                    <img class="profile-avatar"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                        src="{{ $serRun->staff_image }}"
                                                                        alt="{{ $serRun->staff_name }}">
                                                                    <div class="profile-info">
                                                                        <h6 class="profile-name">
                                                                            {{ $serRun->staff_name }}</h6>
                                                                        <p class="profile-designation">
                                                                            {{ $serRun->staff_role }}</p>
                                                                        <p class="profile-contact">
                                                                            <i class="fas fa-phone me-1"></i>
                                                                            {{ $serRun->staff_contact }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Right Column -->
                                                <div class="col-lg-6">
                                                    @if ($serRun->store_id)
                                                        <!-- Store Details Card -->
                                                        <div class="detail-card">
                                                            <div class="detail-card-header">
                                                                <i class="fas fa-store me-2"></i>
                                                                <span>Store Information</span>
                                                            </div>
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
                                                                            <a href="{{ route('store.details', [_selectedCity(), $serRun->store_slug]) }}"
                                                                                class="store-link">
                                                                                {{ ucfirst($serRun->store_name) }}
                                                                            </a>
                                                                        </h6>
                                                                        <p class="profile-address">
                                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                                            {{ $serRun->store_address }}
                                                                        </p>
                                                                        <p class="profile-contact">
                                                                            <i class="fas fa-phone me-1"></i>
                                                                            {{ $serRun->store_phone }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if (isset($serRun->store_id))
                                                <!-- Pricing Section -->
                                                <div class="pricing-section mt-4">
                                                    <div class="pricing-box">
                                                        <span class="pricing-label">Visiting Charges</span>
                                                        <span
                                                            class="pricing-amount">{{ \App\CentralLogics\Helpers::currency_symbol() }}{{ number_format($serRun->quoted_price, 2) }}</span>
                                                    </div>
                                                </div>
                                                <div class="cta-buttons-section mt-4">
                                                    <div class="d-flex gap-2 action_outer_{{ $serRun->id }}">
                                                        @include(
                                                            'front-views.partials.dashboard._service-actions-element',
                                                            ['acceptedReq' => $serRun]
                                                        )
                                                    </div>
                                                </div>

                                                <!-- Action Buttons Section -->
                                                <div class="actions-section mt-4">
                                                    <div class="row g-3">
                                                        <!-- Gatepass Button -->
                                                        <div class="col-sm-6 col-md-3">
                                                            @if ($serRun->gatepass_exists)
                                                                <button
                                                                    class="action-button action-primary w-100 gatepass-modal-btn"
                                                                    data-id="{{ $serRun->service_request_id }}"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#gatepassModal">
                                                                    <i class="fas fa-id-card"></i>
                                                                    <span>Gatepass</span>
                                                                </button>
                                                            @else
                                                                <button class="action-button action-disabled w-100"
                                                                    disabled>
                                                                    <i class="fas fa-id-card"></i>
                                                                    <span>Gatepass</span>
                                                                </button>
                                                            @endif
                                                        </div>

                                                        <!-- Quotation Button -->
                                                        <div class="col-sm-6 col-md-3">
                                                            @if ($serRun->quotation_exists)
                                                                <button
                                                                    class="action-button action-primary w-100 quotation-modal-btn"
                                                                    data-id="{{ $serRun->service_request_id }}"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#quotationModal">
                                                                    <i class="fas fa-file-invoice"></i>
                                                                    <span>Quotation</span>
                                                                </button>
                                                            @else
                                                                <button class="action-button action-disabled w-100"
                                                                    disabled>
                                                                    <i class="fas fa-file-invoice"></i>
                                                                    <span>Quotation</span>
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
                        </div>
                    @else
                        <p class="text-center my-5"> No active bookings...</p>
                    @endif
                </div>
            </div>
            <div class="tab-pane fade " id="nav-History-service" role="tabpanel"
                aria-labelledby="nav-History-service-tab">
                <div class="accordion" id="accordionExample">
                    @if (count($services['history']))
                        @foreach ($services['history'] as $key => $serRun)
                            <div class="service-history-container">
                                @foreach ($services['history'] as $key => $serRun)
                                    <div class="service-accordion-item mb-4">
                                        <!-- Service Card Header -->
                                        <div class="service-card-header" data-bs-toggle="collapse"
                                            data-bs-target="#serviceCollapse{{ $key }}"
                                            aria-expanded="{{ !$key ? 'true' : 'false' }}" role="button">
                                            <div class="row align-items-center g-3">
                                                <!-- Service Image & Info -->
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
                                                            <h5 class="service-name mb-1">{{ $serRun->item_name }}
                                                            </h5>
                                                            <div class="service-meta-info">
                                                                <span
                                                                    class="service-id-badge">#{{ $serRun->service_request_id }}</span>
                                                                <span class="service-date-text">
                                                                    <i class="far fa-calendar-alt me-1"></i>
                                                                    {{ date('d M Y, H:i', strtotime($serRun->created_at)) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Status & Toggle -->
                                                <div class="col-md-6 col-lg-5">
                                                    <div
                                                        class="d-flex align-items-center justify-content-md-end gap-3">
                                                        <span
                                                            class="status-pill status-{{ strtolower(str_replace(' ', '-', $serRun->current_status)) }}">
                                                            {{ $serRun->current_status }}
                                                        </span>
                                                        <div class="collapse-toggle-icon">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Service Card Body (Collapsible) -->
                                        <div id="serviceCollapse{{ $key }}"
                                            class="collapse {{ !$key ? 'show' : '' }}"
                                            data-bs-parent=".service-history-container">
                                            <div class="service-card-body">
                                                <div class="row g-4">
                                                    <!-- Left Column -->
                                                    <div class="col-lg-6">
                                                        <!-- Service Details Card -->
                                                        <div class="detail-card service_info_{{ $serRun->id }}">
                                                            <div class="detail-card-header">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                <span>Service Details</span>
                                                            </div>
                                                            <div class="detail-card-content">
                                                                <div class="detail-row">
                                                                    <span class="detail-label">Service ID</span>
                                                                    <span
                                                                        class="detail-value">#{{ $serRun->service_request_id }}</span>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <span class="detail-label">Booking Date</span>
                                                                    <span
                                                                        class="detail-value">{{ date('d M Y  H:i', strtotime($serRun->created_at)) }}</span>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <span class="detail-label">Current Status</span>
                                                                    <span class="detail-value">
                                                                        <span
                                                                            class="status-pill-sm status-{{ strtolower(str_replace(' ', '-', $serRun->current_status)) }}">
                                                                            {{ $serRun->current_status }}
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Staff Details Card -->
                                                        @if ($serRun->assigned_to)
                                                            <div class="detail-card mt-3">
                                                                <div class="detail-card-header">
                                                                    <i class="fas fa-user-tie me-2"></i>
                                                                    <span>Assigned Staff</span>
                                                                </div>
                                                                <div class="detail-card-content">
                                                                    <div class="profile-box">
                                                                        <img class="profile-avatar"
                                                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                            src="{{ $serRun->staff_image }}"
                                                                            alt="{{ $serRun->staff_name }}">
                                                                        <div class="profile-info">
                                                                            <h6 class="profile-name">
                                                                                {{ $serRun->staff_name }}</h6>
                                                                            <p class="profile-designation">
                                                                                {{ $serRun->staff_role }}</p>
                                                                            <p class="profile-contact">
                                                                                <i class="fas fa-phone me-1"></i>
                                                                                {{ $serRun->staff_contact }}
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- Right Column -->
                                                    <div class="col-lg-6">
                                                        @if ($serRun->store_id)
                                                            <!-- Store Details Card -->
                                                            <div class="detail-card">
                                                                <div class="detail-card-header">
                                                                    <i class="fas fa-store me-2"></i>
                                                                    <span>Store Information</span>
                                                                </div>
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
                                                                                <a href="{{ route('store.details', [_selectedCity(), $serRun->store_slug]) }}"
                                                                                    class="store-link">
                                                                                    {{ ucfirst($serRun->store_name) }}
                                                                                </a>
                                                                            </h6>
                                                                            <p class="profile-address">
                                                                                <i
                                                                                    class="fas fa-map-marker-alt me-1"></i>
                                                                                {{ $serRun->store_address }}
                                                                            </p>
                                                                            <p class="profile-contact">
                                                                                <i class="fas fa-phone me-1"></i>
                                                                                {{ $serRun->store_phone }}
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="detail-card">
                                                                <div class="detail-card-header">
                                                                    <i class="fas fa-store me-2"></i>
                                                                    <span>Rating & Review</span>
                                                                </div>
                                                                <div class="detail-card-content">
                                                                 @if (_reviewStatus($serRun->id)['status'] == 'exists')
                                                                    @php
                                                                        $rev = _reviewStatus($serRun->id)['review'];
                                                                        $store = App\Models\Store::find(
                                                                            $serRun->store_id,
                                                                        );
                                                                    @endphp
                                                                    <div class="d-flex border rounded my-2  p-2">
                                                                       
                                                                        <div class="d-flex flex-column w-100">
                                                                            <div
                                                                                class="d-flex justify-content-between review_info">
                                                                                <div class="">
                                                                                    <p class="mb-2 date_time"
                                                                                        style="">
                                                                                        {{ _formatted_datetime($rev->created_at) }}
                                                                                    </p>
                                                                                    <div class="d-flex ">
                                                                                       
                                                                                        <div class="d-flex ">
                                                                                            @for ($i = 1; $i < 6; $i++)
                                                                                                <i
                                                                                                    class="rating_star fa fa-star {{ $rev->rating >= $i ? 'text-secondary' : '' }}"></i>
                                                                                            @endfor
                                                                                        </div>
                                                                                    </div>
                                                                                    <p class="text-dark">
                                                                                        {{ $rev->comment }}</p>
                                                                                </div>

                                                                                @if ($rev->attachment)
                                                                                    @php $attachments = json_decode($rev->attachment); @endphp
                                                                                    @if (!empty($attachments))
                                                                                        <div class="d-flex">
                                                                                            @foreach ($attachments as $img)
                                                                                                <a target="_blank"
                                                                                                    class="mx-1"
                                                                                                    href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                                                                        loading="lazy"
                                                                                                        class="rounded"
                                                                                                        style="width: 55px;"
                                                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                                                        alt="review"></a>
                                                                                                <a target="_blank"
                                                                                                    href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                                                                        loading="lazy"
                                                                                                        class="rounded"
                                                                                                        style="width: 55px;"
                                                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                                                        alt="review"></a>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @endif
                                                                                @endif
                                                                            </div>
                                                                            @if ($rev->reply)
                                                                                <div
                                                                                    class="d-flex border rounded  p-2">
                                                                                    <img loading="lazy"
                                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                                                        class="img-fluid rounded m-2 reply_img"
                                                                                        style=""
                                                                                        alt="{{ $store->name }}">
                                                                                    <div class="">
                                                                                        <p class="mb-0 date_time"
                                                                                            style="">
                                                                                            {{ _formatted_datetime($rev->replied_at) }}
                                                                                        </p>

                                                                                        <p class="text-dark">
                                                                                            {{ $rev->reply }}</p>
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                        </div>


                                                                    </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if (isset($serRun->store_id))
                                                    <!-- Pricing Section -->
                                                    <div class="pricing-section mt-4">
                                                        <div class="pricing-box">
                                                            <span class="pricing-label">Visiting Charges</span>
                                                            <span
                                                                class="pricing-amount">{{ \App\CentralLogics\Helpers::currency_symbol() }}{{ number_format($serRun->quoted_price, 2) }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="cta-buttons-section mt-4">
                                                        <div class="d-flex gap-2 action_outer_{{ $serRun->id }}">
                                                            @include(
                                                                'front-views.partials.dashboard._service-actions-element',
                                                                ['acceptedReq' => $serRun]
                                                            )
                                                        </div>
                                                    </div>

                                                    <!-- Action Buttons Section -->
                                                    <div class="actions-section mt-4">
                                                        <div class="row g-3">

                                                            <!-- Invoice Button -->
                                                            <div class="col-sm-6 col-md-3">
                                                                @if (_getServiceInvoice($serRun->service_request_id))
                                                                    <a href="{{ asset('storage/app/public/invoice') . '/' . _getServiceInvoice($serRun->service_request_id) }}"
                                                                        target="_blank"
                                                                        class="action-button action-primary w-100">
                                                                        <i class="fas fa-receipt"></i>
                                                                        <span>Invoice</span>
                                                                    </a>
                                                                @else
                                                                    <button
                                                                        class="action-button action-primary w-100 action-disabled w-100"
                                                                        disabled>
                                                                        <i class="fas fa-receipt"></i>
                                                                        <span>Invoice</span>
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            <!-- Review Button -->
                                                            <div class="col-sm-6 col-md-3">

                                                                @if (_reviewStatus($serRun->id)['status'] === true)
                                                                    <button type="button"
                                                                        class="action-button action-primary w-100  w-100 service_review_btn"
                                                                        data-id="{{ $serRun->id }}"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#serviceReviewModal">
                                                                        <i class="fas fa-star"></i>
                                                                        <span>Review</span>
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
                            </div>
                        @endforeach
                    @else
                        <p class="text-center my-5"> No booking history...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="gatepassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gatepass</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="gatepass-modal-content">
                Loading...
            </div>
        </div>
    </div>
</div>
<style>
    /* Import Professional Font */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    /* Service History Container */
    .service-history-container {
        font-family: 'Inter', sans-serif;
        padding: 0;
    }

    /* Service Accordion Item */
    .service-accordion-item {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .service-accordion-item:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }

    /* Service Card Header */
    .service-card-header {
        padding: 20px 24px;
        background: #ffffff;
        cursor: pointer;
        transition: background-color 0.2s ease;
        border-bottom: 1px solid #f0f0f0;
    }

    .service-card-header:hover {
        background: #f8f9fa;
    }

    .service-card-header[aria-expanded="true"] {
        background: #f8f9fa;
    }

    .service-card-header[aria-expanded="true"] .collapse-toggle-icon i {
        transform: rotate(180deg);
    }

    /* Action Buttons */
    .cta-buttons-section {
        padding-top: 0;
    }

    .cta-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 16px 20px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        height: 56px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cta-button i {
        font-size: 20px;
    }

    .cta-confirm {
        background: #81c408;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(17, 153, 142, 0.2);
    }

    .cta-confirm:hover {
        transform: translateY(-3px);
    }

    .cta-reject {
        background: #e63333;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(238, 9, 120, 0.14);
    }

    .cta-reject:hover {
        transform: translateY(-3px);
    }

    .actions-section {
        padding-top: 0;
    }

    .action-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 16px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        height: 48px;
    }

    .action-button i {
        font-size: 16px;
    }

    .action-primary {
        background: #81c408;
        color: #ffffff;
    }

    .action-primary:hover {
        transform: translateY(-2px);
        color: #ffffff;
    }

    .action-success {
        background: #81c408;
        color: #ffffff;
    }

    .action-success:hover {
        transform: translateY(-2px);
        color: #ffffff;
    }

    .action-warning {
        background: #ffdf11;
        color: #ffffff;
    }

    .action-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
        color: #ffffff;
    }

    .action-disabled {
        background: #e9ecef;
        color: #adb5bd;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .action-disabled:hover {
        transform: none;
        box-shadow: none;
    }


    /* Service Image */
    .service-img-box {
        flex-shrink: 0;
        width: 64px;
        height: 64px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e9ecef;
    }

    .service-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Service Info */
    .service-info-box {
        flex: 1;
        min-width: 0;
    }

    .service-name {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        margin: 0;
        line-height: 1.4;
    }

    .service-meta-info {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        margin-top: 6px;
    }

    .service-id-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #e3f2fd;
        color: #1976d2;
        font-size: 12px;
        font-weight: 600;
        border-radius: 4px;
    }

    .service-date-text {
        font-size: 13px;
        color: #6c757d;
    }

    .service-date-text i {
        font-size: 12px;
    }

    /* Status Pills */
    .status-pill {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .status-pill-sm {
        padding: 4px 12px;
        font-size: 12px;
    }

    .status-confirmed {
        background: #d4edda;
        color: #155724;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-completed {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    /* Collapse Toggle Icon */
    .collapse-toggle-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .collapse-toggle-icon i {
        font-size: 14px;
        color: #6c757d;
        transition: transform 0.3s ease;
    }

    /* Service Card Body */
    .service-card-body {
        padding: 24px;
        background: #fafbfc;
    }

    /* Detail Card */
    .detail-card {
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        overflow: hidden;
        height: 50%;
            margin: 10px 0;
    }

    .detail-card-header {
        padding: 14px 18px;
        background: #81c408;
        color: #ffffff;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
    }

    .detail-card-header i {
        font-size: 16px;
    }

    .detail-card-content {
        padding: 18px;
    }

    /* Detail Rows */
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .detail-label {
        font-size: 13px;
        color: #6c757d;
        font-weight: 500;
    }

    .detail-value {
        font-size: 14px;
        color: #212529;
        font-weight: 600;
        text-align: right;
    }

    /* Profile Box */
    .profile-box {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .profile-avatar {
        width: 56px;
        height: 56px;
        border-radius: 8px;
        object-fit: cover;
        border: 2px solid #e9ecef;
        flex-shrink: 0;
    }

    .profile-info {
        flex: 1;
        min-width: 0;
    }

    .profile-name {
        font-size: 15px;
        font-weight: 600;
        color: #212529;
        margin: 0 0 4px 0;
    }

    .store-link {
        color: #667eea;
        text-decoration: none;
        transition: color 0.2s;
    }

    .store-link:hover {
        color: #764ba2;
    }

    .profile-designation,
    .profile-address,
    .profile-contact {
        font-size: 13px;
        color: #6c757d;
        margin: 4px 0;
        line-height: 1.5;
    }

    .profile-contact i,
    .profile-address i {
        color: #adb5bd;
        width: 16px;
    }

    /* Pricing Section */
    .pricing-section {
        background: #ffffff;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 18px 24px;
    }

    .pricing-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pricing-label {
        font-size: 15px;
        font-weight: 500;
        color: #495057;
    }

    .pricing-amount {
        font-size: 19px;
        font-weight: 700;
    }

    /* Action Buttons */
    .actions-section {
        padding-top: 0;
    }

    .action-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 16px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        height: 48px;
    }

    .action-button i {
        font-size: 16px;
    }


    .action-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: #ffffff;
    }

    .action-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(17, 153, 142, 0.4);
        color: #ffffff;
    }


    .action-disabled {
        background: #e9ecef;
        color: #adb5bd;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .action-disabled:hover {
        transform: none;
        box-shadow: none;
    }

    /* Modern Modal */
    .modern-modal .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .modern-modal-header {
        padding: 20px 24px;
        background: #81c408;
        border-bottom: none;
        border-radius: 16px 16px 0 0;
    }

    .modal-header-icon {
        font-size: 20px;
        color: #ffffff;
    }

    .modern-modal-header .modal-title {
        color: #ffffff;
        font-weight: 600;
        font-size: 18px;
    }

    .modern-modal-body {
        padding: 24px;
    }

    /* Star Rating */
    .star-rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 8px;
        font-size: 32px;
    }

    .star-rating-input input {
        display: none;
    }

    .star-rating-input label {
        cursor: pointer;
        color: #dee2e6;
        transition: all 0.2s ease;
        margin: 0;
    }

    .star-rating-input label:hover,
    .star-rating-input label:hover~label,
    .star-rating-input input:checked~label {
        color: #ffc107;
        transform: scale(1.1);
    }

    /* Form Elements */
    .form-label {
        font-size: 14px;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .service-card-header {
            padding: 16px;
        }

        .service-card-body {
            padding: 16px;
        }

        .service-img-box {
            width: 56px;
            height: 56px;
        }

        .service-name {
            font-size: 15px;
        }

        .pricing-amount {
            font-size: 20px;
        }

        .action-button {
            padding: 10px 12px;
            font-size: 13px;
            height: auto;
        }
    }

    @media (max-width: 576px) {
        .service-meta-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
    }

    /* Document Styles for Gatepass & Quotation */
    .document-container {
        background: #ffffff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 12px;
    }

    .document-header {
        text-align: center;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }

    .document-header h3 {
        font-size: 24px;
        font-weight: 700;
        color: #212529;
        margin-bottom: 8px;
    }

    .document-id {
        display: inline-block;
        padding: 6px 16px;
        background: #e3f2fd;
        color: #1976d2;
        font-weight: 600;
        border-radius: 20px;
        font-size: 14px;
    }

    .document-body {
        padding: 20px 0;
    }

    .document-info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .document-info-row:last-child {
        border-bottom: none;
    }

    .document-info-label {
        font-weight: 600;
        color: #495057;
        font-size: 14px;
    }

    .document-info-value {
        color: #212529;
        font-size: 14px;
    }

    .document-table {
        width: 100%;
        margin-top: 20px;
    }

    .document-table th {
        background: #f8f9fa;
        padding: 12px;
        font-weight: 600;
        font-size: 13px;
        color: #495057;
        border: 1px solid #dee2e6;
    }

    .document-table td {
        padding: 12px;
        font-size: 14px;
        border: 1px solid #dee2e6;
    }

    .document-total-row {
        background: #f0f9ff !important;
        font-weight: 700;
    }

    .document-alert {
        margin-top: 24px;
        padding: 16px;
        border-radius: 8px;
        display: flex;
        align-items: start;
        gap: 12px;
    }

    .document-alert i {
        font-size: 20px;
        margin-top: 2px;
    }

    .document-alert.alert-info {
        background: #e7f5ff;
        border: 1px solid #74c0fc;
        color: #1864ab;
    }

    .document-alert.alert-success {
        background: #d3f9d8;
        border: 1px solid #51cf66;
        color: #2b8a3e;
    }
</style>


<!-- Quotation Modal -->
<div class="modal fade" id="quotationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-file-invoice modal-header-icon"></i>
                    <h5 class="modal-title mb-0">Service Quotation</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body modern-modal-body">
                <div id="quotation-modal-content">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading quotation details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
