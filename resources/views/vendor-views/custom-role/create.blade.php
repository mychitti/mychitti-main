@extends('layouts.vendor.app')
@section('title', translate('messages.Create_Role'))
@push('css_or_js')
    <style>
        .module_th {
            position: sticky;
            left: 0;
            background: white;
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Heading -->
        <div class="page-header mt-3">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.custom_role') }}
                </span>
            </h1>
        </div>
        @if (hasPermission('staff_role', 'add') || hasPermission('basic_staff_manage', 'role_manage'))
            <!-- Content Row -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <span class="card-header-icon">
                            <i class="tio-document-text-outlined"></i>
                        </span>
                        <span>{{ translate('messages.role_form') }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @include('vendor-views/forms/role_add')
                </div>
            </div>
        @endif 
        @if (hasPermission('staff_role', 'list') || hasPermission('basic_staff_manage', 'role_manage'))
            <div class="card mt-3">
                <div class="card-header border-0">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">
                            <span class="card-header-icon">
                                <i class="tio-document-text-outlined"></i>
                            </span>
                            <span>
                                {{ translate('messages.roles_table') }}<span class="badge badge-soft-dark ml-2"
                                    id="itemCount">{{ $rl->total() }}</span>
                            </span>
                        </h5>
                        <form class="search-form min--250">
                            <!-- Search -->
                            <div class="input-group input--group">
                                <input value="{{ request()?->search ?? '' }}" type="search" name="search"
                                    class="form-control" placeholder="{{ translate('messages.search_role') }}"
                                    aria-label="{{ translate('messages.search') }}">
                                <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                            </div>
                            <!-- End Search -->
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-align-middle card-table"
                            data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0 w-50px">{{ translate('messages.sl#') }}</th>
                                    <th class="border-0 w-50px">{{ translate('messages.role_name') }}</th>
                                    <th class="border-0 w-100px">{{ translate('messages.modules') }}</th>
                                    <th class="border-0 w-50px">{{ translate('messages.created_at') }}</th>
                                    <th class="border-0 w-50px text-center">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach ($rl as $k => $r)
                                    <tr>
                                        <td>{{ $k + $rl->firstItem() }}</td>
                                        <td>{{ Str::limit($r['name'], 20, '...') }}</td>
                                        <td class="text-capitalize">
                                            @if ($r['modules'] != null)
                                                @foreach ((array) json_decode($r['modules']) as $key => $m)
                                                    @if ($m == 'bank_info')
                                                        {{ translate('messages.profile') }}
                                                    @else
                                                        {{ translate(str_replace('_', ' ', $m)) }}
                                                    @endif


                                                    {{ !$loop->last ? ',' : '.' }}
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ date('d-M-y', strtotime($r['created_at'])) }}</td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                @if (hasPermission('staff_role', 'edit') || hasPermission('basic_staff_manage', 'role_manage'))
                                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                                        href="{{ route('vendor.custom-role.edit', [$r['id']]) }}"
                                                        title="{{ translate('messages.edit_role') }}"><i
                                                            class="tio-edit"></i>
                                                    </a>
                                                @endif
                                                @if (hasPermission('staff_role', 'delete') || hasPermission('basic_staff_manage', 'role_manage'))
                                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                        href="javascript:" data-id="role-{{ $r['id'] }}"
                                                        data-message="{{ translate('messages.Want_to_delete_this_role') }}"
                                                        title="{{ translate('messages.delete_role') }}"><i
                                                            class="tio-delete-outlined"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            @if (hasPermission('staff_role', 'delete') || hasPermission('basic_staff_manage', 'role_manage'))
                                                <form action="{{ route('vendor.custom-role.delete', [$r['id']]) }}"
                                                    method="post" id="role-{{ $r['id'] }}">
                                                    @csrf @method('delete')
                                                </form>
                                            @endif

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if (count($rl) !== 0)
                            <hr>
                        @endif
                        <div class="page-area">
                            <table>
                                <tfoot>
                                    {!! $rl->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if (count($rl) === 0)
                            <div class="empty--data">
                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                                <h5>
                                    {{ translate('no_data_found') }}
                                </h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('script_2')
    <script>
        $(".granular_permission_check").on('change', function() {
            var master_module = $(this).val();
            if ($(this).prop('checked') == true) {
                $('.master_module_heading[data-master-module="' + master_module + '"]').show()
                $('.master_module_table[data-master-module="' + master_module + '"]').show()
            } else {
                $('.master_module_heading[data-master-module="' + master_module + '"]').hide()
                $('.master_module_table[data-master-module="' + master_module + '"]').hide()
            }
        })
    </script>
    <script>
        (function() {
            const $$ = sel => Array.from(document.querySelectorAll(sel));

            // Master select-all
            const selectAll = document.getElementById('selectAll');
            selectAll?.addEventListener('change', (e) => {
                $$('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
                $$('.row-toggle:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
                $$('.column-toggle:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
            });

            // Row "All" toggle
            $$('.row-toggle').forEach(rowTgl => {
                rowTgl.addEventListener('change', (e) => {
                    const feature = e.target.dataset.feature;
                    $(`input.perm-checkbox[data-feature="${feature}"]`).forEach(cb => {
                        if (!cb.disabled) cb.checked = e.target.checked;
                    });
                    refreshHeaderStates();
                });
            });

            // Column toggle (by action)
            $$('.column-toggle').forEach(colTgl => {
                colTgl.addEventListener('change', (e) => {
                    const action = e.target.dataset.action;
                    $(`input.perm-checkbox[data-action="${action}"]`).forEach(cb => {
                        if (!cb.disabled) cb.checked = e.target.checked;
                    });
                    refreshHeaderStates();
                });
            });

            // Individual checkbox changes update row/column/master states
            $$('.perm-checkbox').forEach(cb => {
                cb.addEventListener('change', refreshHeaderStates);
            });

            function $(sel) {
                return Array.from(document.querySelectorAll(sel));
            }

            function refreshHeaderStates() {
                // Row states
                $$('.row-toggle').forEach(rowTgl => {
                    const feature = rowTgl.dataset.feature;
                    const boxes = $(`input.perm-checkbox[data-feature="${feature}"]`).filter(cb => !cb
                        .disabled);
                    const allOn = boxes.length && boxes.every(cb => cb.checked);
                    rowTgl.checked = allOn;
                });

                // Column states
                $$('.column-toggle').forEach(colTgl => {
                    const action = colTgl.dataset.action;
                    const boxes = $(`input.perm-checkbox[data-action="${action}"]`).filter(cb => !cb.disabled);
                    const allOn = boxes.length && boxes.every(cb => cb.checked);
                    colTgl.checked = allOn;
                });

                // Master
                const allBoxes = $$('.perm-checkbox').filter(cb => !cb.disabled);
                const allOn = allBoxes.length && allBoxes.every(cb => cb.checked);
                selectAll.checked = allOn;
            }

            refreshHeaderStates();
        })();
    </script>
@endpush
