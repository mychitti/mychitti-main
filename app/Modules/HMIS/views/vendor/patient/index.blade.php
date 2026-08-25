@extends('layouts.vendor.app')
@section('title', 'New Patient')

@push('css_or_js')
    <style>
        .form-row {
            margin-top: 6px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-header-title"><i class="tio-edit"></i> New Patient</h1>

            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" action="{{ route('vendor.patient.save') }}" enctype="multipart/form-data">
                    @csrf

                    <h5 class="mb-3">Personal Details</h5>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control" value="{{ old('dob') }}"
                                max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Age <small class="text-muted">(years)</small></label>
                            <input type="number" id="age" name="age" class="form-control" value="{{ old('age') }}"
                                min="0" max="150" placeholder="Auto from DOB, or type it">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select</option>
                                @foreach (['male', 'female', 'other'] as $g)
                                    <option value="{{ $g }}" {{ old('gender') == $g ? 'selected' : '' }}>
                                        {{ ucfirst($g) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Blood Group</label>
                            <select name="blood_group" class="form-control">
                                <option value="">Select</option>
                                @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                                    <option {{ old('blood_group') == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Phone</label>
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
                            <select name="" id="" class="js-select2-custom">
                                <option value="">Select</option>
                                @foreach (\App\Models\State::all() as $state)
                                    <option value="{{ $state->id }}" {{ old('state') == $state->id ? 'selected' : '' }}>
                                        {{ $state->state_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Photo</label>
                            <input type="file" name="photo" class="form-control">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Emergency Contact</h5>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control"
                                value="{{ old('emergency_contact_name') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Phone</label>
                            <input type="text" name="emergency_contact_phone" class="form-control"
                                value="{{ old('emergency_contact_phone') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Relation</label>
                            <input type="text" name="emergency_contact_relation" class="form-control"
                                value="{{ old('emergency_contact_relation') }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Medical History</h5>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Known Allergies</label>
                            <textarea name="allergies" class="form-control" rows="2">{{ old('allergies') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Chronic Conditions</label>
                            <textarea name="chronic_conditions" class="form-control" rows="2">{{ old('chronic_conditions') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Current Medications</label>
                            <textarea name="medications" class="form-control" rows="2">{{ old('medications') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Past Surgeries</label>
                            <textarea name="past_surgeries" class="form-control" rows="2">{{ old('past_surgeries') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Family History</label>
                            <textarea name="family_history" class="form-control" rows="2">{{ old('family_history') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Notes</label>
                            <textarea name="medical_notes" class="form-control" rows="2">{{ old('medical_notes') }}</textarea>
                        </div>
                        <div class="form-group col-md-3">
                            <div class="custom-control custom-checkbox mt-4">
                                <input type="checkbox" class="custom-control-input" id="smoking" name="smoking"
                                    {{ old('smoking', $h->smoking ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="smoking">Smoker</label>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <div class="custom-control custom-checkbox mt-4">
                                <input type="checkbox" class="custom-control-input" id="alcohol" name="alcohol"
                                    {{ old('alcohol', $h->alcohol ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="alcohol">Alcohol</label>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Prescriptions &amp; Documents</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" onclick="addDocRow()">
                            <i class="tio-add"></i> Add Document
                        </button>
                    </div>
                    <div id="newDocRows" class="mb-2"></div>

                    @if ($errors->any())
                        <div class="alert alert-danger mt-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-3 d-flex justify-content-end w-100 gap-2">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
// Age tracks DOB, but the desk can overwrite it — a patient who only knows "about 40" gets an
// age with no birth date, and a typed age is never clobbered by a later DOB edit.
(function () {
    const dob = document.getElementById('dob');
    const age = document.getElementById('age');
    if (!dob || !age) return;

    let manual = age.value !== '';

    function ageFrom(value) {
        const b = new Date(value);
        if (isNaN(b)) return '';
        const now = new Date();
        let a = now.getFullYear() - b.getFullYear();
        const m = now.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && now.getDate() < b.getDate())) a--;
        return a >= 0 && a <= 150 ? a : '';
    }

    dob.addEventListener('change', function () {
        if (!dob.value) return;
        const a = ageFrom(dob.value);
        if (a === '') return;
        age.value = a;
        manual = false;
    });

    age.addEventListener('input', function () { manual = true; });
})();

let docIdx = 0;
function addDocRow() {
    const i = docIdx++;
    const html = `<div class="form-row align-items-center mb-2" id="docRow_${i}">
        <div class="col-md-2 col-sm-3 col-4">
            <select name="docs[${i}][type]" class="form-control form-control-sm">
                <option value="report">Report</option>
                <option value="id_proof">ID Proof</option>
                <option value="prescription">Prescription</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-3 col-sm-4 col-7">
            <input type="text" name="docs[${i}][name]" placeholder="Label (optional)" class="form-control form-control-sm">
        </div>
        <div class="col">
            <input type="file" name="docs[${i}][file]" class="form-control form-control-sm" required>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-xs btn-soft-danger" onclick="removeDocRow(${i})"><i class="tio-clear"></i></button>
        </div>
    </div>`;
    document.getElementById('newDocRows').insertAdjacentHTML('beforeend', html);
}
function removeDocRow(i) {
    document.getElementById('docRow_' + i)?.remove();
}
</script>
@endpush
