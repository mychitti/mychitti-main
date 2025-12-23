@extends('front-views.layout')

@section('title','FAQs')

@section('seo')
@endsection

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')


<!-- Contact Start -->
<div class="container-fluid contact py-5">
    <div class="container py-5">
        {!!$content->value!!} 
    </div>
</div>
@endsection

@push('script_2')

@endpush