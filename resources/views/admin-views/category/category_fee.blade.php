@extends('layouts.admin.app')

@section('title', translate('messages.Add new category'))

@push('css_or_js')
    <style>
        .select2-selection__choice {
            padding: 2px 5px;
            font-size: 14px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            border-right: none !important;
        }

        .select2-selection .select2-selection--multiple {
            border: 1px solid #e6e6e6;
            padding: 4px !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border: 1px solid #e6e6e6;
            padding: 4px !important;
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid #dedede !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border: 1px solid #dedede !important;
        }

        .select2-selection {
            height: 200px !important;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--20" alt="">
                </span>
                <span>
                    {{ translate('add_fee_category') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.category.store-fee') }}" id="cat_form" method="post" enctype="multipart/form-data">
                    @csrf
                    <input name= 'id' type="hidden">
                    <div class="row">
                        <div class="form-group col-4">
                            <label class="input-label"
                                for="exampleFormControlInput21">{{ translate('messages.name') }}</label>
                            <input type="text" name="name" class="form-control" placeholder="Vegetables"
                                value="{{ old('name') }}" maxlength="191">
                        </div>
                        <div class="form-group col-4">
                            <label class="input-label"
                                for="exampleFormControlInput21">{{ translate('messages.fee_percentage') }}(with
                                GST)</label>
                            <input type="number" name="fee_percent" class="form-control" placeholder="Ex: 18"
                                value="{{ old('fee_percent') }}" maxlength="191">
                        </div>
                        <div class="form-group col-4">
                            <label class="input-label"
                                for="exampleFormControlInput21">{{ translate('messages.payment_gateway_fees') }}(with
                                GST)</label>
                            <input type="number" name="payment_gateway_fees" class="form-control" placeholder="Ex: 10"
                                value="{{ old('payment_gateway_fees') }}" maxlength="191">
                        </div>
                    </div>

                    <div class="btn--container justify-content-end mt-3">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button id="submit_btn" type="submit"
                            class="btn btn--primary">{{ translate('messages.add') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.category_list') }}<span
                            class="badge badge-soft-dark ml-2" id="itemCount">{{ count($fees) }}</span></h5>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-align-middle"
                        data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">{{ translate('messages.id') }}</th>
                                <th class="border-0 w--1">{{ translate('messages.name') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.platform_fee') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.payment_gateway_fee') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.total') }}(%)</th>
                                <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($fees as $key => $fee)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $fee->id }}</td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ Str::limit($fee->name, 20, '...') }}
                                        </span>
                                    </td>
                                    <td> <span class="d-block font-size-sm text-center">{{ $fee->platform_fee }} </span>
                                    </td>
                                    <td> <span class="d-block font-size-sm text-center">
                                            {{ $fee->payment_gateway_fee }}</span></td>
                                    <td> <span class="d-block font-size-sm text-center"> {{ $fee->total_fee }}</span></td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--primary btn-outline-primary"
                                                onClick="fillvalues({{ $fee->id }})"
                                                title="{{ translate('messages.edit_fee') }}"> <i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $fee->id }}"
                                                data-message="{{ translate('Want to delete this fee category') }}"
                                                title="{{ translate('messages.delete_fee_category') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('admin.category.delete-fee', [$fee->id]) }}" method="get"
                                                id="category-{{ $fee->id }}">
                                                @csrf 
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($fees) !== 0)
                <hr>
            @endif

            @if (count($fees) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-tags').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: "Start Typing..",
                allowClear: true,
            });
        });

        function fillvalues(feeId) {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('admin.category.get-fee-values') }}",
                data: {
                    id: feeId
                },
                success: function(data) {
                    console.log(data)
                    $('#cat_form').attr('action', data.url)
                    $('input[name=id]').val(data.id)
                    $('input[name=name]').val(data.name)
                    $('input[name=fee_percent]').val(data.platform_fee)
                    $('input[name=payment_gateway_fees]').val(data.payment_gateway_fee)
                    $('#reset_btn').hide();
                    $('#submit_btn').text('Update')
                    $('html, body').animate({
                        scrollTop: 0
                    }, 'slow');

                },
                complete: function() {
                    $('#loading').hide()
                }
            });

        }
    </script>

    <script>
        $(document).on('change', '.warnig-charge-btn', function(e) {

            $('.warnig-charge-btn').prop('checked', false);
            swal({
                //text: message,
                title: 'Please Add Lead Charges First',
                type: 'warning',
                confirmButtonColor: '#FC6A57',
                confirmButtonText: 'Add Now',
            }).then(function(isConfirm) {
                console.log(isConfirm)
                if (isConfirm.value) {
                    window.location.href = '{{ route('admin.service.lead-charge') }}';
                } else {


                }
            })

        })
    </script>

    <script src="{{ asset('public/assets/admin') }}/js/view-pages/category-index.js"></script>
    <script>
        "use strict";
        $('.location-reload-to-category').on('click', function() {
            const url = $(this).data('url');
            let nurl = new URL(url);
            nurl.searchParams.delete('search');
            location.href = nurl;
        });

        $("#customFileEg1").change(function() {
            readURL(this);
            $('#viewer').show(1000)
        });
        $('#reset_btn').click(function() {
            $('#exampleFormControlSelect1').val(null).trigger('change');
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload-img.png') }}");
        })
    </script>
@endpush
