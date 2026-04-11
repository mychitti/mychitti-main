<div class="d-flex flex-wrap justify-content-between align-items-center mb-5 mt-4 __gap-12px">
            <div class="js-nav-scroller hs-nav-scroller-horizontal mt-2">
                <!-- Nav -->
                <ul class="nav nav-tabs border-0 nav--tabs nav--pills">
                    @if(hasPermission('action_logs', 'view'))
                    <li class="nav-item">
                        <a class="nav-link  {{ Request::routeIs('admin.logs.action-logs') ? 'active' : '' }}"
                            href="{{ route('admin.logs.action-logs') }}"
                            aria-disabled="true">Common Actions</a>
                    </li>
                    @endif
                    @if(hasPermission('admin_actions', 'view'))
                    <li class="nav-item">
                        <a class="nav-link  {{ Request::routeIs('admin.logs.action-logs.admin') ? 'active' : '' }}"
                            href="{{ route('admin.logs.action-logs.admin') }}"
                            aria-disabled="true">Admin Action Requests</a>
                    </li> 
                    @endif
                    @if(hasPermission('error_logs', 'view'))
                    <li class="nav-item">
                        <a class="nav-link  {{ Request::routeIs('admin.logs.action-logs.errors') ? 'active' : '' }}"
                            href="{{ route('admin.logs.action-logs.errors') }}"
                            aria-disabled="true">App Error Logs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('admin.logs.website-errors.index') ? 'active' : '' }}"
                            href="{{ route('admin.logs.website-errors.index') }}"
                            aria-disabled="true">Website Error Logs</a>
                    </li>
                    @endif
                </ul>
                <!-- End Nav -->
            </div>
        </div>
