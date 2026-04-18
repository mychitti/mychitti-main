@extends('layouts.vendor.app')
@section('title', 'Edit Prescription #' . $rx->id)

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-medicine"></i></span>
            Edit Prescription <span class="text-muted">#{{ $rx->id }}</span>
        </h1>
        <a href="{{ route('vendor.prescription.show', $rx->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    @include('vendor-views.prescription._form', [
        'formAction'     => route('vendor.prescription.update', $rx->id),
        'formMethod'     => 'POST',
        'appointment'    => null,
        'serviceRequest' => null,
        'patient'        => $rx->patient,
        'doctorProfileId'=> $rx->doctor_profile_id,
    ])
</div>
@endsection
