@extends('mc-vendor.theme.layout')

@section('title', 'Finish Signup — MC Vendor Hub')
@section('meta_description', 'Just three details and your My Chitti account is live.')

@section('styles')
    <style>
        .qs-shell { padding: 44px 28px 90px; background: linear-gradient(180deg, var(--blue-pale) 0%, var(--bg) 100%); }
        .qs-card {
            max-width: 560px;
            margin: 0 auto;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 34px 32px;
        }
        .qs-verified {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--green-pale, #F1F9E7);
            border: 1px solid var(--green);
            color: var(--green-dark);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .qs-card h1 { font-size: 26px; line-height: 1.2; margin-bottom: 8px; }
        .qs-card p.sub { font-size: 15px; color: var(--ink-soft); margin-bottom: 26px; }
        .qs-field { margin-bottom: 18px; }
        .qs-field label {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 7px;
        }
        .qs-field input,
        .qs-field select {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            font-family: 'Manrope', sans-serif;
            font-size: 15px;
            color: var(--ink);
            background: var(--white);
        }
        .qs-field input:focus,
        .qs-field select:focus { outline: none; border-color: var(--blue); }
        .qs-err { font-size: 13px; color: #C0392B; margin-top: 6px; }
        .qs-hint { font-size: 13px; color: var(--ink-faint); margin-top: 16px; }
    </style>
@endsection

@section('content')
    <section class="qs-shell">
        <div class="qs-card">

            <div class="qs-verified">
                <span>✓</span>
                @if ($identity['via'] === 'google')
                    Signed in as {{ $identity['email'] }}
                @else
                    Phone {{ $identity['phone'] }} verified
                @endif
            </div>

            <h1>Almost there.</h1>
            <p class="sub">Three details and your account is live. Everything else — address, photos, services, documents — you can add later from your dashboard.</p>

            <form action="{{ route('quick-signup.store') }}" method="post">
                @csrf

                <div class="qs-field">
                    <label for="business_name">Business name</label>
                    <input type="text" id="business_name" name="business_name"
                        value="{{ old('business_name') }}" placeholder="e.g. Sri Balaji Electricals" required>
                    @error('business_name')<div class="qs-err">{{ $message }}</div>@enderror
                </div>

                <div class="qs-field">
                    <label for="owner_name">Your name</label>
                    <input type="text" id="owner_name" name="owner_name"
                        value="{{ old('owner_name', $identity['name']) }}" placeholder="e.g. Ramesh Kumar" required>
                    @error('owner_name')<div class="qs-err">{{ $message }}</div>@enderror
                </div>

                @if (empty($identity['phone']))
                    <div class="qs-field">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                            placeholder="e.g. 9988776655" required>
                        @error('phone')<div class="qs-err">{{ $message }}</div>@enderror
                    </div>
                @endif

                @if (empty($identity['email']))
                    <div class="qs-field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                            placeholder="you@business.com" required>
                        @error('email')<div class="qs-err">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="qs-field">
                    <label for="zone_id">City</label>
                    <select id="zone_id" name="zone_id" required>
                        <option value="" disabled {{ old('zone_id') ? '' : 'selected' }}>Select your city</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('zone_id')<div class="qs-err">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:8px;">
                    Create my account
                </button>

                <p class="qs-hint">
                    By continuing you agree to our
                    <a href="{{ route('mc-vendor-hub-tnc') }}" style="color:var(--blue); font-weight:600;">Terms</a>
                    and
                    <a href="{{ route('mc-vendor-hub-pp') }}" style="color:var(--blue); font-weight:600;">Privacy Policy</a>.
                </p>
            </form>

        </div>
    </section>
@endsection
