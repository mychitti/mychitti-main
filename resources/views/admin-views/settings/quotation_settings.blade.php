@extends('layouts.admin.app')

@section('title', 'Quotation Settings')

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
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Quotation Settings</h1>
        </div>
        <!-- End Page Header -->

        <div class="">
            @if (hasAnyModulePermission(['quotation_bank_account']))

                <div class="card mb-1">
                    <div class="card-header d-flex justify-content-between">
                        <h2 class="card-title h4">
                            <i class="tio-account-circle"></i>
                            <span> {{ translate('messages.bank details') }}</span>

                        </h2>

                        <div class="d-flex">
                            @if (hasPermission('quotation_bank_account', 'add'))
                                <button class="btn btn-primary d-none d-sm-block" type="button" data-toggle="modal"
                                    data-target="#accountModal">+
                                    Add Account</button>

                                <button class="btn btn-outline-primary action-btn d-block d-sm-none" type="button"
                                    data-toggle="modal" data-target="#accountModal">+
                                </button>
                            @endif
                        </div>
                    </div>
                    @if (hasPermission('quotation_bank_account', 'list'))

                        <!-- Body -->
                        <div class="card-body row">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-responsive  table-thead-bordered table-nowrap table-align-middle card-table"
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
                                                @if (hasPermission('quotation_bank_account', 'delete'))
                                                    <a data-id="banner-{{ $account['id'] }}"
                                                        data-message="{{ translate('Want to delete this account ?') }}"
                                                        title="{{ translate('messages.delete_account') }}"
                                                        href="javascript:;" type="button"
                                                        class="btn action-btn btn--danger btn-outline-danger form-alert"><i
                                                            class="tio-delete-outlined"></i></a>
                                                @endif
                                            </td>
                                            @if (hasPermission('quotation_bank_account', 'delete'))
                                                <form
                                                    action="{{ route('admin.business-settings.delete-account', [$account->id]) }}"
                                                    method="get" id="banner-{{ $account['id'] }}">
                                                    @csrf @method('get')
                                                </form>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
            @if (hasAnyModulePermission(['quotation_sign']))

                <div class="card mb-1">
                    <div class="card-header  d-flex justify-content-between">
                        <h2 class="card-title h4">
                            <i class="tio-pen"></i>
                            <span> {{ translate('messages.signatures') }}</span>
                        </h2>
                        @if (hasPermission('quotation_sign', 'add'))
                            <button class="btn btn-primary d-none d-sm-block" type="button" data-toggle="modal"
                                data-target="#addSignModal">
                                + Add New Sign</button>
                            <button class="btn btn-outline-primary action-btn d-block d-sm-none" type="button"
                                data-toggle="modal" data-target="#addSignModal">
                                +</button>
                            @include('admin-views/billing/partials/_add_new_sign')
                        @endif
                    </div>

                    @if (hasPermission('quotation_sign', 'delete'))
                        <!-- Body -->
                        <div class="card-body row">

                            <div class="col-md-12 mb-3">
                                <table id="columnSearchDatatable"
                                    class="table table-borderless  table-responsive  table-thead-bordered table-nowrap table-align-middle card-table">
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
                                                <td>{{$sign->adminEmployee?->f_name . ' ' . $sign->adminEmployee?->l_name }}
                                                </td>
                                                <td>
                                                    <img class=" w-auto h--50px rounded mr-2 onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($sign['image'], asset('storage/app/public/store/signature/') . '/' . $sign['image'], asset('public/assets/admin/img/900x400/img1.jpg'), 'store/signature/') }}"
                                                        data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                                        alt="{{ $sign->adminEmployee?->f_name }} signature">
                                                </td>
                                                <td>
                                                    <div class="btn--container justify-content-center">
                                                        @if (hasPermission('quotation_sign', 'delete'))
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
                                                        @endif
                                                    </div>
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

            @if (hasPermission('quotaiton_manage', 'settings'))
                <div class="card mb-1">
                    <div class="card-header">
                        <h2 class="card-title h4">
                            <i class="tio-settings"></i>
                            <span>{{ translate('messages.configuration') }}</span>
                        </h2>
                    </div>
                    <form class="w-100 p-0 " enctype="multipart/form-data"
                        action="{{ route('admin.quotation.config.save') }}" method="post">
                        @csrf
                        <!-- Body -->
                        <div class="card-body row align-items-end">
                            <div class="col-sm-5 p-2 mb-3">
                                <div class="form-group mb-0 ">
                                    <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                        for="quotation_footer_line">
                                        <span>Quotation Footer Line <span class="form-label-secondary" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.if enabled, this line will show in invoice') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                        <input type="hidden" name="quotation_footer_line" value="0">

                                        <input type="checkbox" class="toggle-switch-input" name="quotation_footer_line"
                                            id="quotation_footer_line" value="1"
                                            {{ $storeConfig?->quotation_footer_line ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                    <input type="text" readonly
                                        value="This is a computer-generated invoice. No signature required."
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-4 p-2 mb-3">
                                <div class="form-group mb-0 ">
                                
                                    <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                        for="jrsdctn_quote_status">
                                        <span>Jurisdiction Statement<span class="form-label-secondary"
                                                data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.This line shows in invoice footer') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                        <input type="hidden" name="jrsdctn_quote_status" value="0">

                                        <input type="checkbox" class="toggle-switch-input" name="jrsdctn_quote_status"
                                            id="jrsdctn_quote_status" value="1"
                                            {{ $storeConfig?->jrsdctn_quote_status ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                    <input type="text" id="jrsdctn_quote_statement" name="jrsdctn_quote_statement"
                                        value="{{ $storeConfig?->jrsdctn_quote_statement }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button style="float:right" class="btn btn-primary my-2">Update</button>
                            </div>

                        </div>
                    </form>

                </div>
            @endif
            @if (hasAnyModulePermission(['quotation_tnc']))

                <div class="card mb-1">
                    <div class="card-header">
                        <h2 class="card-title h4">
                            <i class="tio-document-outlined"></i>
                            <span>{{ translate('messages.terms_and_conditions') }}</span>
                        </h2>
                        @if (hasPermission('quotation_tnc', 'add'))
                            <button class="btn btn-primary d-none d-sm-block" type="button" data-toggle="modal"
                                data-target="#addTNCModal">
                                + Add Terms and Conditions</button>
                            <button class="btn action-btn btn-outline-primary d-block d-sm-none" type="button"
                                data-toggle="modal" data-target="#addTNCModal">
                                + </button>
                        @endif
                    </div>
                    @if (hasPermission('quotation_tnc', 'list'))
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

                                                <td>
                                                    <div class="btn--container justify-content-center">
                                                        @if (hasPermission('quotation_tnc', 'edit'))
                                                            <a type="button" data-id="{{ $tnc->id }}"
                                                                class="btn btn-sm btn--primary btn-outline-primary action-btn edit_tnc"
                                                                data-toggle="modal" data-target="#tncEditModal"
                                                                title="{{ translate('messages.edit_tnc') }}"><i
                                                                    class="tio-edit"></i>
                                                            </a>
                                                        @endif
                                                        @if (hasPermission('quotation_tnc', 'delete'))
                                                            <a class="btn btn-sm btn--danger btn-outline-danger action-btn form-alert"
                                                                data-id="tnc-{{ $tnc['id'] }}"
                                                                data-message="{{ translate('Want to delete this Terms and Conditions ?') }}"
                                                                href="javascript:"
                                                                title="{{ translate('messages.delete_tnc') }}"><i
                                                                    class="tio-delete-outlined"></i>
                                                            </a>
                                                            <form
                                                                action="{{ route('admin.business-settings.tnc.delete', [$tnc['id']]) }}"
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

                                @if (count($tncs) === 0)
                                    <div class="empty--data">
                                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                            alt="public">
                                        <h5>
                                            {{ translate('no_data_found') }}
                                        </h5>
                                    </div>
                                @endif
                                @if (hasPermission('quotation_tnc', 'edit'))
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
                                                                    class="form-control edit_for" placeholder="Ex: Staff">
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
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
    @include('admin-views/billing/partials/_add_bank_account')
    @include('admin-views/form_modals/tnc_add')
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
