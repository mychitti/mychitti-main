@php
    use App\Modules\School\Controllers\Vendor\TimetableController as TT;
    $title = ($section->schoolClass?->name ?? '') . ' - ' . $section->name;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Timetable — {{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#1a1a1a; margin:0; font-size:12px; }
        .sheet { width: 1000px; margin: 16px auto; padding: 0 12px; }
        @if($pdf ?? false) .sheet { width:100%; margin:0; padding:0; } body{font-size:11px;} @endif
        .head { text-align:center; margin-bottom:10px; }
        .head .h { font-size:20px; font-weight:800; }
        .head .s { font-size:12px; color:#374151; }
        table.tt { width:100%; border-collapse:collapse; }
        table.tt th, table.tt td { border:1px solid #94a3b8; padding:6px 8px; text-align:center; vertical-align:middle; }
        table.tt th { background:#f1f5f9; }
        .tt .period { background:#f8fafc; font-weight:700; text-align:left; min-width:130px; }
        .tt .subj { font-weight:700; }
        .tt .teach { color:#475569; font-size:11px; }
        .tt .brk td { background:#fff7ed; color:#9a3412; font-style:italic; }
        .no-print{ text-align:center; margin:14px 0; }
        .btn{ display:inline-block; padding:8px 18px; border:none; border-radius:6px; font-weight:600; text-decoration:none; cursor:pointer; }
        .bp{ background:#4f46e5; color:#fff; } .bd{ background:#059669; color:#fff; }
        @media print { .no-print{ display:none; } }
    </style>
</head>
<body>
<div class="sheet">
    @if(!($pdf ?? false))
        <div class="no-print">
            <button class="btn bp" onclick="window.print()">🖨 Print</button>
            <a class="btn bd" href="{{ route('vendor.school.timetable.pdf', ['section_id' => $section->id]) }}" target="_blank">⬇ PDF</a>
        </div>
    @endif

    <div class="head">
        <div class="h">{{ $store?->name ?? 'School' }}</div>
        @if($branch ?? null)<div class="s" style="font-weight:700;">{{ $branch->name }}</div>@endif
        <div class="s">Class Timetable — <b>{{ $title }}</b></div>
    </div>

    <table class="tt">
        <thead>
            <tr>
                <th class="period">Period</th>
                @foreach($days as $dn => $day)<th>{{ $day }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
        @foreach($periods as $p)
            <tr class="{{ $p->is_break ? 'brk' : '' }}">
                <td class="period">
                    {{ $p->name }}
                    @if($p->start_time && $p->end_time)<br><span style="font-weight:400;color:#64748b;">{{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}</span>@endif
                </td>
                @foreach($days as $dn => $day)
                    @if($p->is_break)
                        <td>— Break —</td>
                    @else
                        @php $cell = $grid[$dn][$p->id] ?? null; @endphp
                        <td>
                            @if($cell && ($cell->subject || $cell->teacher))
                                <div class="subj">{{ $cell->subject?->name ?? '—' }}</div>
                                <div class="teach">{{ TT::teacherName($cell->teacher) }}</div>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
