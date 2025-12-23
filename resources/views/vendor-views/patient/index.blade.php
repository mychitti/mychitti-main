@extends('layouts.vendor.app')

@section('title', 'Add New')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')
    @php $offer = _isSubmoduleEnabled('9')['offer']; @endphp
    @if ( $offer)
        <div class="background-pattern" style="background-color: #fffcf5;">
            <div class="container py-5">
                <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">
                    <div class="col-md-7">
                        <h1 class="display-4 font-weight-bold"> Patient Management.<br>Simplified.</h1>
                        <p class="lead mt-4">Let our system handle patient records, appointments, and medical history so your
                            healthcare team can focus on care.</p>
                    </div>
                    <div class="col-md-5">
                        <div class="highlight-box">
                            <h5 class="font-weight-bold">Starting at
                                {{ \App\CentralLogics\Helpers::format_currency(500) }}/mo
                            </h5>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li>✓ Save Patient Detailed Info</li>
                                <li>✓ Save Patient Medical History & documents</li>
                                <li>✓ Appointment scheduling & reminders</li>
                                <li>✓ Secure access to medical records</li>
                                <li>✓ Telemedicine support</li>
                            </ul>
                            <a href="{{ route('vendor.sub-module.list', ['patient-management']) }}"
                                class="btn btn-primary btn-block">Enable Patient Management</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="content container-fluid {{ 0 ? 'covered-content' : '' }}"
        {{ 0 ? 'style="pointer-events: none;"' : '' }}>
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> New Patient </h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->

        <div class=" ">
            <div class="card shadow">

                <div class="card-body">
                    <div class="progress mb-4" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 20%;"
                            id="progressBar">Step 1 of 5</div>
                    </div>

                    <form id="patientForm" method="POST" action="" enctype="multipart/form-data">
                        @csrf

                        <!-- Step 1 -->
                        <div class="stept" id="step-1">
                            <h3>Personal Details</h3><br>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Date of Birth</label>
                                    <input type="date" name="dob" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select</option>
                                        <option>Male</option>
                                        <option>Female</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Blood Group</label>
                                    <select name="blood_group" class="form-control">
                                        <option value="">Select</option>
                                        @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $group)
                                            <option>{{ $group }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>

                                <div class="form-group col-md-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Address</label>
                                    <input type="text" name="address" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="stept d-none" id="step-2">
                            <h3>Medical History</h3>
                            <div class="form-group">
                                <label>Known Allergies</label>
                                <textarea name="allergies" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Chronic Conditions</label>
                                <textarea name="chronic_conditions" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Current Medications</label>
                                <textarea name="medications" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="stept d-none" id="step-3">
                            <h3>Visit Details</h3>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Visit Type</label>
                                    <select name="visit_type" class="form-control">
                                        <option>OPD</option>
                                        <option>IPD</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Assigned Doctor</label>
                                    <select name="doctor" class="form-control">
                                        <option>-- select --</option>
                                        <option>Dr. KM Sharma</option>
                                        <option>Dr. Neetu Bisht</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Diagnosis</label>
                                <textarea name="diagnosis" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="stept d-none" id="step-4">
                            <h3>Vitals</h3>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Height (cm)</label>
                                    <input type="number" name="height" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Weight (kg)</label>
                                    <input type="number" name="weight" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>BP</label>
                                    <input type="text" name="blood_pressure" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Pulse</label>
                                    <input type="number" name="pulse" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="stept d-none" id="step-5">
                            <h3>Upload Documents</h3>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>ID Proof</label>
                                    <input type="file" name="id_proof" class="form-control-file">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Medical Reports</label>
                                    <input type="file" name="reports[]" multiple class="form-control-file">
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-secondary d-none" id="prevBtn">Back</button>
                            <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                            <button type="submit" class="btn btn-success d-none" id="submitBtn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $("#category").on('change', function() {
            const selectedValue = $('#category option:selected').data('value');
            console.log(selectedValue)
            $("#ledger_account_type2").val(selectedValue).trigger('change'); // not .select2()
        });
    </script>
    <script>
        let currentStep = 1;
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
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepView();
            }
        });

        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateStepView();
            }
        });

        updateStepView();
    </script>
@endpush
