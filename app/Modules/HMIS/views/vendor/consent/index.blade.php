@extends('layouts.vendor.app')
@section('title', 'Consent Forms')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Consent Forms
        </h1>
        @if (hasPermission('consent_form', 'add'))
        <a href="{{ route('vendor.consent.create') }}" class="btn btn-sm btn--primary">
            <i class="tio-add"></i> New Consent
        </a>
        @endif
    </div>

    @if(hasPermission('consent_form', 'list'))
    <div class="card">
        <div class="card-header py-2">
            <form method="GET" class="d-flex gap-2" style="max-width:360px;">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search by patient or title…" value="{{ $search }}">
                <button class="btn btn-sm btn-outline-primary">Search</button>
                @if($search)
                    <a href="{{ route('vendor.consent.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Patient</th>
                            <th>Title</th>
                            <th style="width:160px;">Admission</th>
                            <th style="width:140px;">Signed By</th>
                            <th style="width:140px;">Date Signed</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consents as $c)
                        <tr>
                            <td>
                                <a href="{{ route('vendor.patient.show', $c->patient_id) }}">
                                    {{ $c->patient?->name }}
                                </a>
                                <br><small class="text-muted">{{ $c->patient?->patient_uid }}</small>
                            </td>
                            <td>{{ $c->title }}</td>
                            <td>
                                @if($c->admission)
                                    <a href="{{ route('vendor.ipd.show', $c->ipd_admission_id) }}"
                                       style="font-size:12px;">
                                        {{ $c->admission->admission_number }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td style="font-size:12px;">{{ $c->signatory_name ?: '—' }}</td>
                            <td style="font-size:12px;">{{ $c->signed_at?->format('d M Y H:i') }}</td>
                            <td>
                                @if (hasPermission('consent_form', 'view'))
                                <a href="{{ route('vendor.consent.show', $c->id) }}"
                                   class="btn btn-xs btn-outline-primary">
                                    <i class="tio-visible"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No consent forms found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($consents->hasPages())
        <div class="card-footer">{{ $consents->links() }}</div>
        @endif
    </div>
    @endif
</div>
@endsection
