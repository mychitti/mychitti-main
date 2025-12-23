@extends('front-views.layout')

@section('title','Home')

@section('seo')
<meta content="3" name="keywords">
<meta content="32" name="description">
@endsection

@push('css_or_js')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

@endsection

@push('script_2')

@endpush