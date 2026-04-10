@extends('layouts.admin.app')

@section('title', translate('Customer Details'))

@push('css_or_js')
<style>
/* ── Reset helpers ─────────────────────── */
.cv-page { padding: 0 0 40px; }
.cv-gap  { gap: 20px; }

/* ── Hero card ─────────────────────────── */
.cv-hero {
    background:#eafff9;
    border-radius: 20px;
       padding: 23px;
    position: relative;
    overflow: hidden;
    margin-bottom: -44px;
}
.cv-hero::before {
    content:'';position:absolute;right:-80px;top:-80px;
    width:300px;height:300px;border-radius:50%;
    background:rgba(255,255,255,.06);
}
.cv-hero::after {
    content:'';position:absolute;right:60px;bottom:-120px;
    width:200px;height:200px;border-radius:50%;
    background:rgba(255,255,255,.04);
}
.cv-avatar {
    width:90px;height:90px;border-radius:50%;
    border:4px solid rgba(255,255,255,.35);
    object-fit:cover;flex-shrink:0;
    box-shadow:0 6px 24px rgba(0,0,0,.25);
}
.cv-hero-name { font-size:24px;font-weight:800;line-height:1.2;margin:0; }
.cv-hero-sub  { font-size:13px;opacity:.8;margin-top:4px; }
.cv-pill {
    display:inline-flex;align-items:center;gap:5px;
    background:rgba(255,255,255,.15);backdrop-filter:blur(4px);
    border:1px solid rgba(255,255,255,.2);
    border-radius:20px;padding:3px 12px;font-size:12px;
    white-space:nowrap;
}
.cv-pill.active-pill  { background:rgba(39,174,96,.3);border-color:rgba(39,174,96,.4); }
.cv-pill.blocked-pill { background:rgba(231,76,60,.3); border-color:rgba(231,76,60,.4); }
.cv-dot { width:7px;height:7px;border-radius:50%;display:inline-block; }

/* ── Stat cards ────────────────────────── */
.cv-stats-row { position:relative;z-index:2; }
.cv-stat {
    background:#fff;border-radius:16px;
    box-shadow:0 4px 20px rgba(0,0,0,.08);
    padding:20px 22px;
    display:flex;align-items:center;gap:16px;
    height:100%;
    transition:transform .15s,box-shadow .15s;
}
.cv-stat:hover { transform:translateY(-2px);box-shadow:0 8px 28px rgba(0,0,0,.12); }
.cv-stat-icon {
    width:54px;height:54px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;flex-shrink:0;
}
.cv-stat-val  { font-size:22px;font-weight:800;line-height:1;color:#1a1a2e; }
.cv-stat-lbl  { font-size:12px;color:#6c757d;margin-top:3px;font-weight:500; }

/* ── Section cards ─────────────────────── */
.cv-card {
    background:#fff;border-radius:16px;
    box-shadow:0 2px 12px rgba(0,0,0,.06);
    border:none;overflow:hidden;
    margin-bottom:20px;
}
.cv-card-head {
    padding:16px 20px;
    border-bottom:1px solid #f3f4f6;
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
}
.cv-card-title { font-size:15px;font-weight:700;color:#1a1a2e;margin:0; }

/* ── Info sidebar ──────────────────────── */
.cv-info-section { padding:0 20px 4px; }
.cv-info-label {
    font-size:10px;text-transform:uppercase;letter-spacing:.8px;
    color:#9ca3af;font-weight:600;margin-bottom:2px;
}
.cv-info-val { font-size:14px;font-weight:600;color:#1a1a2e;word-break:break-word; }
.cv-info-divider { height:1px;background:#f3f4f6;margin:14px 0; }

/* ── Table tweaks ──────────────────────── */
.cv-table th { font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;font-weight:700;background:#fafafa; }
.cv-table td { vertical-align:middle;font-size:13px; }
.cv-thumb { width:38px;height:38px;border-radius:8px;object-fit:cover;flex-shrink:0; }

/* ── Empty state ───────────────────────── */
.cv-empty { padding:30px;text-align:center;color:#aaa;font-size:13px; }
.cv-empty i { font-size:32px;display:block;margin-bottom:8px;color:#e0e0e0; }

/* ── Tabs ──────────────────────────────── */
.cv-tabs { border:none;gap:4px; }
.cv-tabs .nav-link {
    border:none;border-radius:8px;font-size:13px;font-weight:600;
    color:#6c757d;padding:7px 16px;
    transition:background .15s,color .15s;
}
.cv-tabs .nav-link.active,
.cv-tabs .nav-link:hover { background:#f0fafa;color:#00696e; }
.cv-tabs .nav-link.active { color:#00696e; }

.copy-btn { cursor:pointer;color:#9ca3af;transition:color .15s; }
.copy-btn:hover { color:#00696e; }
</style>
@endpush

@section('content')
<div class="content container-fluid cv-page">

    {{-- Back --}}
    <div class="mb-3 d-flex align-items-center gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-white">
            <i class="tio-arrow-backward mr-1"></i>Back
        </a>
        <span class="text-muted" style="font-size:13px;">Customer #{{ $customer->id }}</span>
    </div>

    {{-- ── HERO ── --}}
    <div class="cv-hero m-3">
        <div class="d-flex align-items-center gap-4 flex-wrap" style="position:relative;z-index:1;">
            <img class="cv-avatar onerror-image"
                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($customer->image, asset('storage/app/public/profile/').'/'.$customer->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                alt="{{ $customer->f_name }}">

            <div class="flex-grow-1">
                <p class="cv-hero-name">{{ $customer->f_name }}{{ $customer->l_name ? ' '.$customer->l_name : '' }}</p>
                <p class="cv-hero-sub">
                    @if($customer->phone)<i class="tio-android-phone-vs mr-1"></i>{{ $customer->phone }}@endif
                    @if($customer->email)<span class="mx-2">·</span><i class="tio-email mr-1"></i>{{ $customer->email }}@endif
                </p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="cv-pill {{ $customer->status ? 'active-pill' : 'blocked-pill' }}">
                        <span class="cv-dot" style="background:{{ $customer->status ? '#2ecc71' : '#e74c3c' }};"></span>
                        {{ $customer->status ? 'Active' : 'Blocked' }}
                    </span>
                    <span class="cv-pill"><i class="tio-calendar-month mr-1"></i> Joined {{ date('d M Y', strtotime($customer->created_at)) }}</span>
                    @if($customer->gst)
                        <span class="cv-pill"><i class="tio-receipt mr-1"></i> GST: {{ $customer->gst }}</span>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2" style="position:relative;z-index:1;">
                <a href="{{ route('admin.coupon.add-new', ['customer' => $customer->id]) }}"
                    class="btn btn-warning text-white font-semibold shadow-sm">
                    <i class="tio-add"></i> Create Coupon
                </a>
                <button type="button" class="btn btn-light font-semibold shadow-sm" data-toggle="modal" data-target="#editCustomerModal">
                    <i class="tio-edit"></i> Edit
                </button>
                <a href="{{ route('admin.customer.status', [$customer->id, $customer->status ? 0 : 1]) }}"
                    class="btn font-semibold shadow-sm {{ $customer->status ? 'btn-danger' : 'btn-success text-white' }}">
                    <i class="tio-{{ $customer->status ? 'block' : 'checkmark-circle' }}"></i>
                    {{ $customer->status ? 'Block' : 'Unblock' }}
                </a>
                <button type="button" class="btn btn-outline-light font-semibold shadow-sm" data-toggle="modal" data-target="#deleteCustomerModal">
                    <i class="tio-delete"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ── STATS ── --}}
    <div class="cv-stats-row px-3">
        <div class="row g-2">
            <div class="col-6 col-xl-3">
                <div class="cv-stat">
                    <div class="cv-stat-icon" style="background:#e8f1ff;color:#1a56db;">
                        <i class="tio-shopping-cart-outlined"></i>
                    </div>
                    <div>
                        <div class="cv-stat-val">{{ $orders->total() }}</div>
                        <div class="cv-stat-lbl">Total Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="cv-stat">
                    <div class="cv-stat-icon" style="background:#ecfdf5;color:#059669;">
                        <i class="tio-dollar"></i>
                    </div>
                    <div>
                        <div class="cv-stat-val">{{ \App\CentralLogics\Helpers::format_currency($total_order_amount[0]->total_order_amount) }}</div>
                        <div class="cv-stat-lbl">Total Spent</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="cv-stat">
                    <div class="cv-stat-icon" style="background:#fffbeb;color:#d97706;">
                        <i class="tio-wallet"></i>
                    </div>
                    <div>
                        <div class="cv-stat-val">{{ \App\CentralLogics\Helpers::format_currency($customer->wallet_balance ?? 0) }}</div>
                        <div class="cv-stat-lbl">Wallet Balance</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="cv-stat">
                    <div class="cv-stat-icon" style="background:#f5f3ff;color:#7c3aed;">
                        <i class="tio-star-outlined"></i>
                    </div>
                    <div>
                        <div class="cv-stat-val">{{ number_format($customer->loyalty_point ?? 0) }}</div>
                        <div class="cv-stat-lbl">Loyalty Points</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MAIN CONTENT ── --}}
    <div class="row mt-4 px-3">

        {{-- Left column: tabs with all tables --}}
        <div class="col-lg-8">
            <div class="cv-card">
                <div class="cv-card-head">
                    <ul class="nav cv-tabs" id="cvTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tab-orders">
                                Orders
                                <span class="badge badge-soft-secondary ml-1">{{ $orders->total() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-services">
                                Services
                                <span class="badge badge-soft-secondary ml-1">{{ count($services) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-wishlist">
                                Wishlist
                                <span class="badge badge-soft-secondary ml-1">{{ count($productWishlist) + count($storeWishlist) }}</span>
                            </a>
                        </li>
                    </ul>

                    {{-- Orders export (shows only on orders tab) --}}
                    <div id="orders-export-btn">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle"
                                href="javascript:;"
                                data-hs-unfold-options='{"target":"#ordersExportDrop","type":"css-animation"}'>
                                <i class="tio-download-to mr-1"></i>Export
                            </a>
                            <div id="ordersExportDrop" class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                                <a class="dropdown-item" href="{{ route('admin.customer.order-export', ['type'=>'excel','id'=>$customer->id,request()->getQueryString()]) }}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2" src="{{ asset('public/assets/admin/svg/components/excel.svg') }}" alt="">Excel
                                </a>
                                <a class="dropdown-item" href="{{ route('admin.customer.order-export', ['type'=>'csv','id'=>$customer->id,request()->getQueryString()]) }}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2" src="{{ asset('public/assets/admin/svg/components/placeholder-csv-format.svg') }}" alt="">.CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content">

                    {{-- Orders Tab --}}
                    <div class="tab-pane fade show active" id="tab-orders">
                        <div class="px-3 pt-3 pb-1">
                            <form class="search-form theme-style">
                                <div class="input-group input--group input-group-sm" style="max-width:280px;">
                                    <input type="search" name="search" class="form-control"
                                        placeholder="Search by order ID…"
                                        value="{{ request()->search }}">
                                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                </div>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-borderless table-nowrap table-align-middle cv-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="pl-4">#</th>
                                        <th>Order</th>
                                        <th>Store</th>
                                        <th>Status</th>
                                        <th class="text-center">Items</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $key => $order)
                                    <tr>
                                        <td class="pl-4">{{ $key + $orders->firstItem() }}</td>
                                        <td>
                                            <a class="font-semibold text-dark"
                                                href="{{ route($order->order_type=='parcel' ? 'admin.parcel.order.details' : 'admin.order.details', ['id'=>$order->id,'module_id'=>$order->module_id]) }}">
                                                #{{ $order->id }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($order->store)
                                                <a href="{{ route('admin.store.view',$order->store_id) }}" class="text--title">
                                                    {{ Str::limit($order->store->name,18,'…') }}
                                                </a>
                                            @else
                                                <span class="text-muted">Deleted</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $sm=['pending'=>['Pending','info'],'confirmed'=>['Confirmed','info'],'processing'=>['Processing','warning'],'picked_up'=>['Out for Delivery','warning'],'handover'=>['Handover','warning'],'delivered'=>['Delivered','success'],'accepted'=>['Accepted','success'],'canceled'=>['Cancelled','danger'],'failed'=>['Failed','danger'],'refund_requested'=>['Refund Req.','danger']];
                                                [$slbl,$scls]=$sm[$order->order_status]??[ucwords(str_replace('_',' ',$order->order_status)),'secondary'];
                                            @endphp
                                            <span class="badge badge-soft-{{ $scls }}">{{ $slbl }}</span>
                                        </td>
                                        <td class="text-center">{{ $order->details_count ?: '—' }}</td>
                                        <td>{{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</td>
                                        <td>
                                            <div style="font-size:13px;">{{ \App\CentralLogics\Helpers::date_format($order->created_at) }}</div>
                                            <small class="text-muted">{{ \App\CentralLogics\Helpers::time_format($order->created_at) }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--warning btn-outline-warning"
                                                    href="{{ route($order->order_type=='parcel'?'admin.parcel.order.details':'admin.order.details',['id'=>$order->id]) }}"
                                                    title="View"><i class="tio-visible"></i></a>
                                                <a class="btn action-btn btn--primary btn-outline-primary" target="_blank"
                                                    href="{{ route('admin.order.generate-invoice',[$order->id]) }}"
                                                    title="Invoice"><i class="tio-download-to"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="8">
                                        <div class="cv-empty"><i class="tio-shopping-cart-outlined"></i>No orders yet</div>
                                    </td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                        <div class="px-3 py-2">{{ $orders->links() }}</div>
                        @endif
                    </div>

                    {{-- Services Tab --}}
                    <div class="tab-pane fade" id="tab-services">
                        <div class="table-responsive">
                            <table class="table table-borderless table-nowrap table-align-middle cv-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="pl-4">#</th>
                                        <th>Service</th>
                                        <th>Category</th>
                                        <th>Store</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($services as $key => $svc)
                                    <tr>
                                        <td class="pl-4">{{ $key + 1 }}</td>
                                        <td class="font-semibold">{{ $svc->item_name }}</td>
                                        <td><span class="badge badge-soft-info">{{ $svc->category_name }}</span></td>
                                        <td>
                                            @php $sStore = $svc->vendor_id ? _getUserDetails($svc->vendor_id,'store') : null; @endphp
                                            @if($sStore)
                                                <a href="{{ route('admin.store.view',$svc->vendor_id) }}" class="text--title">
                                                    {{ Str::limit($sStore->name,18,'…') }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($svc->current_status=='Cancelled') <span class="badge badge-soft-danger">Cancelled</span>
                                            @elseif($svc->current_status=='Completed') <span class="badge badge-soft-success">Completed</span>
                                            @elseif($svc->current_status) <span class="badge badge-soft-warning">{{ $svc->current_status }}</span>
                                            @else <span class="badge badge-soft-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="font-size:13px;">{{ \App\CentralLogics\Helpers::date_format($svc->updated_at) }}</div>
                                            <small class="text-muted">{{ \App\CentralLogics\Helpers::time_format($svc->updated_at) }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a class="btn action-btn btn--warning btn-outline-warning"
                                                href="{{ route('admin.service.lead-detail',['id'=>$svc->service_id]) }}"
                                                title="View"><i class="tio-visible"></i></a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7">
                                        <div class="cv-empty"><i class="tio-grid-layout"></i>No service requests</div>
                                    </td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Wishlist Tab --}}
                    <div class="tab-pane fade" id="tab-wishlist">
                        <div class="row g-0">
                            <div class="col-md-6" style="border-right:1px solid #f3f4f6;">
                                <div class="px-3 pt-3 pb-1">
                                    <p class="mb-2" style="font-size:12px;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">
                                        Items <span class="badge badge-soft-secondary">{{ count($productWishlist) }}</span>
                                    </p>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-nowrap table-align-middle cv-table mb-0">
                                        <thead><tr><th class="pl-4">Item</th><th>Price</th><th></th></tr></thead>
                                        <tbody>
                                            @forelse($productWishlist as $item)
                                            <tr>
                                                <td class="pl-4">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img class="cv-thumb onerror-image"
                                                            data-onerror-image="{{ asset('public/assets/admin/img/100x100/1.png') }}"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($item->image??'', asset('storage/app/public/product').'/'.($item->image??''), asset('public/assets/admin/img/100x100/1.png'),'product/') }}"
                                                            alt="">
                                                        <span class="font-semibold" style="font-size:13px;">{{ Str::limit($item->name,22,'…') }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ \App\CentralLogics\Helpers::format_currency($item->price) }}</td>
                                                <td>
                                                    <a class="btn action-btn btn--warning btn-outline-warning btn-sm"
                                                        href="{{ route('admin.item.view',[$item->item_id]) }}"><i class="tio-visible"></i></a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="3"><div class="cv-empty"><i class="tio-heart-outlined"></i>Empty</div></td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="px-3 pt-3 pb-1">
                                    <p class="mb-2" style="font-size:12px;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">
                                        Stores <span class="badge badge-soft-secondary">{{ count($storeWishlist) }}</span>
                                    </p>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-nowrap table-align-middle cv-table mb-0">
                                        <thead><tr><th class="pl-4">Store</th><th>City</th><th></th></tr></thead>
                                        <tbody>
                                            @forelse($storeWishlist as $item)
                                            <tr>
                                                <td class="pl-4">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img class="cv-thumb onerror-image"
                                                            data-onerror-image="{{ asset('public/assets/admin/img/100x100/1.png') }}"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($item->logo??'', asset('storage/app/public/store').'/'.($item->logo??''), asset('public/assets/admin/img/100x100/1.png'),'store/') }}"
                                                            alt="">
                                                        <span class="font-semibold" style="font-size:13px;">{{ Str::limit($item->name,18,'…') }}</span>
                                                    </div>
                                                </td>
                                                <td><span class="badge badge-soft-info">{{ $item->zone_name }}</span></td>
                                                <td>
                                                    <a class="btn action-btn btn--warning btn-outline-warning btn-sm"
                                                        href="{{ route('admin.store.view',[$item->id]) }}"><i class="tio-visible"></i></a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="3"><div class="cv-empty"><i class="tio-heart-outlined"></i>Empty</div></td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- end tab-content --}}
            </div>
        </div>

        {{-- Right column: profile info --}}
        <div class="col-lg-4">
            <div class="cv-card">
                <div class="cv-card-head">
                    <span class="cv-card-title"><i class="tio-user-outlined mr-1" style="color:#00696e;"></i> Customer Profile</span>
                </div>

                {{-- Avatar centre --}}
                <div class="text-center pt-2 pb-2">
                    <img class="onerror-image"
                        style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e0f2f1;"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($customer->image, asset('storage/app/public/profile/').'/'.$customer->image, asset('public/assets/admin/img/160x160/img1.jpg'),'profile/') }}"
                        alt="">
                    <p class="mt-2 mb-0 font-semibold" style="font-size:16px;color:#1a1a2e;">
                        {{ $customer->f_name }} {{ $customer->l_name }}
                    </p>
                    <p class="text-muted" style="font-size:12px;">Customer #{{ $customer->id }}</p>
                </div>

                <hr class="mt-0" style="border-color:#f3f4f6;">

                @php
                    $infoRows = [
                        ['icon'=>'tio-android-phone-vs','label'=>'Phone',    'val'=>$customer->phone,   'copy'=>true],
                        ['icon'=>'tio-email',           'label'=>'Email',    'val'=>$customer->email,   'copy'=>false,'mailto'=>true],
                        ['icon'=>'tio-receipt',         'label'=>'GST',      'val'=>$customer->gst,     'copy'=>false],
                        ['icon'=>'tio-map-arrow-top',   'label'=>'Address',  'val'=>$customer->address, 'copy'=>false],
                        ['icon'=>'tio-calendar-month',  'label'=>'Joined',   'val'=>date('d M Y', strtotime($customer->created_at)), 'copy'=>false],
                    ];
                @endphp

                @foreach($infoRows as $row)
                @if(!empty($row['val']))
                <div class="cv-info-section py-2">
                    <div class="d-flex align-items-start gap-3">
                        <div style="width:34px;height:34px;border-radius:10px;background:#f0fafa;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#00696e;font-size:16px;">
                            <i class="{{ $row['icon'] }}"></i>
                        </div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="cv-info-label">{{ $row['label'] }}</div>
                            <div class="cv-info-val d-flex align-items-center gap-1">
                                @if(!empty($row['mailto']))
                                    <a href="mailto:{{ $row['val'] }}" class="text-dark" style="font-weight:600;">{{ $row['val'] }}</a>
                                @else
                                    <span class="textToCopy-{{ $loop->index }}">{{ $row['val'] }}</span>
                                @endif
                                @if($row['copy'])
                                    <button class="copy-btn bg-transparent border-0 p-0 copy-trigger" data-target="{{ $loop->index }}">
                                        <i class="tio-copy"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cv-info-divider"></div>
                @endif
                @endforeach

                @if($customer->addresses->count())
                <div class="cv-info-section pb-3">
                    <div class="cv-info-label mb-2">Saved Addresses</div>
                    @foreach($customer->addresses as $addr)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $addr->latitude }},{{ $addr->longitude }}"
                        target="_blank"
                        class="d-flex align-items-start gap-2 mb-2 text-dark" style="font-size:13px;">
                        <i class="tio-poi text-danger mt-1" style="flex-shrink:0;"></i>
                        <span>{{ $addr->address }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

            </div>
        </div>

    </div>
</div>

{{-- ── Edit Modal ── --}}
<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
            <form action="{{ route('admin.customer.update', $customer->id) }}" method="POST">
                @csrf
                @method('POST')
                <div class="modal-header" style="background:#f8fffe;border-bottom:1px solid #e8f5f5;">
                    <h5 class="modal-title font-semibold"><i class="tio-edit mr-2" style="color:#00696e;"></i>Edit Customer</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="input-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="f_name" class="form-control" value="{{ $customer->f_name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="input-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="input-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $customer->email }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="input-label">GST Number</label>
                            <input type="text" name="gst" class="form-control" value="{{ $customer->gst }}" placeholder="Ex: 22AAAAA0000A1Z5">
                        </div>
                        <div class="col-12 mb-0">
                            <label class="input-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ $customer->address }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;">
                    <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Delete Confirm Modal ── --}}
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content" style="border-radius:16px;border:none;text-align:center;overflow:hidden;">
            <div class="modal-body p-4">
                <div style="width:60px;height:60px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#e74c3c;">
                    <i class="tio-delete"></i>
                </div>
                <h5 class="font-semibold mb-2">Delete Customer?</h5>
                <p class="text-muted mb-4" style="font-size:13px;">
                    This will permanently delete <strong>{{ $customer->f_name }}</strong> and all associated data. This cannot be undone.
                </p>
                <form action="{{ route('admin.customer.delete', $customer->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100 mb-2">Yes, Delete</button>
                    <button type="button" class="btn btn-white w-100" data-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
$(document).ready(function () {
    // Tab toggle — hide export btn on non-orders tabs
    $('[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#tab-orders') {
            $('#orders-export-btn').show();
        } else {
            $('#orders-export-btn').hide();
        }
    });

    // Copy button
    $(document).on('click', '.copy-trigger', function () {
        var idx  = $(this).data('target');
        var text = $('.textToCopy-' + idx).text().trim();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            var t = $('<textarea>').val(text).css({position:'absolute',left:'-9999px'}).appendTo('body').select();
            document.execCommand('copy');
            t.remove();
        }
        $(this).html('Copied!').css('font-size','10px');
        setTimeout(() => $(this).html('<i class="tio-copy"></i>').css('font-size',''), 1500);
    });
});
</script>
@endpush
