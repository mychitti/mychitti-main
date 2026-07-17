@extends('layouts.vendor.app')

@section('title', _moduleLabel('inventory_manage'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/spartan-multi-image-picker.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/inventory_management.css') }}">
    <style>
        .tab_switch {
            justify-content: center;
        }

        @media (max-width: 700px) {
            .items_header {
                padding: 8px;
            }

            .tab_switch {
                justify-content: start;
                padding-inline-start: 1rem !important;
                padding-inline-end: 1rem !important;
            }

            .search_bar2 {
                width: 187px;
            }
        }
    </style>
@endpush
 
@section('content')
    <div class="content container-fluid">
        @include('hmis::vendor-views.partials._pharmacy_header')
        <div class="pharmacy-page-content">
            <div class="card h-100">
                <div class="d-none">
                    <ul class=" nav nav-pills d-flex  tab_switch" id="pills-tab" role="tablist">
                        @if (hasPermission('inventory_item', 'add'))
                            <li class="nav-item mx-0" role="presentation">
                                <button
                                    class="nav-link btn   btn-left {{ !request()->has('tab') || request()->tab == 'item' ? 'active' : '' }}"
                                    id="pills-item-add-tab" data-toggle="pill" data-target="#pills-item-add" type="button"
                                    role="tab" aria-controls="pills-home" aria-selected="true">Items</button>
                            </li>
                        @endif
                        @if (hasPermission('inventory_item_entry', 'add'))
                            <li class="nav-item mx-0" role="presentation">
                                <button
                                    class="nav-link btn  btn-right {{ request()->has('tab') && request()->tab == 'entry' ? 'active' : '' }}"
                                    id="pills-item-entry-tab" data-toggle="pill" data-target="#pills-item-entry"
                                    type="button" role="tab" aria-controls="pills-profile" aria-selected="false">Item
                                    Entries</button>
                            </li>
                        @endif
                    </ul>
                </div>



                <div class="tab-content" id="pills-tabContent">

                    <div class="tab-pane fade {{ !request('tab') || request('tab') == 'items' ? 'show active' : '' }}"
                        id="pills-item-add" role="tabpanel" aria-labelledby="pills-item-add-tab">

                        @if (hasPermission('inventory_item', 'list'))

                            <div class="">
                                <div class="col-md-12 px-0">
                                    <div class="card h-100">
                                        <div class="card-header d-flex flex-wrap items_header align-items-center">
                                            <h3 class="mx-3 mb-0">Items List</h3>
                                            <div class="d-flex gap-2 flex-wrap align-items-center ml-auto">
                                                <div id="reader"></div>
                                                <div id="result"></div>
                                                <a href="{{ route('vendor.inventory.item.scan-barcode') }}" class="btn btn-outline-primary btn-sm btn_sm">Scan Barcode</a>
                                                 @if (hasPermission('inventory_item_entry', 'add'))
                                                    <button type="button" class="btn btn-outline-primary btn-sm btn_sm" data-toggle="modal"
                                                        data-target="#storageUnitManageModal">Stock Management</button>
                                                @endif
                                                @if (hasPermission('inventory_item_entry', 'add'))
                                                    <a href="{{ route('vendor.invoice.my-bills', ['add_purchase' => 1]) }}"
                                                        class="btn btn-outline-primary btn-sm btn_sm">Item Entry</a>
                                                @endif
                                                @if (hasPermission('inventory_item', 'add'))
                                                    <button type="button" class="btn btn-primary btn-sm btn_sm add_item_btn" data-toggle="modal"
                                                        data-target="#addInventoryItemModal">Add Item</button>
                                                @endif
                                                <div class="divider mx-2" style="border-left: 1px solid #cbd5e1; height: 24px;"></div>
                                                {{-- <form action="" class="d-flex date-range-form2">
                                                    <input type="hidden" name="tab" value="items">
                                                    <button style="width:fit-content; white-space:nowrap"
                                                        class="btn btn-outline-warning" type="button" data-toggle="modal"
                                                        data-target="#dateRangeModal">{{ translate($preset) }}</button>
                                                    @if (!request()->has('tab') || request()->get('tab') === 'items')
                                                    @endif
                                                </form> --}}

                                                @if (hasPermission('inventory_item', 'export') || hasPermission('inventory_item', 'delete'))

                                                    
                                                    <!-- Delete Button -->
                                                    <div class="mr-1 delete_selected_btn align-self-center" style="display:none;">
                                                        @if (hasPermission('inventory_item', 'delete'))
                                                            <button style=" white-space: nowrap; min-width:fit-content !important;padding: 5px !important;display:inline-flex !important;width:auto !important;align-items:center;" id="delete_all"
                                                                class="btn btn-sm btn-outline-danger px-3 py-2 btn_sm"
                                                                title="Delete Selected">
                                                                <i class="tio-delete"></i> Delete Selected
                                                            </button>
                                                        @endif
                                                        @if (hasPermission('inventory_item', 'export'))
                                                            <button id="download_selected" style=" white-space: nowrap; min-width:fit-content !important;padding: 5px !important;display:inline-flex !important;width:auto !important;align-items:center;"
                                                                class="btn btn-sm btn-outline-primary px-3 py-2 btn_sm "
                                                                title="Download Selected">
                                                                <i class="tio-download"></i> Download Selected
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <div class="badge badge-soft-success align-items-center"
                                                        style="    height: 39px;    display: flex;">
                                                        <div class="form-check mr-1">
                                                            <input type="checkbox" class="form-check-input" id="check_all">
                                                            <label style=" white-space: nowrap;"
                                                                class="mt-1 form-check-label" id="check_all_label"
                                                                for="check_all">Select All</label>
                                                        </div>
                                                    </div>
                                                @endif

                                                <form action="" class="h-100">
                                                    <!-- Search -->
                                                    <div class="input-group input--group search_bar2"
                                                        style="flex-wrap: nowrap !important; ">
                                                        <input type="hidden" name="tab" value="items">

                                                        <input type="search" style="height: 100%;padding: 11px 10px;"
                                                            name="search" value="{{ request()?->search ?? null }}"
                                                            class="form-control "
                                                            placeholder="{{ translate('messages.search by item or SKU ID') }}">
                                                        <button type="submit" class="btn btn--secondary "><i
                                                                class="tio-search"></i></button>
                                                    </div>
                                                    <!-- End Search -->
                                                </form>
                                                <form action="" class="h-100">
                                                    <input type="hidden" name="tab" value="items">
                                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                                    <select name="missing" class="form-control"
                                                        style="height: 100%; min-width: 190px;"
                                                        title="{{ translate('messages.Show items missing a field') }}"
                                                        onchange="this.form.submit()">
                                                        <option value="">{{ translate('messages.Missing field') }}…</option>
                                                        <option value="any" {{ request('missing') == 'any' ? 'selected' : '' }}>{{ translate('messages.Any missing field') }}</option>
                                                         <option value="selling_price" {{ request('missing') == 'selling_price' ? 'selected' : '' }}>{{ translate('messages.No Selling Price') }}</option>
                                                         <option value="mrp" {{ request('missing') == 'mrp' ? 'selected' : '' }}>{{ translate('messages.No MRP') }}</option>
                                                        <option value="unit" {{ request('missing') == 'unit' ? 'selected' : '' }}>{{ translate('messages.No Unit') }}</option>
                                                        <option value="hsn" {{ request('missing') == 'hsn' ? 'selected' : '' }}>{{ translate('messages.No HSN Code') }}</option>
                                                        <option value="sku_id" {{ request('missing') == 'sku_id' ? 'selected' : '' }}>{{ translate('messages.No SKU ID') }}</option>
                                                        <option value="category" {{ request('missing') == 'category' ? 'selected' : '' }}>{{ translate('messages.No Category') }}</option>
                                                        <option value="landing_price" {{ request('missing') == 'landing_price' ? 'selected' : '' }}>{{ translate('messages.No Purchase Price') }}</option>
                                                    </select>
                                                </form>
                                                @if (hasPermission('inventory_item', 'export'))
                                                    <a href="{{ route('vendor.inventory.item.export') }}"
                                                        class="btn btn-outline-primary btn_sm">Export Items</a>
                                                @endif
                                                @if (hasPermission('inventory_item', 'import'))
                                                    <a data-toggle="modal" data-target="#importExcelModal"
                                                        class="btn btn-outline-primary btn_sm">Import Items</a>
                                                @endif
                                            </div>

                                        </div>
                                        <div class="table-responsive datatable-custom" id="table-div">
                                            <table id="datatable"
                                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th></th>
                                                        <th class="border-0">{{ translate('sl') }}</th>
                                                        <th class="border-0"> Name</th>
                                                        <th class="border-0 hide_on_phone"> Category</th>
                                                        <th class="border-0 hide_on_phone"> Item Type</th>
                                                        {{-- <th class="border-0 hide_on_phone"> Brand</th>
                                                        <th class="border-0 hide_on_phone"> Model Number</th> --}}
                                                        <th class="border-0 hide_on_phone ">Stock</th>
                                                        <th class="border-0 hide_on_phone ">MRP</th>
                                                        <th class="border-0 ">Selling Price</th>

                                                        <th class="border-0 ">Action</th>
                                                    </tr>
                                                </thead>

                                                <tbody id="set-rows">
                                                    @foreach ($inventory_items as $key => $item)
                                                        <tr class="clickable-row"
                                                            data-href="{{ route('vendor.inventory.item.detail', [$item->id]) }}">
                                                            <td class="text-center"> <input type="checkbox"
                                                                    onclick="event.stopPropagation()" name="item_id[]"
                                                                    value="{{ $item->id }}" class="check_select "
                                                                    id="">
                                                            </td>
                                                            <td>{{ $key + $inventory_items->firstItem() }}</td>
                                                            <td>
                                                                <a class="media align-items-center">
                                                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($item['image'], asset('storage/app/public/inventory-item/') . '/' . $item['image'], asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                        alt="{{ $item->item_name }} image">
                                                                    <div class="media-body">
                                                                        <h5 class="text-hover-primary mb-0">
                                                                            {{ Str::limit($item['item_name'], 20, '...') }}
                                                                        </h5>
                                                                        <span class="text-muted">SKU : {{$item->sku_id}}</span>
                                                                    </div>
                                                                </a>
                                                            </td>
                                                            <td class="hide_on_phone">{{ $item->category->name ?? 'N/A' }}</td>

                                                            <td class="hide_on_phone">
                                                                <div
                                                                    class="badge badge-soft-{{ $item->item_type == 'service' ? 'warning' : 'success' }}">
                                                                    {{ ucfirst($item->item_type) }} </div>
                                                            </td>
                                                            {{-- <td class="hide_on_phone">{{ $item->brand }}</td>
                                                            <td class="hide_on_phone">{{ $item->model_number }}</td> --}}
                                                            <td class="hide_on_phone">
                                                                @php
                                                                    $__ds = (float) ($item->stock ?? 0);
                                                                    $__vs = json_decode($item->variations ?? '[]', true);
                                                                    if (is_array($__vs)) { foreach ($__vs as $__v) { $__ds += (float) ($__v['stock'] ?? 0); } }
                                                                @endphp
                                                                <div class="badge badge-soft-{{ $__ds <= 5 ? 'danger' : 'success' }}">{{ $__ds }}</div>
                                                            </td>
                                                            <td class="hide_on_phone">
                                                                {{ _price($item->mrp) }}
                                                            </td>
                                                            <td>
                                                                {{ _price($item->selling_price) }}
                                                            </td>
                                                           
                                                            <td>
                                                                <div class="dropdown">
                                                                    <button class="btn p-1 dropdown-toggle" type="button"
                                                                        data-toggle="dropdown" aria-expanded="false">
                                                                        <i class="fa-solid fa-bars"></i>
                                                                    </button>
                                                                    <div class="dropdown-menu">
                                                                        @if (hasPermission('inventory_item', 'view'))
                                                                            <a class="dropdown-item text-success"
                                                                                href="{{ route('vendor.inventory.item.detail', [$item->id]) }}"
                                                                                title="{{ translate('messages.details') }}"></i>
                                                                                <i class="tio-visible"></i> View
                                                                            </a>
                                                                        @endif
                                                                        @if (hasPermission('inventory_item', 'edit'))
                                                                            <a class="dropdown-item text-primary"
                                                                                href="{{ route('vendor.inventory.edit-item', [$item->id]) }}"
                                                                                title="{{ translate('messages.edit') }}"></i>
                                                                                <i class="tio-edit"></i> Edit
                                                                            </a>
                                                                        @endif
                                                                        @if (hasPermission('inventory_item', 'share'))
                                                                            <a class="dropdown-item text-warning share-btn"
                                                                                onclick="event.stopPropagation()"
                                                                                href="javascript:;"
                                                                                data-url="{{ route('vendor.inventory.item.detail', $item->id) }}"
                                                                                data-title="{{ $item->item_name }}"
                                                                                title="{{ translate('messages.share') }}"></i>
                                                                                <i class="tio-share"></i> Share
                                                                            </a>
                                                                        @endif
                                                                        @if (hasPermission('inventory_item', 'show_on_website'))
                                                                        @if($item->show_on_store_page)
 <a class="dropdown-item text-success form-alert"
                                                                                href="javascript:;"
                                                                                data-id="show_on_website-{{ $item['id'] }}"
                                                                                data-message="{{ translate('messages.Want to hide this item from website') }}"
                                                                                data-title="{{ $item->item_name }}"
                                                                                title="{{ translate('messages.show_on_website') }}">
                                                                                <i class="tio-invisible"></i> Hide from Website
                                                                            </a>
                                                                        @else
                                                                            <a class="dropdown-item text-success form-alert"
                                                                                href="javascript:;"
                                                                                data-id="show_on_website-{{ $item['id'] }}"
                                                                                data-message="{{ translate('messages.Want to show this item on website') }}"
                                                                                data-title="{{ $item->item_name }}"
                                                                                title="{{ translate('messages.show_on_website') }}">
                                                                                <i class="tio-visible"></i> Show on Website
                                                                            </a>
                                                                            @endif
                                                                            <form
                                                                                action="{{ route('vendor.inventory.item.show_on_website', [$item['id'], $item->show_on_store_page ? 0 : 1]) }}"
                                                                                method="get"
                                                                                id="show_on_website-{{ $item['id'] }}">
                                                                            </form>
                                                                        @endif
                                                                        @if (hasPermission('inventory_item', 'delete'))
                                                                            <a class="dropdown-item text-danger form-alert"
                                                                                href="javascript:;"
                                                                                data-id="item-{{ $item['id'] }}"
                                                                                data-message="{{ translate('messages.Want to delete this item') }}"
                                                                                data-title="{{ $item->item_name }}"
                                                                                title="{{ translate('messages.delete_item') }}">
                                                                                <i class="tio-delete-outlined"></i> Delete
                                                                            </a>
                                                                            <form
                                                                                action="{{ route('vendor.inventory.item.delete', [$item['id']]) }}"
                                                                                method="get"
                                                                                id="item-{{ $item['id'] }}">
                                                                            </form>
                                                                        @endif

                                                                    </div>

                                                                </div>
                                                                {{-- <div class="btn--container justify-content-warning">
                                                                    @if (hasPermission('inventory_item', 'view'))
                                                                        <a class="btn action-btn btn--warning btn-outline-warning"
                                                                            href="{{ route('vendor.inventory.item.detail', [$item->id]) }}"
                                                                            title="{{ translate('messages.detail') }}"><i
                                                                                class="tio-visible"></i>
                                                                        </a>
                                                                    @endif
                                                                    @if (hasPermission('inventory_item', 'edit'))
                                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                                            href="{{ route('vendor.inventory.edit-item', [$item->id]) }}"
                                                                            title="{{ translate('messages.edit') }}"><i
                                                                                class="tio-edit"></i>
                                                                        </a>
                                                                    @endif
                                                                    @if (hasPermission('inventory_item', 'share'))
                                                                        <a class="btn action-btn btn--warning btn-outline-warning share-btn"
                                                                            onclick="event.stopPropagation()"
                                                                            href="javascript:;"
                                                                            data-url="{{ route('vendor.inventory.item.detail', $item->id) }}"
                                                                            data-title="{{ $item->item_name }}"><i
                                                                                class="tio-share"></i>
                                                                        </a>
                                                                    @endif
                                                                    @if (hasPermission('inventory_item', 'delete'))
                                                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                                            href="javascript:;"
                                                                            data-id="item-{{ $item['id'] }}"
                                                                            onclick="event.stopPropagation()"
                                                                            data-message="{{ translate('messages.Want to delete this item') }}"
                                                                            title="{{ translate('messages.delete_item') }}"><i
                                                                                class="tio-delete-outlined"></i>
                                                                        </a>
                                                                        <form
                                                                            action="{{ route('vendor.inventory.item.delete', [$item['id']]) }}"
                                                                            method="post" id="item-{{ $item['id'] }}">
                                                                            @csrf @method('get')
                                                                        </form>
                                                                    @endif
                                                                </div> --}}
                                                            </td>

                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @if (count($inventory_items) !== 0)
                                                <hr>
                                            @endif
                                            <div class="page-area">
                                                {!! $inventory_items->links() !!}
                                            </div>

                                            @if (count($inventory_items) === 0)
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
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane fade {{ request('tab') && request('tab') == 'entry' ? 'show active' : '' }}"
                        id="pills-item-entry" role="tabpanel" aria-labelledby="pills-item-entry-tab">
                        @if (hasPermission('inventory_item_entry', 'list'))

                            <div class="">
                                <div class="col-md-12 px-0">
                                    <div class="card h-100">
                                        <div class="card-header d-flex flex-wrap p-2 align-items-center">
                                            <h3 class="mx-3 mb-0">Entries</h3>
                                            <div class="d-flex gap-2 flex-wrap align-items-center ml-auto">
                                                <a href="{{ route('vendor.inventory.item.scan-barcode') }}" class="btn btn-outline-primary btn-sm btn_sm">Scan Barcode</a>
                                                @if (hasPermission('inventory_item_entry', 'add'))
                                                    <button type="button" class="btn btn-primary btn-sm btn_sm" data-toggle="modal"
                                                        data-target="#storageUnitManageModal">Stock Management</button>
                                                @endif
                                                <div class="divider mx-2" style="border-left: 1px solid #cbd5e1; height: 24px;"></div>
                                                <form action="" class="d-flex date-range-form">
                                                    <input type="hidden" name="tab" value="entry">
                                                    <button style="width:fit-content; white-space:nowrap"
                                                        class="btn btn-outline-warning" type="button"
                                                        data-toggle="modal"
                                                        data-target="#dateRangeModal">{{ translate($preset) }}</button>
                                                    {{-- date range modal --}}
                                                    @include('vendor-views/form_modals/date_range')
                                                </form>
                                                @if (hasPermission('inventory_item_entry', 'export') || hasPermission('inventory_item_entry', 'delete'))

                                                    <div class="badge badge-soft-success align-items-center"
                                                        style="    height: 39px;    display: flex;">
                                                        <div class="form-check mr-1">
                                                            <input type="checkbox" class="form-check-input"
                                                                id="check_all_entry">
                                                            <label style=" white-space: nowrap;"
                                                                class="mt-1 form-check-label" id="check_all_label_entry"
                                                                for="check_all_entry">Select All</label>
                                                        </div>
                                                    </div>
                                                    <!-- Delete Button -->
                                                    <div class="mr-1 delete_selected_btn_entry" style="display:none;">
                                                        @if (hasPermission('inventory_item_entry', 'delete'))
                                                            <button style=" white-space: nowrap;" id="delete_all_entry"
                                                                class="btn btn-sm btn-outline-danger px-3 py-2"
                                                                title="Delete Selected">
                                                                <i class="tio-delete"></i> Delete Selected
                                                            </button>
                                                        @endif
                                                        @if (hasPermission('inventory_item_entry', 'export'))
                                                            <button id="download_selected_entry"
                                                                style=" white-space: nowrap;"
                                                                class="btn btn-sm btn-outline-primary px-3 py-2 "
                                                                title="Download Selected">
                                                                <i class="tio-download"></i> Download Selected
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endif

                                                <form action="" class="h-100">
                                                    <!-- Search -->
                                                    <div class="input-group input--group"
                                                        style="flex-wrap: nowrap !important; ">
                                                        <input type="hidden" name="tab" value="entry">
                                                        <input type="search"
                                                            style="min-width:210px;height: 100%;     padding: 11px 10px;"
                                                            name="search" value="{{ request()?->search ?? null }}"
                                                            class="form-control "
                                                            placeholder="{{ translate('messages.search by item or SKU ID') }}">
                                                        <button type="submit" class="btn btn--secondary "><i
                                                                class="tio-search"></i></button>
                                                    </div>
                                                    <!-- End Search -->
                                                </form>
                                                @if (hasPermission('inventory_item_entry', 'export'))
                                                    <a href="{{ route('vendor.inventory.entry.export') }}"
                                                        class="btn btn-outline-primary btn_sm">Export Entries</a>
                                                @endif
                                                @if (hasPermission('inventory_item_entry', 'import'))
                                                    <a data-toggle="modal" data-target="#importEntryExcelModal"
                                                        class="btn btn-outline-primary btn_sm">Import Entries</a>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="table-responsive datatable-custom" id="table-div">
                                            <table id="datatable"
                                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="border-0"></th>
                                                        <th class="border-0">{{ translate('sl') }}</th>
                                                        <th class="border-0">Date</th>
                                                        <th class="border-0">Bill No.</th>
                                                        <th class="border-0">Item Name</th>
                                                        <th class="border-0">Storage Unit</th>
                                                        <th class="border-0 ">Purchase Price</th>
                                                        <th class="border-0 ">Stock</th>
                                                        <th class="border-0 ">Total</th>
                                                        <th class="border-0 ">Created At</th>
                                                    </tr>
                                                </thead>

                                                <tbody id="set-rows">
                                                    @foreach ($entries as $key => $entry)
                                                        <tr>
                                                            <td class="text-center"> <input type="checkbox"
                                                                    onclick="event.stopPropagation()" name="item_id[]"
                                                                    value="{{ $entry->id }}"
                                                                    class="check_select_entry" id="">
                                                            </td>
                                                            <td>{{ $key + $entries->firstItem() }}</td>
                                                            <td>
                                                                {{ $entry->date }}
                                                            </td>
                                                            <td>
                                                                {{ $entry->bill_number }}
                                                            </td>
                                                            <td>
                                                                {{ $entry->item?->item_name }}
                                                                {{ $entry->variation_type ? '(' . $entry->variation_type . ')' : '' }}
                                                            </td>
                                                            <td>
                                                                @if ($entry->storageUnit)
                                                                    {{ $entry->storageUnit?->parent?->name }} >
                                                                    {{ $entry->storageUnit?->name }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                {{ _price($entry->landing_price) }}
                                                            </td>
                                                            <td>
                                                                {{ $entry->quantity . ' ' . $entry->item?->itemUnit?->unit }}
                                                                {{ $entry->secondary_quantity && $entry->primary_quantity ? '(' . $entry->secondary_quantity . ' ' . $entry->item?->secondaryUnit?->unit . ' + ' . $entry->primary_quantity . ' ' . $entry->item?->itemUnit?->unit . ')' : '' }}
                                                            </td>
                                                            <td>
                                                                {{ \App\CentralLogics\Helpers::format_currency($entry->total_amount) }}
                                                            </td>
                                                            <td>
                                                                {{ $entry->created_at }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @if (count($entries) !== 0)
                                                <hr>
                                            @endif
                                            <div class="page-area">
                                                {!! $entries->links() !!}
                                            </div>
                                            @if (count($entries) === 0)
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
                            </div>
                        @endif
                    </div>

                </div>


            </div>
            <div class="card-body row pt-1">

            </div>

        </div>
    </div>
    </div>
    @if (hasPermission('inventory_item', 'import'))
        <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                        <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('vendor.inventory.import') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <a href="{{ asset('storage/app/public/uploaded/excel/inventory_item_updated3.xlsx') }}"
                                download class="btn btn-outline-primary mb-2">View Example</a>
                            <div class="form-group">
                                <label for="file">Upload Excel File</label>
                                <input type="file" style="height: 46px !important;" name="file"
                                    class="form-control" id="file" accept=".xlsx,.xls">
                            </div>
                            <div class="form-group w-100 ">
                                <button type="submit" class="btn btn-primary float-right">Import</button>
                            </div>
                        </form>
                    </div>
                    <div class="p-3">
                        <h4>How It Works</h4>
                        <ol>
                            <li><span class="text-danger"> Do not edit or delete column headings.</span></li>
                            <li><strong>Temp Group</strong><br> Use a short code like <code>TMP1</code>, <code>TMP2</code>
                                to group variations of the same item.<br> All rows belonging to the same item (main +
                                variations) must have the same Temp Group ID. </li>
                            <li><strong>Main Item Details</strong><br> Enter the main item details (e.g., <strong>Name, SKU
                                    ID, GST, MRP, Selling Price, Stock, Storage Unit, Category, Brand</strong>) only in the
                                <strong>first row</strong> of each Temp Group.<br> Leave them blank for the remaining
                                variation rows of that group (except Temp Group ID, which must be filled in all rows).
                            </li>
                            <li><strong>Variation Details</strong><br> Fill <strong>Variation Name, Variation Prices (MRP,
                                    Selling, Purchase), and Variation Stock</strong> in <strong>every row</strong>
                                corresponding to each variation of the item. </li>
                            <li><strong>Mandatory Fields</strong><br> Every item (each Temp Group) must have:<br> -
                                <code>Item Name</code><br> - <code>MRP</code><br>- <code>SKU</code><br>
                            </li>
                            <li><strong>Item Type</strong><br> Specify the type of item, e.g., <code>Product</code> or
                                <code>Service</code>.
                            </li>
                            <li><strong>Main Image (Column U)</strong><br> Paste a single image directly into <strong>column
                                    U</strong> of the item's row. This will be saved as the main/thumbnail image of the
                                item.</li>
                            <li><strong>Multiple Images (Column V)</strong><br> Paste one or more images directly into
                                <strong>column V</strong> of the item's row. These will be saved as the item's gallery
                                images.</li>
                            <li><strong>Category and Unit</strong><br> Provide either <strong>Category Name (exact
                                    spelling)</strong> or <strong>Category ID</strong>.<br> Provide either <strong>Unit Name
                                    (exact spelling)</strong> or <strong>Unit ID</strong>. </li>
                            <li><strong>GST Type</strong><br> Enter the GST type, e.g., <code>CGST + SGST</code>, <code>No
                                    GST</code>, or <code>IGST</code>. </li>
                            <li><strong>Product Condition</strong><br> Enter the product condition, e.g., <code>New
                                    Product</code> or <code>Used Product</code>. </li>
                            <li><strong>Storage Unit</strong><br> Provide either <strong>Storage Unit ID</strong> or
                                <strong>Storage Unit Name (exact spelling)</strong>.
                            </li>
                            <li><strong>Attribute IDs</strong><br> - Enter the list of attribute IDs assigned to the
                                product, separated by commas.<br> - Example: <code>30,91</code><br> (This means the product
                                has two attributes: ID <code>30</code> and ID <code>91</code>.) </li>
                            <li><strong>Choice Options</strong><br> - Enter the options for each attribute in <strong>JSON
                                    format</strong>.<br> - The JSON must use the key <code>choice_[AttributeID]</code> for
                                each attribute.<br> - Example:<br> <code>{"choice_91": ["hard"], "choice_30": ["12 x 10","12
                                    x 8"]}</code><br><br> - In this example:<br> • Attribute <code>91</code> has the option
                                <code>hard</code>.<br> • Attribute <code>30</code> has two options: <code>12 x 10</code> and
                                <code>12 x 8</code>.
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if (hasPermission('inventory_item_entry', 'import'))
        <div class="modal fade" id="importEntryExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                        <button type="button" class="close close_modal" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('vendor.inventory.entry.import') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <a href="{{ asset('storage/app/public/util/inventory_entry_example.xlsx') }}" download
                                class="btn btn-outline-primary mb-2">View Example</a>
                            <div class="form-group">
                                <label for="file">Upload Excel File</label>
                                <input type="file" style="height: 46px !important;" name="file"
                                    class="form-control" id="file" accept=".xlsx,.xls">
                            </div>
                            <div class="form-group w-100 ">
                                <button type="submit" class="btn btn-primary float-right">Import</button>
                            </div>
                        </form>
                    </div>
                    <div class="p-3">
                        <h4>How It Works</h4>
                        <ol>
                            <li>Download Example Sheet. Fill you data in it or create new file with exact same headings.
                            </li>
                            <li><span class="text-danger">**Do not edit or delete headings**</span></li>
                            <li><strong>Item ID, Stock required</strong><br>
                                You must fill <strong>Item Id and Stock</strong>
                            </li>
                            <li><strong>Product Condition</strong><br>
                                <strong>Product Condition</strong> <strong>eg. <code>new</code>, <code>used</code></strong>
                            </li>
                            <li><strong>Purchase Price GST</strong><br>
                                <strong>Purchase Price GST</strong> <strong>eg. <code>including_gst</code>,
                                    <code>excluding_gst</code></strong>
                            </li>

                        </ol>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if (hasPermission('inventory_item', 'add'))
        @include('vendor-views/form_modals/inventory_item_add')
    @endif
    @if (hasPermission('inventory_item_entry', 'add'))
        @include('vendor-views/form_modals/storage_unit_manage')
        {{-- BACKUP: original full stock-entry modal (#itemEntryModal). --}}
        @include('vendor-views/form_modals/inventory_item_entry')
    @endif

@endsection

@push('script_2')
    @include('vendor-views/js/inventory_management')
    <script>
        @if ($tab == 'add')
            $(".add_item_btn").click()
        @endif
        $(document).ready(function() {
            $('.share-btn').click(async function(e) {
                e.preventDefault(); // prevent default action
                var url = $(this).data('url');
                var title = $(this).data('title');

                if (navigator.share) {
                    try {
                        await navigator.share({
                            title: title,
                            text: 'Check out this item: ' + title,
                            url: url
                        });
                        console.log('Shared successfully');
                    } catch (err) {
                        console.error('Error sharing:', err);
                    }
                } else {
                    // fallback for desktop
                    prompt('Copy this link to share:', url);
                }
            });
        });
        $("#check_all").on('change', function() {
            if ($(this).prop('checked') == true) {
                $("#check_all_label").text('Deselect All');
                $(".check_select").prop('checked', true)
                $('.delete_selected_btn').show()
            } else {
                $("#check_all_label").text('Select All');
                $(".check_select").prop('checked', false)
                $('.delete_selected_btn').hide()
            }
        })
        $("#check_all_entry").on('change', function() {
            if ($(this).prop('checked') == true) {
                $("#check_all_label_entry").text('Deselect All');
                $(".check_select_entry").prop('checked', true)
                $('.delete_selected_btn_entry').show()
            } else {
                $("#check_all_label_entry").text('Select All');
                $(".check_select_entry").prop('checked', false)
                $('.delete_selected_btn_entry').hide()
            }
        })
        $(".check_select").on('change', function() {
            if ($('.check_select:checked').length > 0) {
                $('.delete_selected_btn').show();
            } else {
                $('.delete_selected_btn').hide();
            }
        });
        $("#delete_all").on('click', function() {

            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: 'You want to delete selected items',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    var selectedIds = [];
                    $('.check_select:checked').each(function() {
                        selectedIds.push($(this).val());
                    });
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.inventory.item.bulk-delete') }}',
                        data: {
                            item_ids: selectedIds
                        },
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                }
            })

        })
        $("#download_selected").on('click', function() {
            var selectedIds = [];
            $('.check_select:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert('Please select at least one item.');
                return;
            }

            var form = $('<form>', {
                action: '{{ route('vendor.inventory.item.export-selected') }}',
                method: 'GET'
            });

            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: $('meta[name="csrf-token"]').attr('content')
            }));

            selectedIds.forEach(function(id) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'item_ids[]', // Use array syntax
                    value: id
                }));
            });

            form.appendTo('body').submit();
        });
        $("#delete_all_entry").on('click', function() {

            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: 'You want to delete selected entries',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    var selectedIds = [];
                    $('.check_select_entry:checked').each(function() {
                        selectedIds.push($(this).val());
                    });
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.inventory.entry.bulk-delete') }}',
                        data: {
                            item_ids: selectedIds
                        },
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                }
            })

        })
        $("#download_selected_entry").on('click', function() {
            var selectedIds = [];
            $('.check_select_entry:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert('Please select at least one entry.');
                return;
            }

            var form = $('<form>', {
                action: '{{ route('vendor.inventory.entry.export-selected') }}',
                method: 'GET'
            });

            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: $('meta[name="csrf-token"]').attr('content')
            }));

            selectedIds.forEach(function(id) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'item_ids[]', // Use array syntax
                    value: id
                }));
            });

            form.appendTo('body').submit();
        });

        $(document).ready(function() {
            $('.modal_btn').on('click', function() {
                $('#add_user_type').val($(this).attr('data-value'));
                $("#user_ytp").text($(this).attr('data-value'))
            });
        });
        $('#clientCommentModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('whatever')
            var modal = $(this)
            modal.find('.modal-body #user_id').val(id)
        })
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function(e) {
                // Ignore clicks inside a dropdown
                if (e.target.closest('.dropdown')) return;

                window.location = this.dataset.href;
            });
        });
        $(document).on('keyup change', "#secondary_qty, #primary_qty, #secondary_unit, #primary_unit", function() {
            var secondary_qty = $("#secondary_qty").val();
            var primary_qty = $("#primary_qty").val();
            var secondary_unit = $("#secondary_unit option:selected").text();
            var primary_unit = $("#primary_unit option:selected").text();

            if (primary_qty && primary_unit && secondary_qty && secondary_unit) {
                $(".info_text").html('(<i class="tio-info-outlined"></i> ' + primary_qty + ' ' + primary_unit +
                    ' of ' + secondary_qty + ' ' +
                    secondary_unit + ' each)');
            } else {
                $(".info_text").text('');
            }
        });
    </script>




    @include('vendor-views/js/uom_js')
    @include('vendor-views/js/date_range')
@endpush
