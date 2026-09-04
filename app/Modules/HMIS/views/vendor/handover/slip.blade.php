<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover #{{ $handover->id }} — {{ $title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;color:#0D1117;padding:22px 26px;font-size:12px;max-width:600px;margin:0 auto}
        .actions{text-align:center;margin-bottom:14px}
        .actions button{background:#0A2463;color:#fff;border:none;padding:8px 18px;border-radius:7px;cursor:pointer;font-size:13px;font-family:'DM Sans',sans-serif}
        .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0A2463;padding-bottom:10px}
        .head .name{font-size:18px;font-weight:800;color:#0A2463}
        .head .meta{font-size:10px;color:#4B5563;margin-top:2px}
        .title{text-align:center;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#0A2463;margin:12px 0 10px}
        .dir{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
        .info{display:grid;grid-template-columns:1fr 1fr;gap:5px 20px;border:1px solid #c8d2e0;border-radius:8px;padding:10px 14px;font-size:11.5px}
        .info .k{color:#9CA3AF;display:block;font-size:10px}
        .info .v{font-weight:600}
        .full{grid-column:1 / -1}
        .state{margin-top:10px;padding:8px 12px;border-radius:8px;font-size:11px;font-weight:600}
        .warn{margin-top:10px;padding:8px 12px;border-radius:8px;font-size:11px;background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
        .sigs{margin-top:26px;display:flex;justify-content:space-between;gap:24px}
        .sig{flex:1;text-align:center}
        .sig img{max-height:64px;max-width:100%;display:block;margin:0 auto 2px}
        .sig .line{border-top:1px solid #9CA3AF;padding-top:4px;font-size:10.5px;color:#4B5563}
        .sig .who{font-weight:700;color:#0D1117;font-size:11.5px}
        .foot{margin-top:22px;font-size:9.5px;color:#9CA3AF;text-align:center;line-height:1.5}
        .mono{font-family:'DM Mono',monospace}
        @media print{.actions{display:none}body{padding:0;max-width:none}}
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">🖨 Print / Save PDF</button></div>

    @php
        $colour   = $handover->stateColour();
        $inbound  = $handover->is_inbound;
        $dirBg    = $inbound ? '#D1FAE5' : '#FEF3C7';
        $dirFg    = $inbound ? '#065F46' : '#92400E';
    @endphp

    <div class="head">
        <div>
            <div class="name">{{ $store->name ?? 'Clinic' }}</div>
            <div class="meta">
                {{ $store->address ?? '' }}
                @if(!empty($store->phone)) · {{ $store->phone }} @endif
            </div>
        </div>
        <div style="text-align:right">
            <div class="mono" style="font-weight:700;font-size:13px">HO-{{ $handover->id }}</div>
            <div class="meta">{{ optional($handover->happened_at)->format('d M Y · h:i A') }}</div>
        </div>
    </div>

    <div class="title">Handover Receipt</div>

    <div style="text-align:center;margin-bottom:10px">
        <span class="dir" style="background:{{ $dirBg }};color:{{ $dirFg }}">
            {{ $inbound ? 'Delivered to the clinic' : 'Collected from the clinic' }}
        </span>
    </div>

    <div class="info">
        <div class="full">
            <span class="k">Work</span>
            <span class="v">{{ $title }}</span>
        </div>
        <div>
            <span class="k">Lab</span>
            <span class="v">{{ $handover->lab_name ?: '—' }}</span>
        </div>
        <div>
            <span class="k">Lab contact</span>
            <span class="v mono">{{ $handover->lab_phone ?: '—' }}</span>
        </div>
        <div>
            <span class="k">{{ $inbound ? 'Delivered by' : 'Collected by' }}</span>
            <span class="v">{{ $handover->person_name }}</span>
        </div>
        <div>
            <span class="k">Their phone / ID</span>
            <span class="v mono">
                {{ $handover->person_phone ?: '—' }}
                @if(filled($handover->person_id_ref)) · {{ $handover->person_id_ref }} @endif
            </span>
        </div>
        <div>
            <span class="k">{{ $inbound ? 'Received by' : 'Handed over by' }}</span>
            <span class="v">{{ $handover->staff_name }}</span>
        </div>
        <div>
            <span class="k">Contents</span>
            <span class="v">
                {{ trim((string) $handover->purpose) ?: 'Work' }}
                @if($handover->item_count) · {{ $handover->item_count }} item{{ $handover->item_count > 1 ? 's' : '' }} @endif
            </span>
        </div>
        @if(filled($handover->item_note))
            <div class="full">
                <span class="k">Packet</span>
                <span class="v">{{ $handover->item_note }}</span>
            </div>
        @endif
        @if(filled($handover->notes))
            <div class="full">
                <span class="k">Notes</span>
                <span class="v" style="font-weight:400">{{ $handover->notes }}</span>
            </div>
        @endif
    </div>

    {{-- The state is printed as prominently as the names, because a slip that looks identical
         whether or not anyone checked is a slip that quietly certifies an unchecked handover. --}}
    <div class="state" style="background:{{ $colour[1] }};color:{{ $colour[0] }}">
        {{ $handover->stateLabel() }}
        @if($handover->otp_verified_at)
            — verified with {{ $handover->lab_name }} by one-time code on
            {{ $handover->otp_verified_at->format('d M Y · h:i A') }}
        @elseif($handover->verify_state === 'provisional')
            — the lab has not yet confirmed this person. Contents are not to be treated as
            authenticated until they do.
        @endif
    </div>

    @if(!$handover->dispatch_expected)
        <div class="warn">
            Recorded against the clinic's own record.
            @if(filled($handover->override_reason)) Reason given: {{ $handover->override_reason }} @endif
        </div>
    @endif

    <div class="sigs">
        <div class="sig">
            @if($handover->mediaUrl('signature_path'))
                <img src="{{ $handover->mediaUrl('signature_path') }}" alt="">
            @endif
            <div class="line">
                <span class="who">{{ $handover->person_name }}</span><br>
                {{ $handover->lab_name ?: 'Lab representative' }}
            </div>
        </div>
        <div class="sig">
            <div class="line" style="margin-top:{{ $handover->mediaUrl('signature_path') ? '66px' : '0' }}">
                <span class="who">{{ $handover->staff_name }}</span><br>
                For {{ $store->name ?? 'the clinic' }}
            </div>
        </div>
    </div>

    <div class="foot">
        Handover HO-{{ $handover->id }} recorded {{ optional($handover->happened_at)->format('d M Y \a\t h:i A') }}.
        A copy of this exchange was sent to {{ $handover->lab_name ?: 'the lab' }} on WhatsApp.<br>
        If you did not authorise this handover, contact {{ $store->name ?? 'the clinic' }} immediately.
    </div>
</body>
</html>
