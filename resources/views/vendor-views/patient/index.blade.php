@extends('layouts.vendor.app')

@section('title', 'Add New Patient')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row { margin-top: 6px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> New Patient</h1>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="progress mb-4" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 20%;" id="progressBar">Step 1 of 5</div>
                </div>

                <form id="patientForm" method="POST" action="{{ route('vendor.patient.save') }}" enctype="multipart/form-data" novalidate>
                    @csrf

                    <!-- Step 1: Personal Details -->
                    <div class="stept" id="step-1">
                        <h3>Personal Details</h3><br>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select</option>
                                    <option value="male"   {{ old('gender') == 'male'   ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other"  {{ old('gender') == 'other'  ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Blood Group</label>
                                <select name="blood_group" class="form-control">
                                    <option value="">Select</option>
                                    @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $group)
                                        <option {{ old('blood_group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Address</label>
                                <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>City</label>
                                <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>State</label>
                                <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Medical History -->
                    <div class="stept d-none" id="step-2">
                        <h3>Medical History</h3><br>
                        <div class="form-group">
                            <label>Known Allergies</label>
                            <textarea name="allergies" class="form-control" rows="2">{{ old('allergies') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Chronic Conditions</label>
                            <textarea name="chronic_conditions" class="form-control" rows="2">{{ old('chronic_conditions') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Current Medications</label>
                            <textarea name="medications" class="form-control" rows="2">{{ old('medications') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Past Surgeries</label>
                            <textarea name="past_surgeries" class="form-control" rows="2">{{ old('past_surgeries') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Family History</label>
                            <textarea name="family_history" class="form-control" rows="2">{{ old('family_history') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="smoking" name="smoking"
                                        {{ old('smoking') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="smoking">Smoker</label>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="alcohol" name="alcohol"
                                        {{ old('alcohol') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="alcohol">Alcohol</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="medical_notes" class="form-control" rows="2">{{ old('medical_notes') }}</textarea>
                        </div>
                    </div>

                    <!-- Step 3: Visit Details -->
                    <div class="stept d-none" id="step-3">
                        <h3>Visit Details</h3><br>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Visit Type</label>
                                <select name="visit_type" class="form-control">
                                    <option {{ old('visit_type') == 'OPD' ? 'selected' : '' }}>OPD</option>
                                    <option {{ old('visit_type') == 'IPD' ? 'selected' : '' }}>IPD</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Assigned Doctor</label>
                                <select name="doctor" class="form-control">
                                    <option value="">-- select --</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="2">{{ old('diagnosis') }}</textarea>
                        </div>
                    </div>

                    <!-- Step 4: Vitals -->
                    <div class="stept d-none" id="step-4">
                        <h3>Vitals</h3><br>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Height (cm)</label>
                                <input type="number" name="height" class="form-control" value="{{ old('height') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Weight (kg)</label>
                                <input type="number" name="weight" class="form-control" value="{{ old('weight') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>BP (e.g. 120/80)</label>
                                <input type="text" name="blood_pressure" class="form-control" value="{{ old('blood_pressure') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Pulse</label>
                                <input type="number" name="pulse" class="form-control" value="{{ old('pulse') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Documents -->
                    <div class="stept d-none" id="step-5">
                        <h3>Upload Documents</h3><br>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>ID Proof</label>
                                <input type="file" name="id_proof" class="form-control-file">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Medical Reports</label>
                                <input type="file" name="reports[]" multiple class="form-control-file">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Photo</label>
                                <input type="file" name="photo" class="form-control-file" accept="image/*">
                            </div>
                        </div>
                        @if($errors->any())
                            <div class="alert alert-danger mt-2">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="mt-4">
                        <button type="button" class="btn btn-secondary d-none" id="prevBtn">Back</button>
                        <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                        <button type="submit" class="btn btn-success d-none" id="submitBtn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    let currentStep = {{ $errors->any() ? 1 : 1 }};
    const totalSteps = 5;

    const updateStepView = () => {
        document.querySelectorAll('.stept').forEach((step, index) => {
            step.classList.toggle('d-none', index !== currentStep - 1);
        });
        document.getElementById('prevBtn').classList.toggle('d-none', currentStep === 1);
        document.getElementById('nextBtn').classList.toggle('d-none', currentStep === totalSteps);
        document.getElementById('submitBtn').classList.toggle('d-none', currentStep !== totalSteps);

        let progress = (currentStep / totalSteps) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressBar').innerText = 'Step ' + currentStep + ' of ' + totalSteps;
    };

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentStep < totalSteps) { currentStep++; updateStepView(); }
    });
    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentStep > 1) { currentStep--; updateStepView(); }
    });

    @if($errors->any())
        currentStep = 1; // jump back to step 1 on error so user sees what's wrong
    @endif

    updateStepView();
</script>
@endpush
