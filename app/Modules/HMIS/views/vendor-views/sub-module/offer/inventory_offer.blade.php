@extends('layouts.vendor.app')

@section('title', _moduleLabel('inventory_manage'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')

   @include('vendor-views/sub-module/partials/inventory') 

@endsection

@push('script_2')
 
@endpush
