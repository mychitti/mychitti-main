@extends('layouts.vendor.app')

@section('title', 'Library')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .template-option {
            display: inline-block;
            border: 2px solid #ccc;
            border-radius: 6px;
            padding: 5px;
            margin: 5px;
            cursor: pointer;
            text-align: center;
            transition: border-color 0.2s ease;
            width: 150px;
            /* adjust to your preview size */
        }

        .template-option input[type="radio"] {
            display: none;
        }

        .template-option img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .template-option.selected {
            border-color: #007bff;
        }
    </style>
    <style>
        .placeholder {
            border-radius: 7px 7px 0 0;
            text-align: center;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            color: #0000005e;

        }

        .bg1 {
            background-color: #ffeaeaff;
        }

        .bg2 {
            background-color: #efffedff;
        }
        .bg3 {
            background-color: #edf9ffff;
        }
        .bg4{
            background-color: #f9edffff;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Library </h1>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            <div class="col-md-2 p-2">
                <div class="card ">
                    <div class="placeholder bg1">Receivable Receipt</div>
                    <div class="card-body">
                        <h4>Receivable Receipt</h4>
                        <a href="{{ route('vendor.documents.receivable-receipt.create') }}">Create</a>
                    </div>
                </div>
            </div>
            <div class="col-md-2 p-2">
                <div class="card ">
                    <div class="placeholder bg2">Job Card</div>
                    <div class="card-body">
                        <h4>Job Card</h4>
                        <a href="{{ route('vendor.documents.job-card.create') }}">Create</a>
                    </div>
                </div>
            </div>
            <div class="col-md-2 p-2">
                <div class="card ">
                    <div class="placeholder bg3">POS Token</div>
                    <div class="card-body">
                        <h4>POS Token</h4>
                        <a type="button" data-toggle="modal" data-target="#tokenTemplateModal">Templates</a>
                    </div>
                </div>
            </div>
            <div class="col-md-2 p-2">
                <div class="card ">
                    <div class="placeholder bg4">Purchase Gatepass</div>
                    <div class="card-body">
                        <h4>Purchase Gatepass</h4>
                    <a href="{{route('vendor.library.gatepass.add', ['purchase'])}}">Create</a>
                    </div>
                </div>
            </div>
            <div class="col-md-2 p-2">
                <div class="card ">
                    <div class="placeholder bg4">Sale Gatepass</div>
                    <div class="card-body">
                        <h4>Sale Gatepass</h4>
                    <a href="{{route('vendor.library.gatepass.add', ['sale'])}}">Create</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="tokenTemplateModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">POS Token Templates</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('vendor.library.select-template')}}" method="post">
                @csrf
                <input type="hidden" name="action" value="pos_token_template" >
                <div class="modal-body">
                    <div class="d-flex gap-2 flex-wrap">
                        @php $tempCount = [1,2,3];@endphp
                        @foreach ($tempCount as $key => $value)
                        @php $select = isset($store_config) && $store_config && $store_config->pos_token_template == $value ?  true : false; @endphp
                            <div class="template-option {{$select ?  'selected' : '' }}">
                                <label>
                                    <input class="" {{$select ?  'checked' : '' }} type="radio" name="value" value="{{ $value }}">
                                    <img src="{{ asset('storage/app/public/templates/token') . '/' . 'template_' . $value . '.png' }}"
                                        alt="Template {{ $value }} Preview">
                                    <div>Template {{ $value }}</div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script_2')
    <script>
        // Highlight selected template
        $('input[name="value"]').on('change', function() {
            $('.template-option').removeClass('selected');
            $(this).closest('.template-option').addClass('selected');
        });
    </script>
@endpush
