@extends('layouts.admin.app')

@section('title', translate('Category Preview'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between">
                <h1 class="page-header-title text-break">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--22" alt="">
                    </span>
                    <span>{{ $category['name'] }}</span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row review--information-wrapper g-2 mb-3">
            <div class="col-lg-9">
                <div class="card h-100">
                    <!-- Body -->
                    <div class="card-body">
                        <div class="row align-items-md-center">
                            <div class="col-lg-5 col-md-6 mb-3 mb-md-0">
                                <div class="d-flex flex-wrap align-items-center food--media">
                                    <img class="avatar avatar-xxl avatar-4by3 mr-4 onerror-image"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                            $category['image'] ?? '',
                                            asset('storage/app/public/category') . '/' . $category['image'] ?? '',
                                            asset('public/assets/admin/img/160x160/img2.jpg'),
                                            'category/',
                                        ) }}"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                        alt="Category Description">

                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- End Body -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
@endpush
