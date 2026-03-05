 @extends('layouts.vendor.app')

 @section('title', 'POS Token')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
         .dine-table-card {
             cursor: pointer;
             border-width: 1px;
             border-radius: 6px !important;
         } 

         .dine-table-card.active {
             background-color: var(--primary-orange);
             border-color: var(--primary-orange) !important;
             color: white !important;
             box-shadow: 0 0 0 2px var(--primary-orange);
         }

         .desk_p2 { 
             padding: 0.5rem !important; 
         }

         .small_field {
             width: 64px !important;
             padding: 6px !important;
             height: 31px !important;
         }

         .two-line-ellipsis,
         .one-line-ellipsis {
             display: -webkit-box;
             -webkit-line-clamp: 2;
             -webkit-box-orient: vertical;
             overflow: hidden;
             text-overflow: ellipsis;
             word-break: break-word;
             height: 47px;
         }

         .one-line-ellipsis {
             -webkit-line-clamp: 1;
         }

         .cart_form {
             overflow-y: scroll;
             overflow-x: hidden;
             position: sticky;
             top: 60px;
             max-height: 90vh;
         }

         /* Custom Tab Navigation */
         .coffee-nav-tabs {
             border-bottom: none;
             margin-bottom: 30px;
         }

         .coffee-nav-link {
             background: none;
             border: none;
             color: var(--dark-gray);
             padding: 10px 20px;
             border-radius: 20px;
             margin-right: 10px;
             transition: all 0.3s ease;
             text-decoration: none;
             display: inline-block;

             background-color: rgba(255, 153, 102, 0.1);
             color: var(--primary-orange);
             text-decoration: none;
         }

         .coffee-nav-link.active {
             background-color: var(--primary-orange);
             color: white;
         }

         {{-- .coffee-nav-link:hover {
             background-color: rgba(255, 153, 102, 0.1);
             color: var(--primary-orange);
             text-decoration: none;
         } --}}

         /* Header */
         .coffee-header {
             display: flex;
             justify-content: space-between;

             margin-bottom: 30px;
         }

         .coffee-title {
             color: var(--text-dark);
             font-weight: 600;
             margin: 0;
         }

         .header-actions {
             display: flex;
             gap: 15px;
             align-items: center;
         }

         .header-btn {
             background-color: var(--text-dark);
             color: white;
             border: none;
             padding: 8px 16px;
             border-radius: 20px;
             font-size: 14px;
             cursor: pointer;
         }

         .header-text {
             color: var(--dark-gray);
             font-size: 14px;
         }


         /* Cart Sidebar */
         .coffee-cart {
             background: white;
             border-radius: 15px;
             padding: 10px 20px;
             margin: 4px;
             box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
             position: sticky;
             top: 20px;
         }

         .cart-title {
             color: var(--text-dark);
             margin: 0 0 20px 0;
             font-weight: 600;
         }

         .cart-item {
             display: flex;
             align-items: center;
             gap: 12px;
             padding: 10px 0;
             border-bottom: 1px solid #f0f0f0;
         }

         .cart-item:last-child {
             border-bottom: none;
         }

         .cart-item-image {
             width: 40px;
             height: 40px;
             border-radius: 8px;
             object-fit: cover;
             flex-shrink: 0;
         }

         .cart-item-details {
             flex: 1;
         }

         .cart-item-name {
             font-weight: 500;
             color: var(--text-dark);
             font-size: 14px;
             margin: 0 0 2px 0;
         }

         .cart-item-price {
             color: var(--dark-gray);
             font-size: 12px;
             margin: 0;
         }

         .cart-item-controls {
             display: flex;
             align-items: center;
             gap: 5px;
         }

         .cart-quantity-btn {
             background: none;
             border: 1px solid #ddd;
             width: 20px;
             height: 20px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             cursor: pointer;
             transition: all 0.3s ease;
             font-size: 12px;
         }

         .cart-quantity-btn:hover {
             background-color: var(--primary-orange);
             color: white;
             border-color: var(--primary-orange);
         }

         .cart-quantity-display {
             margin: 0 8px;
             font-size: 14px;
             min-width: 15px;
             text-align: center;
             pointer-events: none;
             border: none;
             width: 37px;
         }

         /* Cart Totals */
         .cart-totals {
             border-top: 2px solid #f0f0f0;
             padding-top: 10px;
             margin-top: 10px;
         }

         .total-row {
             display: flex;
             justify-content: space-between;
             margin-bottom: 5px;
             font-size: 14px;
         }

         .total-label {
             color: var(--dark-gray);
         }

         .total-amount {
             color: var(--dark-gray);
         }

         /* .final-total styles defined in design block below */

         .order-btn {
             background-color: var(--primary-orange);
             color: white;
             border: none;
             width: 100%;
             padding: 12px;
             border-radius: 25px;
             font-weight: 500;
             margin-top: 8px;
             cursor: pointer;
             transition: all 0.3s ease;
             font-size: 16px;
         }

         .order-btn:hover {
             background-color: #2cab85ff;
             transform: translateY(-1px);
         }

         /* Tab Content */
         .coffee-tab-content {
             display: none;
         }

         .coffee-tab-content.active {
             display: block;
         }

         .empty-tab {
             text-align: center;
             padding: 60px 20px;
         }

         .empty-tab-text {
             color: var(--dark-gray);
             font-size: 18px;
             margin: 0;
         }

         .cart_footer {
             position: fixed;
             bottom: 0px;
             border: 1px solid #ddd;
             z-index: 111;
             width: 100%;
             background: white;
             height: 52px;
             box-shadow: 0px -6px 10px #e6e6e6;
             display: none;
         }

         .add_item {
             border: 1px solid #e5e5e5;
             padding: 10px;
             border-top: 0;
             position: relative;
             z-index: 1;
             background: white;
             border-radius: 0 0 5px 5px;
             display: none;
         }

         .add_item a {
             font-weight: bold;
             color: #435de0 !important;
             width: 100%;
         }


         /* ── Header ── */
         .coffee-header {
             margin-bottom: 8px !important;
         }

         /* Take Away / Dine-In toggle - match branch select height */
         .order-type-toggle {
             margin: 0 !important;
             padding: 0 !important;
         }

         .order-type-toggle ul {
             margin: 0 !important;
             flex-wrap: nowrap !important;
         }

         .order-type-toggle ul li label span {
             height: 34px !important;
             padding: 0 16px !important;
             font-size: 13px !important;
             font-weight: 600;
             border-radius: 8px !important;
             display: inline-flex !important;
             align-items: center !important;
             justify-content: center !important;
             border: 1.5px solid #dee2e6 !important;
             color: #555 !important;
             background: white;
             transition: all 0.2s ease;
         }

         .order-type-toggle ul li label span:hover {
             border-color: var(--primary-orange) !important;
             color: var(--primary-orange) !important;
         }

         .order-type-toggle ul li label input:checked~span {
             background: var(--primary-orange) !important;
             color: #fff !important;
             border-color: var(--primary-orange) !important;
             box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
         }

         /* ── Cart Top Section (Customer + Order Type) ── */
         .cart-top-section {
             background: white;
             border-radius: 12px;
             padding: 10px 12px 6px;
             margin-bottom: 8px;
             box-shadow: 0 1px 6px rgba(0, 0, 0, 0.07);
         }

         .cart-top-section label {
             font-size: 11px;
             font-weight: 600;
             text-transform: uppercase;
             letter-spacing: 0.4px;
             color: #999;
             margin-bottom: 3px;
         }

         .cart-top-section .form-control {
             font-size: 13px;
             height: 34px;
             padding: 4px 8px;
         }

         .cart-top-section .add-customer-btn {
             height: 43px;
             width: fit-content;
             font-size: 11px;
             padding: 15px 7px;
             border-radius: 8px;
             white-space: nowrap;
             line-height: 1.2;
             flex-shrink: 0;
         }

         /* ── Cart Card ── */
         .coffee-cart {
             padding: 8px 10px !important;
             border-radius: 12px !important;
             box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08) !important;
         }

         /* ── Cart Items scrollbar ── */
         .inner_cart::-webkit-scrollbar {
             width: 4px;
         }

         .inner_cart::-webkit-scrollbar-track {
             background: #f1f1f1;
             border-radius: 4px;
         }

         .inner_cart::-webkit-scrollbar-thumb {
             background: #ccc;
             border-radius: 4px;
         }

         /* ── Totals rows ── */
         .cart-totals {
             border-top: 1px solid #f0f0f0 !important;
             padding-top: 6px !important;
         }

         .total-row {
             font-size: 12px;
             margin-bottom: 3px !important;
             align-items: center;
         }

         .total-label {
             font-size: 12px;
             color: #888;
         }

         /* ── Payment Block ── */
         .payment-block {
             background: #f8f9fa;
             border-radius: 10px;
             padding: 8px 10px;
             margin: 5px 0;
         }

         .payment-block-label {
             font-size: 10px;
             font-weight: 700;
             text-transform: uppercase;
             letter-spacing: 0.5px;
             color: #aaa;
             margin-bottom: 4px;
         }

         /* ── Payment Pills ── */
         .pos--payment-options ul {
             margin: 0 !important;
         }

         .pos--payment-options ul li label span {
             border-radius: 50px !important;
             padding: 5px 12px !important;
             font-size: 11px !important;
             font-weight: 500;
             transition: all 0.2s ease;
             border: 1.5px solid #dee2e6 !important;
             color: #555 !important;
             background: white;
             display: inline-block;
         }

         .pos--payment-options ul li label span:hover {
             border-color: var(--primary-orange) !important;
             color: var(--primary-orange) !important;
         }

         .pos--payment-options ul li label input:checked~span {
             background: var(--primary-orange) !important;
             color: #fff !important;
             border-color: var(--primary-orange) !important;
             box-shadow: 0 2px 5px rgba(0, 0, 0, 0.12);
         }

         .payment-status-options ul li label input[value="paid"]:checked~span {
             background: #28a745 !important;
             border-color: #28a745 !important;
         }

         .payment-status-options ul li label input[value="unpaid"]:checked~span {
             background: #dc3545 !important;
             border-color: #dc3545 !important;
         }

         /* ── Print Bill button ── */
         .order-btn {
             border-radius: 50px !important;
             font-size: 14px !important;
             font-weight: 600;
             letter-spacing: 0.3px;
             padding: 10px !important;
             box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
         }

         /* ── Final Total bar ── */
         .final-total {
             background: var(--primary-orange);
             border-radius: 10px;
             padding: 6px 10px;
             margin-top: 4px;
         }

         .final-total h2 {
             font-size: 14px !important;
             font-weight: 700;
             color: white !important;
             margin: 0;
         }

         .final-total .total-label,
         .final-total .total-amount {
             color: white !important;
         }

         /* Responsive */
     </style>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
     <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>


     <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2custom.css') }}">
     <link rel="stylesheet" href="{{ asset('public/assets/admin/css/') }}{{ $data['design'] }}?v={{ filemtime(public_path('assets/admin/css/' . $data['design'])) }}">
 @endpush
 
 @section('content')

     {{-- @include('vendor-views/sub-module/partials/salary') --}}
     <input type="hidden" id="current_order_from" value="{{ $token->order_from ?? '' }}">

     <div class="content container-fluid px-2">
         <div class="coffee-container">
             <div class="row w-100 g-0">
                 <!-- Category Sidenav Column -->


                 <!-- Main Content -->
                 <div class="col-lg-9 p-2 ">
                     <!-- Header -->
                     <div class="coffee-header mb-2">
                         <h2 class="coffee-title">Token Generate</h2>
                         <div class="d-flex gap-2 flex-wrap align-items-center">
                             <div class="pos--payment-options order-type-toggle m-0">
                                 <ul>
                                     <li>
                                         <label>
                                             <input type="radio" name="type" value="take_away" class="order_type"
                                                 hidden checked>
                                             <span>Take Away</span>
                                         </label>
                                     </li>
                                     <li>
                                         <label>
                                             <input type="radio" name="type" value="dine_in" class="order_type" hidden>
                                             <span>Dine-In</span>
                                         </label>
                                     </li>
                                 </ul>
                             </div>
                             {{-- @if (auth('vendor')->check()) --}}
                             <form action="" style="min-width: 144px;" class="d-flex gap-2">
                                 @if ($data['category_position'] == 'dropdown')
                                     <select class="form-control mx-1 js-select2-custom" id="category_id" name="category"
                                         onchange="this.form.submit()">
                                         <option value="">All Categories</option>
                                         @foreach ($data['categories'] as $key => $category)
                                             <option {{ request()->category == $category->id ? 'selected' : '' }}
                                                 value="{{ $category->id }}">{{ ucfirst($category->name) }}</option>
                                         @endforeach
                                     </select>
                                 @endif
                                 <select class="form-control mx-1 js-select2-custom" id="branch_id" name="branch"
                                     onchange="this.form.submit()">
                                     @foreach ($data['branches'] as $key => $branch)
                                         <option {{ request()->branch == $branch->id ? 'selected' : '' }}
                                             value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                                     @endforeach
                                 </select>
                             </form>
                             {{-- @endif --}}
                             <div class="search_inp">
                                 <div class="input-group">
                                     <input type="text" placeholder="Search Item Name" class="pos_search form-control">
                                     <button type="button"
                                         class="btn btn-white bg-light border outline-0 search-clear-btn">
                                         <i class="tio-search"></i>
                                     </button>
                                 </div>
                                 <div class="add_item">
                                     <a type="button" data-toggle="modal" data-target="#addPOSItemModal">
                                         <i class="tio-add"></i> Add Item
                                     </a>
                                 </div>
                             </div>
                             <button type="button" class="btn btn-outline-danger" onclick="resetCart()">
                                 <i class="tio-refresh mr-1"></i> Reset
                             </button>

                         </div>
                     </div>


                     {{-- ================= DINE IN PANEL ================= --}}
                     <div id="dineInPanel" class="card mb-3" style="display:none;">
                         <div class="card-body p-2">

                             <h6 class="mb-2">Select Table</h6>

                             <input type="hidden" name="table_id" form="cartForm" id="dine_table_id">
                             <input type="hidden" name="waiter_id" form="cartForm" id="dine_waiter_id">

                             <div class="row g-0">

                                 @foreach ($data['tables'] as $table)
                                     @php
                                         $isOccupied = $table->openOrder ? true : false;
                                     @endphp

                                     <div class="col-md-2 col-4 mb-2 p-2">

                                         <div class="card dine-table-card p-2 text-center
                                              {{ $table->status == 'occupied' ? 'border-danger' : 'border-success' }}"
                                             data-id="{{ $table->id }}" data-status="{{ $table->status }}">

                                             <strong>{{ $table->name }}</strong>
                                             <small>{{ strtoupper($table->room_type) }} |
                                                 {{ $table->capacity }}</small>

                                             <span
                                                 class="badge
                                                        {{ $table->status == 'free' ? 'badge-success' : 'badge-danger' }}">
                                                 {{ ucfirst($table->status) }}
                                             </span>

                                         </div>
                                     </div>
                                 @endforeach


                             </div>

                             <div class="form-group mt-3 mb-0">
                                 <label class="mb-1">Select Waiter</label>

                                 <select class="form-control" id="dine_waiter_select">
                                     <option value="">-- Select waiter --</option>
                                     @foreach ($data['waiters'] as $w)
                                         <option value="{{ $w->id }}">{{ $w->name }}</option>
                                     @endforeach
                                 </select>
                             </div>

                         </div>
                     </div>

                     <div class="coffee-tab-content active" id="tab_">
                         <div class="row">
                             @if ($data['category_position'] == 'sidenav')
                                 <div class="col-2">
                                     <div class="pos-category-sidenav" style="display:flex;flex-direction:column;gap:5px;">
                                         <button class="pos-cat-btn active" data-category="">All</button>
                                         @foreach ($data['categories'] as $category)
                                             <button class="pos-cat-btn"
                                                 data-category="{{ $category->id }}">{{ ucfirst($category->name) }}</button>
                                         @endforeach
                                     </div>
                                 </div>
                             @endif
                             <div class="col-10">
                                 @foreach ($data['branchWiseItems'] as $branchName => $items)
                                     <h2 class="card-title align-items-center d-flex mb-3">{{ ucfirst($branchName) }}
                                         Menu<span class="badge badge-soft-dark ml-2"
                                             id="itemCount">{{ count($items) }}</span></h2>

                                     <div class="row g-1 menu-container  rounded desk_p2"
                                         style="{{ $data['design_id'] == 4 ? 'display:none' : '' }}">
                                         @foreach ($items as $key => $item)
                                             <div class="item-card item_card item_{{ $item->id }}"
                                                 data-category-id="{{ $item->category_id }}">
                                                 <div class="coffee-menu-item">
                                                     <img style="cursor:pointer;"
                                                         onclick="addToCart('item', {{ $item->id }},'{{ $item->name }}', {{ $item->price }})"
                                                         src=" {{ \App\CentralLogics\Helpers::onerror_image_helper($item->image, asset('storage/app/public/inventory-item/') . '/' . $item->image, asset('public/assets/admin/img/100x100/2.jpg'), 'inventory-item/') }}"
                                                         alt="{{ $item->name }}"
                                                         class="menu-item-image img_item_{{ $item->id }}">
                                                     <div class="menu-item-content">
                                                         <div class="menu-item-header">
                                                             <h5 style="cursor:pointer;font-size: 16px;"
                                                                 onclick="addToCart('item', {{ $item->id }},'{{ $item->name }}', {{ $item->price }})"
                                                                 data-id="{{ $item->id }}"
                                                                 class="menu-item-title item_name two-line-ellipsis ">
                                                                 {{ ucfirst($item->name) }}</h5>
                                                             <span style="cursor:pointer;"
                                                                 onclick="addToCart('item', {{ $item->id }},'{{ $item->name }}', {{ $item->price }})"
                                                                 class="menu-item-price">₹ {{ $item->price }}</span>
                                                         </div>
                                                         @if ($item->qty_left < 5)
                                                             <span class="text-danger stock_span">Only
                                                                 {{ $item->qty_left }} Left
                                                             </span>
                                                         @else
                                                             <span class="text-success stock_span">{{ $item->qty_left }}
                                                                 In Stock
                                                             </span>
                                                         @endif

                                                         <small class="menu-item-description">
                                                             {{ $item->brand . ', ' . $item->model_number . ', ' . $item->sku_id }}
                                                         </small>

                                                         <div class="menu-item-actions">
                                                             {{-- <div class="size-selector ">
                                                         <div class="pos--payment-options p-0">
                                                             <ul style="flex-wrap: nowrap;">
                                                                 <li style="padding:0!important;">
                                                                     <label>
                                                                         <input type="radio" name="size" value="s"
                                                                             hidden checked>
                                                                         <span class="size_span">S</span>
                                                                     </label>
                                                                 </li>
                                                                 <li style="padding:0!important;">
                                                                     <label>
                                                                         <input type="radio" name="size" value="m"
                                                                             hidden>
                                                                         <span class="size_span">M</span>
                                                                     </label>
                                                                 </li>
                                                                 <li style="padding:0!important;">
                                                                     <label>
                                                                         <input type="radio" name="size" value="l"
                                                                             hidden>
                                                                         <span class="size_span">L</span>
                                                                     </label>
                                                                 </li>
                                                             </ul>
                                                         </div>
                                                     </div> --}}
                                                             <div class="quantity-selector">
                                                                 <button class="quantity-btn"
                                                                     data-id="{{ $item->id }}">-</button>
                                                                 <input type="number"
                                                                     class="quantity-display card_qty_{{ $item->id }}"
                                                                     name="" value="1">
                                                                 <button class="quantity-btn"
                                                                     data-id="{{ $item->id }}">+</button>
                                                             </div>

                                                             <button type="button"
                                                                 onclick="addToCart('item', {{ $item->id }},'{{ $item->name }}', {{ $item->price }})"
                                                                 class="add-cart-btn d-flex gap-1 align-items-center"><i
                                                                     class="fa-solid fa-cart-plus"></i><span
                                                                     class="d-none d-sm-block">
                                                                     Add</span>
                                                             </button>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         @endforeach
                                         @if (!count($items))
                                             <div class="empty-tab">
                                                 <h5 class="empty-tab-text">No items found...</h5>
                                             </div>
                                         @endif
                                     </div>
                                 @endforeach
                             </div>
                         </div>

                     </div>{{-- end coffee-tab-content --}}
                 </div>


                 <div class="col-lg-3">
                     <form action="{{ route('vendor.pos.token-generate') }}" method="post" class="cart_form"
                         id="cartForm">
                         @csrf
                         <input type="hidden" name="x_client" id="x_client_input" value="">
                         <input type="hidden" name="branch_id" value="{{ $branch_id ?? 0 }}">

                         {{-- <div class="d-flex align-items-center mb-3 bg-light p-2 shadow border flex-wrap">
                            

                           
                             <a href="" class="btn btn-outline-danger">
                                 <i class="tio-refresh mr-1"></i> Reset
                             </a>
                         </div> --}}

                         <div class="cart-top-section">
                             <div class="row g-1">
                                 {{-- Customer col --}}
                                 <div class="col-7">
                                     <label class="text-dark text-capitalize"
                                         for="customer">{{ translate('messages.customer') }}</label>
                                     <div class="d-flex gap-1 align-items-center">
                                         <select id='customer_id' name="customer_id" data-url="{{ url()->full() }}"
                                             data-filter="customer_id"
                                             data-placeholder="{{ translate('messages.select_customer') }}"
                                             class="form-control" title="{{ translate('messages.select_client') }}">
                                         </select>
                                         <button type="button" class="btn add-customer-btn flex-shrink-0"
                                             data-toggle="modal" data-target="#addCustomerModal">
                                             + Add New
                                         </button>
                                     </div>
                                 </div>

                                 {{-- Order Type col --}}
                                 <div class="col-5">
                                     <label class="text-dark text-capitalize" for="order_from">Order Type</label>
                                     <select id="itemSelect" name="order_from" class="form-control w-100">
                                         <option data-image="{{ asset('storage/app/public/util/walk-in.png') }}"
                                             value="Walk-in Order">Walk-in Order</option>
                                         @foreach ($data['order_from'] as $key => $value)
                                             <option
                                                 data-image="{{ asset('storage/app/public/store/order_type/' . $value->icon) }}"
                                                 value="{{ $value->name }}">{{ $value->name }}</option>
                                         @endforeach
                                         <option data-image="{{ asset('storage/app/public/util/on-call.png') }}"
                                             value="On Call">On Call</option>
                                         <option data-image="{{ asset('storage/app/public/util/swiggy.png') }}"
                                             value="Swiggy">Swiggy</option>
                                         <option data-image="{{ asset('storage/app/public/util/zomato.png') }}"
                                             value="Zomato">Zomato</option>
                                     </select>
                                 </div>
                             </div>
                         </div>
                         <div class="coffee-cart" id="cart">
                             {{-- <h4 class="cart-title">Cart</h4> --}}

                             <!-- Cart Items -->
                             <div class="inner_cart" ></div>

                             <!-- Totals -->
                             <div class="cart-totals">
                                 <div id="checkout-section">



                                     <div class="total-row ">
                                         <span class="total-label">Coupon:</span>
                                         <span class="total-amount">
                                             <div class="input-group ">
                                                 <input onkeyup="calculateTotals()"
                                                     class="small_field coupon_show coupon_inp form-control"
                                                     type="number" name="coupon" placeholder="Ex: 12">
                                                 <select onchange="calculateTotals()" name="coupon_type"
                                                     class="form-control small_field coupon_type" id="">
                                                     <option value="amount">₹</option>
                                                     <option value="percent">%</option>
                                                 </select>
                                             </div>
                                         </span>
                                     </div>
                                     <div class="total-row ">
                                         <span class="total-label">Discount:</span>
                                         <span class="total-amount">
                                             <div class="input-group ">
                                                 <input onkeyup="calculateTotals()"
                                                     class="small_field discount_show discount_inp form-control"
                                                     type="number" name="discount" placeholder="Ex: 12">
                                                 <select onchange="calculateTotals()" name="discount_type"
                                                     class="form-control small_field discount_type" id="">
                                                     <option value="percent">%</option>
                                                     <option value="amount">₹</option>
                                                 </select>
                                             </div>
                                         </span>
                                     </div>
                                     <div class="total-row ">
                                         <span class="total-label">Delivery:</span>
                                         <span class="total-amount">₹<input name="delivery" type="text"
                                                 onkeyup="calculateTotals()" class="delivery_show delivery_inp"
                                                 value="0"></span>
                                     </div>
                                     @if ($delivery_gst['status'] ?? 0)
                                         <div class="total-row ">
                                             <span class="total-label">Delivery GST Amount:</span>
                                             <input type="hidden" name="delivery_gst_percent"
                                                 class="delivery_gst_percent" value = "{{ $delivery_gst['percent'] }}">
                                             <span class="gst-amount">₹<span class="delivery_gst_amount">0</span>
                                         </div>
                                     @endif
                                     <div class="payment-block">
                                         <div class="d-flex flex-column gap-1">
                                             <div>
                                                 <p class="payment-block-label mb-1 mt-1">Payment Method</p>
                                                 <div class="pos--payment-options p-0">
                                                     <ul>
                                                         <li>
                                                             <label>
                                                                 <input type="radio" name="payment_method"
                                                                     value="cash" hidden checked>
                                                                 <span>Cash</span>
                                                             </label>
                                                         </li>
                                                         <li>
                                                             <label>
                                                                 <input type="radio" name="payment_method"
                                                                     value="bank" hidden>
                                                                 <span>Bank</span>
                                                             </label>
                                                         </li>
                                                         <li>
                                                             <label>
                                                                 <input type="radio" name="payment_method"
                                                                     value="upi" hidden>
                                                                 <span>Upi</span>
                                                             </label>
                                                         </li>
                                                         <li>
                                                             <label>
                                                                 <input type="radio" name="payment_method"
                                                                     value="cash_online" hidden>
                                                                 <span>Cash + Online</span>
                                                             </label>
                                                         </li>
                                                     </ul>
                                                 </div>
                                             </div>

                                             <div>
                                                 <p class="payment-block-label mb-1 mt-0">Status</p>
                                                 <div class="pos--payment-options payment-status-options mt-1 mb-2 p-0">
                                                     <ul>
                                                         <li>
                                                             <label>
                                                                 <input type="radio" name="payment_status"
                                                                     value="paid" hidden checked>
                                                                 <span>Paid</span>
                                                             </label>
                                                         </li>
                                                         <li>
                                                             <label>
                                                                 <input type="radio" name="payment_status"
                                                                     value="unpaid" hidden>
                                                                 <span>Unpaid</span>
                                                             </label>
                                                         </li>
                                                     </ul>
                                                 </div>
                                             </div>

                                         </div>
                                     </div>{{-- end payment-block --}}
                                     <div class="row">

                                         <div class="col-12 d-flex align-items-end">
                                             @php
                                                 $bankAccounts = collect($data['payment_accounts'] ?? [])->where(
                                                     'payment_type',
                                                     'bank',
                                                 );
                                                 $upiAccounts = collect($data['payment_accounts'] ?? [])->where(
                                                     'payment_type',
                                                     'upi',
                                                 );
                                                 $bankCount = $bankAccounts->count();
                                                 $upiCount = $upiAccounts->count();
                                             @endphp
                                             @if (($data['payment_accounts'] ?? collect())->count() > 0)
                                                 <div class="payment-detail-wrap payment-method-bank-wrap w-100"
                                                     style="display:none;">
                                                     @if ($bankCount > 1)
                                                         <select name="payment_detail_id_bank" class="form-control mb-2">
                                                             <option value="">Select Bank Account</option>
                                                             @foreach ($bankAccounts as $account)
                                                                 <option value="{{ $account->id }}">
                                                                     {{ $account->bank_name }} -
                                                                     {{ $account->account_number }}
                                                                 </option>
                                                             @endforeach
                                                         </select>
                                                     @elseif ($bankCount === 1)
                                                         <input type="hidden" name="payment_detail_id_bank"
                                                             value="{{ $bankAccounts->first()->id }}">
                                                         {{-- <div class="form-control mb-2 d-flex align-items-center">
                                                     {{ $bankAccounts->first()->bank_name }} -
                                                     {{ $bankAccounts->first()->account_number }}
                                                 </div> --}}
                                                     @else
                                                         <div class="small text-danger mb-2">No bank account found in POS
                                                             settings.</div>
                                                     @endif
                                                 </div>
                                                 <div class="payment-detail-wrap payment-method-upi-wrap w-100"
                                                     style="display:none;">
                                                     @if ($upiCount > 1)
                                                         <select name="payment_detail_id_upi" class="form-control mb-2">
                                                             {{-- <option value="">Select UPI ID</option> --}}
                                                             @foreach ($upiAccounts as $account)
                                                                 <option value="{{ $account->id }}">
                                                                     {{ $account->upi_id }}</option>
                                                             @endforeach
                                                         </select>
                                                     @elseif ($upiCount === 1)
                                                         <input type="hidden" name="payment_detail_id_upi"
                                                             value="{{ $upiAccounts->first()->id }}">
                                                         <div class="form-control mb-2 d-flex align-items-center">
                                                             {{ $upiAccounts->first()->upi_id }}
                                                         </div>
                                                     @else
                                                         <div class="small text-danger mb-2">No UPI ID found in POS
                                                             settings.
                                                         </div>
                                                     @endif
                                                 </div>
                                             @endif

                                             <div class="payment-detail-wrap payment-method-cash-online-wrap w-100"
                                                 style="display:none;">
                                                 <div class="d-flex gap-2 align-items-center mb-2">
                                                     <label class="mb-0 text-nowrap small">Cash ₹</label>
                                                     <input type="number" name="cash_amount" id="cash_amount_input"
                                                         class="form-control form-control-sm" placeholder="0"
                                                         min="0" step="0.01">
                                                     <label class="mb-0 text-nowrap small">Online ₹</label>
                                                     <input type="number" name="online_amount" id="online_amount_input"
                                                         class="form-control form-control-sm" placeholder="0"
                                                         min="0" step="0.01" readonly>
                                                 </div>
                                             </div>

                                         </div>
                                     </div>

                                     <div class="token_type "> 
                                         <div class="pos--payment-options my-1">
                                             <ul>
                                                 <li>
                                                     <label>
                                                         <input type="radio" name="token_type" value="token" hidden
                                                             checked>
                                                         <span>Token</span>
                                                     </label>
                                                 </li>
                                                 <li> 
                                                     <label>
                                                         <input type="radio" name="token_type" value="kitchen" hidden>
                                                         <span>Kitchen Token</span>
                                                     </label>
                                                 </li>
                                                 <li>
                                                     <label>
                                                         <input type="radio" name="token_type" value="both" hidden>
                                                         <span>Both</span>
                                                     </label>
                                                 </li>
                                             </ul>
                                         </div>
                                     </div>
                                 </div>



                             </div>
                             <div class="total-row">
                                 <span class="total-label">Subtotal:</span>
                                 <span class="total-amount">₹<span class="subtotal_show">0</span></span>
                             </div>
                             <div class="total-row ">
                                 <h2 class="total-label">Total:</h2>
                                 <h2 class="total-amount">₹<span class="total_show">0</span></h2>
                             </div>
                             <input type="hidden" name="token_id" id="current_token_id">
                             <button type="button" id="btn-print-bill" class="order-btn">Print Bill</button>
                             <button type="button" class="btn btn-primary" id="btn-start-dine-order"
                                 style="display:none">
                                 Start order
                             </button>
                             <button type="button" class="btn btn-primary" id="btn-close-dine-order"
                                 style="display:none">
                                 Close & Checkout
                             </button>
                         </div>
                     </form>
                 </div>
             </div>
         </div>
     </div>
     <div class="cart_footer ">
         <a href="#cart">
             <h3 class="text-danger"><i class="fa-solid fa-cart-shopping"></i> Go To Cart</h3>
         </a>
     </div>
     @include('vendor-views/form_modals/pos_item_modal')
 @endsection

 @push('script_2')
     <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
     <script>
         window.addEventListener('pageshow', function(event) {

             // fired even when coming back using browser back button
             if (event.persisted || performance.getEntriesByType("navigation")[0].type === "back_forward") {

                 // clear cart
                 resetCart();
             }

         });
     </script>
   
     <script>  
        // ══════════════════════════════════════════════════
        //  POS PRINTER CONFIG  (injected from PHP) 
        // ══════════════════════════════════════════════════
        const POS_PRINTER = {
            type:        '{{ $store_config?->printer_type ?? 'none' }}',
            ip:          '{{ $store_config?->printer_ip ?? '' }}',
            port:        {{ $store_config?->printer_port ?? 9100 }},
            name:        '{{ $store_config?->printer_name ?? '' }}',
            paperWidth:  '{{ $store_config?->printer_paper_width ?? '80mm' }}',
            autoPrint:   {{ ($store_config?->printer_auto_print ?? 0) ? 'true' : 'false' }},
        };  
 
        // ── Web Serial port reference (persisted across prints in same session) ──
        let _serialPort = null;  
        let _serialWriter = null;   
 
        /**
         * ESC/POS helpers — minimal set for receipt printing
         */
        const ESC = 0x1B, GS = 0x1D;
        function escposInit()  { return new Uint8Array([ESC, 0x40]); }
        function escposCut()   { return new Uint8Array([GS, 0x56, 0x41, 0x10]); }
        function escposAlign(n){ return new Uint8Array([ESC, 0x61, n]); }  // 0=L,1=C,2=R
        function escposText(str){
            const enc = new TextEncoder();
            return enc.encode(str + '\n');
        }
        function escposDoubleHeight(on){
            return new Uint8Array([ESC, 0x21, on ? 0x10 : 0x00]);
        } 
 
        /**   
         * Send raw bytes to a serial port (USB or Bluetooth-COM)
         */
        async function serialPrint(bytes, isAutoPrint = false) {
            try {
                if (!('serial' in navigator)) {
                    throw new Error('Web Serial API not supported. Use Chrome or Edge.');
                }

                // 1. If we already have an open port, use it
                if (_serialPort && _serialPort.readable && _serialPort.writable) {
                    try {
                        const writer = _serialPort.writable.getWriter();
                        await writer.write(bytes);
                        writer.releaseLock();
                        return true;
                    } catch (e) {
                        console.warn('Existing port failed, will try reconnect...', e);
                        _serialPort = null; // reset and try again 
                    }
                }

                // 2. See if we have previously authorized ports we can connect to silently
                if (!_serialPort && isAutoPrint) {
                    const ports = await navigator.serial.getPorts();
                    if (ports.length > 0) {
                        _serialPort = ports[0];
                    }
                }

                // 3. If still no port, we MUST request one.
                // Requesting a port REQUIRES a user gesture. If this was an auto-print (no click), it will fail.
                if (!_serialPort) {
                    if (isAutoPrint) {
                        // We cannot request port on page load without a click.
                        // Show a big button to the user requesting they click to print.
                        showManualPrintOverlay(bytes);
                        return false; 
                    } else {
                        _serialPort = await navigator.serial.requestPort();
                    }
                }

                // 4. Open the port if it's not open
                if (_serialPort && !_serialPort.readable) {
                    await _serialPort.open({ baudRate: 9600 });
                }

                // 5. Write data
                const writer = _serialPort.writable.getWriter();
                await writer.write(bytes);
                writer.releaseLock();
                return true;

            } catch (err) {
                console.error('Serial print error:', err);
                
                // If the error is "Failed to open serial port" it usually means it's exclusively locked
                if (err.message.includes('Failed to open serial port')) {
                    toastr.error('Printer is busy or turned off. Please check connection.', 'Printer Error');
                } else if (err.message.includes('No port selected')) {
                     // user cancelled prompt, ignore
                } else {
                    toastr.error(err.message || 'Serial print failed');
                }
                _serialPort = null; // force re-select next time
                return false;
            }
        }

        // Render a manual print button if auto-print was blocked by browser security
        function showManualPrintOverlay(bytes) {
            let overlay = document.getElementById('pos-manual-print');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'pos-manual-print';
                overlay.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#fff;padding:15px;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:9999;border-left:4px solid var(--primary-orange); display:flex; align-items:center; gap:15px;';
                
                const text = document.createElement('div');
                text.innerHTML = '<b>Receipt Ready</b><br><small class="text-muted">Click to connect printer</small>';
                
                const btn = document.createElement('button');
                btn.className = 'btn btn-primary btn-sm';
                btn.innerHTML = '<i class="tio-print"></i> Print Now';
                btn.onclick = async () => {
                    const ok = await serialPrint(bytes, false); // false = triggered by user click
                    if (ok) overlay.remove();
                };

                const close = document.createElement('button');
                close.className = 'btn btn-ghost-danger btn-sm px-2';
                close.innerHTML = '<i class="tio-clear"></i>';
                close.onclick = () => overlay.remove();

                overlay.appendChild(text);
                overlay.appendChild(btn);
                overlay.appendChild(close);
                document.body.appendChild(overlay);
            }
        }

        /**
         * Build a minimal ESC/POS receipt from the current cart total
         * (Used for USB/Bluetooth direct print)
         */
        function buildEscPosReceipt(pdfUrl) {
            const storeName = '{{ \App\CentralLogics\Helpers::get_store_data()?->name ?? 'Store' }}';
            const total     = document.querySelector('.total_show')?.textContent?.trim() || '0';
            const tokenNum  = document.querySelector('#current_token_id')?.value || '';
            const date      = new Date().toLocaleString('en-IN', { hour12: true });
            const width     = POS_PRINTER.paperWidth === '58mm' ? 32 : 48;
            const line      = '-'.repeat(width);

            const chunks = [
                escposInit(),
                escposAlign(1),              // center
                escposDoubleHeight(true),
                escposText(storeName),
                escposDoubleHeight(false),
                escposText(date),
                escposAlign(0),              // left
                escposText(line),
                escposText('Token : ' + tokenNum),
                escposText(line),
                escposAlign(2),              // right
                escposDoubleHeight(true),
                escposText('Total: Rs. ' + total),
                escposDoubleHeight(false),
                escposAlign(1),              // center
                escposText('\n\nThank You! Visit Again!\n\n'),
                escposCut(),
            ];

            const totalLen = chunks.reduce((s, c) => s + c.length, 0);
            const merged   = new Uint8Array(totalLen);
            let offset = 0;
            chunks.forEach(c => { merged.set(c, offset); offset += c.length; });
            return merged;
        }

        var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

        function printPdf(url) {
            var frame = document.createElement('iframe');
            frame.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:1px;height:1px;';
            frame.src = url;
            document.body.appendChild(frame);
            frame.onload = function() {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch(e) {
                    window.open(url, '_blank');
                }
            };
        }

        function printHtml(htmlContent, fallbackUrl) {
            var printWin = window.open('', '_blank');
            if (!printWin) {
                window.open(fallbackUrl, '_blank');
                return;
            }
            var baseStyle = '<style>' +
                'html,body{margin:0!important;padding:0!important;}' +
                '.ticket{padding:2mm!important;margin:0!important;}' +
                '@media print{@page{margin:0;}}' +
                '<' + '/style>';
            var headClose = '<' + '/head>';
            var modifiedHtml = htmlContent;
            if (modifiedHtml.indexOf(headClose) !== -1) {
                modifiedHtml = modifiedHtml.replace(headClose, baseStyle + headClose);
            } else {
                modifiedHtml = baseStyle + modifiedHtml;
            }
            printWin.document.write(modifiedHtml);
            printWin.document.close();
            printWin.onload = function() {
                printWin.focus();
                printWin.print();
            };
        }

        async function printInvoice(url, isAutoPrint = false, htmlData = null) {
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(
                    JSON.stringify({ type: 'PRINT_INVOICE', url: url })
                );
                return;
            }

            const type = POS_PRINTER.type;
            const autoConfig = POS_PRINTER.autoPrint;

            if (isAutoPrint && !autoConfig && type !== 'none') {
                 return;
            }

            if (type === 'bluetooth') {
                const receipt = buildEscPosReceipt(url);
                const ok = await serialPrint(receipt, isAutoPrint);
                if (!ok) window.open(url, '_blank');
                return;
            }

            if (isMobile && htmlData) {
                var mainHtml = (typeof htmlData === 'object' && htmlData !== null) ? htmlData.main : htmlData;
                if (mainHtml) {
                    printHtml(mainHtml, url);
                } else {
                    window.open(url, '_blank');
                }
                return;
            }

            printPdf(url);
        }

        // ── Auto-trigger on page load if token was just generated ──
        @if (session('pdf_url'))
            document.addEventListener('DOMContentLoaded', function() {
                var htmlData = {!! json_encode(session('print_html_array', null)) !!};
                printInvoice("{{ session('pdf_url') }}", true, htmlData);
            });
        @endif
    </script>

     @if ($data['category_position'] == 'sidenav')
         <script>
             $(document).on('click', '.pos-cat-btn', function() {
                 $('.pos-cat-btn').removeClass('active');
                 $(this).addClass('active');
                 const catId = $(this).data('category');
                 if (!catId) {
                     $('.item_card').show();
                 } else {
                     $('.item_card').each(function() {
                         $(this).toggle($(this).data('category-id') == catId);
                     });
                 }
             });
         </script>
     @endif
     <script>
         function togglePaymentMethodDetails() {
             const method = $('input[name="payment_method"]:checked').val();
             $('.payment-method-bank-wrap').hide();
             $('.payment-method-upi-wrap').hide();
             $('.payment-method-cash-online-wrap').hide();
             if (method === 'bank') {
                 $('.payment-method-bank-wrap').show();
             } else if (method === 'upi') {
                 $('.payment-method-upi-wrap').show();
             } else if (method === 'cash_online') {
                 $('.payment-method-cash-online-wrap').show();
                 syncCashOnlineSplit();
             }
         }
         $(document).on('change', 'input[name="payment_method"]', togglePaymentMethodDetails);
         togglePaymentMethodDetails();

         function syncCashOnlineSplit() {
             const total = parseFloat($('.total_show').text().replace(/[^0-9.]/g, '')) || 0;
             const cash = parseFloat($('#cash_amount_input').val()) || 0;
             $('#online_amount_input').val(Math.max(0, total - cash).toFixed(2));
         }

         $(document).on('input', '#cash_amount_input', function() {
             const total = parseFloat($('.total_show').text().replace(/[^0-9.]/g, '')) || 0;
             let cash = parseFloat($(this).val()) || 0;
             if (cash > total) {
                 cash = total;
                 $(this).val(cash.toFixed(2));
             }
             $('#online_amount_input').val(Math.max(0, total - cash).toFixed(2));
         });  

         $('.order-btn').on('click', function() {
             let orderType = $('input[class="order_type"]:checked').val();

             if (orderType === 'dine_in') {
                 if (!$('#dine_table_id').val()) {
                     toastr.error('Please select table');
                     return false;
                 }
                 if (!$('#dine_waiter_id').val()) {
                     toastr.error('Please select waiter');
                     return false;
                 }
             }

             if ($('.price-display').length) {
                 $('.order_type[value="take_away"]').prop('checked', true);
                 $("#dineInPanel").hide();
                 $('#x_client_input').val(window.ReactNativeWebView ? 'app' : 'web');

                 var btn = $(this);
                 btn.prop('disabled', true);

                 $.ajax({
                     url: $("#cartForm").attr('action'),
                     type: 'POST',
                     data: $("#cartForm").serialize(),
                     headers: {
                         'X-Requested-With': 'XMLHttpRequest'
                     },
                     success: function(res) {
                         if (res.success) {
                             toastr.success('Token generated successfully!');
                             printInvoice(res.pdf_url, true, res.print_html_array);
                             resetCart();
                         } else {
                             toastr.error('Failed to generate token');
                         }
                     },
                     error: function(xhr) {
                         var msg = 'Something went wrong';
                         if (xhr.responseJSON) {
                             if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                             if (xhr.responseJSON.errors) {
                                 msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                             }
                         }
                         toastr.error(msg);
                     },
                     complete: function() {
                         btn.prop('disabled', false);
                     }
                 });
             } else {
                 toastr.error('Please add atleast one item');
             }

         });


         document.addEventListener('click', function(e) {
             if (e.target.classList.contains('coffee-nav-link')) {
                 e.preventDefault();

                 document.querySelectorAll('.coffee-nav-link').forEach(link => {
                     link.classList.remove('active');
                 });

                 document.querySelectorAll('.coffee-tab-content').forEach(content => {
                     content.classList.remove('active');
                 });

                 e.target.classList.add('active');

                 const tabId = e.target.getAttribute('data-tab');
                 document.getElementById(tabId).classList.add('active');
             }

             // Quantity control functionality
             if (e.target.classList.contains('quantity-btn') || e.target.classList.contains('cart-quantity-btn')) {
                 const isIncrement = e.target.textContent === '+';
                 const quantitySpan = isIncrement ?
                     e.target.previousElementSibling :
                     e.target.nextElementSibling;
                 let id = e.target.getAttribute('data-id');
                 let currentQty = parseInt(quantitySpan.value);
                 console.log('quantity-btn')
                 if (isIncrement) {
                     currentQty++;
                 } else if (currentQty > 0) {
                     currentQty--;
                     if (currentQty === 0) {
                         $('[data-item-id="' + id + '"]').remove();
                     }
                 }
                 quantitySpan.value = currentQty;
                 $(".cart_qty_" + id).val(currentQty)

                 let $cartItem = $('.cart-item_' + id);

                 let item_price = parseFloat($cartItem.find('.item_price').val()) || 0;
                 currentQty = parseInt($cartItem.find('.quantity-display').val()) || 0;

                 $cartItem.find('.price-display').text((currentQty * item_price).toFixed(3));
                 console.log(currentQty)
                 if (currentQty > 0) {
                     $(".card_qty_" + id).val(currentQty)
                 }

                 calculateTotals()
             }

             // Add to cart animation
             if (e.target.classList.contains('add-cart-btn')) {
                 e.target.style.transform = 'scale(0.95)';
                 setTimeout(() => {
                     e.target.style.transform = 'scale(1)';
                 }, 150);
             }
         });

         function addToCart(type, item_id, item_name, item_price) {
             {{-- $(".inner_cart").show(); --}}
             var qty = $('.card_qty_' + item_id).val();
             var length = $('[data-item-id="' + item_id + '"]').length;
             if (length) {
                 $('.cart-quantity-btn[data-id="' + item_id + '"][data-action="increase"]').click(); // increase qty 
                 return false;
             }
             var src = $(".img_" + type + '_' + item_id).attr('src');
             var cartHtml = ` <div class="cart-item cart-item_${item_id}" data-item-id="${item_id}">
             <input type="hidden" name="item_id[]" value="${item_id}">
             <input type="hidden" name="item_type[]" value="${type}">
             <input type="hidden" name="unit_price[]" class="item_price" value="${item_price}">
                    <img src="${src}" alt="${item_name}" class="cart-item-image">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item_name}</div>
                        <div class="cart-item-price">₹ <span class="price-display">${(item_price * qty).toFixed(3)}</span> </div>
                    </div>
                    <div class="cart-item-controls">
                        <button type="button" class="cart-quantity-btn" data-id="${item_id}" data-action="decrease">-</button>
                       <input type="number" class="quantity-display cart_qty_${item_id} item_qty" name="item_qty[]" value="${qty}">
                        <button type="button" class="cart-quantity-btn" data-id="${item_id}" data-action="increase">+</button>
                    </div>
                </div>`;
             $(".inner_cart").append(cartHtml);
             calculateTotals();

         }

         function calculateTotals() {

             let subtotal = 0;

             $(".price-display").each(function() {
                 subtotal += parseFloat($(this).text()) || 0;
             });

             let delivery = parseFloat($(".delivery_show").val()) || 0;
             let coupon = parseFloat($(".coupon_show").val()) || 0;
             let coupon_type = $(".coupon_type").val();
             let discount_amount = coupon_amount = 0;
             let total = subtotal + delivery;

             if (coupon) {
                 if (coupon_type === "percent") {
                     coupon_amount = (total * coupon / 100);
                 } else {
                     coupon_amount = coupon;
                 }
             }
             let discount = parseFloat($(".discount_show").val()) || 0;
             let discount_type = $(".discount_type").val();
             if (discount) {
                 if (discount_type === "percent") {
                     discount_amount = (total * discount / 100);
                 } else {
                     discount_amount = discount;
                 }
             }

             total = subtotal + delivery - coupon_amount - discount_amount;

             var delivery_gst_percent = parseFloat($('.delivery_gst_percent').val()) || 0;

             var delivery_gst_amount = (delivery * delivery_gst_percent) / 100;

             total += delivery_gst_amount;

             $(".subtotal_show").text(subtotal.toFixed(3));
             $(".delivery_gst_amount").text(delivery_gst_amount.toFixed(3));
             $(".total_show").text(total.toFixed(3));

             // keep Cash + Online split in sync whenever totals change
             if ($('input[name="payment_method"]:checked').val() === 'cash_online') {
                 syncCashOnlineSplit();
             }

         }
         $(".pos_search").on("keyup", function() {
             var searchTerm = $(this).val().toLowerCase();
             var matched = 0;
             $(".item_card").each(function() {
                 var itemName = $(this).find(".item_name").text().toLowerCase();

                 if (itemName.indexOf(searchTerm) > -1) {
                     $(this).show(); // match found → show
                     matched++;
                 } else {
                     $(this).hide(); // no match → hide
                 }
             });

             let $btn = $(".search-clear-btn i");
             if (searchTerm.length && matched) {
                 $('.menu-container').show();
             } else {
                 $('.menu-container').hide();
             }
             if ($(this).val().length > 0) {
                 $btn.removeClass("tio-search").addClass("tio-clear"); // change icon
                 $(".search-clear-btn").css("pointer-events", "auto"); // enable click
             } else {
                 $btn.removeClass("tio-clear").addClass("tio-search"); // revert icon
                 $(".search-clear-btn").css("pointer-events", "none"); // disable click
             }
         });
         $(".search-clear-btn").on("click", function() {
             $(".pos_search").val("").trigger("keyup").trigger("input"); // clear & reset
         });

         $(".customer_add_form").on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData($(this).get(0));
             formData.append('form_type', 'ajax');

             $.ajaxSetup({
                 headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                 }
             });
             $.ajax({
                 url: $(this).attr('action'),
                 type: 'POST',
                 data: formData,
                 processData: false,
                 contentType: false,
                 success: function(data) {
                     if (data.status) {
                         $('#addCustomerModal').modal('hide')

                         toasterNotification(data.msg)

                         // Suppose your AJAX returns new customer details in data.customer
                         let newOption = new Option(
                             data.customer.f_name + ' (' + data.customer.phone + ')',
                             data.customer.id,
                             true, // selected
                             true // defaultSelected
                         );

                         // Append new option to select
                         $('#customer_id').append(newOption).trigger('change');


                     } else if (data.errors) {
                         for (let i = 0; i < data.errors.length; i++) {
                             toasterNotification(data.errors[i])

                         }
                     }
                 }
             });
         });


         function resetCart() {
             $(".inner_cart").html('');
             $(".quantity-display").val(1)
             $(".total_show").text(0)
             $(".subtotal_show").text(0)
             $(".delivery_inp").val(0)
             $('#customer_id').val('walk_in').trigger('change');
             $('#itemSelect').val('Walk-in Order').trigger('change');
             {{-- $("#branch_id").val(null).trigger('change'); --}}
         }
     </script>
     <script>
         $('#itemSelect').select2({
             // tags: true, // allow custom tags
             templateResult: formatOption,
             templateSelection: formatSelection
         });

         function formatOption(option) {
             if (!option.id) {
                 return option.text;
             }

             let image = $(option.element).data('image');

             // For dynamically added tags (no image)
             if (!image) {
                 return $('<span>' + option.text + '</span>');
             }

             return $(
                 '<span><img src="' + image + '" style="width:30px;height:30px;margin-right:8px;border-radius:4px;"/> ' +
                 option.text +
                 '</span>'
             );
         }

         function formatSelection(option) {
             let image = $(option.element).data('image');
             if (!image) {
                 return option.text;
             }

             return $(
                 '<span><img src="' + image + '" style="width:20px;height:20px;margin-right:5px;border-radius:4px;"/> ' +
                 option.text +
                 '</span>'
             );
         }
         $(".pos_search").on('focus', function() {
             $(".add_item").show()
         });
         {{-- $(document).on('click', function(e) {
             // if the clicked element is NOT inside .search_inp
             if (!$(e.target).closest('.search_inp').length) {
                 $(".add_item").hide();
             }
         }); --}}
         window.selectedTableId = null;
         window.currentDineTokenId = null;


         $('#btn-close-dine-order').on('click', function() {
             $("#checkout-section").show();
             $('#btn-start-dine-order').hide();
             $('#btn-close-dine-order').hide();

             $('#token_id').val()

         });

         $('#btn-start-dine-order').on('click', function() {
             let waiterId = $('#dine_waiter_id').val();
             let table_id = window.selectedTableId;

             if (!table_id) {
                 toastr.error('Please select table');
                 return;
             }

             if (!waiterId) {
                 toastr.error('Please select waiter');
                 return;
             }

             // Check if at least one item is selected
             let items = $('input[name="item_id[]"]').map(function() {
                 return $(this).val();
             }).get();

             if (items.length === 0) {
                 toastr.error('Please select at least one item');
                 return;
             }

             let form = $('#cartForm');
             let formDataArray = form.serializeArray(); // all form data

             // Add table, waiter, and CSRF manually
             formDataArray.push({
                 name: 'table_id',
                 value: table_id
             });
             formDataArray.push({
                 name: 'waiter_id',
                 value: waiterId
             });
             formDataArray.push({
                 name: '_token',
                 value: '{{ csrf_token() }}'
             });

             // Start new order
             $.ajax({
                 url: "{{ route('vendor.pos.dinein.open') }}",
                 type: "POST",
                 data: $.param(formDataArray),
                 success: function(res) {
                     console.log(res);
                     $('#btn-start-dine-order').text('Update Order');
                     $("#btn-close-dine-order").show();
                     $("#current_token_id").val(res.order_id)

                     toastr.success('Order started successfully');
                 },
                 error: function(xhr) {
                     console.log(xhr.responseText);
                     toastr.error('Something went wrong');
                 }
             });
         });


         function updateDineOrder(tokenId) {

             let form = $('#cartForm');
             let data = form.serialize();
             data += '&token_id=' + tokenId;

             $.post("{{ route('vendor.pos.dinein.update') }}", data, function(res) {

                 if (res.status) {
                     toastr.success('Order updated');
                 } else {
                     toastr.error('Failed to update order');
                 }

             });
         }



         function handleOrderTypeUI() {

             let val = $('input[class="order_type"]:checked').val();

             if (val === 'dine_in') {
                 $('#dineInPanel').slideDown();

                 $('#checkout-section').hide();
                 $('#btn-start-dine-order').show();


             } else {
                 $('#checkout-section').show();
                 $('#btn-start-dine-order').hide();
                 $('#dineInPanel').slideUp();

                 $('#dine_table_id').val('');
                 $('#dine_waiter_id').val('');
                 $('.dine-table-card').removeClass('active');
                 $('#dine_waiter_select').val('');

             }
         }


         $(document).on('change', 'input[class="order_type"]', handleOrderTypeUI);

         $(document).ready(handleOrderTypeUI);


         // toggle dine in panel
         $('input[class="order_type"]').on('change', function() {

             if ($(this).val() === 'dine_in') {



             } else {

             }

         });
         $(function() {

             let val = $('input[class="order_type"]:checked').val();

             if (val === 'dine_in') {
                 $('#btn-start-dine-order').show();
                 $('#btn-close-dine-order').show();
                 $('#dine-table-section').show();
             } else {
                 $('#btn-start-dine-order').hide();
                 $('#btn-close-dine-order').hide();
                 $('#dine-table-section').hide();
             }

         });

         // table select
         $(document).on('click', '.dine-table-card', function(e) {

             e.preventDefault();
             e.stopPropagation();
             e.stopImmediatePropagation();

             let tableId = $(this).data('id');

             window.selectedTableId = tableId;

             $('.dine-table-card').removeClass('active');
             $(this).addClass('active');

             $("#checkout-section").hide();

             $('#dine_table_id').val(tableId);
             loadDineInTable(tableId);

             return false;
         });



         function loadDineInTable(tableId) {
             $.get("{{ route('vendor.pos.dinein.table-state') }}", {
                 table_id: tableId
             }, function(res) {

                 if (res.has_order) {

                     fillCartFromOrder(res.order);
                     $("#dine_waiter_select").val(res.order.waiter_id)
                     $("#dine_waiter_id").val(res.order.waiter_id)

                     //  VERY IMPORTANT
                     window.currentDineTokenId = res.order.id;
                     $("#current_token_id").val(currentDineTokenId)

                     $('#btn-start-dine-order').text('Update Order');
                     $('#btn-close-dine-order').show();

                 } else {

                     resetCart();
                     $("#dine_waiter_select").val('')
                     $("#dine_waiter_id").val('')


                     //  clear token
                     window.currentDineTokenId = null;
                     $('#btn-close-dine-order').hide();
                     $('#btn-start-dine-order').text('Start Order');
                 }

             });
         }



         function fillCartFromOrder(order) {
             console.log('fd');
             $('.inner_cart').html('');

             order.token_items.forEach(function(item) {

                 let html = `
        <div class="cart-item cart-item_${item.item_id}" data-item-id="${item.item_id}">
            <input type="hidden" name="item_id[]" value="${item.item_id}">
            <input type="hidden" name="item_type[]" value="item">
            <input type="hidden" name="unit_price[]" class="item_price" value="${item.unit_price}">
            <img src="${item.image}" class="cart-item-image">

            <div class="cart-item-details">
                <div class="cart-item-name">${item.item_name}</div>
                <div class="cart-item-price">₹
                    <span class="price-display">${(item.unit_price*item.qty).toFixed(3)}</span>
                </div>
            </div>

            <div class="cart-item-controls">
                <button type="button" class="cart-quantity-btn" data-id="${item.item_id}" data-action="decrease">-</button>
                <input type="number" class="quantity-display cart_qty_${item.item_id} item_qty"
                       name="item_qty[]" value="${item.qty}">
                <button type="button" class="cart-quantity-btn" data-id="${item.item_id}" data-action="increase">+</button>
            </div>
        </div>
        `;

                 $('.inner_cart').append(html);
             });

             calculateTotals();
         }



         // waiter select
         $('#dine_waiter_select').on('change', function() {
             $('#dine_waiter_id').val($(this).val());
         });
     </script>
     @include('vendor-views/js/pos_items_js')
 @endpush
