@extends('layouts.admin.app')

@section('title', 'Billing Settings')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }

        .ck.ck-reset {
            width: 100% !important;
        }
    </style> 
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Billing Settings</h1>
        </div>
        <!-- End Page Header -->

        <div class="">
            {{-- Invoice Template --}}
            <div class="card mb-1">
                <div class="card-header">
                    <h2 class="card-title h4"><i class="tio-document-text mr-1"></i> Invoice Template</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.billing.save-invoice-template') }}" method="POST">
                        @csrf
                        <div class="d-flex gap-3 flex-wrap mb-3">
                            @php
                                $templates = [
                                    'service_n_manual'     => 'Template 1 (Classic)',
                                    'service_n_manual_new' => 'Template 2 (New)',
                                ];
                            @endphp
                            @foreach ($templates as $value => $label)
                                <label class="template-card {{ $invoice_template === $value ? 'selected' : '' }}"
                                       style="border:2px solid {{ $invoice_template === $value ? '#0f3460' : '#dee2e6' }};border-radius:8px;padding:10px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:500;">
                                    <input type="radio" name="invoice_template" value="{{ $value }}"
                                           {{ $invoice_template === $value ? 'checked' : '' }}
                                           style="accent-color:#0f3460;">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn--primary">Save</button>
                    </form>
                </div>
            </div>

            {{-- Invoice Prefix --}}
            <div class="card mb-1">
                <div class="card-header">
                    <h2 class="card-title h4"><i class="tio-settings mr-1"></i> Invoice Prefix</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.billing.save-invoice-prefix') }}" method="POST" class="d-flex align-items-end gap-3">
                        @csrf
                        <div class="form-group mb-0">
                            <label class="input-label">Prefix <small class="text-muted">(used in Invoice ID, e.g. <strong>{{ $invoice_prefix }}</strong>_26-27_1)</small></label>
                            <input type="text" name="prefix" class="form-control" value="{{ $invoice_prefix }}"
                                maxlength="10" placeholder="e.g. MSM" style="max-width:200px;" required>
                        </div>
                        <button type="submit" class="btn btn--primary mb-0">Save</button>
                    </form>
                </div>
            </div>

            @if (hasAnyModulePermission(['billing_bank_account']))
                <div class="card mb-1">
                    <div class="card-header d-flex justify-content-between">
                        <h2 class="card-title h4">
                            <i class="tio-account-circle"></i>
                            <span> {{ translate('messages.bank details') }}</span>

                        </h2>
                        @if (hasPermission('billing_bank_account', 'add'))
                            <div class="d-flex">
                                <button class="btn btn-primary d-none d-sm-block" type="button" data-toggle="modal"
                                    data-target="#accountModal">
                                    + Add Account
                                </button>
                                <button class="btn action-btn btn-outline-primary d-block d-sm-none" type="button"
                                    data-toggle="modal" data-target="#accountModal">
                                    +
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Body -->
                    @if (hasPermission('billing_bank_account', 'list'))

                        <div class="card-body row">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-responsive table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                                    "search": "#datatableSearch",
                                    "entries": "#datatableEntries",
                                    "isResponsive": false,
                                    "isShowPaging": false,
                                    "paging":false,
                                }'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0 text-center">{{ translate('messages.#') }}</th>
                                        <th class=" border-0 text-center">{{ translate('messages.bank_name') }}</th>
                                        <th class=" border-0 text-center">{{ translate('messages.account_holder_name') }}
                                        </th>
                                        <th class=" border-0 text-center">{{ translate('messages.account_number') }}</th>
                                        <th class=" border-0 text-center">{{ translate('messages.ifsc_code') }}</th>
                                        <th class=" border-0 text-center">{{ translate('messages.upi_qr_code') }}</th>
                                        <th class=" border-0 text-center">{{ translate('messages.action') }}</th>
                                    </tr>
                                </thead>

                                <tbody id="table-div">

                                    @foreach ($accounts as $key => $account)
                                        <tr>
                                            <td class="text-center">{{ $key + 1 }}</td>
                                            <td class="text-center">{{ $account->bank_name }}</td>
                                            <td class="text-center">{{ $account->account_holder_name }}</td>
                                            <td class="text-center">{{ $account->account_number }}</td>
                                            <td class="text-center">{{ $account->ifsc_code }}</td>
                                            <td class="text-center">
                                                @if ($account->upi_qr_code)
                                                    <span class="text-success">Available</span>
                                                @else
                                                    <span class="text-danger">Not Available</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (hasPermission('billing_bank_account', 'delete'))
                                                    <a data-id="banner-{{ $account['id'] }}"
                                                        data-message="{{ translate('Want to delete this account ?') }}"
                                                        title="{{ translate('messages.delete_account') }}"
                                                        href="javascript:;" type="button"
                                                        class="btn action-btn btn--danger btn-outline-danger form-alert"><i
                                                            class="tio-delete-outlined"></i></a>
                                                    <form
                                                        action="{{ route('admin.business-settings.delete-account', [$account->id]) }}"
                                                        method="get" id="banner-{{ $account['id'] }}">
                                                        @csrf @method('get')
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody> 
                            </table>
                        </div>
                    @endif
                </div>
            @endif
            @if (hasAnyModulePermission(['billing_signatures']))

                <div class="card mb-1">
                    <div class="card-header  d-flex justify-content-between">
                        <h2 class="card-title h4">
                            <i class="tio-pen"></i>
                            <span> {{ translate('messages.signatures') }}</span>
                        </h2>
                        @if (hasPermission('billing_signatures', 'add'))
                            <button class="btn btn-primary d-none d-sm-block" type="button" data-toggle="modal"
                                data-target="#addSignModal">
                                + Add New Sign</button>
                            <button class="btn action-btn btn-outline-primary d-block d-sm-none" type="button"
                                data-toggle="modal" data-target="#addSignModal">
                                + </button>
                            @include('admin-views.billing.partials._add_new_sign')
                        @endif
                    </div>
 
                    <!-- Body -->
                    @if (hasPermission('billing_signatures', 'list'))

                        <div class="card-body row">

                            <div class="col-md-12 mb-3">



                                <table id="columnSearchDatatable"
                                    class="table table-borderless table-responsive  table-thead-bordered table-nowrap table-align-middle card-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Signature By</th>
                                            <th>Signature</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody id="set-rows">
                                        @foreach ($signatures as $key => $sign)
                                            <tr>
                                                <th>{{ $key + 1 }}</th>
                                                <td>
                                                    <span class="d-block font-size-sm text-body">
                                                        {{ $sign->staff_id == 0 ? 'Self' : $sign->adminEmployee?->f_name . ' ' . $sign->adminEmployee?->l_name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <img class=" w-auto h--50px rounded mr-2 onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($sign['image'], asset('storage/app/public/store/signature/') . '/' . $sign['image'], asset('public/assets/admin/img/900x400/img1.jpg'), 'store/signature/') }}"
                                                        data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                                        alt="{{ $sign->adminEmployee?->f_name }} signature">
                                                </td>
                                                <td>
                                                    @if (hasPermission('billing_signatures', 'delete'))
                                                        <div class="btn--container justify-content-center">
                                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                                data-id="sign-{{ $sign['id'] }}"
                                                                data-message="{{ translate('Want to delete this sign ?') }}"
                                                                href="javascript:"
                                                                title="{{ translate('messages.delete_sign') }}"><i
                                                                    class="tio-delete-outlined"></i></a>
                                                            <form
                                                                action="{{ route('admin.business-settings.signature.delete', [$sign['id']]) }}"
                                                                method="get" id="sign-{{ $sign['id'] }}">
                                                            </form>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach


                                    </tbody>
                                </table>
                            </div>

                        </div>
                    @endif

                </div>
            @endif
                @if (hasAnyModulePermission(['billing_tnc']))

                    <div class="card mb-1">
                        <div class="card-header">
                            <h2 class="card-title h4">
                                <i class="tio-document-outlined"></i>
                                <span>{{ translate('messages.terms_and_conditions') }}</span>
                            </h2>
                            @if (hasPermission('billing_tnc', 'add'))

                                <button class="btn btn-primary d-none d-sm-block" type="button" data-toggle="modal"
                                    data-target="#addTNCModal">
                                    + Add Terms and Conditions</button>
                                <button class="btn action-btn btn-outline-primary d-block d-sm-none" type="button"
                                    data-toggle="modal" data-target="#addTNCModal">
                                    + </button>
                            @endif
                        </div>
                        @if (hasPermission('billing_tnc', 'list'))

                            <!-- Body -->
                            <div class="card-body row">

                                <div class="table-responsive datatable-custom" id="table-div">
                                    <table id="columnSearchDatatable"
                                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>{{ translate('messages.sl') }}</th>
                                                <th>{{ translate('messages.For') }}</th>
                                                <th>{{ translate('messages.Content') }}</th>
                                                <th class="text-center">Default</th>
                                                <th class="text-center">{{ translate('messages.action') }}</th>
                                            </tr>
                                        </thead>

                                        <tbody id="set-rows">
                                            @foreach ($tncs as $key => $tnc)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                        <span class="d-block font-size-sm text-body for_{{ $tnc->id }}">
                                                            {{ $tnc->tnc_for }}
                                                        </span>
                                                    </td>
                                                    <td class="content_{{ $tnc->id }}">{!! $tnc->content !!}</td>

                                                    <td class="text-center">
                                                        @if ($tnc->is_default)
                                                            <span class="badge badge-success px-3 py-2">&#10003; Default</span>
                                                        @else
                                                            @if (hasPermission('billing_tnc', 'edit'))
                                                                <form action="{{ route('admin.business-settings.tnc.set-default', $tnc->id) }}" method="POST" style="display:inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-xs btn-outline-success">
                                                                        Set as Default
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <div class="btn--container justify-content-center">
                                                            @if (hasPermission('billing_tnc', 'edit'))
                                                                <a type="button" data-id="{{ $tnc->id }}"
                                                                    class="btn btn-sm btn--primary btn-outline-primary action-btn edit_tnc"
                                                                    data-toggle="modal" data-target="#tncEditModal"
                                                                    title="{{ translate('messages.edit_tnc') }}"><i class="tio-edit"></i>
                                                                </a>
                                                            @endif
                                                            @if (hasPermission('billing_tnc', 'delete'))
                                                                <a class="btn btn-sm btn--danger btn-outline-danger action-btn form-alert"
                                                                    data-id="tnc-{{ $tnc['id'] }}"
                                                                    data-message="{{ translate('Want to delete this Terms and Conditions ?') }}"
                                                                    href="javascript:"
                                                                    title="{{ translate('messages.delete_tnc') }}"><i class="tio-delete-outlined"></i>
                                                                </a>
                                                                <form action="{{ route('admin.business-settings.tnc.delete', [$tnc['id']]) }}"
                                                                    method="get" id="tnc-{{ $tnc['id'] }}">
                                                                    @csrf @method('get')
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if (hasPermission('billing_tnc', 'edit'))
                                        <div class="modal fade" id="tncEditModal" tabindex="-1"
                                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="exampleModalLabel">Edit Terms And
                                                            Conditions
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form class="customer_add_form" enctype="multipart/form-data"
                                                            class="w-100"
                                                            action="{{ route('admin.business-settings.tnc.update') }}"
                                                            method="post">
                                                            @csrf
                                                            <div class="">
                                                                <input type="hidden" name="tnc_id" class="tnc_edit_id">
                                                                <div class="form-row ">
                                                                    <label for="">For</label>
                                                                    <input required type="text" name="for"
                                                                        class="form-control edit_for"
                                                                        placeholder="Ex: Staff">
                                                                </div>
                                                                <div class="form-row ">
                                                                    <textarea name="tnc_content" id="ckeditor2"></textarea>
                                                                </div>
                                                                <div class="d-flex justify-content-end w-100">
                                                                    <button class="btn btn-primary">Save</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div> 
                                        </div>
                                    @endif
                                    @if (count($tncs) === 0)
                                        <div class="empty--data">
                                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                                alt="public">
                                            <h5>
                                                {{ translate('no_data_found') }}
                                            </h5>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

        </div>
    </div>
    @if (hasPermission('billing_bank_account', 'add'))
        @include('admin-views.billing.partials._add_bank_account')
    @endif
    @if (hasPermission('billing_tnc', 'add'))
        @include('admin-views.billing.form_modals.tnc_add')
    @endif
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>

    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'));
        ClassicEditor
            .create(document.querySelector('#ckeditor'));
        ClassicEditor
            .create(document.querySelector('#editor2'));
        $("#coverImageUpload").change(function() {
            readURL(this, "coverImageViewer");
        });
        $("#customFileEg1").change(function() {
            readURL(this, "viewer");
        });
 
        let ckeditorInstance = null;

        if (!ckeditorInstance) {
            ClassicEditor
                .create(document.querySelector('#ckeditor2'))
                .then(editor => {
                    ckeditorInstance = editor;
                })
                .catch(error => {
                    console.error(error);
                });
        }


        $(".edit_tnc").on('click', function() {
            var id = $(this).attr('data-id');
            var tnc_for = $('.for_' + id).text().trim();
            var content = $('.content_' + id).html();

            if (ckeditorInstance) {
                ckeditorInstance.setData(content);
            }

            $('.edit_for').val(tnc_for);
            $('.tnc_edit_id').val(id);
        });
    </script>
@endpush
