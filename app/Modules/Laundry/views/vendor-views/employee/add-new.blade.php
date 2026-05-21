@extends('layouts.vendor.app')
@section('title', translate('messages.Staff Add'))
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .select2-selection__choice {
            padding: 2px 5px;
            font-size: 14px;
        }

        .select2-selection__choice__remove {
            margin-left: 5px;
        }

        .select2-selection__rendered {
            max-height: 50px;
            overflow-y: auto;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            line-height: inherit;
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
      
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Heading -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                </span>
                <span>
                    Add New Staff
                </span>
            </h1>
        </div>

        <!-- Content Row -->
        @include('vendor-views/forms/employee_add')
        @include('vendor-views/form_modals/role_add')
        @include('vendor-views/form_modals/department_add')
    </div>
@endsection

@push('script_2')
    <script>
        "use strict";
        $('#reset_btn').click(function() {
            $('#viewer').attr('src', '{{ asset('public/assets/admin/img/160x160/img1.jpg') }}');
        })
        $("#customFileUpload").change(function() {
            readURL(this, 'viewer');
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-tags').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: "Select or add tags",
                allowClear: true,
                maximumSelectionLength: 6
            });

            // Show/hide employment end date based on employee type
            function toggleEndDate() {
                if ($('#employee_type').val() === 'temporary') {
                    $('#employment_end_date_wrap').show();
                    $('#employment_end_date').attr('required', true);
                } else {
                    $('#employment_end_date_wrap').hide();
                    $('#employment_end_date').removeAttr('required').val('');
                }
            }
            $('#employee_type').on('change', toggleEndDate);
            toggleEndDate();
        });
    </script>
@endpush
