@extends('layouts.vendor.app')
@section('title', 'Item Images')
@push('css_or_js')
    <style>
        .gallery-item .check_select {
            {{-- display: none; --}}
        }

        .gallery-item:hover .check_select {
            display: block;
        }

        .check_select {
            z-index: 100;
            transform: scale(1.5);
            /* Adjust size here */
            margin-right: 5px;
            /* Optional: add spacing */
        }
    </style>
@endpush

@section('content')
    <!-- LightGallery CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-video.css">
    <!-- LightGallery JS -->

    <div class="content container-fluid p-1"> 
        <div class="page-header">
            <div class="d-flex flex-wrap px-3 w-100">
                <div class="d-flex w-100 flex-wrap justify-content-between  align-items-center">
                    <h1 class="page-header-title mb-2">
                        <a href="javascript:history.back()" class="mr-2" style="color: inherit;" title="Back">
                            <i class="tio-chevron-left"></i>
                        </a>
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                        </span>
                        <span>
                            Item Images
                            <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($item_images) }}</span>
                        </span>

                    </h1>

                    <button class="btn  btn-primary mx-2 d-none d-md-block" data-toggle="modal" data-target="#galleryModal">
                        Add Images
                    </button>
                    <button class="btn btn-sm btn-primary mx-2 d-block d-md-none" data-toggle="modal"
                        data-target="#galleryModal">
                        <i class="tio-add-photo"></i>
                    </button>
                </div>

                <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="exampleModalLabel"
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
                                <form action="{{ route('vendor.inventory.item-images-store') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <input type="file" multiple name="image[]" class="form-control" accept="image/*" />
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
            </div>
        </div>
        <!-- Page Heading -->

        <div class="card">


            <div class="card-body p-0 main-cont">
                <form id="bulk_form" action="{{ route('vendor.gallery.bulk-delete') }}" method="post">
                    <div class="d-flex flex-wrap lightgallery ">
                        @csrf
                        @foreach ($item_images as $key => $value)
                            <div class="position-relative">
                                {{-- <span class="check_select position-absolute"> <span
                                        class="btn-right-fixed copy-to-clipboard"></span>
                                </span> --}}
                                <a target="_blank"
                                    href="{{ asset('storage/app/public/inventory-item/') }}/{{ $value->image }}"
                                    style="cursor:default; margin:5px; "
                                    class="table-rest-info flex-column border rounded position-relative "
                                    alt="Gallery image">
                                    
                                    <button class="btn btn-transparent copy-to-clipboard p-2 border rounded"
                                        data-id="id_{{ $value['id'] }}" style="position: absolute;right: 0;"><i
                                            class="tio-copy"></i></button>

                                    <img style="width: 110px; height: 110px;  cursor:zoom-in;"
                                        onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                        src="{{ asset('storage/app/public/inventory-item/') }}/{{ $value->image }}">
                                    <div class="d-flex justify-content-between w-100">
                                        <span style=" font-size: 10px; margin-top: 8px; color: #5e5e5e;"
                                            id="id_{{ $value['id'] }}">{{ $value->image }}
                                        </span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
            <div class="page-area">
                {!! $item_images->links() !!}
            </div>
            @if (count($item_images) === 0)
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    <script>
        $(document).on('click', '.copy-to-clipboard', function(e) {
            e.preventDefault(); 
            e.stopPropagation();
            let elementId = $(this).data('id');
            let commandElement = document.getElementById(elementId);
            navigator.clipboard.writeText(commandElement.textContent);
            toastr.success('Copied to clipboard!');
            $(this).html('<i class="tio-checkmark-circle"></i>');
            setTimeout(() => {
                $(this).html('<i class="tio-copy"></i>');
            }, 1000);
        });
  
        lightGallery(document.querySelector('.lightgallery'), {
            selector: '.gallery-item',
            download: false,
            thumbnail: true
        });
    </script>
@endpush
