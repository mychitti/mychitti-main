@extends('front-views.layout')

@section('title', $user_details->f_name . ' ' . $user_details->l_name)

@section('seo')

@endsection

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .btn-grey {
            background-color: #b8b8b8;
        }

        .spacer {
            height: 70px;
        }
 
        .coupon-card {
            position: relative;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.2s;
        }

        .coupon-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .coupon-top {
            background: linear-gradient(135deg, #81c408 0%, #a6d54eff 100%);
            padding: 16px;
            position: relative;
        }

        .coupon-top::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            right: 0;
            height: 12px;
            background: radial-gradient(circle at 6px, transparent 6px, white 6px);
            background-size: 12px 12px;
            background-position: 0 0;
        }

        .coupon-name {
            color: white;
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .coupon-code-box {
            background: white;
            color: #81c408;
            padding: 6px 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 16px;
            font-weight: 700;
            display: inline-block;
            letter-spacing: 1px;
        }

        .coupon-bottom {
            padding: 16px;
        }

        .coupon-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            font-size: 13px;
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 600;
        }

        .discount-value {
            font-size: 20px;
            color: #81c408;
        }

        .coupon-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #393e46ff;
            padding-top: 12px;
            border-top: 1px dashed #e5e7eb;
        }
    </style>
@endpush

@section('content')
    <div class="spacer"></div>

    <div class="container">
        <div class=" dash_div d-flex align-items-start shadow rounded ">
            <div class=" left_nav nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <img class="profile_img"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($user_details->image, asset('storage/app/public/profile/') . '/' . $user_details->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                    alt="profile">
                <a href="{{ route('dashboard', ['profile']) }}"
                    class=" text-center nav-link  {{ Request::is('dashboard/profile') || Request::is('dashboard') ? 'active' : '' }}">Profile</a>
                <a href="{{ route('dashboard', ['address']) }}"
                    class=" text-center nav-link  {{ Request::is('dashboard/address') ? 'active' : '' }}">Address</a>
                <a href="{{ route('dashboard', ['bookings']) }}"
                    class=" text-center nav-link  {{ Request::is('dashboard/bookings') ? 'active' : '' }}">Bookings</a>
                <a href="{{ route('dashboard', ['coupons']) }}"
                    class=" text-center nav-link  {{ Request::is('dashboard/coupons') ? 'active' : '' }}">Coupons</a>
                <a href="{{ route('dashboard', ['favourites']) }}"
                    class=" text-center nav-link  {{ Request::is('dashboard/favourites') ? 'active' : '' }}">Favourites</a>
                <button class="nav-link" type="button" data-bs-toggle="modal"
                    data-bs-target="#exampleModalLogout">Logout</button>

            </div>
            <div class="tab-content " id="v-pills-tabContent" style="width:100% ;min-height: 300px;">
                @if (request()->tab == 'profile' || Request::is('dashboard'))
                    @include('front-views.partials.dashboard._profile-tab')
                @elseif(request()->tab == 'address')
                    @include('front-views.partials.dashboard._address-tab')
                @elseif(request()->tab == 'favourites')
                    @include('front-views.partials.dashboard._favourites-tab')
                @elseif(request()->tab == 'bookings')
                    @include('front-views.partials.dashboard._service-tab')
                @elseif(request()->tab == 'coupons')
                    @include('front-views.partials.dashboard._coupons-tab')
                @endif
                {{-- @include('front-views.partials.dashboard._orders-tab') --}}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="exampleModalLogout" tabindex="-1" aria-labelledby="exampleModalLogoutLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLogoutLabel">Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="f-4">Are you sure you want to logout?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ route('user.logout') }}" class="btn btn-primary">Yes</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="item_det"></div>
                    <form action="{{ route('submit-review') }}" class="reviewformSubmit">
                        <div class="star-rating">
                            <input type="hidden" name="order_item_id" id="order_item_id">

                            <input type="radio" id="star5" name="rating" value="5" />
                            <label for="star5" title="5 stars">★</label>

                            <input type="radio" id="star4" name="rating" value="4" />
                            <label for="star4" title="4 stars">★</label>

                            <input type="radio" id="star3" name="rating" value="3" />
                            <label for="star3" title="3 stars">★</label>

                            <input type="radio" id="star2" name="rating" value="2" />
                            <label for="star2" title="2 stars">★</label>

                            <input type="radio" id="star1" name="rating" value="1" />
                            <label for="star1" title="1 star">★</label>

                            <p style="font-size: 18px;line-height: 33px">: *Rating </p>
                        </div>

                        <label for="revie" title="">Review*</label>
                        <textarea name="review" class="form-control mb-2" id="revie"placeholder="Start typing..."></textarea>

                        <label for="revie" title="">Image (optionl)</label>
                        <input type="file" name="attachment[]" class="form-control mb-2">

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="serviceReviewModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="service_det"></div>
                    <form action="{{ route('submit-service-review') }}" class="reviewformSubmit">
                        <div class="star-rating">
                            <input type="hidden" name="service_id" id="service_id_inp">

                            <input type="radio" id="2star5" name="rating" value="5" />
                            <label for="2star5" title="5 stars">★</label>

                            <input type="radio" id="2star4" name="rating" value="4" />
                            <label for="2star4" title="4 stars">★</label>

                            <input type="radio" id="2star3" name="rating" value="3" />
                            <label for="2star3" title="3 stars">★</label>

                            <input type="radio" id="2star2" name="rating" value="2" />
                            <label for="2star2" title="2 stars">★</label>

                            <input type="radio" id="2star1" name="rating" value="1" />
                            <label for="2star1" title="1 star">★</label>

                            <p style="font-size: 18px;line-height: 33px">: *Rating </p>
                        </div>

                        <label for="revie" title="">Review*</label>
                        <textarea name="review" class="form-control mb-2" id="revie"placeholder="Start typing..."></textarea>

                        <label for="revie" title="">Image (optionl)</label>
                        <input type="file" name="attachment[]" class="form-control mb-2">

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $(document).on('click', '.service_review_btn', function() {
            var service_id = $(this).data('id')
            $('#service_det').html($('.service_info_' + service_id).html());
            $('#service_id_inp').val(service_id);
        })
        $(document).on("click", ".updateStatus", function(e) {

            e.preventDefault();

            let data = {};
            let action = $(this).data('action');
            let gatepass_id = $(this).data('gatepass_id');
            let service_id = $(this).data('service_id');
            let acceptance_id = $(this).data('acceptance_id');

            if (action == 'confirm_service') {

                data = {
                    acceptance_id: acceptance_id,
                    service_id: service_id
                };

            } else if (action == 'gatepass_approval') {

                data = {
                    gatepass_id: gatepass_id,
                    action: $(this).data('action1')
                };
            } else if (action == 'gatepass_return_approval') {
                data = {
                    gatepass_id: gatepass_id,
                };
            } else if (action == 'quotation_approval') {
                data = {
                    quote_id: $(this).data('quotation_id'),
                    action: $(this).data('action1')
                };
            }
            console.log(action)

            let url = $(this).data('url');

            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                }
            });

            $.post({
                url: url,
                data: data,
                success: function(res) {

                    if (res.errors && res.errors.length > 0) {

                        toasterNotification(res.errors[0].message);

                    } else {
                        toasterNotification(res.message);

                        if (action == 'confirm_service') {
                            $(".action_outer_" + acceptance_id).html(res.html);
                        } else if (action == 'gatepass_approval' || action ==
                            'gatepass_return_approval') {
                            $('.gatepass-cta_' + gatepass_id).html(res.html);
                        } else if (action == 'quotation_approval') {
                            $('.quotation-cta_' + res.quotation_id).html(res.html);
                        } else {
                            console.log('no action')
                        }
                    }
                },
                error: function() {
                    toasterNotification('Something went wrong');
                }
            });

        });

        // Gatepass Modal Handler
        $(document).on('click', '.gatepass-modal-btn', function() {

            var serviceId = $(this).data('id');
            var modalContent = $('#gatepass-modal-content');

            // loader
            modalContent.html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading gatepass details...</p>
                </div>
            `);

            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: '{{ route('service.gatepass.details') }}', // change to your route
                type: 'GET',
                data: {
                    service_id: serviceId
                },
                success: function(response) {

                    // response should be HTML
                    modalContent.html(response.html);

                },
                error: function() {

                    modalContent.html(`
                <div class="text-center text-danger py-4">
                    Failed to load gatepass details.
                </div>
            `);

                }
            });

        });
        // Quotation Modal Handler
        $(document).on('click', '.quotation-modal-btn', function() {

            var serviceId = $(this).data('id');
            var modalContent = $('#quotation-modal-content');

            // loader
            modalContent.html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading quotation details...</p>
                </div>
            `);

            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: '{{ route('service.quotation.details') }}', // change to your route
                type: 'GET',
                data: {
                    service_id: serviceId
                },
                success: function(response) {

                    // response should be HTML
                    modalContent.html(response.html);

                },
                error: function() {

                    modalContent.html(`
                <div class="text-center text-danger py-4">
                    Failed to load quotation details.
                </div>
            `);

                }
            });

        });
        $(".reviewformSubmit").on("submit", function(e) {
            e.preventDefault();

            var formData = new FormData($(this)[0]);
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: $(this).attr("action"),
                processData: false,
                contentType: false,
                async: false,
                cache: false,
                data: formData,
                beforeSend: function() {},
                success: function(data) {
                    if (data.errors && data.errors.length > 0) {
                        toasterNotification(data.errors[0].message);
                    } else {
                        toasterNotification(data.message);
                        $('.btn-close').click()
                        $(".reviewformSubmit").trigger('reset')
                    }
                },
            });
        });
    </script>

    <script>
        $('#deleteAccount').on('click', function() {
            Swal.fire({
                title: "This action can't be undone",
                text: "Are you sure? You wan't to delete your account",
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.get({
                        url: "{{ route('delete-account', ['id' => $user_details->id]) }}",
                        data: {
                            id: {{ $user_details->id }}
                        },
                        success: function(data) {
                            if (data.errors) {
                                msg = data.errors[0].message;
                                toasterNotification(msg);
                            } else {
                                // console.log(data)
                                window.location.href = "{{ route('user-login') }}"

                            }
                        }
                    });
                }
            })
        })
        $('.reviewModalBtn').on('click', function() {
            var order_item_id = $(this).attr('data-id')
            $('#item_det').html($('.item_info_' + order_item_id).html());
            $('#order_item_id').val(order_item_id);
        })
        $('.serviceReviewModalBtn').on('click', function() {
            var service_id = $(this).attr('data-id')
            $('#service_det').html($('.service_info_' + service_id).html());
            $('#service_id').val(service_id);
        })

        function removeWishlist(type, id) {

            var url = "{{ route('remove-from-wishlist', ['type' => 'TYPE', 'id' => 'ID']) }}";
            var url = url.replace('TYPE', type).replace('ID', id);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: url,
                data: {
                    type: type
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    toasterNotification(data.message)
                    $('#nav-stores').load(window.location.href + ' #wishTabInner')
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
        }

        $(document).on('click', '.gatepass-modal', function() {
            var itemId = $(this).data('id');

            $('#gatepassModal').modal('show');

            $.ajax({
                url: "gatepass-details/" + itemId,
                method: 'GET',
                success: function(response) {
                    $('#gatepass-modal-content').html(response);
                },
                error: function() {
                    $('#gatepass-modal-content').html('<p>Error loading data</p>');
                }
            });
        });
        $(document).on('click', '.quotation-modal', function() {
            var itemId = $(this).data('id');

            $('#quotationModal').modal('show');

            $.ajax({
                url: "quotation-details/" + itemId,
                method: 'GET',
                success: function(response) {
                    $('#quotation-modal-content').html(response);
                },
                error: function() {
                    $('#quotation-modal-content').html('<p>Error loading data</p>');
                }
            });
        });
    </script>
@endpush
