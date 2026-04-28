@extends('layouts.vendor.app')
@section('title', 'Consent — ' . $consent->title)

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Consent Form
        </h1>
        <div class="d-flex gap-2">
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
                    <div class="row mb-4" style="font-size:13px;">
                        <div class="col-6">
                            <strong>Patient:</strong> {{ $consent->patient?->name }}<br>
                            <strong>MUID:</strong> {{ $consent->patient?->patient_uid }}<br>
                            @if($consent->patient?->phone)
                            <strong>Phone:</strong> {{ $consent->patient->phone }}
                            @endif
                        </div>
                        <div class="col-6 text-right">
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
                    <div class="row mt-4" style="font-size:13px;">
                        <div class="col-4 text-center">
                            <div style="border-bottom:1px solid #333; margin-bottom:6px; min-height:40px;"></div>
                            <div><strong>Patient / Signatory</strong></div>
                            <div class="text-muted">{{ $consent->signatory_name ?: '—' }}</div>
                        </div>
                        <div class="col-4 text-center">
                            <div style="border-bottom:1px solid #333; margin-bottom:6px; min-height:40px;"></div>
                            <div><strong>Witness</strong></div>
                            <div class="text-muted">{{ $consent->witness_name ?: '—' }}</div>
                        </div>
                        <div class="col-4 text-center">
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
            <div class="d-flex justify-content-end mt-2 no-print">
                <form action="{{ route('vendor.consent.destroy', $consent->id) }}"
                      method="POST" onsubmit="return confirm('Delete this consent record?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="tio-delete-outlined"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css_or_js')
<style>
@media print {
    .navbar, .sidebar, .page-header, .no-print, footer { display: none !important; }
    .content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
@endpush
