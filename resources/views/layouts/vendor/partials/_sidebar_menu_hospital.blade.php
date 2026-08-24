                {{-- Hospital Management label --}}
                <li class="nav-item">
                    <small class="nav-subtitle"
                        title="{{ translate('Hospital Management') }}">{{ translate('Hospital Management') }}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>

                {{-- Hospital Dashboard --}}
                @if (selected_menu('hospital_dashboard') && hasPermission('hospital_manage', 'dashboard'))
                    <li class="nav-item {{ Request::is('dashboard') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.dashboard') }}"
                            title="Hospital Dashboard">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Dashboard</span>
                        </a>
                    </li>
                @endif 


                @if (!auth('vendor')->check() && vendorPlanHasModule('hospital_manage'))
                    <li class="nav-item {{ Request::is('opd*') && request('scope') === 'my' ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('vendor.opd.index', ['scope' => 'my']) }}" title="My OPD Appointments">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">My OPD
                                Appointments</span>
                        </a>
                    </li>
                @endif

                @if (selected_menu('leads_manage') &&
                        hasAnyPermission([
                            'leads_manage.list',
                            'leads_manage.add',
                            'leads_manage.statuses',
                            'leads_manage.export',
                            'leads_manage.report',
                            'leads_manage.settings',
                            'leads.list',
                            'leads.add',
                            'leads.export',
                            'leads.report',
                            'leads.settings',
                        ]) &&
                        $store_data->module->id == 6)
                    @php
                        $appointmentDefaultRoute = '#';
                        if (
                            hasAnyPermission([
                                'leads_manage.list',
                                'leads_manage.add',
                                'leads_manage.statuses',
                                'leads_manage.export',
                                'leads.list',
                                'leads.add',
                                'leads.export',
                            ])
                        ) {
                            $appointmentDefaultRoute = route('vendor.service.leads_list');
                        } elseif (hasAnyPermission(['leads_manage.report', 'leads.report'])) {
                            $appointmentDefaultRoute = route('vendor.service.report');
                        } elseif (hasPermission('leads_manage', 'settings') || hasPermission('leads', 'settings')) {
                            $appointmentDefaultRoute = route('vendor.service.lead-settings');
                        }
                    @endphp
                    <li
                        class="nav-item {{ Request::is('service/report') || Request::is('lead*') || Request::is('service/leads*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ $appointmentDefaultRoute }}"
                            title="{{ _moduleLabel('leads_manage') }}">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/leads_management_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ _moduleLabel('leads_manage') }} </span>
                        </a>
                    </li>
                @endif

                  @if (!auth('vendor')->check())
                    @php
                        $__empId = auth('vendor_employee')->id();
                        $__sid = \App\CentralLogics\Helpers::get_store_id();
                        $__isDoctor = \App\Models\DoctorProfile::where('emp_id', $__empId)->where('store_id', $__sid)->exists();
                        $__isNurse = \App\Models\NurseProfile::where('emp_id', $__empId)->where('store_id', $__sid)->exists();
                    @endphp
                    @if ($__isDoctor)
                        <li class="nav-item {{ Request::is('my-doctor-profile/patients*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.my-doctor-profile.patients') }}" title="My Patients">
                                <i class="tio-users nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">My Patients</span>
                            </a>
                        </li>
                        <li class="nav-item {{ Request::is('my-doctor-profile/edit*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.my-doctor-profile.edit') }}" title="My Profile & Slots">
                                <i class="tio-user nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">My Profile &
                                    Slots</span>
                            </a>
                        </li>
                    @endif
                    @if ($__isNurse)
                        <li class="nav-item {{ Request::is('my-nurse-profile*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.my-nurse-profile.patients') }}" title="My Patients">
                                <i class="tio-users nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">My Patients</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- Staff (free for doctors — outside hospital_manage plan gate) --}}
                @if (selected_menu('hospital_staff') && hasAnyPermission([
                        'staff_doctor.list',
                        'staff_doctor.add',
                        'staff_doctor.export',
                        'staff_nurse.list',
                        'staff_nurse.add',
                        'staff_nurse.export',
                    ]))
                    @php
                        $staffDefaultRoute = '#';
                        if (hasAnyPermission(['staff_doctor.list', 'staff_doctor.add', 'staff_doctor.export'])) {
                            $staffDefaultRoute = route('vendor.doctor.list');
                        } elseif (hasAnyPermission(['staff_nurse.list', 'staff_nurse.add', 'staff_nurse.export'])) {
                            $staffDefaultRoute = route('vendor.nurse.list');
                        }
                    @endphp
                    <li class="nav-item {{ Request::is('doctor*') || Request::is('nurse*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ $staffDefaultRoute }}"
                            title="Staff">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Patient Management_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Staff</span>
                        </a>
                    </li>
                @endif

                @if (hasMasterModulePermission('hospital_manage'))
                    {{-- Patients  --}}
                    @if (selected_menu('patient') && hasAnyPermission(['patient.add', 'patient.export', 'patient.list']))
                        <li class="nav-item {{ Request::is('patient/list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.patient.list') }}" title="Patients">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Clients_management_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Patients</span>
                            </a>
                        </li>
                    @endif

                    {{-- Documents sent out to patients, across every patient --}}
                    @if (selected_menu('patient') && hasAnyPermission(['patient_documents.list']))
                        <li class="nav-item {{ Request::is('patient/sent-documents') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.patient.sent-documents') }}" title="Sent Documents">
                                <i class="tio-send nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Sent Documents</span>
                            </a>
                        </li>
                    @endif

                    {{-- Outpatient --}}
                    {{-- Outpatient — single direct OPD Register link (only live item) --}}
                    @if (selected_menu('opd_register') && hasAnyPermission(['opd_register.list', 'opd_register.add', 'opd_register.export']))
                        <li class="nav-item {{ Request::is('opd*') && request('scope') !== 'my' ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.opd.index') }}" title="OPD Register">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/outpatient.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">OPD
                                    Register</span>
                            </a>
                        </li>
                    @endif

                    {{-- Catalogs — every priced list the hospital maintains, in one place. Each
                         entry is gated on its own feature, and the group itself only appears when
                         at least one of them is visible: a hospital without a lab should not be
                         shown an empty Catalogs menu. --}}
                    @php
                        // Route::has() as well as the permission: a module that is not installed
                        // registers no routes, and route() on a missing name throws.
                        $canTreatmentCatalog = selected_menu('opd_register') && Route::has('vendor.opd.treatment-catalog')
                            && hasPermission('opd_register', 'view');
                        $canLabCatalog       = selected_menu('lab') && Route::has('vendor.lab.catalog')
                            && hasPermission('lab_catalog', 'view');
                        $canRadiologyCatalog = selected_menu('radiology') && Route::has('vendor.radiology.catalog')
                            && hasPermission('radiology_catalog', 'view');
                    @endphp
                    @if ($canTreatmentCatalog || $canLabCatalog || $canRadiologyCatalog)
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('opd/treatment-catalog*') || Request::is('lab/catalog*') || Request::is('radiology/catalog*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                                title="Catalogs">
                                <i class="tio-library nav-link-icon" style="font-size:18px;"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    Catalogs</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                @if ($canTreatmentCatalog)
                                    <li class="navbar-vertical-aside-has-menu {{ Request::is('opd/treatment-catalog*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.opd.treatment-catalog') }}"
                                            title="What each treatment costs">
                                            <span class="tio-money nav-icon"></span>
                                            <span class="text-truncate">Treatment Catalog</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($canLabCatalog)
                                    <li class="navbar-vertical-aside-has-menu {{ Request::is('lab/catalog*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.lab.catalog') }}"
                                            title="Lab tests, their parameters and prices">
                                            <span class="tio-test-tube nav-icon"></span>
                                            <span class="text-truncate">Test Catalog</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($canRadiologyCatalog)
                                    <li class="navbar-vertical-aside-has-menu {{ Request::is('radiology/catalog*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('vendor.radiology.catalog') }}"
                                            title="Radiology studies and their prices">
                                            <span class="tio-scanner nav-icon"></span>
                                            <span class="text-truncate">Radiology Catalog</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if (selected_menu('inpatient') && hasAnyPermission([
                            'ipd_admission.list',
                            'ipd_admission.add',
                            'ipd_admission.export',
                            'ward.list',
                            'ward.add',
                            'ward.edit',
                            'ward.delete',
                            'bed.list',
                        ]))
                        @php
                            $inpatientDefaultRoute = '#';
                            if (hasAnyPermission(['ipd_admission.list', 'ipd_admission.add', 'ipd_admission.export'])) {
                                $inpatientDefaultRoute = route('vendor.ipd.index');
                            } elseif (
                                hasAnyPermission(['ward.list', 'ward.add', 'ward.edit', 'ward.delete', 'bed.list'])
                            ) {
                                $inpatientDefaultRoute = route('vendor.ward.index');
                            }
                        @endphp
                        <li
                            class="nav-item {{ Request::is('ipd*') || Request::is('ward*') || Request::is('bed*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ $inpatientDefaultRoute }}"
                                title="Inpatient">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/inpatient.png') }}"
                                    alt="" class="nav-link-icon">
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Inpatient</span>
                            </a>
                        </li>
                    @endif
                    {{-- =============================== PHARMACY/INVENTORY Management =========================== --}}
                    @if (selected_menu('inventory_manage') &&
                        (hasMasterModulePermission('inventory_manage') ||
                            hasPermission('pharmacy_dispense_queue', 'list') ||
                            hasAnyPermission(['pharmacy.list', 'pharmacy.add'])))
                        @php
                            // Land on the free Medicines & Stock page by default; the pharmacy header
                            // tabs provide access to Dispense Queue, Dashboard and the paid modules.
                            $pharmacyDefaultRoute = Route::has('vendor.pharmacy.medicines')
                                ? route('vendor.pharmacy.medicines')
                                : route('vendor.inventory.dashboard');
                        @endphp
                        <li
                            {{-- 'prescription/dispense*' intentionally omitted — the Dispense Queue
                                 item below owns that route, otherwise both highlight at once. --}}
                            class="nav-item  {{ Request::is('pharmacy*') || Request::is('inventory*') || Request::is('billing/purchase-bills*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ $pharmacyDefaultRoute }}" title="Pharmacy">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Inventory_management_color.png') }}"
                                    alt="" class="nav-link-icon">
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Pharmacy</span>
                            </a>
                        </li>
                    @endif

                    {{-- Dispense Queue — its own entry. Prescriptions are written on the clinical
                         screens (OPD / Patient), so the pharmacy header tab alone is easy to miss. --}}
                    @if (selected_menu('pharmacy_dispense') && Route::has('vendor.prescription.dispense.queue') && hasPermission('pharmacy_dispense_queue', 'list'))
                        <li class="nav-item {{ Request::is('prescription/dispense*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.prescription.dispense.queue') }}" title="Dispense Queue">
                                <span class="nav-link-icon"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:1.5rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M10.5 20.5 3.5 13.5a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7Z" />
                                        <path d="m8.5 8.5 7 7" />
                                    </svg>
                                </span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Dispense Queue</span>
                            </a>
                        </li>
                    @endif

                    {{-- Laboratory — bundled with Hospital Management --}}
                    @if (selected_menu('lab') && Route::has('vendor.lab.home') &&
                            (auth('vendor')->check() ||
                                hasAnyPermission([
                                    'lab_worklist.view',
                                    'lab_result.view',
                                    'lab_report.view',
                                    'lab_critical.view',
                                    'lab_order.view',
                                    'lab_reagent.view',
                                    'lab_history.view',
                                    'lab_billing.view',
                                    'lab_catalog.view',
                                ])))
                        <li class="nav-item {{ Request::is('lab*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.lab.home') }}" title="Laboratory">
                                <span class="nav-link-icon"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:1.5rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M9 3h6M10 3v6.5L5.5 17a2 2 0 0 0 1.8 3h9.4a2 2 0 0 0 1.8-3L14 9.5V3" />
                                        <path d="M7.5 14h9" />
                                    </svg>
                                </span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Laboratory</span>
                            </a>
                        </li>
                    @endif

                    {{-- Nursing Station — ward workstation, bundled with Hospital Management --}}
                    @if (selected_menu('nursing') && Route::has('vendor.nursing.index') &&
                            (auth('vendor')->check() ||
                                hasAnyPermission([
                                    'nursing_vitals.view',
                                    'nursing_mar.view',
                                    'nursing_fluid.view',
                                    'nursing_note.view',
                                    'nursing_task.view',
                                    'nursing_handover.view',
                                ])))
                        <li class="nav-item {{ Request::is('nursing*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.nursing.index') }}" title="Nursing Station">
                                <span class="nav-link-icon"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:1.5rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 21h18M5 21V7l7-4 7 4v14" />
                                        <path d="M10 12h4M12 10v4" />
                                    </svg>
                                </span>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Nursing
                                    Station</span>
                            </a>
                        </li>
                    @endif

                    {{-- Pre-Op Preparation — surgical prep, bundled with Hospital Management --}}
                    @if (selected_menu('preop') && Route::has('vendor.preop.index') &&
                            (auth('vendor')->check() ||
                                hasAnyPermission([
                                    'preop_schedule.view',
                                    'preop_case.view',
                                    'preop_checklist.view',
                                    'preop_med.view',
                                    'preop_consent.view',
                                    'preop_clearance.view',
                                    'preop_anaesthesia.view',
                                    'preop_result.view',
                                    'preop_blood.view',
                                    'preop_handover.view',
                                ])))
                        <li class="nav-item {{ Request::is('preop*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.preop.index') }}" title="Pre-Op Preparation">
                                <span class="nav-link-icon"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:1.5rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                        <path d="M5 8V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2" />
                                        <path d="M9 14h6M12 11v6" />
                                    </svg>
                                </span>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Pre-Op
                                    Prep</span>
                            </a>
                        </li>
                    @endif

                    {{-- Radiology — imaging department, bundled with Hospital Management --}}
                    @if (selected_menu('radiology') && Route::has('vendor.radiology.home') &&
                            (auth('vendor')->check() ||
                                hasAnyPermission([
                                    'radiology_study.view',
                                    'radiology_viewer.view',
                                    'radiology_report.view',
                                    'radiology_urgent.view',
                                    'radiology_schedule.view',
                                    'radiology_equipment.view',
                                    'radiology_billing.view',
                                    'radiology_catalog.view',
                                ])))
                        <li class="nav-item {{ Request::is('radiology*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.radiology.home') }}" title="Radiology">
                                <span class="nav-link-icon"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:1.5rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 3v18M3 12h18M7 7l10 10M17 7L7 17" />
                                    </svg>
                                </span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Radiology</span>
                            </a>
                        </li>
                    @endif

                    @if (selected_menu('forms_and_consents') && hasAnyPermission(['consent_form.list', 'consent_form.add', 'consent_template.list', 'consent_template.add']))
                        @php
                            $consentDefaultRoute = '#';
                            if (hasAnyPermission(['consent_form.list', 'consent_form.add'])) {
                                $consentDefaultRoute = route('vendor.consent.index');
                            } elseif (hasAnyPermission(['consent_template.list', 'consent_template.add'])) {
                                $consentDefaultRoute = route('vendor.consent.template.index');
                            }
                        @endphp
                        <li class="nav-item {{ Request::is('consent*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ $consentDefaultRoute }}"
                                title="Forms & Consents">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Documents_color.png') }}"
                                    alt="" class="nav-link-icon"> <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Forms &
                                    Consents</span>
                            </a>
                        </li>
                    @endif

                    @if (selected_menu('hospital_settings') && hasPermission('hospital_manage', 'settings'))
                        {{-- Hospital Settings --}}
                        <li class="nav-item {{ Request::is('hospital/settings') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.hospital.settings') }}" title="Hospital Settings">
                                <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/settings.png') }}"
                                    alt="" class="nav-link-icon">
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Hospital
                                    Settings</span>
                            </a>
                        </li>
                    @endif

                @endif

                {{-- Other label --}}
                <li class="nav-item">
                    <small class="nav-subtitle" title="{{ translate('Other') }}">{{ translate('Other') }}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>


                {{-- ===================================== inventory END========================== --}}

                {{-- ===================================== BILLING ========================== --}}

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
                                        title="{{ translate('messages.Bill') }}">
                                        <span class="tio-coin nav-icon"></span>
                                        <span class="text-truncate">Bills</span>
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
                <!-- Dashboards -->
                {{-- @if (auth('vendor')->check()) --}}
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('store-panel') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('vendor.master-dashboard') }}" title="{{ translate('messages.dashboard') }}">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Dashboard_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                               {{auth('vendor')->check() ? 'Master' : ''}} {{ translate('messages.dashboard') }}
                            </span> 
                        </a>
                    </li>
                {{-- @endif --}}


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


                {{-- =============================== TASK Management=========================== --}}

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
                {{-- =============================== PROJECT Management=========================== --}}
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
                {{-- =============================== ORDER Management=========================== --}}
                @if (0 && selected_menu('order_manage') && \App\CentralLogics\Helpers::employee_module_permission_check('order_manage'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('order*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                            title="Orders">
                            <i class="tio-money nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Orders
                            </span>
                        </a>

                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display: {{ Request::is('order/add') ? 'block' : 'none' }}">
                            <li class="nav-item {{ Request::is('order/dashboard') ? 'active' : '' }}">
                                <a class="nav-link " href="#" title=" Project dashboard">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Order Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/add') ? 'active' : '' }}">
                                <a class="nav-link " href="#"
                                    title="{{ translate('messages.add') }} {{ translate('messages.new') }} Project">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.add') }}
                                        {{ translate('messages.new') }} Order</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('order/list') ? 'active' : '' }}">
                                <a class="nav-link " href="#" title="list order">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Order List</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    {{-- ============= SHOP ORDERS ============================== --}}
                    @if (
                        \App\CentralLogics\Helpers::employee_module_permission_check('order') &&
                            $store_data->module->module_type == 'ecommerce')
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.order_section') }}">{{ translate('messages.order_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <!-- Order -->
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('order*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.orders') }}">
                                <i class="tio-shopping-cart nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.orders') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('order*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('order/list/all') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.order.list', ['all']) }}"
                                        title="{{ translate('messages.all_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.all') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ \App\Models\Order::where('store_id', \App\CentralLogics\Helpers::get_store_id())->where(function ($query) {
                                                        return $query->whereNotIn(
                                                                'order_status',
                                                                config('order_confirmation_model') == 'store' ||
                                                                \App\CentralLogics\Helpers::get_store_data()->self_delivery_system
                                                                    ? ['failed', 'canceled', 'refund_requested', 'refunded']
                                                                    : ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'],
                                                            )->orWhere(function ($query) {
                                                                return $query->where('order_status', 'pending')->where('order_type', 'take_away');
                                                            });
                                                    })->StoreOrder()->NotDigitalOrder()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('order/list/pending') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.order.list', ['pending']) }}"
                                        title="{{ translate('messages.pending_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.pending') }}
                                            {{ config('order_confirmation_model') == 'store' || \App\CentralLogics\Helpers::get_store_data()->self_delivery_system ? '' : translate('messages.take_away') }}
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                @if (config('order_confirmation_model') == 'store' || \App\CentralLogics\Helpers::get_store_data()->self_delivery_system)
                                                    {{ \App\Models\Order::where(['order_status' => 'pending', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->OrderScheduledIn(30)->NotDigitalOrder()->count() }}
                                                @else
                                                    {{ \App\Models\Order::where(['order_status' => 'pending', 'store_id' => \App\CentralLogics\Helpers::get_store_id(), 'order_type' => 'take_away'])->StoreOrder()->OrderScheduledIn(30)->NotDigitalOrder()->count() }}
                                                @endif
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li class="nav-item {{ Request::is('order/list/confirmed') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.order.list', ['confirmed']) }}"
                                        title="{{ translate('messages.confirmed_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.confirmed') }}
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                {{ \App\Models\Order::whereIn('order_status', ['confirmed', 'accepted'])->StoreOrder()->whereNotNull('confirmed')->where('store_id', \App\CentralLogics\Helpers::get_store_id())->OrderScheduledIn(30)->NotDigitalOrder()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li class="nav-item {{ Request::is('order/list/cooking') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.order.list', ['cooking']) }}"
                                        title="{{ translate('messages.processing_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            @if ($store_data->module->module_type == 'food')
                                                {{ translate('messages.cooking') }}
                                            @else
                                                {{ translate('messages.processing') }}
                                            @endif
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ \App\Models\Order::where(['order_status' => 'processing', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('order/list/ready_for_delivery') ? 'active' : '' }}">
                                    <a class="nav-link"
                                        href="{{ route('vendor.order.list', ['ready_for_delivery']) }}"
                                        title="{{ translate('messages.ready_for_delivery') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.ready_for_delivery') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ \App\Models\Order::where(['order_status' => 'handover', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('order/list/item_on_the_way') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.order.list', ['item_on_the_way']) }}"
                                        title="{{ translate('messages.items_on_the_way') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.item_on_the_way') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ \App\Models\Order::where(['order_status' => 'picked_up', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('order/list/delivered') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.order.list', ['delivered']) }}"
                                        title="{{ translate('messages.delivered_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.delivered') }}
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                {{ \App\Models\Order::where(['order_status' => 'delivered', 'store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('order/list/refunded') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.order.list', ['refunded']) }}"
                                        title="{{ translate('messages.refunded_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.refunded') }}
                                            <span class="badge badge-soft-danger bg-light badge-pill ml-1">
                                                {{ \App\Models\Order::Refunded()->where(['store_id' => \App\CentralLogics\Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('order/list/scheduled') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.order.list', ['scheduled']) }}"
                                        title="{{ translate('messages.scheduled_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.scheduled') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ \App\Models\Order::where('store_id', \App\CentralLogics\Helpers::get_store_id())->StoreOrder()->Scheduled()->where(function ($q) {
                                                        if (
                                                            config('order_confirmation_model') == 'store' ||
                                                            \App\CentralLogics\Helpers::get_store_data()->self_delivery_system
                                                        ) {
                                                            $q->whereNotIn('order_status', ['failed', 'canceled', 'refund_requested', 'refunded']);
                                                        } else {
                                                            $q->whereNotIn('order_status', ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])->orWhere(
                                                                function ($query) {
                                                                    $query->where('order_status', 'pending')->where('order_type', 'take_away');
                                                                },
                                                            );
                                                        }
                                                    })->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- End Order -->
                    @endif
                    {{-- ============= SHOP ORDERS END ============================== --}}

                @endif
                {{-- =============================== SUPPLIERS =========================== --}}
                {{-- A hospital's clients ARE its patients — the same person, one record, kept in
                     step by PatientCustomerLink. So this menu is not a second people list here;
                     what is left of it is labs (for lab work) and the supplier side (pharmacy and
                     consumables purchasing), which patients never cover. --}}
                @if (
                    (auth('vendor')->check() && selected_menu('client_manage') && hasMasterModulePermission('client_manage')) ||
                        (auth('vendor_employee')->check() && hasMasterModulePermission('client_manage')))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('client*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                            title="Lab & Suppliers">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Clients_management_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Lab & Suppliers
                            </span>
                        </a>

                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display: {{ Request::is('client*') ? 'block' : 'none' }}">
                            @if (hasPermission('client_manage', 'add'))
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('customer/add') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.customer.add', ['user_type' => 'vendor']) }}"
                                        title="Add New Lab / Supplier">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Add New Lab / Supplier</span>
                                    </a>
                                </li>
                            @endif
                            @if (hasAnyPermission(['client_manage.list', 'client_manage.import', 'client_manage.export']))
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('client/list') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.customer.list', ['type' => 'vendor']) }}"
                                        title="Lab & Suppliers List">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class=" text-truncate">Lab & Suppliers List</span>
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
                                <a class="nav-link" href="#" title="{{ translate('messages.Feedbacks') }}">
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
                {{-- =============================== QUOTATION Management=========================== --}}
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
                {{-- =============================== POS Management=========================== --}}
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
                                    <a class="nav-link " href="{{ route('vendor.pos.token') }}" title="POS Token">
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
                                    <a class="nav-link " href="{{ route('vendor.pos.items') }}" title="POS Items">
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
                {{-- =============================== ACCOUNT Management=========================== --}}
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
                                                    <a class="nav-link " href="{{ route('vendor.account.add') }}"
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
                                                    <li class="nav-item {{ Request::is('asset') ? 'active' : '' }}">
                                                        <a class=" nav-link"
                                                            href="{{ route('vendor.asset.index') }}" title="Assets">
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

                {{-- =============================== Basic Staff (free, shown when HR not subscribed) --}}
                @if (selected_menu('staff_manage') && auth('vendor')->check() && !\App\CentralLogics\Helpers::permission_check('hr_manage'))
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
                            <li class="nav-item {{ Request::is('basic-staff') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.basic-staff.index') }}"
                                    title="Staff List">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Staff List</span>
                                </a>
                            </li>
                            <li
                                class="nav-item {{ Request::is('basic-staff/create') || Request::is('basic-staff/edit/*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.basic-staff.create') }}"
                                    title="Add Staff">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Add Staff</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('basic-staff/roles*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.basic-staff.roles') }}" title="Roles">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Roles &amp; Permissions</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- =============================== HR Management=========================== --}}
                @if (
                    (selected_menu('staff_manage') ||
                        selected_menu('attendance_manage') ||
                        selected_menu('leave_manage') ||
                        selected_menu('salary_manage')) &&
                        hasMasterModulePermission('hr_manage'))
                    <li class="navbar-vertical-aside {{ Request::is('hr*') || Request::is('task-salary-categories') || Request::is('shifts*') || Request::is('custom-role*') || Request::is('staff*') || Request::is('salary*') || Request::is('leave*') || Request::is('attendance*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.hr.dashboard') }}" title="HR Management">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/HR_management_color.png') }}" alt="" class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">HR Management</span>
                        </a>
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
                @if (0 && selected_menu('smart_calendar'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('smart-calendar*') ? 'active' : '' }}">
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
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('salary-history*') ? 'active' : '' }}">
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
                @endif


                @if (in_array($store_data->module->module_type, ['ecommerce']))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('item/flash-sale*') ? 'active' : '' }}">
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
                            href="{{ route('vendor.addon.add-new') }}" title="{{ translate('messages.addons') }}">
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
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            title="{{ translate('messages.items') }}">
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
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            title="{{ translate('messages.categories') }}">
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

                            <li class="nav-item {{ Request::is('category/sub-category-list') ? 'active' : '' }}">
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
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            title="{{ translate('messages.campaigns') }}">
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
                                    <span class="text-truncate">{{ translate('messages.basic_campaigns') }}</span>
                                </a>
                            </li>
                            <li class="nav-item {{ Request::is('campaign/item/list') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('vendor.campaign.itemlist') }}"
                                    title="{{ translate('messages.Item Campaigns') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">{{ translate('messages.Item Campaigns') }}</span>
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
                <!-- Business Section-->

                @if (0 && selected_menu('reports') && \App\CentralLogics\Helpers::employee_module_permission_check('reports'))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/reports2*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
                            title="Reports">
                            <img src="{{ asset('storage/app/public/nav/report.png') }}" alt=""
                                class="nav-link-icon">
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                Reports</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                            <li class="nav-item {{ Request::is('business-settings/reports2') ? 'active' : '' }}"
                                style="margin-top:0 !important;">
                                <a class="nav-link " href="javascript:;" title="Coming Soon">
                                    <span class="tio-settings nav-icon"></span>
                                    <span class="text-truncate">Coming Soon</span>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endif



                <!-- End StoreWallet -->
                @if ($store_data->module->id == 5 && \App\CentralLogics\Helpers::employee_module_permission_check('reviews'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('reviews') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('vendor.reviews') }}" title="{{ translate('messages.reviews') }}">
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
                            href="{{ route('vendor.message.list') }}" title="{{ translate('messages.chat') }}">
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






                @if (selected_menu('library') && \App\CentralLogics\Helpers::employee_module_permission_check('library'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('library*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('vendor.library.all') }}" title="Library">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Library_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class=" text-truncate">Library</span>
                        </a>
                    </li>
                @endif


                @if (selected_menu('post_ads') && \App\CentralLogics\Helpers::employee_module_permission_check('post_ads'))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('notification*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link "
                            href="{{ route('vendor.notification.add-new') }}" title="Post Ads">
                            <img src="{{ asset('storage/app/public/uploaded/sidebar_icons/Advertisements_color.png') }}"
                                alt="" class="nav-link-icon">
                            <span class="text-truncate">Post Ads</span>
                        </a>
                    </li>
                @endif
                {{-- WhatsApp — right after Post Ads --}}
                @include('layouts.vendor.partials._sidebar_menu_whatsapp')
                @include('layouts.vendor.partials._sidebar_menu_notifications')
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
                                <li
                                    class="nav-item  {{ Request::is('settings/settings/webpage') ? 'active' : '' }}">
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

                        </ul>
                    </li>
                @endif

                @if (selected_menu('subscriptions') && \App\CentralLogics\Helpers::employee_module_permission_check('subscriptions'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('subscriptions') ? 'active' : '' }}">
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
    border-radius: 10px; align-items: center;background-color: color-mix(in srgb, #ffffff 15%, transparent);">
                        <label class="switch toggle-switch-lg m-0">
                            <input type="checkbox" class="toggle-switch-input keep-minimized"
                                {{ _isMenuMinimized() ? 'checked' : '' }} value = '1'>
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                        </label>
                        <span class="text-white pl-2" style="font-size: 14px;">Keep Menu <br> Minimized</span>
                    </div>
                </li>

                <li class="nav-item pt-5 pb-2" style="text-align: center;">

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
