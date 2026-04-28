{{-- Vitals-only partial — used in "Already Booked" mode on the OPD register --}}
{{-- Patient/doctor come from the service_request; staff just fills complaint + vitals --}}

<div class="form-group">
    <label class="input-label">Chief Complaint</label>
    <textarea name="chief_complaint" class="form-control" rows="2"
        placeholder="Patient's main complaint...">{{ old('chief_complaint') }}</textarea>
</div>

<div class="card mb-3">
    <div class="card-header py-2"><h6 class="mb-0">Vitals</h6></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="input-label" style="font-size:12px;">Blood Pressure (mmHg)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="bp_systolic" class="form-control" placeholder="Systolic" min="0" max="300" value="{{ old('bp_systolic') }}">
                        <div class="input-group-text">/</div>
                        <input type="number" name="bp_diastolic" class="form-control" placeholder="Diastolic" min="0" max="200" value="{{ old('bp_diastolic') }}">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="input-label" style="font-size:12px;">Temp (°F)</label>
                    <input type="number" name="temperature" class="form-control form-control-sm" placeholder="98.6" min="90" max="110" step="0.1" value="{{ old('temperature') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="input-label" style="font-size:12px;">Pulse (bpm)</label>
                    <input type="number" name="pulse_rate" class="form-control form-control-sm" placeholder="72" min="0" max="300" value="{{ old('pulse_rate') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="input-label" style="font-size:12px;">SpO2 (%)</label>
                    <input type="number" name="spo2" class="form-control form-control-sm" placeholder="98" min="0" max="100" value="{{ old('spo2') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="input-label" style="font-size:12px;">Resp. Rate (/min)</label>
                    <input type="number" name="respiratory_rate" class="form-control form-control-sm" placeholder="16" min="0" max="100" value="{{ old('respiratory_rate') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="input-label" style="font-size:12px;">Weight (kg)</label>
                    <input type="number" name="weight" class="form-control form-control-sm" placeholder="70" min="0" step="0.1" value="{{ old('weight') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="input-label" style="font-size:12px;">Height (cm)</label>
                    <input type="number" name="height" class="form-control form-control-sm" placeholder="170" min="0" value="{{ old('height') }}">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="input-label">Notes</label>
    <textarea name="notes" class="form-control" rows="3" placeholder="Clinical notes, observations...">{{ old('notes') }}</textarea>
</div>
