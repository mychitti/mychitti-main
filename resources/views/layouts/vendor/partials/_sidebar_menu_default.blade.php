                    <!-- Dashboards -->
                    @unless ((isset($skipForLaundry) && $skipForLaundry) || (isset($skipForPOS) && $skipForPOS))
                        @if (selected_menu('dashboard'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.dashboard') }}"
                                    title="{{ translate('messages.dashboard') }}">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}"
                                        alt="" class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ translate('messages.dashboard') }}
                                    </span>
                                </a>
                            </li>
                        @endif
                    @endunless

                    @if (!auth('vendor')->check())
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('profile/view') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.profile.view') }}" title="My Profile">
                                <i class="tio-user-outlined nav-link-icon" style="font-size:20px;"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    My Profile
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (!auth('vendor')->check() && \App\CentralLogics\Helpers::employee_module_permission_check('assigned_tasks'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('task/assigned-tasks*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.task.assigned_tasks') }}" title="Assigned Tasks">
                                <img src="{{ asset('storage/app/public/nav/task (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Assigned Tasks
                                </span>
                            </a>
                        </li>
                    @endif
                    @if (!auth('vendor')->check() && \App\CentralLogics\Helpers::employee_module_permission_check('assigned_projects'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('service/assigned-projects*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.service.assigned_projects') }}" title="Assigned projects">
                                <img src="{{ asset('storage/app/public/nav/project.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Assigned Projects
                                </span>
                            </a>
                        </li>
                    @endif

                   
                    {{-- hasMasterModulePermission('leads_manage') && --}}
                    @unless (isset($skipForPOS) && $skipForPOS)
                        @if (selected_menu('leads_manage') && hasMasterModulePermission('leads_manage') && $store_data->module->id == 6 && (hasAnyPermission(['leads_manage.dashboard', 'leads_manage.list', 'leads_manage.add', 'leads_manage.statuses', 'leads_manage.export', 'leads_manage.report', 'leads_manage.settings'])))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('service/report') || Request::is('lead*') || Request::is('service/leads*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="{{ _moduleLabel('leads_manage') }}">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/leads_management_color.png') }}"
                                        alt="" class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ _moduleLabel('leads_manage') }} </span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('service/report') || Request::is('lead*') || Request::is('service/leads*') ? 'block' : 'none' }}">

                                    @if (hasAnyPermission(['leads_manage.dashboard']))
                                        <li class="nav-item {{ Request::is('service/leads-dashboard*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.service.leads-dashboard') }}"
                                                title="Leads Dashboard">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Leads Dashboard</span>
                                            </a>
                                        </li>
                                    @endif
                                 
                                    @if (auth('vendor_employee')->check() && hasAnyPermission(['leads_manage.assigned_list']))
                                      
                                        <li
                                            class="nav-item  {{ Request::is('service/assigned-services*') && !Request::is('service/leads-dashboard*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.service.assigned_services') }}"
                                                title=" Leads">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate sidebar--badge-container">
                                                    Assigned {{ _isHospital() ? 'Appointments' : 'Leads' }}
                                                </span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasAnyPermission(['leads_manage.list', 'leads_manage.add', 'leads_manage.statuses', 'leads_manage.export']))
                                        <li
                                            class="nav-item  {{ Request::is('service/leads*') && !Request::is('service/leads-dashboard*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.service.leads_list') }}"
                                                title=" Leads">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate sidebar--badge-container">
                                                    All {{ _isHospital() ? 'Appointments' : 'Leads' }}
                                                </span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasAnyPermission(['leads_manage.report']))
                                        <li class="nav-item {{ Request::is('service/report') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.service.report') }}" title="report">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate sidebar--badge-container">
                                                    {{ _isHospital() ? 'Appointments' : 'Leads' }} Report
                                                </span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasAnyPermission(['leads_manage.settings']))
                                        <li class="nav-item {{ Request::is('service/report') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.service.report') }}" title="report">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate sidebar--badge-container">
                                                    {{ _isHospital() ? 'Appointments' : 'Leads' }} Report
                                                </span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (auth('vendor')->check())
                                        <li class="nav-item {{ Request::is('service/lead-settings') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.service.lead-settings') }}"
                                                title="Lead Settings">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span
                                                    class="text-truncate">{{ _isHospital() ? 'Appointment Settings' : 'Lead Settings' }}</span>
                                            </a>
                                        </li>
                                        <li
                                            class="nav-item {{ Request::is('service/lead-subscription') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.service.lead-subscription') }}"
                                                title="Lead Subscription">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">{{ _isHospital() ? 'Appointment Subscription' : 'Lead Subscription' }}</span>
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                            </li>
                        @elseif(auth('vendor_employee')->check() && hasAnyPermission(['leads_manage.assigned_list']))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('service/assigned-services*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.service.assigned_services') }}"
                                    title="Assigned {{ _isHospital() ? 'Appointments' : 'Leads' }}">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/leads_management_color.png') }}"
                                        alt="" class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        Assigned {{ _isHospital() ? 'Appointments' : 'Leads' }}
                                    </span>
                                </a>
                            </li>
                        @endif
                    @endunless

                    {{-- =============================== TASK Management=========================== --}}
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        @if (selected_menu('task_management') && hasMasterModulePermission('task_manage'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('task*') && !Request::is('task-salary-categories') && !Request::is('task/assigned-tasks') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="Task Management">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Tasks_management_color.png') }}"
                                        alt="" class="nav-link-icon">

                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Task
                                        Management</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('task*') && !Request::is('task-salary-categories') ? 'block' : 'none' }}">

                                    {{-- <li class="nav-item {{ Request::is('task/add') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.task.add') }}"
                                            title="{{ translate('messages.add') }} {{ translate('messages.new') }} task">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.add') }}
                                                {{ translate('messages.new') }} Task</span>
                                        </a>
                                    </li> --}}
                                    @if (hasAnyPermission(['task.list', 'task.export', 'task.add']))
                                        <li class="nav-item {{ Request::is('task/list') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.task.list') }}"
                                                title="list Project">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Tasks</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('task', 'settings'))
                                        <li class="nav-item {{ Request::is('task/setting') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.task.setting') }}"
                                                title="Task Settings">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Task Settings</span>
                                            </a>
                                        </li>
                                    @endif
                                    {{-- <li class="nav-item {{ Request::is('task/setting/workflow-form') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.task.setting.workflow-form') }}"
                                            title="Task Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Create Workflow Form</span>
                                        </a>
                                    </li> --}}
                                </ul>
                            </li>
                        @endif
                    @endunless
                    {{-- =============================== PROJECT Management=========================== --}}
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        @if (selected_menu('project_manage') && hasMasterModulePermission('projects_manage'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('project*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="Project Management">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Project%20_management_color.png') }}"
                                        alt="" class="nav-link-icon">


                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Project
                                        Management</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('project*') ? 'block' : 'none' }}">
                                    {{-- <li
                                    class="nav-item {{ Request::is('project/dashboard') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.project.dashboard') }}"
                                        title=" Project dashboard">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Projects Dashboard</span>
                                    </a>
                                </li> --}}
                                    @if (hasPermission('project', 'add'))
                                        <li class="nav-item {{ Request::is('project/add') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.project.add') }}"
                                                title="{{ translate('messages.add') }} {{ translate('messages.new') }} Project">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">{{ translate('messages.add') }}
                                                    {{ translate('messages.new') }} Project</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('project', 'list'))
                                        <li class="nav-item {{ Request::is('project/list') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.project.all') }}"
                                                title="list Project">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Projects List</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('project', 'settings'))
                                        <li class="nav-item {{ Request::is('project/settings') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.project.settings') }}"
                                                title="list Project">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Project Settings</span>
                                            </a>
                                        </li>
                                    @endif
                                    {{--  @if (hasAnyModulePermission(['project_task']))
                                  <li
                                        class="nav-item {{ Request::is('project/task/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.project.task.list') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Projects Tasks</span>
                                        </a>
                                    </li> 
                                @endif --}}
                                </ul>
                            </li>
                        @endif
                    @endunless
               
                    {{-- =============================== CLIENT Management=========================== --}}
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        @if (
                            (auth('vendor')->check() && selected_menu('client_manage') && hasMasterModulePermission('client_manage')) ||
                                (auth('vendor_employee')->check() && hasMasterModulePermission('client_manage')))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('client*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="Client Management">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Clients_management_color.png') }}"
                                        alt="" class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Client
                                        Management
                                    </span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('client*') ? 'block' : 'none' }}">
                                    @if (hasPermission('client_manage', 'add'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('customer/add') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.customer.add') }}"
                                                title="Add New Client">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class=" text-truncate">Add New Client</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasAnyPermission(['client_manage.list', 'client_manage.import', 'client_manage.export']))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('client/list') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.customer.list') }}"
                                                title="{{ translate('messages.clients') }} Management">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class=" text-truncate">{{ translate('messages.clients') }}
                                                    List</span>
                                            </a>
                                        </li>
                                    @endif
                                    {{-- <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('customer/overview') ? 'active' : '' }}">
                                    <a class="nav-link" href="#"
                                        title="{{ translate('messages.clients_overview') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">{{ translate('messages.clients_overview') }}
                                        </span>
                                    </a>
                                </li> --}}
                                </ul>
                            </li>
                        @endif
                    @endunless
                    {{-- ===============================CUSTOMER SUPPORT=========================== --}}
                    @if (0 &&
                            selected_menu('customer_support') &&
                            \App\CentralLogics\Helpers::employee_module_permission_check('customer_support'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('order*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Customer Support">
                                <img src="{{ asset('storage/app/public/nav/client (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Customer
                                    Support
                                </span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('order/add') ? 'block' : 'none' }}">

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('customer/support') ? 'active' : '' }}">
                                    <a class="nav-link" href="#" title="Calls">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Calls</span>
                                    </a>
                                </li>

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('customer/support') ? 'active' : '' }}">
                                    <a class="nav-link" href="#"
                                        title="{{ translate('messages.Feedbacks') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Feedbacks</span>
                                    </a>
                                </li>
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('customer/support') ? 'active' : '' }}">
                                    <a class="nav-link" href="#"
                                        title="{{ translate('messages.Call Marketing') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Call Marketing
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    {{-- ===================================== BILLING ========================== --}}
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        @if (\App\CentralLogics\Helpers::employee_module_permission_check('billing') && selected_menu('billing'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/settings') || Request::is('billing*') || Request::is('invoice-list') || Request::is('invoices') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="Billing">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Billing_management_color.png') }}"
                                        alt="" class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        Billing</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">

                                    @if (hasPermission('billing', 'add_basic'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('billing/manual-bill') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.invoice.manual-bill') }}"
                                                title="{{ translate('messages.Add Bill') }}">
                                                <span class="tio-document-text nav-icon"></span>
                                                <span class="text-truncate">Add Bill</span>
                                            </a>
                                        </li>
                                    @endif
                                    {{-- @if (hasMasterModulePermission('billing'))
                                    @if (hasPermission('billing', 'add_advanced'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('billing/create-invoice') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.invoice.create-invoice') }}"
                                                title="{{ translate('messages.Generate Advanced Invoice') }}">
                                                <span class="tio-document-text nav-icon"></span>
                                                <span class="text-truncate">Generate Advanced Invoice</span>
                                            </a>
                                        </li>
                                    @endif
                                @endif --}}
                                    @if (hasAnyPermission(['billing.list', 'billing.export', 'billing.import']))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('billing/credit') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.invoice.list') }}"
                                                title="{{ translate('messages.Bill List') }}">
                                                <span class="tio-coin nav-icon"></span>
                                                <span class="text-truncate">Bill List</span>
                                            </a>
                                        </li>

                                        @if ($store_data->module->id == 5)
                                            <li class="nav-item {{ Request::is('invoice-list') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.order.invoices') }}"
                                                    title="invoices">
                                                    <span class="tio-document-text nav-icon"></span>
                                                    <span class="text-truncate">Invoices</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endif
                                    @if (hasMasterModulePermission('billing') || hasMasterModulePermission('advanced_billing'))
                                        @if (hasAnyModulePermission(['purchase_bill']))
                                            <li class="nav-item  {{ Request::is('billing/purchase-bills') ? 'active' : '' }}"
                                                style="margin-top:0 !important;">
                                                <a class="nav-link " href="{{ route('vendor.invoice.my-bills') }}"
                                                    title="Purchase Bills">
                                                    <span class="tio-money-vs nav-icon"></span>
                                                    <span class="text-truncate">Purchase Bills</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission('billing', 'settings') ||
                                                hasAnyModulePermission(['billing_bank_account', 'billing_signatures', 'billing_tnc']))
                                            <li class="nav-item  {{ Request::is('billing/settings') || Request::is('business-settings/settings') ? 'active' : '' }}"
                                                style="margin-top:0 !important;">
                                                <a class="nav-link " href="{{ route('vendor.invoice.settings') }}"
                                                    title="Billing Settings">
                                                    <span class="tio-money-vs nav-icon"></span>
                                                    <span class="text-truncate">Billing Settings</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if (_offeredModule('reciepts'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/manual-bill') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="Reciepts">
                                    <i class="tio-receipt nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        Reciepts</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                    <li class="nav-item  {{ Request::is('receipt/templates') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link " href="" title="Templates">
                                            <span class="tio-money-vs nav-icon"></span>
                                            <span class="text-truncate">Templates</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endunless
                    {{-- =============================== QUOTATION Management=========================== --}}
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        @if (hasMasterModulePermission('quotaiton_manage') && selected_menu('quotation_manage'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('quotation*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="Quotation Management">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Quotations_management_color.png') }}"
                                        alt="" class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Quotation
                                        Management</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('quotation*') ? 'block' : 'none' }}">
                                    @if (hasPermission('quotaiton_manage', 'add'))
                                        <li class="nav-item {{ Request::is('quotation/add') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.quotation.add') }}"
                                                title="{{ translate('messages.add') }} {{ translate('messages.new') }} Quotation">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">{{ translate('messages.add') }}
                                                    {{ translate('messages.new') }} Quotation</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (hasAnyPermission(['quotaiton_manage.list']))
                                        <li class="nav-item {{ Request::is('quotation/list') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.quotation.list') }}"
                                                title="Quotation {{ translate('messages.list') }}">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Quotations List</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('quotaiton_manage', 'settings') ||
                                            hasAnyModulePermission(['quotation_bank_account', 'quotation_sign', 'quotation_tnc']))
                                        <li class="nav-item {{ Request::is('quotation/settings') ? 'active' : '' }}"
                                            style="margin-top:0 !important;">
                                            <a class="nav-link " href="{{ route('vendor.quotation.settings') }}"
                                                title="Quotation Settings">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Quotation Settings</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endunless
                    {{-- =============================== POS Management=========================== --}}
                    @unless (isset($skipForPOS) && $skipForPOS)
                        @if (selected_menu('pos') && hasMasterModulePermission('pos') && $store_data->module->module_type == 'ecommerce')
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('pos') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link  "
                                    href="{{ route('vendor.pos.index') }}" title="{{ translate('messages.pos') }}">
                                    <i class="tio-shopping-basket-outlined nav-icon"></i>
                                    <span class="text-truncate">{{ translate('messages.pos') }}</span>
                                </a>
                            </li>
                        @endif
                        @if (selected_menu('pos') && $store_data->module->id == 6 && hasMasterModulePermission('pos'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('pos*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="POS">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/POS_color.png') }}"
                                        alt="" class="nav-link-icon">

                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">POS</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                    @if (hasPermission('pos', 'dashboard'))
                                        <li class="nav-item {{ Request::is('pos/dashboard') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.pos.dashboard') }}"
                                                title="POS Dashboard">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Dashboard</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('pos_token', 'generate'))
                                        <li class="nav-item {{ Request::is('pos/token') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.pos.token') }}"
                                                title="POS Token">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Token Generate</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('restaurant_tables', 'list') || hasPermission('restaurant_tables', 'add'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('pos/restaurant-tables*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Restaurant Tables">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate ">
                                                    Restaurant Tables</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                @if (hasPermission('restaurant_tables', 'list'))
                                                    <li
                                                        class="nav-item {{ Request::is('pos/restaurant-tables/index') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.pos.restaurant-tables.index') }}"
                                                            title="{{ translate('messages.List') }}">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">List</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasPermission('restaurant_tables', 'add'))
                                                    <li
                                                        class="nav-item {{ Request::is('pos/restaurant-tables/create') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.pos.restaurant-tables.create') }}"
                                                            title="{{ translate('messages.Add New') }}">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Add new</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
                                    @if (hasPermission('pos_token', 'list'))
                                        <li class="nav-item {{ Request::is('pos/token-list') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.pos.token.list') }}"
                                                title="Tokens List">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Tokens List</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('pos_items', 'list') || hasPermission('pos_items', 'add'))
                                        <li class="nav-item {{ Request::is('pos/items') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.pos.items') }}"
                                                title="POS Items">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">POS Items</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('pos_branch', 'list') || hasPermission('pos_branch', 'add'))
                                        <li class="nav-item {{ Request::is('pos/branch') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.pos.branch.index') }}"
                                                title="Branches">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Branches</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('pos', 'settings'))
                                        <li class="nav-item {{ Request::is('pos/settings') ? 'active' : '' }}"
                                            style="margin-top:0 !important;">
                                            <a class="nav-link " href="{{ route('vendor.pos.settings') }}"
                                                title="POS Settings">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Settings</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('pos', 'report'))
                                        <li class="nav-item {{ Request::is('pos/report') ? 'active' : '' }}"
                                            style="margin-top:0 !important;">
                                            <a class="nav-link " href="{{ route('vendor.pos.report') }}"
                                                title="POS Report">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Report</span>
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                            </li>
                        @endif
                    @endunless
                    {{-- =============================== ACCOUNT Management=========================== --}}
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        @if (selected_menu('account_manage') && hasMasterModulePermission('account_manage'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('account*') || Request::is('asset*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title=" Account Management">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Billing_management_color.png') }}"
                                        alt="" class="nav-link-icon">

                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        Account Management</span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                    @if (_storeAccountType() == 'ledger' && hasPermission('dashboard', 'view'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('account/dashboard') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.account.dashboard') }}"
                                                title="{{ translate('messages.dashboard') }}">
                                                <span class="tio-dashboard nav-icon"></span>
                                                <span class="text-truncate">Dashboard</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\CentralLogics\Helpers::permission_check('account_manage'))
                                        @if (_storeAccountType() == 'ledger')
                                            @if (hasPermission('approvals', 'list'))
                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/approvals') ? 'active' : '' }}">
                                                    <a class="nav-link " href="{{ route('vendor.account.approvals') }}"
                                                        title="{{ translate('messages.Approvals') }}">
                                                        <span class="tio-dashboard nav-icon"></span>
                                                        <span class="text-truncate">Approvals</span>
                                                    </a>
                                                </li>
                                            @endif

                                            @if (hasAnyPermission([
                                                    'apporval_form_journal_entry.add',
                                                    'apporval_form_journal_entry.edit',
                                                    'apporval_form_master_ledger.add',
                                                    'apporval_form_master_ledger.edit',
                                                ]))

                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/request-form*') ? 'active' : '' }}">
                                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                        href="javascript:;" title="Approval Forms">
                                                        <span class="tio-notebook-bookmarked nav-icon"></span>
                                                        <span class="text-truncate ">
                                                            Approval Forms</span>
                                                    </a>

                                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                        @if (hasAnyModulePermission(['apporval_form_journal_entry']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/request-form/journal-entry') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.request-form.journal-entry.index') }}"
                                                                    title="{{ translate('messages.Request Form') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Journal Entry Request
                                                                        Form</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['apporval_form_master_ledger']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/request-form/master-ledger') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.request-form.master-ledger.index') }}"
                                                                    title="{{ translate('messages.Request Form') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Master Leger Request
                                                                        Form</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['apporval_form_incoming_requests']))
                                                            @if (auth('vendor_employee')->check())
                                                                <li
                                                                    class="nav-item {{ Request::is('account/ds') ? 'active' : '' }}">
                                                                    <a class="nav-link "
                                                                        href="{{ route('vendor.account.request-form.incoming-requests') }}"
                                                                        title="{{ translate('messages.Request Form') }}">
                                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                                        <span class="text-truncate">Incoming
                                                                            Requests</span>
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        @endif
                                                    </ul>
                                                </li>
                                            @endif
                                        @endif
                                        @if (hasAnyModulePermission([
                                                'boa_journal_entry',
                                                'boa_day_book',
                                                'boa_petty_cashbook',
                                                'boa_monthly_maintenance',
                                                'boa_master_ledger',
                                            ]))
                                            <li
                                                class="navbar-vertical-aside-has-menu {{ Request::is('account/management') ||
                                                Request::is('account/journal-entry') ||
                                                Request::is('account/day-book') ||
                                                Request::is('account/petty-cashbook') ||
                                                Request::is('account/maintenance')
                                                    ? 'active'
                                                    : '' }}">
                                                <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                    href="javascript:;" title="Books of Accounts">
                                                    <span class="tio-notebook-bookmarked nav-icon"></span>
                                                    <span class="text-truncate ">
                                                        Books of Accounts</span>
                                                </a>


                                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                    @if (hasAnyModulePermission(['boa_master_ledger']))
                                                        <li
                                                            class="nav-item {{ Request::is('account/management') ? 'active' : '' }}">
                                                            <a class="nav-link "
                                                                href="{{ route('vendor.account.add') }}"
                                                                title="{{ translate('messages.Master Ledger Book') }}">
                                                                <span class="tio-circle nav-indicator-icon"></span>
                                                                <span class="text-truncate">Master Ledger Book</span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (_storeAccountType() == 'ledger')
                                                        @if (hasAnyModulePermission(['boa_journal_entry']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/journal-entry') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.journal-entry.index') }}"
                                                                    title="{{ translate('messages.Journal Entry Book') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Journal Entry
                                                                        Book</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['boa_day_book']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/day-book') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.day-book.index') }}"
                                                                    title="{{ translate('messages.Day Book') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Day Book</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['boa_petty_cashbook']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/petty-cashbook') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.petty-cashbook.index') }}"
                                                                    title="{{ translate('messages.Petty CashBook') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Petty CashBook</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['boa_monthly_maintenance']))
                                                            <li
                                                                class="nav-item  {{ Request::is('account/maintenance') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.maintenance.index') }}"
                                                                    title="{{ translate('messages.monthly_maintenance') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Monthly
                                                                        Maintenance</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                    @endif
                                                </ul>
                                            </li>
                                        @endif
                                        @if (_storeAccountType() == 'ledger')
                                            @if (hasAnyModulePermission(['banking_bank_accounts', 'banking_cash_book', 'banking_bank_reconciliation']))
                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/banking*') ? 'active' : '' }}">
                                                    <a class=" sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                        href="javascript:;" title="Banking">
                                                        <span class="tio-credit-cards nav-icon"></span>
                                                        <span class=" text-truncate">
                                                            Banking</span>
                                                    </a>

                                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                        @if (hasAnyModulePermission(['banking_bank_accounts']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/banking/bank-account') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.banking.bank-account.index') }}"
                                                                    title="{{ translate('messages.bank_accounts') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Bank Accounts</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['banking_cash_book']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/banking/cash-book') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.banking.cash-book.index') }}"
                                                                    title="{{ translate('messages.Cash Book') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Cash Book</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['banking_bank_reconciliation']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/banking/bank-reconciliation') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.banking.bank-reconciliation.index') }}"
                                                                    title="{{ translate('messages.bank_reconciliation') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Bank
                                                                        Reconciliation</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </li>
                                            @endif
                                            @if (hasAnyModulePermission(['statements_trial_balance', 'statements_balance_sheet']))
                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/statement*') ? 'active' : '' }}">
                                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                        href="javascript:;" title="Statements">
                                                        <span class="tio-file-text-outlined nav-icon"></span>
                                                        <span class="text-truncate">
                                                            Statements</span>
                                                    </a>

                                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                        @if (hasAnyModulePermission(['statements_trial_balance']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/statement/trial-balance') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.statement.trial-balance') }}"
                                                                    title="{{ translate('messages.trial_balance') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Trial Balance</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['statements_balance_sheet']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/statement/balance-sheet') ? 'active' : '' }}">
                                                                <a class="nav-link " href="javascript:;"
                                                                    title="{{ translate('messages.balance_sheet') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Balance Sheet</span>
                                                                </a>
                                                            </li>
                                                        @endif

                                                    </ul>
                                                </li>
                                            @endif
                                            @if (hasAnyModulePermission(['rmf_maintenance_requests', 'rmf_bill_payments', 'rmf_property_valuation']))
                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/monthly-finance*') ? 'active' : '' }}">
                                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                        href="javascript:;" title="Reports">
                                                        <span class="tio-chart-bar-2 nav-icon"></span>
                                                        <span class="text-truncate">
                                                            Recurring Monthly Finance</span>
                                                    </a>

                                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                        @if (hasAnyModulePermission(['rmf_maintenance_requests']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/monthly-finance/monthly-maintanance') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.monthly-finance.monthly-maintanance') }}"
                                                                    title="Maintenance Requests">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Maintenance
                                                                        Requests</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['rmf_bill_payments']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/tax-report') ? 'active' : '' }}">
                                                                <a class="nav-link " href="javascript:;"
                                                                    title="{{ translate('messages.Bill Payments') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Bill Payments
                                                                    </span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['rmf_property_valuation']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/monthly-finance/property-valuation') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.monthly-finance.property-valuation') }}"
                                                                    title="{{ translate('messages. Property Valuation ') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Property
                                                                        Valuation</span>
                                                                </a>
                                                            </li>
                                                        @endif

                                                    </ul>
                                                </li>
                                            @endif
                                            @if (hasAnyModulePermission(['assets_company_assets', 'assets_chart_of_accounts']))
                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('asset*') ? 'active' : '' }}">
                                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                        href="javascript:;" title="Assets">
                                                        <span class="tio-chart-bar-2 nav-icon"></span>
                                                        <span class="text-truncate">
                                                            Assets</span>
                                                    </a>

                                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                        @if (hasAnyModulePermission(['assets_company_assets']))
                                                            {{-- @if (auth('vendor')->check()) --}}
                                                            <li
                                                                class="nav-item {{ Request::is('asset') ? 'active' : '' }}">
                                                                <a class=" nav-link"
                                                                    href="{{ route('vendor.asset.index') }}"
                                                                    title="Assets">
                                                                    <span class="tio-circle nav-indicator-icon"></span>

                                                                    <span class=" text-truncate">Company Assets
                                                                        (properties)</span>
                                                                </a>
                                                            </li>
                                                            {{-- @else --}}
                                                            <li
                                                                class="nav-item {{ Request::is('asset/alotted') ? 'active' : '' }}">
                                                                <a class=" nav-link"
                                                                    href="{{ route('vendor.asset.alotted') }}"
                                                                    title="Assets">
                                                                    <span class="tio-circle nav-indicator-icon"></span>

                                                                    <span class=" text-truncate">Alotted Company
                                                                        Assets</span>
                                                                </a>
                                                            </li>
                                                            {{-- @endif --}}
                                                        @endif

                                                        @if (hasAnyModulePermission(['assets_chart_of_accounts']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/settings') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.setting.chart-of-account.index') }}"
                                                                    title="{{ translate('messages.Chart of Accounts') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-account-square">Chart of
                                                                        Accounts</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </li>
                                            @endif
                                            @if (hasAnyModulePermission(['reports_account_report', 'reports_tax_report', 'reports_audit_logs']))
                                                <li
                                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/report*') ? 'active' : '' }}">
                                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                        href="javascript:;" title="Reports">
                                                        <span class="tio-chart-bar-2 nav-icon"></span>
                                                        <span class="text-truncate">
                                                            Reports</span>
                                                    </a>

                                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                        @if (hasAnyModulePermission(['reports_account_report']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/report') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.report') }}"
                                                                    title="{{ translate('messages.report') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Accounts Report</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['reports_tax_report']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/report/tax') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.report.tax') }}"
                                                                    title="{{ translate('messages. Tax Reports (GST/VAT) ') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Tax Reports
                                                                        (GST/VAT)</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasAnyModulePermission(['reports_audit_logs']))
                                                            <li
                                                                class="nav-item {{ Request::is('account/report/audit-logs') ? 'active' : '' }}">
                                                                <a class="nav-link "
                                                                    href="{{ route('vendor.account.report.audit-logs') }}"
                                                                    title="{{ translate('messages. Audit Logs ') }}">
                                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                                    <span class="text-truncate">Audit Logs</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </li>
                                            @endif


                                        @endif
                                        @if (hasAnyModulePermission(['for_gst_filing_report']))
                                            <li
                                                class="navbar-vertical-aside-has-menu {{ Request::is('account/taxation/gst') ? 'active' : '' }}">
                                                <a class="nav-link " href="{{ route('vendor.account.taxation.gst') }}"
                                                    title="{{ translate('messages.GST') }}">
                                                    <span class="tio-dashboard nav-icon"></span>
                                                    <span class="text-truncate">For GST Filing Report</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasAnyModulePermission(['settings_account_type', 'settings_common']))
                                            <li
                                                class="navbar-vertical-aside-has-menu {{ Request::is('account/setting*') ? 'active' : '' }}">
                                                <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                    href="javascript:;" title="Settings">
                                                    <span class="tio-settings nav-icon"></span>
                                                    <span class=" text-truncate">
                                                        Settings</span>
                                                </a>

                                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                                    @if (hasAnyModulePermission(['settings_account_type']))
                                                        <li
                                                            class="nav-item {{ Request::is('account/setting') ? 'active' : '' }}">
                                                            <a class="nav-link "
                                                                href="{{ route('vendor.account.setting') }}"
                                                                title="{{ translate('messages. Account Type ') }}">
                                                                <span class="tio-circle nav-indicator-icon"></span>
                                                                <span class="text-truncate">Account Type</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if (hasAnyModulePermission(['settings_common']))
                                                        <li
                                                            class="nav-item {{ Request::is('account/setting/common-settings') ? 'active' : '' }}">
                                                            <a class="nav-link "
                                                                href="{{ route('vendor.account.setting.common-settings') }}"
                                                                title="{{ translate('messages.Account Settings') }}">
                                                                <span class="tio-circle nav-indicator-icon"></span>
                                                                <span class="text-truncate">Common Settings</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </li>
                                        @endif
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endunless
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        {{-- =============================== iNVENTORY Management=========================== --}}
                        @if (selected_menu('inventory_manage') && hasMasterModulePermission('inventory_manage'))
                            @php
                                // Retail POS stores get BASIC inventory only (Items + Stock) — hide the
                                // advanced inventory areas (Dashboard, Stock In/Out, Sales, Purchase, Reports).
                                // Unless the store also purchased the full Inventory plan — then show everything.
                                $posRetailBasicInv = strtolower(\App\CentralLogics\Helpers::get_store_data()->business_type ?? '') === 'pos_retail'
                                    && !\App\CentralLogics\Helpers::has_purchased_module('inventory_manage');
                            @endphp

                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('inventory*') || Request::is('item/entry') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="{{ _moduleLabel('inventory_manage') }}">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Inventory_management_color.png') }}"
                                        alt="" class="nav-link-icon">

                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ _moduleLabel('inventory_manage') }}</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                    @if (hasPermission('inventory', 'dashboard') && !$posRetailBasicInv)
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('inventory/dashboard') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.inventory.dashboard') }}"
                                                title="{{ translate('messages.dashboard') }}">
                                                <span class="tio-dashboard nav-icon"></span>
                                                <span class="text-truncate">{{ _moduleLabel('inventory') }}
                                                    Dashboard</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('inventory*') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:" title="Products / Services">
                                            <i class="tio-shopping-cart nav-icon"></i>
                                            <span class=" text-truncate">
                                                Products / Services
                                            </span>
                                        </a>
                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                            style="display: {{ Request::is('inventory') || Request::is('inventory/storage-spaces') ? 'block' : 'none' }}">
                                            <li class="nav-item  {{ Request::is('inventory') ? 'active' : '' }}">
                                                <a class="nav-link" href="{{ route('vendor.inventory.index') }}"
                                                    title=" {{ translate('messages.add_inventory_item') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate sidebar--badge-container">
                                                        Inventory Items
                                                    </span>
                                                </a>
                                            </li>

                                            @if (hasAnyPermission(['inventory_storage_units.list', 'inventory_storage_units.add']))
                                                <li
                                                    class="nav-item {{ Request::is('inventory/storage-spaces') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.storage-spaces') }}"
                                                        title="Storage Units">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Storage Units
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                            {{-- <li
                                            class="nav-item {{ Request::is('inventory/item-images') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.inventory.item-images') }}"
                                                title="Item Images">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate sidebar--badge-container">
                                                    Item Images
                                                </span>
                                            </a>
                                        </li> --}}
                                        </ul>
                                    </li>
                                    @if (hasAnyPermission(['inventory_stock_in_out.list']) && !$posRetailBasicInv)
                                        <li
                                            class="navbar-vertical-aside-has-menu  {{ Request::is('inventory/stock*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:" title="Stock Management">
                                                <i class="tio-folders-outlined nav-icon"></i>
                                                <span class=" text-truncate">
                                                    Stock Management
                                                </span>
                                            </a>
                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: ">
                                                @if (hasPermission('inventory_stock_in_out', 'list'))
                                                    <li
                                                        class="nav-item  {{ Request::is('inventory/stock/stock-in-out') ? 'active' : '' }}">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.stock.stock-in-out') }}"
                                                            title="Stock in / Stock out">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Stock in / Stock out
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
                                    @if (hasAnyPermission([
                                            'inventory_sale_order.list',
                                            'inventory_sale_order.export',
                                            'inventory_sale_return.export',
                                            'inventory_sale_return.list',
                                        ]) && !$posRetailBasicInv)
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('inventory/sale*') ? 'active' : '' }} ">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:" title="Sales">
                                                <i class="tio-chart-bar-1 nav-icon"></i>
                                                <span class=" text-truncate">
                                                    Sales
                                                </span>
                                            </a>
                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                                style="display: {{ Request::is('inventory/sale*') ? 'block' : 'none' }} ">
                                                @if (hasPermission('inventory_sale_order', 'list') || hasPermission('inventory_sale_order', 'export'))
                                                    <li
                                                        class="nav-item {{ Request::is('inventory/sale/orders') ? 'active' : '' }}">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.sale.orders') }}"
                                                            title="Sale Orders">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Sale Orders
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif

                                                @if (hasPermission('inventory_sale_return', 'list') || hasPermission('inventory_sale_return', 'export'))
                                                    <li
                                                        class="nav-item {{ Request::is('inventory/sale/orders-return') ? 'active' : '' }}">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.sale.orders-return') }}"
                                                            title="Return Orders">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Return Orders
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
                                    @if (hasAnyPermission([
                                            'inventory_purchase_return.add',
                                            'inventory_purchase_return.list',
                                            'inventory_purchase_order.add',
                                            'inventory_purchase_order.list',
                                            'purchase_bill.list',
                                        ]) && !$posRetailBasicInv)
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('inventory/purchase*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:" title="Purchase">
                                                <i class="tio-chart-bar-2 nav-icon"></i>
                                                <span class=" text-truncate">
                                                    Purchase
                                                </span>
                                            </a>
                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                                style="display: {{ Request::is('inventory/purchase*') ? 'block' : 'none' }}">
                                                @if (hasPermission('inventory_purchase_order', 'add') || hasPermission('inventory_purchase_order', 'list'))
                                                    <li
                                                        class="nav-item {{ Request::is('inventory/purchase/orders') ? 'active' : '' }}">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.purchase.orders') }}"
                                                            title="Purchase Orders">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Purchase Orders
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasPermission('inventory_purchase_return', 'add') || hasPermission('inventory_purchase_return', 'list'))
                                                    <li
                                                        class="nav-item {{ Request::is('inventory/purchase/return') ? 'active' : '' }}">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.purchase.return') }}"
                                                            title="Return Purchase">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Return Purchase
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasPermission('purchase_bill', 'list'))
                                                    <li
                                                        class="nav-item {{ Request::is('billing/purchase-bills') ? 'active' : '' }}">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.invoice.my-bills') }}"
                                                            title="Purchase Bills">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Purchase Bills
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
                                    @if (hasAnyPermission([
                                            'stock_report.list',
                                            'stock_report.export',
                                            'sale_report.export',
                                            'sale_report.list',
                                            'profit_loss_summary.export',
                                            'profit_loss_summary.list',
                                            'purchase_report.export',
                                            'purchase_report.list',
                                        ]) && !$posRetailBasicInv)
                                        <li class="navbar-vertical-aside-has-menu ">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:" title="Reports">
                                                <i class="tio-document-outlined nav-icon"></i>
                                                <span class=" text-truncate">
                                                    Reports
                                                </span>
                                            </a>
                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: ">
                                                @if (hasPermission('stock_report', 'list') || hasPermission('stock_report', 'export'))
                                                    <li class="nav-item ">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.report.stock') }}"
                                                            title="Stock Report">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Stock Report
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasPermission('sale_report', 'list') || hasPermission('sale_report', 'export'))
                                                    <li class="nav-item ">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.report.sale') }}"
                                                            title="Sales Report">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Sales Report
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasPermission('purchase_report', 'list') || hasPermission('purchase_report', 'export'))
                                                    <li class="nav-item ">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.report.purchase') }}"
                                                            title="Purchase Report">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Purchase Report
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasPermission('profit_loss_summary', 'list') || hasPermission('profit_loss_summary', 'export'))
                                                    <li class="nav-item ">
                                                        <a class="nav-link"
                                                            href="{{ route('vendor.inventory.report.profit-and-loss') }}"
                                                            title="Profit & Loss Summary">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate sidebar--badge-container">
                                                                Profit & Loss Summary
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                <li class="nav-item ">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.report.gst') }}"
                                                        title="GST Report">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            GST Report
                                                        </span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="nav-item {{ Request::is('inventory/report/batch-expiry') ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                        href="{{ route('vendor.inventory.report.batch-expiry') }}"
                                                        title="Batch & Expiry">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate sidebar--badge-container">
                                                            Batch &amp; Expiry
                                                        </span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                    @endif
                                    @if (!$posRetailBasicInv)
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('inventory/gatepass*') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                            href="javascript:" title="Gatepass">
                                            <i class="tio-shopping-cart nav-icon"></i>
                                            <span class=" text-truncate">
                                                Gatepass
                                            </span>
                                        </a>
                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                            style="display: {{ Request::is('inventory/gatepass*') ? 'block' : 'none' }}">
                                            <li class="nav-item  {{ Request::is('inventory') ? 'active' : '' }}">
                                                <a class="nav-link"
                                                    href="{{ route('vendor.inventory.gatepass.list', ['purchase']) }}"
                                                    title=" {{ translate('messages.purchase gatepass') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate sidebar--badge-container">
                                                        Purchase Gatepass
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="nav-item  {{ Request::is('inventory') ? 'active' : '' }}">
                                                <a class="nav-link"
                                                    href="{{ route('vendor.inventory.gatepass.list', ['sale']) }}"
                                                    title=" {{ translate('messages.sale gatepass') }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate sidebar--badge-container">
                                                        Sale Gatepass
                                                    </span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    @endif
                                    @if (hasPermission('inventory', 'settings'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('inventory/settings') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.inventory.settings') }}"
                                                title="{{ translate('messages.dashboard') }}">
                                                <span class="tio-settings-outlined nav-icon"></span>
                                                <span class="text-truncate">{{ _moduleLabel('inventory') }}
                                                    Settings</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                            {{-- ===================================== inventory END========================== --}}
                        @endif
                    @endunless

                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        {{-- =============================== Basic Staff (free, shown when HR not subscribed) --}}
                        @if (
                            !\App\CentralLogics\Helpers::permission_check('hr_manage') &&
                                !\App\CentralLogics\Helpers::permission_check('laundry') &&
                                selected_menu('staff_manage'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('basic-staff*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="Staff Management">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/HR_management_color.png') }}"
                                        alt="" class="nav-link-icon">
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        Staff Management</span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('basic-staff*') ? 'block' : 'none' }}">
                                    @if (hasPermission('basic_staff_manage', 'list'))
                                        <li class="nav-item {{ Request::is('basic-staff') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.basic-staff.index') }}"
                                                title="Staff List">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Staff List</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('basic_staff_manage', 'add'))
                                        <li class="nav-item {{ Request::is('basic-staff/create') || Request::is('basic-staff/edit/*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.basic-staff.create') }}"
                                                title="Add Staff">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Add Staff</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('basic_staff_manage', 'role_manage'))
                                        <li class="nav-item {{ Request::is('basic-staff/roles*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.basic-staff.roles') }}"
                                                title="Roles & Permissions">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Roles &amp; Permissions</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endunless

                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        {{-- =============================== HR Management=========================== --}}
                        @if (
                            (selected_menu('staff_manage') ||
                                selected_menu('attendance_manage') ||
                                selected_menu('leave_manage') ||
                                selected_menu('salary_manage')) &&
                                hasMasterModulePermission('hr_manage'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('hr*') || Request::is('task-salary-categories') || Request::is('shifts*') || Request::is('custom-role*') || Request::is('staff*') || Request::is('salary*') || Request::is('leave*') || Request::is('attendance*') ? 'active' : '' }} ">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                    title="HR Management">
                                    <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/HR_management_color.png') }}"
                                        alt="" class="nav-link-icon">

                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        HR Management</span>
                                </a>

                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                    @if (hasPermission('hr_manage', 'dashboard'))
                                        <li class="nav-item {{ Request::is('hr/dashboard') ? 'active' : '' }}">
                                            <a class="nav-link " href="{{ route('vendor.hr.dashboard') }}"
                                                title="HR Management Dashboard">
                                                <span class="tio-dashboard-outlined nav-icon"></span>
                                                <span class="text-truncate">Dashboard</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasAnyModulePermission(['staff_manage', 'staff_team', 'staff_department', 'staff_role']) &&
                                            selected_menu('staff_manage'))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('staff*') || Request::is('custom-role*') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;"
                                                title="Staff
                                                Management">
                                                <i class="tio-group-junior nav-icon"></i>
                                                <span class=" text-truncate">Staff
                                                    Management</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                                style="display: {{ Request::is('staff*') || Request::is('custom-role*') ? 'block' : 'none' }}">
                                                @if (hasPermission('staff_manage', 'add'))
                                                    <li
                                                        class="nav-item {{ Request::is('staff/add-new') ? 'active' : '' }}">
                                                        <a class="nav-link " href="{{ route('vendor.staff.add-new') }}"
                                                            title="{{ translate('messages.add') }} {{ translate('messages.new') }} {{ translate('messages.Employee') }}">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">{{ translate('messages.add') }}
                                                                {{ translate('messages.new') }} Staff</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasAnyPermission(['staff_manage.list', 'staff_manage.export']))
                                                    <li class="nav-item {{ Request::is('staff/list') ? 'active' : '' }}">
                                                        <a class="nav-link " href="{{ route('vendor.staff.list') }}"
                                                            title="Staff {{ translate('messages.list') }}">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Staff
                                                                {{ translate('messages.list') }} </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasAnyModulePermission(['staff_team']))
                                                    <li class="nav-item {{ Request::is('staff/team') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.staff.team.index') }}"
                                                            title="Teams">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Teams </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasAnyModulePermission(['staff_department']))
                                                    <li
                                                        class="nav-item {{ Request::is('staff-department') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.staff-department.all') }}"
                                                            title="Staff Department">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Staff Department</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasAnyModulePermission(['staff_role']))
                                                    <li
                                                        class="nav-item {{ Request::is('custom-role/create') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.custom-role.create') }}"
                                                            title="Staff Role">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Staff Roles (Permissions)</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasPermission('staff_manage', 'settings'))
                                                    <li
                                                        class="nav-item {{ Request::is('staff/settings*') ? 'active' : '' }}">
                                                        <a class="nav-link " href="{{ route('vendor.staff.settings') }}"
                                                            title="Staff T&C">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Staff Settings</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
                                    @if (selected_menu('attendance_manage') && hasAnyModulePermission(['attendance_manage', 'attendance_report']))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('attendance*') ? 'active' : '' }}">
                                            <a class="sub-link  js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Attendance Management">
                                                <i class="tio-event nav-icon"></i>
                                                <span class="text-truncate">Attendance
                                                    Management</span>
                                            </a>

                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                                style="display: {{ Request::is('attendance*') ? 'block' : 'none' }}">
                                                @if (hasPermission('attendance_manage', 'list'))
                                                    <li
                                                        class="nav-item {{ Request::is('attendance/list') ? 'active' : '' }}">
                                                        <a class="nav-link " href="{{ route('vendor.attendance.all') }}"
                                                            title="{{ translate('messages.manage') }}">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Attendance Manage</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasAnyModulePermission(['attendance_report']))
                                                    <li
                                                        class="nav-item {{ Request::is('attendance/report') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.attendance.report') }}"
                                                            title="Reports">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Attendance Reports</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif


                                    @if (selected_menu('salary_manage') &&
                                            hasAnyModulePermission(['salary_advanced', 'salary_report', 'task_salary_category', 'salary_manage']))
                                        <li
                                            class="navbar-vertical-aside-has-menu {{ Request::is('salary*') || Request::is('task-salary-categories') ? 'active' : '' }}">
                                            <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                                href="javascript:;" title="Salary">
                                                <i class="tio-user nav-icon"></i>
                                                <span
                                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-truncate">Salary
                                                    Management</span>
                                            </a>
                                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                                style="display: {{ Request::is('salary*') || Request::is('task-salary-categories') ? 'block' : 'none' }}">
                                                @if (hasAnyModulePermission(['salary_manage']))
                                                    <li
                                                        class="nav-item {{ Request::is('salary/list') ? 'active' : '' }}">
                                                        <a class="nav-link " href="{{ route('vendor.salary.list') }}"
                                                            title="manage">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Salary Manage</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasAnyModulePermission(['task_salary_category']))
                                                    <li
                                                        class="nav-item {{ Request::is('task-salary-categories') ? 'active' : '' }}">
                                                        <a class="nav-link "
                                                            href="{{ route('vendor.task-salary-categories.index') }}"
                                                            title="report">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Task Salary Category</span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (hasAnyModulePermission(['salary_report']))
                                                    <li
                                                        class="nav-item {{ Request::is('salary/report') ? 'active' : '' }}">
                                                        <a class="nav-link " href="{{ route('vendor.salary.report') }}"
                                                            title="report">
                                                            <span class="tio-circle nav-indicator-icon"></span>
                                                            <span class="text-truncate">Salary Report</span>
                                                        </a>
                                                    </li>
                                                @endif

                                            </ul>
                                        </li>
                                    @endif
                                    @if (hasAnyModulePermission(['shift_manage']))
                                        <li class="navbar-vertical-aside {{ Request::is('shifts*') ? 'active' : '' }}">
                                            <a class="sub-link  nav-link" href="{{ route('vendor.shifts.index') }}"
                                                title="Shifts Management">
                                                <i class="tio-timer nav-icon"></i>
                                                <span
                                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Shifts
                                                    Management</span>
                                            </a>
                                        </li>
                                    @endif


                                    @if (selected_menu('leave_manage') && hasAnyModulePermission(['leave_manage']))
                                        <li class="navbar-vertical-aside {{ Request::is('leave*') ? 'active' : '' }}">
                                            <a class="sub-link  nav-link" href="{{ route('vendor.leave.all') }}"
                                                title="Leave Management">
                                                <i class="tio-category nav-icon"></i>
                                                <span
                                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Leave
                                                    Management</span>
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                            </li>
                        @endif
                    @endunless



                    {{-- =============================== MY BUSINESS =========================== --}}
                    @if (selected_menu('my_business') && \App\CentralLogics\Helpers::employee_module_permission_check('my_business'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ (Request::is('business-settings*') || Request::is('withdraw-method*') || Request::is('wallet/wallet-payment-list') || Request::is('settings/general*')) && !Request::is('business-settings/settings') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="My Business">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Business_color.png') }}"
                                    alt="" class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    My Business</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                @if (hasPermission('webpage_settings', 'view'))
                                <li class="nav-item  {{ Request::is('settings/settings/webpage') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.settings.webpage') }}"
                                        title="Webpage Settings">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Webpage Settings</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('store_settings', 'view'))
                                <li class="nav-item {{ Request::is('store/edit') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.shop.edit') }}"
                                        title="Store Settings">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Store Settings</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('service_setup', 'view'))
                                <li class="nav-item {{ Request::is('settings/service-setup') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.settings.service-setup') }}"
                                        title="Service Setup">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Service Setup</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('profile_settings', 'view'))
                                <li class="nav-item {{ Request::is('settings/general/profile') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.settings.general.profile') }}"
                                        title="Profile Settings">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Profile Settings</span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('performance_analytics', 'view'))
                                <li
                                    class="nav-item {{ Request::is('store-panel/performance-analytics*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.performance-analytics.index') }}"
                                        title="Performance Analytics">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            Performance Analytics
                                        </span>
                                    </a>
                                </li>
                                @endif
                                @if (hasPermission('reviews', 'view'))
                                <li class="nav-item {{ Request::is('service/reviews*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.service.reviews') }}"
                                        title="Reviews">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            Reviews
                                        </span>
                                    </a>
                                </li>
                              
                                @endif
                                {{-- <li
                                    class="navbar-vertical-aside-has-menu  {{ Request::is('business-settings/terms-and-conditions') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('vendor.business-settings.common-terms-and-conditions') }}"
                                        title="Terms and Conditions">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Terms and Conditions</span>
                                    </a>
                                </li> --}}

                                {{-- <li
                                    class="navbar-vertical-aside-has-menu  {{ Request::is('business-settings*') || Request::is('withdraw-method*') || Request::is('wallet/wallet-payment-list') || Request::is('settings/general*') ? 'active' : '' }}">
                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:;" title="Business Section">
                                        <span class="tio-circle nav-indicator-icon"></span>

                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            Business Section</span>
                                    </a>
                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">

                                        @if (\App\CentralLogics\Helpers::employee_module_permission_check('store_setup'))
                                            <li class="nav-item {{ Request::is('business-settings/about-us') ? 'active' : '' }}"
                                                style="margin-top:0 !important;">
                                                <a class="nav-link "
                                                    href="{{ route('vendor.business-settings.about-us') }}"
                                                    title="Terms and Conditions">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">About Us</span>
                                                </a>
                                            </li>
                                            <li class="nav-item {{ Request::is('general/holidays') ? 'active' : '' }}"
                                                style="margin-top:0 !important;">
                                                <a class="nav-link "
                                                    href="{{ route('vendor.settings.general.holidays') }}"
                                                    title="Holidays">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">Holidays</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li> --}}
                                @if (0 && auth('vendor')->check())
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('offer-banner') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link"
                                            href="{{ route('vendor.banner.offer') }}"
                                            title="{{ translate('messages.offer_banners') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>

                                            <span
                                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.offer_banners') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('banner*') && !Request::is('offer-banner') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link"
                                            href="{{ route('vendor.banner.list') }}"
                                            title="{{ translate('messages.banners') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>

                                            <span
                                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.banners') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="navbar-vertical-aside-has-menu {{ Request::is('gallery') ? 'active' : '' }}">
                                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link"
                                            href="{{ route('vendor.gallery.all') }}"
                                            title="{{ translate('messages.gallery') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>

                                            <span class=" text-truncate">{{ translate('messages.gallery') }}</span>
                                        </a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif
                    {{-- =============================== MY WALLET =========================== --}}
                    @if (selected_menu('my_wallet') && \App\CentralLogics\Helpers::employee_module_permission_check('wallet'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('wallet/wallet-payment-list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.wallet.wallet_payment_list') }}"
                                title="{{ translate('messages.my_wallet') }}">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/My%20Wallet_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class=" text-truncate">{{ translate('messages.my_wallet') }}</span>
                            </a>
                        </li>

                        @if (
                            \App\CentralLogics\Helpers::employee_module_permission_check('wallet') &&
                                \App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('withdraw-method*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('vendor.wallet-method.index') }}"
                                    title="{{ translate('messages.my_wallet') }}">
                                    <i class="tio-museum nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.disbursement_method') }}</span>
                                </a>
                            </li>
                        @endif
                    @endif

                    {{-- @if (selected_menu('notifications') && \App\CentralLogics\Helpers::employee_module_permission_check('notifications'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('notifications*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.notifications') }}" title="notifications">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Notifications_color.png') }}"
                                    alt="" class="nav-link-icon">

                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Notifications
                                </span>
                            </a>
                        </li>
                    @endif --}}

                    @if (0 && selected_menu('smart_calendar'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('smart-calendar*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.smart-calendar.all') }}" title="Smart Calendar">
                                <img src="{{ asset('storage/app/public/nav/reminder.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">Smart Calendar</span>
                            </a>
                        </li>
                    @endif

                    <!-- End Dashboards -->

                    @if (auth('vendor_employee')->check())
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('attendance*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.employee-attendance') }}" title="attendance">
                                <img src="{{ asset('storage/app/public/nav/attendance.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">
                                    Attendance
                                </span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('salary-history*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.salary-history') }}" title="My Salary">
                                <img src="{{ asset('storage/app/public/nav/salary.png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">
                                    My Salary
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('leaves*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.employee-leave') }}" title="My Leaves">
                                <img src="{{ asset('storage/app/public/nav/leave (1).png') }}" alt=""
                                    class="nav-link-icon">
                                <span class=" text-truncate">
                                    My Leaves
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('my-performance*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.report.staff-combined') }}" title="My Performance">
                                <i class="tio-chart-bar-4 nav-link-icon" style="font-size:20px;"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    My Performance
                                </span>
                            </a>
                        </li>
                    @endif





                    @if (in_array($store_data->module->module_type, ['ecommerce']))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('item/flash-sale*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.item.flash_sale') }}"
                                title="{{ translate('messages.flash_sales') }}">
                                <i class="tio-apps nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.flash_sales') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($store_data->module->module_type == 'ecommerce')
                        <li class="nav-item">
                            <small
                                class="nav-subtitle">{{ $store_data->module->module_type == 'ecommerce' ? translate('messages.item_management') : 'Service Management' }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                    @endif

                    <!-- AddOn -->
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('addon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('addon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.addon.add-new') }}"
                                title="{{ translate('messages.addons') }}">
                                <i class="tio-add-circle-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.addons') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    <!-- End AddOn -->
                    @if (
                        \App\CentralLogics\Helpers::employee_module_permission_check('item') &&
                            $store_data->module->module_type == 'ecommerce')
                        <!-- Food -->
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('item*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.items') }}">
                                <i class="tio-premium-outlined nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.items') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('item*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('item/add-new') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.item.add-new') }}"
                                        title="{{ translate('messages.add_new_item') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add_new') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('item/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.item.list') }}"
                                        title="{{ translate('messages.items_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>

                                @if (\App\CentralLogics\Helpers::get_mail_status('product_approval'))
                                    <li
                                        class="nav-item {{ Request::is('item/pending/item/list') || Request::is('item/requested/item/view/*') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.pending_item_list') }}"
                                            title="{{ translate('messages.pending_item_list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate">{{ translate('messages.pending_item_list') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (\App\CentralLogics\Helpers::get_mail_status('product_gallery'))
                                    <li class="nav-item {{ Request::is('item/product-gallery') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.product_gallery') }}"
                                            title="{{ translate('messages.Product_Gallery') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate">{{ translate('messages.Product_Gallery') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="nav-item {{ Request::is('item/price-update-list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.item.price-update-list') }}"
                                        title="{{ translate('messages.price_update_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Price Update</span>
                                    </a>
                                </li>

                                @if ($store_data->module->module_type != 'food')
                                    <li class="nav-item {{ Request::is('item/stock-limit-list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.stock-limit-list') }}"
                                            title="{{ translate('messages.stock_limit_list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate">{{ translate('messages.stock_limit_list') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (\App\CentralLogics\Helpers::get_store_data()->item_section)
                                    <li class="nav-item {{ Request::is('item/bulk-import') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.bulk-import') }}"
                                            title="{{ translate('messages.bulk_import') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('item/bulk-export') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.item.bulk-export-index') }}"
                                            title="{{ translate('messages.bulk_export') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        <!-- End Food -->
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('category*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.categories') }}">
                                <i class="tio-category nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.categories') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('category*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('category/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.category.add') }}"
                                        title="{{ translate('messages.category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.category') }}</span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('category/sub-category-list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.category.add-sub-category') }}"
                                        title="{{ translate('messages.sub_category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.sub_category') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- DeliveryMan -->
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('deliveryman'))
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.deliveryman_section') }}">{{ translate('messages.deliveryman_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('delivery-man/add') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.delivery-man.add') }}"
                                title="{{ translate('messages.add_delivery_man') }}">
                                <i class="tio-running nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.add_delivery_man') }}
                                </span>
                            </a>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('delivery-man/list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.delivery-man.list') }}"
                                title="{{ translate('messages.deliveryman') }}">
                                <i class="tio-filter-list nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.deliverymen_list') }}
                                </span>
                            </a>
                        </li>
                    @endif


                    <!-- Campaign -->
                    @if ($store_data->module->id == 5 && \App\CentralLogics\Helpers::employee_module_permission_check('campaign'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('campaign*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:" title="{{ translate('messages.campaigns') }}">
                                <i class="tio-image nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.campaigns') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('campaign*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('campaign/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.campaign.list') }}"
                                        title="{{ translate('messages.basic_campaigns') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{ translate('messages.basic_campaigns') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('campaign/item/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.campaign.itemlist') }}"
                                        title="{{ translate('messages.Item Campaigns') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{ translate('messages.Item Campaigns') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- End Campaign -->

                    <!-- Coupon -->
                    @if ($store_data->module->id == 5 && \App\CentralLogics\Helpers::employee_module_permission_check('coupon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('coupon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.coupon.add-new') }}"
                                title="{{ translate('messages.coupons') }}">
                                <i class="tio-ticket nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }}</span>
                            </a>
                        </li>
                    @endif
                    <!-- End Coupon -->
                    @if (selected_menu('hospital_manage') && \App\CentralLogics\Helpers::permission_check('hospital_manage'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('patient*') || Request::is('doctor*') || Request::is('nurse*') || Request::is('appointment*') || Request::is('prescription*') || Request::is('ward*') || Request::is('opd*') || Request::is('ipd*') || Request::is('hospital/dashboard') || Request::is('hospital-bill*') || Request::is('consent*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:;" title="Hospital Management">
                                <i class="tio-hospital nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Hospital Management
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                <li class="nav-item {{ Request::is('hospital/dashboard') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.hospital.dashboard') }}"
                                        title="Hospital Dashboard">
                                        <span class="tio-dashboard-vs-outlined nav-icon"></span>
                                        <span class="text-truncate">Dashboard</span>
                                    </a>
                                </li>
                                @if (auth('vendor_employee')->check())
                                    <li
                                        class="nav-item {{ Request::is('hospital/staff-dashboard') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.hospital.staff-dashboard') }}"
                                            title="My OPD Visits">
                                            <span class="tio-document-text nav-icon"></span>
                                            <span class="text-truncate">My OPD Visits</span>
                                        </a>
                                    </li>
                                @endif
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/request-form*') ? 'active' : '' }}">
                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:;" title="Approval Forms">
                                        <span class="tio-group-senior nav-icon"></span>
                                        <span class="text-truncate ">
                                            Staff</span>
                                    </a>

                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                        <li class="nav-item {{ Request::is('doctor*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.doctor.list') }}"
                                                title="Doctors">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Doctors</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ Request::is('nurse*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.nurse.list') }}"
                                                title="Nurses">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Nurses</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>


                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('patient/list') || Request::is('opd*') || Request::is('prescription*') ? 'active' : '' }}">
                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:;" title="Outpatient">
                                        <span class="tio-hospital nav-icon"></span>
                                        <span class="text-truncate">Outpatient</span>
                                    </a>

                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                        <li class="nav-item {{ Request::is('patient/list') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.patient.list') }}"
                                                title="Patient List">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Patients</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ Request::is('opd*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.opd.index') }}"
                                                title="OPD Register">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">OPD Register</span>
                                            </a>
                                        </li>
                                        <li
                                            class="nav-item {{ Request::is('prescription') || Request::is('prescription/list') || Request::is('prescription/create') || Request::is('prescription/*/edit') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.prescription.list') }}"
                                                title="Prescriptions">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Prescriptions</span>
                                            </a>
                                        </li>
                                        <li
                                            class="nav-item {{ Request::is('prescription/dispense*') ? 'active' : '' }}">
                                            <a class="nav-link"
                                                href="{{ route('vendor.prescription.dispense.queue') }}"
                                                title="Dispense Queue">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Dispense Queue</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/request-form*') ? 'active' : '' }}">
                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:;" title="Approval Forms">
                                        <span class="tio-hospital nav-icon"></span>
                                        <span class="text-truncate ">
                                            Inpatient</span>
                                    </a>

                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                        <li class="nav-item {{ Request::is('ipd*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.ipd.index') }}"
                                                title="IPD Admissions">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">IPD Admissions</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ Request::is('ward*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.ward.index') }}"
                                                title="Wards & Beds">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Wards &amp; Beds</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('account/request-form*') ? 'active' : '' }}">
                                    <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                        href="javascript:;" title="Approval Forms">
                                        <span class="tio-documents-outlined nav-icon"></span>
                                        <span class="text-truncate ">
                                            Documents</span>
                                    </a>

                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                        <li
                                            class="nav-item {{ Request::is('consent') || Request::is('consent/create') || Request::is('consent/[0-9]*') ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ route('vendor.consent.index') }}"
                                                title="Consent Forms">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Consent Forms</span>
                                            </a>
                                        </li>
                                        <li class="nav-item {{ Request::is('consent/template*') ? 'active' : '' }}">
                                            <a class="nav-link"
                                                href="{{ route('vendor.consent.template.index') }}"
                                                title="Consent Templates">
                                                <span class="tio-circle nav-indicator-icon"></span>
                                                <span class="text-truncate">Consent Templates</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <li class="nav-item {{ Request::is('billing*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.invoice.list') }}"
                                        title="Billing">
                                        <span class="tio-file-text-outlined nav-icon"></span>
                                        <span class="text-truncate">Billing</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif


                    <!-- End StoreWallet -->
                    @if ($store_data->module->id == 5 && \App\CentralLogics\Helpers::employee_module_permission_check('reviews'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('reviews') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.reviews') }}"
                                title="{{ translate('messages.reviews') }}">
                                <i class="tio-star-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.reviews') }}
                                </span>
                            </a>
                        </li>
                    @endif
                    <!-- End Business Settings -->
                    @if (
                        \App\CentralLogics\Helpers::employee_module_permission_check('chat') &&
                            \App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('message*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.message.list') }}"
                                title="{{ translate('messages.chat') }}">
                                <i class="tio-chat nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Chat') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    {{-- <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('messages.Report_section') }}">{{ translate('messages.Report_section') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li> --}}

                    @if (
                        \App\CentralLogics\Helpers::employee_module_permission_check('report') &&
                            \App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/report/expense-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.report.expense-report') }}"
                                title="{{ translate('messages.expense_report') }}">
                                <span class="tio-history nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.expense_report') }}</span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('report/disbursement-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.report.disbursement-report') }}"
                                title="{{ translate('messages.disbursement_report') }}">
                                <span class="tio-saving nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.disbursement_report') }}</span>
                            </a>
                        </li>
                    @endif






                    {{-- @if (selected_menu('library') && \App\CentralLogics\Helpers::permission_check('library'))
                         <li class="nav-item">
                            <small class="nav-subtitle" title="{{ translate('Library') }}">{{ translate('Library') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('library*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.library.all') }}" title="Library">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Library_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class=" text-truncate">Library</span>
                            </a>
                        </li>
                    @endif --}}


                    @if (selected_menu('documents') &&
                            (\App\CentralLogics\Helpers::employee_module_permission_check('documents') ||
                                \App\CentralLogics\Helpers::employee_module_permission_check('library')))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('documents*') || Request::is('business-settings/settings/receivable-receipts') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                href="javascript:;" title="Documents">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Documents_color.png') }}"
                                    alt="" class="nav-link-icon">

                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Documents</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('documents*') || Request::is('business-settings/settings/receivable-receipts') ? 'block' : 'none' }}">
                                @if (\App\CentralLogics\Helpers::employee_module_permission_check('documents'))
                                    <li
                                        class="nav-item {{ Request::is('documents/receivable-receipt/list') ? 'active' : '' }}">
                                        <a class="nav-link "
                                            href="{{ route('vendor.documents.receivable-receipt.list') }}"
                                            title="list Project">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Receivable Receipts List</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('business-settings/settings/receivable-receipts') ? 'active' : '' }}"
                                        style="margin-top:0 !important;">
                                        <a class="nav-link "
                                            href="{{ route('vendor.business-settings.settings.receivable-receipts') }}"
                                            title="Receivable Receipt Settings">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Receivable Receipt Settings</span>
                                        </a>
                                    </li>
                                    <li
                                        class="nav-item {{ Request::is('documents/job-card/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('vendor.documents.job-card.list') }}"
                                            title="jobcard list">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Jobcards List</span>
                                        </a>
                                    </li>
                                    <li
                                        class="nav-item {{ Request::is('documents/gatepass/list') ? 'active' : '' }}">
                                        <a class="nav-link"
                                            href="{{ route('vendor.documents.gatepass.list', ['purchase']) }}"
                                            title="Inventory Gatepass">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Inventory Gatepass</span>
                                        </a>
                                    </li>
                                @endif
                                @if (\App\CentralLogics\Helpers::employee_module_permission_check('library'))
                                    <li class="nav-item {{ Request::is('library*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.library.all') }}"
                                            title="Library">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Library</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if (selected_menu('post_ads') && \App\CentralLogics\Helpers::employee_module_permission_check('notifications'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('push-notification') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link "
                                href="{{ route('vendor.notification.add-new') }}" title="push notifications">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Advertisements_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="text-truncate">Post Ads</span>
                            </a>
                        </li>
                    @endif

                    {{-- Laundry --}}
                    @if(0)
                    @unless (isset($skipForLaundry) && $skipForLaundry)
                        @if (selected_menu('laundry') && \App\CentralLogics\Helpers::permission_check('laundry'))
                            <li class="navbar-vertical-aside-has-menu {{ Request::is('laundry*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                    href="javascript:;" title="Laundry">
                                    <i class="tio-shopping-basket nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Laundry</span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('laundry*') ? 'block' : 'none' }}">
                                    <li class="nav-item {{ Request::is('laundry/dashboard*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.laundry.dashboard') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Dashboard</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('laundry/orders*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.laundry.orders') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Walk-in Orders</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('laundry/challans*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.laundry.challans') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Hotel Challans</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('laundry/register*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.laundry.register') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Monthly Register</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('laundry/items*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.laundry.items') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">Item Master</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endunless
                    @endif

                    @if (selected_menu('subscriptions') && \App\CentralLogics\Helpers::employee_module_permission_check('subscriptions'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('subscriptions') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link "
                                href="{{ route('vendor.subscriptions') }}" title="Subscriptions">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Subscriptions_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="text-truncate">Subscriptions</span>
                            </a>
                        </li>
                    @endif
                    @php($primary_color = \App\Models\BusinessSetting::where('key', 'primary_color')->first())
                    @php($secondary_color = \App\Models\BusinessSetting::where('key', 'secondary_color')->first())
                    @php($primary_btn_hover = \App\Models\BusinessSetting::where('key', 'primary_btn_hover')->first())
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('subscriptions') ? 'active' : '' }}">
                        <div
                            style="padding: 10px 11px;
    display: flex;
    margin: 0 10px;
    border-radius: 10px; align-items: center;background-color: color-mix(in srgb, {{ $primary_color ? $primary_color->value : '#754BFF' }} 15%, transparent);">
                            <label class="switch toggle-switch-lg m-0">
                                <input type="checkbox" class="toggle-switch-input keep-minimized"
                                    {{ _isMenuMinimized() ? 'checked' : '' }} value = '1'>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                            <span class="text-primary pl-2" style="font-size: 14px;">Keep Menu <br> Minimized</span>
                        </div>
                    </li>

                    <li class="nav-item py-5">

                    </li>
                    {{-- =============================== MENU PREFERENCE =========================== --}}
                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('menu_preference'))
                        <a class="text-truncate"
                            style="    position: absolute;bottom: 2px;left: auto;background: #fff4f4;padding: 2px;font-size: 12px;text-align: center;width: 96%;"
                            href="{{ route('vendor.menu_preference') }}" title="Menu Preference">

                            <span class="text-truncate"><i class="tio-settings-outlined"></i> Menu
                                Preferences</span>
                        </a>
                    @endif
