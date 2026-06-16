@extends('front-views.layout')
@section('title', 'My School')

@push('css_or_js')
    <style>
        .sp-wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 100px 16px 70px;
        }

        .sp-head {
            font-size: 26px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 6px;
        }

        .sp-sub {
            color: #6b7280;
            margin: 0 0 28px;
        }

        .sp-alert {
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .sp-alert.ok {
            background: #dcfce7;
            color: #15803d;
        }

        .sp-alert.err {
            background: #fee2e2;
            color: #b91c1c;
        }

        .sp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .sp-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
            transition: .18s;
        }

        .sp-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(79, 70, 229, .12);
        }

        .sp-av {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #81c408;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .sp-name {
            font-weight: 700;
            font-size: 17px;
            color: #111827;
        }

        .sp-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .sp-card .btn-open {
            display: inline-block;
            margin-top: 12px;
            background: #81c408;
            color: #fff;
            padding: 9px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }

        .sp-unlink {
            float: right;
            color: #cbd5e1;
            font-size: 13px;
        }

        .sp-form {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
        }

        .sp-form h3 {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .sp-form p {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 16px;
        }

        .sp-form label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .sp-form input {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #d8dde6;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 14px;
        }

        .sp-form input:focus {
            outline: none;
            border-color: #81c408;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .15);
        }

        .sp-form button {
            background: #81c408;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
        }
        .spacer{ height:80px; }
    </style>
@endpush

@section('content')
    <div class="spacer"></div>
    <div class="container">
        <div class="dash_div d-flex align-items-start shadow rounded">
            @include('front-views.partials.dashboard._nav')
            <div class="tab-content" style="width:100%; min-height:300px; padding:24px;">
        <h1 class="sp-head"><i class="fas fa-school" style="color:#81c408;"></i> My School</h1>
        <p class="sp-sub">View your child's attendance, results, fees, homework, timetable and school notices.</p>

        @if (session('success'))
            <div class="sp-alert ok">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="sp-alert err">{{ session('error') }}</div>
        @endif

        @if ($children->count())
            <div class="sp-grid">
                @foreach ($children as $c)
                    <div class="sp-card">
                        <a href="{{ route('school.portal.unlink', $c->id) }}" class="sp-unlink" title="Remove"
                            onclick="return confirm('Remove this child from your account?')"><i
                                class="fas fa-times"></i></a>
                        <div class="sp-av">
                            @if($c->photo)
                                <img src="{{ asset('storage/app/public/school/students/' . $c->photo) }}" alt="{{ $c->name }}" style="width:100%;height:100%;border-radius:14px;object-fit:cover;">
                            @else
                                {{ strtoupper(substr($c->name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="sp-name">{{ $c->name }}</div>
                        <div class="sp-meta">{{ $c->school_name }}</div>
                        <div class="sp-meta">{{ $c->currentEnrollment?->schoolClass?->name }}
                            {{ $c->currentEnrollment?->section ? '- ' . $c->currentEnrollment->section->name : '' }} ·
                            {{ $c->admission_no }}</div>
                        <a href="{{ route('school.portal.show', $c->id) }}" class="btn-open">Open <i
                                class="fas fa-arrow-right" style="font-size:.7rem;"></i></a>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="sp-form">
            <h3>{{ $children->count() ? 'Add another child' : 'Link your child' }}</h3>
            <p>Enter your child's <b>Admission Number</b> and <b>Date of Birth</b> exactly as registered with the school.
            </p>
            <form action="{{ route('school.portal.link') }}" method="POST">
                @csrf
                <div class="row">
                <div class="col-md-6 mb-3 mb-md-0 px-1">
                    <label>Admission Number</label>
                    <input name="admission_no" value="{{ old('admission_no') }}" placeholder="e.g. ADM0042" required>
                </div>
                <div class="col-md-6 mb-3 mb-md-0 px-1">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob') }}" required>
                </div>
                </div>
                <button type="submit"><i class="fas fa-link"></i> Link Student</button>
            </form>
        </div>
            </div>
        </div>
    </div>
@endsection
