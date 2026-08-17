@extends('layouts.vendor.app')
@section('title', 'Documents Sent to Patients')

@section('content')
@php
    // Only the kinds that carry a document — a follow-up reminder or a feedback request never
    // creates a share row, so they have nothing to list here.
    $kindOptions = [
        'treatment'        => 'Consultation summary',
        'prescription'     => 'Prescription',
        'prescription_pdf' => 'Prescription (PDF)',
        'medicines'        => 'Medicine instructions',
        'lab'              => 'Lab report',
        'radiology'        => 'Radiology report',
        'document'         => 'Document (sent by hand)',
    ];
    $kindColour = [
        'treatment'        => '#dbeafe',
        'prescription'     => '#d1fae5',
        'prescription_pdf' => '#d1fae5',
        'medicines'        => '#fef3c7',
        'lab'              => '#e0e7ff',
        'radiology'        => '#cffafe',
        'document'         => '#ede9fe',
    ];
@endphp

<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-send"></i> Documents Sent to Patients</h1>
                <p class="text-muted mb-0" style="font-size:13px;">
                    Every record shared on WhatsApp — the link the patient opened, or the file they were sent.
                </p>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        @foreach ([
            ['Sent', $summary['total'], 'tio-send', 'primary'],
            ['Opened', $summary['opened'], 'tio-visible', 'success'],
            ['Not opened yet', $summary['pending'], 'tio-time', 'warning'],
            ['Sent as file', $summary['files'], 'tio-attachment', 'secondary'],
        ] as [$label, $value, $icon, $tone])
            <div class="col-sm-6 col-lg-3 mb-2">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted" style="font-size:12px;">{{ $label }}</span>
                            <h2 class="mb-0">{{ $value }}</h2>
                        </div>
                        <i class="{{ $icon }} text-{{ $tone }}" style="font-size:26px; opacity:.55;"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow">
        <div class="card-header">
            <form method="get" class="form-row w-100 align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label style="font-size:12px;">Patient</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                           placeholder="Name, UID or phone">
                </div>
                <div class="form-group col-md-2 mb-2">
                    <label style="font-size:12px;">Document</label>
                    <select name="kind" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach ($kindOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('kind') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <label style="font-size:12px;">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="opened"  {{ request('status') === 'opened' ? 'selected' : '' }}>Opened</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Not opened yet</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired unopened</option>
                        <option value="pdf"     {{ request('status') === 'pdf' ? 'selected' : '' }}>Sent as file</option>
                    </select>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <label style="font-size:12px;">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2 mb-2">
                    <label style="font-size:12px;">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-1 mb-2">
                    <button class="btn btn-sm btn--primary btn-block">Filter</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Patient</th>
                        <th>Document</th>
                        <th>Sent</th>
                        <th>To</th>
                        <th>Delivery</th>
                        <th>Opened</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shares as $s)
                        @php
                            $label   = $kindOptions[$s->kind] ?? (\App\Services\HmisWhatsAppShare::KINDS[$s->kind]['label'] ?? ucfirst(str_replace('_', ' ', $s->kind)));
                            $asPdf   = ($s->sent_as ?? 'link') === 'pdf';
                            $expired = !$asPdf && $s->expires_at && \Carbon\Carbon::parse($s->expires_at)->isPast();
                            $revoked = !$asPdf && $s->revoked_at;
                            $live    = !$asPdf && !$expired && !$revoked;
                            $views   = (int) ($s->views ?? 0);
                            $url     = \App\Services\HmisWhatsAppShare::panelUrl($s->kind, $s->record_id ? (int) $s->record_id : null);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('vendor.patient.show', $s->patient_id) }}" class="font-weight-bold">
                                    {{ $s->patient_name ?: 'Patient #' . $s->patient_id }}
                                </a>
                                <div class="text-muted" style="font-size:11px;">{{ $s->patient_uid }}</div>
                            </td>
                            <td>
                                <span class="badge" style="font-size:11px; background:{{ $kindColour[$s->kind] ?? '#f3f4f6' }}; color:#374151; font-weight:600;">
                                    {{ $label }}
                                </span>
                                @if ($s->title ?? null)
                                    <div class="text-muted" style="font-size:11.5px;">{{ $s->title }}</div>
                                @endif
                            </td>
                            <td style="font-size:12px;">
                                {{ \Carbon\Carbon::parse($s->created_at)->format('d M Y') }}
                                <div class="text-muted" style="font-size:11px;">{{ \Carbon\Carbon::parse($s->created_at)->format('h:i A') }}</div>
                            </td>
                            <td style="font-size:12px;">{{ \App\Services\HmisWhatsAppShare::maskedPhone($s->sent_to ?: $s->patient_phone) }}</td>
                            <td style="font-size:12px;">
                                @if ($asPdf)
                                    <span class="text-muted"><i class="tio-attachment mr-1"></i>File</span>
                                @elseif ($revoked)
                                    <span class="text-danger"><i class="tio-blocked mr-1"></i>Revoked</span>
                                @elseif ($expired)
                                    <span class="text-muted"><i class="tio-time mr-1"></i>Expired</span>
                                @else
                                    <span class="text-primary"><i class="tio-link mr-1"></i>Link</span>
                                @endif
                            </td>
                            <td style="font-size:12px;">
                                @if ($asPdf)
                                    <span class="text-muted">—</span>
                                @elseif ($views > 0)
                                    <span class="text-success font-weight-bold">{{ $views }}×</span>
                                    <div class="text-muted" style="font-size:11px;">
                                        {{ \Carbon\Carbon::parse($s->last_viewed_at ?: $s->created_at)->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-warning">Not yet</span>
                                @endif
                            </td>
                            <td class="text-right" style="white-space:nowrap;">
                                @if ($live)
                                    <button type="button" class="btn btn-xs btn-soft-secondary"
                                            onclick="copyShareLink(this, '{{ \App\Services\HmisWhatsAppShare::recordUrl($s->token) }}')"
                                            title="Copy the link that was sent">
                                        <i class="tio-link"></i>
                                    </button>
                                @endif
                                @if ($url)
                                    <a href="{{ $url }}" target="_blank" class="btn btn-xs btn-soft-primary" title="Open this record">
                                        <i class="tio-visible"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="tio-send" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                                No documents have been sent yet for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shares->hasPages())
            <div class="card-footer d-flex justify-content-end">{{ $shares->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('script_2')
<script>
    function copyShareLink(btn, url) {
        const done = () => {
            const html = btn.innerHTML;
            btn.innerHTML = '<i class="tio-checkmark"></i>';
            setTimeout(() => { btn.innerHTML = html; }, 1500);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(done).catch(() => window.prompt('Copy this link', url));
            return;
        }
        // http:// panel — the clipboard API is unavailable, so fall back to a selectable prompt.
        const box = document.createElement('textarea');
        box.value = url; box.style.position = 'fixed'; box.style.opacity = '0';
        document.body.appendChild(box); box.select();
        try { document.execCommand('copy'); done(); } catch (e) { window.prompt('Copy this link', url); }
        document.body.removeChild(box);
    }
</script>
@endpush
