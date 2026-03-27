<div class="footer">
    <div class="row justify-content-between align-items-center">
        <div class="col">
            <p class="font-size-sm mb-0">
                &copy; {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value }}. <span
                    class="d-none d-sm-inline-block">{{ \App\Models\BusinessSetting::where(['key' => 'footer_text'])->first()->value }}</span>
            </p>
        </div>
        <div class="col-auto">
            <div class="d-flex justify-content-end">
                <!-- List Dot -->
                <ul class="list-inline list-separator">
                    @if (hasPermission('action_logs', 'view'))
                        <li class="list-inline-item">
                            <a class="list-separator-link" href="{{ route('admin.logs.action-logs') }}">Logs</a>
                        </li>
                    @elseif (hasPermission('error_logs', 'view'))
                        <li class="list-inline-item">
                            <a class="list-separator-link" href="{{ route('admin.logs.action-logs.errors') }}">Logs</a>
                        </li>
                    @elseif (hasPermission('admin_actions', 'view'))
                        <li class="list-inline-item">
                            <a class="list-separator-link" href="{{ route('admin.logs.action-logs.admin') }}">Logs</a>
                        </li>
                    @endif 
                    @if (!_onlyStoreAddEdit())
                        <li class="list-inline-item">
                            <a class="list-separator-link" 
                                href="{{ route('admin.business-settings.business-setup') }}">{{ translate('messages.business_setup') }}</a>
                        </li>
                    @endif

                    <li class="list-inline-item">
                        <a class="list-separator-link"
                            href="{{ route('admin.settings') }}">{{ translate('messages.profile') }}</a>
                    </li>
                    @if (!_onlyStoreAddEdit())
                        <li class="list-inline-item">
                            <!-- Keyboard Shortcuts Toggle -->
                            <div class="hs-unfold">
                                <a class="js-hs-unfold-invoker h-unset btn btn-icon btn-ghost-secondary rounded-circle"
                                    href="{{ route('admin.dashboard') }}">
                                    {{ translate('messages.home') }}
                                </a>
                            </div>
                            <!-- End Keyboard Shortcuts Toggle -->
                        </li>
                    @endif
                    <li class="list-inline-item">
                        <label class="badge badge-soft-primary m-0">
                            {{ translate('messages.software_version') }} : {{ env('SOFTWARE_VERSION') }}
                        </label>
                    </li>
                </ul>
                <!-- End List Dot -->
            </div>
        </div>
    </div>
</div>
