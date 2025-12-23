@extends('layouts.vendor.app')
@section('title', 'Gallery')
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
            <div class="d-flex flex-wrap   w-100">
                <div class="d-flex flex-wrap  align-items-center">
                    <h1 class="page-header-title mb-2">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                        </span>
                        <span>
                            Gallery
                            <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($galleries) }}</span>
                        </span>

                    </h1>
                    <div style="" class="ml-5">
                        <input type="checkbox" id="check_all">
                        <label for="check_all" id="check_all_label" style="font-size: 20px;">Select All</label>
                    </div>
                    <div style="" class="ml-3">
                        <select name="action" id="action" class="form-control">
                            <option value="">--select action--</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                     <button class="btn  btn-primary mx-2 d-none d-md-block" data-toggle="modal" data-target="#galleryModal">
                        Add Gallery
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
                                <form action="{{ route('vendor.gallery.store') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <input type="file" multiple name="file[]" class="form-control" accept="image/*" />
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
                        @foreach ($galleries as $key => $value)
                            <div class="position-relative">
                                <input type="checkbox" name="gallery_id[]" value="{{ $value->id }}"
                                    style="right: 9px;bottom: 9px;" name="" class="check_select position-absolute"
                                    id="">

                                <a target="_blank"
                                    href="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}"
                                    style="cursor:default; margin:5px; "
                                    class="table-rest-info flex-column border rounded position-relative gallery-item"
                                    alt="Gallery image">
                                    <button class="btn btn-transparent dlt-btn p-0" data-id="{{ $value->id }}"
                                        style="position: absolute;right: 0;"><i style="    font-size: 20px;"
                                            class="tio-delete text-danger"></i></button>

                                    <img style="width: 110px; height: 110px;  cursor:zoom-in;"
                                        onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                        src="{{ asset('storage/app/public/store/gallery') }}/{{ $value->image }}">
                                    <div class="d-flex justify-content-between w-100">
                                        <span
                                            style=" font-size: 10px; margin-top: 8px; color: #5e5e5e;">{{ _formatted_datetime($value->created_at, 'date') }}
                                        </span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
            <div class="page-area">
                {!! $galleries->links() !!}
            </div>
            @if (count($galleries) === 0)
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
        $('.dlt-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).attr('data-id')
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('vendor.gallery.delete') }}",
                data: {
                    id: id
                },
                success: function(data) {
                    toastr.success("Image Deleted Successfully.");
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                },
                complete: function() {
                    $('#loading').hide()
                }
            });

        });
        $("#check_all").on('change', function() {
            if ($(this).prop('checked') == true) {
                $("#check_all_label").text('Deselect All');
                $(".check_select").prop('checked', true)

            } else {
                $("#check_all_label").text('Select All');
                $(".check_select").prop('checked', false)

            }
        })
        $("#action").on('change', function() {
            if ($(this).val() == 'delete') {

                Swal.fire({
                    title: '{{ translate('messages.Are you sure?') }}',
                    text: 'You want to delete selected gallery',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.no') }}',
                    confirmButtonText: '{{ translate('messages.Yes') }}',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        $("#bulk_form").submit()
                    }
                })
            }

        })
        lightGallery(document.querySelector('.lightgallery'), {
            selector: '.gallery-item',
            download: false,
            thumbnail: true
        });
    </script>
@endpush
