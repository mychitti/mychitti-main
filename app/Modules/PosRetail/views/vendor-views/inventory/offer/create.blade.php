@extends('layouts.vendor.app')

@section('title', 'Create Offer')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .offer-section-no {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .offer-card .card-header {
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        .product-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e7eaf3;
            border-radius: 6px;
            padding: 8px 12px;
            background: #f8f9fb;
            margin-top: 8px;
        }
        .search-results {
            position: absolute;
            z-index: 20;
            background: #fff;
            border: 1px solid #e7eaf3;
            border-radius: 6px;
            width: 100%;
            max-height: 240px;
            overflow-y: auto;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
        }
        .search-results .result-item {
            padding: 8px 12px;
            cursor: pointer;
        }
        .search-results .result-item:hover {
            background: #f8f9fb;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between">
            <h1 class="page-header-title mb-0"><i class="tio-gift"></i> Create Offer</h1>
            <a href="{{ route('vendor.retail-pos.offer.index') }}" class="btn btn-outline-secondary btn_sm">
                <i class="tio-back-ui"></i> Back to Offers
            </a>
        </div>

        <form action="{{ route('vendor.retail-pos.offer.store') }}" method="post" enctype="multipart/form-data"
            id="offerForm">
            @csrf
            <input type="hidden" name="status" id="offerStatus" value="published">
            <input type="hidden" name="item_id" value="{{ $item->id ?? '' }}">

            <div class="row">
                <div class="col-lg-8">

                    {{-- 1. Basic Information --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">1</span> Basic Information</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Offer Name <span class="text-danger">*</span></label>
                                    <input type="text" name="offer_name" class="form-control" required
                                        placeholder="Buy 2 Get 1 Free"
                                        value="{{ $item ? 'Offer on ' . $item->item_name : '' }}">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Offer Code <span class="text-danger">*</span></label>
                                    <input type="text" name="offer_code" class="form-control" required
                                        placeholder="B2G1">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Offer Type <span class="text-danger">*</span></label>
                                    <select name="offer_type" class="form-control">
                                        <option value="buy_x_get_y_free">Buy X Get Y Free</option>
                                        <option value="flat_discount">Flat Discount</option>
                                        <option value="percent_discount">Percent Discount</option>
                                        <option value="bundle_deal">Bundle Deal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label>Description</label>
                                <textarea name="description" rows="2" class="form-control"
                                    placeholder="Describe the offer"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Applicable Products (Buy - X) --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">2</span> Applicable Products (Buy - X)</div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Apply On</label>
                                <div class="d-flex flex-wrap" style="gap: 16px;">
                                    @foreach (['specific_products' => 'Specific Products', 'category' => 'Category', 'brand' => 'Brand', 'sku_combination' => 'SKU Combination', 'mix_match' => 'Mix & Match'] as $val => $lbl)
                                        <label class="d-inline-flex align-items-center mb-0" style="gap: 4px;">
                                            <input type="radio" name="apply_on" value="{{ $val }}"
                                                {{ $val === 'specific_products' ? 'checked' : '' }}> {{ $lbl }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-group position-relative mb-0">
                                <label>Select Products <span class="text-danger">*</span></label>
                                <input type="text" id="buySearch" class="form-control"
                                    placeholder="Search products by name or SKU" autocomplete="off">
                                <div class="search-results d-none" id="buyResults"></div>
                                <div id="buyProducts" class="mt-2"></div>
                            </div>
                        </div>
                    </div>

                    {{-- 6. Buy Condition & 7. Reward --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">3</span> Buy Condition (X) &amp; Reward (Y)</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Buy Quantity (X) <span class="text-danger">*</span></label>
                                    <input type="number" name="buy_quantity" min="1" value="2" class="form-control" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Buy Type</label>
                                    <select name="buy_type" class="form-control">
                                        <option value="same_product">Same Product</option>
                                        <option value="any_product">Any Product</option>
                                        <option value="category">Category</option>
                                        <option value="brand">Brand</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Count Based On</label>
                                    <select name="count_based_on" class="form-control">
                                        <option value="quantity">Quantity</option>
                                        <option value="amount">Amount</option>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Reward Type</label>
                                    <select name="reward_type" class="form-control">
                                        <option value="free_product">Free Product</option>
                                        <option value="discount_percent">Discount %</option>
                                        <option value="discount_amount">Discount Amount</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group position-relative">
                                    <label>Free / Reward Product</label>
                                    <input type="hidden" name="reward_product_id" id="rewardProductId">
                                    <input type="text" id="rewardSearch" class="form-control"
                                        placeholder="Search product" autocomplete="off">
                                    <div class="search-results d-none" id="rewardResults"></div>
                                    <small class="text-muted" id="rewardSelected"></small>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Free Quantity (Y) <span class="text-danger">*</span></label>
                                    <input type="number" name="free_quantity" min="1" value="1" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 form-group mb-0">
                                    <label>Reward Value <small class="text-muted">(% or ₹)</small></label>
                                    <input type="number" name="reward_value" step="0.01" min="0" class="form-control"
                                        placeholder="e.g. 10">
                                    <small class="text-muted">Used for Flat / Percent / Discount rewards. Ignore for
                                        free-product offers.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 8. Additional Conditions --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">4</span> Additional Conditions</div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4 form-group mb-0">
                                    <label>Minimum Bill Value</label>
                                    <input type="number" name="min_bill_value" step="0.01" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-4 form-group mb-0">
                                    <label>Maximum Offer Value</label>
                                    <input type="number" name="max_offer_value" step="0.01" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-switch mt-3">
                                        <input type="checkbox" class="custom-control-input" id="rewardStock"
                                            name="apply_only_if_reward_stock_available" value="1" checked>
                                        <label class="custom-control-label" for="rewardStock">Apply only if reward stock
                                            available</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 9. Offer Limits --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">5</span> Offer Limits</div>
                        <div class="card-body">
                            <div class="row mb-0">
                                <div class="col-md-3 form-group mb-0">
                                    <label>Max Free Qty / Bill</label>
                                    <input type="number" name="max_free_qty_per_bill" class="form-control">
                                </div>
                                <div class="col-md-3 form-group mb-0">
                                    <label>Max Uses / Day</label>
                                    <input type="number" name="max_uses_per_day" class="form-control">
                                </div>
                                <div class="col-md-3 form-group mb-0">
                                    <label>Max Uses / Customer</label>
                                    <input type="number" name="max_uses_per_customer" class="form-control"
                                        placeholder="Unlimited">
                                </div>
                                <div class="col-md-3 form-group mb-0">
                                    <label>Total Campaign Limit</label>
                                    <input type="number" name="total_campaign_limit" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 10. Priority & Settings --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">6</span> Priority &amp; Settings</div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3 form-group mb-0">
                                    <label>Priority <span class="text-danger">*</span></label>
                                    <input type="number" name="priority" min="1" value="1" class="form-control" required>
                                </div>
                                <div class="col-md-3 form-group mb-0">
                                    <label>Combine With Other Offers</label>
                                    <select name="combine_with_other_offers" class="form-control">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-0">
                                    <label>Show In POS</label>
                                    <select name="show_in_pos" class="form-control">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="custom-control custom-switch mt-3">
                                        <input type="checkbox" class="custom-control-input" id="autoExpire"
                                            name="auto_expire_after_end_date" value="1" checked>
                                        <label class="custom-control-label" for="autoExpire">Auto-expire after End
                                            Date</label>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Lower priority number means higher priority (e.g. 1 is highest).</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">

                    {{-- 2. Date & Time --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">7</span> Date &amp; Time</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" required
                                        value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-6 form-group">
                                    <label>End Date <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control" required>
                                </div>
                                <div class="col-6 form-group mb-0">
                                    <label>Start Time</label>
                                    <input type="time" name="start_time" class="form-control" value="09:00">
                                </div>
                                <div class="col-6 form-group mb-0">
                                    <label>End Time</label>
                                    <input type="time" name="end_time" class="form-control" value="21:00">
                                </div>
                            </div>
                            <label class="mt-3">Applicable Days</label>
                            <div class="d-flex flex-wrap" style="gap: 12px;">
                                @foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $val => $lbl)
                                    <label class="d-inline-flex align-items-center mb-0" style="gap: 4px;">
                                        <input type="checkbox" name="applicable_days[]" value="{{ $val }}" checked>
                                        {{ $lbl }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 3. Applicable Branches --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">8</span> Applicable Branches</div>
                        <div class="card-body">
                            <label class="d-inline-flex align-items-center mb-2" style="gap: 6px;">
                                <input type="checkbox" id="allBranches" name="all_branches" value="1"> <strong>Select
                                    All</strong>
                            </label>
                            <div class="d-flex flex-wrap" style="gap: 12px;">
                                @forelse ($branches as $branch)
                                    <label class="d-inline-flex align-items-center mb-0" style="gap: 4px;">
                                        <input type="checkbox" class="branch-checkbox" name="branch_ids[]"
                                            value="{{ $branch->id }}"> {{ $branch->name }}
                                    </label>
                                @empty
                                    <small class="text-muted">No branches found.</small>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- 4. Applicable Customers --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">9</span> Applicable Customers</div>
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label>Customer Type</label>
                                <select name="customer_type" class="form-control">
                                    <option value="all_customers">All Customers</option>
                                    <option value="new_customers">New Customers</option>
                                    <option value="returning_customers">Returning Customers</option>
                                    <option value="vip_customers">VIP Customers</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 11. Promotion & Notification --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">10</span> Promotion &amp; Notification</div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Offer Image / Banner</label>
                                <input type="file" name="banner" accept="image/*" class="form-control-file">
                                <small class="text-muted">1200 × 500 px recommended.</small>
                            </div>
                            <div class="d-flex flex-column" style="gap: 6px;">
                                <label class="d-inline-flex align-items-center mb-0" style="gap: 6px;"><input
                                        type="checkbox" name="notify_sms" value="1"> SMS</label>
                                <label class="d-inline-flex align-items-center mb-0" style="gap: 6px;"><input
                                        type="checkbox" name="notify_whatsapp" value="1"> WhatsApp</label>
                                <label class="d-inline-flex align-items-center mb-0" style="gap: 6px;"><input
                                        type="checkbox" name="notify_push" value="1"> Push Notification</label>
                                <label class="d-inline-flex align-items-center mb-0" style="gap: 6px;"><input
                                        type="checkbox" name="notify_in_app" value="1"> In App</label>
                            </div>
                        </div>
                    </div>

                    {{-- 12. Advanced --}}
                    <div class="card offer-card mb-3">
                        <div class="card-header"><span class="offer-section-no mr-2">11</span> Advanced</div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Customer Eligibility</label>
                                <select name="customer_eligibility" class="form-control">
                                    <option value="all_customers">All Customers</option>
                                    <option value="first_time_only">First Time Only</option>
                                    <option value="loyalty_members_only">Loyalty Members Only</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label>Allow Multiple Times</label>
                                <select name="allow_multiple_times" class="form-control">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card offer-card mb-4">
                <div class="card-body d-flex justify-content-end flex-wrap" style="gap: 10px;">
                    <button type="button" class="btn btn-outline-secondary" id="saveDraftBtn">Save Draft</button>
                    <button type="submit" class="btn btn-primary">Save &amp; Publish Offer</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script_2')
    @php
        $initialItem = $item ? ['id' => $item->id, 'name' => $item->item_name, 'sku' => $item->sku_id, 'stock' => $item->stock] : null;
    @endphp
    <script>
        $(function() {
            var initialItem = @json($initialItem);
            var searchUrl = "{{ route('vendor.retail-pos.offer.search-items') }}";
            var buyIds = [];

            function renderBuyProduct(p) {
                if (buyIds.indexOf(p.id) !== -1) return;
                buyIds.push(p.id);
                var chip = $('<div class="product-chip"></div>');
                chip.append(
                    '<div><div class="font-weight-bold">' + p.name + '</div>' +
                    '<small class="text-muted">SKU: ' + (p.sku || '—') + ' · Stock: ' + (p.stock ?? '—') + '</small></div>'
                );
                chip.append('<input type="hidden" name="buy_product_ids[]" value="' + p.id + '">');
                var remove = $('<button type="button" class="btn btn-sm text-danger">&times;</button>');
                remove.on('click', function() {
                    buyIds = buyIds.filter(function(id) { return id !== p.id; });
                    chip.remove();
                });
                chip.append(remove);
                $('#buyProducts').append(chip);
            }

            if (initialItem) renderBuyProduct(initialItem);

            function bindSearch(inputSel, resultSel, onPick) {
                var timer = null;
                $(inputSel).on('input', function() {
                    var q = $(this).val();
                    clearTimeout(timer);
                    if (!q) { $(resultSel).addClass('d-none').empty(); return; }
                    timer = setTimeout(function() {
                        $.get(searchUrl, { q: q }, function(data) {
                            var box = $(resultSel).empty();
                            if (!data.length) { box.addClass('d-none'); return; }
                            data.forEach(function(p) {
                                var row = $('<div class="result-item"></div>').html(
                                    '<div class="font-weight-bold">' + p.name + '</div>' +
                                    '<small class="text-muted">SKU: ' + (p.sku || '—') + ' · Stock: ' + (p.stock ?? '—') + '</small>'
                                );
                                row.on('click', function() {
                                    onPick(p);
                                    box.addClass('d-none').empty();
                                });
                                box.append(row);
                            });
                            box.removeClass('d-none');
                        });
                    }, 350);
                });
            }

            bindSearch('#buySearch', '#buyResults', function(p) {
                renderBuyProduct(p);
                $('#buySearch').val('');
            });

            bindSearch('#rewardSearch', '#rewardResults', function(p) {
                $('#rewardProductId').val(p.id);
                $('#rewardSearch').val(p.name);
                $('#rewardSelected').text('Selected: ' + p.name);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#buySearch, #buyResults').length) $('#buyResults').addClass('d-none');
                if (!$(e.target).closest('#rewardSearch, #rewardResults').length) $('#rewardResults').addClass('d-none');
            });

            $('#allBranches').on('change', function() {
                var checked = $(this).is(':checked');
                $('.branch-checkbox').prop('checked', checked).prop('disabled', checked);
            });

            $('#saveDraftBtn').on('click', function() {
                $('#offerStatus').val('draft');
                $('#offerForm').submit();
            });
        });
    </script>
@endpush
