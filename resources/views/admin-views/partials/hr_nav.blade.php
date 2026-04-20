   {{-- =============================== HR Management=========================== --}}
   @if (hasMasterModulePermission('hr_manage'))
       <li
           class="navbar-vertical-aside-has-menu {{ Request::is('hr*') || Request::is('task-salary-categories') || Request::is('shifts*') || Request::is('custom-role*') || Request::is('staff*') || Request::is('salary*') || Request::is('leave*') || Request::is('attendance*') || Request::is('holidays*') ? 'active' : '' }} ">
           <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
               title="HR Management">

               <i class="tio-group-junior nav-icon"></i>

               <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                   HR Management</span>
           </a>

           <ul class="js-navbar-vertical-aside-submenu nav nav-sub"> 
               @if (hasPermission('hr_manage', 'dashboard'))
                   <li class="nav-item {{ Request::is('hr/dashboard') ? 'active' : '' }}">
                       <a class="nav-link " href="{{ route('admin.hr.dashboard') }}" title="HR Management Dashboard">
                           <span class="tio-dashboard-outlined nav-icon"></span>
                           <span class="text-truncate">Dashboard</span>
                       </a>
                   </li>
               @endif
               @if (hasAnyModulePermission(['staff_manage', 'staff_team', 'staff_department', 'staff_role']))
                   <li
                       class="navbar-vertical-aside-has-menu {{ Request::is('staff*') || Request::is('custom-role*') ? 'active' : '' }}">
                       <a class="sub-link js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                           href="javascript:;" title="Staff
                                                Management">
                           <i class="tio-group-junior nav-icon"></i>
                           <span class=" text-truncate">Staff
                               Management</span>
                       </a> 

                       <ul class="js-navbar-vertical-aside-submenu nav nav-sub"  
                           style="display: {{ Request::is('staff*') || Request::is('custom-role*') ? 'block' : 'none' }}">
                           @if (hasPermission('staff_manage', 'add'))
                               <li class="nav-item {{ Request::is('staff/add-new') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.users.employee.add-new') }}"
                                       title="{{ translate('messages.add') }} {{ translate('messages.new') }} {{ translate('messages.Employee') }}">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">{{ translate('messages.add') }}
                                           {{ translate('messages.new') }} Staff</span>
                                   </a>
                               </li>
                           @endif
                           @if (hasAnyPermission(['staff_manage.list', 'staff_manage.export']))
                               <li class="nav-item {{ Request::is('staff/list') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.users.employee.list') }}"
                                       title="Staff {{ translate('messages.list') }}">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Staff
                                           {{ translate('messages.list') }} </span>
                                   </a>
                               </li>
                           @endif
                           @if (hasAnyModulePermission(['staff_team']))
                               <li class="nav-item {{ Request::is('staff/team') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.staff.team.index') }}" title="Teams">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Teams </span>
                                   </a>
                               </li>
                           @endif
                           @if (hasAnyModulePermission(['staff_department']))
                               <li class="nav-item {{ Request::is('staff-department') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.staff-department.all') }}"
                                       title="Staff Department">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Staff Department</span>
                                   </a>
                               </li>
                           @endif
                           @if (hasAnyModulePermission(['staff_role']))
                               <li class="nav-item {{ Request::is('custom-role/create') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.users.custom-role.create') }}"
                                       title="Staff Role">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Staff Roles (Permissions)</span>
                                   </a>
                               </li>
                           @endif
                           @if (hasPermission('staff_manage', 'settings'))
                               <li class="nav-item {{ Request::is('staff/settings*') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.staff.settings') }}" title="Staff T&C">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Staff Settings</span>
                                   </a>
                               </li>
                           @endif
                       </ul>
                   </li>
               @endif
               @if (hasAnyModulePermission(['attendance_manage', 'attendance_report']))
                   <li class="navbar-vertical-aside-has-menu {{ Request::is('attendance*') ? 'active' : '' }}">
                       <a class="sub-link  js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                           href="javascript:;" title="Attendance Management">
                           <i class="tio-event nav-icon"></i>
                           <span class="text-truncate">Attendance
                               Management</span>
                       </a>

                       <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                           style="display: {{ Request::is('attendance*') ? 'block' : 'none' }}">
                           @if (hasPermission('attendance_manage', 'list'))
                               <li class="nav-item {{ Request::is('attendance/list') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.attendance.all') }}"
                                       title="{{ translate('messages.manage') }}">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Attendance Manage</span>
                                   </a>
                               </li>
                           @endif
                           @if (hasAnyModulePermission(['attendance_report']))
                               <li class="nav-item {{ Request::is('attendance/report') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.attendance.report') }}" title="Reports">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Attendance Reports</span>
                                   </a>
                               </li>
                           @endif
                       </ul>
                   </li>
               @endif


               @if (hasAnyModulePermission(['salary_advanced', 'salary_report', 'task_salary_category', 'salary_manage']))
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
                               <li class="nav-item {{ Request::is('salary/list') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.salary.list') }}" title="manage">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Salary Manage</span>
                                   </a>
                               </li>
                           @endif
                             @if (\App\CentralLogics\Helpers::permission_check('advance_requests'))
                            <li class="nav-item {{ Request::is('*all-advance-requests*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.users.salary.all-advance-requests') }}" title="Advance Requests">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="text-truncate">Advance Requests</span>
                                </a>
                            </li>
                            @endif
                           @if (hasAnyModulePermission(['task_salary_category']))
                               <li class="nav-item {{ Request::is('task-salary-categories') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.task-salary-categories.index') }}"
                                       title="report">
                                       <span class="tio-circle nav-indicator-icon"></span>
                                       <span class="text-truncate">Task Salary Category</span>
                                   </a>
                               </li>
                           @endif
                           @if (hasAnyModulePermission(['salary_report']))
                               <li class="nav-item {{ Request::is('salary/report') ? 'active' : '' }}">
                                   <a class="nav-link " href="{{ route('admin.salary.report') }}" title="report">
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
                       <a class="sub-link  nav-link" href="{{ route('admin.shifts.index') }}"
                           title="Shifts Management">
                           <i class="tio-timer nav-icon"></i>
                           <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Shifts
                               Management</span>
                       </a>
                   </li>
               @endif


               @if (hasAnyModulePermission(['leave_manage']))
                   <li class="navbar-vertical-aside {{ Request::is('leave/list') ? 'active' : '' }}">
                       <a class="sub-link  nav-link" href="{{ route('admin.leave.all') }}" title="Leave Management">
                           <i class="tio-category nav-icon"></i>
                           <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Leave
                               Management</span>
                       </a>
                   </li>
               @endif

               @if (auth('admin')->user()->role_id != 1)
                   <li class="navbar-vertical-aside {{ Request::is('leave/my-requests') ? 'active' : '' }}">
                       <a class="sub-link nav-link" href="{{ route('admin.leave.my-requests') }}" title="My Leaves">
                           <i class="tio-calendar-note nav-icon"></i>
                           <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">My Leaves</span>
                       </a>
                   </li>
                   <li class="navbar-vertical-aside {{ Request::is('advance-payment') ? 'active' : '' }}">
                       <a class="sub-link nav-link" href="{{ route('admin.advance-payment') }}" title="My Salary Advance">
                           <i class="tio-money nav-icon"></i>
                           <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">My Salary Advance</span>
                       </a>
                   </li>
               @endif

               <li class="navbar-vertical-aside {{ Request::is('holidays*') ? 'active' : '' }}">
                   <a class="sub-link  nav-link" href="{{ route('admin.holidays.index') }}" title="Holidays">
                       <i class="tio-calendar nav-icon"></i>
                       <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">Holidays</span>
                   </a>
               </li>

           </ul>
       </li>
   @endif
