@extends('front-views.layout')
@section('title', 'Appointment Confirmed — ' . $store->name)

@push('css_or_js')
<style>
    .confirm-wrap {
        max-width: 520px;
        margin: 60px auto;
        padding: 0 16px 80px;
        text-align: center;
    }
    .confirm-icon {
        width: 72px; height: 72px;
        background: #d1fae5;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px;
        font-size: 36px;
    }
    .confirm-title { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
    .confirm-sub   { color: #6b7280; margin-bottom: 28px; font-size: 14px; }

    .token-badge {
        display: inline-block;
        background: #2563eb;
        color: #fff;
        font-size: 32px;
        font-weight: 800;
        border-radius: 14px;
        padding: 10px 36px;
        margin-bottom: 28px;
        letter-spacing: 1px;
    }
    .token-label { font-size: 12px; color: #9ca3af; display: block; margin-bottom: 4px; }

    .detail-card {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 20px 24px;
        text-align: left;
        margin-bottom: 24px;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: #6b7280; }
    .detail-value { font-weight: 600; color: #111; text-align: right; }
</style>
@endpush

@section('content')
<div class="confirm-wrap">

    <div class="confirm-icon">✓</div>
    <h2 class="confirm-title">Appointment Confirmed!</h2>
    <p class="confirm-sub">Your appointment has been booked successfully at {{ $store->name }}.</p>

    {{-- Token --}}
    @if($appointment->token)
        <span class="token-label">Your Token Number</span>
        <div class="token-badge"># {{ $appointment->token->token_number }}</div>
    @endif

    {{-- Details --}}
    <div class="detail-card">
        <div class="detail-row">
            <span class="detail-label">Patient</span>
            <span class="detail-value">{{ $appointment->patient?->name }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Patient UID</span>
            <span class="detail-value">{{ $appointment->patient?->patient_uid }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Doctor</span>
            <span class="detail-value">
                Dr. {{ $appointment->doctorProfile?->employee?->f_name }}
                {{ $appointment->doctorProfile?->employee?->l_name }}
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Specialization</span>
            <span class="detail-value">{{ $appointment->doctorProfile?->specialization }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">{{ $appointment->appointment_date?->format('d M Y, l') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Time</span>
            <span class="detail-value">
                {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}
            </span>
        </div>
        @if($appointment->slot)
        <div class="detail-row">
            <span class="detail-label">Slot</span>
            <span class="detail-value">
                {{ \Carbon\Carbon::parse($appointment->slot->slot_start)->format('h:i A') }}
                – {{ \Carbon\Carbon::parse($appointment->slot->slot_end)->format('h:i A') }}
            </span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value" style="color:#059669;">Scheduled</span>
        </div>
        @if($appointment->reason)
        <div class="detail-row">
            <span class="detail-label">Reason</span>
            <span class="detail-value">{{ $appointment->reason }}</span>
        </div>
        @endif
    </div>

    <a href="{{ route('store.details', [request()->city ?? 'city', $store->slug]) }}"
        class="btn btn-outline-primary mr-2">
        Back to Store
    </a>
    <a href="{{ route('front.appointment.book', [request()->city ?? 'city', $store->slug]) }}"
        class="btn btn-primary">
        Book Another
    </a>

</div>
@endsection
