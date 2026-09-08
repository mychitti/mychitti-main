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
                                @php
                                    // Owner-or-permission, matching what the quick-save route itself
                                    // enforces: the permission middleware lets any vendor owner
                                    // through, while hasPermission() answers false for an owner
                                    // until some role has been granted patient/add — which would
                                    // hide a button whose own endpoint would have accepted the call.
                                    $canAddPatient = auth('vendor')->check() || hasPermission('patient', 'add');
                                @endphp
                                <div class="fg"><label class="fl">Patient *</label>
                                    {{-- Adding sits in the list itself rather than beside it: the desk
                                         goes looking for the patient first either way, and only finds
                                         out they are not on the register from this dropdown. Searching
                                         and adding are the same gesture, so they are the same control. --}}
                                    <select class="fs" name="patient_id" id="labPatientSelect" required>
                                        <option value="">Select patient...</option>
                                        @if ($canAddPatient)<option value="add_new">＋ Add New Patient</option>@endif
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

                            {{-- Where this order is actually run. Left blank it stays on the bench
                                 here, which is what every order did before these fields existed.

                                 The phone is not cosmetic: it is the number a handover verification
                                 code goes to when somebody turns up claiming to be from this lab,
                                 so it is the difference between being able to catch a stranger at
                                 the counter and having to take their word for it. --}}
                            <div class="fg" style="margin-bottom:14px">
                                <label class="fl">Send to an outside lab</label>
                                <div style="display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:8px">
                                    <select class="fs" name="external_lab_id" onchange="labPickReferral(this)">
                                        <option value="">Run in-house</option>
                                        @foreach ($referralLabs as $rl)
                                            <option value="{{ $rl->id }}" data-name="{{ $rl->f_name }}" data-phone="{{ $rl->phone }}">{{ $rl->f_name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="fi" name="external_lab_name" id="extLabName" placeholder="Lab name" maxlength="190">
                                    <input class="fi" name="external_lab_phone" id="extLabPhone" placeholder="Lab WhatsApp number" maxlength="40">
                                </div>
                                <div style="font-size:10px;color:var(--light)">Leave as “Run in-house” for tests done here. The number is where handover codes and confirmations are sent.</div>
                            </div>

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

        @if ($canAddPatient)
        {{-- Deliberately outside the order <form>: a nested form is invalid markup, and any named
             input in here would be posted with the lab order. Nothing below carries a name — the
             fields are read by id and sent on their own request, the same arrangement the OPD
             registration screen uses for its own quick-add. --}}
        <div class="qp-veil" id="labQpVeil" onclick="if(event.target===this)labQpClose()">
            <div class="qp-box" role="dialog" aria-modal="true" aria-labelledby="labQpTitle">
                <div class="qp-hd">
                    <h3 id="labQpTitle">Add New Patient</h3>
                    <button type="button" class="qp-x" onclick="labQpClose()" aria-label="Close">&times;</button>
                </div>
                <div class="qp-bd">
                    <div class="qp-err" id="labQpErr"></div>
                    <div class="frow2">
                        <div class="fg"><label class="fl">Name *</label>
                            <input class="fi" id="labQpName" autocomplete="off" placeholder="Patient name"></div>
                        <div class="fg"><label class="fl">Mobile *</label>
                            <input class="fi" id="labQpPhone" autocomplete="off" inputmode="numeric" placeholder="10-digit mobile"></div>
                    </div>
                    <div class="frow2">
                        <div class="fg"><label class="fl">Age *</label>
                            <input class="fi" id="labQpAge" type="number" min="0" max="150" placeholder="Years"></div>
                        <div class="fg"><label class="fl">Gender *</label>
                            <select class="fs" id="labQpGender">
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select></div>
                    </div>
                    <div class="fg" style="margin-bottom:0"><label class="fl">Address</label>
                        <input class="fi" id="labQpAddress" autocomplete="off" placeholder="Address (optional)"></div>
                </div>
                <div class="qp-ft">
                    <button type="button" class="btn btn-outline" onclick="labQpClose()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="labQpSave" onclick="labQpSave()">Save Patient</button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div></div>
@endsection

@push('css_or_js')
<style>
    /* select2custom.css forces `height:43px` and a pale 1px border onto every single select in
       the panel with !important, which is why this field stood 10px taller and lighter than the
       .fs controls either side of it. Overridden here, at higher specificity and with the same
       weapon, rather than edited there — that file dresses every other screen's dropdowns and is
       not this one's to change. */
    .labx .select2-container .select2-selection--single,
    .labx .select2-container--default .select2-selection--single{
        height:33px!important;min-height:33px!important;
        border:1.5px solid var(--border)!important;border-radius:8px!important;background:var(--white)}
    .labx .select2-container--default .select2-selection--single .select2-selection__rendered{
        line-height:30px!important;padding-left:10px;padding-right:26px;font-size:12px;color:var(--text)}
    .labx .select2-container--default .select2-selection--single .select2-selection__arrow{height:31px;right:4px}
    .labx .select2-container--default.select2-container--open .select2-selection--single{
        border-color:var(--blue)!important}
    .labx .select2-container--default .select2-selection--single .select2-selection__placeholder{
        color:#9CA3AF;font-size:12px}
    /* The .fg grid cell is what the label sits in; without this the container can size itself
       from its content and sit a pixel or two off its neighbours. */
    .labx .fg .select2-container{width:100%!important;display:block}
    /* Adding is an action, not another patient — it should not read as one more name in the list. */
    .select2-results__option[id$="-add_new"]{color:#1565C0;font-weight:700;border-bottom:1px solid #c8d2e0}
    /* Its own overlay rather than the theme's modal: this screen is scoped to .labx and carries
       none of the admin modal's styling, so a bootstrap modal here would render as an unstyled
       white slab in the middle of the lab's own design. */
    .qp-veil{display:none;position:fixed;inset:0;z-index:1080;background:rgba(13,17,23,.45);
        align-items:flex-start;justify-content:center;padding:60px 16px;overflow-y:auto}
    .qp-veil.open{display:flex}
    .qp-box{background:#fff;border-radius:12px;width:100%;max-width:520px;
        box-shadow:0 18px 48px rgba(13,17,23,.28);font-family:'DM Sans',sans-serif}
    .qp-hd{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;
        border-bottom:1px solid #c8d2e0}
    .qp-hd h3{margin:0;font-size:14px;font-weight:800;color:#0A2463}
    .qp-x{background:none;border:none;font-size:22px;line-height:1;color:#9CA3AF;cursor:pointer;padding:0 2px}
    .qp-bd{padding:16px 18px}
    .qp-ft{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid #c8d2e0}
    .qp-err{display:none;background:#FFEBEE;border:1px solid #ffcdd2;color:#B71C1C;
        border-radius:8px;padding:7px 10px;font-size:11.5px;font-weight:600;margin-bottom:12px}
    .qp-err.show{display:block}
</style>
@endpush

@push('script_2')
<script>
// ---- Quick-add patient -------------------------------------------------------
// Posts to the same vendor.patient.quick-save the OPD registration screen uses, so a patient
// added from the bench is identical to one added at the front desk — same UID series, same
// medical-history row — rather than a second, thinner kind of patient record.
function labQpOpen(){
  var v=document.getElementById('labQpVeil');
  if(!v)return;
  // Cleared on the way in, so a previous abandoned attempt is not sitting there to be saved twice.
  ['labQpName','labQpPhone','labQpAge','labQpAddress'].forEach(function(id){
    var el=document.getElementById(id); if(el)el.value='';
  });
  var g=document.getElementById('labQpGender'); if(g)g.value='';
  labQpErr('');
  v.classList.add('open');
  document.getElementById('labQpName').focus();
}

// Searchable, because a hospital's patient list outgrows a plain dropdown almost immediately, and
// "Add New Patient" is one of its rows — picking it opens the modal and puts the select back to
// blank, so nothing can be submitted as the literal string 'add_new'. (storeOrder validates
// patient_id against exists:patients,id in any case.)
if(window.jQuery && jQuery.fn.select2){
  jQuery('#labPatientSelect').select2({placeholder:'Select patient...',width:'100%',allowClear:false});
  jQuery('#labPatientSelect').on('select2:select', function(e){
    if(e.params.data.id==='add_new'){
      jQuery(this).val('').trigger('change');
      labQpOpen();
    }
  });
}
function labQpClose(){
  var v=document.getElementById('labQpVeil');
  if(!v)return;
  v.classList.remove('open');
  labQpErr('');
}
function labQpErr(msg){
  var e=document.getElementById('labQpErr');
  if(!e)return;
  e.textContent=msg||'';
  e.classList.toggle('show',!!msg);
}
function labQpSave(){
  var name=(document.getElementById('labQpName').value||'').trim();
  var phone=(document.getElementById('labQpPhone').value||'').trim();
  var age=(document.getElementById('labQpAge').value||'').trim();
  var gender=document.getElementById('labQpGender').value;
  var address=(document.getElementById('labQpAddress').value||'').trim();

  // Checked here as well as on the server so a typo costs a glance, not a round trip.
  if(!name){labQpErr('Patient name is required.');document.getElementById('labQpName').focus();return;}
  if(!/^(?:\+?91|0)?[6-9]\d{9}$/.test(phone.replace(/[\s\-()]/g,''))){
    labQpErr('Enter a valid 10-digit mobile number.');document.getElementById('labQpPhone').focus();return;}
  if(age===''||isNaN(age)||Number(age)<0||Number(age)>150){
    labQpErr('Enter an age between 0 and 150.');document.getElementById('labQpAge').focus();return;}
  if(!gender){labQpErr('Gender is required.');document.getElementById('labQpGender').focus();return;}

  var btn=document.getElementById('labQpSave');
  btn.disabled=true;btn.textContent='Saving...';
  labQpErr('');

  var body=new FormData();
  body.append('_token','{{ csrf_token() }}');
  body.append('name',name);
  body.append('phone',phone);
  body.append('age',age);
  body.append('gender',gender);
  body.append('address',address);

  fetch('{{ route("vendor.patient.quick-save") }}',{
    method:'POST',
    body:body,
    headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
    credentials:'same-origin'
  })
  .then(function(r){return r.json().then(function(d){return {ok:r.ok,data:d};});})
  .then(function(res){
    var d=res.data||{};
    if(res.ok&&d.success&&d.patient){
      // Straight into the dropdown and selected, so the half-filled order is still there to finish.
      var sel=document.getElementById('labPatientSelect');
      var opt=new Option(d.patient.text,d.patient.id,true,true);
      sel.appendChild(opt);
      // select2 renders from its own copy of the options, so it has to be told to repaint;
      // the native path stays for the case where the library did not load.
      if(window.jQuery && jQuery.fn.select2 && jQuery(sel).hasClass('select2-hidden-accessible')){
        jQuery(sel).val(d.patient.id).trigger('change');
      }else{
        sel.value=d.patient.id;
        sel.dispatchEvent(new Event('change',{bubbles:true}));
      }
      labQpClose();
      return;
    }
    // Laravel hands validation back as {errors:{field:[msg]}} with a 422.
    var msg=d.message||'Could not save that patient.';
    if(d.errors){
      var first=Object.keys(d.errors)[0];
      if(first&&d.errors[first]&&d.errors[first][0])msg=d.errors[first][0];
    }
    labQpErr(msg);
  })
  .catch(function(){labQpErr('Something went wrong. Please try again.');})
  .finally(function(){btn.disabled=false;btn.textContent='Save Patient';});
}
document.addEventListener('keydown',function(e){
  var v=document.getElementById('labQpVeil');
  if(!v||!v.classList.contains('open'))return;
  if(e.key==='Escape')labQpClose();
  if(e.key==='Enter'&&v.contains(e.target)&&e.target.tagName!=='BUTTON'){e.preventDefault();labQpSave();}
});

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
// Picking a lab fills its name and number in, but only into boxes still empty — a number
// corrected for this one order is the number that lab actually answers, and re-selecting the
// same lab should not quietly put the stale one back.
function labPickReferral(sel){
  var opt=sel.options[sel.selectedIndex];
  var n=document.getElementById('extLabName'), p=document.getElementById('extLabPhone');
  if(!opt||!opt.value){ return; }
  if(n && !n.value.trim()) n.value=opt.dataset.name||'';
  if(p && !p.value.trim()) p.value=opt.dataset.phone||'';
}
</script>
@endpush
