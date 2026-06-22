@php
    $ddConfig  = \App\Models\StoreConfig::where('store_id', \App\CentralLogics\Helpers::get_store_id())->first();
    $ddCurrent = $ddConfig?->default_dashboard;

    // Always visible
    $ddOptions = [
        'main'            => ['icon' => 'tio-home',                      'label' => 'Main Dashboard',      'desc' => 'Overview, wallet & leads summary',        'module' => null],
        'leads_page'      => ['icon' => 'tio-chart-bar-1',               'label' => 'Leads Page',          'desc' => 'List, filters and actions for all leads',  'module' => null],
        'leads_dashboard' => ['icon' => 'tio-chart-bar-1',               'label' => 'Leads Dashboard',     'desc' => 'Lead stats, performance & analytics',      'module' => null],
        // Shown only when the store has the corresponding module
        'hospital'        => ['icon' => 'tio-hospital',                  'label' => 'Hospital Dashboard',  'desc' => 'Appointments, OPD & IPD',                  'module' => 'hospital'],
        'laundry'         => ['icon' => 'tio-shopping-basket',            'label' => 'Laundry Dashboard',   'desc' => 'Orders, challans & monthly register',       'module' => 'laundry'],
        'hr'              => ['icon' => 'tio-user-outlined',              'label' => 'HR Management',       'desc' => 'Staff, attendance & payroll',               'module' => 'hr_manage'],
        'account'         => ['icon' => 'tio-money',                     'label' => 'Accounts Dashboard',  'desc' => 'Income, expenses & ledger',                 'module' => 'account_manage'],
        'inventory'       => ['icon' => 'tio-shopping-cart',             'label' => 'Inventory Dashboard', 'desc' => 'Stock, sales & item entries',               'module' => 'inventory_manage'],
        'pos'             => ['icon' => 'tio-shopping-basket-outlined',  'label' => 'POS',                 'desc' => 'Point of sale orders',                      'module' => 'pos'],
        // Shown only for Retail POS stores.
        'retail_pos'      => ['icon' => 'tio-shopping-basket-outlined',  'label' => 'Retail POS Dashboard','desc' => 'Sales, branches & stock',                   'module' => null, 'business_type' => 'pos_retail'],
    ];
@endphp  

<div class="modal fade" id="defaultDashboardModal" tabindex="-1" role="dialog" aria-labelledby="defaultDashboardModalLabel">
    <div class="modal-dialog modal-dialog-centered" style="max-width:340px;" role="document">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 8px 40px rgba(0,0,0,.18);overflow:hidden;">
            <div class="modal-header" style="padding:14px 18px;border-bottom:1px solid #f0f0f0;">
                <h5 class="modal-title" id="defaultDashboardModalLabel"
                    style="font-size:13px;font-weight:700;color:#18181b;margin:0;">
                    Default Dashboard
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="font-size:18px;color:#71717a;padding:0;margin:0;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:12px 14px;">
                <p style="font-size:11px;color:#71717a;margin:0 0 10px;">
                    Select which dashboard opens when you log in or visit the panel.
                </p>
                @foreach($ddOptions as $key => $opt)
                @php
                    $active = ($ddCurrent === $key);
                    if ($opt['module'] !== null && !hasMasterModulePermission($opt['module'])) continue;
                    if (isset($opt['business_type']) && strtolower(\App\CentralLogics\Helpers::get_store_data()->business_type ?? '') !== $opt['business_type']) continue;
                @endphp
                <form method="POST" action="{{ route('vendor.profile.default-dashboard') }}" style="margin-bottom:6px;">
                    @csrf
                    <input type="hidden" name="default_dashboard" value="{{ $key }}">
                    <button type="submit"
                        style="width:100%;text-align:left;padding:10px 13px;border-radius:10px;
                               border:1.5px solid {{ $active ? '#18181b' : '#e4e4e7' }};
                               background:{{ $active ? '#18181b' : '#fff' }};
                               cursor:pointer;display:flex;align-items:center;gap:11px;
                               font-family:inherit;transition:border-color .12s,background .12s;">
                        <i class="{{ $opt['icon'] }}"
                           style="font-size:17px;color:{{ $active ? '#fff' : '#71717a' }};flex-shrink:0;"></i>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12px;font-weight:700;color:{{ $active ? '#fff' : '#18181b' }};line-height:1.2;">
                                {{ $opt['label'] }}
                            </div>
                            <div style="font-size:10px;color:{{ $active ? 'rgba(255,255,255,.65)' : '#a1a1aa' }};margin-top:1px;">
                                {{ $opt['desc'] }}
                            </div>
                        </div>
                        @if($active)
                        <span style="font-size:13px;color:#fff;font-weight:700;flex-shrink:0;">✓</span>
                        @endif
                    </button>
                </form>
                @endforeach
                @if(!$ddCurrent)
                <p style="font-size:10px;color:#a1a1aa;text-align:center;margin:8px 0 0;">
                    No preference set — dashboard is auto-detected.
                </p>
                @endif
            </div>
        </div>
    </div>
</div>
