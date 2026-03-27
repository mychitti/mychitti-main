@extends('layouts.admin.app')

@section('title', 'Account Settings')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* otp element styling  */
        .otp-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-container p {
            margin-bottom: 20px;
            color: #666;
        }

        .otp-form {
            display: flex;
            justify-content: space-between;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i>Account Settings</h1>
            </div>
            <div class="page-header-select-wrapper">
                <div class="d-flex gap-1">
                    @if (hasPermission('settings_common', 'reset'))
                        <button type="button" class="btn btn-outline-danger reset_acc_btn">
                            Reset Accounts Management
                        </button>
                        <button class="send_otp h-0 w-0 p-0 border-0" data-toggle="modal"
                            data-target="#resetOtpModal"></button>
                    @endif
                </div>


            </div>
        </div>
        @if (hasPermission('settings_common', 'edit'))
            <div class="main-content">
                <form class="" enctype="multipart/form-data" method="post"
                    action="{{ route('admin.account.setting.all_update') }}">
                    @csrf

                    <div class="col-md-12 p-2 mb-1 row ">


                        <div class="col-md-3 p-2 mb-1">
                            <div class="form-group mb-0 ">
                                <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                    for="">
                                    <span>Resubmits per Request Form</span>
                                </label>
                                <input required value="{{ $storeConfig?->resubmit_per_req_form ?? '' }}"
                                    name="resubmit_per_req_form" type="number" placeholder="Ex: 3" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3 p-2 mb-1">
                            <div class="form-group mb-0 ">
                                <label for="">
                                    <span>Monthly Maintenance Requests</span>
                                </label>
                                <select name="monthly_maintnnce_req" class="form-control" id="">
                                    <option
                                        {{ !$storeConfig || $storeConfig?->monthly_maintnnce_req == 'manual_pay' ? 'selected' : '' }}
                                        value="manual_pay">Manual marking</option>
                                    <option {{ $storeConfig?->monthly_maintnnce_req == 'auto_pay' ? 'selected' : '' }}
                                        value="auto_pay">Auto mark as paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12 p-2 " style="display: flex;align-items: end;justify-content: end;">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>
    @if (hasPermission('settings_common', 'edit'))

        <div class="card col-md-4">
            <div class="card-body p-0 pt-2">
                <h3>Expense Types</h3>
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
                                <th class="border-0">{{ translate('messages.Name') }}</th>
                                <th class="border-0">{{ translate('messages.Action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($expense_types as $k => $value)
                                <tr>
                                    <td>{{ $k + 1 }}</td>
                                    <td>{{ $value['name'] }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <img style="    width: 24px; filter: contrast(0);"
                                                    src = "{{ asset('storage/app/public/util/10025520.png') }}"
                                                    alt="action">
                                            </button>
                                            <div class="dropdown-menu">

                                                <a type="button" class="dropdown-item  text--success edit_acc_opt_btn"
                                                    data-id="{{ $value->id }}" type="button"
                                                    data-name="{{ $value->name }}" data-toggle="modal"
                                                    data-target="#expTypeEditModal" title="Edit"><i
                                                        class="tio-share-vs"></i>
                                                    Edit</a>


                                                <a class="dropdown-item form-alert text-danger" href="javascript:;"
                                                    data-id="customer-{{ $value['id'] }}"
                                                    data-message="{{ translate('messages.Want to delete this option') }}"
                                                    title="{{ translate('messages.delete_option') }}"><i
                                                        class="tio-delete-outlined"></i>
                                                    Delete</a>
                                            </div>
                                            <form
                                                action="{{ route('admin.account.setting.account-option-delete', [$value->id]) }}"
                                                method="post" id="customer-{{ $value['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($expense_types) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
        <div class="modal fade" id="expTypeEditModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Option</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body ">
                        <form id="accountForm" enctype="multipart/form-data"
                            action="{{ route('admin.account.setting.account-option-update') }}" method="post">
                            @csrf
                            <input type="hidden" name="id" class="acc_id">

                            <div class="form-group">
                                <label>Name</label>
                                <input class="form-control edit_name" type="text" name="name" placeholder="Name"
                                    required>
                            </div>
                            <div class="w-100 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if (hasPermission('settings_common', 'reset'))
        <div class="modal fade" id="resetOtpModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Verify OTP</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body ">
                        <div class="container py-5">
                            <h2 class="text-center">Enter OTP</h2>
                            <div class="p-5 bg-light rounded" style="max-width: 550px; margin: 0 auto;">
                                <div class="row ">
                                    <form class="otpForm" style="margin: 0 auto;"
                                        action="{{ route('admin.account.reset_accounts_module') }}" method="post">
                                        <p>OTP sent to store's registered phone number.</p>
                                        @csrf

                                        <div class="d-flex justify-content-center w-100">
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                        </div>
                                        <input type="hidden" value="1" name="ajax" />
                                        <button type="submit"
                                            class="btn btn-lg btn-block btn-danger mt-3">Proceed</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script_2')
    <script>
        $(document).on('click', ".edit_acc_opt_btn", function() {
            var name = $(this).attr('data-name')
            var id = $(this).attr('data-id')
            $(".acc_id").val(id)
            $(".edit_name").val(name)
        })

        $(".reset_acc_btn").on('click', function() {
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: 'Resetting account management will permanently delete all data, including the master ledger, journal ledger, daybook, cashbook entries, and all other accounting records. All account settings will also be restored to default. This action is irreversible.',
                type: 'error',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('.send_otp').click()
                }
            })
        })

        $(".send_otp").on('click', function(e) {
            e.preventDefault();
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: "{{ route('admin.account.send_otp') }}",
                success: function(data) {
                    console.log(data);
                    if (data.status) {

                    }
                },
            });
        })
        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });
    </script>
@endpush
