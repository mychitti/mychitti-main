<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? '')) }}</title>
    <style>
        body { font-family: 'Times New Roman', Georgia, serif; color: #222; margin: 0; padding: 40px; line-height: 1.6; }
        .lt-wrap { max-width: 800px; margin: 0 auto; }
        .lt-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f3460; padding-bottom: 12px; margin-bottom: 28px; }
        .lt-store h2 { margin: 0; font-size: 22px; color: #0f3460; }
        .lt-store small { color: #666; }
        .lt-logo img { width: 80px; }
        .lt-title { text-align: center; font-size: 17px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 24px; text-decoration: underline; }
        .lt-body { white-space: pre-wrap; font-size: 15px; }
        .lt-actions { max-width: 800px; margin: 18px auto 0; text-align: right; }
        .lt-actions button { background: #0f3460; color: #fff; border: 0; border-radius: 6px; padding: 8px 18px; cursor: pointer; font-family: sans-serif; }
        .lt-form { max-width: 800px; margin: 0 auto 18px; padding: 12px 16px; background: #f5f7fb; border-radius: 8px; font-family: sans-serif; font-size: 13px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .lt-form label { display: block; font-weight: 600; margin-bottom: 3px; }
        .lt-form input { padding: 6px 8px; border: 1px solid #ccc; border-radius: 5px; }
        .lt-form button { background: #0f3460; color: #fff; border: 0; border-radius: 5px; padding: 7px 14px; cursor: pointer; }
        @media print { .lt-actions, .lt-form { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    @if ($type === 'termination')
        {{-- Termination needs a reason + last working day; regenerate with these values. --}}
        <form class="lt-form" method="get" action="">
            <div>
                <label>Reason</label>
                <input type="text" name="reason" value="{{ request('reason') }}" placeholder="Reason for termination" style="min-width:260px;">
            </div>
            <div>
                <label>Last Working Day</label>
                <input type="date" name="last_working_day" value="{{ request('last_working_day') }}">
            </div>
            <button type="submit">Apply</button>
        </form>
    @endif

    <div class="lt-wrap">
        <div class="lt-head">
            <div class="lt-store">
                <h2>{{ $store->name ?? 'Store' }}</h2>
                <small>{{ $store->address ?? '' }}</small>
                @if (!empty($store->phone))<br><small>Phone: {{ $store->phone }}</small>@endif
            </div>
            @if (!empty($store->logo))
                <div class="lt-logo"><img src="{{ asset('storage/app/public/store/' . $store->logo) }}" alt=""></div>
            @endif
        </div>

        <div class="lt-title">{{ $title }}</div>
        <div class="lt-body">{{ $body }}</div>
    </div>
    <div class="lt-actions"><button onclick="window.print()">Print</button></div>
</body>
</html>
