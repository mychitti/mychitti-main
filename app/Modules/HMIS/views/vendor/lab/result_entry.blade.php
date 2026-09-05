@extends('layouts.vendor.app')
@section('title', 'Laboratory — Result Entry')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        @if (!$order)
            <div class="lcard"><div class="empty">No samples awaiting result entry. <a href="{{ route('vendor.lab.worklist') }}">Go to worklist →</a></div></div>
        @else
            @php
                $locked = in_array($order->status, ['verified', 'sent']);
                $age = $order->patient?->dob ? \Carbon\Carbon::parse($order->patient->dob)->age . 'Y' : '—';
            @endphp
            {{-- Above the form, not beside it. Once these numbers are typed they are
                 indistinguishable from any other result in the patient's chart, so the one useful
                 place to say "nobody has confirmed where this report came from" is in front of the
                 person about to type them. --}}
            @if (!empty($reUnconfirmed))
                <div style="background:#FEF2F2;border:1px solid #FECACA;border-left:4px solid #DC2626;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#991B1B">
                    <strong>⚠ This order's delivery has not been confirmed with the lab.</strong><br>
                    {{ $reUnconfirmed->person_name }}
                    @if (filled($reUnconfirmed->lab_name)) of {{ $reUnconfirmed->lab_name }} @endif
                    delivered it on {{ optional($reUnconfirmed->happened_at)->format('d M Y · h:i A') }},
                    and nobody at {{ $reUnconfirmed->lab_name ?: 'the lab' }} has vouched for them.
                    Confirm the handover before entering these results
                    (<a href="{{ route('vendor.handover.slip', $reUnconfirmed->id) }}" target="_blank" style="color:#991B1B;font-weight:700">view the handover</a>).
                </div>
            @endif

            <div class="layout-2col">
                <div>
                    <form method="post" action="{{ route('vendor.lab.orders.results', $order->id) }}">
                        @csrf
                        <div class="lcard">
                            <div class="card-hd">
                                <h3><div class="hd-icon" style="background:var(--ltteal)">🔬</div> Result Entry — {{ $order->order_no }} · {{ $order->patient->name ?? '' }}</h3>
                                <div class="card-actions">
                                    <select class="fsel" onchange="if(this.value)location.href='{{ route('vendor.lab.result-entry') }}?order='+this.value">
                                        @foreach ($pickable as $p)
                                            <option value="{{ $p->id }}" {{ $p->id == $order->id ? 'selected' : '' }}>{{ $p->order_no }} — {{ $p->patient->name ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div style="padding:12px 16px;background:#F9FAFB;border-bottom:1px solid var(--border);display:flex;gap:24px;flex-wrap:wrap">
                                <div><span style="font-size:10px;color:var(--light);text-transform:uppercase;display:block">Patient</span><span style="font-size:12px;font-weight:700">{{ $order->patient->name ?? '—' }} · {{ $order->patient->patient_uid ?? '' }}</span></div>
                                <div><span style="font-size:10px;color:var(--light);text-transform:uppercase;display:block">Age/Sex</span><span style="font-size:12px;font-weight:700">{{ $age }} / {{ ucfirst($order->patient->gender ?? '—') }}</span></div>
                                <div><span style="font-size:10px;color:var(--light);text-transform:uppercase;display:block">Doctor</span><span style="font-size:12px;font-weight:700">{{ $order->doctorProfile ? 'Dr. ' . trim(($order->doctorProfile->employee->f_name ?? '') . ' ' . ($order->doctorProfile->employee->l_name ?? '')) : ($order->referred_by ?: '—') }}</span></div>
                                <div><span style="font-size:10px;color:var(--light);text-transform:uppercase;display:block">Sample</span><span style="font-size:12px;font-weight:700">{{ $order->sample_type ?: '—' }}</span></div>
                                <div><span style="font-size:10px;color:var(--light);text-transform:uppercase;display:block">Priority</span><span class="pill pill-blue" style="margin-top:2px">{{ ucfirst($order->priority) }}</span></div>
                            </div>

                            @foreach ($order->items as $item)
                                <div class="result-section-title">{{ $item->test_name }}</div>
                                <div class="result-hd"><div>Parameter</div><div>Result</div><div>Unit</div><div>Reference Range</div><div>Flag</div></div>
                                @foreach ($item->results as $res)
                                    @php
                                        $ref = $res->ref_range_text ?: (($res->normal_low !== null || $res->normal_high !== null) ? trim(($res->normal_low ?? '') . ' – ' . ($res->normal_high ?? '')) : '—');
                                        $icls = $res->result_flag === 'H' ? 'high' : ($res->result_flag === 'L' ? 'low' : ($res->result_flag === 'N' ? 'normal' : ''));
                                    @endphp
                                    <div class="result-row">
                                        <div style="font-weight:600">{{ $res->parameter_name }}{!! $res->is_critical ? ' <span class="pill pill-red" style="font-size:8px">CRITICAL</span>' : '' !!}</div>
                                        <div><input class="result-input {{ $icls }}" name="result_value[{{ $res->id }}]" value="{{ $res->result_value }}"
                                            data-low="{{ $res->normal_low }}" data-high="{{ $res->normal_high }}" oninput="labFlag(this)" {{ $locked ? 'readonly' : '' }}></div>
                                        <div style="font-size:11px;color:var(--muted)">{{ $res->unit }}</div>
                                        <div class="ref-range">{{ $ref }}</div>
                                        <div class="flag-cell flag-{{ $res->result_flag === 'H' ? 'high' : ($res->result_flag === 'L' ? 'low' : 'norm') }}">
                                            {{ $res->result_flag === 'H' ? '▲ HIGH' : ($res->result_flag === 'L' ? '▼ LOW' : ($res->result_flag === 'N' ? '● Normal' : '')) }}
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Prefilled from the catalogue when the test was ordered. Edited
                                     here it becomes this patient's report and nothing else's. --}}
                                <div class="fg" style="padding:10px 16px 4px">
                                    <label class="fl">Interpretation — printed under {{ $item->test_name }}</label>
                                    <textarea class="fi" name="interpretation[{{ $item->id }}]" rows="4"
                                              style="resize:vertical" {{ $locked ? 'readonly' : '' }}
                                              placeholder="Leave blank to print no interpretation for this test.">{{ filled($item->interpretation) ? $item->interpretation : ($item->test->interpretation ?? '') }}</textarea>
                                </div>
                            @endforeach

                            <div style="padding:14px 16px;border-top:1px solid var(--border)">
                                <div class="fg" style="margin-bottom:10px"><label class="fl">Lab Technician Notes / Comments</label><textarea class="fi" name="technician_notes" rows="2" style="resize:none" {{ $locked ? 'readonly' : '' }}>{{ $order->technician_notes }}</textarea></div>
                                <div class="frow3">
                                    @php
                                        // What is already on the report wins; an untouched report
                                        // opens on whoever is at the screen. Any name saved before
                                        // these were free-text boxes is kept as an option of its
                                        // own, so opening an old report cannot quietly rename who
                                        // analysed it.
                                        $reAnalysed = $order->analysed_by ?: $currentSigner;
                                        $reVerified = $order->verified_by_name ?: $currentSigner;
                                        $reKnown    = $signers->pluck('name')->all();
                                        $reGroups   = $signers->groupBy('group');
                                    @endphp
                                    <div class="fg">
                                        <label class="fl">Analysed By</label>
                                        <select class="fs" name="analysed_by" {{ $locked ? 'disabled' : '' }}>
                                            <option value="">— not recorded —</option>
                                            @if (filled($reAnalysed) && !in_array($reAnalysed, $reKnown))
                                                <option value="{{ $reAnalysed }}" selected>{{ $reAnalysed }}</option>
                                            @endif
                                            @foreach ($reGroups as $reGroup => $reMembers)
                                                <optgroup label="{{ $reGroup }}">
                                                    @foreach ($reMembers as $reSigner)
                                                        <option value="{{ $reSigner['name'] }}" {{ $reAnalysed === $reSigner['name'] ? 'selected' : '' }}>{{ $reSigner['name'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="fg">
                                        <label class="fl">Verified By</label>
                                        <select class="fs" name="verified_by" {{ $locked ? 'disabled' : '' }}>
                                            <option value="">— not recorded —</option>
                                            @if (filled($reVerified) && !in_array($reVerified, $reKnown))
                                                <option value="{{ $reVerified }}" selected>{{ $reVerified }}</option>
                                            @endif
                                            @foreach ($reGroups as $reGroup => $reMembers)
                                                <optgroup label="{{ $reGroup }}">
                                                    @foreach ($reMembers as $reSigner)
                                                        <option value="{{ $reSigner['name'] }}" {{ $reVerified === $reSigner['name'] ? 'selected' : '' }}>{{ $reSigner['name'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="fg"><label class="fl">Report Date</label><input class="fi" value="{{ ($order->reported_at ?? now())->format('d M Y · h:i A') }}" readonly style="background:#F9FAFB"></div>
                                </div>
                                @if (!$locked)
                                    @if (hasPermission('lab_result', 'edit'))
                                    <div style="display:flex;gap:8px;justify-content:flex-end">
                                        <button class="btn btn-outline" name="finalize" value="0">Save Draft</button>
                                        <button class="btn btn-teal" name="finalize" value="1" onclick="return confirm('Finalize & verify this report? Results will be locked.')">📤 Finalize &amp; Send Report</button>
                                    </div>
                                    @endif
                                @else
                                    <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center">
                                        <span class="pill pill-teal">✓ Verified — locked</span>
                                        <a href="{{ route('vendor.lab.orders.report', $order->id) }}" target="_blank" class="btn btn-primary">🖨 Print Report</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                <div>
                    <div class="lcard">
                        <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">👁</div> Report Preview</h3><a href="{{ route('vendor.lab.orders.report', $order->id) }}" target="_blank" class="btn btn-outline btn-sm">Full</a></div>
                        <div style="padding:12px 14px;font-size:11px">
                            <div style="text-align:center;margin-bottom:8px"><div style="font-size:13px;font-weight:800">{{ \App\CentralLogics\Helpers::get_store_data()->name ?? 'Laboratory' }}</div></div>
                            <div style="display:flex;justify-content:space-between;color:var(--muted)"><span>Patient</span><span style="font-weight:700;color:var(--text)">{{ $order->patient->name ?? '—' }}</span></div>
                            <div style="display:flex;justify-content:space-between;color:var(--muted)"><span>Sample</span><span style="font-weight:700;color:var(--text)">{{ $order->order_no }}</span></div>
                            <hr style="border:none;border-top:1px dashed var(--border);margin:8px 0">
                            @foreach ($order->results as $r)
                                @if ($r->result_value)
                                    <div style="display:flex;justify-content:space-between;padding:2px 0">
                                        <span>{{ $r->parameter_name }}</span>
                                        <span class="num" style="color:{{ $r->result_flag === 'H' ? 'var(--redA)' : ($r->result_flag === 'L' ? 'var(--amber)' : 'var(--greenA)') }}">{{ $r->result_value }} {{ $r->result_flag === 'H' ? '▲' : ($r->result_flag === 'L' ? '▼' : '') }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div></div>
@endsection

@push('script_2')
<script>
function labFlag(input){
  var v=parseFloat(input.value);
  var low=input.dataset.low!==''?parseFloat(input.dataset.low):null;
  var high=input.dataset.high!==''?parseFloat(input.dataset.high):null;
  var cell=input.closest('.result-row').querySelector('.flag-cell');
  input.classList.remove('high','low','normal');
  if(isNaN(v)){ if(cell){cell.className='flag-cell';cell.textContent='';} return; }
  if(high!==null&&!isNaN(high)&&v>high){ input.classList.add('high'); if(cell){cell.className='flag-cell flag-high';cell.textContent='▲ HIGH';} }
  else if(low!==null&&!isNaN(low)&&v<low){ input.classList.add('low'); if(cell){cell.className='flag-cell flag-low';cell.textContent='▼ LOW';} }
  else { input.classList.add('normal'); if(cell){cell.className='flag-cell flag-norm';cell.textContent='● Normal';} }
}
</script>
@endpush
