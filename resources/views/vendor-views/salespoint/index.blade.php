 @extends('layouts.vendor.app')

 @section('title', 'POS Token')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
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
             padding: 20px;
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
             padding-top: 15px;
             margin-top: 15px;
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

         .final-total {
             font-weight: bold;
             color: var(--text-dark);
         }

         .final-total .total-label,
         .final-total .total-amount {
             color: var(--text-dark);
             font-weight: bold;
         }

         .order-btn {
             background-color: var(--primary-orange);
             color: white;
             border: none;
             width: 100%;
             padding: 12px;
             border-radius: 25px;
             font-weight: 500;
             margin-top: 20px;
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

         /* Responsive */
     </style>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
     <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>


     <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2custom.css') }}">
     <link rel="stylesheet" href="{{ asset('public/assets/admin/css/') }}{{ $data['design'] }}">
 @endpush

 @section('content')

     {{-- @include('vendor-views/sub-module/partials/salary') --}}

     <div class="content container-fluid px-2">
         <div class="coffee-container">
             <div class="row w-100 g-0">
                 <!-- Main Content -->
                 <div class="col-lg-8 p-2 ">
                     <!-- Header -->
                     <div class="coffee-header mb-2">
                         <h2 class="coffee-title">Token Generate</h2>
                         <div class="d-flex gap-2 flex-wrap align-items-start">

                             @if (auth('vendor')->check())
                                 <form action="" style="min-width: 144px;" class="d-flex">
                                     <select class="form-control mx-1 js-select2-custom" id="branch_id" name="branch"
                                         onchange="this.form.submit()">
                                         @foreach ($data['branches'] as $key => $branch)
                                             <option {{ request()->branch == $branch->id ? 'selected' : '' }}
                                                 value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                                         @endforeach
                                     </select>
                                 </form>
                             @endif
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

                         </div>
                     </div>

                     <div class="coffee-tab-content active" id="tab_">
                         @foreach ($data['branchWiseItems'] as $branchName => $items)
                             <h2 class="card-title align-items-center d-flex mb-3">{{ ucfirst($branchName) }} Menu<span
                                     class="badge badge-soft-dark ml-2" id="itemCount">{{ count($items) }}</span></h2>

                             <div class="row g-1 menu-container  rounded desk_p2"
                                 style="{{ $data['design_id'] == 4 ? 'display:none' : '' }}">
                                 @foreach ($items as $key => $item)
                                     <div class="item-card item_card item_{{ $item->id }}">
                                         <div class="coffee-menu-item">
                                             <img style="cursor:pointer;"
                                                 onclick="addToCart('item', {{ $item->id }},'{{ $item->name }}', {{ $item->price }})"
                                                 src=" {{ \App\CentralLogics\Helpers::onerror_image_helper($item->image, asset('storage/app/public/inventory-item/') . '/' . $item->image, asset('public/assets/admin/img/100x100/2.jpg'), 'inventory-item/') }}"
                                                 alt="{{ $item->name }}"
                                                 class="menu-item-image img_item_{{ $item->id }}">
                                             <div class="menu-item-content">
                                                 <div class="menu-item-header">
                                                     <h5 style="cursor:pointer;"
                                                         onclick="addToCart('item', {{ $item->id }},'{{ $item->name }}', {{ $item->price }})"
                                                         data-id="{{ $item->id }}"
                                                         class="menu-item-title item_name two-line-ellipsis ">
                                                         {{ ucfirst($item->name) }}</h5>
                                                     <span style="cursor:pointer;"
                                                         onclick="addToCart('item', {{ $item->id }},'{{ $item->name }}', {{ $item->price }})"
                                                         class="menu-item-price">{{ _price($item->price) }}</span>
                                                 </div>
                                                 @if ($item->qty_left < 5)
                                                     <span class="text-danger stock_span">Only {{ $item->qty_left }} Left
                                                     </span>
                                                 @else
                                                     <span class="text-success stock_span">{{ $item->qty_left }} In Stock
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


                 <div class="col-lg-4">
                     <form action="{{ route('vendor.pos.token-generate') }}" method="post" id="cartForm">
                         @csrf
                         <input type="hidden" name="branch_id" value="{{ $branch_id ?? 0 }}">

                         <div class="d-flex align-items-center mb-3 bg-light p-2 shadow border flex-wrap">
                             <label class="mb-0 mr-2 font-weight-bold" style="white-space: nowrap;">Token Number</label>
                             <i class="tio-edit text-primary mr-2" style="cursor:pointer;"></i>

                             <div class="input-group input-group-sm" style="width: auto; width: 150px;">
                                 <input maxlength="3" type="text" name="token_prefix"
                                     class="form-control text-center font-weight-bold" value="TKN"
                                     style="max-width:60px;    height: 41px !important; border-right:0px !important; ">
                                 <span class="input-group-text bg-light font-weight-bold">
                                     {{ $data['upcoming_number'] }}
                                 </span>
                             </div>

                             <button type="button" class="btn btn-outline-danger" onclick="resetCart()">
                                 <i class="tio-refresh mr-1"></i> Reset
                             </button>
                             {{-- <a href="" class="btn btn-outline-danger">
                                 <i class="tio-refresh mr-1"></i> Reset
                             </a> --}}
                         </div>

                         {{-- select customemr  --}}
                         <div class="mb-2 row align-items-end">
                             <div class="col-md-6 px-1">
                                 <label class="text-dark text-capitalize"
                                     for="customer">{{ translate('messages.customer') }}</label>
                                 <select id='customer_id' name="customer_id" data-url="{{ url()->full() }}"
                                     data-filter="customer_id"
                                     data-placeholder="{{ translate('messages.select_customer') }}"
                                     class="form-control w-100" title="{{ translate('messages.select_client') }}">
                                 </select>
                             </div>
                             <div class="col-md-6 px-1 mt-1">
                                 <button type="button" class="btn add-customer-btn mb-1 w-100" data-toggle="modal"
                                     data-target="#addCustomerModal">
                                     + Add New Client
                                 </button>
                             </div>
                         </div>






                         <div class="mb-2">
                             <label class="text-dark text-capitalize" for="customer">Order Type</label>
                             {{-- <select data-placeholder="Type new or select" name="order_from"
                                 class="js-select2-custom-tags">
                                 <option value=""></option>
                                 @foreach ($data['order_from'] as $key => $value)
                                     <option value="{{ $value->name }}">{{ $value->name }}</option>
                                 @endforeach
                                 <option value="Walk-in Order">Walk-in Order</option>
                                 <option value="On Call">On Call</option>
                                 <option value="Swiggy">Swiggy</option>
                                 <option value="Zomato">Zomato</option>
                             </select> --}}
                             <select id="itemSelect" name="order_from" class="form-control">
                                 <option data-image="{{ asset('storage/app/public/util/walk-in.png') }}"
                                     value="Walk-in Order">Walk-in Order</option>
                                 @foreach ($data['order_from'] as $key => $value)
                                     <option
                                         data-image="{{ asset('storage/app/public/store/order_type/' . $value->icon) }}"
                                         value="{{ $value->name }}">{{ $value->name }}</option>
                                 @endforeach

                                 <option data-image="{{ asset('storage/app/public/util/on-call.png') }}" value="On Call">
                                     On Call</option>
                                 <option data-image="{{ asset('storage/app/public/util/swiggy.png') }}" value="Swiggy">
                                     Swiggy</option>
                                 <option data-image="{{ asset('storage/app/public/util/zomato.png') }}" value="Zomato">
                                     Zomato</option>
                             </select>
                         </div>
                         <div class="coffee-cart" id="cart">
                             <h4 class="cart-title">Cart</h4>

                             <!-- Cart Items -->
                             <div class="inner_cart">
                             </div>

                             <!-- Totals -->
                             <div class="cart-totals">
                                 <div class="pos--payment-options mt-3 mb-3">
                                     <p class="mb-3">{{ translate('paid_By') }}</p>
                                     <ul>
                                         <li>
                                             <label>
                                                 <input type="radio" name="payment_method" value="cash" hidden
                                                     checked>
                                                 <span>Cash</span>
                                             </label>
                                         </li>
                                         <li>
                                             <label>
                                                 <input type="radio" name="payment_method" value="card" hidden>
                                                 <span>Card</span>
                                             </label>
                                         </li>
                                         <li>
                                             <label>
                                                 <input type="radio" name="payment_method" value="upi" hidden>
                                                 <span>Upi</span>
                                             </label>
                                         </li>
                                     </ul>
                                 </div>

                                 <div class="total-row">
                                     <span class="total-label">Subtotal:</span>
                                     <span class="total-amount">₹<span class="subtotal_show">0</span></span>
                                 </div>

                                 <div class="total-row">
                                     <span class="total-label">Coupon:</span>
                                     <span class="total-amount">
                                         <div class="input-group ">
                                             <input onkeyup="calculateTotals()"
                                                 class="small_field coupon_show coupon_inp form-control" type="number"
                                                 name="coupon" placeholder="Ex: 12">
                                             <select onchange="calculateTotals()" name="coupon_type"
                                                 class="form-control small_field coupon_type" id="">
                                                 <option value="amount">₹</option>
                                                 <option value="percent">%</option>
                                             </select>
                                         </div>
                                     </span>
                                 </div>
                                 <div class="total-row">
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
                                 <div class="total-row">
                                     <span class="total-label">Delivery:</span>
                                     <span class="total-amount">₹<input name="delivery" type="text"
                                             onkeyup="calculateTotals()" class="delivery_show delivery_inp"
                                             value="0"></span>
                                 </div>
                                 @if ($delivery_gst['status'] ?? 0)
                                     <div class="total-row">
                                         <span class="total-label">Delivery GST Amount:</span>
                                         <input type="hidden" name="delivery_gst_percent" class="delivery_gst_percent"
                                             value = "{{ $delivery_gst['percent'] }}">
                                         <span class="gst-amount">₹<span class="delivery_gst_amount">0</span>
                                     </div>
                                 @endif
                                 <div class="total-row final-total">
                                     <span class="total-label">Total:</span>
                                     <span class="total-amount">₹<span class="total_show">0</span></span>
                                 </div>
                                 <div class="pos--payment-options mt-3 mb-3">
                                     <ul>
                                         <li>
                                             <label>
                                                 <input type="radio" name="payment_status" value="paid" hidden
                                                     checked>
                                                 <span>Paid</span>
                                             </label>
                                         </li>
                                         <li>
                                             <label>
                                                 <input type="radio" name="payment_status" value="unpaid" hidden>
                                                 <span>Unpaid</span>
                                             </label>
                                         </li>
                                     </ul>
                                 </div>
                             </div>
                             <style>

                             </style>

                             <button type="button" class="order-btn">Place order</button>
                             <button type="button" onclick="printInvoice()">Print Invoice</button>
                             <div class="token_type">
                                 <div class="pos--payment-options mt-3 mb-3">
                                     <ul>
                                         <li>
                                             <label>
                                                 <input type="radio" name="token_type" value="token" hidden checked>
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
         @if (session('pdf_url'))
             printInvoice("{{ session('pdf_url') }}")
         @endif
         function printInvoice(url) {
             window.ReactNativeWebView.postMessage(
                 JSON.stringify({
                     type: 'PRINT_INVOICE',
                     url: url
                 })
             );
         }
     </script>
     <script>
         $('.order-btn').on('click', function() {
             if ($('.price-display').length) {
                 $("#cartForm").submit();
             } else {
                 toastr.error('Please add atleast one item');
             }
         })
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
             $("#branch_id").val(null).trigger('change');
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
         $(document).on('click', function(e) {
             // if the clicked element is NOT inside .search_inp
             if (!$(e.target).closest('.search_inp').length) {
                 $(".add_item").hide();
             }
         });
     </script>
     @include('vendor-views/js/pos_items_js')
 @endpush
