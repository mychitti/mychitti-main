@extends('layouts.vendor.app')
@section('title', 'Radiology — Equipment')

@push('css_or_js')<meta name="csrf-token" content="{{ csrf_token() }}">@endpush

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @php $modIcon = fn($m)=>['X-Ray'=>'☢','CT Scan'=>'🔬','MRI'=>'🧲','Ultrasound'=>'🔊','ECG'=>'❤️'][$m] ?? '⚙'; @endphp
        <div class="card">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">⚙</div> Radiology Equipment</h3>
                @if (hasPermission('radiology_equipment', 'add'))<button class="btn btn-primary btn-sm" onclick="var f=document.getElementById('radEquipForm');f.style.display=f.style.display==='none'?'block':'none'">+ Add Equipment</button>@endif
            </div>
            @if (hasPermission('radiology_equipment', 'add'))
                <form method="post" action="{{ route('vendor.radiology.equipment.save') }}" id="radEquipForm" style="display:none;padding:14px;background:#FAFAFA;border-bottom:1px solid var(--border)">
                    @csrf
                    <div class="frow3">
                        <div class="fg"><label class="fl">Name *</label><input class="fi" name="name" required></div>
                        <div class="fg"><label class="fl">Model</label><input class="fi" name="model"></div>
                        <div class="fg"><label class="fl">Modality</label><select class="fs" name="modality">@foreach (['X-Ray','CT Scan','MRI','Ultrasound','ECG'] as $m)<option>{{ $m }}</option>@endforeach</select></div>
                    </div>
                    <div class="frow3">
                        <div class="fg"><label class="fl">Location</label><input class="fi" name="location"></div>
                        <div class="fg"><label class="fl">Status</label><select class="fs" name="status"><option value="online">Online</option><option value="maintenance">Maintenance</option><option value="offline">Offline</option></select></div>
                        <div class="fg" style="justify-content:flex-end"><button class="btn btn-primary">Save Equipment</button></div>
                    </div>
                </form>
            @endif
            <div style="padding:14px"><div class="layout-3col">
                @foreach ($equipment->chunk(ceil(max($equipment->count(),1)/2)) as $chunk)
                    <div>
                        @foreach ($chunk as $e)
                            <div class="equip-card" style="{{ $e->status!=='online' ? 'border-color:var(--amberA)' : '' }}">
                                <div class="equip-icon">{{ $modIcon($e->modality) }}</div>
                                <div style="flex:1">
                                    <div style="font-size:13px;font-weight:700">{{ $e->name }}</div>
                                    <div style="font-size:10px;color:var(--light)">{{ $e->model }}{{ $e->location ? ' · '.$e->location : '' }}</div>
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:8px;flex-wrap:wrap">
                                        <div class="equip-dot" style="background:{{ $e->status==='online'?'var(--greenA)':($e->status==='maintenance'?'var(--amberA)':'var(--redA)') }}"></div>
                                        <span style="font-size:11px;color:var(--muted)">{{ ucfirst($e->status) }}</span>
                                        <span class="pill {{ $e->status==='online'?'pill-green':($e->status==='maintenance'?'pill-amber':'pill-red') }}">{{ $e->status==='online'?'Active':'Offline' }}</span>
                                    </div>
                                    <div style="font-size:11px;color:var(--light);margin-top:6px">Last service: {{ optional($e->last_service)->format('d M Y') ?? '—' }} · Total: {{ number_format($e->studies_total) }}</div>
                                    @if (hasPermission('radiology_equipment', 'edit'))
                                        <form method="post" action="{{ route('vendor.radiology.equipment.update', $e->id) }}" style="display:flex;gap:6px;margin-top:8px;align-items:center">@csrf
                                            <select name="status" class="fsel" style="padding:4px 8px;font-size:11px"><option value="online" {{ $e->status==='online'?'selected':'' }}>Online</option><option value="maintenance" {{ $e->status==='maintenance'?'selected':'' }}>Maintenance</option><option value="offline" {{ $e->status==='offline'?'selected':'' }}>Offline</option></select>
                                            <button class="btn btn-outline btn-xs">Update</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                <div>
                    <div class="card">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">📊</div> Utilisation</h3></div>
                        @foreach ($equipment as $e)
                            <div class="stat-row"><span class="stat-l">{{ $e->name }}</span><span class="stat-v {{ $e->status==='online'?'g':'a' }} num">{{ $e->status==='online'?'Online':'Offline' }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div></div>
        </div>
    </div>
</div></div>
@endsection
