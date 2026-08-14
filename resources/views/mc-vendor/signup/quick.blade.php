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

        /* intl-tel-input wraps the field in a .iti div, which is inline-block by default and would
           shrink the full-width phone box to its content. */
        .qs-field .iti { width: 100%; }
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
            <p class="sub">
                One detail and your account is live. Your business name, city and everything else —
                address, photos, services, documents — you can set from your dashboard.
            </p>

            {{-- Business name, owner name and city are not asked for: the controller fills them in
                 (the Google name, and Tirupati) and the panel's unfinished-profile banner asks the
                 vendor to correct them. Only what cannot be invented is collected here — the phone,
                 which is NOT NULL and UNIQUE on vendors, and the email when OTP got us here. --}}
            <form action="{{ route('quick-signup.store') }}" method="post">
                @csrf

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

                {{-- Kept as an error slot only: nothing here asks for a city, but if the default
                     zone could not be resolved the validator still has somewhere to say so. --}}
                @error('zone_id')<div class="qs-err">{{ $message }}</div>@enderror
                @error('business_name')<div class="qs-err">{{ $message }}</div>@enderror

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

@section('scripts')
    {{-- Only reached on the Google path, where we still have to ask for a number — so it asks the
         same way the listing form and the OTP step do. jQuery first: the shared partial is written
         against it and this page's layout ships none. --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    @include('front-views.partials.tel_input')
@endsection
