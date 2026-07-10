{{-- ===================== SCHOOL MODULE MENU ===================== --}}
@if (hasMasterModulePermission('school_manage'))

    @php
        // Per-feature visibility (permission + selected menu)
        $vAcademic     = selected_menu('academic_setup') && hasAnyPermission(['academic_setup.view', 'academic_setup.add', 'academic_setup.edit']);
        $vTimetable    = selected_menu('timetable') && hasAnyPermission(['timetable.view', 'timetable.add', 'timetable.edit']);
        $vExams        = selected_menu('exams') && hasAnyPermission(['exams.view', 'exams.add', 'exams.enter_marks']);
        $vQuestionBank = selected_menu('exams') && hasAnyPermission(['question_bank.view', 'question_bank.add']);
        $vHomework     = selected_menu('homework') && hasAnyPermission(['homework.view', 'homework.add', 'homework.edit', 'homework.evaluate']);
        $vStudents     = selected_menu('students') && hasAnyPermission(['students.view', 'students.add', 'students.edit']);
        $vAdmissions   = selected_menu('admissions') && hasAnyPermission(['admissions.view', 'admissions.add', 'admissions.edit']);
        $vPromotion    = hasAnyPermission(['student_promotion.view', 'student_promotion.promote']);
        $vAttendance   = selected_menu('student_attendance') && hasAnyPermission(['student_attendance.view', 'student_attendance.add', 'student_leave.view', 'student_leave.add', 'student_leave.approve', 'student_leave.reject', 'student_leave.delete', 'short_leave.view', 'short_leave.add', 'short_leave.return', 'short_leave.delete']);
        $vCertificates = selected_menu('certificates') && hasAnyPermission(['certificates.view', 'certificates.add', 'certificates.edit']);
        $vFees         = selected_menu('fees') && hasAnyPermission(['fee_dues.view', 'fee_collection.view', 'fee_collection.collect', 'fee_heads.view', 'fee_structure.view', 'scholarship.view']);
        $vTransport    = selected_menu('transport') && hasAnyPermission(['transport.view', 'transport.add', 'transport.edit']);
        $vHostel       = selected_menu('hostel') && hasAnyPermission(['hostel.view', 'hostel.add', 'hostel.edit']);
        $vNotices      = hasAnyPermission(['notices.view', 'notices.add', 'notices.edit']);
        $vReports      = hasAnyPermission(['school_reports.view']);
        $vSettings     = hasAnyPermission(['school_settings.view', 'school_settings.edit']);
 
        // Group visibility
        $grpAcademics  = $vAcademic || $vTimetable || $vExams || $vQuestionBank || $vHomework;
        $grpStudents   = $vStudents || $vAdmissions || $vPromotion || $vAttendance || $vCertificates;
        $grpFacilities = $vTransport || $vHostel;
        $grpGeneral    = $vNotices || $vReports || $vSettings;

        // Per-feature "is current page" flags
        $ttPeriods = Request::is('school/timetable/periods*');
        $ttTeacher = Request::is('school/timetable/teacher*');
        $ttSubs    = Request::is('school/timetable/substitutions*');
        $ttOpen    = Request::is('school/timetable*');
        $ttClass   = $ttOpen && !$ttPeriods && !$ttTeacher && !$ttSubs;

        $stuImport   = Request::is('school/students/import*');
        $stuSettings = Request::is('school/students/settings*');
        $stuOpen     = Request::is('school/students*');
        $stuList     = $stuOpen && !$stuImport && !$stuSettings;

        $attReport   = Request::is('school/student-attendance/report*');
        $attOpen     = Request::is('school/student-attendance*');
        $leaveOpen   = Request::is('school/student-leave*');
        $shortOpen   = Request::is('school/short-leave*');
        $attGrpOpen  = $attOpen || $leaveOpen || $shortOpen;

        $certTemplates = Request::is('school/certificates/settings');
        $certOpen      = Request::is('school/certificates*');
        $certList      = $certOpen && !$certTemplates;

        // Group "open" flags
        $openAcademics  = $ttOpen || Request::is('school/academic*') || Request::is('school/exams*') || Request::is('school/question-bank*') || Request::is('school/homework*');
        $openStudents   = $stuOpen || Request::is('school/admissions*') || Request::is('school/promotion*') || $attGrpOpen || $certOpen;
        $openFees       = Request::is('school/fees*');
        $openFacilities = Request::is('school/transport*') || Request::is('school/hostel*');
        $openGeneral    = Request::is('school/notices*') || Request::is('school/reports*') || Request::is('school/settings*');
    @endphp

    {{-- Dashboard --}}
    @if (hasPermission('school_dashboard','view'))
    <li class="navbar-vertical-aside-has-menu {{ Request::is('school/dashboard') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.school.dashboard') }}" title="{{ translate('School Dashboard') }}">
            <i class="tio-education nav-icon"></i>
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('School Dashboard') }}</span>
        </a>
    </li>
    @endif

    {{-- ===== Group: Academics ===== --}}
    @if ($grpAcademics)
        <li class="navbar-vertical-aside-has-menu {{ $openAcademics ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('Academics') }}">
                <i class="tio-book nav-icon"></i>
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Academics') }}</span>
            </a>
            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $openAcademics ? 'block' : 'none' }}">

                @if ($vAcademic)
                    <li class="nav-item {{ Request::is('school/academic*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.academic.index') }}" title="{{ translate('Academic Setup') }}">
                            <span class="tio-book nav-icon"></span><span class="text-truncate">{{ translate('Academic Setup') }}</span>
                        </a>
                    </li>
                @endif

                @if ($vTimetable)
                    <li class="navbar-vertical-aside-has-menu {{ $ttOpen ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('Timetable') }}">
                            <i class="tio-table nav-icon"></i><span class="text-truncate">{{ translate('Timetable') }}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $ttOpen ? 'block' : 'none' }}">
                            <li class="nav-item {{ $ttClass ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.school.timetable.index') }}" title="{{ translate('Class Timetable') }}">
                                    <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Class Timetable') }}</span>
                                </a>
                            </li>
                            <li class="nav-item {{ $ttTeacher ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.school.timetable.teacher') }}" title="{{ translate('Teacher Timetable') }}">
                                    <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Teacher Timetable') }}</span>
                                </a>
                            </li>
                            <li class="nav-item {{ $ttSubs ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.school.timetable.substitutions') }}" title="{{ translate('Substitutions') }}">
                                    <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Substitutions') }}</span>
                                </a>
                            </li>
                            @if (hasAnyPermission(['timetable.edit']))
                                <li class="nav-item {{ $ttPeriods ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.school.timetable.periods') }}" title="{{ translate('Periods & Time Slots') }}">
                                        <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Periods & Time Slots') }}</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if ($vExams)
                    <li class="nav-item {{ Request::is('school/exams*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.exams.index') }}" title="{{ translate('Exams & Results') }}">
                            <span class="tio-document-text-outlined nav-icon"></span><span class="text-truncate">{{ translate('Exams & Results') }}</span>
                        </a>
                    </li>
                @endif
                @if ($vQuestionBank && Route::has('vendor.school.question-bank.index'))
                    <li class="nav-item {{ Request::is('school/question-bank*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.question-bank.index') }}" title="{{ translate('Question Bank') }}">
                            <span class="tio-help-outlined nav-icon"></span><span class="text-truncate">{{ translate('Question Bank') }}</span>
                        </a>
                    </li>
                @endif

                @if ($vHomework)
                    <li class="nav-item {{ Request::is('school/homework*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.homework.index') }}" title="{{ translate('Homework') }}">
                            <span class="tio-notebook-bookmarked nav-icon"></span><span class="text-truncate">{{ translate('Homework') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    {{-- ===== Group: Students ===== --}}
    @if ($grpStudents)
        <li class="navbar-vertical-aside-has-menu {{ $openStudents ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('Students') }}">
                <i class="tio-group-equal nav-icon"></i>
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Students') }}</span>
            </a>
            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $openStudents ? 'block' : 'none' }}">

                @if ($vStudents)
                    <li class="navbar-vertical-aside-has-menu {{ $stuOpen ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('Students') }}">
                            <i class="tio-user nav-icon"></i><span class="text-truncate">{{ translate('Student Records') }}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $stuOpen ? 'block' : 'none' }}">
                            <li class="nav-item {{ $stuList ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.school.students.index') }}" title="{{ translate('All Students') }}">
                                    <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('All Students') }}</span>
                                </a>
                            </li>
                            @if (hasAnyPermission(['students.import']))
                                <li class="nav-item {{ $stuImport ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.school.students.import') }}" title="{{ translate('Bulk Import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Bulk Import') }}</span>
                                    </a>
                                </li>
                            @endif
                            @if (hasAnyPermission(['school_settings.edit']))
                                <li class="nav-item {{ $stuSettings ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.school.students.settings') }}" title="{{ translate('Admission No. Settings') }}">
                                        <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Admission No. Settings') }}</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if ($vAdmissions)
                    <li class="nav-item {{ Request::is('school/admissions*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.admissions.index') }}" title="{{ translate('Admissions') }}">
                            <span class="tio-user-add nav-icon"></span><span class="text-truncate">{{ translate('Admissions') }}</span>
                        </a>
                    </li>
                @endif

                @if ($vPromotion)
                    <li class="nav-item {{ Request::is('school/promotion*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.promotion.index') }}" title="{{ translate('Promotion') }}">
                            <span class="tio-stairs-up nav-icon"></span><span class="text-truncate">{{ translate('Promotion') }}</span>
                        </a>
                    </li>
                @endif

                @if ($vAttendance)
                    <li class="navbar-vertical-aside-has-menu {{ $attGrpOpen ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('Attendance') }}">
                            <i class="tio-checkmark-circle-outlined nav-icon"></i><span class="text-truncate">{{ translate('Attendance') }}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $attGrpOpen ? 'block' : 'none' }}">
                            @if (hasAnyPermission(['student_attendance.view', 'student_attendance.add']))
                            <li class="nav-item {{ $attOpen && !$attReport ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.school.student-attendance.mark') }}" title="{{ translate('Mark Attendance') }}">
                                    <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Mark Attendance') }}</span>
                                </a>
                            </li>
                            @endif
                            @if (hasAnyPermission(['student_attendance.view']))
                            <li class="nav-item {{ $attReport ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.school.student-attendance.report') }}" title="{{ translate('Attendance Report') }}">
                                    <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Attendance Report') }}</span>
                                </a>
                            </li>
                            @endif
                            @if (hasAnyPermission(['student_leave.view', 'student_leave.add', 'student_leave.approve', 'student_leave.reject', 'student_leave.delete']))
                            <li class="nav-item {{ $leaveOpen ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.school.student-leave.index') }}" title="{{ translate('Leave Requests') }}">
                                    <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Leave Requests') }}</span>
                                </a>
                            </li>
                            @endif
                            @if (Route::has('vendor.school.short-leave.index') && hasAnyPermission(['short_leave.view', 'short_leave.add', 'short_leave.return', 'short_leave.delete']))
                                <li class="nav-item {{ $shortOpen ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.school.short-leave.index') }}" title="{{ translate('Short Leave / Gate Pass') }}">
                                        <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Short Leave / Gate Pass') }}</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if ($vCertificates)
                    <li class="navbar-vertical-aside-has-menu {{ $certOpen ? 'active' : '' }}">
                        <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate('Certificates') }}">
                            <i class="tio-receipt-outlined nav-icon"></i><span class="text-truncate">{{ translate('Certificates') }}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $certOpen ? 'block' : 'none' }}">
                            @if (hasAnyPermission(['certificates.view', 'certificates.add']))
                                <li class="nav-item {{ $certList ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.school.certificates.index') }}" title="{{ translate('Certificate List') }}">
                                        <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Certificate List') }}</span>
                                    </a>
                                </li>
                            @endif
                            @if (hasAnyPermission(['certificates.edit']))
                                <li class="nav-item {{ $certTemplates ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.school.certificates.settings') }}" title="{{ translate('Certificate Templates') }}">
                                        <span class="tio-circle nav-indicator-icon"></span><span class="text-truncate">{{ translate('Certificate Templates') }}</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    {{-- ===== Group: Fees ===== --}}
    @if ($vFees)
        <li class="navbar-vertical-aside-has-menu {{ $openFees ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('Fees') }}">
                <i class="tio-money nav-icon"></i>
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Fees') }}</span>
            </a>
            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $openFees ? 'block' : 'none' }}">
                @if (hasAnyPermission(['fee_dues.view', 'fee_collection.view', 'fee_collection.collect']))
                <li class="nav-item {{ Request::is('school/fees') || Request::is('school/fees/collect*') || Request::is('school/fees/receipt*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.school.fees.index') }}" title="{{ translate('Dues & Collection') }}">
                        <span class="tio-money-vs nav-icon"></span><span class="text-truncate">{{ translate('Dues & Collection') }}</span>
                    </a>
                </li>
                @endif
                @if (hasPermission('fee_structure','view'))
                <li class="nav-item {{ Request::is('school/fees/structure*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.school.fees.structure') }}" title="{{ translate('Fee Structure') }}">
                        <span class="tio-money-vs nav-icon"></span><span class="text-truncate">{{ translate('Fee Structure') }}</span>
                    </a>
                </li>
                @endif
                @if (hasPermission('fee_heads','view'))
                <li class="nav-item {{ Request::is('school/fees/heads*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.school.fees.heads') }}" title="{{ translate('Fee Heads') }}">
                        <span class="tio-money nav-icon"></span><span class="text-truncate">{{ translate('Fee Heads') }}</span>
                    </a>
                </li>
                @endif
                @if (hasPermission('scholarship','view'))
                <li class="nav-item {{ Request::is('school/fees/concessions*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.school.fees.concessions') }}" title="{{ translate('Scholarships & Concessions') }}">
                        <span class="tio-gift nav-icon"></span><span class="text-truncate">{{ translate('Scholarships') }}</span>
                    </a>
                </li>
                @endif
                @if (hasPermission('fee_collection','view'))
                <li class="nav-item {{ Request::is('school/fees/payments*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('vendor.school.fees.payments') }}" title="{{ translate('Collection Report') }}">
                        <span class="tio-chart-bar-1 nav-icon"></span><span class="text-truncate">{{ translate('Collection Report') }}</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>
    @endif

    {{-- ===== Group: Facilities ===== --}}
    @if ($grpFacilities)
        <li class="navbar-vertical-aside-has-menu {{ $openFacilities ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('Facilities') }}">
                <i class="tio-cube nav-icon"></i>
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Facilities') }}</span>
            </a>
            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $openFacilities ? 'block' : 'none' }}">
                @if ($vTransport)
                    <li class="nav-item {{ Request::is('school/transport*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.transport.index') }}" title="{{ translate('Transport') }}">
                            <span class="tio-truck nav-icon"></span><span class="text-truncate">{{ translate('Transport') }}</span>
                        </a>
                    </li>
                @endif
                @if ($vHostel)
                    <li class="nav-item {{ Request::is('school/hostel*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.hostel.index') }}" title="{{ translate('Hostel') }}">
                            <span class="tio-hotel nav-icon"></span><span class="text-truncate">{{ translate('Hostel') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    {{-- ===== Group: General ===== --}}
    @if ($grpGeneral)
        <li class="navbar-vertical-aside-has-menu {{ $openGeneral ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="{{ translate('General') }}">
                <i class="tio-apps nav-icon"></i>
                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('General') }}</span>
            </a>
            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{ $openGeneral ? 'block' : 'none' }}">
                @if ($vNotices)
                    <li class="nav-item {{ Request::is('school/notices*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.notices.index') }}" title="{{ translate('Notice Board') }}">
                            <span class="tio-comment nav-icon"></span><span class="text-truncate">{{ translate('Notice Board') }}</span>
                        </a>
                    </li>
                @endif
                @if ($vReports)
                    <li class="nav-item {{ Request::is('school/reports*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.reports.index') }}" title="{{ translate('Reports') }}">
                            <span class="tio-chart-bar-1 nav-icon"></span><span class="text-truncate">{{ translate('Reports') }}</span>
                        </a>
                    </li>
                @endif
                @if ($vSettings)
                    <li class="nav-item {{ Request::is('school/settings*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('vendor.school.settings.index') }}" title="{{ translate('School Settings') }}">
                            <span class="tio-settings nav-icon"></span><span class="text-truncate">{{ translate('Settings') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

@endif

{{-- Shared platform features (HR, Accounts, Library, Inventory, Notifications, etc.) --}}
@include('layouts.vendor.partials._sidebar_menu_default', ['store_data' => $store_data])
