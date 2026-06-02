@extends('layouts.admin.app')
@section('title', 'Pricing')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .pricing-tab-nav .nav-link {
            color: #677788;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 0;
            border-bottom: 3px solid transparent;
        }
        .pricing-tab-nav .nav-link.active {
            color: #377dff;
            border-bottom-color: #377dff;
            background: transparent;
        }
        .pricing-tab-nav {
            border-bottom: 1px solid #e7eaf3;
            margin-bottom: 0;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-card .stat-val { font-size: 22px; font-weight: 700; }
        .stat-card .stat-lbl { font-size: 12px; color: #8c98a4; margin-top: 2px; }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="page-header">
        <div class="d-flex align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-dollar-outlined nav-icon" style="font-size:22px"></i>
                </span>
                <span>Pricing</span>
            </h1>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="card mb-0">
        <ul class="nav pricing-tab-nav px-3" id="pricingTabs">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'wallet' ? 'active' : '' }}"
                   href="{{ route('admin.pricing.index', ['tab' => 'wallet']) }}">
                    <i class="tio-wallet-outlined mr-1"></i> Vendor Wallet
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'modules' ? 'active' : '' }}"
                   href="{{ route('admin.pricing.index', ['tab' => 'modules']) }}">
                    <i class="tio-dashboard mr-1"></i> Module Pricing
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'templates' ? 'active' : '' }}"
                   href="{{ route('admin.pricing.index', ['tab' => 'templates']) }}">
                    <i class="tio-layout mr-1"></i> Webpage Templates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'platform-fee' ? 'active' : '' }}"
                   href="{{ route('admin.pricing.index', ['tab' => 'platform-fee']) }}">
                    <i class="tio-receipt mr-1"></i> Platform Fee
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'zone-wallet' ? 'active' : '' }}"
                   href="{{ route('admin.pricing.index', ['tab' => 'zone-wallet']) }}">
                    <i class="tio-poi-outlined mr-1"></i> Zone Wallet Minimums
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'lead-charges' ? 'active' : '' }}"
                   href="{{ route('admin.pricing.index', ['tab' => 'lead-charges']) }}">
                    <i class="tio-currency-dollar mr-1"></i> Lead Charges
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'lead-subscriptions' ? 'active' : '' }}"
                   href="{{ route('admin.pricing.index', ['tab' => 'lead-subscriptions']) }}">
                    <i class="tio-bookmarks mr-1"></i> Lead Subscriptions
                </a>
            </li>
        </ul>
    </div>

    <div class="mt-3">

        {{-- ===== TAB: VENDOR WALLET ===== --}}
        @if($tab === 'wallet')
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-settings mr-1"></i> Wallet Recharge Settings</h5>
                        <a href="{{ route('admin.store.wallet.index') }}" class="btn btn--primary btn-sm">
                            <i class="tio-open-in-new mr-1"></i> View All Wallets
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.pricing.wallet-settings') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-sm-6 col-lg-3 form-group">
                                    <label class="form-label">Wallet Recharge GST %</label>
                                    <input type="number" name="wallet_recharge_gst_percent" class="form-control"
                                           min="0" step="0.01" placeholder="Ex: 10"
                                           value="{{ $wallet_recharge_gst_percent }}" required>
                                </div>
                                <div class="col-sm-6 col-lg-3 form-group">
                                    <label class="form-label">Wallet Recharge GST Status</label>
                                    <select name="wallet_recharge_gst_status" class="form-control">
                                        <option value="included" {{ $wallet_recharge_gst_status === 'included' ? 'selected' : '' }}>Included</option>
                                        <option value="excluded" {{ $wallet_recharge_gst_status === 'excluded' ? 'selected' : '' }}>Excluded</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-lg-3 form-group">
                                    <label class="form-label">Wallet Recharge HSN</label>
                                    <input type="text" name="wallet_recharge_hsn" class="form-control"
                                           placeholder="HSN" value="{{ $wallet_recharge_hsn }}" required>
                                </div>
                                <div class="col-sm-6 col-lg-3 form-group">
                                    <label class="form-label">Wallet Minimum Balance</label>
                                    <input type="number" name="wallet_min_balance" class="form-control"
                                           min="0" placeholder="Ex: 100"
                                           value="{{ $wallet_min_balance }}">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn--primary">Save Wallet Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ===== TAB: MODULE PRICING ===== --}}
        @if($tab === 'modules')
        <div class="row">
            {{-- Plan Durations --}}
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="tio-time mr-1"></i> Plan Durations
                        </h5>
                        <button class="btn btn-sm btn--primary" data-toggle="modal" data-target="#addDurationModal">
                            + Add Duration
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Label</th>
                                        <th>Months</th>
                                        <th>Sort</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($plan_durations as $dur)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $dur->label }}</td>
                                        <td>{{ $dur->months }}</td>
                                        <td>{{ $dur->sort_order }}</td>
                                        <td>
                                            <form action="{{ route('admin.plan.duration.toggle', $dur->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs {{ $dur->is_active ? 'btn-success' : 'btn-secondary' }}">
                                                    {{ $dur->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <a class="btn action-btn btn--primary btn-outline-primary btn-sm"
                                               data-toggle="modal" data-target="#editDurationModal{{ $dur->id }}">
                                                <i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger btn-sm"
                                               href="{{ route('admin.plan.duration.delete', $dur->id) }}"
                                               onclick="return confirm('Delete this duration?')">
                                                <i class="tio-delete"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    {{-- Edit Duration Modal --}}
                                    <div class="modal fade" id="editDurationModal{{ $dur->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Duration</h5>
                                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                </div>
                                                <form action="{{ route('admin.plan.duration.update', $dur->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Label</label>
                                                            <input class="form-control" type="text" name="label" value="{{ $dur->label }}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Months</label>
                                                            <input class="form-control" type="number" name="months" value="{{ $dur->months }}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Sort Order</label>
                                                            <input class="form-control" type="number" name="sort_order" value="{{ $dur->sort_order }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn--primary">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No durations found</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Module Pricing --}}
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="tio-dashboard mr-1"></i> Module Pricing
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Module</th>
                                        <th>Price/Month</th>
                                        <th>Discounts</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sub_modules as $type)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $type->name }}</td>
                                        <td>{{ _price($type->price_per_month) }}</td>
                                        <td>
                                            @foreach($plan_durations as $dur)
                                                <span class="badge badge-soft-info mr-1">
                                                    {{ $dur->label }}: {{ ($sub_module_discounts[$type->id] ?? collect())->firstWhere('plan_duration_id', $dur->id)?->discount ?? 0 }}%
                                                </span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <a class="btn action-btn btn--primary btn-outline-primary btn-sm"
                                               data-toggle="modal" data-target="#editModuleModal{{ $type->id }}">
                                                <i class="tio-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    {{-- Edit Module Modal --}}
                                    <div class="modal fade" id="editModuleModal{{ $type->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit – {{ $type->name }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                </div>
                                                <form action="{{ route('admin.plan.update-modules') }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $type->id }}">
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="form-group col-12">
                                                                <label>Name</label>
                                                                <input class="form-control" type="text" name="name" value="{{ $type->name }}">
                                                            </div>
                                                            <div class="form-group col-md-6">
                                                                <label>Price Per Month ({{ \App\CentralLogics\Helpers::currency_symbol() }})</label>
                                                                <input class="form-control" type="number" name="price_per_month" value="{{ $type->price_per_month }}">
                                                            </div>
                                                            @foreach($plan_durations as $dur)
                                                            <div class="form-group col-md-6">
                                                                <label>Discount – {{ $dur->label }} ({{ $dur->months }}M) %</label>
                                                                <input class="form-control" type="number" step="0.01"
                                                                       name="discounts[{{ $dur->id }}]"
                                                                       value="{{ ($sub_module_discounts[$type->id] ?? collect())->firstWhere('plan_duration_id', $dur->id)?->discount ?? 0 }}">
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn--primary">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">No modules found</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- GST Settings --}}
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="tio-receipt mr-1"></i> GST Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.plan.gst-settings') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label>GST Mode</label>
                                    <select class="form-control" name="gst_mode">
                                        <option value="exclude" {{ ($gst_settings['gst_mode'] ?? 'exclude') === 'exclude' ? 'selected' : '' }}>Exclude GST</option>
                                        <option value="include" {{ ($gst_settings['gst_mode'] ?? '') === 'include' ? 'selected' : '' }}>Include GST</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>GST %</label>
                                    <input class="form-control" type="number" step="0.01" name="gst_percent"
                                           value="{{ $gst_settings['gst_percent'] ?? 0 }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>HSN Code</label>
                                    <input class="form-control" type="text" name="hsn"
                                           value="{{ $gst_settings['hsn'] ?? '' }}" placeholder="e.g. 998314">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn--primary">Save GST Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Duration Modal --}}
        <div class="modal fade" id="addDurationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Duration</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('admin.plan.duration.store') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Label</label>
                                <input class="form-control" type="text" name="label" placeholder="e.g. Quarterly" required>
                            </div>
                            <div class="form-group">
                                <label>Months</label>
                                <input class="form-control" type="number" name="months" placeholder="e.g. 3" required>
                            </div>
                            <div class="form-group">
                                <label>Sort Order</label>
                                <input class="form-control" type="number" name="sort_order" value="0">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn--primary">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- ===== TAB: WEBPAGE TEMPLATES ===== --}}
        @if($tab === 'templates')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="tio-layout mr-1"></i> Webpage Templates Pricing</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Preview</th>
                                <th>Template</th>
                                <th>Name</th>
                                <th>Price ({{ \App\CentralLogics\Helpers::currency_symbol() }})</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                            <tr id="tmpl-row-{{ $template->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if(!empty($template->thumbnail))
                                        <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($template->thumbnail, asset('storage/app/public/uploaded/templates/' . $template->thumbnail), asset('public/assets/admin/img/160x160/img2.jpg'), 'uploaded/templates/') }}"
                                             class="thumb-img onerror-image"
                                             data-full="{{ asset('storage/app/public/uploaded/templates/' . $template->thumbnail) }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             alt="Template"
                                             style="width:70px;height:50px;object-fit:cover;border-radius:6px;cursor:zoom-in;border:1px solid #e0e0e0;">
                                    @else
                                        <div style="width:70px;height:50px;border-radius:6px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:11px;border:1px dashed #ccc;">No image</div>
                                    @endif
                                </td>
                                <td><span class="badge badge-soft-info">Template {{ $template->id }}</span></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" style="min-width:160px;"
                                           id="tmpl-name-{{ $template->id }}"
                                           value="{{ $template->name ?? 'Template ' . $template->id }}">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm" style="max-width:140px;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ \App\CentralLogics\Helpers::currency_symbol() }}</span>
                                        </div>
                                        <input type="number" class="form-control"
                                               id="tmpl-price-{{ $template->id }}"
                                               value="{{ $template->price ?? 0 }}" min="0" step="0.01">
                                    </div>
                                </td>
                                <td>
                                    <label class="toggle-switch toggle-switch-sm d-flex align-items-center" style="gap:8px;">
                                        <input type="checkbox" class="toggle-switch-input tmpl-status-toggle"
                                               data-id="{{ $template->id }}"
                                               {{ $template->status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                        <span class="toggle-switch-content">
                                            <span class="d-block tmpl-status-label-{{ $template->id }}">
                                                {{ $template->status ? 'Active' : 'Inactive' }}
                                            </span>
                                        </span>
                                    </label>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn--primary tmpl-save-btn" data-id="{{ $template->id }}">Save</button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No templates found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Lightbox --}}
        <div id="imgLightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:9999;align-items:center;justify-content:center;">
            <span style="position:absolute;top:18px;right:24px;font-size:32px;color:#fff;cursor:pointer;" id="closeLb">&times;</span>
            <img src="" id="lightboxImg" alt="Preview" style="max-width:90vw;max-height:88vh;border-radius:10px;">
        </div>
        @endif

        {{-- ===== TAB: PLATFORM FEE ===== --}}
        @if($tab === 'platform-fee')
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="tio-receipt mr-1"></i> Platform Fee (Monthly Deduction)</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.pricing.platform-fee') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="input-label">
                                        Subscribed Vendors Fee ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                    </label>
                                    <input type="number" name="platform_fee_subscribed" class="form-control"
                                           min="0" step="0.01" placeholder="0.00"
                                           value="{{ $platform_fee_subscribed }}">
                                    <small class="text-muted">Monthly fee deducted from subscribed vendor wallets</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="input-label">
                                        Unsubscribed Vendors Fee ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                    </label>
                                    <input type="number" name="platform_fee_unsubscribed" class="form-control"
                                           min="0" step="0.01" placeholder="0.00"
                                           value="{{ $platform_fee_unsubscribed }}">
                                    <small class="text-muted">Monthly fee deducted from unsubscribed vendor wallets</small>
                                </div>
                            </div>
                            <div class="alert alert-soft-info d-flex align-items-center mt-2">
                                <i class="tio-info-outined mr-2"></i>
                                <span>Deductions run automatically on the <strong>1st of every month</strong> at midnight. Each vendor is charged only once per month.</span>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn--primary">Save Platform Fee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ===== TAB: ZONE WALLET MINIMUMS ===== --}}
        @if($tab === 'zone-wallet')
        <div class="row">
            <div class="col-md-5 mb-3">
                <div class="card h-100">
                    <div class="card-header flex-column align-items-start">
                        <h5 class="card-title mb-0"><i class="tio-poi-outlined mr-1"></i> Add Zone Wallet Minimum</h5>
                        <p class="text-muted small mb-0">Minimum wallet balance a vendor needs (per zone, optionally per category) to receive leads. Vendors below this — and without an active lead subscription — are skipped during lead distribution. A value of <strong>0</strong> disables the requirement for that zone/category.</p>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.service.zone-wallet-config-save') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Zone <span class="text-danger">*</span></label>
                                <select name="zone_id" class="form-control js-select2-custom" required>
                                    <option value="">Select Zone</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category <small class="text-muted">(optional — leave empty for zone level)</small></label>
                                <select name="category_id[]" class="form-control js-select2-custom" multiple>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Minimum Balance <span class="text-danger">*</span></label>
                                <input type="number" name="min_balance" class="form-control" placeholder="Ex: 100" min="0" step="0.01" required>
                            </div>
                            <button type="submit" class="btn btn--primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Zone Wallet Configs</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>SL</th>
                                    <th>Zone</th>
                                    <th>Category</th>
                                    <th>Min Balance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($zoneWalletConfigs as $config)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $config->zone?->name ?? 'N/A' }}</td>
                                        <td>{{ $config->category?->name ?? 'All Categories' }}</td>
                                        <td>
                                            <form action="{{ route('admin.service.zone-wallet-config-update', $config->id) }}" method="post" class="d-flex align-items-center">
                                                @csrf
                                                <input type="number" name="min_balance" value="{{ $config->min_balance }}" class="form-control form-control-sm" style="width: 110px;" min="0" step="0.01" required>
                                                <button type="submit" class="btn btn-sm btn--primary ml-2">Update</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn--danger btn-outline-danger" href="{{ route('admin.service.zone-wallet-config-delete', $config->id) }}" title="Delete">
                                                <i class="tio-delete-outlined"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No zone configs added yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ===== TAB: LEAD CHARGES ===== --}}
        @if($tab === 'lead-charges')
            @include('admin-views.service.partials._lead_charge_form')
            @include('admin-views.service.partials._lead_charges_list')
        @endif

        {{-- ===== TAB: LEAD SUBSCRIPTIONS ===== --}}
        @if($tab === 'lead-subscriptions')
        <div class="row">
            <div class="col-md-5 mb-3">
                <div class="card h-100">
                    <div class="card-header flex-column align-items-start">
                        <h5 class="card-title mb-0"><i class="tio-bookmarks mr-1"></i> Add Lead Subscription Plan</h5>
                        <p class="text-muted small mb-0">A vendor with an active lead subscription receives leads without needing the minimum wallet balance. "Dedicated" plans route a zone/category's leads exclusively to the subscriber.</p>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.service.lead-subscriptions.plan.store') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Ex: Monthly Shared" maxlength="100" required>
                            </div>
                            <div class="row">
                                <div class="col-6 form-group">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type" class="form-control" required>
                                        <option value="shared">Shared</option>
                                        <option value="dedicated">Dedicated</option>
                                    </select>
                                </div>
                                <div class="col-6 form-group">
                                    <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                    <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Duration (days) <span class="text-danger">*</span></label>
                                <input type="number" name="duration_days" class="form-control" min="1" placeholder="Ex: 30" required>
                            </div>
                            <div class="row">
                                <div class="col-6 form-group">
                                    <label class="form-label">Zone <small class="text-muted">(optional)</small></label>
                                    <select name="zone_id" class="form-control js-select2-custom">
                                        <option value="">Any</option>
                                        @foreach ($zones as $zone)
                                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 form-group">
                                    <label class="form-label">Category <small class="text-muted">(optional)</small></label>
                                    <select name="category_id" class="form-control js-select2-custom">
                                        <option value="">Any</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn--primary">Save Plan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lead Subscription Plans</h5>
                        <a href="{{ route('admin.service.lead-subscriptions.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="tio-open-in-new mr-1"></i> Grant / Manage
                        </a>
                    </div>
                    <div class="card-body">
                        @forelse ($leadSubPlans as $plan)
                            <form action="{{ route('admin.service.lead-subscriptions.plan.update', $plan->id) }}" method="post"
                                  class="d-flex align-items-center flex-wrap border-bottom pb-2 mb-2" style="gap:8px;">
                                @csrf
                                <span class="badge badge-soft-{{ $plan->type === 'dedicated' ? 'warning' : 'info' }}">{{ ucfirst($plan->type) }}</span>
                                <input type="text" name="name" value="{{ $plan->name }}" class="form-control form-control-sm" style="width:150px;" required>
                                <input type="number" name="price" value="{{ $plan->price }}" class="form-control form-control-sm" style="width:90px;" min="0" step="0.01" required title="Price ₹">
                                <input type="number" name="duration_days" value="{{ $plan->duration_days }}" class="form-control form-control-sm" style="width:70px;" min="1" required title="Days">
                                <select name="status" class="form-control form-control-sm" style="width:95px;">
                                    <option value="1" {{ $plan->status ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !$plan->status ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn--primary">Save</button>
                                <a href="{{ route('admin.service.lead-subscriptions.plan.destroy', $plan->id) }}"
                                   class="btn btn-sm btn--danger btn-outline-danger" title="Delete"
                                   onclick="return confirm('Delete this plan?')"><i class="tio-delete-outlined"></i></a>
                            </form>
                        @empty
                            <p class="text-center text-muted py-3 mb-0">No lead subscription plans yet</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endif


    </div>
</div>
@endsection

@push('script_2')
<script>
@if($tab === 'templates')
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Thumbnail lightbox
    $(document).on('click', '.thumb-img', function () {
        $('#lightboxImg').attr('src', $(this).data('full'));
        $('#imgLightbox').css('display', 'flex');
    });
    $('#closeLb, #imgLightbox').on('click', function (e) {
        if ($(e.target).is('#imgLightbox') || $(e.target).is('#closeLb')) {
            $('#imgLightbox').hide();
        }
    });

    // Save template
    $(document).on('click', '.tmpl-save-btn', function () {
        var id     = $(this).data('id');
        var price  = $('#tmpl-price-' + id).val();
        var name   = $('#tmpl-name-' + id).val();
        var status = $('#tmpl-row-' + id + ' .tmpl-status-toggle').is(':checked') ? 1 : 0;
        var btn    = $(this);

        btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: "{{ route('admin.webpage-templates.update') }}",
            method: 'POST',
            data: { _token: csrfToken, id: id, price: price, name: name, status: status },
            success: function (res) { toastr.success(res.message || 'Template updated.'); },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Error saving.';
                toastr.error(msg);
            },
            complete: function () { btn.prop('disabled', false).text('Save'); }
        });
    });

    // Status toggle
    $(document).on('change', '.tmpl-status-toggle', function () {
        var id = $(this).data('id'), $t = $(this);
        $.ajax({
            url: "{{ route('admin.webpage-templates.toggle') }}",
            method: 'POST',
            data: { _token: csrfToken, id: id },
            success: function (res) {
                $('.tmpl-status-label-' + id).text(res.status ? 'Active' : 'Inactive');
                toastr.success('Status updated.');
            },
            error: function () {
                $t.prop('checked', !$t.prop('checked'));
                toastr.error('Failed to update status.');
            }
        });
    });
@endif
</script>
@endpush
