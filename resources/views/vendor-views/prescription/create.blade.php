@extends('layouts.vendor.app')
@section('title', 'New Prescription')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-medicine"></i></span>
            New Prescription
        </h1>
        <a href="{{ route('vendor.prescription.list') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    @include('vendor-views.prescription._form', [
        'rx'             => null,
        'formAction'     => route('vendor.prescription.store'),
        'formMethod'     => 'POST',
        'doctorProfileId'=> $doctorProfileId ?? null,
    ])
</div>
@endsection
