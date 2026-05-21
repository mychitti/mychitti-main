@extends('layouts.vendor.app')

@section('title', 'Convert to Order')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 6)
        <style>
            .hidden_hsn {
                display: none;
            }
        </style>
    @endif
    <style>
        .hidden_tax {
            display: none;
        }

        .select2-results__option:last-child {
            color: rgb(90, 123, 186) !important;
            font-weight: bold;
        }


        #totalWithoutGST,
        #totalWithGST,
        .currency {
            font-size: 18px;
            color: black;
        }

        .item_row_inv td {
            padding: 2px !important;
        }

        .form-row {
            margin-top: 6px;
        }

        .hidden_tax {
            display: none;
        }
    </style>

    <style>
        .custom-input {
            padding-left: 0;
            border: 1px solid #e8e6e6;
            box-shadow: none;
            border-left: none;
        }

        .custom-input:focus {
            box-shadow: none;
            border: 1px solid #ececec;
            outline: none;
            border-left: none;
        }

        .item_row_inv td {
            padding: 2px !important;
        }

        .item_row_quote td {
            padding: 2px !important;
        }


        @media (max-width: 768px) {
            table {
                display: block;
                /* Make table block */
                border: none;
            }

            thead {
                display: none;
                /* Hide headers */
            }

            tbody tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                /* Add border around cards */
                padding: 10px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 5px 10px;
            }

            tbody td::before {
                content: attr(data-label);
                /* Use data-label for headings */
                font-weight: bold;
                flex: 1;
            }

            td {
                flex: 2;
            }
        }

        .table th {
            padding: 5px !important;
        }

        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1111;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }
    </style>
    <style>
        .payment-status {
            background: #ebf5ff;
            border-radius: 15px;
            padding: 12px;
            margin-bottom: 30px;
            /* border: 1px solid #e8e8e8; */
            color: white;
        }

        .div2 {
            background: #ffe5fb;
        }

        .payment-status h3 {
            margin-bottom: 15px;
            font-weight: 600;
        }

        .radio-group {
            display: flex;
            gap: 20px;
        }

        .radio-option2,
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .radio-option2:hover,
        .radio-option:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .radio-option2.active,
        .radio-option.active {
            background: rgba(255, 255, 255, 0.9);
            color: #0984e3;
            border-color: white;
        }

        .radio-option2 input[type="radio"],
        .radio-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #0984e3;
        }

        .main-section {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
            margin-bottom: 30px;
        }

        .items-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .section-header {
            background: #d7fff7;
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h3 {
            font-weight: 600;
        }

        .add-item-btn {
            background: rgb(255 255 255 / 65%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            /* color: white; */
            padding: 3px 14px;
            border-radius: 13px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-item-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .add-item-btn::before {
            content: '+';
            font-size: 18px;
            font-weight: bold;
        }

        .table-container {
            padding: 25px;
            max-height: 400px;
            overflow-y: auto;
            padding-top: 0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #636e72;
            border: 2px dashed #e5e5e5;
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .empty-add-btn {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .empty-add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 184, 148, 0.4);
        }

        .totals-section {
            background: white;
            border-radius: 15px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .totals-header {
            background: #e5e2ff;
            color: white;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .totals-content {
            padding: 25px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f4;
            font-size: 16px;
        }

        .total-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 18px;
            color: #2d3436;
        }

        .convert-btn {
            width: 100%;
            background: linear-gradient(135deg, #fd79a8, #e84393);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(232, 67, 147, 0.4);
        }

        .convert-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(232, 67, 147, 0.5);
        }

        .submit-section {
            display: flex;
            justify-content: center;
            padding: 0 30px 30px;
        }

        .final-submit-btn {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 184, 148, 0.3);
            min-width: 200px;
        }

        .final-submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 184, 148, 0.4);
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .help-btn {
                position: static !important;
                margin-top: 15px;
                display: inline-block;
            }


            .main-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .radio-group {
                flex-direction: column;
                gap: 10px;
            }

            .totals-section {
                position: static;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(167, 216, 240, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(167, 216, 240, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(167, 216, 240, 0);
            }
        }

        .t_head tr th {
            padding: 10px !important;
        }

        .main_item {
            padding: 10px;
            background: #fffbfa;
            width: fit-content;
            min-width: 299px;
            border-radius: 10px;
            box-shadow: 0px 0px 8px #e2e2e2;
        }

        .totals-inner {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);

        }
    </style>

@endpush

@section('content')
    <div id="toast" class="toast">This is a toaster notification!</div>

    {{-- @include('vendor-views/sub-module/partials/billing') --}}

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Convert to Order #{{ $lead->id }}</h1>
            <div class="page-header-select-wrapper">
            </div>
        </div>
        <!-- End Page Header -->



        <div class="row g-2">
            <form class="convert-order-form w-100" action="{{ route('vendor.lead.convert-to-order-store') }}"
                method="post">
                <div class="form-content">
                    <!-- Payment Status -->


                    <!-- Main Section -->
                    <div class="main-section">
                        <!-- Items Section -->
                        <div class="items-section ">
                            <div class="section-header">
                                <h3>Order Items</h3>

                            </div>
                            <div class="table-container">
                                <h4 class="mt-3">Main Item</h4>
                                <div class="main_item">
                                    <div class="d-flex">
                                        <div>
                                            <img class="avatar avatar-lg mr-3 onerror-image"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($lead->item?->image, asset('storage/app/public/product/') . '/' . $lead->item?->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'product/') }}"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                alt="{{ $lead->item?->name }} ">
                                        </div>
                                        <div class="item_content">
                                            <h4>{{ ucfirst($lead->item?->name) }}</h4>
                                            <p>{{ _getCatName($lead->item?->category_id) }}</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="table-container">
                                <h4>Additional Items</h4>
                                <div class="empty-state">
                                    <div class="icon">📦</div>
                                    <p>No items added yet</p>
                                    <button type="button" class="empty-add-btn pulse" onclick="addMoreRow(null, 'inv')">
                                        + Add Item
                                    </button>
                                </div>
                                <table class="table items_table" style="display:none;">
                                    <thead class="t_head" style="    background: #eaeaea;">
                                        <tr>
                                            <th scope="col">Item</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Qty</th>
                                            <th scope="col">Unit</th>
                                            <th class="tax_inp_data hidden_tax" scope="col">Tax <i>(in %)</i></th>
                                            <th class="hsn_inp hidden_hsn" scope="col">HSN</th>
                                            <th class=" " scope="col">Total</th>
                                            <th scope="col"><button type="button" class="add-item-btn"
                                                    onclick="addMoreRow(null, 'inv')">Add
                                                    Item</button></th>
                                        </tr>
                                    </thead>
                                    <tbody class="rows_parent">

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Totals Section -->
                        <div class="totals-section">
                            <div class="payment-status">
                                <h3>Payment Status</h3>
                                <div class="radio-group">
                                    <label class="radio-option active">
                                        <input type="radio" name="payment_stts" value="Paid" checked
                                            onchange="updateRadioStyle(this)">
                                        <span>Paid</span>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="payment_stts" value="Unpaid"
                                            onchange="updateRadioStyle(this)">
                                        <span>Unpaid</span>
                                    </label>
                                </div>
                            </div>
                            <div class="payment-status div2">
                                <h3>Tax Type</h3>
                                <div class="radio-group">
                                    <label class="radio-option2 ">
                                        <input type="radio" name="tax_type" value="gst" class="tax_type"
                                            onchange="updateRadioStyle2(this)">
                                        <span>GST</span>
                                    </label>
                                    <label class="radio-option2 active">
                                        <input type="radio" name="tax_type" value="non-gst" checked class="tax_type"
                                            onchange="updateRadioStyle2(this)">
                                        <span>Non GST</span>
                                    </label>
                                </div>
                            </div>
                            <div class="totals-inner">
                                <div class="totals-header">
                                    <h3>Order Summary</h3>
                                </div>
                                <div class="totals-content">
                                    <div class="total-row">
                                        <span>Taxable Amount:</span>
                                        <span><span class="currency">₹</span><span id="taxable_amount">0</span></span>
                                    </div>
                                    <div class="total-row">
                                        <span>Tax Amount:</span>
                                        <span><span class="currency">₹</span><span id="tax_amount">0</span></span>
                                    </div>
                                    <div class="total-row">
                                        <span>Delivery Charges:</span>
                                        <span><span class="currency">₹</span><span><input type="number" name=""
                                                    id="delivery_charges" style="width: 75px;"></span></span>
                                    </div>
                                    <div class="total-row">
                                        <span>Total:</span>
                                        <span><span class="currency">₹</span><span id="totalWithGST">0</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="submit-section w-100 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Convert to Order
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('script_2')
    @include('vendor-views/billing/basic-inoice-js')
    <script>
        function updateRadioStyle(radio) {
            // Remove active class from all radio options
            document.querySelectorAll('.radio-option').forEach(option => {
                option.classList.remove('active');
            });

            // Add active class to the parent of the checked radio
            radio.closest('.radio-option').classList.add('active');
        }

        function updateRadioStyle2(radio) {
            // Remove active class from all radio options
            document.querySelectorAll('.radio-option2').forEach(option => {
                option.classList.remove('active');
            });

            // Add active class to the parent of the checked radio
            radio.closest('.radio-option2').classList.add('active');
        }

        function addItem() {
            // Placeholder function for adding items
            alert('Add item functionality would be implemented here');

            // For demo purposes, let's hide the empty state
            const emptyState = document.querySelector('.empty-state');
            if (emptyState) {
                emptyState.style.display = 'none';
            }
        }

        function showPreview() {
            alert('Order preview functionality would be implemented here');
        }

        // Add smooth scrolling behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add form validation on submit
        document.querySelector('.convert-order-form').addEventListener('submit', function(e) {
            e.preventDefault();

            // Basic validation
            const paymentStatus = document.querySelector('input[name="payment_stts"]:checked');
            if (!paymentStatus) {
                alert('Please select a payment status');
                return;
            }

            // Show success message
            alert('Order conversion initiated! This would normally submit to your server.');
        });

        // Add loading state to buttons
        function addLoadingState(button) {
            const originalText = button.textContent;
            button.textContent = 'Processing...';
            button.disabled = true;

            setTimeout(() => {
                button.textContent = originalText;
                button.disabled = false;
            }, 2000);
        }

        document.querySelector('.final-submit-btn').addEventListener('click', function() {
            addLoadingState(this);
        });
    </script>
@endpush
