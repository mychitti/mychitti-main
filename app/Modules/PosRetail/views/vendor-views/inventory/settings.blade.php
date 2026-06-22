@extends('layouts.vendor.app')

@section('title', 'Purchase Settings')

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
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Purchase Settings</h1>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            <form class="w-100 p-0" action="{{ route('vendor.inventory.settings-save') }}" method="post">
                @csrf
                <div class="col-md-12">
                    <div class=" h-100">
                        <div class="">
                            <div class="form-row">
                                <label for="">Terms and Conditions</label>
                                <textarea name="content" id="editor2">{{ $tnc ? $tnc->terms_n_conditons : '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mt-3">
                    <label class="font-weight-bold d-block mb-1">Print Label Size (mm)</label>
                    <small class="text-muted d-block mb-2">Used by the quick single-label print (barcode / full).</small>
                    <div class="d-flex flex-wrap align-items-end" style="gap:12px;">
                        <div>
                            <label style="font-size:12px;display:block;margin-bottom:2px;color:#555;">Label Width (mm)</label>
                            <input type="number" name="label_width" value="{{ $labelWidth ?? 50 }}" step="0.5" min="10"
                                class="form-control" style="width:140px;" placeholder="50">
                        </div>
                        <div>
                            <label style="font-size:12px;display:block;margin-bottom:2px;color:#555;">Label Height (mm)</label>
                            <input type="number" name="label_height" value="{{ $labelHeight ?? 25 }}" step="0.5" min="10"
                                class="form-control" style="width:140px;" placeholder="25">
                        </div>
                        <div class="pb-1">
                            <a href="{{ route('vendor.inventory.label-formats') }}" class="btn btn-primary"
                                onclick="event.stopPropagation();">🏷 Design Label Formats</a>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">Build fully custom, drag-and-drop label layouts (store name, dates, prices, barcode…) and set a default.</small>
                </div>
                <div class="d-flex justify-content-end w-100">
                    <button class="btn btn-primary my-2">Update</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor2'));
    </script>
@endpush
