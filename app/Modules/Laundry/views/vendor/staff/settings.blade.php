@extends('layouts.vendor.app')

@section('title', 'Staff Settings')

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
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Settings</h1>
        </div>
        <!-- End Page Header -->


        <div class="row g-2">
            <form class="w-100 p-0" action="{{ route('vendor.staff.settings.save') }}" method="post">
                @csrf
                <div class="form-row col-md-4">
                    <div class="d-flex justify-content-between w-100">
                        <label for="">Employee Id Prefix</label>
                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_name">
                            <input {{$store->prefix_status ? 'checked' : ''}} name="prefix_status" value="1" type="checkbox" class="toggle-switch-input" id="toggleColumn_name">
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                        </label>
                    </div>
                    <input type="text" value="{{$store->prefix_status ? $store->emp_prefix : ''}}" {{$store->prefix_status ? '' : 'disabled'}}  maxlength="3"   class="form-control alphaInput" name="prefix" id="prefix_inp" placeholder ="EX : SFT">
                    <small>1 to 3 Characters. Upppercase only.</small>
                </div>
                <div class="col-md-12">
                    <div class=" h-100">
                        <div class="">
                            <div class="form-row">
                                <label for="">Terms and Conditions for Staff</label>
                                <textarea name="content" id="editor2">{{ $tAndCContentForStaff ? $tAndCContentForStaff->terms_n_conditons : '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end w-100">
                    <button class="btn btn-primary my-2">Update</button>
                </div>

            </form>

        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $('.alphaInput').on('keyup', function() {
            let value = $(this).val();
            value = value.replace(/[^a-zA-Z]/g, '');
            value = value.toUpperCase();
            value = value.substring(0, 3);
            $(this).val(value);
        });
        $("#toggleColumn_name").on('change', function() {
            if ($(this).prop('checked') == true) {
                $("#prefix_inp").removeAttr('disabled')
            } else {
                $("#prefix_inp").attr('disabled', true)
                $("#prefix_inp").val('')
            }
        })
    </script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'));
        ClassicEditor
            .create(document.querySelector('#editor2'));
    </script>
@endpush
