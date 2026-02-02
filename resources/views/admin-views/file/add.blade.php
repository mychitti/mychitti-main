@extends('layouts.admin.app')

@section('title', 'Upload File')

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-file-add-outlined"></i> Upload File
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.file.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row ">
                        <div class="col-md-4">
                            <label for="">Select Folder</label>
                            <select required name="folder" data-placeholder="Folder" id="folder"
                                class="form-control js-select2-custom-tags  ">
                                <option value=""></option>
                                @foreach ($folderList as $folder)
                                    <option value="{{ $folder }}">{{ $folder }}</option>
                                @endforeach
                            </select>
                            <label for="">Image</label>
                            <label class="avatar-border-lg avatar-uploader profile-cover-avatar border mt-3"
                                style="      position: relative;" for="avatarUploader">
                                <img id="viewer"
                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                    class="avatar-img onerror-image"
                                    src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="Image">
                                {{-- accept="image/*,android/allowCamera"  --}}
                                <input type="file" name="file" class="js-file-attach avatar-uploader-input"
                                    id="customFileEg1" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                <label class="avatar-uploader-trigger" style="    width: 100%;height: 100%;"
                                    for="customFileEg1">
                                    <i class="tio-file-add-outlined avatar-uploader-icon shadow-soft"
                                        style="    position: absolute;bottom: 0;right: 0;padding: 10px;"></i>
                                </label>
                            </label>
                            {{-- <label for="">Other File Type</label>
                            <input type="file" name="other_file" class="form-control" /> --}}
                            <label for="">Multiple Files (any format)</label>
                            <input type="file" name="multiple_files[]" class="form-control" multiple />
                        </div>
                        <div class="col-md-8 gap-1 d-flex flex-wrap">
                            @foreach ($data as $item)
                                <div class="position-relative">
                                    <a href="{{ $item->url }}" target="_blank"
                                        class="table-rest-info flex-column border rounded p-2"
                                        style="margin:5px; width:130px">

                                        {{-- File icon --}}
                                        <div class="text-center">
                                            @if (in_array($item->ext, ['jpg', 'jpeg', 'png', 'webp']))
                                                <img src="{{ $item->url }}"
                                                    style="width:110px; height:110px; object-fit:cover;">
                                            @else
                                                <i class="tio-file" style="font-size:60px;"></i>
                                            @endif
                                        </div>

                                        <small class="text-muted text-center mt-1">
                                            {{ $item->file }}
                                        </small>


                                        <span class="badge badge-secondary mt-1" style="font-style: lowercase;">
                                            {{ strtolower($item->folder) }}
                                        </span>
                                    </a>
                                </div>
                            @endforeach

                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-3">
                        <button type="submit" class="btn btn--primary">Submit</button>
                    </div>
                </form>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>

    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/profile-index.js"></script>
@endpush
