@extends('layouts.vendor.app')
@section('title', 'Edit OPD Visit')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Edit OPD Visit #{{ $visit->id }}
        </h1>
        <a href="{{ route('vendor.opd.show', $visit->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('vendor.opd.update', $visit->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('vendor-views.opd._form', ['visit' => $visit])
                @include('vendor-views.opd._form_vitals_only', ['visit' => $visit])
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn--primary">Update Visit</button>
                    <a href="{{ route('vendor.opd.show', $visit->id) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
