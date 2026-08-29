{{-- What a patient sees when the hospital asks to move their appointment.

     Read on a phone, once, by somebody who is not a user of this software and never will be. So:
     one question, the old time and the new one side by side, and two buttons. The palette matches
     the health-record page a patient may already have opened from the same hospital. --}}
@php
    $answered = in_array($req->status, ['accepted', 'declined'], true);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Appointment — {{ $store->name ?? 'Hospital' }}</title>
    <style>
        * { box-sizing:border-box; }
        body {
            margin:0; padding:16px; background:#f6f8fb; color:#1e293b;
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
            font-size:15px; line-height:1.55;
        }
        .wrap { max-width:520px; margin:0 auto; }
        .card { background:#fff; border:1px solid #e6e9ef; border-radius:14px; padding:20px; margin-bottom:14px; }
        .clinic { text-align:center; border-bottom:1px solid #e6e9ef; padding-bottom:14px; margin-bottom:16px; }
        .clinic h1 { font-size:19px; margin:0 0 4px; }
        .clinic p { margin:0; font-size:13px; color:#64748b; }
        .kind { display:inline-block; background:#fff4e5; color:#b9770e; font-size:12px; font-weight:700;
            padding:4px 12px; border-radius:20px; letter-spacing:.3px; text-transform:uppercase; margin-bottom:12px; }
        .ask { font-size:16px; margin:0 0 16px; }
        .when { border:1px solid #e6e9ef; border-radius:12px; padding:14px; margin-bottom:12px; }
        .when.old { background:#f9fafc; }
        .when.new { border-color:#128c7e; background:#e8f7ef; }
        .when span { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; margin-bottom:3px; }
        .when strong { font-size:16px; }
        .when.old strong { color:#64748b; text-decoration:line-through; }
        .note { background:#f6f8fb; border-left:3px solid #128c7e; border-radius:8px;
            padding:12px 14px; font-size:14px; margin-bottom:14px; }
        .btn { display:block; width:100%; padding:14px; border-radius:10px; font-size:15px;
            font-weight:700; text-align:center; cursor:pointer; border:1px solid transparent; margin-bottom:10px; }
        .btn-yes { background:#128c7e; color:#fff; }
        .btn-no  { background:#fff; color:#1e293b; border-color:#e6e9ef; }
        .field { width:100%; padding:12px; border:1px solid #e6e9ef; border-radius:10px; font-size:14px;
            font-family:inherit; margin-bottom:12px; }
        .flash { border-radius:12px; padding:14px; font-size:14px; margin-bottom:14px;
            background:#e8f7ef; border:1px solid #128c7e; }
        .state { font-size:13px; font-weight:700; padding:4px 12px; border-radius:20px; display:inline-block; }
        .foot { text-align:center; font-size:12px; color:#64748b; padding:6px 0 20px; line-height:1.7; }
        .foot a { color:#128c7e; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="clinic">
            <h1>{{ $store->name ?? 'Hospital' }}</h1>
            @if($store?->address)
                <p>{{ $store->address }}</p>
            @endif
        </div>

        @if($flash)
            <div class="flash">{{ $flash }}</div>
        @endif

        @if($answered)
            {{-- Already answered, including on the reply that has just been recorded. The page has
                 to keep working after the tap: a patient who closes it and opens the same link an
                 hour later is checking what they agreed to, and must find the answer here. --}}
            @php $colour = $req->stateColour(); @endphp
            <span class="state" style="color:{{ $colour[0] }}; background:{{ $colour[1] }};">{{ $req->stateLabel() }}</span>

            <div class="when {{ $req->status === 'accepted' ? 'new' : 'old' }}" style="margin-top:14px;">
                <span>{{ $req->status === 'accepted' ? 'Your appointment' : 'Your appointment stands at' }}</span>
                <strong style="text-decoration:none; color:#1e293b;">
                    {{ $req->status === 'accepted' ? $req->proposedLabel() : $req->currentLabel() }}
                </strong>
            </div>

            @if($req->status === 'declined')
                <p style="font-size:14px; color:#64748b; margin:0;">
                    We have told {{ $store->name ?? 'the hospital' }} that
                    {{ $req->proposedLabel() }} does not suit you. They will contact you to arrange another time.
                </p>
            @endif
        @else
            <div class="kind">Reschedule request</div>

            <p class="ask">
                Hello {{ $req->patient?->name ?: 'there' }}, we would like to move your appointment
                @if($doctor)
                    with Dr. {{ trim(($doctor->f_name ?? '') . ' ' . ($doctor->l_name ?? '')) }}
                @endif
                to a new time.
            </p>

            <div class="when old">
                <span>Currently booked for</span>
                <strong>{{ $req->currentLabel() }}</strong>
            </div>

            <div class="when new">
                <span>New time we are proposing</span>
                <strong>{{ $req->proposedLabel() }}</strong>
            </div>

            @if(filled($req->note))
                <div class="note">{{ $req->note }}</div>
            @endif

            {{-- Two submit buttons carrying the answer in their own value, so the page needs no
                 JavaScript at all. It is opened once, on a phone, from a WhatsApp browser — the
                 fewer moving parts between the patient and their answer, the better. --}}
            <form method="POST" action="{{ route('appointment-reschedule.respond', ['token' => $req->token]) }}">
                @csrf

                <button type="submit" name="answer" value="accept" class="btn btn-yes">
                    Yes, {{ $req->proposedLabel() }} works
                </button>

                <input type="text" name="note" class="field" maxlength="500"
                       placeholder="If it doesn’t suit — when would? (optional)">

                <button type="submit" name="answer" value="decline" class="btn btn-no">
                    No, that time doesn’t suit me
                </button>
            </form>

            <p style="font-size:13px; color:#64748b; margin:4px 0 0;">
                Your original appointment on {{ $req->currentLabel() }} stands until you confirm.
            </p>
        @endif
    </div>

    <div class="foot">
        @if($store?->phone)
            Need to talk to us? Call <a href="tel:{{ $store->phone }}">{{ $store->phone }}</a><br>
        @endif
        This link is personal to you — please do not forward it.
    </div>
</div>
</body>
</html>
