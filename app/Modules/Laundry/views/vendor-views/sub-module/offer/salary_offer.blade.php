@extends('layouts.vendor.app')

@section('title', 'Salary Management')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        } 
    </style>
@endpush

@section('content')
      @include('vendor-views/sub-module/partials/salary')
  
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
 
@endpush
