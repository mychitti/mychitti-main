@extends('layouts.vendor.app')

@section('title', 'Lead Settings')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">Lead Settings</h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('vendor.service.lead-settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group col-4 border rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="col-sm-4 col-form-label font-weight-bold p-0">Available for Leads</label>
                            <label class="toggle-switch toggle-switch-sm" for="lead_available">
                                <input type="checkbox" name="lead_available" id="lead_available" class="toggle-switch-input"
                                    {{ $storeConfig->lead_available ?? 1 ? 'checked' : '' }}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                        </div>

                        <div class="">

                            <p class="text-muted mt-1" style="font-size: 13px;">
                                When turned off, your store will not receive new lead requests and the enquiry button will
                                be disabled on your store page.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
