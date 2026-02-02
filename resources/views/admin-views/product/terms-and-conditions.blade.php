@extends('layouts.admin.app')

@section('title', request()->product_gellary == 1 ? translate('Add item') : translate('Edit item'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
    <script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
        }
    }
</script>
    <style>
        .seo-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .seo-header {
            background: #f4f4f4;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seo-header:hover {
            background: #e4e4e4;
        }

        .seo-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .seo-header i {
            transition: transform 0.3s;
        }

        .seo-header[aria-expanded="true"] i {
            transform: rotate(180deg);
        }

        .seo-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control,
        .form-control:focus {
            border-radius: 4px;
        }

        .char-counter {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }

        .info-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }

        .faq-item {
            background-color: #f8f9fa;
            position: relative;
        }

        .seo-content-item {
            background-color: #f8f9fa;
            position: relative;
        }

        .remove-faq,
        .remove-seo-content {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
    <style>
        .non_changeable {
            pointer-events: none;

        }
    </style>

    <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Bold,
            Italic,
            Font,
            Paragraph,
            Table,
            TableToolbar
        } from 'ckeditor5';

        let editorInstance;

        ClassicEditor
            .create(document.querySelector('#editor'), {
                plugins: [Essentials, Bold, Italic, Font, Paragraph, Table, TableToolbar],
                toolbar: {
                    items: [
                        'undo', 'redo', '|', 'bold', 'italic', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                        'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells'
                    ]
                }
            })
            .then(editor => {
                editorInstance = editor;
            })
            .catch(error => {
                console.error('There was a problem initializing the editor.', error);
            });

        let variatonEditors = {};

        document.querySelectorAll('.editor').forEach((editorElement, index) => {
            ClassicEditor
                .create(editorElement, {
                    plugins: [Essentials, Bold, Italic, Font, Paragraph, Table, TableToolbar],
                    toolbar: {
                        items: [
                            'undo', 'redo', '|', 'bold', 'italic', '|',
                            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                            'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells'
                        ]
                    }
                })
                .then(editor => {
                    variatonEditors[editorElement.id] = editor;
                })
                .catch(error => {
                    console.error('There was a problem initializing the editor.', error);
                });
        });
    </script>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap __gap-15px justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--22" alt="">
                </span>
                <span>
                    Terms and Conditions
                </span>
            </h1>

        </div>
        <!-- End Page Header -->
        <form  method="post" id="ck_editor_form" action="{{route('admin.item.terms-and-conditions.store')}}" enctype="multipart/form-data">
            <div class="row g-2">
                <div class="col-12">
                    <!-- SEO Section -->
                    <div class="seo-section">
                        <div class="seo-header" data-toggle="collapse" data-target="#gatepassCollapse" aria-expanded="false"
                            aria-controls="gatepassCollapse">
                            <h4><i class="fas fa-search-dollar mr-2"></i> Gatepass</h4>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse show" id="gatepassCollapse">

                            <div class="seo-body">
                                <div id="seoContentContainer">
                                    <div class="form-group mb-0">
                                        <textarea class="form-control ck_editor" name="gatepass_tnc" id="gatepass_tnc" placeholder="Enter terms and conditions for gatepass">{{$gatepass_tnc}}</textarea>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-seo-content">
                                        <i class="tio-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="seo-section">

                        <div class="seo-header" data-toggle="collapse" data-target="#quotationCollapse"
                            aria-expanded="false" aria-controls="quotationCollapse">
                            <h4><i class="fas fa-search-dollar mr-2"></i> Quotation</h4>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse show" id="quotationCollapse">

                            <div class="seo-body">
                                <div id="seoContentContainer">
                                    <div class="form-group mb-0">
                                        <textarea class="form-control ck_editor" name="quotation_tnc" id="quotation_tnc" placeholder="Enter terms and conditions for quotation">{{$quotattion_tnc}}</textarea>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-seo-content">
                                        <i class="tio-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="btn--container justify-content-end">
                    <button type="reset" id="reset_btn" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                    <button type="submit"
                        class="btn btn--primary">{{ isset($temp_product) && $temp_product == 1 ? translate('Edit_&_Approve') : translate('messages.submit') }}</button>
                </div>
            </div>
    </div>
    </form>
    </div>




@endsection


@push('script_2')
    @include('vendor-views/multiple_ck_editor');
@endpush
