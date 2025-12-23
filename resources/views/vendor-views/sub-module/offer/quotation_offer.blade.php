@extends('layouts.vendor.app')

@section('title', 'Quotation Management')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        } 
    </style>
@endpush

@section('content')
      @include('vendor-views/sub-module/partials/quotation')

@endsection

@push('script_2')
 
@endpush
