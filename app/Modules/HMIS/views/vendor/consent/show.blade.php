@extends('layouts.vendor.app')
@section('title', 'Consent — ' . $consent->title)

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    {{-- Stacks on a phone rather than squeezing a two-line title against two buttons. The buttons
         keep their labels on one line each (white-space:nowrap) — "Back to Patient" breaking after
         "Back to" is what made this look broken. --}}
    <div class="page-header consent-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Consent Form
        </h1>
        <div class="d-flex gap-2 consent-actions">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
                <i class="tio-print"></i> Print
            </button>
            @if($consent->admission)
            <a href="{{ route('vendor.ipd.show', $consent->ipd_admission_id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Back to IPD
            </a>
            @else
            <a href="{{ route('vendor.patient.show', $consent->patient_id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Back to Patient
            </a>
            @endif
        </div>
    </div>

    {{-- ── Printable consent document ────────────────────────────────── --}}
    <div class="row justify-content-center">
        <div class="col-lg-8" id="consentPrintArea">
            <div class="card">
                <div class="card-body p-4" style="font-family: Georgia, serif;">

                    {{-- Header --}}
                    <div class="text-center mb-4">
                        <h4 style="font-size:18px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
                            {{ $consent->title }}
                        </h4>
                        <div class="text-muted" style="font-size:12px;">
                            Signed on: {{ $consent->signed_at?->format('d M Y, h:i A') }}
                        </div>
                    </div>

                    <hr>

                    {{-- Patient + Admission info --}}
                    <div class="row mb-4 consent-meta" style="font-size:13px;">
                        <div class="col-sm-6 mb-2 mb-sm-0">
                            <strong>Patient:</strong> {{ $consent->patient?->name }}<br>
                            <strong>MUID:</strong> {{ $consent->patient?->patient_uid }}<br>
                            @if($consent->patient?->phone)
                            <strong>Phone:</strong> {{ $consent->patient->phone }}
                            @endif
                        </div>
                        <div class="col-sm-6 text-sm-right">
                            @if($consent->admission)
                            <strong>Admission #:</strong> {{ $consent->admission->admission_number }}<br>
                            <strong>Admitted:</strong> {{ $consent->admission->admission_date?->format('d M Y') }}<br>
                            @endif
                            <strong>Date:</strong> {{ $consent->signed_at?->format('d M Y') }}
                        </div>
                    </div>

                    {{-- Consent body --}}
                    <div style="font-size:14px; line-height:1.8; white-space:pre-wrap; min-height:300px;">{{ $consent->content }}</div>

                    <hr class="mt-5">

                    {{-- Signatures --}}
                    <div class="row mt-4 consent-signatures" style="font-size:13px;">
                        <div class="col-sm-4 text-center mb-4 mb-sm-0">
                            <div style="border-bottom:1px solid #333; margin-bottom:6px; min-height:40px;"></div>
                            <div><strong>Patient / Signatory</strong></div>
                            <div class="text-muted">{{ $consent->signatory_name ?: '—' }}</div>
                        </div>
                        <div class="col-sm-4 text-center mb-4 mb-sm-0">
                            <div style="border-bottom:1px solid #333; margin-bottom:6px; min-height:40px;"></div>
                            <div><strong>Witness</strong></div>
                            <div class="text-muted">{{ $consent->witness_name ?: '—' }}</div>
                        </div>
                        <div class="col-sm-4 text-center mb-4 mb-sm-0">
                            <div style="border-bottom:1px solid #333; margin-bottom:6px; min-height:40px;"></div>
                            <div><strong>Staff / Doctor</strong></div>
                            <div class="text-muted">
                                {{ $consent->createdBy?->f_name }} {{ $consent->createdBy?->l_name }}
                            </div>
                        </div>
                    </div>

                    @if($consent->notes)
                    <div class="mt-3 p-2 bg-light rounded" style="font-size:12px;">
                        <strong>Notes:</strong> {{ $consent->notes }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Delete button (outside print area) --}}
            @if (hasPermission('consent_form', 'delete'))
            <div class="d-flex justify-content-end mt-2 no-print">
                <form action="{{ route('vendor.consent.destroy', $consent->id) }}"
                      method="POST" onsubmit="return confirm('Delete this consent record?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="tio-delete-outlined"></i> Delete
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('css_or_js')
<style>
/* ── Phones ──
   The header put a two-line title and two buttons on one flex row, which is what made this look
   broken. Stacked, with each button keeping its own label on one line — "Back to Patient" folding
   after "Back to" was the worst of it. */
@media (max-width: 575.98px) {
    .consent-header {
        flex-direction: column;
        align-items: stretch !important;
        gap: 10px;
    }
    .consent-header .page-header-title { font-size: 18px; }
    .consent-actions > .btn {
        flex: 1;
        justify-content: center;
        white-space: nowrap;
    }
    /* A consent form is read, not skimmed — the body gets the room instead of the padding. */
    #consentPrintArea .card-body { padding: 18px 16px !important; }
}

/* ── Print ──
   This is the copy that gets signed and filed, so it is worth more than hiding the chrome. The
   page is reduced to the document itself: everything outside #consentPrintArea is removed rather
   than relying on a list of class names that will not keep up with the layout around it. */
@media print {
    @page { margin: 14mm; }

    body * { visibility: hidden; }
    #consentPrintArea, #consentPrintArea * { visibility: visible; }
    #consentPrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
        padding: 0 !important;
    }

    .no-print, .no-print * { display: none !important; }

    .content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
    #consentPrintArea .card-body { padding: 0 !important; }

    /* A signature block split across a page break is a form somebody has to print again. */
    .consent-signatures { page-break-inside: avoid; break-inside: avoid; }
    .consent-signatures > div { page-break-inside: avoid; break-inside: avoid; }

    /* Bootstrap's grid is float/flex based and survives printing, but the muted greys wash out on
       most printers — force the ink so a signatory's name is actually legible on paper. */
    #consentPrintArea .text-muted { color: #444 !important; }
    #consentPrintArea .bg-light {
        background: #f2f2f2 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
@endpush
