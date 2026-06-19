@extends('layouts.vendor.app')

@section('title', isset($customer) ? 'Edit Client' : 'Add New')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
            padding: 0 12px !important;
        }

        .btn-outline-primary.active {
            background-color: #00868f !important;
        }

        label {
            font-size: 11px;
            font-weight: bold;
            line-height: 19px;
            margin-bottom: 0px !important;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid p-2">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between w-100">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>
                {{ isset($customer) ? 'Edit Client Info' : 'Add New Client' }}</h1>
      
        </div>
        <!-- Modal -->
        @if (hasPermission('client_manage', 'import'))
            <div class="modal fade" id="importCustModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Upload File</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <a href="{{ asset('storage/app/public/util') }}/users.xlsx"
                                class="btn btn-primary btn-outline-primary mb-2">Download Example Excel</a>
                            <form action="{{ route('vendor.customer.upload-excel') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf

                                <input type="file" name="file" class="form-control" accept=".xls,.xlsx" />
                                <button type="submit" class="btn btn-primary btn--primary ">Upload
                                </button>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- End Page Header -->

        <div class="row g-0 p-2">
            @if (hasPermission('client_manage', 'add'))
                @include('vendor-views/forms/customer_add')
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        function readURL2(input) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#viewer3').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg3").change(function() {
            readURL2(this);
        });
    </script>
@endpush
