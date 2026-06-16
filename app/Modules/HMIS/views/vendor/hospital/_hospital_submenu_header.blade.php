@php
    $isAppointmentModule = Request::is('*service/report*') || Request::is('*lead*') || Request::is('*service/leads*') || Request::is('*appointment*');
    $isStaffModule = Request::is('*doctor*') || Request::is('*nurse*') || Request::is('*my-doctor-profile*');
    $isInpatientModule = Request::is('*ipd*') || Request::is('*ward*') || Request::is('*bed*');
    $isConsentModule = Request::is('*consent*');

    $storeId = \App\CentralLogics\Helpers::get_store_id();

    // 1. Queries for Appointment Management
    if ($isAppointmentModule) {
        $todayAppointments = \App\Models\Appointment::where('store_id', $storeId)
            ->whereDate('appointment_date', today())
            ->get();
        $appointmentsTodayCount = $todayAppointments->count();
        $scheduledTodayCount = $todayAppointments->where('status', 'scheduled')->count();
        $completedTodayCount = $todayAppointments->where('status', 'completed')->count();
        $consultingTodayCount = $todayAppointments->where('status', 'consulting')->count();
        $checkedInTodayCount = $todayAppointments->where('status', 'checked_in')->count();
        $walkInTodayCount = $todayAppointments->where('booking_type', 'walk_in')->count();
        $onlineTodayCount = $todayAppointments->where('booking_type', 'online')->count();

        $totalLeadsCount = \DB::table('service_requests')
            ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
            ->count();
        $newLeadsTodayCount = \DB::table('service_requests')
            ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
            ->whereDate('created_at', today())
            ->count();
 
        $cancelledTodayCount = $todayAppointments->whereIn('status', ['cancelled', 'no_show'])->count();
    }

    // 2. Queries for Staff
    if ($isStaffModule) {
        $totalDoctorsCount = \App\Models\DoctorProfile::where('store_id', $storeId)->count();
        $totalNursesCount = \App\Models\NurseProfile::where('store_id', $storeId)->count();
        $activeSlotsCount = \App\Models\DoctorSlot::whereHas('doctor', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            }) 
            ->where('is_active', true) 
            ->count(); 
        $dayShiftNurses = \App\Models\NurseProfile::where('store_id', $storeId)->where('shift', 'day')->count();
        $nightShiftNurses = \App\Models\NurseProfile::where('store_id', $storeId)->where('shift', 'night')->count();
        $totalDoctorServices = \DB::table('doctor_services')
            ->join('doctor_profiles', 'doctor_services.doctor_profile_id', '=', 'doctor_profiles.id')
            ->where('doctor_profiles.store_id', $storeId)
            ->count(); 
    }

    // 3. Queries for Inpatient
    if ($isInpatientModule) {
        $activeIpdCount = \App\Models\IpdAdmission::where('store_id', $storeId)
            ->where('status', 'admitted')
            ->count();
        $dischargedTodayCount = \App\Models\IpdAdmission::where('store_id', $storeId)
            ->where('status', 'discharged')
            ->whereDate('discharge_date', today())
            ->count();
        $activeWardsCount = \App\Models\Ward::where('store_id', $storeId)
            ->where('is_active', true)
            ->count();

        $bedsQuery = \App\Models\Bed::where('store_id', $storeId)->get();
        $totalBedsCount = $bedsQuery->count();
        $availableBedsCount = $bedsQuery->where('status', 'available')->count();
        $occupiedBedsCount = $bedsQuery->where('status', 'occupied')->count();
    }

    // 4. Queries for Consents
    if ($isConsentModule) {
        $signedTodayCount = \App\Models\PatientConsent::where('store_id', $storeId)
            ->whereDate('signed_at', today())
            ->count();
        $signedThisMonthCount = \App\Models\PatientConsent::where('store_id', $storeId)
            ->whereMonth('signed_at', today()->month)
            ->whereYear('signed_at', today()->year)
            ->count();
        $totalSignedCount = \App\Models\PatientConsent::where('store_id', $storeId)->count();
        $activeTemplatesCount = \App\Models\ConsentTemplate::where('store_id', $storeId)
            ->where('is_active', true)
            ->count();
        $totalTemplatesCount = \App\Models\ConsentTemplate::where('store_id', $storeId)->count();
        $inactiveTemplatesCount = $totalTemplatesCount - $activeTemplatesCount;
    }
@endphp

@push('css_or_js')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Metrics Grid ── */
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
            gap: 10px;
        }
        .metric-card {
            padding: 8px 12px;
            border-right: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .metric-card:last-child {
            border-right: none;
        }
        .metric-card .value {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            font-family: 'Outfit', sans-serif;
            line-height: 1.2;
            display: flex;
            align-items: baseline;
            gap: 2px;
        }
        .metric-card .subtext {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        /* ── Tabs Grid ── */
        .hospital-tabs-row {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 24px;  
            display: flex;
            gap: 6px;
            overflow: visible !important;
            margin-bottom: 20px;
        } 
        .hospital-tab-btn { 
            font-family: 'Inter', sans-serif !important;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            white-space: nowrap;
            text-decoration: none !important;
        }
        .hospital-tab-btn:hover {
            color: #0f172a;
            background-color: #f8fafc;
        }
        .hospital-tab-btn.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
    </style>
@endpush

@if($isAppointmentModule)
    <div class="metrics-row">
        <div class="metric-card">
            <div class="value" style="color: #2563eb;">{{ $appointmentsTodayCount }}</div>
            <div class="subtext">QUEUE TODAY<br><span style="color: #3b82f6; font-weight: 700;">{{ $scheduledTodayCount }} pending</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #10b981;">{{ $completedTodayCount }}</div>
            <div class="subtext">COMPLETED TODAY<br><span style="color: #16a34a; font-weight: 700;">{{ $consultingTodayCount }} consulting</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #8b5cf6;">{{ $checkedInTodayCount }}</div>
            <div class="subtext">WAITING ROOM<br><span style="color: #7c3aed; font-weight: 700;">In clinic</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ea580c;">{{ $walkInTodayCount }}</div>
            <div class="subtext">WALK-IN BOOKINGS<br><span style="color: #ea580c; font-weight: 700;">Direct visits</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #06b6d4;">{{ $newLeadsTodayCount }}</div>
            <div class="subtext">NEW LEADS TODAY<br><span style="color: #0891b2; font-weight: 700;">{{ $totalLeadsCount }} total leads</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ef4444;">{{ $cancelledTodayCount }}</div>
            <div class="subtext">CANCELLED / NO-SHOW<br><span style="color: #dc2626; font-weight: 700;">Today</span></div>
        </div>
    </div>
    <div class="hospital-tabs-row">
        @if(hasAnyPermission(['leads_manage.list', 'leads_manage.add', 'leads_manage.statuses', 'leads_manage.export', 'leads.list', 'leads.add', 'leads.export']))
            <a href="{{ route('vendor.service.leads_list') }}" class="hospital-tab-btn {{ (Request::is('*service/leads*') || Request::is('*lead*') || Request::is('*appointment*')) ? 'active' : '' }}">
                Appointments
            </a>
        @endif
        @if(hasAnyPermission(['leads_manage.report', 'leads.report']))
            <a href="{{ route('vendor.service.report') }}" class="hospital-tab-btn {{ Request::is('*service/report*') ? 'active' : '' }}">
                Appointments Report
            </a>
        @endif
        @if(hasPermission('leads_manage', 'settings') || hasPermission('leads', 'settings'))
            <a href="{{ route('vendor.service.lead-settings') }}" class="hospital-tab-btn {{ Request::is('*service/lead-settings*') ? 'active' : '' }}">
                Appointment Settings
            </a>
        @endif
    </div>
@elseif($isStaffModule)
    <div class="metrics-row">
        <div class="metric-card">
            <div class="value" style="color: #2563eb;">{{ $totalDoctorsCount }}</div>
            <div class="subtext">TOTAL DOCTORS<br><span style="color: #3b82f6; font-weight: 700;">Active profiles</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #10b981;">{{ $totalNursesCount }}</div>
            <div class="subtext">TOTAL NURSES<br><span style="color: #16a34a; font-weight: 700;">On duty</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ea580c;">{{ $activeSlotsCount }}</div>
            <div class="subtext">ACTIVE SLOTS<br><span style="color: #ea580c; font-weight: 700;">Appointment slots</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #8b5cf6;">{{ $dayShiftNurses }}</div>
            <div class="subtext">DAY SHIFT NURSES<br><span style="color: #7c3aed; font-weight: 700;">Active shift</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ef4444;">{{ $nightShiftNurses }}</div>
            <div class="subtext">NIGHT SHIFT NURSES<br><span style="color: #dc2626; font-weight: 700;">On call</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #06b6d4;">{{ $totalDoctorServices }}</div>
            <div class="subtext">OFFERED SERVICES<br><span style="color: #0891b2; font-weight: 700;">Clinical offerings</span></div>
        </div>
    </div>
    <div class="hospital-tabs-row">
        @if(hasAnyPermission(['staff_doctor.list', 'staff_doctor.add', 'staff_doctor.export']))
            <a href="{{ route('vendor.doctor.list') }}" class="hospital-tab-btn {{ (Request::is('*doctor*') || Request::is('*my-doctor-profile*')) ? 'active' : '' }}">
                Doctors
            </a>
        @endif
        @if(hasAnyPermission(['staff_nurse.list', 'staff_nurse.add', 'staff_nurse.export']))
            <a href="{{ route('vendor.nurse.list') }}" class="hospital-tab-btn {{ Request::is('*nurse*') ? 'active' : '' }}">
                Nurses
            </a>
        @endif
    </div>
@elseif($isInpatientModule)
    <div class="metrics-row">
        <div class="metric-card">
            <div class="value" style="color: #2563eb;">{{ $activeIpdCount }}</div>
            <div class="subtext">ADMITTED (IPD)<br><span style="color: #3b82f6; font-weight: 700;">Currently in-ward</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #10b981;">{{ $dischargedTodayCount }}</div>
            <div class="subtext">DISCHARGED TODAY<br><span style="color: #16a34a; font-weight: 700;">Completed stays</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ea580c;">{{ $availableBedsCount }}</div>
            <div class="subtext">AVAILABLE BEDS<br><span style="color: #ea580c; font-weight: 700;">Ready for admission</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ef4444;">{{ $occupiedBedsCount }}</div>
            <div class="subtext">OCCUPIED BEDS<br><span style="color: #dc2626; font-weight: 700;">Beds in use</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #8b5cf6;">{{ $totalBedsCount }}</div>
            <div class="subtext">TOTAL HOSPITAL BEDS<br><span style="color: #7c3aed; font-weight: 700;">Bed capacity</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #06b6d4;">{{ $activeWardsCount }}</div>
            <div class="subtext">ACTIVE WARDS<br><span style="color: #0891b2; font-weight: 700;">Operational wards</span></div>
        </div>
    </div>
    <div class="hospital-tabs-row">
        @if(hasAnyPermission(['ipd_admission.list', 'ipd_admission.add', 'ipd_admission.export']))
            <a href="{{ route('vendor.ipd.index') }}" class="hospital-tab-btn {{ Request::is('*ipd*') ? 'active' : '' }}">
                IPD Admissions
            </a>
        @endif
        @if(hasAnyPermission(['ward.list', 'ward.add', 'ward.edit', 'ward.delete', 'bed.list']))
            <a href="{{ route('vendor.ward.index') }}" class="hospital-tab-btn {{ (Request::is('*ward*') || Request::is('*bed*')) ? 'active' : '' }}">
                Wards & Beds
            </a>
        @endif
    </div>
@elseif($isConsentModule)
    <div class="metrics-row">
        <div class="metric-card">
            <div class="value" style="color: #2563eb;">{{ $signedTodayCount }}</div>
            <div class="subtext">SIGNED TODAY<br><span style="color: #3b82f6; font-weight: 700;">Forms signed today</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #10b981;">{{ $signedThisMonthCount }}</div>
            <div class="subtext">SIGNED THIS MONTH<br><span style="color: #16a34a; font-weight: 700;">Current cycle</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #8b5cf6;">{{ $totalSignedCount }}</div>
            <div class="subtext">TOTAL SIGNED FORMS<br><span style="color: #7c3aed; font-weight: 700;">All-time records</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ea580c;">{{ $activeTemplatesCount }}</div>
            <div class="subtext">ACTIVE TEMPLATES<br><span style="color: #ea580c; font-weight: 700;">Ready templates</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #06b6d4;">{{ $totalTemplatesCount }}</div>
            <div class="subtext">CONSENT TEMPLATES<br><span style="color: #0891b2; font-weight: 700;">Total structures</span></div>
        </div>
        <div class="metric-card">
            <div class="value" style="color: #ef4444;">{{ $inactiveTemplatesCount }}</div>
            <div class="subtext">INACTIVE TEMPLATES<br><span style="color: #dc2626; font-weight: 700;">Draft or deprecated</span></div>
        </div>
    </div>
    <div class="hospital-tabs-row">
        @if(hasAnyPermission(['consent_form.list', 'consent_form.add']))
            <a href="{{ route('vendor.consent.index') }}" class="hospital-tab-btn {{ (Request::is('*consent*') && !Request::is('*consent/template*')) ? 'active' : '' }}">
                Consent Forms
            </a>
        @endif
        @if(hasAnyPermission(['consent_template.list', 'consent_template.add']))
            <a href="{{ route('vendor.consent.template.index') }}" class="hospital-tab-btn {{ Request::is('*consent/template*') ? 'active' : '' }}">
                Consent Templates
            </a>
        @endif
    </div>
@endif
