@extends('layouts.vendor.app')
@section('title', 'Laboratory — ' . ($test ? 'Edit' : 'Add') . ' Test')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        <form method="post" action="{{ $test ? route('vendor.lab.catalog.update', $test->id) : route('vendor.lab.catalog.store') }}">
            @csrf
            <div class="lcard">
                <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">⚙</div> {{ $test ? 'Edit Test' : 'Add Test' }}</h3>
                    <a href="{{ route('vendor.lab.catalog') }}" class="btn btn-outline btn-sm">← Back</a></div>
                <div style="padding:14px">
                    <div class="frow3">
                        <div class="fg"><label class="fl">Test / Panel Name *</label><input class="fi" name="name" value="{{ $test->name ?? '' }}" required></div>
                        <div class="fg"><label class="fl">Code</label><input class="fi" name="code" value="{{ $test->code ?? '' }}"></div>
                        <div class="fg"><label class="fl">Department</label><input class="fi" name="department" value="{{ $test->department ?? '' }}" placeholder="Haematology, Biochemistry..."></div>
                    </div>
                    <div class="frow3">
                        <div class="fg"><label class="fl">Sample Type</label><input class="fi" name="sample_type" value="{{ $test->sample_type ?? '' }}" placeholder="Venous Blood, Urine..."></div>
                        <div class="fg"><label class="fl">Price ({{ \App\CentralLogics\Helpers::currency_symbol() ?? '₹' }}) *</label><input class="fi" type="number" step="0.01" name="price" value="{{ $test->price ?? '' }}" required></div>
                        <div class="fg"><label class="fl">Turnaround Time</label><input class="fi" name="tat_text" value="{{ $test->tat_text ?? '' }}" placeholder="e.g. 1–2 hours"></div>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--muted)"><input type="checkbox" name="is_active" value="1" {{ !$test || $test->is_active ? 'checked' : '' }}> Active (available for ordering)</label>
                </div>
            </div>

            <div class="lcard">
                <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">🧬</div> Parameters &amp; Reference Ranges</h3>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="addParam()">+ Add Parameter</button></div>
                <div style="display:grid;grid-template-columns:1.4fr .8fr .8fr .8fr 1fr .8fr .8fr 40px;gap:6px;padding:8px 16px;font-size:10px;font-weight:700;color:var(--light);text-transform:uppercase;background:#F9FAFB;border-bottom:1px solid var(--border)">
                    <div>Parameter</div><div>Unit</div><div>Normal Low</div><div>Normal High</div><div>Ref Text</div><div>Crit Low</div><div>Crit High</div><div></div>
                </div>
                <div id="paramRows" style="padding:8px 16px">
                    @php $params = $test?->parameters ?? collect([null]); @endphp
                    @foreach ($params as $i => $p)
                        <div class="param-row" style="display:grid;grid-template-columns:1.4fr .8fr .8fr .8fr 1fr .8fr .8fr 40px;gap:6px;margin-bottom:6px">
                            <input class="fi" name="parameters[{{ $i }}][name]" value="{{ $p->name ?? '' }}" placeholder="e.g. Haemoglobin">
                            <input class="fi" name="parameters[{{ $i }}][unit]" value="{{ $p->unit ?? '' }}" placeholder="g/dL">
                            <input class="fi" type="number" step="0.001" name="parameters[{{ $i }}][normal_low]" value="{{ $p->normal_low ?? '' }}">
                            <input class="fi" type="number" step="0.001" name="parameters[{{ $i }}][normal_high]" value="{{ $p->normal_high ?? '' }}">
                            <input class="fi" name="parameters[{{ $i }}][ref_range_text]" value="{{ $p->ref_range_text ?? '' }}" placeholder="< 200 / Negative">
                            <input class="fi" type="number" step="0.001" name="parameters[{{ $i }}][critical_low]" value="{{ $p->critical_low ?? '' }}">
                            <input class="fi" type="number" step="0.001" name="parameters[{{ $i }}][critical_high]" value="{{ $p->critical_high ?? '' }}">
                            <button type="button" class="btn btn-outline btn-xs" onclick="this.closest('.param-row').remove()">✕</button>
                        </div>
                    @endforeach
                </div>
                <div style="padding:14px 16px;border-top:1px solid var(--border);text-align:right">
                    <button class="btn btn-primary">{{ $test ? 'Update Test' : 'Save Test' }}</button>
                </div>
            </div>
        </form>
    </div>
</div></div>
@endsection

@push('script_2')
<script>
var pIdx = {{ ($test?->parameters->count() ?? 1) }};
function addParam(){
  var html='<div class="param-row" style="display:grid;grid-template-columns:1.4fr .8fr .8fr .8fr 1fr .8fr .8fr 40px;gap:6px;margin-bottom:6px">'
    +'<input class="fi" name="parameters['+pIdx+'][name]" placeholder="Parameter">'
    +'<input class="fi" name="parameters['+pIdx+'][unit]" placeholder="Unit">'
    +'<input class="fi" type="number" step="0.001" name="parameters['+pIdx+'][normal_low]">'
    +'<input class="fi" type="number" step="0.001" name="parameters['+pIdx+'][normal_high]">'
    +'<input class="fi" name="parameters['+pIdx+'][ref_range_text]" placeholder="Ref text">'
    +'<input class="fi" type="number" step="0.001" name="parameters['+pIdx+'][critical_low]">'
    +'<input class="fi" type="number" step="0.001" name="parameters['+pIdx+'][critical_high]">'
    +'<button type="button" class="btn btn-outline btn-xs" onclick="this.closest(\'.param-row\').remove()">✕</button></div>';
  document.getElementById('paramRows').insertAdjacentHTML('beforeend',html);
  pIdx++;
}
</script>
@endpush
