@extends('mc-vendor.theme.layout')

@section('title', 'Account Created — MC Vendor Hub')

@section('styles')
    <style>
        .qd-shell { padding: 60px 28px 100px; background: linear-gradient(180deg, var(--blue-pale) 0%, var(--bg) 100%); }
        .qd-card {
            max-width: 560px;
            margin: 0 auto;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 40px 34px;
            text-align: center;
        }
        .qd-tick {
            width: 62px; height: 62px;
            border-radius: 50%;
            background: var(--green-pale, #F1F9E7);
            border: 2px solid var(--green);
            color: var(--green-dark);
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; font-weight: 800;
            margin: 0 auto 22px;
        }
        .qd-card h1 { font-size: 27px; margin-bottom: 10px; }
        .qd-card p { font-size: 15.5px; color: var(--ink-soft); margin-bottom: 24px; }
        .qd-steps {
            text-align: left;
            background: var(--bg-soft);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 20px 22px;
            margin-bottom: 26px;
        }
        .qd-steps h2 {
            font-size: 13px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .06em; color: var(--ink-faint); margin-bottom: 12px;
        }
        .qd-steps ol { margin: 0; padding-left: 20px; }
        .qd-steps li { font-size: 14.5px; color: var(--ink-soft); padding: 5px 0; }
    </style>
@endsection

@section('content')
    <section class="qd-shell">
        <div class="qd-card">
            <div class="qd-tick">✓</div>

            <h1>Your account is ready.</h1>
            <p>We've sent a confirmation to your email. Your listing isn't public yet — finish your profile and our team will review it.</p>

            <div class="qd-steps">
                <h2>What's left</h2>
                <ol>
                    <li>Add your address and drop a pin on the map</li>
                    <li>Pick your category and list the services you offer</li>
                    <li>Upload your logo and a GST or ID document</li>
                </ol>
            </div>

            <a href="https://vendor.mcvendorhub.com/login" class="btn btn-primary" style="width:100%;">
                Go to my dashboard
            </a>
            <p style="font-size:13px; color:var(--ink-faint); margin:14px 0 0;">
                Sign in with the phone number you just verified.
            </p>
        </div>
    </section>
@endsection
