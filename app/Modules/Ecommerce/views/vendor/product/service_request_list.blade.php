@extends('layouts.vendor.app')

@section('title', $heading ?? 'Leads')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('45bfc10c466a1d72f474', {
            cluster: 'ap2'
        });

        var channel = pusher.subscribe('my-channel');
        channel.bind('my-event', function(data) {
            alert(JSON.stringify(data));
        });
    </script>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header --> 
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>{{$heading ?? 'Leads'}} <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($product) }}</span></h1>
            <div class="page-header-select-wrapper">


            </div>
        </div>
        <!-- End Page Header -->



        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{$heading ?? 'Leads'}}</h5>

                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                    data-hs-datatables-options='{
                        "order": [],
                        "orderCellsTop": true,
                        "paging":false

                    }'>
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">Service</th>
                            <th class="border-0">User</th>
                            <th class="border-0">Category</th>
                            <th class="border-0">Requested At</th>
                            <th class="text-uppercase border-0">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($product as $lead)
                            @if (_getCurrentServiceStatus($lead->id) == null || _getCurrentServiceStatus($lead->id) == 'Confirmation Request Sent')
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        <a class="media align-items-center" href="javascript:;" style="cursor:default;">
                                            <img class="avatar avatar-lg mr-3 onerror-image"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($lead->image, asset('storage/app/public/product/') . '/' . $lead->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'product/') }}"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                alt="{{ $lead->item_name }} image">
                                            <div class="media-body">
                                                <h5 class="text-hover-primary mb-0">
                                                    {{ Str::limit($lead->item_name, 20, '...') }}</h5>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">

                                            {{ $lead->f_name }}

                                        </span>

                                    </td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ $lead->category_name }}
                                        </span>

                                    </td>
                                    <td>
                                        <div>
                                            {{ $lead->created_at }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                                <button type="button" data-toggle="modal"
                                                    data-target="#exampleModalDet{{ $lead->id }}"
                                                    class="btn action-btn btn--warning btn-outline-warning"
                                                    title="View"><i class="tio-visible-outlined"></i>
                                                </button>
                                           
                                            <div class="modal fade" id="exampleModalDet{{ $lead->id }}" tabindex="-1"
                                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Details</h1>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <table class="table ">
                                                                <tr>
                                                                    <th>Requirements</th>
                                                                    <td>{{ $lead->requirements }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Qty</th>
                                                                    <td>{{ $lead->qty }}</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @if (_acceptedReq($lead->id))
                                                    <a style="min-width:50px;" class="btn  btn--primary btn-outline-primary"
                                                        href="#" data-toggle="modal"
                                                        data-target="#exampleModal33-{{ $lead->id }}"
                                                        title="Contact Details">
                                                        <i class="tio-visible-outlined"></i>
                                                    </a>
                                             

                                                @php
                                                    $user_details = _getUserDetails($lead->uid);

                                                @endphp
                                                @if ($user_details)
                                                    <!--modal -->
                                                    <div class="modal fade" id="exampleModal33-{{ $lead->id }}"
                                                        tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="exampleModalLabel">Requested
                                                                        for : {{ $lead->item_name }} </h5>
                                                                    <button type="button" class="close"
                                                                        data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>

                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="form-group">

                                                                       

                                                                        <ul class="list-unstyled">

                                                                            <li>
                                                                                <strong>Name:</strong>
                                                                                <span>{{ $user_details->f_name . ' ' . $user_details->l_name }}</span>
                                                                            </li>

                                                                            <li>
                                                                                <strong>Email:</strong>
                                                                                <a
                                                                                    href="mailto:{{ $user_details->email }}">{{ $user_details->email }}</a>
                                                                            </li>

                                                                            <li>
                                                                                <strong>Mobile:</strong>
                                                                                
                                                                                     <a href="javascript:;" style="cursor:default;"
                                                                    class="textToCopy">{{ $user_details->phone }}</a>
                                                                <button
                                                                    class="copy-btn bg-transparent outline-none border-0">
                                                                    <i class="tio-copy"></i>
                                                                </button>

                                                                            </li>

                                                                            <li>
                                                                                @if (!_getCurrentServiceStatus($lead->id))
                                                                                    <form
                                                                                        action="{{ route('vendor.service.send-confirmation-notification', ['id' => $lead->id]) }}">
                                                                                        @csrf
                                                                                        <input type="hidden"
                                                                                            name="id" 
                                                                                            value="{{ $lead->id }}">
                                                                                        <label for="lead_price"
                                                                                            class="form-label">Visiting
                                                                                            Charges</label>
                                                                                        <input type="number"
                                                                                            name="price" id="lead_price"
                                                                                            class="form-control mb-1"
                                                                                            placeholder="Visiting Charges">
                                                                                        <button type="submit"
                                                                                            class="btn btn--primary">Send
                                                                                            Confirmation Request</button>
                                                                                    </form>
                                                                                @else
                                                                                    <h4 class="text--primary">
                                                                                        {{ _getCurrentServiceStatus($lead->id) }}
                                                                                    </h4>
                                                                                @endif

                                                                                @if (_getCurrentServiceStatus($lead->id) == 'Confirmed')
                                                                                    <a href="{{ route('vendor.service.cancel', [$lead->id]) }}"
                                                                                        class="btn btn-outline-danger">Cancel</a>
                                                                                @endif
                                                                            </li>

                                                                        </ul>

                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button id="reset_btn" type="reset"
                                                                        data-dismiss="modal"
                                                                        class="btn btn-secondary">{{ translate('Close') }}
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                <!--modal end -->
                                            @else
                                                    <a style="min-width:50px;"
                                                        class="btn  btn--primary btn-outline-primary"
                                                        href="{{ route('vendor.service.accept', [$lead->id]) }}"title="Accept Request">Accept
                                                    </a>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @if (count($product))
                    <hr>
                @else
                    <div class="page-area">
                    </div>
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>

            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('45bfc10c466a1d72f474', {
            cluster: 'ap2'
        });

        var channel = pusher.subscribe('my-channel');
        channel.bind('my-event', function(data) {
            alert(JSON.stringify(data));
        });
    </script>
   
    <script>
        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

          $(document).ready(function () {
    $(".copy-btn").on("click", function () {
        // Get the previous <p> or span element text
        var text = $(this).prev(".textToCopy").text().trim();
        console.log(text); // Debugging

        if (navigator.clipboard && window.isSecureContext) {
            // Modern way to copy
            navigator.clipboard.writeText(text).then(() => {
                console.log("Copied successfully!");
            }).catch(err => {
                console.error("Clipboard copy failed", err);
            });
        } else {
            // Fallback for older browsers
            var tempInput = $("<textarea>"); // Use textarea instead of input
            $("body").append(tempInput);
            tempInput.val(text).css({
                position: "absolute",
                left: "-9999px", // Hide offscreen
            }).select();
            document.execCommand("copy");
            tempInput.remove();
        }
        $(this).html("Copied!");
        setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
    });
});
    </script>
@endpush
