@extends('front-views.layout')

@section('title', 'Stores')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

    <div class="spacer" style="height: 43px;"></div>
    <!-- Contact Start -->
    <div class="container-fluid contact py-5">
        <div class="container">
            <div class=" rounded">
                <h2>Stores on My Chitti</h2>

                <div class="row">
                    @foreach ($stores as $key => $store)
                        <div style="    max-width: 214px !important;" class="my-1">
                            <div class="rounded position-relative fruite-item">
                                <div class="vesitable-img ">
                                    <img data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->cover_photo, asset('storage/app/public/store/cover/') . '/' . $store->cover_photo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/cover/') }}"
                                        class="img-fluid w-100 rounded-top"
                                        style="height: 180px !important; object-fit: cover;"
                                        alt="{{ ucfirst($store->name) }}">
                                </div>
                                <div class="text-white bg-primary rounded position-absolute"
                                    style="top: 10px; right: 10px; ">
                                    @if ($store->logo != '')
                                        <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                            class="img-fluid rounded"
                                            style=" width: 65px !important; border: 4px solid #81c408;height: 65px !important;object-fit: cover;"
                                            alt="{{ ucfirst($store->name) }}">
                                    @endif
                                </div>
                                <div class="p-2 border rounded-bottom">
                                    <span class="badge bg-light text-dark">{{ round($store->distance, 2) }} km</span>
                                    <h4 class="one-line-ellipsis text-start product_name" style="font-size: 15px;">
                                        {{ ucfirst($store->name) }}</h4>
                                    <p>{{ $store->footer_text }}</p>
                                    <div class="d-flex justify-content-between flex-lg-wrap">
                                        <p class="fs-6 fw-bold mb-0"></p>
                                        <a href="{{ route('store.details', [_selectedCity() , $store->slug]) }}"
                                            class="btn border border-secondary rounded p-1 px-2 text-primary"><i
                                                class="fa fa-shopping-bag me-2 text-primary"></i> Explore </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class='d-flex'>
                    {{ $stores->links() }}
                </div>

            </div>
        </div>
    </div>
    <!-- Contact End -->

@endsection

@push('script_2')
@endpush
