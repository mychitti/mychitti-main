@extends('layouts.vendor.app')

@section('title', 'Approvals')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .zx9k2m {
            max-width: 400px;
            margin: 0 auto;
            padding-top: 0;
            width: 370px;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 8px rgb(0 0 0 / 15%);
        }

        .zx9k2m.disabled {
            pointer-events: none;
            position: relative;

        }

        .zx9k2m.disabled::before {
            content: '';
            z-index: 894394;
            position: absolute;
            background: #ffffffb2;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;

        }

        .qp7w3n {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .hv4j8r {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-size: 14px;
        }

        .lt6m9s {
            font-size: 14px;
            color: #333;
        }

        .bn2x5p {
            {{-- width: 100%; --}} padding: 12px;
            font-size: 15px;
            font-weight: 500;
        }

        .tx8q4k {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #333;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid ">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Request Rules</h1>
            </div>
            <div class="page-header-select-wrapper">
                <button type="button" class="btn btn-primary mx-1" data-toggle="modal" data-target="#ruleModal">
                    + Create Request Rule
                </button>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="table-responsive datatable-custom">
            <table id="columnSearchDatatable"
                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{ translate('sl') }}</th>
                        <th class="border-0">Form</th>
                        <th class="border-0">Department</th>
                        <th class="border-0">Role</th>
                        <th class="border-0">Permissions</th>
                        <th class="border-0">Next Level</th>
                        <th class="border-0">Action</th>
                    </tr>
                </thead>

                <tbody id="set-rows">
                    @foreach ($rules as $rule)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ translate($rule->form_type) }}
                                </span>
                            </td>
                            <td>
                                {{ $rule->department?->title }}
                            </td>
                            <td>
                                {{ $rule->role?->name }}
                            </td>
                            <td>
                                @if ($rule->permissions)
                                    @foreach ($rule->permissions as $key => $value)
                                        <span class="badge badge-soft-light text-dark border">
                                            {{ $value }}
                                        </span>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                <b>Department:</b>
                                @foreach ($rule->next_level_deps as $dep)
                                    {{ $dep->title }}@if (!$loop->last)
                                        ,
                                    @endif
                                @endforeach
                                <br>

                                <b>Role:</b>
                                @foreach ($rule->next_level_roles as $role)
                                    {{ $role->name }}@if (!$loop->last)
                                        ,
                                    @endif
                                @endforeach
                                <br>

                                <b>Person:</b>
                                @foreach ($rule->next_level_emps as $emp)
                                    {{ $emp->f_name }} {{ $emp->l_name }}@if (!$loop->last)
                                        ,
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                        aria-expanded="false">
                                        <img style="    width: 24px; filter: contrast(0);"
                                            src = "{{ asset('storage/app/public/util/10025520.png') }}" alt="action">
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="{{route('vendor.account.request_rule_edit', [$rule['id'] ])}}"
                                            class="dropdown-item  text--primary edit_rule_btn" data-id="{{ $rule['id'] }}" title="Edit"><i
                                                class="tio-edit"></i>
                                            Edit</a>
                                        </a>

                                        <a class="dropdown-item form-alert text-danger" href="javascript:;"
                                            data-id="customer-{{ $rule['id'] }}"
                                            data-message="{{ translate('messages.Want to delete this rule') }}"
                                            title="{{ translate('messages.delete_rule') }}"><i
                                                class="tio-delete-outlined"></i>
                                            Delete</a>
                                    </div>
                                    <form action="{{ route('vendor.account.request_rule_delete', [$rule->id]) }}"
                                        method="post" id="customer-{{ $rule['id'] }}">
                                        @csrf @method('get')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if (count($rules))
                <hr>
            @else
                <div class="page-area">
                </div>
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
    </div>
    <div class="modal fade" id="editRuleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body rule_form_html">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    {{-- @include('vendor-views/account/approval-edit', ['rule' => $rule]) --}}

    <!-- Button trigger modal -->

    <div class="modal fade" id="ruleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <form class="" action="{{ route('vendor.account.request_rule_store') }}" method="post">
                        @csrf
                        <div class=" p-2 d-flex gap-2 flex-wrap ">
                            <div class="zx9k2m  pt-1">
                                <h4 class="tx8q4k mt-3">Create Request Rule</h4>

                                <div class="mb-3 ">
                                    <label class="qp7w3n form-label">Select Form</label><br>
                                    <select data-placeholder="Select Form" required name="form_type"
                                        class="form-control hv4j8r js-select2-custom">
                                        <option></option>
                                        <option value="master_ledger">Master Ledger</option>
                                        <option value="journal_entry">Journal Entry</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="qp7w3n">Select Department</label><br>
                                    <select data-placeholder="Select Department" required id="dep_id"
                                        name="department_id" class="form-control hv4j8r js-select2-custom">
                                        <option></option>
                                        @foreach ($departments as $key => $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="qp7w3n">Select Role</label><br>
                                    <select data-placeholder="Select Role" required id="role_id" name="role_id"
                                        class="form-control hv4j8r js-select2-custom">
                                        <option></option>
                                        @foreach ($roles as $key => $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="mb-4 ">
                                    <label class="qp7w3n d-block mb-2">Set Permissions</label><br>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @php
                                            $permissions = [
                                                'review',
                                                'verify',
                                                'reject',
                                                'approve',
                                                'forward',
                                                'close',
                                            ];
                                        @endphp

                                        @foreach ($permissions as $permission)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                                    id="{{ $permission }}_check" value="{{ $permission }}"
                                                    {{ $permission === 'review' ? 'checked' : '' }}>
                                                <label class="form-check-label lt6m9s" for="{{ $permission }}_check">
                                                    {{ ucfirst($permission) }}
                                                </label>
                                            </div>
                                        @endforeach

                                    </div>

                                </div>

                                {{-- <button class="btn btn-primary bn2x5p">Save Rule</button> --}}

                            </div>
                            <div class="zx9k2m send_to_section pt-1 disabled">
                                <h4 class="tx8q4k mt-3">Send To (Next Level)</h4>

                                <div class="mb-3">
                                    <label class="qp7w3n">Select Department</label><br>
                                    <select data-placeholder="Select Department" multiple id="dep_id2"
                                        name="send_to_dep_id[]" class="form-control hv4j8r js-select2-custom">
                                        <option></option>
                                        @foreach ($departments as $key => $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="qp7w3n">Select Role</label><br>
                                    <select data-placeholder="Select Role" id="role_id2" name="send_to_role_id[]"
                                        class="form-control hv4j8r js-select2-custom" multiple>
                                        <option></option>
                                        @foreach ($roles as $key => $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3 ">
                                    <label class="qp7w3n form-label">Select Person</label><br>
                                    <select data-placeholder="Select Person" multiple name="employee_id[]"
                                        id="emp_select1" class="form-control hv4j8r js-select2-custom">
                                        <option></option>
                                        @foreach ($employees as $key => $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->f_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end w-100 p-3">
                            <button class="btn btn-primary bn2x5p">Save Rule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $("#forward_check").on('change', function() {
            if ($(this).prop('checked') == true) {
                $(".send_to_section").removeClass('disabled')
                {{-- $("#dep_id2").attr('required', true)
                $("#role_id2").attr('required', true)
                $("#emp_select").attr('required', true) --}}
            } else {
                $(".send_to_section").addClass('disabled')
                {{-- $("#dep_id2").removeAttr('required')
                $("#role_id2").removeAttr('required')
                $("#emp_select").removeAttr('required') --}}
            }
        })
        $("#role_id2").on('change', function() {
            fetchEmployees();
        })
        $("#dep_id2").on('change', function() {
            fetchEmployees();
        })

        function fetchEmployees() {
            let role = $("#role_id2").val()
            let dep = $("#dep_id2").val()

            if (!role || !dep) return false

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.account.fetchEmployees') }}',
                data: {
                    dep: dep,
                    role: role,
                },
                success: function(data) {
                    console.log(data)
                    $('#emp_select').empty();

                    $('#emp_select').append('<option value=""></option>');

                    $.each(data.emp, function(key, emp) {
                        $('#emp_select').append(
                            $('<option>', {
                                value: emp.id,
                                text: emp.f_name + ' ' + emp.l_name
                            })
                        );
                    });

                    $('#emp_select').select2();
                },

            });
        }
        {{-- $(".edit_rule_btn").on('click', function() {
            var id = $(this).attr('data-id')
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: "{{ route('vendor.account.request_rule_edit') }}",
                type: 'POST',
                data: {
                    id: id,
                },
                success: function(data) {
                    console.log(data)
                    $('.rule_form_html').html(data);
                }
            });
        }) --}}
    </script>
@endpush
