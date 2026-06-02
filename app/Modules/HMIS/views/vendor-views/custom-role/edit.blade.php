@extends('layouts.vendor.app')
@section('title', 'Edit Role')
@push('css_or_js')
    <style>
        .module_th {
            position: sticky;
            left: 0;
            background: white;
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        <!-- Page Heading -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.edit_role') }}
                </span>
            </h1>
        </div>
        <!-- Page Heading -->

        <!-- Content Row -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <span class="card-header-icon">
                        <i class="tio-document-text-outlined"></i>
                    </span>
                    <span>{{ translate('messages.role_form') }}</span>
                </h5>
            </div>
            <div class="card-body">
                <form class="role_form" action="{{ route('vendor.custom-role.update', [$role['id']]) }}" method="post">
                    @csrf
                    <div id="default-form">
                        <div class="form-group">
                            <label class="input-label" for="name">{{ translate('messages.role_name') }} </label>
                            <input type="text" id="name" name="name[]" class="form-control"
                                placeholder="{{ translate('role_name_example') }}" value="{{ $role['name'] }}"
                                maxlength="100" required>
                        </div>
                        <input type="hidden" name="lang[]" value="default">
                    </div>

                    <h5>{{ translate('messages.module_permission') }} : </h5>
                    <hr>
                    @php $module_id = \App\CentralLogics\Helpers::get_store_data()->module_id ?? 0; @endphp

                    <div class="check--item-wrapper mx-0">

                        @if ($module_id == 5)
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="item" class="form-check-input"
                                        id="item"
                                        {{ in_array('item', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                    <label class="form-check-label "
                                        for="item">{{ $module_id == 5 ? 'Products' : 'Service Setup' }}</label>
                                </div>
                            </div>
                        @endif
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="menu_preference" class="form-check-input"
                                   {{ in_array('menu_preference', (array) json_decode($role['modules'])) ? 'checked' : '' }} id="menu_preference">
                                <label class="form-check-label input-label " for="menu_preference">Menu Preference</label>
                            </div>
                        </div>
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="notifications" class="form-check-input"
                                  {{ in_array('notifications', (array) json_decode($role['modules'])) ? 'checked' : '' }}  id="notifications">
                                <label class="form-check-label input-label " for="notifications">Notifications</label>
                            </div>
                        </div>
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="documents" class="form-check-input"
                                  {{ in_array('documents', (array) json_decode($role['modules'])) ? 'checked' : '' }}  id="documents">
                                <label class="form-check-label input-label " for="documents">Documents</label>
                            </div>
                        </div>
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="store_availability" class="form-check-input"
                                   {{ in_array('store_availability', (array) json_decode($role['modules'])) ? 'checked' : '' }}  id="store_availability">
                                <label class="form-check-label input-label " for="store_availability">Store
                                   Availability</label>
                            </div>
                        </div>
                        @if(vendorPlanHasModule('inventory_manage'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="inventory_manage"
                                    class="form-check-input granular_permission_check" id="inventory_manage"
                                    {{ in_array('inventory_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label " for="inventory_manage">{{ _moduleLabel('inventory_manage') }}</label>
                            </div>
                        </div>
                        @endif
                        @if(vendorPlanHasModule('task_manage'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="task_manage"
                                    class="form-check-input granular_permission_check" id="task_manage"
                                    {{ in_array('task_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label " for="task_manage">Task Management</label>
                            </div>
                        </div>
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="assigned_tasks" class="form-check-input"
                                    id="assigned_tasks"
                                    {{ in_array('assigned_tasks', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label input-label " for="assigned_tasks">Assigned Tasks</label>
                            </div>
                        </div>
                        @endif
                        @if(vendorPlanHasModule('projects_manage'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="projects_manage"
                                    class="form-check-input granular_permission_check"
                                    {{ in_array('projects_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}
                                    id="projects_manage">
                                <label class="form-check-label " for="projects_manage">Project Management</label>
                            </div>
                        </div>
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="assigned_projects"
                                    class="form-check-input" id="assigned_projects"
                                    {{ in_array('assigned_projects', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label input-label " for="assigned_projects">Assigned Projects</label>
                            </div>
                        </div>
                        @endif

                        @if ($module_id == 5)
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="orders" class="form-check-input"
                                        {{ in_array('order', (array) json_decode($role['modules'])) ? 'checked' : '' }}
                                        id="orders">
                                    <label class="form-check-label input-label " for="order">Orders</label>
                                </div>
                            </div>
                        @else
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="leads_manage"
                                        class="form-check-input granular_permission_check"
                                        {{ in_array('leads_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}
                                        id="leads_manage">
                                    <label class="form-check-label input-label " for="leads_manage">
                                   {{_isHospital() ? 'Appointments' : 'Leads Management' }}</label>
                                </div>
                            </div>
                        @endif
                        @if(vendorPlanHasModule('quotaiton_manage'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="quotaiton_manage"
                                    class="form-check-input granular_permission_check" id="quotaiton_manage"
                                    {{ in_array('quotaiton_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label " for="quotaiton_manage">Quotation Managemnent</label>
                            </div>
                        </div>
                        @endif
                        {{-- <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="store_setup" class="form-check-input"
                                    id="store_setup"
                                    {{ in_array('store_setup', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label "
                                    for="store_setup">{{ translate('messages.business_setup') }}</label>
                            </div>
                        </div> --}}
                        @if (config('module.' . \App\CentralLogics\Helpers::get_store_data()->module->module_type)['add_on'])
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="addon" class="form-check-input"
                                        id="addon"
                                        {{ in_array('addon', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                    <label class="form-check-label "
                                        for="addon">{{ translate('messages.addon') }}</label>
                                </div>
                            </div>
                        @endif
                        {{-- <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="bank_info" class="form-check-input"
                                    id="bank_info"
                                    {{ in_array('bank_info', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label "
                                    for="bank_info">{{ translate('messages.profile') }}</label>
                            </div>
                        </div> --}}
                        {{-- <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="employee" class="form-check-input"
                                    id="employee"
                                    {{ in_array('employee', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label "
                                    for="employee">{{ translate('messages.Employee') }}</label>
                            </div>
                        </div> --}}
                        {{-- <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="my_shop" class="form-check-input"
                                    id="my_shop"
                                    {{ in_array('my_shop', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label "
                                    for="my_shop">{{ translate('messages.my_shop') }}</label>
                            </div>
                        </div> --}}

                        {{-- <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="campaign" class="form-check-input"
                                    id="campaign"
                                    {{ in_array('campaign', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label "
                                    for="campaign">{{ translate('messages.campaign') }}</label>
                            </div>
                        </div> --}}

                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="reviews" class="form-check-input"
                                    id="reviews"
                                    {{ in_array('reviews', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label "
                                    for="reviews">{{ translate('messages.reviews') }}</label>
                            </div>
                        </div>
                        @if(vendorPlanHasModule('pos'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="pos"
                                    class="form-check-input granular_permission_check" id="pos"
                                    {{ in_array('pos', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label " for="pos">POS</label>
                            </div>
                        </div>
                        @endif

                        {{-- <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="chat" class="form-check-input"
                                    id="chat"
                                    {{ in_array('chat', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label " for="chat">{{ translate('messages.chat') }}</label>
                            </div>
                        </div> --}}
                        @if(vendorPlanHasModule('hr_manage'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="hr_manage"
                                    class="form-check-input granular_permission_check" id="hr_manage"
                                    {{ in_array('hr_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label  " for="hr_manage">HR Management</label>
                            </div>
                        </div>
                        @endif
                        @if(vendorPlanHasModule('account_manage'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="account_manage"
                                    class="form-check-input granular_permission_check" id="account_manage"
                                    {{ in_array('account_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label  " for="account_manage">Account Management</label>
                            </div>
                        </div>
                        @endif
                        @if(vendorPlanHasModule('billing'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="billing"
                                    class="form-check-input granular_permission_check"
                                    {{ in_array('billing', (array) json_decode($role['modules'])) ? 'checked' : '' }}
                                    id="billing">
                                <label class="form-check-label  " for="billing">Billing</label>
                            </div>
                        </div>
                        @endif
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="hospital_manage"
                                    class="form-check-input granular_permission_check"
                                    {{ in_array('hospital_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}
                                    id="hospital_manage">
                                <label class="form-check-label input-label " for="hospital_manage">Hospital Management</label>
                            </div>
                        </div>

                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="staff_manage" class="form-check-input"
                                    id="staff_manage"
                                    {{ in_array('staff_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label " for="staff_manage">Staff Management</label>
                            </div>
                        </div>
                        @if(vendorPlanHasModule('client_manage'))
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="client_manage"
                                    class="form-check-input granular_permission_check" id="client_manage"
                                    {{ in_array('client_manage', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="client_manage">Client Management</label>
                            </div>
                        </div>
                        @endif
                        <div class="check-item">
                            <div class="form-group form-check form--check">
                                <input type="checkbox" name="modules[]" value="my_business"
                                    class="form-check-input granular_permission_check" id="my_business"
                                    {{ in_array('my_business', (array) json_decode($role['modules'])) ? 'checked' : '' }}>
                                <label class="form-check-label input-label " for="my_business">My Business</label>
                            </div>
                        </div>
                        @foreach ($accessibleModules as $key => $value)
                            <div class="check-item">
                                <div class="form-group form-check form--check">
                                    <input type="checkbox" name="modules[]" value="{{ $key }}"
                                        {{ in_array($key, (array) json_decode($role['modules'])) ? 'checked' : '' }}
                                        class="form-check-input" id="check_{{ $key }}">
                                    <label class="form-check-label "
                                        for="check_{{ $key }}">{{ $value }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex align-items-center my-3">
                        <h4 class="mb-0">Permissions: {{ $role->name }}</h4>
                        {{-- <div class="ml-auto form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label font-weight-bold" for="selectAll">
                                Select all
                            </label>
                        </div> --}}
                    </div>
                    @php
                        $features = \App\Models\Feature::with(['permissions' => fn($q) => $q->orderBy('action')])
                            ->orderBy('master_module')
                            ->orderBy('name')
                            ->get()
                            ->groupBy('master_module'); // 🔹 Group features by module

                        $assigned = $role->permissions()->pluck('feature_permissions.id')->all();

                        // Build the set of action columns dynamically (per module or globally)
                        $allActions = collect(\App\Models\Feature::with('permissions')->get())
                            ->flatMap(fn($f) => $f->permissions->pluck('action'))
                            ->unique()
                            ->sort()
                            ->values()

                            ->all();
                    @endphp

                    @foreach ($features as $moduleName => $moduleFeatures)
                        @php
                            // Get unique actions available for this specific module
                            $moduleActions = collect();
                            foreach ($moduleFeatures as $feature) {
                                foreach ($feature->permissions as $permission) {
                                    $moduleActions->push($permission->action);
                                }
                            }
                            $moduleActions = $moduleActions->unique()->sort()->values();
                        @endphp

                        <h5 class="mt-4 master_module_heading" data-master-module="{{ $moduleName }}"
                            {{ in_array($moduleName, (array) json_decode($role['modules'])) ? '' : 'style=display:none' }}>
                            {{ ucfirst($moduleName) }}
                        </h5>

                        <div class="table-responsive master_module_table" data-master-module="{{ $moduleName }}"
                            {{ in_array($moduleName, (array) json_decode($role['modules'])) ? '' : 'style=display:none' }}>
                            <table class="table table-bordered table-sm align-middle module-table"
                                data-module="{{ $moduleName }}">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="min-width:180px">Feature</th>

                                        @foreach ($moduleActions as $action)
                                            <th class="text-center">
                                                <div class="form-check d-inline-flex align-items-center">
                                                    <input class="form-check-input column-toggle" type="checkbox"
                                                        id="col_{{ $moduleName }}_{{ $action }}"
                                                        data-action="{{ $action }}"
                                                        data-module="{{ $moduleName }}">
                                                    <label class="form-check-label ml-1"
                                                        for="col_{{ $moduleName }}_{{ $action }}">
                                                        {{ ucfirst($action) }}
                                                    </label>
                                                </div>
                                            </th>
                                        @endforeach

                                        <th class="text-center" style="min-width:140px">Row</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($moduleFeatures as $feature)
                                        @php
                                            $byAction = $feature->permissions->keyBy('action');
                                            $rowCheckedCount = 0;
                                        @endphp
                                        <tr>
                                            <th class="module_th">
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        class="mr-2">{{ $feature->display_name ?? ucfirst($feature->name) }}</span>
                                                </div>
                                            </th>

                                            @foreach ($moduleActions as $action)
                                                @php
                                                    $perm = $byAction->get($action);
                                                    $pid = $perm->id ?? null;
                                                    $isChecked = $pid && in_array($pid, $assigned);
                                                    if ($isChecked) {
                                                        $rowCheckedCount++;
                                                    }
                                                @endphp
                                                <td class="text-center">
                                                    @if ($pid)
                                                        <div class="form-check d-inline-block">
                                                            <input class="form-check-input perm-checkbox" type="checkbox"
                                                                id="p_{{ $pid }}" name="permissions[]"
                                                                value="{{ $pid }}"
                                                                data-feature="{{ $feature->name }}"
                                                                data-action="{{ $action }}"
                                                                data-module="{{ $moduleName }}"
                                                                @if ($isChecked) checked @endif>
                                                        </div>
                                                    @else
                                                        <span class="text-muted"></span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            <td class="text-center">
                                                <div class="form-check d-inline-flex align-items-center">
                                                    @php
                                                        $rowHasAny = $feature->permissions->count() > 0;
                                                        $rowAllOn =
                                                            $rowHasAny &&
                                                            $rowCheckedCount === $feature->permissions->count();
                                                    @endphp
                                                    <input class="form-check-input row-toggle" type="checkbox"
                                                        id="row_{{ $feature->name }}"
                                                        data-feature="{{ $feature->name }}"
                                                        data-module="{{ $moduleName }}"
                                                        @if ($rowAllOn) checked @endif
                                                        @unless ($rowHasAny) disabled @endunless>
                                                    <label class="form-check-label ml-1"
                                                        for="row_{{ $feature->name }}">All</label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach


                    <div class="btn--container justify-content-end mt-4">
                        <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="button"
                            class="btn btn--primary submit_btn">{{ translate('messages.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script_2')
    <script>
        $('.submit_btn').on('click', function(e) {
            e.preventDefault();
            {{-- if ($('.perm-checkbox[data-module="pos"]:checked').length > 0) {
                $("#pos_inp").prop('checked', true);
            } --}}
            $(".role_form").submit()
        })
        $(".granular_permission_check").on('change', function() {
            var master_module = $(this).val();
            if ($(this).prop('checked') == true) {
                $('.master_module_heading[data-master-module="' + master_module + '"]').show()
                $('.master_module_table[data-master-module="' + master_module + '"]').show()
            } else {
                $('.master_module_heading[data-master-module="' + master_module + '"]').hide()
                $('.master_module_table[data-master-module="' + master_module + '"]').hide()
            }
        })
    </script>
    <script>
        (function() {
            const $$ = sel => Array.from(document.querySelectorAll(sel));

            // Master select-all
            const selectAll = document.getElementById('selectAll');
            selectAll?.addEventListener('change', (e) => {
                $$('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
                $$('.row-toggle:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
                $$('.column-toggle:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
            });

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

            // Column toggle (by action)
            $$('.column-toggle').forEach(colTgl => {
                colTgl.addEventListener('change', (e) => {
                    const action = e.target.dataset.action;
                    $(`input.perm-checkbox[data-action="${action}"]`).forEach(cb => {
                        if (!cb.disabled) cb.checked = e.target.checked;
                    });
                    refreshHeaderStates();
                });
            });

            // Individual checkbox changes update row/column/master states
            $$('.perm-checkbox').forEach(cb => {
                cb.addEventListener('change', refreshHeaderStates);
            });

            function $(sel) {
                return Array.from(document.querySelectorAll(sel));
            }

            function refreshHeaderStates() {
                // Row states
                $$('.row-toggle').forEach(rowTgl => {
                    const feature = rowTgl.dataset.feature;
                    const boxes = $(`input.perm-checkbox[data-feature="${feature}"]`).filter(cb => !cb
                        .disabled);
                    const allOn = boxes.length && boxes.every(cb => cb.checked);
                    rowTgl.checked = allOn;
                });

                // Column states
                $$('.column-toggle').forEach(colTgl => {
                    const action = colTgl.dataset.action;
                    const boxes = $(`input.perm-checkbox[data-action="${action}"]`).filter(cb => !cb.disabled);
                    const allOn = boxes.length && boxes.every(cb => cb.checked);
                    colTgl.checked = allOn;
                });

                // Master
                const allBoxes = $$('.perm-checkbox').filter(cb => !cb.disabled);
                const allOn = allBoxes.length && allBoxes.every(cb => cb.checked);
                selectAll.checked = allOn;
            }

            refreshHeaderStates();
        })();
    </script>
@endpush
