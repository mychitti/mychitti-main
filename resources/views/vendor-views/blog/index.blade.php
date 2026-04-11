@extends('layouts.vendor.app')

@section('title', 'My Blog Posts')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-9 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--22" alt="">
                        </span>
                        <span>Blog Posts <span class="badge badge-soft-dark ml-2">{{ $blogs->total() }}</span></span>
                    </h1>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper justify-content-between">
                    <form action="{{ route('vendor.blog.index') }}" method="GET" class="d-flex gap-2">
                        <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm w-auto" placeholder="Search...">
                        <button class="btn btn-sm btn--primary">Search</button>
                        @if($search)
                            <a href="{{ route('vendor.blog.index') }}" class="btn btn-sm btn--reset">Clear</a>
                        @endif
                    </form>
                    <a href="{{ route('vendor.blog.create') }}" class="btn btn--primary font-regular">+ Add Blog Post</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Website</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($blogs as $key => $blog)
                            <tr>
                                <td>{{ $key + $blogs->firstItem() }}</td>
                                <td>
                                    <img class="avatar avatar-lg mr-3"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($blog->image, asset('storage/app/public/blog') . '/' . $blog->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'blog/') }}"
                                        alt="{{ $blog->title }}">
                                </td>
                                <td>
                                    <h6 class="mb-0">{{ Str::limit($blog->title, 40) }}</h6>
                                    @if($blog->short_description)
                                        <small class="text-muted">{{ Str::limit($blog->short_description, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($blog->type === 'mc_vendor')
                                        <span class="badge badge-soft-info">mcvendorhub.com</span>
                                    @else
                                        <span class="badge badge-soft-success">mychitti.net</span>
                                    @endif
                                </td>
                                <td>
                                    @if($blog->status == 1)
                                        <span class="badge badge-soft-success">Published</span>
                                    @else
                                        <span class="badge badge-soft-warning">Pending Approval</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('vendor.blog.edit', $blog->id) }}" title="Edit">
                                            <i class="tio-edit"></i>
                                        </a>
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                            href="javascript:" data-id="blog-del-{{ $blog->id }}"
                                            data-message="Want to delete this blog post?" title="Delete">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('vendor.blog.delete', $blog->id) }}" method="post" id="blog-del-{{ $blog->id }}">
                                            @csrf @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty--data text-center py-4">
                                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                                        <h5>No blog posts yet</h5>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($blogs->hasPages())
                <div class="page-area px-3 pb-3">
                    {!! $blogs->withQueryString()->links() !!}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
<script>"use strict";</script>
@endpush
