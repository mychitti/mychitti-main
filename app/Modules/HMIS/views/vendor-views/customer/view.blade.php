@extends('layouts.vendor.app')

@section('title', isset($customer) ? $customer->f_name : 'Customer View')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .gst_elem {
            background: #ecffec;
            width: fit-content;
            border-radius: 10px;
            padding: 8px 27px;
            margin: 10px 0;
        }

        .progress-container {
            width: 50px;
            margin: 15px auto;
            font-size: 14px;
            text-align: center;
        }

        .progress-text {
            margin-bottom: 5px;
            color: #444;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 0.4s ease;
        }

        .nav-link {
            margin-right: 5px;
            margin-left: 5px;
        }

        .nav-link.active {
            background-color: #005555 !important;
            color: white !important;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        {{-- .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.05;
            border-radius: 12px;
        } --}} .stat-card.green {
            background: rgba(16, 185, 129, 0.14);
        }

        .stat-card.purple {
            background: rgba(138, 92, 246, 0.16);
        }

        .stat-card.blue {
            background: rgba(55, 155, 255, 0.1);
        }

        .stat-card.orange {
            background: rgba(245, 159, 11, 0.09);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-card.green .stat-icon {
            background: #10b981;
        }

        .stat-card.purple .stat-icon {
            background: #8b5cf6;
        }

        .stat-card.blue .stat-icon {
            background: #3b82f6;
        }

        .stat-card.orange .stat-icon {
            background: #f59e0b;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-value2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-card.green .stat-value {
            color: #10b981;
        }

        .stat-card.purple .stat-value {
            color: #8b5cf6;
        }

        .stat-card.blue .stat-value {
            color: #3b82f6;
        }

        .stat-card.orange .stat-value {
            color: #f59e0b;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .order-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .order-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .order-title {
            font-size: 20px;
            font-weight: 600;
            color: #374151;
        }

        .order-count {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            padding: 10px 15px 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 280px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: #60a5fa;
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .search-icon {
            position: absolute;
            right: 11px;
            background: white;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #60a5fa;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .export-btn {
            background: white;
            border: 2px solid #e5e7eb;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .export-btn:hover {
            border-color: #60a5fa;
            background: #f8fafc;
        }

        .customer-info-btn {
            background: white;
            border: 2px solid #e5e7eb;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .customer-info-btn:hover {
            border-color: #60a5fa;
            background: #f8fafc;
        }

        .total-badge {
            background: #06b6d4;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 8px;
        }

        .table-container {
            overflow-x: auto;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th {
            background: #f9fafb;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .order-table td {
            padding: 20px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #4b5563;
        }

        .order-table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #dc2626;
            display: inline-block;
        }

        .customer-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            padding: 15px;
        }

        .customer-panel.open {
            right: 0;
        }

        .customer-panel h3 {
            font-size: 20px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 30px;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .info-label {
            font-weight: 500;
            color: #374151;
            min-width: 80px;
        }

        .info-value {
            color: #6b7280;
            flex: 1;
        }

        .copy-btn {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 2px;
            margin-left: 8px;
            font-size: 12px;
        }

        .copy-btn:hover {
            color: #374151;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .overlay.show {
            display: block;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6b7280;
        }

        .close-btn:hover {
            color: #374151;
        }

        .sort-icon {
            margin-left: 8px;
            color: #9ca3af;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .header-controls {
                flex-direction: column;
                width: 100%;
            }

            .search-input {
                width: 100%;
            }

            .customer-panel {
                width: 100%;
                right: -100%;
            }
        }

        .profile-card {
            background-color: #f2fffd;
            border-radius: 12px;
            padding: 20px;
            max-width: 700px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            margin-right: 15px;
            object-fit: cover;
        }

        .guest-info {
            flex: 1;
        }

        .guest-label {
            color: #28a745;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .guest-description {
            color: #666;
            font-size: 12px;
            line-height: 1.3;
        }

        .biodata-section {
            margin-bottom: 20px;
        }

        .section-title {
            color: #333;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .field-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .field-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .field-value {
            color: #333;
            font-size: 14px;
            background-color: #f8f9fa;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            min-width: 120px;
            text-align: center;
        }

        .gender-buttons {
            display: flex;
            gap: 8px;
        }

        .gender-btn {
            padding: 6px 16px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            color: #666;
            font-size: 14px;
            cursor: pointer;
        }

        .gender-btn.active {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }

        .contact-section {
            margin-bottom: 20px;
        }

        .contact-field {
            margin-bottom: 12px;
        }

        .contact-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .contact-value {
            color: #333;
            font-size: 14px;
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }

        .submit-btn {
            width: 100%;
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background-color: #218838;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid p-2">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between w-100 pr-5">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>
                Client Overview</h1>



        </div>
        <div class="stats-grid">
            <div class="stat-card green ">
                <div class="stat-icon">
                    <img style="      border: 1px solid #dddddd;  width: 90px;aspect-ratio:1;  border-radius: 50%;"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($customer['profile_pic'], asset('storage/app/public/profile/') . '/' . $customer['profile_pic'], asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                        alt="{{ $customer['f_name'] }}">
                </div>
                <div class="stat-value2">{{ $customer->f_name . ' ' . $customer->l_name }}</div>

                <span class="badge badge-soft-info badge-pill">{{ strtoupper($customer->user_type) }}</span>

                <div class="stat-label"><i class="tio-call"></i> {{ $customer->phone }}</div>
                <div class="stat-label"><i class="tio-email"></i> {{ $customer->email }}</div>
                @if (!empty($linkedPatient) && hasPermission('patient', 'view'))
                    <div class="stat-label">
                        <a href="{{ route('vendor.patient.show', $linkedPatient->id) }}"
                            title="Same person in the hospital records">
                            <i class="tio-heart-outlined"></i> Patient {{ $linkedPatient->patient_uid }}
                        </a>
                    </div>
                @endif
            </div>

            <div class="stat-card purple ">
                <div class="stat-icon">
                    ₹
                </div>
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value">{{ _price($data['totalAmount']) }}</div>
                <div class="stat-label"><b>Unpaid
                    </b>{{ _price($data['unpaidAmount']) }} included</div>

            </div>

            <div class="stat-card orange ">
                <div class="stat-icon">
                    <i class="tio-savings"></i>
                </div>
                <div class="stat-label">Total Services</div>
                <div class="stat-value">{{ count($services) }}</div>
                <div class="stat-label"><span style="    text-wrap: nowrap;"><b>Pending </b><span
                            class="badge badge-soft-info badge-pill">{{ count($services->where('status', 'new')) }}</span></span>,
                    <span style="    text-wrap: nowrap;"><b>Completed </b><span
                            class="badge badge-soft-success badge-pill">{{ count($services->where('current_status', 'Completed')) }}</span></span>,
                    <span style="    text-wrap: nowrap;"><b>Cancelled </b><span
                            class="badge badge-soft-danger badge-pill">{{ count($services->where('current_status', 'Cancelled')) }}</span></span>
                </div>

            </div>
            <div class="stat-card blue ">
                <div class="stat-icon">
                    <i class="tio-timer"></i>
                </div>
                <div class="stat-label">Work In Progress</div>
                <div class="d-flex justify-content-around">
                    <div>
                        <div class="stat-value">{{ count($tasks) }}</div>
                        <div class="stat-label">Tasks</div>
                    </div>
                    <div>
                        <div class="stat-value">{{ count($projects) }}</div>
                        <div class="stat-label">Projects</div>
                    </div>
                </div>


            </div>

            {{-- <div class="stat-card orange nav-link" id="nav-contact-tab" data-toggle="tab" data-target="#nav-contact" type="button" role="tab" aria-controls="nav-contact" aria-selected="false"">
                <div class="stat-icon">
                    <i class="fas fa-award"></i>
                </div>
                <div class="stat-value">0</div>
                <div class="stat-label">Loyalty point</div>
            </div> --}}
        </div>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button
                        class="tab_link nav-link {{ !request('tab') || request('tab') == '' || request('tab') == 'profile' ? 'active' : '' }}"
                        id="nav-profile-tab" data-toggle="tab" data-target="#nav-profile" type="button" role="tab"
                        aria-controls="nav-profile" aria-selected="false">Profile</button>
                    @if (hasPermission('client_manage', 'transactions'))
                        <button class="tab_link nav-link {{ request('tab') == 'transaction' ? 'active' : '' }}"
                            id="nav-transaction-tab" data-toggle="tab" data-target="#nav-transaction" type="button"
                            role="tab" aria-controls="nav-transaction" aria-selected="true">Transactions</button>
                    @endif
                    @if (hasPermission('client_manage', 'leads'))
                        <button class="tab_link nav-link {{ request('tab') == 'service' ? 'active' : '' }}"
                            id="nav-service-tab" data-toggle="tab" data-target="#nav-service" type="button" role="tab"
                            aria-controls="nav-service" aria-selected="false">Service Leads</button>
                    @endif
                    @if (hasPermission('client_manage', 'tasks'))
                        <button class="tab_link nav-link {{ request('tab') == 'task' ? 'active' : '' }}" id="nav-task-tab"
                            data-toggle="tab" data-target="#nav-task" type="button" role="tab" aria-controls="nav-task"
                            aria-selected="false">Tasks</button>
                    @endif
                    @if (hasPermission('client_manage', 'projects'))
                        <button class="tab_link nav-link {{ request('tab') == 'project' ? 'active' : '' }}"
                            id="nav-project-tab" data-toggle="tab" data-target="#nav-project" type="button" role="tab"
                            aria-controls="nav-project" aria-selected="false">Projects</button>
                    @endif
                    {{-- <button class="tab_link nav-link {{ request('tab') == 'gst' ? 'active' : '' }}" id="nav-gst-tab"
                        data-toggle="tab" data-target="#nav-gst" type="button" role="tab" aria-controls="nav-gst"
                        aria-selected="false">GST Details</button> --}}
                </div>
            </nav>
            <div class="d-flex gap-2">
                <form action="" class=" date-range-form">
                    <input type="hidden" name="tab" id="tab_field" value="{{ request('tab') }}">
                    @include('vendor-views/form_modals/date_range')
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                </form>
                @if (hasPermission('client_manage', 'comment'))
                    <button class="customer-info-btn" id="customerInfoBtn">
                        <i class="fas fa-user"></i>
                        Comments
                    </button>
                @endif
            </div>
        </div>

        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane pb-5 fade  {{ !request('tab') || request('tab') == '' || request('tab') == 'profile' ? 'show active' : '' }}"
                id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <h3>Customer Information</h3>
                <div class="profile-card">
                    <div class="d-flex justify-content-between align-items-center ">
                        <div class="profile-header">
                            <img style="      border: 1px solid #dddddd;  width: 50px;aspect-ratio:1;  border-radius: 50%;"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($customer['profile_pic'], asset('storage/app/public/profile/') . '/' . $customer['profile_pic'], asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                alt="{{ $customer['f_name'] }}">
                            <div class="guest-info">
                                <div class="guest-label">{{ $customer->f_name }}</div>
                                <div class="guest-description"><i>Client since:
                                        {{ _monthNYear($customer->created_at) }}</i>
                                </div>
                            </div>
                        </div>
                        @if (hasPermission('client_manage', 'edit'))
                            <a href="{{ route('vendor.customer.edit', $customer->id) }}" class="btn text-primary"><i
                                    class="tio-edit"></i> Edit</a>
                        @endif
                    </div>

                    <div class="biodata-section">
                        <div class="section-title">Personal Information</div>

                        <div class="field-row">
                            <div class="field-label">Name</div>
                            <div class="field-value">{{ $customer->f_name }} {{ $customer->l_name }}</div>
                        </div>

                        <div class="field-row">
                            <div class="field-label">Joined On</div>
                            <div class="field-value">{{ $customer->created_at }}</div>
                        </div>
                        <div class="field-row">
                            <div class="field-label">Email</div>
                            <div class="field-value">{{ $customer->email }}</div>
                        </div>
                        <div class="field-row">
                            <div class="field-label">Phone Number</div>
                            <div class="field-value">{{ $customer->phone }}</div>
                        </div>
                        @if ($customer->gst)
                            <div class="field-row">
                                <div class="field-label">GST Number</div>
                                <div class="field-value">{{ $customer->gst }}</div>
                            </div>
                        @endif
                        @if ($customer->id_number)
                            <div class="field-row">
                                <div class="field-label">ID Number</div>
                                <div class="field-value">{{ $customer->id_number }}</div>
                            </div>
                        @endif
                        @if ($customer->id_proof)
                            <div class="field-row">
                                <div class="field-label">ID Proof</div>
                                <div class="field-value"><a
                                        href="{{ asset('storage/app/public/customer/documents/') . '/' . $customer->id_proof }}">View</a>
                                </div>
                            </div>
                        @endif

                    </div>

                    <div class="contact-section">
                        <div class="section-title">Addresses</div>
                        @if ($customer->shipping_address)
                            <div class="contact-field">
                                <div class="contact-label">Shipping Address</div>
                                <div class="contact-value">{{ $customer->shipping_address->address1 }},
                                    {{ $customer->shipping_address->city }}, {{ $customer->shipping_address->state }} -
                                    {{ $customer->shipping_address->pincode }}</div>
                            </div>
                        @endif
                        @if ($customer->billing_address)
                            <div class="contact-field">
                                <div class="contact-label">Billing Address</div>
                                <div class="contact-value">{{ $customer->billing_address->address1 }},
                                    {{ $customer->billing_address->city }}, {{ $customer->billing_address->state }} -
                                    {{ $customer->billing_address->pincode }}</div>
                            </div>
                        @endif
                        {{-- @if (!$customer->shipping_address && !$customer->billing_address)
                            <div class="contact-field">
                                <div class="contact-label">No addresses available</div>
                                <button class="btn btn--primary">Add Address</button>
                            </div>
                        @endif --}}
                    </div>
                </div>
            </div>
            @if (hasPermission('client_manage', 'transactions'))
                <div class="tab-pane fade {{ request('tab') == 'transaction' ? 'show active' : '' }}"
                    id="nav-transaction" role="tabpanel" aria-labelledby="nav-transaction-tab">
                    <div class="order-section">
                        <div class="order-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="order-title">Transactions List</span>
                                <span class="order-count">{{ $invoices->count() }}</span>
                            </div>
                            <div class="header-controls">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input" placeholder="Search by Invoice ID">
                                </div>
                                <form id="exportForm" action="{{ route('vendor.customer.transactions.export') }}"
                                    method="POST" target="_blank">
                                    @csrf
                                    <input type="hidden" name="user_id" value = "{{ $id }}">
                                    <input type="hidden" name="type" value = "selected">
                                    <input type="hidden" name="service_inv_id" id="service_inv_id">
                                    <input type="hidden" name="manual_inv_id" id="manual_inv_id">
                                    <button type="button" class="btn btn_sm btn--primary export_selected">Export Selected
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="check_all" name="" id=""></th>
                                        <th>SL <i class=" sort-icon"></i></th>
                                        <th>Date <i class=" sort-icon"></i></th>
                                        <th>Invoice ID <i class=" sort-icon"></i></th>
                                        <th>Total Amount <i class=" sort-icon"></i></th>
                                        <th>Tax Amount <i class=" sort-icon"></i></th>
                                        <th>Credit <i class=" sort-icon"></i></th>
                                        <th>Debit <i class=" sort-icon"></i></th>
                                        <th>Payment Status <i class=" sort-icon"></i></th>
                                        <th>Created At <i class=" sort-icon"></i></th>
                                        <th><i class="fas fa-ellipsis-h"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $key => $value)
                                        <tr>
                                            <td><input type="checkbox" value="{{ $value->id }}"
                                                    class="check_item_{{ $value->inv_type }}" name=""
                                                    id="">
                                            </td>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $value->invoice_date ?? $value->created_at }}
                                            <td>
                                                <a target="_blank"
                                                    href="{{ asset('storage/app/public/invoice') . '/' . $value->pdf }}"
                                                    class="">{{ $value->invoice_id }}</a>
                                            </td>
                                            <td>{{ _price($value->total_amount) }} </td>
                                            <td>{{ _price($value->final_tax) }}</td>
                                            <td>
                                                @if ($value->invoice_type == 'credit')
                                                    <span class="text-success">{{ _price($value->total_amount) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($value->invoice_type == 'debit')
                                                    <span class="text-danger">{{ _price($value->total_amount) }}</span>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($value->payment_status == 'Paid')
                                                    <span
                                                        class="badge badge-soft-success">{{ translate('messages.paid') }}</span>
                                                @else
                                                    <span
                                                        class="badge badge-soft-danger">{{ translate('messages.unpaid') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($value->invoice_date ?? $value->created_at)->format('d M Y') }}
                                            </td>
                                            <td><a target="_blank"
                                                    href="{{ asset('storage/app/public/invoice') . '/' . $value->pdf }}"
                                                    class="btn action-btn btn--warning btn-outline-warning withdraw-info-show"><i
                                                        class="tio-visible"></i></a></td>
                                        </tr>
                                    @endforeach
                                    @if ($invoices->count() == 0)
                                        <tr>
                                            <td colspan="9" class="text-center">No transactions found</td>
                                        </tr>
                                    @endif

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if (hasPermission('client_manage', 'leads'))
                <div class="tab-pane fade {{ request('tab') == 'service' ? 'show active' : '' }}" id="nav-service"
                    role="tabpanel" aria-labelledby="nav-service-tab">
                    <div class="order-section">
                        <div class="order-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="order-title">Service Leads List</span>
                                <span class="order-count">{{ $services->count() }}</span>
                            </div>

                            <div class="header-controls">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input"
                                        placeholder="Ex : search by Service ID or Name">

                                </div>
                                <form id="leadExportForm" action="{{ route('vendor.customer.leads.export') }}"
                                    method="POST" target="_blank">
                                    @csrf
                                    <input type="hidden" name="user_id" value ="{{ $id }}">
                                    <input type="hidden" name="type" value = "selected">
                                    <input type="hidden" name="lead_id" id="lead_id">
                                    <button type="button" class="btn btn_sm btn--primary export_selected_lead">Export
                                        Selected
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="check_all_lead" name="" id="">
                                        </th>
                                        <th>SL <i class=" sort-icon"></i></th>
                                        <th>Date <i class=" sort-icon"></i></th>
                                        <th>Service Name <i class=" sort-icon"></i></th>
                                        <th>Service Lead ID <i class=" sort-icon"></i></th>
                                        <th>Status <i class=" sort-icon"></i></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($services as $key => $value)
                                        <tr>
                                            <td><input type="checkbox" value="{{ $value->id }}" class="check_lead"
                                                    name="check_lead" id="">
                                            </td>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($value->created_at)->format('d M Y H:i') }}</td>
                                            <td>
                                                <a class="media align-items-center" href="javascript:void(0)   ;">
                                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $value->image ?? '',
                                                            asset('storage/app/public/product') . '/' . $value->image ?? '',
                                                            asset('public/assets/admin/img/160x160/img2.jpg'),
                                                            'product/',
                                                        ) }}"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                        alt="{{ $value->item_name }} image">
                                                    <div title="{{ $value->item_name }}" class="media-body">
                                                        <h5 class="text-hover-primary mb-0">
                                                            {{ Str::limit($value->item_name, 20, '...') }}
                                                        </h5>
                                                    </div>
                                                </a>
                                            </td>
                                            <td>
                                                <a
                                                    href="{{ route('vendor.service.lead-details', [$value->service_id]) }}">{{ $value->service_id }}</a>
                                            </td>

                                            <td>
                                                @if ($value->current_status == 'Cancelled')
                                                    <span class="badge badge-soft-danger">
                                                        {{ translate('messages.cancelled') }}
                                                    </span>
                                                @elseif($value->current_status == 'Completed')
                                                    <span class="badge badge-soft-success">
                                                        {{ translate('messages.completed') }}
                                                    </span>
                                                @elseif($value->current_status)
                                                    <span class="badge badge-soft-warning">
                                                        {{ $value->current_status }}
                                                    </span>
                                                @else
                                                    <span
                                                        class="badge badge-soft-info">{{ ucfirst($value->status) }}</span>
                                                @endif
                                            </td>
                                            <td><a href="{{ route('vendor.service.lead-details', [$value->id]) }}"
                                                    class="btn action-btn btn--warning btn-outline-warning withdraw-info-show"><i
                                                        class="tio-visible"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($services->count() == 0)
                                        <tr>
                                            <td colspan="6" class="text-center">No services found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if (hasPermission('client_manage', 'tasks'))
                <div class="tab-pane fade {{ request('tab') == 'task' ? 'show active' : '' }}" id="nav-task"
                    role="tabpanel" aria-labelledby="nav-task-tab">
                    <div class="order-section">
                        <div class="order-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="order-title">Tasks List</span>
                                <span class="order-count">{{ $tasks->count() }}</span>
                            </div>

                            <div class="header-controls">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input" placeholder="Ex : search by Task Title">

                                </div>
                                <form id="taskExportForm" action="{{ route('vendor.customer.tasks.export') }}"
                                    method="POST" target="_blank">
                                    @csrf
                                    <input type="hidden" name="user_id" value ="{{ $id }}">
                                    <input type="hidden" name="type" value = "selected">
                                    <input type="hidden" name="task_id" id="task_id">
                                    <button type="button" class="btn btn_sm btn--primary export_selected_tasks">Export
                                        Selected
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="check_all_task" name="" id="">
                                        </th>
                                        <th>SL <i class=" sort-icon"></i></th>
                                        <th>Title <i class=" sort-icon"></i></th>
                                        <th>Duration <i class=" sort-icon"></i></th>
                                        <th>Assignee <i class=" sort-icon"></i></th>
                                        <th>Progress <i class=" sort-icon"></i></th>
                                        <th>Status <i class=" sort-icon"></i></th>
                                        <th>Created At <i class=" sort-icon"></i></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tasks as $key => $value)
                                        <tr>
                                            <td><input type="checkbox" value="{{ $value->id }}" class="check_task"
                                                    name="check_task" id="">
                                            </td>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <a class="media align-items-center" href="javascript:void(0)   ;">
                                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $value->file ?? '',
                                                            asset('storage/app/public/task') . '/' . $value->file ?? '',
                                                            asset('public/assets/admin/img/160x160/img2.jpg'),
                                                            'task/',
                                                        ) }}"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                        alt="{{ $value->title }} image">
                                                    <div title="{{ $value->title }}" class="media-body">
                                                        <h5 class="text-hover-primary mb-0">
                                                            {{ Str::limit($value->title, 20, '...') }}
                                                        </h5>
                                                    </div>
                                                </a>
                                            </td>
                                            <td>

                                                <span
                                                    class="badge badge-soft-info">{{ $value->time_count . ' ' . $value->time_unit . '(s)' }}</span>
                                            </td>
                                            <td>
                                                {{ $value->employee?->f_name . ' ' . $value->employee?->l_name }}
                                            </td>
                                            <td>
                                                @php
                                                    $progress = $value->progress;
                                                    // Color transition logic: red (255, 100, 100) to green (100, 255, 100)
                                                    $r = round(255 - (155 * $progress) / 100); // red fades
                                                    $g = round(100 + (155 * $progress) / 100); // green increases
                                                    $barColor = "rgb($r, $g, 100)";
                                                @endphp
                                                <div class="progress-container">
                                                    <div class="progress-text">{{ $progress }}%</div>
                                                    <div class="progress-bar">
                                                        <div class="progress-fill"
                                                            style="width: {{ $progress }}%; background-color: {{ $barColor }};">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {{ $value->status }}</td>
                                            <td>{{ \Carbon\Carbon::parse($value->created_at)->format('d M Y H:i') }}</td>
                                            <td><a href="{{ $value->parent_id ? route('vendor.task.subtask.detail', [$value->id]) : route('vendor.task.detail', [$value->id]) }}"
                                                    class="btn action-btn btn--warning btn-outline-warning withdraw-info-show"><i
                                                        class="tio-visible"></i>
                                                </a></td>
                                        </tr>
                                    @endforeach
                                    @if ($tasks->count() == 0)
                                        <tr>
                                            <td colspan="8" class="text-center">No tasks found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
            
            @if (hasPermission('client_manage', 'projects'))
                <div class="tab-pane fade {{ request('tab') == 'project' ? 'show active' : '' }}" id="nav-project"
                    role="tabpanel" aria-labelledby="nav-project-tab">
                    <div class="order-section">
                        <div class="order-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="order-title">Projects List</span>
                                <span class="order-count">{{ $projects->count() }}</span>
                            </div>

                            <div class="header-controls">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input"
                                        placeholder="Ex : search by Project Title or Team Leader">

                                </div>
                                <form id="projectExportForm" action="{{ route('vendor.customer.project.export') }}"
                                    method="POST" target="_blank">
                                    @csrf
                                    <input type="hidden" name="user_id" value ="{{ $id }}">
                                    <input type="hidden" name="type" value = "selected">
                                    <input type="hidden" name="project_id" id="project_id">
                                    <button type="button" class="btn btn_sm btn--primary export_selected_project">Export
                                        Selected
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="check_all_project" name=""
                                                id="">
                                        </th>
                                        <th>SL <i class=" sort-icon"></i></th>
                                        <th>Title <i class=" sort-icon"></i></th>
                                        <th>Team Leader <i class=" sort-icon"></i></th>
                                        <th>Progress <i class=" sort-icon"></i></th>
                                        <th>Status <i class=" sort-icon"></i></th>
                                        <th>Advance Pay <i class=" sort-icon"></i></th>
                                        <th>Deadline <i class=" sort-icon"></i></th>
                                        <th>Other Info <i class=" sort-icon"></i></th>
                                        <th>Created At <i class=" sort-icon"></i></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($projects as $key => $value)
                                        <tr>
                                            <td><input type="checkbox" value="{{ $value->id }}"
                                                    class="check_project" name="check_project" id=""></td>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <a class="media align-items-center" href="javascript:void(0)   ;">
                                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $value->file ?? '',
                                                            asset('storage/app/public/project') . '/' . $value->file ?? '',
                                                            asset('public/assets/admin/img/160x160/img2.jpg'),
                                                            'project/',
                                                        ) }}"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                        alt="{{ $value->project_title }} image">
                                                    <div title="{{ $value->project_title }}" class="media-body">
                                                        <h5 class="text-hover-primary mb-0">
                                                            {{ Str::limit($value->project_title, 20, '...') }}
                                                        </h5>
                                                    </div>
                                                </a>
                                            </td>
                                            <td>
                                                {{ $value->teamLeader?->f_name . ' ' . $value->teamLeader?->l_name }}
                                            </td>
                                            <td>
                                                @php
                                                    $progress = $value->prog_percent;
                                                    // Color transition logic: red (255, 100, 100) to green (100, 255, 100)
                                                    $r = round(255 - (155 * $progress) / 100); // red fades
                                                    $g = round(100 + (155 * $progress) / 100); // green increases
                                                    $barColor = "rgb($r, $g, 100)";
                                                @endphp
                                                <div class="progress-container">
                                                    <div class="progress-text">{{ $progress }}%</div>
                                                    <div class="progress-bar">
                                                        <div class="progress-fill"
                                                            style="width: {{ $progress }}%; background-color: {{ $barColor }};">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td> {{ $value->progress_status }}</td>
                                            <td> {{ _price($value->advance_pay) }}
                                            </td>
                                            <td>{{ $value->end_date }}</td>

                                            <td> {{ ucfirst($value->project_type . ' - ' . $value->project_size) }}</td>


                                            <td>{{ \Carbon\Carbon::parse($value->created_at)->format('d M Y H:i') }}</td>
                                            <td><a href="{{ route('vendor.project.details', [$value->id]) }}"
                                                    class="btn action-btn btn--warning btn-outline-warning withdraw-info-show"><i
                                                        class="tio-visible"></i>
                                                </a></td>
                                        </tr>
                                    @endforeach
                                    @if ($projects->count() == 0)
                                        <tr>
                                            <td colspan="10" class="text-center">No projects found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
            {{-- <div class="tab-pane fade{{ request('tab') == 'gst' ? 'show active' : '' }} " id="nav-gst"
                role="tabpanel" aria-labelledby="nav-gst-tab">
                <div class="order-section">
                    <div class="d-flex  align-items-center gst_elem ">
                        <b>GST: </b>
                        <div class="p-2">
                            <input type="checkbox" name="" id="gst_payable">
                            <label class="mb-0" for="gst_payable"> Payable </label>
                        </div>
                        <div class="p-2">
                            <input type="checkbox" name="" id="gst_receivable">
                            <label class="mb-0" for="gst_receivable"> Receivable </label>
                        </div>
                    </div>
                    <div class="d-flex  align-items-center gst_elem ">
                        <b>TDS: </b>
                        <div class="p-2">
                            <input type="checkbox" name="" id="tds_payable">
                            <label class="mb-0" for="tds_payable"> Payable </label>
                        </div>
                        <div class="p-2">
                            <input type="checkbox" name="" id="tds_receivable">
                            <label class="mb-0" for="tds_receivable"> Receivable </label>
                        </div>
                    </div>
                    <div class="d-flex  align-items-center  gst_elem">
                        <b>TCS: </b>
                        <div class="p-2">
                            <input type="checkbox" name="" id="tcs_payable">
                            <label class="mb-0" for="tcs_payable"> Payable </label>
                        </div>
                        <div class="p-2">
                            <input type="checkbox" name="" id="tcs_receivable">
                            <label class="mb-0" for="tcs_receivable"> Receivable </label>
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>

        <!-- Order List Section -->
        @if (hasPermission('client_manage', 'comment'))

            <div class="overlay" id="overlay"></div>
            <div class="customer-panel" id="customerPanel">
                <button class="close-btn" id="closeBtn">×</button>
                <h3>Comments</h3>
                <div class="customer-info">
                    @foreach ($customer->comments as $key => $value)
                        <div class="card m-1 p-2">
                            {{ $value->comment }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endsection

    @push('script_2')
        <script>
            $(document).ready(function() {
                // Customer Information Panel Toggle
                $('#customerInfoBtn').click(function() {
                    $('#customerPanel').addClass('open');
                    $('#overlay').addClass('show');
                });

                $('#closeBtn, #overlay').click(function() {
                    $('#customerPanel').removeClass('open');
                    $('#overlay').removeClass('show');
                });

                // Search functionality
                $('.search-input').on('input', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    $('.order-table tbody tr').each(function() {
                        const orderId = $(this).find('td:nth-child(2)').text().toLowerCase();
                        const store = $(this).find('td:nth-child(3)').text().toLowerCase();

                        if (orderId.includes(searchTerm) || store.includes(searchTerm)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Copy functionality
                $('.copy-btn').click(function() {
                    const text = $(this).parent().text().replace(':', '').trim().split(' ')[0];
                    navigator.clipboard.writeText(text).then(function() {
                        // You could add a toast notification here
                        console.log('Copied to clipboard: ' + text);
                    });
                });

                {{-- // Sort functionality (basic implementation)
                $('.order-table th').click(function() {
                    const column = $(this).index();
                    const isAscending = $(this).hasClass('asc');

                    // Remove all sorting classes
                    $('.order-table th').removeClass('asc desc');

                    // Add appropriate class
                    if (isAscending) {
                        $(this).addClass('desc');
                    } else {
                        $(this).addClass('asc');
                    }

                    // Sort rows (simplified version)
                    const rows = $('.order-table tbody tr').get();
                    rows.sort(function(a, b) {
                        const aText = $(a).find('td').eq(column).text();
                        const bText = $(b).find('td').eq(column).text();

                        if (isAscending) {
                            return aText.localeCompare(bText);
                        } else {
                            return bText.localeCompare(aText);
                        }
                    });

                    $('.order-table tbody').empty().append(rows);
                }); --}}

                // Export dropdown (you can expand this)
                $('.export-btn').click(function() {
                    alert('Export functionality would be implemented here');
                });
            });
            $(".tab_link").on('click', function() {
                let tab = $(this).attr('data-target');

                tab = tab.replace('#', '').split('-').pop();

                let newUrl = new URL(window.location.href);
                newUrl.searchParams.set('tab', tab);
                window.history.pushState({}, '', newUrl);
                $("#tab_field").val(tab)
            });
            $('.check_all').on('change', function() {
                if ($(this).prop('checked') == true) {
                    $(".check_item_service").prop('checked', true)
                    $(".check_item_manual").prop('checked', true)
                } else {
                    $(".check_item_service").prop('checked', false)
                    $(".check_item_manual").prop('checked', false)
                }
            })
            $('.check_all_project').on('change', function() {
                if ($(this).prop('checked') == true) {
                    $(".check_project").prop('checked', true)
                } else {
                    $(".check_project").prop('checked', false)
                }
            })
            $('.check_all_lead').on('change', function() {
                if ($(this).prop('checked') == true) {
                    $(".check_lead").prop('checked', true)
                } else {
                    $(".check_lead").prop('checked', false)
                }
            })
            $('.check_all_task').on('change', function() {
                if ($(this).prop('checked') == true) {
                    $(".check_task").prop('checked', true)
                } else {
                    $(".check_task").prop('checked', false)
                }
            })
            $(".export_selected").on('click', function() {
                let selected_service = [];
                let selected_manual = [];

                $(".check_item_service:checked").each(function() {
                    selected_service.push($(this).val());
                });
                $(".check_item_manual:checked").each(function() {
                    selected_manual.push($(this).val());
                });

                if (selected_service.length === 0 && selected_manual.length === 0) {
                    toastr.error("Please select at least one transaction.");
                    return;
                }

                $("#service_inv_id").val(JSON.stringify(selected_service));
                $("#manual_inv_id").val(JSON.stringify(selected_manual));

                $("#exportForm").submit();
            });
            $(".export_selected_lead").on('click', function() {
                let selected_lead = [];
                $(".check_lead:checked").each(function() {
                    selected_lead.push($(this).val());
                });
                if (selected_lead.length === 0) {
                    toastr.error("Please select at least one service lead.");
                    return;
                }
                $("#lead_id").val(JSON.stringify(selected_lead));
                $("#leadExportForm").submit();
            });
            $(".export_selected_tasks").on('click', function() {
                selected_task = [];
                $(".check_task:checked").each(function() {
                    selected_task.push($(this).val());
                });
                if (selected_task.length === 0) {
                    toastr.error("Please select at least one task.");
                    return;
                }
                $("#task_id").val(JSON.stringify(selected_task));
                $("#taskExportForm").submit();
            });
            $(".export_selected_project").on('click', function() {
                selected_project = [];
                $(".check_project:checked").each(function() {
                    selected_project.push($(this).val());
                });
                if (selected_project.length === 0) {
                    toastr.error("Please select at least one project.");
                    return;
                }
                $("#project_id").val(JSON.stringify(selected_project));
                $("#projectExportForm").submit();
            });
        </script>
        @include('vendor-views/js/date_range')
    @endpush
