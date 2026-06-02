@extends('layouts.admin.app')

@section('title', translate('Charges List'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Lead Charges</h1>
        </div>
        @include('admin-views.service.partials._lead_charges_list')
    </div>
@endsection
