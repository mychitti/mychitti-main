@extends('layouts.vendor.app')
@section('title', 'Edit Patient')

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
                <h1 class="page-header-title"><i class="tio-edit"></i> Edit Patient</h1>
                <a href="{{ route('vendor.patient.show', $patient->id) }}" class="btn btn-sm btn-soft-secondary">
                    <i class="tio-arrow-backward"></i> Back
                </a>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" action="{{ route('vendor.patient.update', $patient->id) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @php $h = $patient->medicalHistory; @endphp

                    <h5 class="mb-3">Personal Details</h5>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $patient->name) }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" class="form-control"
                                value="{{ old('dob', $patient->dob) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select</option>
                                @foreach (['male', 'female', 'other'] as $g)
                                    <option value="{{ $g }}"
                                        {{ old('gender', $patient->gender) == $g ? 'selected' : '' }}>{{ ucfirst($g) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Blood Group</label>
                            <select name="blood_group" class="form-control">
                                <option value="">Select</option>
                                @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                                    <option {{ old('blood_group', $patient->blood_group) == $bg ? 'selected' : '' }}>
                                        {{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control"
                                value="{{ old('phone', $patient->phone) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $patient->email) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control"
                                value="{{ old('address', $patient->address) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>City</label>
                            <input type="text" name="city" class="form-control"
                                value="{{ old('city', $patient->city) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>State</label>
                            <select name="" id="" class="js-select2-custom">
                                <option value="">Select</option>
                                @foreach (\App\Models\State::all() as $state)
                                    <option {{ $patient->state == $state->id ? 'selected' : '' }}
                                        value="{{ $state->id }}" {{ old('state') == $state->id ? 'selected' : '' }}>
                                        {{ $state->state_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Pincode</label>
                            <input type="text" name="pincode" class="form-control"
                                value="{{ old('pincode', $patient->pincode) }}">
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
                                value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Phone</label>
                            <input type="text" name="emergency_contact_phone" class="form-control"
                                value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Relation</label>
                            <input type="text" name="emergency_contact_relation" class="form-control"
                                value="{{ old('emergency_contact_relation', $patient->emergency_contact_relation) }}">
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Medical History</h5>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Known Allergies</label>
                            <textarea name="allergies" class="form-control" rows="2">{{ old('allergies', $patient->allergies) }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Chronic Conditions</label>
                            <textarea name="chronic_conditions" class="form-control" rows="2">{{ old('chronic_conditions', $h->chronic_conditions ?? '') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Current Medications</label>
                            <textarea name="medications" class="form-control" rows="2">{{ old('medications', $h->current_medications ?? '') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Past Surgeries</label>
                            <textarea name="past_surgeries" class="form-control" rows="2">{{ old('past_surgeries', $h->past_surgeries ?? '') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Family History</label>
                            <textarea name="family_history" class="form-control" rows="2">{{ old('family_history', $h->family_history ?? '') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Notes</label>
                            <textarea name="medical_notes" class="form-control" rows="2">{{ old('medical_notes', $h->notes ?? '') }}</textarea>
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
                        <h5 class="mb-0">Documents</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" onclick="addDocRow()">
                            <i class="tio-add"></i> Add Document
                        </button>
                    </div>

                    {{-- Existing documents --}}
                    <div id="existingDocsList" class="mb-3">
                        @forelse($patient->documents as $doc)
                        <div class="d-flex align-items-center mb-2 p-2 border rounded" id="existingDoc_{{ $doc->id }}">
                            <span class="badge badge-soft-info mr-2" style="white-space:nowrap;">{{ ucfirst(str_replace('_',' ',$doc->document_type)) }}</span>
                            <span class="flex-grow-1 text-truncate small mr-2">{{ $doc->document_name }}</span>
                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-xs btn-soft-secondary mr-1">
                                <i class="tio-eye"></i>
                            </a>
                            <button type="button" class="btn btn-xs btn-soft-danger" onclick="deleteExistingDoc({{ $doc->id }})">
                                <i class="tio-delete"></i>
                            </button>
                        </div>
                        @empty
                        <p class="text-muted small" id="noDocsMsg">No documents uploaded yet.</p>
                        @endforelse
                    </div>

                    {{-- New document rows added dynamically --}}
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
                        <a href="{{ route('vendor.patient.show', $patient->id) }}"
                            class="btn btn-soft-secondary ml-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
const deleteDocUrl = "{{ url('vendor/patient/' . $patient->id . '/document') }}";

function deleteExistingDoc(docId) {
    if (!confirm('Delete this document?')) return;
    fetch(deleteDocUrl + '/' + docId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) document.getElementById('existingDoc_' + docId)?.remove();
    });
}

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
