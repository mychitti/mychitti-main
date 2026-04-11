@extends('layouts.admin.app')

@section('title', translate('messages. Blog Category'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--20" alt="">
                </span>
                <span>
                    {{ ($type ?? 'common') === 'mc_vendor' ? 'mcvendorhub.com' : 'mychitti.net' }} — Blog Categories
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-body">
                <form
                    action="{{ isset($category) ? route('admin.category.update', [$category['id']]) : route('admin.blog.category-store') }}"
                    method="post" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-md-8">

                            <div class="form-group lang_form" id="default-form">
                                <label class="input-label" for="exampleFormControlInput1">{{ translate('messages.name') }}
                                    <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                        data-placement="right" data-original-title="{{ translate('messages.Required.') }}">
                                        *
                                    </span>

                                </label>
                                <input type="text" name="name" value="{{ old('name.0') }}" class="form-control"
                                    placeholder="{{ translate('messages.new_category') }}" maxlength="191">
                            </div>

                        </div>
                        <input type="hidden" name="type" value="{{ $type ?? 'common' }}">
                        <div class="col-md-4">
                            <div class="h-100 d-flex align-items-center flex-column">
                                <label class="mb-3 text-center">{{ translate('messages.image') }} <small
                                        class="text-danger">* ( {{ translate('messages.ratio') }} 1:1)</small></label>
                                <label class="text-center my-auto position-relative d-inline-block">
                                    <img class="img--176 border" id="viewer"
                                        @if (isset($category)) src="{{ asset('storage/app/public/category') }}/{{ $category['image'] }}"
                                        @else
                                        src="{{ asset('public/assets/admin/img/upload-img.png') }}" @endif
                                        alt="image" />
                                    <div class="icon-file-group">
                                        <div class="icon-file">
                                            <input type="file" name="image" id="customFileEg1"
                                                class="custom-file-input read-url"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <i class="tio-edit"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-3">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit"
                            class="btn btn--primary">{{ isset($category) ? translate('messages.update') : translate('messages.add') }}</button>
                    </div>

                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{ translate('messages.blog_category_list') }}<span
                            class="badge badge-soft-dark ml-2" id="itemCount">{{ $categories->total() }}</span></h5>

                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-align-middle"
                        data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">{{ translate('messages.id') }}</th>
                                <th class="border-0">Image</th>
                                <th class="border-0 w--1">{{ translate('messages.name') }}</th>
                                <th class="border-0 w--1">{{ translate('messages.type') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($categories as $key => $category)
                                <tr>
                                    <td>{{ $key + $categories->firstItem() }}</td>
                                    <td>{{ $category->id }}</td>
                                    <td> <img class="avatar avatar-lg mr-3 onerror-image"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $category->image ?? '',
                                                asset('storage/app/public/blog/category') . '/' . $category->image ?? '',
                                                asset('public/assets/admin/img/160x160/img2.jpg'),
                                                'blog/category/',
                                            ) }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            alt="{{ $category->name }} image"></td>

                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ Str::limit($category['name'], 20, '...') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($category->type == 'mc_vendor')
                                            <span
                                                class="badge badge-soft-info">{{ translate('messages.mc_vendor') }}</span>
                                        @else
                                            <span
                                                class="badge badge-soft-success">{{ translate('messages.common') }}</span>
                                        @endif
                                    </td>



                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--primary btn-outline-primary"
                                                href="{{ route('admin.blog.category-edit', [$category['id']]) }}"
                                                title="{{ translate('messages.edit_category') }}"><i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $category['id'] }}"
                                                data-message="{{ translate('Want to delete this category') }}"
                                                title="{{ translate('messages.delete_category') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('admin.blog.category-delete', [$category['id']]) }}"
                                                method="post" id="category-{{ $category['id'] }}">
                                                @csrf @method('delete')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($categories) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $categories->appends($_GET)->links() !!}
            </div>
            @if (count($categories) === 0)
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
    <script>
        $(document).on('change', '.warnig-charge-btn', function(e) {

            $('.warnig-charge-btn').prop('checked', false);
            swal({
                //text: message,
                title: 'Please Add Lead Charges First',
                type: 'warning',
                confirmButtonColor: '#FC6A57',
                confirmButtonText: 'Add Now',
            }).then(function(isConfirm) {
                console.log(isConfirm)
                if (isConfirm.value) {
                    window.location.href = '{{ route('admin.service.lead-charge') }}';
                } else {


                }
            })

        })
    </script>

    <script src="{{ asset('public/assets/admin') }}/js/view-pages/category-index.js"></script>
    <script>
        "use strict";
        $('.location-reload-to-category').on('click', function() {
            const url = $(this).data('url');
            let nurl = new URL(url);
            nurl.searchParams.delete('search');
            location.href = nurl;
        });

        $("#customFileEg1").change(function() {
            readURL(this);
            $('#viewer').show(1000)
        });
        $('#reset_btn').click(function() {
            $('#exampleFormControlSelect1').val(null).trigger('change');
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload-img.png') }}");
        })
    </script>
@endpush
