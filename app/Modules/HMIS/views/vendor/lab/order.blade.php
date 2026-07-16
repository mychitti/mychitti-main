@extends('layouts.vendor.app')
@section('title', 'Laboratory — Order New Test')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        <form method="post" action="{{ route('vendor.lab.order.store') }}">
            @csrf
            <div class="layout-2col">
                <div>
                    <div class="lcard">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">➕</div> Order New Lab Test</h3></div>
                        <div style="padding:14px">
                            <div class="frow3">
                                <div class="fg"><label class="fl">Patient *</label>
                                    <select class="fs" name="patient_id" required>
                                        <option value="">Select patient...</option>
                                        @foreach ($patients as $p)<option value="{{ $p->id }}">{{ $p->name }} {{ $p->patient_uid ? '(' . $p->patient_uid . ')' : '' }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="fg"><label class="fl">Ordering Doctor</label>
                                    <select class="fs" name="doctor_profile_id">
                                        <option value="">— None / External —</option>
                                        @foreach ($doctors as $d)<option value="{{ $d->id }}">Dr. {{ trim(($d->employee->f_name ?? '') . ' ' . ($d->employee->l_name ?? '')) ?: $d->specialization }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="fg"><label class="fl">Priority</label>
                                    <select class="fs" name="priority"><option value="routine">Routine</option><option value="urgent">Urgent</option><option value="stat">Emergency (STAT)</option></select>
                                </div>
                            </div>
                            <div class="frow3">
                                <div class="fg"><label class="fl">Department</label>
                                    <select class="fs" name="department"><option>OPD</option><option>IPD</option><option>ICU</option><option>Emergency</option></select>
                                </div>
                                <div class="fg"><label class="fl">Sample Type(s)</label>
                                    <select class="fs" name="sample_types[]" id="sampleTypes" multiple>
                                        @foreach ($sampleTypes as $s)
                                            <option value="{{ $s }}">{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    <div style="font-size:10px;color:var(--light)">Auto-filled from the tests you pick — type to add a one-off, or set a test's sample in the <a href="{{ route('vendor.lab.catalog') }}" style="color:var(--blue);font-weight:600">Test Catalog</a>.</div>
                                </div>
                                <div class="fg"><label class="fl">External Referral</label><input class="fi" name="referred_by" placeholder="Outside doctor / clinic"></div>
                            </div>
                            <div class="fg" style="margin-bottom:14px"><label class="fl">Clinical Notes</label><input class="fi" name="clinical_notes" placeholder="Diagnosis / reason for test..."></div>

                            <div class="fg"><label class="fl">Select Tests</label>
                                <input class="fi" id="testFilter" placeholder="Filter tests..." style="margin:6px 0">
                                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px" id="testGrid">
                                    @forelse ($tests as $t)
                                        <label class="test-opt" data-name="{{ strtolower($t->name) }}" onclick="setTimeout(labRecalc,0)">
                                            <input type="checkbox" name="tests[]" value="{{ $t->id }}" data-price="{{ $t->price }}" data-sample="{{ $t->sample_type }}" style="accent-color:var(--blue)">
                                            <div><div style="font-size:12px;font-weight:600">{{ $t->name }}</div><div style="font-size:10px;color:var(--light)">{{ $t->department }} · {{ \App\CentralLogics\Helpers::format_currency($t->price) }}</div></div>
                                        </label>
                                    @empty
                                        <div class="empty" style="grid-column:1/-1">No tests in catalog. <a href="{{ route('vendor.lab.catalog.create') }}">Add a test →</a></div>
                                    @endforelse
                                </div>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0 0;border-top:1px solid var(--border);margin-top:14px">
                                <div style="font-size:13px;font-weight:700">Selected: <span id="testCount" style="color:var(--blue)">0 tests</span> · Total: <span id="testTotal" class="num" style="color:var(--greenA)">{{ \App\CentralLogics\Helpers::format_currency(0) }}</span></div>
                                @if (hasPermission('lab_order', 'add'))<button class="btn btn-primary" type="submit">📋 Place Lab Order</button>@endif
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="lcard">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">⏱</div> Turnaround Times</h3></div>
                        @foreach ($tests->take(8) as $t)
                            <div style="display:flex;justify-content:space-between;padding:7px 16px;border-bottom:1px solid #F3F4F6;font-size:11px"><span>{{ $t->name }}</span><span class="num">{{ $t->tat_text ?: '—' }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>
</div></div>
@endsection

@push('script_2')
<script>
var sym="{{ \App\CentralLogics\Helpers::currency_symbol() ?? '₹' }}";
// Select every sample the chosen tests need (an order can need blood AND urine).
// Only ever adds — anything picked by hand stays picked.
function labSyncSamples(){
  var sel=document.getElementById('sampleTypes');
  if(!sel) return;
  var needed={};
  document.querySelectorAll('#testGrid input[type=checkbox]:checked').forEach(function(cb){
    (cb.dataset.sample||'').split(',').forEach(function(s){
      s=s.trim(); if(s){needed[s.toLowerCase()]=true;}
    });
  });
  var changed=false;
  Array.prototype.forEach.call(sel.options,function(op){
    if(needed[op.value.toLowerCase()] && !op.selected){op.selected=true;changed=true;}
  });
  // Repaint select2 without re-firing our own change handlers.
  if(changed && window.jQuery && jQuery(sel).hasClass('select2-hidden-accessible')){
    jQuery(sel).trigger('change.select2');
  }
}
function labRecalc(){
  var c=0,t=0;
  document.querySelectorAll('#testGrid input[type=checkbox]').forEach(function(cb){
    cb.closest('.test-opt').classList.toggle('sel',cb.checked);
    if(cb.checked){c++;t+=parseFloat(cb.dataset.price||0);}
  });
  document.getElementById('testCount').textContent=c+' test'+(c!==1?'s':'');
  document.getElementById('testTotal').textContent=sym+' '+t.toFixed(2);
  labSyncSamples();
}
if(window.jQuery && jQuery.fn.select2){
  jQuery('#sampleTypes').select2({placeholder:'Select sample type(s)',width:'100%',closeOnSelect:false,tags:true});
}
document.querySelectorAll('#testGrid input').forEach(function(cb){cb.addEventListener('change',labRecalc);});
document.getElementById('testFilter').addEventListener('input',function(){
  var q=this.value.toLowerCase();
  document.querySelectorAll('#testGrid .test-opt').forEach(function(o){o.style.display=o.dataset.name.includes(q)?'':'none';});
});
</script>
@endpush
