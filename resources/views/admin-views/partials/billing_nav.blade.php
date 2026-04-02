  @if (\App\CentralLogics\Helpers::module_permission_check('billing'))
      <li
          class="navbar-vertical-aside-has-menu {{ Request::is('business-settings/settings') || Request::is('billing*') || Request::is('invoice-list') || Request::is('invoices') ? 'active' : '' }}">
          <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:;" title="Billing">
              <i class="tio-home-vs-1-outlined nav-icon"></i>
              <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                  Billing</span>
          </a>

          <ul class="js-navbar-vertical-aside-submenu nav nav-sub">

              <li class="navbar-vertical-aside-has-menu {{ Request::is('billing/manual-bill') ? 'active' : '' }}">
                  <a class="nav-link " href="{{ route('admin.billing.manual-bill') }}"
                      title="{{ translate('messages.Generate Bill') }}">
                      <span class="tio-document-text nav-icon"></span>
                      <span class="text-truncate">Generate Bill</span>
                  </a>
              </li>
              @if (hasMasterModulePermission('billing'))
                  @if (hasPermission('billing', 'add_advanced'))
                      <li
                          class="navbar-vertical-aside-has-menu {{ Request::is('billing/create-invoice') ? 'active' : '' }}">
                          <a class="nav-link " href="{{ route('admin.billing.create-invoice') }}"
                              title="{{ translate('messages.Generate Advanced Invoice') }}">
                              <span class="tio-document-text nav-icon"></span>
                              <span class="text-truncate">Generate Advanced Invoice</span>
                          </a>
                      </li>
                  @endif
              @endif
              @if (hasAnyPermission(['billing.list', 'billing.export', 'billing.import']))

                  <li class="navbar-vertical-aside-has-menu {{ Request::is('billing/credit') ? 'active' : '' }}">
                      <a class="nav-link " href="{{ route('admin.billing.list') }}"
                          title="{{ translate('messages.Bill') }}">
                          <span class="tio-coin nav-icon"></span>
                          <span class="text-truncate">Bills</span>
                      </a>
                  </li>
                  <li class="navbar-vertical-aside-has-menu {{ Request::is('billing') ? 'active' : '' }}">
                      <a class="nav-link " href="{{ route('admin.billing.index') }}"
                          title="{{ translate('messages.Mychitti Bills') }}">
                          <span class="tio-coin nav-icon"></span>
                          <span class="text-truncate">Mychitti Bills</span>
                      </a>
                  </li>

                  @if (Config::get('module.current_module_id') == 5)
                      <li class="nav-item {{ Request::is('invoice-list') ? 'active' : '' }}">
                          <a class="nav-link " href="{{ route('admin.order.invoices') }}" title="invoices">
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
                          <a class="nav-link " href="{{ route('admin.billing.my-bills') }}" title="Purchase Bills">
                              <span class="tio-money-vs nav-icon"></span>
                              <span class="text-truncate">Purchase Bills</span>
                          </a>
                      </li>
                  @endif
                  @if (hasPermission('billing', 'settings') ||
                          hasAnyModulePermission(['billing_bank_account', 'billing_signatures', 'billing_tnc']))
                      <li class="nav-item  {{ Request::is('billing/settings') || Request::is('business-settings/settings') ? 'active' : '' }}"
                          style="margin-top:0 !important;">
                          <a class="nav-link " href="{{ route('admin.billing.settings') }}" title="Billing Settings">
                              <span class="tio-money-vs nav-icon"></span>
                              <span class="text-truncate">Billing Settings</span>
                          </a>
                      </li>
                  @endif
              @endif
          </ul>
      </li>
  @endif
