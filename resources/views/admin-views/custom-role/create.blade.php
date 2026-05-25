@extends('layouts.admin.app')
@section('title',translate('messages.custom_role'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/role.png')}}" class="w--26" alt="">
            </span>
            <span>
                {{translate('messages.employee_Role')}}
            </span>
        </h1>
    </div>
    <!-- End Page Header -->  
    <!-- Content Row -->
    <div class="row">
        <div class="col-md-12">  
            <div class="card">  
                <div class="card-body">
                    <form action="{{route('admin.users.custom-role.create.post')}}" method="post">
                        @csrf
                        @if ($language)
                     
                            <div class="form-group lang_form" id="default-form">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.role_name')}}  <span class="form-label-secondary text-danger"
                                    data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('messages.Required.')}}"> *
                                    </span>
                                </label>
                                <input type="text" name="name[]" class="form-control" placeholder="{{translate('role_name_example')}}" maxlength="191">
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                                @foreach($language as $lang)
                                    <div class="form-group d-none lang_form" id="{{$lang}}-form">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.role_name')}} ({{strtoupper($lang)}})</label>
                                        <input type="text" name="name[]" class="form-control" placeholder="{{translate('role_name_example')}}" maxlength="191">
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{$lang}}">
                                @endforeach
                            @else
                                <div class="form-group">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.role_name')}}</label>
                                    <input type="text" name="name" class="form-control" placeholder="{{translate('role_name_example')}}" value="{{old('name')}}" maxlength="191">
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                            @endif

                        <div class="d-flex flex-wrap select--all-checkes">
                            <h5 class="input-label m-0 text-capitalize">{{translate('messages.Set_permission')}} : </h5>
                            <div class="check-item pb-0 w-auto">
                                <div class="form-group form-check form--check m-0 ml-2">
                                    <input type="checkbox" name="modules[]" value="collect_cash" class="form-check-input" id="select-all">
                                    <label class="form-check-label ml-2" for="select-all">{{ translate('select_all') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="check--item-wrapper">
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="collect_cash" class="form-check-input"
                                           id="collect_cash">
                                    <label class="form-check-label qcont text-dark" for="collect_cash">{{translate('messages.collect_Cash')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="addon" class="form-check-input"
                                           id="addon">
                                    <label class="form-check-label qcont text-dark" for="addon">{{translate('messages.addon')}}</label>
                                </div>
                            </div> --}}
                            @if(isAddonActive('attendance'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="attendance" class="form-check-input granular_permission_check"
                                           id="attendance">
                                    <label class="form-check-label qcont text-dark" for="attendance">{{translate('messages.attendance')}}</label>
                                </div>
                            </div>
                            @endif
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="attribute" class="form-check-input"
                                           id="attribute">
                                    <label class="form-check-label qcont text-dark" for="attribute">{{translate('messages.attribute')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="banner" class="form-check-input"
                                           id="banner">
                                    <label class="form-check-label qcont text-dark" for="banner">{{translate('messages.banner')}}</label>
                                </div>
                            </div>
                            @if(isAddonActive('billing'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="billing" class="form-check-input granular_permission_check"
                                           id="billing">
                                    <label class="form-check-label qcont text-dark" for="billing">{{translate('messages.billing')}}</label>
                                </div>
                            </div>
                            @endif
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="blog" class="form-check-input"
                                           id="blog">
                                    <label class="form-check-label qcont text-dark" for="blog">{{translate('messages.blog')}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="campaign" class="form-check-input"
                                           id="campaign">
                                    <label class="form-check-label qcont text-dark" for="campaign">{{translate('messages.campaign')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="category" class="form-check-input"
                                           id="category">
                                    <label class="form-check-label qcont text-dark" for="category">{{translate('messages.category')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="coupon" class="form-check-input"
                                           id="coupon">
                                    <label class="form-check-label qcont text-dark" for="coupon">{{translate('messages.coupon')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="customer_management" class="form-check-input granular_permission_check"
                                           id="customer_management">
                                    <label class="form-check-label qcont text-dark" for="customer_management">{{translate('messages.customer_management')}}</label>
                                </div>
                            </div>
                            @if(isAddonActive('client_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="client_manage" class="form-check-input granular_permission_check"
                                           id="client_manage">
                                    <label class="form-check-label qcont text-dark" for="client_manage">{{translate('messages.mychitti_client_management')}}</label>
                                </div>
                            </div>
                            @endif
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="deliveryman" class="form-check-input"
                                           id="deliveryman">
                                    <label class="form-check-label qcont text-dark" for="deliveryman">{{translate('messages.deliveryman')}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="provide_dm_earning" class="form-check-input"
                                           id="provide_dm_earning">
                                    <label class="form-check-label qcont text-dark" for="provide_dm_earning">{{translate('messages.provide_dm_earning')}}</label>
                                </div>
                            </div> --}}
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="employee" class="form-check-input granular_permission_check"
                                           id="employee">
                                    <label class="form-check-label qcont text-dark" for="employee">{{translate('messages.Employee')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="employee_role" class="form-check-input"
                                           id="employee_role" >
                                    <label class="form-check-label qcont text-dark" for="employee_role">{{translate('messages.employee_role')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="item" class="form-check-input"
                                           id="item">
                                    <label class="form-check-label qcont text-dark" for="item">{{Config::get('module.current_module_id') == 6 ? 'Service' : 'Product'}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="leave" class="form-check-input granular_permission_check"
                                           id="leave">
                                    <label class="form-check-label qcont text-dark" for="leave">{{ translate('messages.leave')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="notification" class="form-check-input"
                                           id="notification">
                                    <label class="form-check-label qcont text-dark" for="notification">{{translate('messages.notification')}}</label>
                                </div>
                            </div>
                            @if (Config::get('module.current_module_id')== 5)
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="orders" class="form-check-input" id="orders">
                                        <label class="form-check-label text-dark " for="order">Orders</label>
                                    </div>
                                </div>
                            @elseif(isAddonActive('leads_manage'))
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="leads_manage"
                                            class="form-check-input granular_permission_check" id="leads_manage">
                                        <label class="form-check-label text-dark " for="leads_manage">Service Leads</label>
                                    </div>
                                </div>
                            @endif
                            
                            @if(isAddonActive('inventory_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="inventory_manage"
                                        class="form-check-input granular_permission_check" id="inventory_manage">
                                    <label class="form-check-label text-dark" for="inventory_manage">Inventory Manage</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('task_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="task_manage"
                                        class="form-check-input granular_permission_check" id="task_manage">
                                    <label class="form-check-label text-dark" for="task_manage">Task Management</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('projects_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="projects_manage"
                                        class="form-check-input granular_permission_check" id="projects_manage">
                                    <label class="form-check-label text-dark" for="projects_manage">Project Management</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('hr_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="hr_manage"
                                        class="form-check-input granular_permission_check" id="hr_manage">
                                    <label class="form-check-label  text-dark" for="hr_manage">HR Management</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('account_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="account_manage"
                                        class="form-check-input granular_permission_check" id="account_manage">
                                    <label class="form-check-label  text-dark" for="account_manage">Account Management</label>
                                </div>
                            </div>
                            @endif
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="staff_manage" class="form-check-input"
                                        id="staff_manage">
                                    <label class="form-check-label input-label " for="staff_manage">Staff Management</label>
                                </div>
                            </div> --}}
                            @if(isAddonActive('client_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="client_manage"
                                        class="form-check-input granular_permission_check" id="client_manage">
                                    <label class="form-check-label  text-dark" for="client_manage">Client Management</label>
                                </div>
                            </div>
                            @endif
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="salary" class="form-check-input granular_permission_check"
                                           id="salary">
                                    <label class="form-check-label qcont text-dark" for="salary">{{translate('messages.salary')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="store" class="form-check-input granular_permission_check"
                                           id="store">
                                    <label class="form-check-label qcont text-dark" for="store">{{translate('messages.store')}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="store_add_edit" class="form-check-input"
                                           id="store_add_edit">
                                    <label class="form-check-label qcont text-dark" for="store_add_edit">{{translate('messages.store_add & edit')}}</label>
                                </div>
                            </div>
                             <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="store_documents" class="form-check-input"
                                           id="store_documents">
                                    <label class="form-check-label qcont text-dark" for="store_documents">{{translate('messages.store_documents')}}</label>
                                </div>
                            </div> --}}
                            @if(isAddonActive('billing'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="billing" class="form-check-input granular_permission_check"
                                           id="billing">
                                    <label class="form-check-label qcont text-dark" for="billing"> Billing</label>
                                </div>
                            </div>
                            @endif
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="google_ads" class="form-check-input"
                                           id="google_ads">
                                    <label class="form-check-label qcont text-dark" for="google_ads">Google Ads</label>
                                </div>
                            </div>
                            @if(isAddonActive('quotaiton_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="quotaiton_manage" class="form-check-input granular_permission_check"
                                           id="quotaiton_manage">
                                    <label class="form-check-label qcont text-dark" for="quotaiton_manage">Quotation Manage</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('projects_manage'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="projects_manage" class="form-check-input granular_permission_check"
                                           id="projects_manage">
                                    <label class="form-check-label qcont text-dark" for="projects_manage">Project Manage</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('support_ticket'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="support_ticket" class="form-check-input granular_permission_check"
                                           id="support_ticket">
                                    <label class="form-check-label qcont text-dark" for="support_ticket">Support Tickets</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('sales_crm'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="sales_crm" class="form-check-input granular_permission_check"
                                           id="sales_crm">
                                    <label class="form-check-label qcont text-dark" for="sales_crm">Sales &amp; Marketing CRM</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('ai_agent'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="ai_agent" class="form-check-input granular_permission_check"
                                           id="ai_agent">
                                    <label class="form-check-label qcont text-dark" for="ai_agent">AI Agent</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('analytics'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="analytics" class="form-check-input granular_permission_check"
                                           id="analytics">
                                    <label class="form-check-label qcont text-dark" for="analytics">Analytics</label>
                                </div>
                            </div>
                            @endif
                            @if(isAddonActive('logs'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="logs" class="form-check-input granular_permission_check"
                                           id="logs">
                                    <label class="form-check-label qcont text-dark" for="logs">Logs</label>
                                </div>
                            </div>
                            @endif
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="report" class="form-check-input"
                                            id="report">
                                    <label class="form-check-label qcont text-dark" for="report">{{translate('messages.report')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="settings" class="form-check-input"
                                           id="settings">
                                    <label class="form-check-label qcont text-dark" for="settings">{{translate('messages.settings')}}</label>
                                </div>
                            </div>

                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="withdraw_list" class="form-check-input"
                                            id="withdraw_list">
                                    <label class="form-check-label qcont text-dark" for="withdraw_list">{{translate('messages.withdraw_list')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="zone" class="form-check-input"
                                           id="zone">
                                    <label class="form-check-label qcont text-dark" for="zone">{{translate('messages.zone')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="module" class="form-check-input"
                                           id="module_system">
                                    <label class="form-check-label qcont text-dark" for="module_system">{{translate('messages.module')}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="parcel" class="form-check-input"
                                           id="parcel">
                                    <label class="form-check-label qcont text-dark" for="parcel">{{translate('messages.parcel')}}</label>
                                </div>
                            </div> --}}
                            @if(isAddonActive('pos'))
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="pos" class="form-check-input granular_permission_check"
                                           id="pos">
                                    <label class="form-check-label qcont text-dark" for="pos">{{translate('messages.pos')}}</label>
                                </div>
                            </div>
                            @endif
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="subscription_plan" class="form-check-input"
                                           id="subscription_plan">
                                    <label class="form-check-label qcont text-dark" for="subscription_plan">{{translate('messages.subscription_plan')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="unit" class="form-check-input"
                                           id="unit">
                                    <label class="form-check-label qcont text-dark" for="unit">{{translate('messages.unit')}}</label>
                                </div>
                            </div>
                        </div>

                        {{-- Sales CRM Zone Restriction --}}
                        <div id="crm-zone-section" class="mt-3 p-3 border rounded bg-light" style="display:none">
                            <label class="input-label mb-1">{{ translate('Sales CRM — Zone Access') }}</label>
                            <p class="text-muted small mb-2">{{ translate('Select zones this role can access. Leave empty to allow all zones.') }}</p>
                            <select name="crm_zones[]" id="crm_zones_select" class="js-select2-custom form-control" multiple
                                    data-placeholder="{{ translate('All zones (no restriction)') }}">
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Granular Action-level Permissions --}}
                        @php
                            $modules = \App\Models\Feature::with(['permissions' => fn($q) => $q->orderBy('action')])
                                ->orderBy('master_module')->orderBy('name')
                                ->get()->groupBy('master_module');
                        @endphp

                        @if($modules->count() > 0)
                        <div class="d-flex align-items-center my-3">
                            <h4 class="mb-0">{{translate('messages.action_level_permissions')}}</h4>
                            <div class="ml-auto form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllPerms">
                                <label class="form-check-label font-weight-bold" for="selectAllPerms">
                                    {{ translate('select_all') }}
                                </label>
                            </div>
                        </div>

                        @foreach($modules as $moduleName => $features)
                            @php
                                $allActions = collect($features)
                                    ->flatMap(fn($f) => $f->permissions->pluck('action'))
                                    ->unique()->values()->sort()->all();
                            @endphp

                            <h5 class="mt-4 master_module_heading" data-master-module="{{ $moduleName }}" style="display:none;">
                                {{ ucfirst(str_replace('_', ' ', $moduleName)) }}
                            </h5>

                            <div class="table-responsive master_module_table" data-master-module="{{ $moduleName }}" style="display:none;">
                                <table class="table table-bordered table-sm align-middle module-table" data-module="{{ $moduleName }}">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="min-width:180px">{{translate('messages.feature')}}</th>
                                            @foreach($allActions as $action)
                                                <th class="text-center">
                                                    <div class="form-check d-inline-flex align-items-center">
                                                        <input class="form-check-input column-toggle" type="checkbox"
                                                            id="col_{{ $moduleName }}_{{ $action }}"
                                                            data-action="{{ $action }}" data-module="{{ $moduleName }}">
                                                        <label class="form-check-label ml-1" for="col_{{ $moduleName }}_{{ $action }}">
                                                            {{ ucfirst($action) }}
                                                        </label>
                                                    </div>
                                                </th>
                                            @endforeach
                                            <th class="text-center" style="min-width:100px">{{translate('messages.row')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($features as $feature)
                                            @php $byAction = $feature->permissions->keyBy('action'); @endphp
                                            <tr>
                                                <th>{{ $feature->display_name ?? ucfirst(str_replace('_', ' ', $feature->name)) }}</th>
                                                @foreach($allActions as $action)
                                                    @php $perm = $byAction->get($action); $pid = $perm->id ?? null; @endphp
                                                    <td class="text-center">
                                                        @if($pid)
                                                            <div class="form-check d-inline-block">
                                                                <input class="form-check-input perm-checkbox" type="checkbox"
                                                                    id="p_{{ $pid }}" name="permissions[]"
                                                                    value="{{ $pid }}" data-feature="{{ $feature->name }}"
                                                                    data-action="{{ $action }}" data-module="{{ $moduleName }}">
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="text-center">
                                                    <div class="form-check d-inline-flex align-items-center">
                                                        <input class="form-check-input row-toggle" type="checkbox"
                                                            id="row_{{ $feature->name }}" data-feature="{{ $feature->name }}"
                                                            data-module="{{ $moduleName }}"
                                                            @unless($feature->permissions->count() > 0) disabled @endunless>
                                                        <label class="form-check-label ml-1" for="row_{{ $feature->name }}">All</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                        @endif

                        <div class="btn--container justify-content-end mt-4">
                            <button type="reset" id="reset-btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header border-0 py-2">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">
                            {{translate('messages.roles_table')}} <span class="badge badge-soft-dark ml-2" id="itemCount">{{$roles->total()}}</span>
                        </h5>
                        <form class="search-form min--200">
                            <!-- Search -->
                            <div class="input-group input--group">
                                <input id="datatableSearch_" type="search" name="search"  value="{{request()?->search}}" class="form-control" placeholder="{{translate('ex_:_search_role_name')}}" aria-label="Search">
                                <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                            </div>
                            <!-- End Search -->
                        </form>
                        @if(request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                               class="role--table table table-borderless table-thead-bordered table-align-middle card-table"
                               data-hs-datatables-options='{
                                 "order": [],
                                 "orderCellsTop": true,
                                 "paging":false
                               }'>
                            <thead class="thead-light">
                            <tr>
                                <th scope="col" class="border-0">{{translate('sl')}}</th>
                                <th scope="col" class="border-0">{{translate('messages.role_name')}}</th>
                                <th scope="col" class="border-0">{{translate('messages.Permissions')}}</th>
                                <th scope="col" class="border-0">{{translate('messages.created_at')}}</th>
                                <th scope="col" class="border-0 text-center">{{translate('messages.action')}}</th>
                            </tr>
                            </thead>
                            <tbody  id="set-rows">
                            @foreach($roles as $k=>$role)
                                <tr>
                                    <td scope="row">{{$k+$roles->firstItem()}}</td>
                                    <td title="{{ $role['name'] }}" >{{Str::limit($role['name'],25,'...')}}</td>
                                    <td class="text-capitalize">
                                        @if($role['modules']!=null)
                                            @foreach((array)json_decode($role['modules']) as $key=>$module)
                                                {{translate(str_replace('_',' ',$module))}}

                                                {{  !$loop->last ? ',' : '.'}}
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        <div class="create-date">
                                            {{\App\CentralLogics\Helpers::date_format($role['created_at'])}}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--primary btn-outline-primary"
                                                href="{{route('admin.users.custom-role.edit',[$role['id']])}}" title="{{translate('messages.edit_role')}}"><i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:" data-id="role-{{$role['id']}}" data-message="{{translate('messages.Want_to_delete_this_role')}}"
                                               title="{{translate('messages.delete_role')}}"><i class="tio-delete-outlined"></i>
                                            </a>
                                        </div>
                                        <form action="{{route('admin.users.custom-role.delete',[$role['id']])}}"
                                                method="post" id="role-{{$role['id']}}">
                                            @csrf @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($roles) !== 0)
                    <hr>
                    @endif
                    <div class="page-area">
                        {!! $roles->links() !!}
                    </div>
                    @if(count($roles) === 0)
                    <div class="empty--data">
                        <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                        <h5>
                            {{translate('no_data_found')}}
                        </h5>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/custom-role-index.js"></script>

    <script>
        // Show/hide granular permission tables when module checkbox is toggled
        $(".granular_permission_check").on('change', function() {
            var master_module = $(this).val();
            if ($(this).prop('checked')) {
                $('.master_module_heading[data-master-module="' + master_module + '"]').show();
                $('.master_module_table[data-master-module="' + master_module + '"]').show();
            } else {
                $('.master_module_heading[data-master-module="' + master_module + '"]').hide();
                $('.master_module_table[data-master-module="' + master_module + '"]').hide();
                // Uncheck all permissions in hidden module
                $('.master_module_table[data-master-module="' + master_module + '"] .perm-checkbox').prop('checked', false);
                $('.master_module_table[data-master-module="' + master_module + '"] .row-toggle').prop('checked', false);
                $('.master_module_table[data-master-module="' + master_module + '"] .column-toggle').prop('checked', false);
            }
        });
    </script>

    <script>
        (function() {
            const $$ = sel => Array.from(document.querySelectorAll(sel));
            const $ = sel => Array.from(document.querySelectorAll(sel));

            // Master select-all for permissions
            const selectAllPerms = document.getElementById('selectAllPerms');
            if (selectAllPerms) {
                selectAllPerms.addEventListener('change', (e) => {
                    $$('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
                    $$('.row-toggle:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
                    $$('.column-toggle:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
                });
            }

            // Row "All" toggle
            $$('.row-toggle').forEach(rowTgl => {
                rowTgl.addEventListener('change', (e) => {
                    const feature = e.target.dataset.feature;
                    $(`input.perm-checkbox[data-feature="${feature}"]`).forEach(cb => {
                        if (!cb.disabled) cb.checked = e.target.checked;
                    });
                    refreshHeaderStates();
                });
            });

            // Column toggle (by action + module)
            $$('.column-toggle').forEach(colTgl => {
                colTgl.addEventListener('change', (e) => {
                    const action = e.target.dataset.action;
                    const mod = e.target.dataset.module;
                    $(`input.perm-checkbox[data-action="${action}"][data-module="${mod}"]`).forEach(cb => {
                        if (!cb.disabled) cb.checked = e.target.checked;
                    });
                    refreshHeaderStates();
                });
            });

            // Individual checkbox changes
            $$('.perm-checkbox').forEach(cb => {
                cb.addEventListener('change', refreshHeaderStates);
            });

            function refreshHeaderStates() {
                // Row states
                $$('.row-toggle').forEach(rowTgl => {
                    const feature = rowTgl.dataset.feature;
                    const boxes = $(`input.perm-checkbox[data-feature="${feature}"]`).filter(cb => !cb.disabled);
                    rowTgl.checked = boxes.length && boxes.every(cb => cb.checked);
                });

                // Column states
                $$('.column-toggle').forEach(colTgl => {
                    const action = colTgl.dataset.action;
                    const mod = colTgl.dataset.module;
                    const boxes = $(`input.perm-checkbox[data-action="${action}"][data-module="${mod}"]`).filter(cb => !cb.disabled);
                    colTgl.checked = boxes.length && boxes.every(cb => cb.checked);
                });

                // Master
                if (selectAllPerms) {
                    const allBoxes = $$('.perm-checkbox').filter(cb => !cb.disabled);
                    selectAllPerms.checked = allBoxes.length && allBoxes.every(cb => cb.checked);
                }
            }

            refreshHeaderStates();
        })();
    </script>
    <script>
        $(document).ready(function () {
            var $crmCb      = $('#sales_crm');
            var $section    = $('#crm-zone-section');
            var $zoneSelect = $('#crm_zones_select');

            function toggleZoneSection() {
                if ($crmCb.is(':checked')) {
                    $section.show();
                    // Re-trigger Select2 to render correctly after section is shown
                    if ($zoneSelect.hasClass('select2-hidden-accessible')) {
                        $zoneSelect.select2('destroy');
                    }
                    $zoneSelect.select2({
                        placeholder: '{{ translate("All zones (no restriction)") }}',
                        allowClear: true,
                        width: '100%'
                    });
                } else {
                    $section.hide();
                    $zoneSelect.val(null).trigger('change');
                }
            }

            $crmCb.on('change', toggleZoneSection);
            toggleZoneSection();
        });
    </script>
@endpush

