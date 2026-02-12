@extends('layouts.vendor.app')

@section('title', 'Staff Settings')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
 
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Terms and Conditions</h1>
        </div>
        <!-- End Page Header -->

       
    </div>
@endsection

@push('script_2')
  
@endpush
