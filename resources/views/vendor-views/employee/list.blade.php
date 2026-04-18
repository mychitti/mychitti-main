@extends('layouts.vendor.app')
@section('title', 'Staff List')
@push('css_or_js')
    <style>
        .status-badge {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .error-badge {
            background: linear-gradient(45deg, #cd4e4e, #a04444);

        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                    </span>
                    <span>
                        Staff List
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $em->total() }}</span>
                    </span>

                </h1>
                @if (hasPermission('staff_manage', 'add'))
                    <a href="{{ route('vendor.employee.add-new') }}" class="btn btn--primary mb-2">
                        <i class="tio-add-circle"></i>
                        <span class="text">Add New Staff</span>
                    </a>
                @endif
            </div>
        </div>
        <!-- Page Heading -->

        <div class="card">
            <div class="card-header py-2 justify-content-end border-0">
                <div class="search--button-wrapper justify-content-end">
                    <form action="" class="d-flex">
                        <select class="form-control mx-1" name="status" onchange="this.form.submit()">
                            <option {{ (request('status') ?? '') == 'All' ? 'selected' : '' }} value="All">All</option>
                            <option {{ (request('status') ?? '') == 'on_duty' ? 'selected' : '' }} value="on_duty">On Duty
                            </option>
                            <option {{ (request('status') ?? '') == 'off_duty' ? 'selected' : '' }} value="off_duty">Off
                                Duty</option>

                        </select>
                    </form>
                    <form class="search-form">

                        <!-- Search -->
                        <div class="input-group input--group">
                            <input value="{{ request()?->search ?? null }}" type="search" name="search"
                                class="form-control"
                                placeholder="{{ translate('messages.Ex:') }} {{ translate('Search by name or email..') }}"
                                aria-label="Search">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    @if (hasPermission('staff_manage', 'export'))
                        <!-- Unfold -->
                        <div class="hs-unfold mr-2">
                            <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle h--40px" href="javascript:;"
                                data-hs-unfold-options='{
                            "target": "#usersExportDropdown",
                            "type": "css-animation"
                        }'>
                                <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                            </a>

                            <div id="usersExportDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                                <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                                <a id="export-excel" class="dropdown-item"
                                    href="{{ route('vendor.employee.export-employee', ['type' => 'excel', request()->getQueryString()]) }}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{ asset('public/assets/admin/svg/components/excel.svg') }}"
                                        alt="Image Description">
                                    {{ translate('messages.excel') }}
                                </a>
                                <a id="export-csv" class="dropdown-item"
                                    href="{{ route('vendor.employee.export-employee', ['type' => 'csv', request()->getQueryString()]) }}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{ asset('public/assets/admin/svg/components/placeholder-csv-format.svg') }}"
                                        alt="Image Description">
                                    .{{ translate('messages.csv') }}
                                </a>

                            </div>
                        </div>
                        <!-- End Unfold -->
                    @endif
                </div>
            </div>
            @if (hasPermission('staff_manage', 'list'))

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="datatable"
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('messages.#') }}</th>
                                    <th class="border-0">{{ translate('messages.employee_id') }}</th>
                                    <th class="border-0">{{ translate('messages.name') }}</th>
                                    <th class="border-0">{{ translate('messages.email') }}</th>
                                    <th class="border-0">{{ translate('messages.phone') }}</th>
                                    <th class="border-0">{{ translate('messages.Role') }}</th>
                                    <th class="border-0">{{ translate('messages.Status') }}</th>
                                    <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach ($em as $k => $e)
                                    <tr class="employee-row" style="cursor:pointer"
                                        data-href="{{ hasPermission('staff_manage', 'view') ? route('vendor.employee.view', [$e['id']]) : '#' }}">
                                        <th scope="row">{{ $k + $em->firstItem() }}</th>
                                        <td>
                                            <a
                                                href="{{ hasPermission('staff_manage', 'view') ? route('vendor.employee.view', [$e['id']]) : '#' }}">{{ $e['employee_id'] }}</a>
                                        </td>
                                        <td class="text-capitalize text-break"><a
                                                 href="{{ hasPermission('staff_manage', 'view') ? route('vendor.employee.view', [$e['id']]) : '#' }}">{{ $e['f_name'] }}
                                                {{ $e['l_name'] }}</a></td>
                                        @if($e['resignation'] || $e['terminate'])
                                            <td>{{ $e['resigned_email'] }} <span class="badge badge-soft-danger">Resigned</span></td>
                                            <td>{{ $e['resigned_phone'] }} <span class="badge badge-soft-danger">Resigned</span></td>
                                        @else
                                            <td>{{ $e['email'] }} </td>
                                            <td>{{ $e['phone'] }} </td>
                                        @endif
                                        <td>{{ $e->role ? $e->role['name'] : translate('messages.role_deleted') }}</td>
                                        <td>
                                            @if ($e['resignation'])
                                                <span class="status-badge error-badge">Resigned</span>
                                            @elseif($e['terminate'])
                                                <span class="status-badge error-badge">Terminated</span>
                                            @else
                                                @if ($e->status)
                                                    <span class="status-badge">Active</span>
                                                @else
                                                    <span class="status-badge error-badge">Inactive</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if (auth('vendor_employee')->id() != $e['id'])
                                                <div class="btn--container justify-content-center">
                                                    @if (hasPermission('staff_manage', 'view'))
                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                            href="{{ route('vendor.employee.view', [$e['id']]) }}"><i
                                                                class="tio-visible"></i></a>
                                                    @endif
                                                    @if (hasPermission('staff_manage', 'edit') && !$e['terminate'] && !$e['resignation'])
                                                        <a class="btn action-btn btn--warning btn-outline-warning"
                                                            href="{{ route('vendor.employee.edit', [$e['id']]) }}"
                                                            title="Edit Staff"><i class="tio-edit"></i>
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('staff_manage', 'delete'))
                                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                            href="javascript:" data-id="employee-{{ $e['id'] }}"
                                                            data-message="{{ translate('messages.Want_to_delete_this_role') }}"
                                                            title="{{ translate('messages.delete_Employee') }}"><i
                                                                class="tio-delete-outlined"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                                @if (hasPermission('staff_manage', 'delete'))
                                                    <form action="{{ route('vendor.employee.delete', [$e['id']]) }}"
                                                        method="post" id="employee-{{ $e['id'] }}">
                                                        @csrf @method('delete')
                                                    </form>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (count($em) !== 0)
                    <div class="card-footer">
                        <div class="page-area">
                            <table>
                                <tfoot>
                                    {!! $em->links() !!}
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
                @if (count($em) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('script_2')
<script>
    $(document).on('click', '.employee-row', function(e) {
        if ($(e.target).closest('.btn--container, a, button, form').length) return;
        var href = $(this).data('href');
        if (href && href !== '#') window.location.href = href;
    });
</script>
@endpush
