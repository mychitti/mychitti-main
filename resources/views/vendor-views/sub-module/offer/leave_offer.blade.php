@extends('layouts.vendor.app')

@section('title', 'Leave Management')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        } 
    </style>
@endpush

@section('content')
   
   @include('vendor-views/sub-module/partials/leave')
    
@endsection

@push('script_2')
 
@endpush
