@extends('layouts.vendor.app')
@section('title', 'Prescription #' . $rx->id)

@section('content')
<div class="content container-fluid">

    {{-- $canEditRx is passed from PrescriptionController::show() --}}

    {{-- Toolbar (hidden on print) --}}
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('vendor.prescription.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Back
            </a>
            @if($canEditRx && (hasPermission('prescription', 'edit')))
            <a href="{{ route('vendor.prescription.edit', $rx->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-edit"></i> Edit
            </a>
            @endif
        </div>
        @if (hasPermission('prescription', 'print'))
        <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
            {{-- Only the PDF is offered by hand. The rest of what a patient is sent — the
                 prescription link, the medicine instructions, the follow-up — is a standing
                 decision the hospital makes once under Notification Settings and goes out on its
                 own when the prescription is finalized (PrescriptionController::autoSendToPatient),
                 so a button beside it would only send the same thing twice. The PDF is the one a
                 hospital genuinely re-sends: unlike the link it never expires, and a patient who
                 lost the file asks for it again. --}}
            @if ($rx->is_finalized && filled($rx->patient?->phone))
            <form method="post" action="{{ route('vendor.hmis-whatsapp.prescription-pdf', $rx->id) }}" class="mb-0"
                  onsubmit="return confirm('Send this prescription as a PDF to {{ addslashes($rx->patient->name ?? 'the patient') }} on WhatsApp?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success" title="Attach the prescription PDF and send it to the patient on WhatsApp">
                    <i class="tio-attachment"></i> Send as PDF
                </button>
            </form>
            @endif
            <button onclick="window.print()" class="btn btn--primary btn-sm">
                <i class="tio-print"></i> Print
            </button>
        </div>
        @endif
    </div>

    {{-- Printable Prescription --}}
    <div class="rx-print-wrap" id="rxPrint">

        {{-- Header --}}
        <div class="rx-header">
            <div class="rx-clinic">
                <h2 class="rx-clinic-name">{{ $rx->store?->name }}</h2>
                <p class="rx-clinic-addr">{{ $rx->store?->address }}</p>
                @if($rx->store?->phone)
                <p class="rx-clinic-phone">Ph: {{ $rx->store->phone }}</p>
                @endif
            </div>
            <div class="rx-doctor-box">
                <p class="rx-dr-name">
                    Dr. {{ $rx->doctorProfile?->employee?->f_name }}
                    {{ $rx->doctorProfile?->employee?->l_name }}
                </p>
                <p class="rx-dr-spec">{{ $rx->doctorProfile?->specialization }}</p>
                <p class="rx-dr-qual">{{ $rx->doctorProfile?->qualification }}</p>
                @if($rx->doctorProfile?->registration_number)
                <p class="rx-dr-reg">Reg. No: {{ $rx->doctorProfile->registration_number }}</p>
                @endif
            </div>
        </div>
        <hr class="rx-divider">

        {{-- Patient + Date --}}
        <div class="rx-patient-row">
            <div class="rx-patient-info">
                <span class="rx-label">Patient:</span>
                <strong>{{ $rx->patient?->name }}</strong>
                <span class="rx-muted">({{ $rx->patient?->patient_uid }})</span>
                @if($rx->patient?->gender)
                &bull; {{ ucfirst($rx->patient->gender) }}
                @endif
                @if($rx->patient?->dob)
                &bull; Age: {{ \Carbon\Carbon::parse($rx->patient->dob)->age }} yrs
                @endif
                @if($rx->patient?->blood_group)
                &bull; <span style="color:#dc2626;font-weight:600;">{{ $rx->patient->blood_group }}</span>
                @endif
            </div>
            <div class="rx-date-box">
                <span class="rx-label">Date:</span> {{ $rx->created_at->format('d M Y') }}
                @if($rx->appointment)
                <br><span class="rx-label">Appt #:</span> {{ $rx->appointment_id }}
                @endif
            </div>
        </div>

        {{-- Diagnosis --}}
        @if($rx->diagnosis)
        <div class="rx-section mt-3">
            <p class="rx-section-label">Diagnosis</p>
            <p class="rx-section-body">{{ $rx->diagnosis }}</p>
        </div>
        @endif

        {{-- Medicines --}}
        <div class="rx-symbol-row mt-3">
            <span class="rx-symbol">℞</span>
        </div>
        @if($rx->items->count())
        <table class="rx-med-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Qty</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rx->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $item->medicine_name }}</strong></td>
                    <td>{{ $item->dosage ?: '—' }}</td>
                    <td>{{ $item->frequency ?: '—' }}</td>
                    <td>{{ $item->duration ?: '—' }}</td>
                    <td>{{ $item->quantity ?: '—' }}</td>
                    <td>{{ $item->instructions ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="rx-muted mt-2">No medicines prescribed.</p>
        @endif

        {{-- Notes --}}
        @if($rx->notes)
        <div class="rx-section mt-4">
            <p class="rx-section-label">Advice / Notes</p>
            <p class="rx-section-body">{{ $rx->notes }}</p>
        </div>
        @endif

        {{-- Follow-up --}}
        @if($rx->follow_up_date)
        <div class="rx-followup mt-3">
            <strong>Follow-up:</strong> {{ $rx->follow_up_date->format('d M Y') }}
        </div>
        @endif

        {{-- Signature --}}
        <div class="rx-signature-row">
            <div>
                @if($rx->is_finalized)
                <span class="badge badge-soft-success">Finalized</span>
                @else
                <span class="badge badge-soft-warning no-print">Draft</span>
                @endif
            </div>
            <div class="rx-sig-box">
                <div class="rx-sig-line"></div>
                <p>Dr. {{ $rx->doctorProfile?->employee?->f_name }}
                   {{ $rx->doctorProfile?->employee?->l_name }}</p>
            </div>
        </div>

        <p class="rx-footer-note">This prescription is computer generated.</p>
    </div>{{-- end rx-print-wrap --}}
</div>
@endsection

@push('css_or_js')
<style>
/* ── Screen styles ────────────────────────────────────── */
.rx-print-wrap {
    max-width: 800px; margin: 0 auto;
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 10px; padding: 28px 32px;
    font-family: 'Times New Roman', Times, serif;
    color: #111;
}
.rx-header { display: flex; justify-content: space-between; align-items: flex-start; }
.rx-clinic-name { font-size: 20px; font-weight: 700; margin: 0 0 4px; }
.rx-clinic-addr, .rx-clinic-phone { font-size: 12px; color: #555; margin: 0; }
.rx-doctor-box { text-align: right; }
.rx-dr-name { font-size: 15px; font-weight: 700; margin: 0 0 2px; }
.rx-dr-spec, .rx-dr-qual, .rx-dr-reg { font-size: 12px; color: #555; margin: 0; }
.rx-divider { border-top: 2px solid #1d4ed8; margin: 12px 0; }
.rx-patient-row { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; }
.rx-patient-info { font-size: 13px; }
.rx-date-box { font-size: 13px; text-align: right; }
.rx-label { font-weight: 600; }
.rx-muted { color: #6b7280; font-size: 12px; }
.rx-section-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #374151; margin: 0 0 4px; }
.rx-section-body { font-size: 14px; margin: 0; }
.rx-symbol { font-size: 36px; font-weight: 700; color: #1d4ed8; line-height: 1; }
.rx-med-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
.rx-med-table th { background: #eff6ff; text-align: left; padding: 6px 10px; border-bottom: 2px solid #bfdbfe; font-size: 12px; }
.rx-med-table td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; }
.rx-med-table tr:last-child td { border-bottom: none; }
.rx-followup { font-size: 14px; background: #f0fdf4; border-left: 3px solid #22c55e; padding: 6px 12px; border-radius: 4px; }
.rx-signature-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 32px; }
.rx-sig-box { text-align: center; }
.rx-sig-line { border-top: 1.5px solid #374151; width: 180px; margin-bottom: 4px; }
.rx-sig-box p { font-size: 12px; margin: 0; }
.rx-footer-note { font-size: 10px; color: #9ca3af; text-align: center; margin-top: 20px; }

/* ── Print styles ─────────────────────────────────────── */
@media print {
    body * { visibility: hidden; }
    #rxPrint, #rxPrint * { visibility: visible; }
    #rxPrint { position: fixed; top: 0; left: 0; width: 100%; padding: 20px 28px; border: none; }
    .no-print { display: none !important; }
}
</style>
@endpush
