   {{-- =============================== ACCOUNT Management=========================== --}}
   @if (hasMasterModulePermission('account_manage'))
       <li class="navbar-vertical-aside-has-menu {{ Request::is('account*') || Request::is('asset*') ? 'active' : '' }}">
           <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;"
               title=" Account Management">
               <i class="tio-money nav-icon"></i>

               <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                   Account Management</span>
           </a>
           <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
               @if (_storeAccountType() == 'ledger' && hasPermission('dashboard', 'view'))
                   <li class="navbar-vertical-aside-has-menu {{ Request::is('account/dashboard') ? 'active' : '' }}">
                       <a class="nav-link " href="{{ route('admin.account.dashboard') }}"
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
                               <a class="nav-link " href="{{ route('admin.account.approvals') }}"
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
                                               href="{{ route('admin.account.request-form.journal-entry.index') }}"
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
                                               href="{{ route('admin.account.request-form.master-ledger.index') }}"
                                               title="{{ translate('messages.Request Form') }}">
                                               <span class="tio-circle nav-indicator-icon"></span>
                                               <span class="text-truncate">Master Leger Request
                                                   Form</span>
                                           </a>
                                       </li>
                                   @endif
                                   @if (hasAnyModulePermission(['apporval_form_incoming_requests']))

                                       @if (auth('vendor_employee')->check())
                                           <li class="nav-item {{ Request::is('account/ds') ? 'active' : '' }}">
                                               <a class="nav-link "
                                                   href="{{ route('admin.account.request-form.incoming-requests') }}"
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
                                   <li class="nav-item {{ Request::is('account/management') ? 'active' : '' }}">
                                       <a class="nav-link " href="{{ route('admin.account.add') }}"
                                           title="{{ translate('messages.Master Ledger Book') }}">
                                           <span class="tio-circle nav-indicator-icon"></span>
                                           <span class="text-truncate">Master Ledger Book</span>
                                       </a>
                                   </li>
                               @endif

                               @if (hasAnyModulePermission(['boa_journal_entry']))
                                   <li class="nav-item {{ Request::is('account/journal-entry') ? 'active' : '' }}">
                                       <a class="nav-link " href="{{ route('admin.account.journal-entry.index') }}"
                                           title="{{ translate('messages.Journal Entry Book') }}">
                                           <span class="tio-circle nav-indicator-icon"></span>
                                           <span class="text-truncate">Journal Entry
                                               Book</span>
                                       </a>
                                   </li>
                               @endif
                               @if (hasAnyModulePermission(['boa_day_book']))
                                   <li class="nav-item {{ Request::is('account/day-book') ? 'active' : '' }}">
                                       <a class="nav-link " href="{{ route('admin.account.day-book.index') }}"
                                           title="{{ translate('messages.Day Book') }}">
                                           <span class="tio-circle nav-indicator-icon"></span>
                                           <span class="text-truncate">Day Book</span>
                                       </a>
                                   </li>
                               @endif
                               @if (hasAnyModulePermission(['boa_petty_cashbook']))
                                   <li class="nav-item {{ Request::is('account/petty-cashbook') ? 'active' : '' }}">
                                       <a class="nav-link " href="{{ route('admin.account.petty-cashbook.index') }}"
                                           title="{{ translate('messages.Petty CashBook') }}">
                                           <span class="tio-circle nav-indicator-icon"></span>
                                           <span class="text-truncate">Petty CashBook</span>
                                       </a>
                                   </li>
                               @endif
                               @if (hasAnyModulePermission(['boa_monthly_maintenance']))
                                   <li class="nav-item  {{ Request::is('account/maintenance') ? 'active' : '' }}">
                                       <a class="nav-link " href="{{ route('admin.account.maintenance.index') }}"
                                           title="{{ translate('messages.monthly_maintenance') }}">
                                           <span class="tio-circle nav-indicator-icon"></span>
                                           <span class="text-truncate">Monthly
                                               Maintenance</span>
                                       </a>
                                   </li>
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
                                               href="{{ route('admin.account.banking.bank-account.index') }}"
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
                                               href="{{ route('admin.account.banking.cash-book.index') }}"
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
                                               href="{{ route('admin.account.banking.bank-reconciliation.index') }}"
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
                                               href="{{ route('admin.account.statement.trial-balance') }}"
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
                                               href="{{ route('admin.account.monthly-finance.monthly-maintanance') }}"
                                               title="Maintenance Requests">
                                               <span class="tio-circle nav-indicator-icon"></span>
                                               <span class="text-truncate">Maintenance
                                                   Requests</span>
                                           </a>
                                       </li>
                                   @endif
                                   @if (hasAnyModulePermission(['rmf_bill_payments']))
                                       <li class="nav-item {{ Request::is('account/tax-report') ? 'active' : '' }}">
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
                                               href="{{ route('admin.account.monthly-finance.property-valuation') }}"
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

                           <li class="navbar-vertical-aside-has-menu {{ Request::is('asset*') ? 'active' : '' }}">
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
                                           <a class=" nav-link" href="{{ route('admin.asset.index') }}"
                                               title="Assets">
                                               <span class="tio-circle nav-indicator-icon"></span>

                                               <span class=" text-truncate">Company Assets
                                                   (properties)</span>
                                           </a>
                                       </li>
                                       {{-- @else --}}
                                       <li class="nav-item {{ Request::is('asset/alotted') ? 'active' : '' }}">
                                           <a class=" nav-link" href="{{ route('admin.asset.alotted') }}"
                                               title="Assets">
                                               <span class="tio-circle nav-indicator-icon"></span>

                                               <span class=" text-truncate">Alotted Company
                                                   Assets</span>
                                           </a>
                                       </li>
                                       {{-- @endif --}}
                                   @endif

                                   @if (hasAnyModulePermission(['assets_chart_of_accounts']))
                                       <li class="nav-item {{ Request::is('account/settings') ? 'active' : '' }}">
                                           <a class="nav-link "
                                               href="{{ route('admin.account.setting.chart-of-account.index') }}"
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
                                       <li class="nav-item {{ Request::is('account/report') ? 'active' : '' }}">
                                           <a class="nav-link " href="{{ route('admin.account.report') }}"
                                               title="{{ translate('messages.report') }}">
                                               <span class="tio-circle nav-indicator-icon"></span>
                                               <span class="text-truncate">Accounts Report</span>
                                           </a>
                                       </li>
                                   @endif
                                   @if (hasAnyModulePermission(['reports_tax_report']))
                                       <li class="nav-item {{ Request::is('account/report/tax') ? 'active' : '' }}">
                                           <a class="nav-link " href="{{ route('admin.account.report.tax') }}"
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
                                           <a class="nav-link " href="{{ route('admin.account.report.audit-logs') }}"
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
                           <a class="nav-link " href="{{ route('admin.account.taxation.gst') }}"
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
                                   <li class="nav-item {{ Request::is('account/setting') ? 'active' : '' }}">
                                       <a class="nav-link " href="{{ route('admin.account.setting') }}"
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
                                           href="{{ route('admin.account.setting.common-settings') }}"
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
