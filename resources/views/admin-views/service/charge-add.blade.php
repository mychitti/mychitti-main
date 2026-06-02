@extends('layouts.admin.app')

@section('title', 'Lead Charge Add')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Lead Charges</h1>
        </div>
        @include('admin-views.service.partials._lead_charge_form')
    </div>
@endsection
