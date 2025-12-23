@extends('front-views.layout')

@section('title', $user_details->f_name . ' ' . $user_details->l_name)

@section('seo')

@endsection

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
    .btn-grey{
        background-color: #b8b8b8;
    }
    </style>
@endpush

@section('content')
    <div class="spacer"></div>

    <div class="container  mt-5">
        <div class=" dash_div d-flex align-items-start shadow rounded ">
            <div class=" left_nav nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <img class="profile_img"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($user_details->image, asset('storage/app/public/profile/') . '/' . $user_details->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                    alt="profile">
                <button class="nav-link active" id="v-pills-profile-tab" data-bs-toggle="pill"
                    data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile"
                    aria-selected="false">Profile</button>
                <button class="nav-link " id="v-pills-home-tab" data-bs-toggle="pill" data-bs-target="#v-pills-home"
                    type="button" role="tab" aria-controls="v-pills-home" aria-selected="true">Address</button>
                @if (Config::get('module.current_module_id') == 5)
                    <button class="nav-link " id="v-pills-order-tab" data-bs-toggle="pill" data-bs-target="#v-pills-order"
                        type="button" role="tab" aria-controls="v-pills-order" aria-selected="true">Orders</button>

                    <button class="nav-link " id="v-pills-service-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-service" type="button" role="tab" aria-controls="v-pills-service"
                        aria-selected="true">Services</button>
                @endif
                <button class="nav-link" id="v-pills-coupons-tab" data-bs-toggle="pill" data-bs-target="#v-pills-coupons"
                    type="button" role="tab" aria-controls="v-pills-coupons" aria-selected="false">Coupons</button>
                <button class="nav-link" id="v-pills-messages-tab" data-bs-toggle="pill" data-bs-target="#v-pills-messages"
                    type="button" role="tab" aria-controls="v-pills-messages" aria-selected="false">Favourite</button>
                <button class="nav-link" type="button" data-bs-toggle="modal"
                    data-bs-target="#exampleModalLogout">Logout</button>
            </div>
            <div class="tab-content " id="v-pills-tabContent" style="width:100% ;min-height: 300px;">
            
                @include('front-views.partials.dashboard._profile-tab')
                @include('front-views.partials.dashboard._address-tab')
                @include('front-views.partials.dashboard._orders-tab')
                @include('front-views.partials.dashboard._service-tab')
                @include('front-views.partials.dashboard._coupons-tab')
                @include('front-views.partials.dashboard._favourites-tab')
            </div>
        </div>
    </div>

    <!-- Button trigger modal -->


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

@endsection

@push('script_2')
    <script>
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
