@extends('layouts.vendor.app')

@section('title', 'Rule Edit')

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
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Request Rule Edit</h1>
            </div>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->
        <div class="card 
            " style="width:fit-content;">
            <form class="" action="{{ route('vendor.account.request_rule_update') }}" method="post">
                @csrf
                <input type="hidden" name="id" value="{{ $rule->id }}">
                <div class=" p-2 d-flex gap-2 flex-wrap ">
                    <div class="zx9k2m  pt-1">
                        <h4 class="tx8q4k mt-3">Edit Request Rule</h4>

                        <div class="mb-3 ">
                            <label class="qp7w3n form-label">Select Form</label><br>
                            <select data-placeholder="Select Form" required name="form_type"
                                class="form-control hv4j8r js-select2-custom">
                                <option></option>
                                <option {{ $rule->form_type == 'master_ledger' ? 'selected' : '' }} value="master_ledger">
                                    Master Ledger</option>
                                <option {{ $rule->form_type == 'journal_entry' ? 'selected' : '' }} value="journal_entry">
                                    Journal Entry</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="qp7w3n">Select Department</label><br>
                            <select data-placeholder="Select Department" required id="dep_id4" name="department_id"
                                class="form-control hv4j8r js-select2-custom">
                                <option></option>
                                @foreach ($departments as $key => $dep)
                                    <option {{ $rule->department_id == $dep->id ? 'selected' : '' }}
                                        value="{{ $dep->id }}">{{ $dep->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="qp7w3n">Select Role</label><br>
                            <select data-placeholder="Select Role" required id="role_id4" name="role_id"
                                class="form-control hv4j8r js-select2-custom">
                                <option></option>
                                @foreach ($roles as $key => $dep)
                                    <option {{ $rule->role_id == $dep->id ? 'selected' : '' }} value="{{ $dep->id }}">
                                        {{ $dep->name }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div class="mb-4 ">
                            <label class="qp7w3n d-block mb-2">Set Permissions</label><br>
                            <div class="d-flex gap-2 flex-wrap">
                                @php
                                    $permissions = ['review', 'verify', 'reject', 'approve', 'forward', 'close'];
                                @endphp

                                @foreach ($permissions as $permission)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                            {{ in_array($permission, $rule->permissions) ? 'checked' : '' }}
                                            id="{{ $permission }}_check" value="{{ $permission }}"
                                            {{ $permission === 'review' ? 'checked' : '' }}>
                                        <label class="form-check-label lt6m9s" for="{{ $permission }}_check">
                                            {{ ucfirst($permission) }}
                                        </label>
                                    </div>
                                @endforeach

                            </div>

                        </div>


                    </div>
                    <div
                        class="zx9k2m send_to_section pt-1 {{ !in_array('forward', $rule->permissions) ? 'disabled' : '' }}">
                        <h4 class="tx8q4k mt-3">Send To (Next Level)</h4>
 
                        <div class="mb-3">
                            <label class="qp7w3n">Select Department</label><br>
                            <select data-placeholder="Select Department" multiple id="dep_id24" name="send_to_dep_id[]"
                                class="form-control hv4j8r js-select2-custom">
                                <option></option>
                                @foreach ($departments as $key => $dep)
                                    <option {{ in_array($dep->id, $rule->send_to_dep_id ?? []) ? 'selected' : '' }}
                                        value="{{ $dep->id }}">
                                        {{ $dep->title }} 
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="qp7w3n">Select Role</label><br>
                            <select data-placeholder="Select Role" id="role_id24" name="send_to_role_id[]"
                                class="form-control hv4j8r js-select2-custom" multiple>
                                <option></option>
                                @foreach ($roles as $key => $dep)
                                    <option {{ in_array($dep->id, $rule->send_to_role_id ?? []) ? 'selected' : '' }}
                                        value="{{ $dep->id }}">{{ $dep->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 ">
                            <label class="qp7w3n form-label">Select Person</label><br>
                            <select data-placeholder="Select Person" multiple name="employee_id[]" id="emp_select14"
                                class="form-control hv4j8r js-select2-custom">
                                <option></option>
                                @foreach ($employees as $key => $dep)
                                    <option {{ in_array($dep->id, $rule->send_to_employee_id ?? []) ? 'selected' : '' }}
                                        value="{{ $dep->id }}">{{ $dep->f_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end w-100 p-3">
                    <button class="btn btn-primary bn2x5p">Update Rule</button>
                </div>
            </form>
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
    </script>
@endpush
