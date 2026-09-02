<form class="custom_ajax_form" action="{{ route('vendor.custom-role.create') }}" method="post">
    @csrf
    @php $module_id = \App\CentralLogics\Helpers::get_store_data()->module_id ?? 0; @endphp
    <div class="form-group">
        <label class="input-label" for="name">{{ translate('messages.role_name') }}</label>
        <input type="text" id="name" name="name[]" class="form-control"
            placeholder="{{ translate('role_name_example') }}" value="{{ old('name') }}" required maxlength="191">
    </div>
    <input type="hidden" name="lang[]" value="default">
    <h5 class="text-capitalize">{{ translate('messages.module_permission') }} : </h5>
    <hr>
    <div class="check--item-wrapper mx-0">
        @if ($module_id == 5)
            <div class="check-item">
                <div class="form-group form-check form--check">
                    <input type="checkbox" name="modules[]" value="item" class="form-check-input" id="item">
                    <label class="form-check-label input-label "
                        for="item">{{ $module_id == 5 ? 'Products' : 'Service Setup' }}</label>
                </div>
            </div>
        @endif
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="menu_preference" class="form-check-input" id="menu_preference">
                <label class="form-check-label input-label " for="menu_preference">Menu Preference</label>
            </div>
        </div>
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="notifications" class="form-check-input" id="notifications">
                <label class="form-check-label input-label " for="notifications">Notifications</label>
            </div>
        </div>
        {{-- <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="library" class="form-check-input" id="library">
                <label class="form-check-label input-label " for="library">Library</label>
            </div>
        </div> --}}
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="documents" class="form-check-input" id="documents">
                <label class="form-check-label input-label " for="documents">Documents</label>
            </div>
        </div>
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="store_availability" class="form-check-input" id="store_availability">
                <label class="form-check-label input-label " for="store_availability">Store Availability</label>
            </div>
        </div>

        @if(vendorPlanHasModule('inventory_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="inventory_manage"
                    class="form-check-input granular_permission_check" id="inventory_manage">
                <label class="form-check-label " for="inventory_manage">Inventory Manage</label>
            </div>
        </div>
        @endif
        @if(vendorPlanHasModule('task_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="task_manage"
                    class="form-check-input granular_permission_check" id="task_manage">
                <label class="form-check-label " for="task_manage">Task Management</label>
            </div>
        </div>
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="assigned_tasks" class="form-check-input"
                    id="assigned_tasks">
                <label class="form-check-label input-label " for="assigned_tasks">Assigned Tasks</label>
            </div>
        </div>
        @endif
        @if(vendorPlanHasModule('projects_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="projects_manage"
                    class="form-check-input granular_permission_check" id="projects_manage">
                <label class="form-check-label " for="projects_manage">Project Management</label>
            </div>
        </div>
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="assigned_projects" class="form-check-input"
                    id="assigned_projects">
                <label class="form-check-label input-label " for="assigned_projects">Assigned Projects</label>
            </div>
        </div>
        @endif
        @if ($module_id == 5)
            <div class="check-item">
                <div class="form-group form-check form--check">
                    <input type="checkbox" name="modules[]" value="orders" class="form-check-input" id="orders">
                    <label class="form-check-label input-label " for="order">Orders</label>
                </div>
            </div>
        @elseif (vendorPlanHasModule('leads_manage'))
            <div class="check-item">
                <div class="form-group form-check form--check">
                    <input type="checkbox" name="modules[]" value="leads_manage"
                        class="form-check-input granular_permission_check" id="leads_manage">
                    <label class="form-check-label input-label " for="leads_manage">{{_isHospital() ? 'Appointments' : 'Leads Management' }}</label>
                </div>
            </div>
        @endif

        @if(vendorPlanHasModule('quotaiton_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="quotaiton_manage" class="form-check-input"
                    id="quotaiton_manage">
                <label class="form-check-label input-label granular_permission_check" for="quotaiton_manage">Quotation
                    Management</label>
            </div>
        </div>
        @endif
        @if (config('module.' . \App\CentralLogics\Helpers::get_store_data()->module->module_type)['add_on'])
            <div class="check-item">
                <div class="form-group form-check form--check">
                    <input type="checkbox" name="modules[]" value="addon" class="form-check-input" id="addon">
                    <label class="form-check-label input-label "
                        for="addon">{{ translate('messages.addons') }}</label>
                </div>
            </div>
        @endif
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="reviews" class="form-check-input" id="reviews">
                <label class="form-check-label input-label "
                    for="reviews">{{ translate('messages.reviews') }}</label>
            </div>
        </div>
        @if(vendorPlanHasModule('pos'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="pos"
                    class="form-check-input granular_permission_check" id="pos">
                <label class="form-check-label " for="pos">POS</label>
            </div>
        </div>
        @endif
        @if(vendorPlanHasModule('hr_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="hr_manage"
                    class="form-check-input granular_permission_check" id="hr_manage">
                <label class="form-check-label input-label " for="hr_manage">HR Management</label>
            </div>
        </div>
        @endif
        @if(vendorPlanHasModule('account_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="account_manage"
                    class="form-check-input granular_permission_check" id="account_manage">
                <label class="form-check-label  " for="account_manage">Account Management</label>
            </div>
        </div>
        @endif
        {{-- Not behind vendorPlanHasModule(): basic billing is free for every store. The billing
             route group carries no planwise guard, and list / view / add_basic / edit / delete /
             pay / settings are all flagged free on feature_permissions. Gating this checkbox on
             the Advanced Billing subscription meant an owner could raise invoices themselves but
             had no way to let a receptionist do it. Granting a paid row (add_advanced,
             reminder_update) from here still buys nothing — hasPermission() re-checks the plan for
             anything not marked free. --}}
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="billing"
                    class="form-check-input granular_permission_check" id="billing">
                <label class="form-check-label  " for="billing">Billing</label>
            </div>
        </div>
        @if (vendorPlanHasModule('hospital_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="hospital_manage" class="form-check-input"
                    id="hospital_manage">
                <label class="form-check-label input-label " for="hospital_manage">Hospital Management</label>
            </div>
        </div>
        @endif

        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="staff_manage" class="form-check-input"
                    id="staff_manage">
                <label class="form-check-label input-label " for="staff_manage">Staff Management</label>
            </div>
        </div>
        @if(vendorPlanHasModule('client_manage'))
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="client_manage"
                    class="form-check-input granular_permission_check" id="client_manage">
                <label class="form-check-label input-label " for="client_manage">Client Management</label>
            </div>
        </div>
        @endif
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="my_business"
                    class="form-check-input granular_permission_check" id="my_business">
                <label class="form-check-label input-label " for="my_business">My Business</label>
            </div>
        </div>
        {{-- Not behind vendorPlanHasModule(): WhatsApp is open to every store and bills through
             its own plan, like My Business above. --}}
        <div class="check-item">
            <div class="form-group form-check form--check">
                <input type="checkbox" name="modules[]" value="whatsapp"
                    class="form-check-input granular_permission_check" id="whatsapp">
                <label class="form-check-label input-label " for="whatsapp">WhatsApp</label>
            </div>
        </div>

    </div>

    @php
        $features = \App\Models\Feature::with(['permissions' => fn($q) => $q->orderBy('action')])
            ->orderBy('name')
            ->get();
    @endphp

    <div class="d-flex align-items-center my-3">
        <h4 class="mb-0">Action-level Permissions</h4>
        <div class="ml-auto form-check">
            <input class="form-check-input" type="checkbox" id="selectAll">
            <label class="form-check-label font-weight-bold" for="selectAll">
                Select all
            </label>
        </div>
    </div>

    @php
        // Group features by module
        $modules = \App\Models\Feature::with(['permissions' => fn($q) => $q->orderBy('action')])
            ->orderBy('master_module')
            ->orderBy('name')
            ->get()
            ->groupBy('master_module');
    @endphp

    @foreach ($modules as $moduleName => $features)
        @php
            // Build the set of action columns dynamically for this module
            $allActions = collect($features)
                ->flatMap(fn($f) => $f->permissions->pluck('action'))
                ->unique()
                ->values()
                ->sort()
                ->all();
        @endphp

        <h5 class="mt-4 master_module_heading" data-master-module="{{ $moduleName }}" style="display:none;">
            {{ ucfirst($moduleName) }}
        </h5>

        <div class="table-responsive master_module_table" data-master-module="{{ $moduleName }}"
            style="display:none;">
            <table class="table table-bordered table-sm align-middle module-table" data-module="{{ $moduleName }}">
                <thead class="thead-light">
                    <tr>
                        <th style="min-width:180px">Feature</th>

                        @foreach ($allActions as $action)
                            <th class="text-center">
                                <div class="form-check d-inline-flex align-items-center">
                                    <input class="form-check-input column-toggle" type="checkbox"
                                        id="col_{{ $moduleName }}_{{ $action }}"
                                        data-action="{{ $action }}" data-module="{{ $moduleName }}">
                                    <label class="form-check-label ml-1"
                                        for="col_{{ $moduleName }}_{{ $action }}">
                                        {{ permission_action_label($action) }}
                                    </label>
                                </div>
                            </th>
                        @endforeach

                        <th class="text-center" style="min-width:140px">Row</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($features as $feature)
                        @php
                            $byAction = $feature->permissions->keyBy('action');
                            $rowCheckedCount = 0;
                        @endphp
                        <tr>
                            <th>
                                <div class="d-flex align-items-center">
                                    <span
                                        class="mr-2">{{ $feature->display_name ?? ucfirst($feature->name) }}</span>
                                </div>
                            </th>

                            @foreach ($allActions as $action)
                                @php
                                    $perm = $byAction->get($action);
                                    $pid = $perm->id ?? null;
                                @endphp
                                <td class="text-center">
                                    @if ($pid)
                                        <div class="form-check d-inline-block">
                                            <input class="form-check-input perm-checkbox" type="checkbox"
                                                id="p_{{ $pid }}" name="permissions[]"
                                                value="{{ $pid }}" data-feature="{{ $feature->name }}"
                                                data-action="{{ $action }}" data-module="{{ $moduleName }}">
                                            <label class="sr-only" for="p_{{ $pid }}">
                                                {{ $feature->name }} → {{ $action }}
                                            </label>
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
                                        $rowAllOn = $rowHasAny && $rowCheckedCount === $feature->permissions->count();
                                    @endphp
                                    <input class="form-check-input row-toggle" type="checkbox"
                                        id="row_{{ $feature->name }}" data-feature="{{ $feature->name }}"
                                        data-module="{{ $moduleName }}"
                                        @if ($rowAllOn) checked @endif
                                        @unless ($rowHasAny) disabled @endunless>
                                    <label class="form-check-label ml-1" for="row_{{ $feature->name }}">All</label>
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
        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
    </div>
</form>
