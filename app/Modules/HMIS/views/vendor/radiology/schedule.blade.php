@extends('layouts.vendor.app')
@section('title', 'Radiology — Schedule')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @php
            $canBook = hasPermission('radiology_schedule', 'add');
            $canStart = hasPermission('radiology_study', 'edit');
            $canWriteReport = hasPermission('radiology_report', 'add');
            $booked = collect($slots)->filter()->count();
            $avail = count($slots) - $booked;
        @endphp
        <div class="layout-2col">
            <div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">📅</div> Appointment Schedule</h3>
                        <form method="get" class="card-actions mb-0">
                            <input class="fi" type="date" name="date" value="{{ $date }}" style="width:150px">
                            <select name="modality" class="fsel" onchange="this.form.submit()"><option value="">All Modalities</option>@foreach (['X-Ray','CT Scan','MRI','Ultrasound','ECG'] as $m)<option value="{{ $m }}" {{ $modality===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select>
                            <button class="btn btn-outline btn-sm">Go</button>
                        </form>
                    </div>
                    <div style="padding:10px 16px;background:#F9FAFB;border-bottom:1px solid var(--border);display:grid;grid-template-columns:90px 1fr 130px 90px;gap:8px;font-size:10px;font-weight:700;color:var(--light);text-transform:uppercase">
                        <div>Time</div><div>Patient</div><div>Study</div><div>Action</div>
                    </div>
                    @foreach ($slots as $time => $s)
                        <div style="display:grid;grid-template-columns:90px 1fr 130px 90px;gap:8px;padding:10px 16px;border-bottom:1px solid #F3F4F6;align-items:center {{ $s ? '' : '' }}">
                            <div class="num" style="font-size:13px">{{ $time }}</div>
                            @if ($s)
                                <div><div style="font-weight:600;font-size:12px">{{ $s->patient->name ?? '—' }}</div><div style="font-size:10px;color:var(--light)">{{ $s->patient->patient_uid ?? '' }} · {{ ucfirst($s->status) }}</div></div>
                                <div style="font-size:11px">{{ $s->study_name }}</div>
                                <div>
                                    @if($s->status==='pending')@if($canStart)<a href="{{ route('vendor.radiology.studies.start', $s->id) }}" class="btn {{ in_array($s->priority,['urgent','stat'])?'btn-red':'btn-primary' }} btn-xs">Start</a>@endif
                                    @elseif($canWriteReport)<a href="{{ route('vendor.radiology.report', ['study'=>$s->id]) }}" class="btn btn-outline btn-xs">Report</a>@endif
                                </div>
                            @else
                                <div style="font-size:11px;color:var(--light)">— Open Slot —</div><div></div>
                                <div>@if($canBook)<button class="btn btn-ghost btn-xs" onclick="document.getElementById('radBook').scrollIntoView();document.querySelector('#radBook [name=scheduled_at]').value='{{ $date }}T{{ $time }}'">Book</button>@endif</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($canBook)
                <div class="card" id="radBook">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltteal)">➕</div> Book a Study</h3></div>
                    <div class="card-body">
                        <form method="post" action="{{ route('vendor.radiology.order') }}">
                            @csrf
                            <div class="frow3">
                                <div class="fg"><label class="fl">Patient *</label><select class="fs" name="patient_id" required><option value="">Select patient…</option>@foreach ($patients as $p)<option value="{{ $p->id }}">{{ $p->name }} {{ $p->patient_uid ? '('.$p->patient_uid.')' : '' }}</option>@endforeach</select></div>
                                <div class="fg"><label class="fl">Referring Doctor</label><select class="fs" name="doctor_profile_id"><option value="">— External —</option>@foreach ($doctors as $d)<option value="{{ $d->id }}">Dr. {{ trim(($d->employee->f_name ?? '').' '.($d->employee->l_name ?? '')) ?: $d->specialization }}</option>@endforeach</select></div>
                                <div class="fg"><label class="fl">Priority</label><select class="fs" name="priority"><option value="routine">Routine</option><option value="urgent">Urgent</option><option value="stat">STAT</option></select></div>
                            </div>
                            <div class="frow3">
                                <div class="fg"><label class="fl">Modality</label><select class="fs" name="modality"><option value="">— Auto —</option>@foreach (['X-Ray','CT Scan','MRI','Ultrasound','ECG'] as $m)<option>{{ $m }}</option>@endforeach</select></div>
                                <div class="fg"><label class="fl">Study *</label><input class="fi" name="study_name" list="radTests" placeholder="e.g. Chest X-Ray PA View" required>
                                    <datalist id="radTests">@foreach ($tests as $t)<option value="{{ $t->name }}">{{ $t->modality }} · {{ \App\CentralLogics\Helpers::format_currency($t->price) }}</option>@endforeach</datalist></div>
                                <div class="fg"><label class="fl">Price (₹)</label><input class="fi" type="number" step="0.01" name="price" placeholder="auto from catalog"></div>
                            </div>
                            <div class="frow3">
                                <div class="fg"><label class="fl">Department</label><select class="fs" name="department"><option>OPD</option><option>IPD</option><option>ICU</option><option>Emergency</option></select></div>
                                <div class="fg"><label class="fl">Scheduled</label><input class="fi" type="datetime-local" name="scheduled_at"></div>
                                <div class="fg"><label class="fl">Clinical History</label><input class="fi" name="clinical_history" placeholder="Indication"></div>
                            </div>
                            <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary">📋 Book Study</button></div>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            <div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">📊</div> Slot Overview · {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3></div>
                    <div style="padding:12px 16px">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            <div style="text-align:center;padding:12px;background:var(--ltgreen);border-radius:8px"><div class="num" style="color:var(--greenA);font-size:20px">{{ $avail }}</div><div style="font-size:10px;color:var(--greenA)">Available</div></div>
                            <div style="text-align:center;padding:12px;background:var(--ltblue);border-radius:8px"><div class="num" style="color:var(--blue);font-size:20px">{{ $booked }}</div><div style="font-size:10px;color:var(--blue)">Booked</div></div>
                        </div>
                    </div>
                </div>

                @if (hasPermission('radiology_schedule', 'edit'))
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">⏰</div> Schedule Hours</h3></div>
                        <div style="padding:12px 14px">
                            <form method="post" action="{{ route('vendor.radiology.schedule.settings') }}">
                                @csrf
                                <div class="frow2">
                                    <div class="fg"><label class="fl">Day Start</label><input class="fi" type="time" name="day_start" value="{{ $settings->day_start }}" required></div>
                                    <div class="fg"><label class="fl">Day End</label><input class="fi" type="time" name="day_end" value="{{ $settings->day_end }}" required></div>
                                </div>
                                <div class="fg" style="margin-bottom:10px"><label class="fl">Slot Interval (minutes)</label><input class="fi" type="number" min="5" max="240" name="slot_minutes" value="{{ $settings->slot_minutes }}" required></div>
                                <div class="frow2">
                                    <div class="fg"><label class="fl">Lunch Start <span style="color:var(--light)">(optional)</span></label><input class="fi" type="time" name="lunch_start" value="{{ $settings->lunch_start }}"></div>
                                    <div class="fg"><label class="fl">Lunch End</label><input class="fi" type="time" name="lunch_end" value="{{ $settings->lunch_end }}"></div>
                                </div>
                                <div style="font-size:10px;color:var(--light);margin-bottom:8px">Slots between Day Start and End at this interval, skipping the lunch window.</div>
                                <button class="btn btn-primary btn-sm" style="width:100%">Save Hours</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div></div>
@endsection
