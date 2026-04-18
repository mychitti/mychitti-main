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
    @if($reqDetails)
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
                                <div class="subtitle mb-1">
                                    {{ $reqDetails->address }}
                                    @if($custDet->latitude && $custDet->longitude)
                                        <a href="https://www.google.com/maps?q={{ $custDet->latitude }},{{ $custDet->longitude }}"
                                           target="_blank"
                                           class="text-primary ml-1"
                                           title="Show on Map">
                                            <i class="tio-map-outlined"></i> Navigate
                                        </a>
                                    @endif
                                </div>
                                <div class=" mb-1">{{ $custDet->email }}</div>
                                <div class="">{{ $custDet->phone }}</div>
                                <div class=""><i>Customer since: {{ _monthNYear($custDet->created_at) }}</i></div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <!-- Resturent Card Wrapper -->


        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 row align-items-start">


                <div class="row col-12  p-0">
                    <div class="col-6  list_div">
                        <h4>Recieved Vendors</h4>
                        <div class="leads_table table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                                    "order": [],
                                    "orderCellsTop": true,
                                    "paging":false}'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Vendor </th>
                                        <th class="border-0">Recieved At </th>
                                    </tr>
                                </thead>
                                <tbody id="set-rows">
                                    @foreach ($recievedLeadVendors as $key => $vendor)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <a class="d-flex align-items-center"
                                                    href="{{ route('admin.store.view', $vendor->store_id) }}">
                                                    <img class="img--40 mx-1 rounded onerror-image"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $vendor->logo ?? '',
                                                            asset('storage/app/public/store') . '/' . $vendor->logo ?? '',
                                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                                            'store/',
                                                        ) }}"
                                                        alt="Image Description">
                                                    <div class="text-center">
                                                        <h5
                                                            class="text-capitalize text--title font-semibold text-hover-primary d-block mb-1">
                                                            {{ $vendor->name }}
                                                        </h5>

                                                    </div>
                                                </a>

                                            </td>
                                            <td>
                                                {{ $vendor->created_at }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (count($recievedLeadVendors))
                                <hr>
                            @else
                                <div class="page-area">
                                </div>
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 list_div">
                        <h4>Accepted Vendors</h4>
                        <div class="leads_table table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                                    "order": [],
                                    "orderCellsTop": true,
                                    "paging":false}'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Vendor </th>
                                        <th class="border-0">Accepted At </th>
                                    </tr>
                                </thead>
                                <tbody id="set-rows">
                                    @foreach ($acceptedLeadVendors as $key => $vendor)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <a class="d-flex align-items-center"
                                                    href="{{ route('admin.store.view', $vendor->store_id) }}">
                                                    <img class="img--40 mx-1 rounded onerror-image"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $vendor->logo ?? '',
                                                            asset('storage/app/public/store') . '/' . $vendor->logo ?? '',
                                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                                            'store/',
                                                        ) }}"
                                                        alt="Image Description">
                                                    <div class="text-center">
                                                        <h5
                                                            class="text-capitalize text--title font-semibold text-hover-primary d-block mb-1">
                                                            {{ $vendor->name }}
                                                        </h5>

                                                    </div>
                                                </a>

                                            </td>
                                            <td>
                                                {{ $vendor->created_at }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (count($acceptedLeadVendors))
                                <hr>
                            @else
                                <div class="page-area">
                                </div>
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 list_div mt-2">
                        <h4>Confirmed Vendors</h4>
                        <div class="leads_table table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                                    "order": [],
                                    "orderCellsTop": true,
                                    "paging":false}'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Vendor </th>
                                        <th class="border-0">Confirmed At </th>
                                    </tr>
                                </thead>
                                <tbody id="set-rows">
                                    @foreach ($confirmedLeadVendors as $key => $vendor)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <a class="d-flex align-items-center"
                                                    href="{{ route('admin.store.view', $vendor->store_id) }}">
                                                    <img class="img--40 mx-1 rounded onerror-image"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $vendor->logo ?? '',
                                                            asset('storage/app/public/store') . '/' . $vendor->logo ?? '',
                                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                                            'store/',
                                                        ) }}"
                                                        alt="Image Description">
                                                    <div class="text-center">
                                                        <h5
                                                            class="text-capitalize text--title font-semibold text-hover-primary d-block mb-1">
                                                            {{ $vendor->name }}
                                                        </h5>

                                                    </div>
                                                </a>

                                            </td>
                                            <td>
                                                {{ $vendor->confirmed_at }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (count($confirmedLeadVendors))
                                <hr>
                            @else
                                <div class="page-area">
                                </div>
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-6 list_div mt-2">
                        <h4>Completed Vendors</h4>

                        <div class="leads_table table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                                "order": [],
                                "orderCellsTop": true,
                                "paging":false}'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Vendor </th>
                                        <th class=" border-0">{{ translate('messages.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="set-rows">
                                    @foreach ($completedLeadVendors as $key => $vendor)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <a class="d-flex align-items-center"
                                                    href="{{ route('admin.store.view', $vendor->store_id) }}">
                                                    <img class="img--40 mx-1 rounded onerror-image"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $vendor->logo ?? '',
                                                            asset('storage/app/public/store') . '/' . $vendor->logo ?? '',
                                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                                            'store/',
                                                        ) }}"
                                                        alt="Image Description">
                                                    <div class="text-center">
                                                        <h5
                                                            class="text-capitalize text--title font-semibold text-hover-primary d-block mb-1">
                                                            {{ $vendor->name }}
                                                        </h5>

                                                    </div>
                                                </a>

                                            </td>
                                         
                                            <td>
                                                <div class="btn--container flex-column ">
                                                     <a href="{{route('admin.service.lead-timeline',[$vendor->service_id])}}"
                                                    style="padding: 0px 20px !important; width: fit-content;"
                                                    class="btn action-btn btn-sm btn--primary btn-outline-primary my-1"
                                                    title="{{ translate('messages.view') }}">Timeline
                                                </a>
                                                     <a data-toggle="modal" data-target="#gatePassModal"
                                                    style="padding: 0px 20px !important; width: fit-content;"
                                                    class="btn action-btn btn-sm btn--warning btn-outline-warning my-1"
                                                    title="{{ translate('messages.view') }}">Gatepass
                                                </a>
                                                <a data-toggle="modal" data-target="#quotationModal"
                                                    style="padding: 0px 20px !important; width: fit-content;"
                                                    class="btn action-btn btn-sm btn--primary btn-outline-primary my-1"
                                                    title="{{ translate('messages.view') }}">Quotation
                                                </a>
                                                  @if (_getServiceInvoice($vendor->service_id))
                                                        <a target="_blank"
                                                            href='{{ asset('storage/app/public/invoice') . '/' . _getServiceInvoice($vendor->service_id) }}'
                                                            style="padding: 0px 20px !important; width: fit-content;"
                                                            class="btn action-btn btn-sm btn--warning btn-outline-warning"
                                                            title="{{ translate('messages.view') }}">Invoice
                                                        </a>
                                                    @else
                                                        <span class="text-secondary">Invoice<i class="tio-info-outined"
                                                                data-toggle="tooltip" data-placement="left"
                                                                title="Bill not generated yet"></i></span>
                                                    @endif
                                                </div>
                                            </td>
                                       
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Modal -->
                            <div class="modal fade" id="gatePassModal" tabindex="-1"
                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Gatepass</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Title</th>
                                                        <th scope="col">Description</th>
                                                        <th scope="col">Image</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($gpItems as $key => $item)
                                                        <tr>
                                                            <td>{{ $key + 1 }}</td>
                                                            <td>{{ $item->title }}</td>
                                                            <td> {{ $item->description }}</td>
                                                            <td>
                                                                <div class="d-flex">
                                                                    @php
                                                                        $gpimages = json_decode($item->image, true);
                                                                    @endphp

                                                                    @if (is_array($gpimages))
                                                                        @foreach ($gpimages as $img2)
                                                                            <a target="_blank"
                                                                                href="{{ asset('storage/app/public/gatepass') }}/{{ $img2 }}"
                                                                                style="cursor:default;"
                                                                                class="table-rest-info"
                                                                                alt="Gatepass image">

                                                                                <img style="cursor: zoom-in;"
                                                                                    src="{{ asset('storage/app/public/gatepass') }}/{{ $img2 }}">

                                                                            </a>
                                                                        @endforeach
                                                                    @else
                                                                        <a target="_blank"
                                                                            href="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}"
                                                                            style="cursor:default;"
                                                                            class="table-rest-info" alt="Gatepass image">
                                                                            <img style="cursor: zoom-in;"
                                                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                                                src="{{ asset('storage/app/public/gatepass') }}/{{ $item->image }}">
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <!-- Modal -->
                            <div class="modal fade" id="quotationModal" tabindex="-1"
                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Quotation</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
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
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if (count($completedLeadVendors))
                                <hr>
                            @else
                                <div class="page-area">
                                </div>
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>


                <!-- End Header -->

                <!-- Table -->
                <!-- End Table -->
            </div>
        </div>
        <!-- End Card -->
    </div>
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Location</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="searchInput"></div>
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
    @else
    Not Found
    @endif

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
                    <input type="hidden" name = "new_item[]" value = ''  >  
                      <td><input type="text" name="title[]" placeholder="Title" class="form-control"></td>
                      <td><input type="file" name="image[]" required class="form-control "></td>
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
