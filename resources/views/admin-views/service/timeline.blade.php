@extends('layouts.admin.app')

@section('title', 'Lead Details')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <style>
        .list_div {}

        .leads_table {
            box-shadow: 0px 0px 5px #cacaca;
        }

        .list_div:last-child {
            border-right: none;
        }

        /*timeline desing */
        * {
            border: 0;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .timeline_elem a {
            color: var(--primary);
            transition: color var(--trans-dur);
        }

        /*body,*/
        .timeline_elem button {
            color: var(--fg);
            font: 1em/1.5 "IBM Plex Sans", sans-serif;
        }

        .timeline_elem h1 {
            font-size: 2em;
            margin: 0 0 3rem;
            padding-top: 1.5rem;
            text-align: center;
        }

        .timeline_elem .btn {
            background-color: var(--fg);
            border-radius: 0.25em;
            color: var(--bg);
            cursor: pointer;
            padding: 0.375em 0.75em;
            transition:
                background-color calc(var(--trans-dur) / 2) linear,
                color var(--trans-dur);
            -webkit-tap-highlight-color: transparent;
        }

        .timeline_elem .btn:hover {
            background-color: hsl(var(--hue), 10%, 50%);
        }

        .timeline_elem .btn-group {
            display: flex;
            gap: 0.375em;
            margin-bottom: 1.5em;
        }

        .timeline_elem .timeline {
            margin: auto;
            padding: 0 1.5em;
            width: 100%;
            max-width: 36em;
        }

        .timeline_elem .timeline__arrow {
            background-color: transparent;
            border-radius: 0.25em;
            cursor: pointer;
            flex-shrink: 0;
            margin-inline-end: 0.25em;
            outline: transparent;
            width: 2em;
            height: 2em;
            transition:
                background-color calc(var(--trans-dur) / 2) linear,
                color var(--trans-dur);
            -webkit-appearance: none;
            appearance: none;
            -webkit-tap-highlight-color: transparent;
        }

        .timeline_elem .timeline__arrow:focus-visible,
        .timeline_elem .timeline__arrow:hover {
            background-color: hsl(var(--hue), 10%, 50%, 0.4);
        }

        .timeline_elem .timeline__arrow-icon {
            display: block;
            pointer-events: none;
            transform: rotate(-90deg);
            transition: transform var(--trans-dur) var(--trans-timing);
            width: 100%;
            height: auto;
        }

        .timeline_elem .timeline__date {
            font-size: 0.833em;
            line-height: 2.4;
        }

        .timeline_elem .timeline__dot {
            background-color: currentColor;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
            margin: 0.625em 0;
            margin-inline-end: 1em;
            position: relative;
            width: 0.75em;
            height: 0.75em;
        }

        .timeline_elem .timeline__item {
            position: relative;
            padding-bottom: 2.25em;
        }

        .timeline_elem .timeline__item:not(:last-child):before {
            background-color: currentColor;
            content: "";
            display: block;
            position: absolute;
            top: 1em;
            left: 2.625em;
            width: 0.125em;
            height: 100%;
            transform: translateX(-50%);
        }

        .timeline_elem [dir="rtl"] .timeline__arrow-icon {
            transform: rotate(90deg);
        }

        .timeline_elem [dir="rtl"] .timeline__item:not(:last-child):before {
            right: 2.625em;
            left: auto;
            transform: translateX(50%);
        }

        .timeline_elem .timeline__item-header {
            display: flex;
        }

        .timeline_elem .timeline__item-body {
            border-radius: 0.375em;
            overflow: hidden;
            margin-top: 0.5em;
            margin-inline-start: 4em;
            height: 0;
        }

        .timeline_elem .timeline__item-body-content {
            background-color: hsl(var(--hue), 10%, 50%, 0.2);
            opacity: 0;
            padding: 0.5em 0.75em;
            visibility: hidden;
            transition:
                opacity var(--trans-dur) var(--trans-timing),
                visibility var(--trans-dur) steps(1, end);
        }

        .timeline_elem .timeline__meta {
            width: 100%;
        }

        .timeline_elem .timeline__title {
            font-size: 1.5em;
            line-height: 1.333;
        }

        /* Expanded state */
        .timeline_elem .timeline__item-body--expanded {
            height: auto;
        }

        .timeline_elem .timeline__item-body--expanded .timeline__item-body-content {
            opacity: 1;
            visibility: visible;
            transition-delay: var(--trans-dur), 0s;
        }

        .timeline_elem .timeline__arrow[aria-expanded="true"] .timeline__arrow-icon {
            transform: rotate(0);
        }
    </style>

    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const ctl = new CollapsibleTimeline("#timeline");
        });

        class CollapsibleTimeline {
            constructor(el) {
                this.el = document.querySelector(el);

                this.init();
            }
            init() {
                this.el?.addEventListener("click", this.itemAction.bind(this));
            }
            animateItemAction(button, ctrld, contentHeight, shouldCollapse) {
                const expandedClass = "timeline__item-body--expanded";
                const animOptions = {
                    duration: 300,
                    easing: "cubic-bezier(0.65,0,0.35,1)"
                };

                if (shouldCollapse) {
                    button.ariaExpanded = "false";
                    ctrld.ariaHidden = "true";
                    ctrld.classList.remove(expandedClass);
                    animOptions.duration *= 2;
                    this.animation = ctrld.animate([{
                            height: `${contentHeight}px`
                        },
                        {
                            height: `${contentHeight}px`
                        },
                        {
                            height: "0px"
                        }
                    ], animOptions);
                } else {
                    button.ariaExpanded = "true";
                    ctrld.ariaHidden = "false";
                    ctrld.classList.add(expandedClass);
                    this.animation = ctrld.animate([{
                            height: "0px"
                        },
                        {
                            height: `${contentHeight}px`
                        }
                    ], animOptions);
                }
            }
            itemAction(e) {
                const {
                    target
                } = e;
                const action = target?.getAttribute("data-action");
                const item = target?.getAttribute("data-item");

                if (action) {
                    const targetExpanded = action === "expand" ? "false" : "true";
                    const buttons = Array.from(this.el?.querySelectorAll(`[aria-expanded="${targetExpanded}"]`));
                    const wasExpanded = action === "collapse";

                    for (let button of buttons) {
                        const buttonID = button.getAttribute("data-item");
                        const ctrld = this.el?.querySelector(`#item${buttonID}-ctrld`);
                        const contentHeight = ctrld.firstElementChild?.offsetHeight;

                        this.animateItemAction(button, ctrld, contentHeight, wasExpanded);
                    }

                } else if (item) {
                    const button = this.el?.querySelector(`[data-item="${item}"]`);
                    const expanded = button?.getAttribute("aria-expanded");

                    if (!expanded) return;

                    const wasExpanded = expanded === "true";
                    const ctrld = this.el?.querySelector(`#item${item}-ctrld`);
                    const contentHeight = ctrld.firstElementChild?.offsetHeight;

                    this.animateItemAction(button, ctrld, contentHeight, wasExpanded);
                }
            }
        }
    </script>
@endpush

@section('content')
    <div class="content container-fluid">


        <!-- Resturent Card Wrapper -->
        <div class="row">
            <div class="col-md-7">
                <h4 class="resturant-card" style="background-color:#f0f0f0">{{ $reqDetails->name }}</h4>
            </div>
            <div class="col-md-5" style=" overflow: auto;">

                @php $custDet = _customerDetByserviceId($reqDetails->id); @endphp
                <div class="col-12 mb-1">

                    <div class="resturant-card card--bg-3 position-relative " data-toggle="collapse" href="#collapseExample"
                        role="button" aria-expanded="false" aria-controls="collapseExample">
                        <div class="d-flex justify-content-between">
                            <h4 class="mb-0">Customer Details</h4> <i class="fa fa-chevron-down"
                                style="font-size: 20px;"></i>
                        </div>

                        <div class="collapse" id="collapseExample">
                            <div class="card card-body">
                                <div class="mb-2">
                                    <h5 class="title" style="font-size:1.1rem; display:inline;">
                                        {{ $custDet->f_name . ' ' . $custDet->l_name }}</h5>
                                </div>
                                <div class="subtitle mb-1">{{ $custDet->email }}</div>
                                <div class="subtitle">{{ $custDet->phone }}</div>
                                <div class=""><i>Customer since: {{ _monthNYear($custDet->created_at) }}</i></div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <!-- Resturent Card Wrapper -->
        <div class="card">
            <!-- Header -->
            <div class="card-header row py-2 align-items-start">
                <div class="timeline_elem col-md-7">
                    <svg display="none">
                        <symbol id="arrow">
                            <polyline points="7 10,12 15,17 10" fill="none" stroke="currentcolor" stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2" />
                        </symbol>
                    </svg>
                    <!--<h1>A Brief History of Unix Time</h1>-->
                    <div id="timeline" class="timeline">
                        @foreach ($timeline as $key => $tl)
                            <div class="timeline__item">
                                <div class="timeline__item-header">

                                    <button
                                        {{ !in_array($tl->status, ['User Request Service', 'Quotation Created', 'Gatepass Created']) ? 'style=visibility:hidden' : '' }}
                                        class="timeline__arrow" type="button" id="item{{ $key }}"
                                        aria-labelledby="item{{ $key }}-name" aria-expanded="false"
                                        aria-controls="item{{ $key }}-ctrld" aria-haspopup="true"
                                        data-item="{{ $key }}">
                                        <svg class="timeline__arrow-icon" viewBox="0 0 24 24" width="24px" height="24px">
                                            <use href="#arrow" />
                                        </svg>
                                    </button>
                                    <span class="timeline__dot"></span>
                                    <span id="item{{ $key }}-name" class="timeline__meta">
                                        <time class="timeline__date"
                                            datetime="1970-01-01">{{ _formatted_datetime($tl->created_at) }}</time><br>
                                        <strong class="timeline__title">{{ $tl->status }}</strong>
                                    </span>
                                </div>
                                <div class="timeline__item-body" id="item{{ $key }}-ctrld" role="region"
                                    aria-labelledby="item{{ $key }}" aria-hidden="true">
                                    <div class="timeline__item-body-content">
                                        @if ($tl->status == 'User Request Service')
                                            <p class="timeline__item-p">Requested for <b> {{ $reqDetails->name }}</b>.</p>
                                        @elseif($tl->status == 'Quotation Created')
                                            <p class="timeline__item-p">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Price</th>
                                                        <th scope="col">Tax</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($quotationItems as $key => $item)
                                                        <tr>
                                                            <td>{{ $key + 1 }}</td>
                                                            <td>{{ $item->name }}</td>
                                                            <td> {{ \App\CentralLogics\Helpers::currency_symbol() . $item->price }}
                                                            </td>
                                                            <td>{{ $item->tax . '% tax' }}</td>
                                                        </tr>
                                                    @endforeach

                                                </tbody>
                                            </table>
                                            </p>
                                        @elseif($tl->status == 'Gatepass Created')
                                            <p class="timeline__item-p">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Title</th>
                                                        <th scope="col">Description</th>
                                                        <th scope="col">Images</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($gpItems as $key => $item)
                                                        <tr>
                                                            <td>{{ $key + 1 }}</td>
                                                            <td>{{ $item->title }}</td>
                                                            <td> {{ $item->description }}</td>
                                                            <td>
                                                                @if (is_array(json_decode($item->image)))
                                                                    <div class="d-flex lightgallery">
                                                                        @foreach (json_decode($item->image) as $key => $value)
                                                                            <a target="_blank"
                                                                                href="{{ asset('storage/app/public/gatepass') }}/{{ $value }}"
                                                                                style="cursor:default;"
                                                                                class="table-rest-info"
                                                                                alt="Gatepass image">
                                                                                <img style="width: 30px; height: 30px; cursor:zoom-in;"
                                                                                    onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                                                    src="{{ asset('storage/app/public/gatepass') }}/{{ $value }}">
                                                                            </a>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="lightgallery">
                                                                        <a target="_blank"
                                                                            href="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}"
                                                                            style="cursor:default;" class="table-rest-info"
                                                                            alt="Gatepass image">
                                                                            <img style="width: 80px; height: 80px; cursor:zoom-in;"
                                                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                                                src="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}">
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                </tbody>
                                            </table>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                </div>
                @if ($reqDetails->requirements)
                    <div class="col-md-5" style=" height: 90vh; overflow: auto;">

                        <h3>Additional Requirements</h3>
                        <div class="col-12 mb-1 p-0">
                            <div class="resturant-card card--bg-3 position-relative">
                                {{ $reqDetails->requirements }}
                            </div>
                        </div>

                    </div>
                @endif
                <!-- End Header -->

                <!-- Table -->
                <!-- End Table -->
            </div>
        </div>


    </div>
    <!-- End Card -->
    </div>


@endsection

@push('script_2')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}">
    </script>
    <script>
        $(document).ready(function() {
            // Latitude and Longitude
            var lat = {{ $custDet->latitude }}; // Replace with your latitude
            var lng = {{ $custDet->longitude }}; // Replace with your longitude

            // Create a map object and specify the DOM element for display.
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat: lat,
                    lng: lng
                },
                zoom: 10
            });

            // Create a marker and set its position.
            var marker = new google.maps.Marker({
                map: map,
                position: {
                    lat: lat,
                    lng: lng
                },
                title: 'Your Location'
            });

            var geocoder = new google.maps.Geocoder();

            // Reverse Geocoding to get the place name
            geocoder.geocode({
                'location': {
                    lat: lat,
                    lng: lng
                }
            }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        $('#addressElem').text(results[0].formatted_address);
                        $('#searchInput').text(results[0].formatted_address);
                    } else {
                        $('#addressElem').text('');
                        $('#searchInput').text('');
                    }
                } else {
                    $('#name').text('');
                }
            });

        });

        $(document).on('click', '.submit_btn', function() {
            var $itemRows = $('.item_row');
            if ($itemRows.length == 0) {
                toastr.error('Please add atleast one item');
            } else {
                $('#quote_form').submit();
            }
        })

        function deleteRow(rowId) {
            $('.row_' + rowId).remove()
        }

        function addMoreRow() {

            var $lastItemRow = $('.item_row').last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;

            }

            var html = `<tr class="item_row row_` + dataId + `" data-id="` + dataId + `">
                    <input type="hidden" name = "new_item[]" value = '' >  
                      <td><input type="text" name="title[]" placeholder="Title" class="form-control"></td>
                      <td><input type="file" name="image[]" required class="form-control"></td>
                      <td><button type="button" onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>
                    <tr class=" row_` + dataId + `" >
                        <td colspan="2"><textarea type="number" name="desc[]" placeholder="Description" class="form-control"></textarea></td>
                    </tr>`;

            $('.rows_parent').append(html)
        }

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
    </script>

    <script>
        $('#search-form').on('submit', function() {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.store.search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.total);
                    $('.page-area').hide();
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
