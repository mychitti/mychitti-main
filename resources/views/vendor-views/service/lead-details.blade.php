@extends('layouts.vendor.app')

@section('title', 'Lead Details')

@push('css_or_js')
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <style>
        .select2-container {
            width: 150px !important;
        }

        .action-card {
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center; 
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease-in-out;
            background: #f8f9fa;
            /* Light gray background */
            border: 1px solid #ddd;
        }

        .action-card:hover {
            background: #e9ecef;
            transform: scale(1.05);
        }

        .comment {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .start-job {
            background-color: #cfe2ff;
            color: #084298;
        }

        .end-job {
            background-color: #f8d7da;
            color: #842029;
        }

        .location {
            background-color: #cff4fc;
            color: #055160;
        }

        .gatepass {
            background-color: #fff3cd;
            color: #664d03;
        }

        .quotation {
            background-color: #e2e3e5;
            color: #41464b;
        }

        /*timeline desing */
        * {
            border: 0;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        #map {
            height: 300px;
            width: 100%;
        }

        /* otp element styling  */
        .otp-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-container p {
            margin-bottom: 20px;
            color: #666;
        }

        .otp-form {
            display: flex;
            justify-content: space-between;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
        }

        /*:root {*/
        /*	--hue: 223;*/
        /*	--bg: hsl(var(--hue),10%,90%);*/
        /*	--fg: hsl(var(--hue),10%,10%);*/
        /*	--primary: hsl(var(--hue),90%,50%);*/
        /*	--trans-dur: 0.3s;*/
        /*	--trans-timing: cubic-bezier(0.65,0,0.35,1);*/
        /*	font-size: calc(16px + (24 - 16) * (100vw - 320px) / (2560 - 320));*/
        /*}*/
        .timeline_elem a {
            color: var(--primary);
            transition: color var(--trans-dur);
        }

        /*body,*/
        .timeline_elem button {
            color: var(--fg);
            font: 1em/1.5 "IBM Plex Sans", sans-serif;
        }

        /*body {*/
        /*	background-color: var(--bg);*/
        /*	height: 100vh;*/
        /*	transition:*/
        /*		background-color var(--trans-dur),*/
        /*		color var(--trans-dur);*/
        /*}*/
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
    @if ($reqDetails)

        @php $custDet = _customerDetByserviceId($reqDetails->id); @endphp
        <div class="content container-fluid">


            <!-- Resturent Card Wrapper -->
            <div class="row">
                <div class="col-md-7">
                    <h4 class="resturant-card" style="background-color:#f0f0f0">{{ $reqDetails->name }}</h4>
                </div>
                <div class="col-md-5">
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        @if (hasPermission('leads_manage', 'add_comment'))
                            <!-- Add Comment -->
                            <div class="action-card comment" data-toggle="modal" data-target="#commentModal">
                                <i class="fas fa-comment"></i> Add Comment
                            </div>
                        @endif
                        @if ($acceptanceDetails->current_status != 'Completed' && $acceptanceDetails->current_status != 'Cancelled')
                            @if ($venJobDetails && $venJobDetails->started_at && !$venJobDetails->ended_at)
                                <!-- End Job -->
                                @if (hasPermission('leads_manage', 'end_job'))
                                    <div class="action-card end-job start_job_btn end"
                                        data-url="{{ route('vendor.lead.start-job') }}" data-value="{{ $custDet->phone }}"
                                        data-service-id="{{ $acceptanceDetails->service_request_id }}"
                                        data-toggle="modal" data-target="#jobModal">  
                                        <i class="fas fa-stop"></i> End Job
                                    </div>
                                @endif
                            @elseif($venJobDetails && !$venJobDetails->started_at)
                                @if (hasPermission('leads_manage', 'start_job'))
                                    <!-- Start Job -->
                                    <div class="action-card start-job start_job_btn start"
                                        data-url="{{ route('vendor.lead.start-job') }}" data-value="{{ $custDet->phone }}"
                                        data-service-id="{{ $acceptanceDetails->service_request_id }}"
                                        data-toggle="modal" data-target="#jobModal">
                                        <i class="fas fa-play"></i> Start Job
                                    </div>
                                @endif
                            @endif
                        @endif

                        <!-- Customer Location -->
                        <a class="action-card location text-decoration-none"
                            href="https://www.google.com/maps?q={{ $custDet->latitude }},{{ $custDet->longitude }}"
                            target="_blank">
                            <i class="fas fa-map-marker-alt"></i> Client Location
                        </a>
                        @if (_isHospital())
                            @php
                                $srRx = \App\Models\Prescription::where('service_request_id', $acceptanceDetails->service_request_id)->first();
                            @endphp
                            @if($srRx)
                                <a class="action-card text-decoration-none"
                                    style="background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8;"
                                    href="{{ route('vendor.prescription.show', $srRx->id) }}">
                                    <i class="tio-print"></i> View Prescription
                                </a>
                                @if(!$srRx->is_finalized)
                                <a class="action-card text-decoration-none"
                                    style="background:#fff7ed; border-color:#fed7aa; color:#c2410c;"
                                    href="{{ route('vendor.prescription.edit', $srRx->id) }}">
                                    <i class="tio-edit"></i> Edit Prescription
                                </a>
                                @endif
                            {{-- @else
                                <a class="action-card text-decoration-none"
                                    style="background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8;"
                                    href="{{ route('vendor.prescription.create', ['service_request_id' => $acceptanceDetails->service_request_id]) }}">
                                    <i class="tio-medicine"></i> Add Prescription
                                </a> --}}
                            @endif
                        @endif

                        @if (hasPermission('leads_receivable_receipt', 'view') && isset($data['receivable_rec']) && $data['receivable_rec']->pdf)
                            <a target="_blank" class="action-card gatepass"
                                href="{{ asset('storage/app/public/store/recivable-receipts/' . $data['receivable_rec']->pdf) }}">
                                Receivable Receipt
                            </a>
                        @endif
                        @if (hasPermission('leads_jobcard', 'view') && $data['job_card'] && $data['job_card']->pdf)
                            <a target="_blank" class="action-card gatepass "
                                href="{{ asset('storage/app/public/store/jobcards/' . $data['job_card']->pdf) }}">
                                Job Card
                            </a>
                        @endif
                        {{-- asset('storage/app/public/store/jobcards/' . $fileName) --}}
                    </div>

                    @if ($acceptanceDetails->assigned_type == 'vendor')
                        <!-- Secondary Buttons -->
                        <div class="d-flex flex-wrap gap-3 mt-3">
                            @if (hasAnyModulePermission(['leads_gatepass']))
                                <a href="{{ route('vendor.service.gatepass-details', [$acceptanceDetails->service_request_id]) }}"
                                    class="action-card gatepass text-decoration-none">
                                    <i class="fas fa-file-alt"></i> Gatepass
                                </a>
                            @endif
                            @if (hasAnyModulePermission(['leads_quotation']))
                                <a href="{{ route('vendor.service.quotations', [$acceptanceDetails->service_request_id]) }}"
                                    class="action-card quotation text-decoration-none">
                                    <i class="fas fa-file-invoice"></i> Quotation
                                </a>
                            @endif
                            @if ($acceptanceDetails->current_status != 'Completed' && $acceptanceDetails->current_status != 'Cancelled')
                                <div class="modal fade modal_{{ $acceptanceDetails->service_request_id }}"
                                    id="jobModal2_{{ $acceptanceDetails->id }}" tabindex="-1"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="container ">
                                                    <h2 class="text-center">Enter OTP</h2>
                                                    <div class="p-5 bg-light rounded"
                                                        style="max-width: 550px; margin: 0 auto;">
                                                        <div class="row ">
                                                            <form enctype="multipart/form-data" class="otpForm"
                                                                style="margin: 0 auto;"
                                                                action="{{ route('vendor.lead.job-otp-verify2') }}"
                                                                method="post">
                                                                @csrf
                                                                <div class="d-flex flex-column bg-white p-3">
                                                                    <label for="">File Upload (Optional)</label>
                                                                    <input type="file" name="file"
                                                                        class="form-controlmb-3" id="">
                                                                </div>
                                                                <p>OTP has been sent to client. Please verify.</p>
                                                                <input type="hidden" name="status"
                                                                    id="sttas_{{ $acceptanceDetails->service_request_id }}"
                                                                    value="">
                                                                <input type="hidden" name="acc_id" id=""
                                                                    value="{{ $acceptanceDetails ? $acceptanceDetails->id : 0 }}">
                                                                <input type="hidden" name="phone" id="ver_phone"
                                                                    value="{{ $reqDetails->phone }}">
                                                                <input type="hidden" name="action" id="job_actions"
                                                                    value="">
                                                                <div class="d-flex justify-content-center w-100">
                                                                    <input type="text" maxlength="1"
                                                                        class="otp-input" name="otp[]" />
                                                                    <input type="text" maxlength="1"
                                                                        class="otp-input" name="otp[]" />
                                                                    <input type="text" maxlength="1"
                                                                        class="otp-input" name="otp[]" />
                                                                    <input type="text" maxlength="1"
                                                                        class="otp-input" name="otp[]" />
                                                                </div>
                                                                <input type="hidden" value="1" name="ajax" />
                                                                <button type="submit"
                                                                    class="btn btn-lg btn-block btn--primary mt-3">Submit</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (hasPermission('leads_manage', 'status_change'))
                                    <select name="module_id" data-placeholder="Status"
                                        data-value="{{ $acceptanceDetails->id }}" class="form-control js-select2-custom"
                                        onchange="changeStatus(this.value, {{ $acceptanceDetails->service_request_id }})"
                                        title="Change Status">
                                        <option></option>
                                        @foreach ($default_statuses as $st)
                                            @php
                                                $jobInfo = _getWhere('vendor_emp_jobs', [
                                                    'service_id' => $acceptanceDetails->service_request_id,
                                                ]);
                                                if ($jobInfo && count($jobInfo)) {
                                                    $jobInfo = $jobInfo[0];
                                                } else {
                                                    $jobInfo = '';
                                            } @endphp

                                            <option value="{{ $st->id }}"
                                                {{ $jobInfo && $jobInfo->status == $st->id ? 'selected' : '' }}>
                                                {{ $st->status }}</option>
                                        @endforeach
                                        @foreach ($statuses as $st)
                                            @php
                                                $jobInfo = _getWhere('vendor_emp_jobs', [
                                                    'service_id' => $acceptanceDetails->service_request_id,
                                                ]);
                                                if ($jobInfo && count($jobInfo)) {
                                                    $jobInfo = $jobInfo[0];
                                                } else {
                                                    $jobInfo = '';
                                            } @endphp

                                            <option value="{{ $st->id }}"
                                                {{ $jobInfo && $jobInfo->status == $st->id ? 'selected' : '' }}>
                                                {{ $st->status }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @if (hasPermission('leads_manage', 'start_job'))
                                    <button type="button"
                                        class="invisible btn btn-primary start_job_btn start btn_{{ $acceptanceDetails->service_request_id }}"
                                        data-value="{{ $acceptanceDetails->id }}" data-toggle="modal"
                                        data-target="#jobModal2_{{ $acceptanceDetails->id }}">Start Job</button>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <!-- Resturent Card Wrapper -->

            <div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="container py-5">
                                <h2 class="text-center">Enter OTP</h2>
                                <div class="p-5 bg-light rounded" style="max-width: 550px; margin: 0 auto;">
                                    <div class="row ">
                                        <form class="otpForm" style="margin: 0 auto;"
                                            action="{{ route('vendor.lead.job-otp-verify') }}" method="post">
                                            <p>OTP has been sent to client. Please verify.</p>
                                            @csrf
                                            <input type="hidden" name="acc_id" id=""
                                                value="{{ $acceptanceDetails ? $acceptanceDetails->id : 0 }}">
                                            <input type="hidden" name="phone" id="ver_phone"
                                                value="{{ $custDet->phone }}">
                                            <input type="hidden" name="action" id="job_action" value="">
                                            <div class="d-flex justify-content-center w-100">
                                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                                <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            </div>
                                            <button type="submit"
                                                class="btn btn-lg btn-block btn--primary mt-3">Submit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="post" action="{{ route('vendor.lead.save-comment') }}">
                            @csrf
                            <div class="modal-body">
                                <label for="comment">Comment</label>
                                <input type="hidden" name="lead_id" value="{{ $reqDetails->id }}">
                                <textarea name="comment" class="form-control my-2" placeholder="Comment..." id="comment"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Card -->
            <div class="card">
                <!-- Header -->
                <div class="card-header row py-2 align-items-start">
                    <div class="timeline_elem col-md-7">
                        <svg display="none">
                            <symbol id="arrow">
                                <polyline points="7 10,12 15,17 10" fill="none" stroke="currentcolor"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
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
                                            <svg class="timeline__arrow-icon" viewBox="0 0 24 24" width="24px"
                                                height="24px">
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
                                                <p class="timeline__item-p">Requested for <b> {{ $reqDetails->name }}</b>.
                                                </p>
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
                                                                                style="cursor:default;"
                                                                                class="table-rest-info"
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
                    <div class="col-md-5" style=" height: 90vh; overflow: auto;">
                        <h3>Client Details</h3>

                        <div class="col-12 mb-1 p-0">
                            <div class="resturant-card card--bg-3 position-relative">
                                <div class="my-1">
                                    <h5 class="title" style="font-size:1.1rem; display:inline;">
                                        {{ $custDet->f_name . ' ' . $custDet->l_name }}</h5>
                                </div>
                                <div class="subtitle my-1">{{ $custDet->email }}</div>
                                <div class="subtitle my-1">{{ $custDet->phone }}</div>
                                <div class="subtitle my-1"><a type="button" class="" data-toggle="modal"
                                        data-target="#mapModal"><i class="fas fa-map-marker-alt mx-1"></i><span
                                            id="addressElem"> </span></a></div>

                                <div class=""><i>Client since: {{ _monthNYear($custDet->created_at) }}</i></div>
                            </div>
                        </div>
                        @if ($reqDetails->requirements)
                            <h3>Additional Requirements</h3>
                            <div class="col-12 mb-1 p-0">
                                <div class="resturant-card card--bg-3 position-relative">
                                    {{ $reqDetails->requirements }}
                                </div>
                            </div>
                        @endif

                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <!-- End Table -->
                </div>
            </div>
            <!-- End Card -->
        </div>

        <!-- Modal -->
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
                        <a class="btn btn-primary"
                            href="https://www.google.com/maps?q={{ $custDet->latitude }},{{ $custDet->longitude }}"
                            target="_blank">Navigate</a>

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
    @if ($reqDetails)
        <script>
            function changeStatus(status, service_id) {
                if (status == 12 || status == 14) {
                    $(".btn_" + service_id).click()
                    $('#sttas_' + service_id).val(status)
                    if (sendOtp(status, service_id)) {
                        if (verifyOtp()) {
                            updateStatus(status, service_id);
                        }
                    };

                } else {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to change status?",
                        type: 'warning',
                        showCancelButton: true,
                        cancelButtonColor: 'default',
                        confirmButtonColor: '#FC6A57',
                        cancelButtonText: 'No',
                        confirmButtonText: 'Yes',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.value) {
                            updateStatus(status, service_id);
                        }
                    })
                }
            }

            function updateStatus(status, service_id) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.post({
                    url: '{{ route('vendor.service.change-status') }}',
                    data: {
                        status: status,
                        service_id: service_id
                    },
                    beforeSend: function() {
                        $('#loading').show()
                    },
                    success: function(data) {
                        if (data.status) {
                            toastr.success(data.message);
                        } else {
                            toastr.error(data.message);

                        }
                    },
                    complete: function() {
                        $('#loading').hide()
                    }
                });
            }


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
    @endif
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
        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });

        $(".start_job_btn").on('click', function() {
            console.log($(this).attr('data-id'))
            if ($(this).hasClass('end')) {
                job_action = 'end';
            } else {
                job_action = 'start';
            }
            console.log(job_action)
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).attr('data-url'),
                data: { 
                    customer_phone: $(this).attr('data-value'),
                    job_action: job_action,
                    service_id: $(this).attr('data-service-id')
                },
                success: function(data) {
                    $('#job_action').val(job_action)
                },
            });
        })

        function sendOtp(status, service_id) {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('vendor.lead.change-job-status') }}",
                data: {
                    service_id: service_id,
                    status: status
                },
                success: function(data) {},
            });
        }

        function verifyOtp() {

        }
        $(".otpForm").on('submit', function(e) {
            e.preventDefault();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                } 
            });
            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    console.log(data)
                    if (data.status) {
                        toastr.success(data.msg);
                        setTimeout(() => {
                            window.location.reload();
                        }, 500)
                    } else {
                        toastr.error(data.msg);

                    }
                },
                complete: function() {
                    $('#loading').hide()
                }
            });

        })
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    <script>
        document.querySelectorAll('.lightgallery').forEach(gallery => {
            lightGallery(gallery, {
                plugins: [lgVideo], // Add lgVideo plugin
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                thumbWidth: 80,
                thumbHeight: "auto",
                videojs: true // Enable video support
            });
        });
    </script>
@endpush
