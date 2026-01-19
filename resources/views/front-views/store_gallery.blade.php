@extends('front-views.layout')

@section('title', $store['meta_title'] ?? $store['name'])

@section('meta_keywords', '')
@section('meta_description', $store['meta_description'])


@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .grid-xy123 {
            column-count: 4;
            column-gap: 20px;
            padding: 20px;
        }

        .grid-xy123 .item-xy123 {
            break-inside: avoid;
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .grid-xy123 .item-xy123 img {
            width: 100%;
            display: block;
            border-radius: 12px;
        }

        @media (max-width: 992px) {
            .grid-xy123 {
                column-count: 2;
            }
        }

        @media (max-width: 600px) {
            .grid-xy123 {
                column-count: 1;
            }
        }
    </style>
@endpush

@section('content')
    <div class="spacer" style="height: 68px;"></div>

    <div class="row " style=" --bs-gutter-x: 0rem !important;">
        <nav class="px-3" style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E&#34;);"
            aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('store.details',[_selectedCity() , $store->slug])}}">{{ $store->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gallery</li>
            </ol>
        </nav>
        <!-- Fruits Shop Start-->
        <div class="container-fluid fruite pb-5">
            <div class="grid-xy123">
                @foreach ($store->galleries as $key => $value)
                    <div class="item-xy123"><img src="{{ asset('storage/app/public/store/gallery') . '/' . $value->image }}"
                            alt="gallery"></div>
                @endforeach
            </div>
        </div>
        <!-- Fruits Shop End-->
    </div>
@endsection

@push('script_2')
@endpush
