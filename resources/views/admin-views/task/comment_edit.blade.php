@extends('layouts.admin.app')

@section('title', 'Edit Task Update')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Edit Task Update </h1>

        </div>

        <!-- End Page Header -->
        <div class="row g-2">
            <div class=" mb-2 row col-12 " >
                <div class="col-md-9 card h-100">
                    <form class="w-100 p-0" enctype="multipart/form-data" action="{{ $task->parent_id ?  route('admin.task.subtask-update.update')  :  route('admin.task.comment.update') }}"
                        method="post">
                        @csrf
                        <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                        <div class="card-body row pt-0">
                            <div class="form-row col-md-6">
                                <label for="exampleInputEmail1">Title<span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ $comment->title }}">
                            </div>
                            <div class="form-row col-md-6">
                                <label for="exampleInputEmail1">Files (optional)</label>
                                <input type="file" name="files[]" multiple class="form-control">
                            </div>
                            <div class="form-row col-md-12">
                                <label for="exampleInputEmail1">Description (optional)</label>
                                <textarea rows="6" name="comment" class="form-control">{{ $comment->comment }}</textarea>
                            </div>

                        </div>
                        <div class="d-flex justify-content-end">
                        <button type = "submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div> 
                <div class="col-md-3 card h-100 ">
                <h3>Files </h3>
                    <div class="card-body row pt-0 ">
                        @php
                            $files = json_decode($comment['files'], true);
                            $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
                        @endphp

                        @if (!empty($files))
                            <div class="file-previews d-flex flex-wrap gap-2">
                                @foreach ($files as $file)
                                    <div class="position-relative">
                                        @php
                                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                            $fileUrl = asset('storage/app/public/task/' . $file); // adjust path if needed
                                        @endphp
                                        <a style="position: absolute;    border: 1px solid red;border-radius: 50%;top: -8px;right: -5px;"
                                            href="{{ route('admin.task.comment.pic-delete', [$comment['id'], $file]) }}"><i
                                                class="tio-delete text-danger"></i></a>
                                        @if (in_array($ext, $imageTypes))
                                            {{-- Image preview --}}
                                            <a href="{{ $fileUrl }}" target="_blank" title="View Image">
                                                <img src="{{ $fileUrl }}" alt="File"
                                                    style="width: 60px; height: 60px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px;">
                                            </a>
                                        @else
                                            {{-- Other file types --}}
                                            <a href="{{ $fileUrl }}" target="_blank"
                                                style="width:fit-content !important;"
                                                class="btn action-btn btn-sm btn-outline-secondary" title="View File">
                                                View File ({{ strtoupper($ext) }})
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
@endpush
