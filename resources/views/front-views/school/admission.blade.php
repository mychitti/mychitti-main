@extends('front-views.layout')
@section('title', 'Admission Enquiry — ' . $store->name)

@push('css_or_js')
<style>
    .adm-wrap { max-width: 760px; margin: 0 auto; padding: 100px 16px 70px; }
    .adm-head { display:flex; align-items:center; gap:14px; margin-bottom:22px; padding-bottom:18px; border-bottom:1px solid #eef0f3; }
    .adm-logo { width:56px; height:56px; border-radius:12px; object-fit:cover; border:1px solid #eee; flex-shrink:0; }
    .adm-name { font-size:22px; font-weight:700; margin:0; color:#111827; line-height:1.2; }
    .adm-sub  { font-size:13px; color:#6b7280; margin:2px 0 0; }
    .adm-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:26px; box-shadow:0 6px 22px rgba(15,23,42,.05); }
    .adm-card h3 { font-size:17px; font-weight:700; margin:0 0 4px; color:#111827; }
    .adm-card .lead { font-size:13px; color:#6b7280; margin:0 0 18px; }
    .adm-row { display:flex; flex-wrap:wrap; gap:14px; }
    .adm-grp { flex:1 1 220px; margin-bottom:14px; }
    .adm-grp.full { flex-basis:100%; }
    .adm-grp label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
    .adm-grp label .req { color:#e11d48; }
    .adm-grp input, .adm-grp select, .adm-grp textarea {
        width:100%; padding:11px 13px; border:1px solid #d8dde6; border-radius:10px; font-size:14px; color:#111827; background:#fff; }
    .adm-grp input:focus, .adm-grp select:focus, .adm-grp textarea:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.15); }
    .adm-err { color:#e11d48; font-size:12px; margin-top:4px; }
    .adm-submit { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border:none; border-radius:10px; padding:13px 26px; font-weight:600; font-size:15px; cursor:pointer; width:100%; }
    .adm-submit:hover { filter:brightness(1.05); }
    .adm-alert { padding:14px 16px; border-radius:12px; margin-bottom:18px; font-size:14px; }
    .adm-alert.ok { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
    .adm-note { text-align:center; font-size:12px; color:#9ca3af; margin-top:14px; }
</style>
@endpush

@section('content')
<div class="adm-wrap">
    <div class="adm-head">
        @if($store->logo)<img class="adm-logo" src="{{ asset('storage/app/public/store/'.$store->logo) }}" alt="">@endif
        <div>
            <h1 class="adm-name">{{ $store->name }}</h1>
            <p class="adm-sub">@if($store->address){{ $store->address }}@endif</p>
        </div>
    </div>

    @if(session('success'))
        <div class="adm-alert ok"><i class="tio-checkmark-circle"></i> {{ session('success') }}</div>
    @endif

    <div class="adm-card">
        <h3>Admission Enquiry</h3>
        <p class="lead">Fill in the details below and our team will get in touch with you.</p>

        <form action="{{ route('front.school.admission.store', [$city, $slug]) }}" method="POST">
            @csrf

            <div class="adm-row">
                <div class="adm-grp">
                    <label>Student Name <span class="req">*</span></label>
                    <input name="student_name" value="{{ old('student_name') }}" required>
                    @error('student_name')<div class="adm-err">{{ $message }}</div>@enderror
                </div>
                <div class="adm-grp">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob') }}">
                </div>
            </div>

            <div class="adm-row">
                <div class="adm-grp">
                    <label>Gender</label>
                    <select name="gender">
                        @foreach(['' => 'Select', 'male'=>'Male','female'=>'Female','other'=>'Other'] as $k=>$v)
                            <option value="{{ $k }}" @selected(old('gender')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="adm-grp">
                    <label>Seeking Admission in Class</label>
                    <select name="seeking_class_id">
                        <option value="">Select class</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('seeking_class_id')==$c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
            </div>

            @if($branches->count())
                <div class="adm-grp full">
                    <label>Preferred Campus</label>
                    <select name="branch_id">
                        <option value="">No preference</option>
                        @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id')==$b->id)>{{ $b->name }}</option>@endforeach
                    </select>
                </div>
            @endif

            <div class="adm-row">
                <div class="adm-grp">
                    <label>Parent / Guardian Name <span class="req">*</span></label>
                    <input name="guardian_name" value="{{ old('guardian_name') }}" required>
                    @error('guardian_name')<div class="adm-err">{{ $message }}</div>@enderror
                </div>
                <div class="adm-grp">
                    <label>Mobile Number <span class="req">*</span></label>
                    <input name="guardian_phone" value="{{ old('guardian_phone') }}" required>
                    @error('guardian_phone')<div class="adm-err">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="adm-row">
                <div class="adm-grp">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}">
                    @error('email')<div class="adm-err">{{ $message }}</div>@enderror
                </div>
                <div class="adm-grp">
                    <label>Previous School</label>
                    <input name="previous_school" value="{{ old('previous_school') }}">
                </div>
            </div>

            <div class="adm-grp full">
                <label>Message / Remarks</label>
                <textarea name="remarks" rows="3">{{ old('remarks') }}</textarea>
            </div>

            <button type="submit" class="adm-submit">Submit Enquiry</button>
            <div class="adm-note">By submitting, you agree to be contacted by {{ $store->name }} regarding admissions.</div>
        </form>
    </div>
</div>
@endsection
