@extends('layouts.vendor.app')

@section('title', 'Terms And Conditions')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Terms And Conditions</h1>
            
        </div>
     {!! $terms_and_conditions !!}
    </div>

@endsection


