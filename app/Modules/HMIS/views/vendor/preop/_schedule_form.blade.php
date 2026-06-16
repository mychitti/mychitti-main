<form method="post" action="{{ route('vendor.preop.schedule') }}">
    @csrf
    <div class="form-grid">
        <div class="form-group"><label class="form-label">Admitted Patient *</label>
            <select class="fselect" name="ipd_admission_id" required>
                <option value="">Select admitted patient…</option>
                @foreach($admissions as $a)
                    <option value="{{ $a->id }}">{{ $a->patient->name ?? 'Patient' }} · {{ $a->bed->bed_number ?? '' }} · {{ $a->diagnosis }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group"><label class="form-label">Procedure *</label><input class="finput" name="procedure" placeholder="e.g. Laparoscopic Cholecystectomy" required></div>
        <div class="form-group"><label class="form-label">Surgeon</label>
            <select class="fselect" name="surgeon">
                <option value="">— Select —</option>
                @foreach($doctors as $d)<option value="Dr. {{ trim(($d->employee->f_name ?? '').' '.($d->employee->l_name ?? '')) }}">Dr. {{ trim(($d->employee->f_name ?? '').' '.($d->employee->l_name ?? '')) ?: $d->specialization }}</option>@endforeach
            </select>
        </div>
        <div class="form-group"><label class="form-label">Anaesthetist</label><input class="finput" name="anaesthetist" placeholder="Dr. name"></div>
        <div class="form-group"><label class="form-label">OT Room</label><input class="finput" name="ot_room" placeholder="OT-2"></div>
        <div class="form-group"><label class="form-label">Scheduled Date &amp; Time</label><input class="finput" type="datetime-local" name="scheduled_at"></div>
        <div class="form-group"><label class="form-label">Est. Duration</label><input class="finput" name="est_duration" placeholder="60–90 min"></div>
        <div class="form-group"><label class="form-label">NBM Since</label><input class="finput" name="nbm_since" placeholder="07:00 AM"></div>
    </div>
    <div style="display:flex;justify-content:flex-end"><button class="btn btn-teal">🔪 Schedule &amp; Start Pre-Op</button></div>
</form>
