@php
    $patient = $record->patient ?? null;
    $doctor  = \App\Services\HmisWhatsAppShare::doctorName($record->doctorProfile ?? null);
    $titles  = [
        'visit_registered' => 'Visit Registered',
        'treatment'    => 'Consultation Summary',
        'prescription' => 'Prescription',
        'medicines'    => 'How to take your medicines',
        'lab'          => 'Lab Report',
        'radiology'    => 'Radiology Report',
    ];
    $title = $titles[$kind] ?? 'Health Record';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — {{ $store->name ?? 'Health Record' }}</title>
    <style>
        /* Colours are literal hex on purpose — do NOT refactor them back into CSS variables.
           mpdf renders this same page into the WhatsApp attachment and has no var() support, so
           "border:1px solid var(--line)" reaches it as a border with no colour at all and crashes
           it (Uninitialized string offset 0 in Mpdf::_setBorderLine). Palette, for editing:
           ink #1e293b · mute #64748b · line #e6e9ef · bg #f6f8fb · brand #128c7e */
        * { box-sizing:border-box; }
        body {
            margin:0; padding:16px; background:#f6f8fb; color:#1e293b;
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
            font-size:15px; line-height:1.55;
        }
        .wrap { max-width:680px; margin:0 auto; }
        .card { background:#fff; border:1px solid #e6e9ef; border-radius:14px; padding:20px; margin-bottom:14px; }
        .clinic { text-align:center; border-bottom:1px solid #e6e9ef; padding-bottom:14px; margin-bottom:14px; }
        .clinic h1 { font-size:19px; margin:0 0 4px; }
        .clinic p { margin:0; font-size:13px; color:#64748b; }
        .kind { display:inline-block; background:#e8f7ef; color:#128c7e; font-size:12px; font-weight:700;
            padding:4px 12px; border-radius:20px; letter-spacing:.3px; text-transform:uppercase; margin-bottom:12px; }
        .meta { display:flex; flex-wrap:wrap; gap:10px 26px; font-size:14px; margin-bottom:4px; }
        .meta div { min-width:130px; }
        .meta span { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; }
        h2 { font-size:14px; text-transform:uppercase; letter-spacing:.4px; color:#64748b;
            margin:0 0 8px; font-weight:700; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.3px; color:#64748b;
            border-bottom:1px solid #e6e9ef; padding:8px 6px; font-weight:600; }
        td { padding:10px 6px; border-bottom:1px solid #f2f4f8; vertical-align:top; }
        tr:last-child td { border-bottom:0; }
        .med { font-weight:600; }
        .sub { font-size:13px; color:#64748b; }
        .flag { font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; white-space:nowrap; }
        .flag-high { background:#fdeaea; color:#c0392b; }
        .flag-low  { background:#fff5e6; color:#b9770e; }
        .flag-ok   { background:#e8f7ef; color:#128c7e; }
        .note { background:#f6f8fb; border-left:3px solid #128c7e; border-radius:8px;
            padding:12px 14px; font-size:14px; }
        .foot { text-align:center; font-size:12px; color:#64748b; padding:6px 0 20px; }
        .btn { display:block; width:100%; padding:12px; border:1px solid #e6e9ef; border-radius:10px;
            background:#fff; color:#1e293b; font-size:14px; font-weight:600; cursor:pointer; }
        .scroll { overflow-x:auto; }
        @media print {
            body { background:#fff; padding:0; }
            .card { border:0; padding:0; }
            .no-print { display:none; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="clinic">
            <h1>{{ $store->name ?? 'Health Record' }}</h1>
            @if (!empty($store->address))
                <p>{{ $store->address }}</p>
            @endif
            @if (!empty($store->phone))
                <p>Ph: {{ $store->phone }}</p>
            @endif
        </div>

        <div class="kind">{{ $title }}</div>

        <div class="meta">
            <div>
                <span>Patient</span>
                <b>{{ $patient->name ?? '—' }}</b>
            </div>
            @if (!empty($patient->patient_uid))
                <div>
                    <span>Patient ID</span>
                    {{ $patient->patient_uid }}
                </div>
            @endif
            @if ($doctor)
                <div>
                    <span>Doctor</span>
                    {{ $doctor }}
                </div>
            @endif
            <div>
                <span>Date</span>
                @php
                    $on = $record->visit_date ?? $record->reported_at ?? $record->created_at;
                @endphp
                {{ $on ? \Carbon\Carbon::parse($on)->format('d M Y') : '—' }}
            </div>
        </div>
    </div>

    @if ($kind === 'visit_registered' && filled($record->token_number))
        <div class="card">
            <h2>Your token number</h2>
            <div style="font-size:34px;font-weight:800;color:#128c7e;line-height:1.1;">{{ $record->token_number }}</div>
            <div class="mute" style="margin-top:6px;">Please show this at reception when you arrive.</div>
        </div>
    @endif

    {{-- One visit, two moments: the slip handed over at registration and the summary written
         afterwards. Diagnosis and treatment are simply still empty at the first of them. --}}
    @if (in_array($kind, ['treatment', 'visit_registered']))
        @if (filled($record->chief_complaint))
            <div class="card">
                <h2>Reported complaint</h2>
                <div>{{ $record->chief_complaint }}</div>
            </div>
        @endif
        @if (filled($record->diagnosis))
            <div class="card">
                <h2>Diagnosis</h2>
                <div>{{ $record->diagnosis }}</div>
            </div>
        @endif
        @if (filled($record->treatment))
            <div class="card">
                <h2>Treatment advised</h2>
                <div>{{ $record->treatment }}</div>
            </div>
        @endif

        @php
            $vitals = array_filter([
                'BP'          => $record->bp_systolic && $record->bp_diastolic ? $record->bp_systolic . '/' . $record->bp_diastolic . ' mmHg' : null,
                'Pulse'       => $record->pulse_rate ? $record->pulse_rate . ' bpm' : null,
                'Temperature' => $record->temperature ? $record->temperature . ' °F' : null,
                'SpO2'        => $record->spo2 ? $record->spo2 . '%' : null,
                'Weight'      => $record->weight ? $record->weight . ' kg' : null,
                'Height'      => $record->height ? $record->height . ' cm' : null,
            ]);
        @endphp
        @if (!empty($vitals))
            <div class="card">
                <h2>Vitals recorded</h2>
                <div class="meta">
                    @foreach ($vitals as $label => $value)
                        <div>
                            <span>{{ $label }}</span>
                            {{ $value }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (filled($record->notes))
            <div class="card">
                <h2>Doctor's notes</h2>
                <div class="note">{{ $record->notes }}</div>
            </div>
        @endif
    @endif

    @if (in_array($kind, ['prescription', 'medicines']))
        @if (filled($record->diagnosis))
            <div class="card">
                <h2>Diagnosis</h2>
                <div>{{ $record->diagnosis }}</div>
            </div>
        @endif

        <div class="card">
            <h2>Your medicines</h2>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Dose</th>
                            <th>When</th>
                            <th>How long</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($record->items as $item)
                            <tr>
                                <td>
                                    <div class="med">{{ $item->medicine_name }}</div>
                                    @if (filled($item->instructions))
                                        <div class="sub">{{ $item->instructions }}</div>
                                    @endif
                                </td>
                                <td>{{ $item->dosage ?: '—' }}</td>
                                <td>{{ $item->frequency ?: '—' }}</td>
                                <td>{{ $item->duration ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="sub">No medicines listed.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Please remember</h2>
            <div class="note">
                Take every medicine exactly as written above, and finish the full course even if you feel better
                sooner. Do not change the dose or stop early without asking your doctor. If anything feels wrong
                after a dose, contact {{ $store->name ?? 'the hospital' }} straight away.
            </div>
        </div>

        @if (filled($record->notes))
            <div class="card">
                <h2>Doctor's notes</h2>
                <div class="note">{{ $record->notes }}</div>
            </div>
        @endif

        @if ($record->follow_up_date)
            <div class="card">
                <h2>Next visit</h2>
                <div>{{ \Carbon\Carbon::parse($record->follow_up_date)->format('d M Y') }}</div>
            </div>
        @endif
    @endif

    @if ($kind === 'lab')
        <div class="card">
            <h2>Tests done</h2>
            <div>{{ $record->items->pluck('test_name')->filter()->implode(', ') ?: '—' }}</div>
            @if ($record->order_no)
                <div class="sub">Order {{ $record->order_no }}</div>
            @endif
        </div>

        <div class="card">
            <h2>Results</h2>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th>Result</th>
                            <th>Normal range</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($record->results as $res)
                            @php
                                $flag = strtolower((string) $res->result_flag);
                                $cls  = str_contains($flag, 'high') ? 'flag-high'
                                      : (str_contains($flag, 'low') ? 'flag-low' : 'flag-ok');
                                $range = $res->ref_range_text
                                    ?: trim(($res->normal_low !== null ? $res->normal_low : '') . ' – ' . ($res->normal_high !== null ? $res->normal_high : ''), ' –');
                            @endphp
                            <tr>
                                <td class="med">{{ $res->parameter_name }}</td>
                                <td>
                                    {{ $res->result_value }} {{ $res->unit }}
                                    @if ($flag && $flag !== 'normal')
                                        <span class="flag {{ $cls }}">{{ strtoupper($res->result_flag) }}</span>
                                    @endif
                                </td>
                                <td class="sub">{{ $range ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="sub">Results are not published yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="note">
                A result outside the normal range does not by itself mean something is wrong. Please discuss this
                report with your doctor before acting on it.
            </div>
        </div>
    @endif

    @if ($kind === 'radiology')
        <div class="card">
            <h2>Scan done</h2>
            <div>{{ trim(($record->modality ?? '') . ' ' . ($record->study_name ?? '')) ?: '—' }}</div>
            @php
                $studyMeta = array_filter([
                    $record->body_part ? 'Body part: ' . $record->body_part : null,
                    $record->study_no ? 'Study ' . $record->study_no : null,
                    $record->radiologist ? 'Reported by ' . $record->radiologist : null,
                ]);
            @endphp
            @if ($studyMeta)
                <div class="sub">{{ implode(' · ', $studyMeta) }}</div>
            @endif
        </div>

        {{-- Clinical history first, then findings, then the impression — the order a radiologist
             writes in, so the report reads the same here as it does on the printed copy. --}}
        @if (filled($record->clinical_history))
            <div class="card">
                <h2>Clinical history</h2>
                <div>{!! nl2br(e($record->clinical_history)) !!}</div>
            </div>
        @endif

        @if (filled($record->findings))
            <div class="card">
                <h2>Findings</h2>
                <div>{!! nl2br(e($record->findings)) !!}</div>
            </div>
        @endif

        @if (filled($record->impression))
            <div class="card">
                <h2>Impression</h2>
                <div class="note">{!! nl2br(e($record->impression)) !!}</div>
            </div>
        @endif

        @if (filled($record->recommendations))
            <div class="card">
                <h2>Recommended next steps</h2>
                <div>{!! nl2br(e($record->recommendations)) !!}</div>
            </div>
        @endif

        <div class="card">
            <div class="note">
                An imaging report is read alongside your symptoms and your other results. Please go through it
                with your doctor before acting on anything in it.
            </div>
        </div>
    @endif

    {{-- $pdf renders this same page into the attachment mpdf produces, so the document a patient
         receives on WhatsApp and the page they open from a link are the same thing. The controls
         and the link wording are the only parts that don't belong in a file. --}}
    @if (empty($pdf))
        <div class="card no-print">
            <button class="btn" onclick="window.print()">Save or print this page</button>
        </div>
    @endif

    <div class="foot">
        Shared with you by {{ $store->name ?? 'your hospital' }}.
        @if (empty($pdf))
            @if ($expires)
                This link works until {{ \Carbon\Carbon::parse($expires)->format('d M Y') }}.
            @endif
            <br>Please don't forward it — it opens your personal health record.
        @else
            <br>This document contains your personal health information — please keep it private.
        @endif
    </div>
</div>
</body>
</html>
