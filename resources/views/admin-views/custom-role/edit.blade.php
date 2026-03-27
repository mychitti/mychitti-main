@extends('layouts.admin.app')
@section('title',translate('Edit Role'))
@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Heading -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--26" alt="">
            </span>
            <span>
                {{translate('messages.employee_Role')}}
            </span> 
        </h1>
    </div> 
    <!-- Page Heading --> 
    <!-- Content Row -->
    <div class="row"> 
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.users.custom-role.update',[$role['id']])}}" method="post">
                        @csrf
                        @if($language)
                            <ul class="nav nav-tabs mb-4">
                                <li class="nav-item">
                                    <a class="nav-link lang_link active"
                                    href="#"
                                    id="default-link">{{translate('messages.default')}}</a>
                                </li>
                                @foreach ($language as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link"
                                            href="#"
                                            id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="lang_form" id="default-form">
                                <div class="form-group">
                                    <label class="input-label" for="default_title">{{translate('messages.role_name')}} ({{translate('messages.default')}}) <span class="form-label-secondary text-danger"
                                        data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('messages.Required.')}}"> *
                                        </span>
                                 </label>
                                    <input type="text" name="name[]" id="default_title" class="form-control" placeholder="{{translate('role_name_example')}}" value="{{$role?->getRawOriginal('name')}}"  >
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                            </div>
                            @foreach($language as $lang)
                                <?php
                                    if(count($role['translations'])){
                                        $translate = [];
                                        foreach($role['translations'] as $t)
                                        {
                                            if($t->locale == $lang && $t->key=="name"){
                                                $translate[$lang]['name'] = $t->value;
                                            }
                                        }
                                    }
                                ?>
                                <div class="d-none lang_form" id="{{$lang}}-form">
                                    <div class="form-group">
                                        <label class="input-label" for="{{$lang}}_title">{{translate('messages.role_name')}} ({{strtoupper($lang)}})</label>
                                        <input type="text" name="name[]" id="{{$lang}}_title" class="form-control" placeholder="{{translate('role_name_example')}}" value="{{$translate[$lang]['name']??''}}"  >
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{$lang}}">
                                </div>
                            @endforeach
                        @else
                        <div id="default-form">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.role_name')}} ({{ translate('messages.default') }})</label>
                                <input type="text" name="name[]" class="form-control" placeholder="{{translate('role_name_example')}}" value="{{$role['name']}}" maxlength="100">
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                        </div>
                        @endif

                        <div class="d-flex flex-wrap select--all-checkes">
                            <h5 class="input-label m-0 text-capitalize">{{translate('messages.Update_permission')}} : </h5>
                            <div class="check-item pb-0 w-auto">
                                <div class="form-group form-check form--check m-0 ml-2">
                                    <input type="checkbox" name="modules[]" value="account" class="form-check-input" id="select-all">
                                    <label class="form-check-label ml-2" for="select-all">{{ translate('Select All') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="check--item-wrapper">
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="attendance" class="form-check-input granular_permission_check"
                                           id="attendance" {{in_array('attendance',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="attendance">{{translate('messages.attendance')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="billing" class="form-check-input granular_permission_check"
                                           id="billing"  {{in_array('billing',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="billing">{{translate('messages.billing')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="blog" class="form-check-input"
                                           id="blog"  {{in_array('blog',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="blog">{{translate('messages.blog')}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="collect_cash" class="form-check-input"
                                           id="collect_cash"  {{in_array('collect_cash',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="collect_cash">{{translate('messages.collect_cash')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="addon" class="form-check-input"
                                           id="addon"  {{in_array('addon',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="addon">{{translate('messages.addon')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="attribute" class="form-check-input"
                                           id="attribute"  {{in_array('attribute',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="attribute">{{translate('messages.attribute')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="banner" class="form-check-input"
                                           id="banner"  {{in_array('banner',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="banner">{{translate('messages.banner')}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="campaign" class="form-check-input"
                                           id="campaign"  {{in_array('campaign',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="campaign">{{translate('messages.campaign')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="category" class="form-check-input"
                                           id="category"  {{in_array('category',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="category">{{translate('messages.category')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="coupon" class="form-check-input"
                                           id="coupon"  {{in_array('coupon',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="coupon">{{translate('messages.coupon')}}</label>
                                </div>
                            </div>

                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="client_manage" class="form-check-input granular_permission_check"
                                           id="client_manage"  {{in_array('client_manage',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="client_manage">{{translate('messages.client_management')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="deliveryman" class="form-check-input"
                                           id="deliveryman"  {{in_array('deliveryman',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="deliveryman">{{translate('messages.deliveryman')}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="provide_dm_earning" class="form-check-input"
                                           id="provide_dm_earning"  {{in_array('provide_dm_earning',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="provide_dm_earning">{{translate('messages.provide_dm_earning')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="employee" class="form-check-input granular_permission_check"
                                           id="employee"  {{in_array('employee',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="employee">{{translate('messages.Employee')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="employee_role" class="form-check-input"
                                           id="employee_role"  {{in_array('employee_role',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="employee_role">{{translate('messages.employee_role')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="item" class="form-check-input"
                                           id="item"  {{in_array('item',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="item">{{Config::get('module.current_module_id') == 6 ? 'Service' : 'Product'}}</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="leave" class="form-check-input granular_permission_check"
                                           id="leave"  {{in_array('leave',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="leave">{{translate('messages.leave')}}</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="notification" class="form-check-input"
                                           id="notification"  {{in_array('notification',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="notification">{{translate('messages.push_notification')}} </label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="order" class="form-check-input granular_permission_check"
                                           id="order"  {{in_array('order',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="order">{{translate('messages.order')}}</label>
                                </div>
                            </div> --}}
                            @if (Config::get('module.current_module_id')== 5)
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" {{in_array('order',(array)json_decode($role['modules']))?'checked':''}} value="orders" class="form-check-input" id="orders">
                                        <label class="form-check-label text-dark " for="order">Orders</label>
                                    </div>
                                </div>
                            @else
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" {{in_array('leads_manage',(array)json_decode($role['modules']))?'checked':''}} value="leads_manage"
                                            class="form-check-input granular_permission_check" id="leads_manage">
                                        <label class="form-check-label text-dark " for="leads_manage">Service Leads</label>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" {{in_array('inventory_manage',(array)json_decode($role['modules']))?'checked':''}} value="inventory_manage"
                                        class="form-check-input granular_permission_check" id="inventory_manage">
                                    <label class="form-check-label text-dark" for="inventory_manage">Inventory Manage</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" {{in_array('task_manage',(array)json_decode($role['modules']))?'checked':''}} value="task_manage"
                                        class="form-check-input granular_permission_check" id="task_manage">
                                    <label class="form-check-label text-dark" for="task_manage">Task Management</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" {{in_array('projects_manage',(array)json_decode($role['modules']))?'checked':''}} value="projects_manage"
                                        class="form-check-input granular_permission_check" id="projects_manage">
                                    <label class="form-check-label text-dark" for="projects_manage">Project Management</label>
                                </div>
                            </div>
                                                <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" {{in_array('hr_manage',(array)json_decode($role['modules']))?'checked':''}} value="hr_manage"
                                        class="form-check-input granular_permission_check" id="hr_manage">
                                    <label class="form-check-label  text-dark" for="hr_manage">HR Management</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" {{in_array('account_manage',(array)json_decode($role['modules']))?'checked':''}} value="account_manage"
                                        class="form-check-input granular_permission_check" id="account_manage">
                                    <label class="form-check-label  text-dark" for="account_manage">Account Management</label>
                                </div>
                            </div>

                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="store" class="form-check-input"
                                           id="store"  {{in_array('store',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="store">{{translate('messages.store')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="store_add_edit" class="form-check-input"
                                           {{in_array('store_add_edit',(array)json_decode($role['modules']))?'checked':''}} id="store_add_edit">
                                    <label class="form-check-label qcont text-dark" for="store_add_edit">{{translate('messages.store_add & edit')}}</label>
                                </div>
                            </div>
                               <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="store_documents" class="form-check-input"
                                         {{in_array('store_documents',(array)json_decode($role['modules']))?'checked':''}}  id="store_documents">
                                    <label class="form-check-label qcont text-dark" for="store_documents">{{translate('messages.store_documents')}}</label>
                                </div>
                            </div>
                              <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="service_billing" class="form-check-input granular_permission_check"
                                         {{in_array('service_billing',(array)json_decode($role['modules']))?'checked':''}}  id="service_billing">
                                    <label class="form-check-label qcont text-dark" for="service_billing">Service Billing</label>
                                </div>
                            </div>
                             <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="google_ads" class="form-check-input"
                                          {{in_array('google_ads',(array)json_decode($role['modules']))?'checked':''}}   id="google_ads">
                                    <label class="form-check-label qcont text-dark" for="google_ads">Google Ads</label>
                                </div>
                            </div>
                              <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="quotaiton_manage" class="form-check-input granular_permission_check"
                                         {{in_array('quotaiton_manage',(array)json_decode($role['modules']))?'checked':''}}  id="quotaiton_manage">
                                    <label class="form-check-label qcont text-dark" for="quotaiton_manage">Quotation Manage</label>
                                </div>
                            </div>
                            {{-- <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="projects_manage" class="form-check-input granular_permission_check"
                                         {{in_array('projects_manage',(array)json_decode($role['modules']))?'checked':''}}   id="projects_manage">
                                    <label class="form-check-label qcont text-dark" for="projects_manage">Project Manage</label>
                                </div>
                            </div> --}}
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="support_ticket" class="form-check-input granular_permission_check"
                                           id="support_ticket" {{in_array('support_ticket',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="support_ticket">Support Tickets</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="ai_agent" class="form-check-input granular_permission_check"
                                           id="ai_agent" {{in_array('ai_agent',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="ai_agent">AI Agent</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="logs" class="form-check-input granular_permission_check"
                                           id="logs" {{in_array('logs',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="logs">Logs</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="report" class="form-check-input"
                                            id="report"  {{in_array('report',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="report">{{translate('messages.report')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="salary" class="form-check-input granular_permission_check"
                                           id="salary" {{in_array('salary',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="salary">{{translate('messages.salary')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="subscription_plan" class="form-check-input"
                                           id="subscription_plan" {{in_array('subscription_plan',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="subscription_plan">{{translate('messages.subscription_plan')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="settings" class="form-check-input"
                                           id="settings"  {{in_array('settings',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="settings">{{translate('messages.settings')}}</label>
                                </div>
                            </div>

                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="withdraw_list" class="form-check-input"
                                            id="withdraw_list"  {{in_array('withdraw_list',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="withdraw_list">{{translate('messages.withdraw_list')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="zone" class="form-check-input"
                                           id="zone"  {{in_array('zone',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="zone">{{translate('messages.zone')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="module" class="form-check-input"
                                           id="module_system"  {{in_array('module',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="module_system">{{translate('messages.module')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="parcel" class="form-check-input"
                                           id="parcel"  {{in_array('parcel',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="parcel">{{translate('messages.parcel')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="pos" class="form-check-input granular_permission_check"
                                           id="pos"  {{in_array('pos',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="pos">{{translate('messages.pos')}}</label>
                                </div>
                            </div>
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="unit" class="form-check-input"
                                           id="unit"  {{in_array('unit',(array)json_decode($role['modules']))?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="unit">{{translate('messages.unit')}}</label>
                                </div>
                            </div>
                        </div>

                        {{-- Granular Action-level Permissions --}}
                        @php
                            $roleModules = (array)json_decode($role['modules']);
                            $assigned = $assignedPermissions ?? [];
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
                                $moduleChecked = in_array($moduleName, $roleModules);
                            @endphp

                            <h5 class="mt-4 master_module_heading" data-master-module="{{ $moduleName }}" style="{{ $moduleChecked ? '' : 'display:none;' }}">
                                {{ ucfirst(str_replace('_', ' ', $moduleName)) }}
                            </h5>

                            <div class="table-responsive master_module_table" data-master-module="{{ $moduleName }}" style="{{ $moduleChecked ? '' : 'display:none;' }}">
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
                                                                    data-action="{{ $action }}" data-module="{{ $moduleName }}"
                                                                    @if(in_array($pid, $assigned)) checked @endif>
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
                            <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{translate('messages.update')}}</button>
                        </div>
                    </form>
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
@endpush
