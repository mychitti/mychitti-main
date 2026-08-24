@extends('layouts.vendor.app')
@section('title', 'Laboratory — Test Worklist')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        @php
            $wlCols = 'grid-template-columns:90px 1fr 150px 130px 95px 95px 96px;display:grid;gap:8px';
            $lowReagents = \App\Models\LabReagent::where('store_id', \App\CentralLogics\Helpers::get_store_id())->get()
                ->filter(fn($r) => in_array($r->statusLevel(), ['low', 'critical', 'out']));
        @endphp
        <div class="layout-2col">
            <div>
                <div class="lcard">
                    <div class="card-hd">
                        <h3><div class="hd-icon" style="background:var(--ltblue)">📋</div> Today's Test Worklist</h3>
                        <div class="card-actions">
                            <form method="get" class="mb-0">
                                <select name="department" class="fsel" onchange="this.form.submit()">
                                    <option value="">All Departments</option>
                                    @foreach (['OPD', 'IPD', 'ICU', 'Emergency'] as $d)
                                        <option value="{{ $d }}" {{ request('department') === $d ? 'selected' : '' }}>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @if (hasPermission('lab_order', 'view'))<a href="{{ route('vendor.lab.order') }}" class="btn btn-primary btn-sm">+ Order Test</a>@endif
                        </div>
                    </div>
                    <div class="search-bar">
                        <div class="search-wrap"><input class="si" id="wlSearch" placeholder="🔍 Search patient, test, sample ID..." oninput="wlFilter()"></div>
                        <select class="fsel" id="wlStatus" onchange="wlFilter()"><option value="">All Status</option><option>ordered</option><option>in_progress</option><option>verified</option><option>sent</option></select>
                        <select class="fsel" id="wlPriority" onchange="wlFilter()"><option value="">All Priority</option><option>urgent</option><option>stat</option><option>routine</option></select>
                    </div>
                    <div class="tbl-hd" style="{{ $wlCols }}">
                        <div>Sample ID</div><div>Patient</div><div>Test(s)</div><div>Ordered By</div><div>Priority</div><div>Status</div><div>Action</div>
                    </div>
                    <div id="wlBody">
                        @forelse ($orders as $o)
                            @php
                                $isUrgent = in_array($o->priority, ['urgent', 'stat']);
                                $rowCls = $o->status === 'in_progress' ? 'active-row' : ($isUrgent && $o->status === 'ordered' ? 'urgent' : (in_array($o->status, ['verified', 'sent']) ? 'done' : ''));
                                $doc = $o->doctorProfile ? 'Dr. ' . trim(($o->doctorProfile->employee->f_name ?? '') . ' ' . ($o->doctorProfile->employee->l_name ?? '')) : ($o->referred_by ?: '—');
                                $testNames = $o->items->pluck('test_name');
                            @endphp
                            <div class="tbl-row {{ $rowCls }}" style="{{ $wlCols }}" data-status="{{ $o->status }}" data-priority="{{ $o->priority }}" data-search="{{ strtolower($o->order_no . ' ' . ($o->patient->name ?? '') . ' ' . $testNames->implode(' ')) }}">
                                <div class="num" style="font-size:11px;color:var(--blue)">{{ $o->order_no }}</div>
                                <div><div style="font-size:13px;font-weight:700">{{ $o->patient->name ?? '—' }}</div><div style="font-size:10px;color:var(--light)">{{ $o->patient->patient_uid ?? '' }}{{ $o->department ? ' · ' . $o->department : '' }}</div></div>
                                <div><div style="font-size:11px;font-weight:600">{{ $testNames->take(2)->implode(', ') }}</div>@if ($testNames->count() > 2)<div style="font-size:10px;color:var(--muted)">+ {{ $testNames->count() - 2 }} more</div>@endif</div>
                                <div style="font-size:11px">{{ $doc }}</div>
                                <div>
                                    @if ($o->priority === 'stat')<span class="pill pill-red">🚨 STAT</span>
                                    @elseif ($o->priority === 'urgent')<span class="pill pill-red">🚨 Urgent</span>
                                    @else<span class="pill pill-blue">Routine</span>@endif
                                </div>
                                <div>
                                    @if ($o->status === 'ordered')<span class="pill pill-amber">Pending</span>
                                    @elseif ($o->status === 'in_progress')<span class="pill pill-navy">● In Progress</span>
                                    @elseif ($o->status === 'resulted')<span class="pill pill-purple">Resulted</span>
                                    @elseif ($o->status === 'verified')<span class="pill pill-teal">✓ Verified</span>
                                    @else<span class="pill pill-green">✓ Sent</span>@endif
                                </div>
                                <div>
                                    @if ($o->status === 'ordered')
                                        @if (hasPermission('lab_worklist', 'edit'))<a href="{{ route('vendor.lab.orders.start', $o->id) }}" class="btn {{ $isUrgent ? 'btn-red' : 'btn-primary' }} btn-xs">Start</a>@endif
                                    @elseif (in_array($o->status, ['in_progress', 'resulted']))
                                        @if (hasPermission('lab_result', 'view'))<a href="{{ route('vendor.lab.result-entry', ['order' => $o->id]) }}" class="btn btn-green btn-xs">✓ Enter</a>@endif
                                    @elseif (hasPermission('lab_report', 'view'))
                                        <a href="{{ route('vendor.lab.orders.report', $o->id) }}" target="_blank" class="btn btn-outline btn-xs">Report</a>
                                    @endif

                                    {{-- Only on orders that leave the building. An in-house order
                                         never has a stranger at the counter, and offering a
                                         chain-of-custody button on one is an invitation to record
                                         handovers that are really just staff walking down a
                                         corridor. --}}
                                    @if ($o->is_outsourced && hasPermission('lab_worklist', 'edit'))
                                        <a href="javascript:void(0)" class="btn btn-outline btn-xs"
                                           onclick="hoOpen({{ $o->id }}, 'out', 'lab_order')" title="Samples going to the lab">↑ Out</a>
                                        <a href="javascript:void(0)" class="btn btn-outline btn-xs"
                                           onclick="hoOpen({{ $o->id }}, 'in', 'lab_order')" title="Report or samples coming back">↓ In</a>
                                    @endif
                                </div>
                            </div>

                            {{-- An arrival nobody has vouched for. Shown on the worklist row itself
                                 rather than only inside the order, because the whole point is that
                                 a technician about to key results off a delivered report sees that
                                 the report's origin was never confirmed BEFORE they type it in. --}}
                            @if (($wlUnconfirmed[$o->id] ?? null))
                                {{-- Deliberately NOT .tbl-row: wlFilter() forces every .tbl-row it
                                     finds back to display:grid, which would flatten this banner
                                     into seven invisible columns the first time anyone typed in
                                     the search box. --}}
                                <div class="wl-warn" style="background:#FEF2F2;border-left:3px solid #DC2626;padding:6px 10px;font-size:11px;color:#991B1B">
                                    ⚠ A delivery on this order is <strong>not yet confirmed</strong> with
                                    {{ $wlUnconfirmed[$o->id]->lab_name ?: 'the lab' }} —
                                    handed over by {{ $wlUnconfirmed[$o->id]->person_name }} on
                                    {{ optional($wlUnconfirmed[$o->id]->happened_at)->format('d M, h:i A') }}.
                                    Confirm it before treating the report as genuine.
                                </div>
                            @endif
                        @empty
                            <div class="empty">No lab orders in the last 2 days. @if (hasPermission('lab_order', 'view'))<a href="{{ route('vendor.lab.order') }}">Order a test →</a>@endif</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                @if (hasPermission('lab_critical', 'view'))
                <div class="lcard">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltred)">🚨</div> Critical Values</h3><span class="pill pill-red">{{ $criticalAlerts->count() }} Active</span></div>
                    @forelse ($criticalAlerts as $a)
                        <div class="alert-row"><div class="alert-dot" style="background:var(--redB)"></div><div style="flex:1"><div class="alert-title">{{ $a->order->patient->name ?? 'Patient' }} — {{ $a->parameter_name }} = {{ $a->result_value }} {{ $a->unit }}</div><div class="alert-sub">CRITICAL {{ $a->result_flag === 'H' ? 'HIGH' : 'LOW' }} · {{ $a->order->department }}</div></div>
                            @if (hasPermission('lab_critical', 'notify'))<form method="post" action="{{ route('vendor.lab.results.notify', $a->id) }}" class="mb-0">@csrf<button class="btn btn-red btn-xs">Notify</button></form>@endif</div>
                    @empty
                        <div class="empty" style="padding:20px">No critical values 🎉</div>
                    @endforelse
                    @if ($criticalAlerts->count())
                        <div style="padding:10px 14px;border-top:1px solid var(--border)">
                            @if (hasPermission('lab_critical', 'notify'))<form method="post" action="{{ route('vendor.lab.critical.notify-all') }}" class="mb-0">@csrf<button class="btn btn-red btn-sm" style="width:100%">📢 Notify All Doctors</button></form>@endif
                        </div>
                    @endif
                </div>
                @endif

                <div class="lcard">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">📊</div> Today's Stats</h3></div>
                    <div class="stat-row"><span class="stat-l">Total Tests Ordered</span><span class="stat-v num">{{ $labStats['totalToday'] }}</span></div>
                    <div class="stat-row"><span class="stat-l">Completed</span><span class="stat-v g num">{{ $labStats['completed'] }}</span></div>
                    <div class="stat-row"><span class="stat-l">In Progress</span><span class="stat-v a num">{{ $labStats['inProgress'] }}</span></div>
                    <div class="stat-row"><span class="stat-l">Pending</span><span class="stat-v b num">{{ $labStats['testsPending'] }}</span></div>
                    <div class="stat-row"><span class="stat-l">Critical Results</span><span class="stat-v r num">{{ $labStats['criticalOpen'] }}</span></div>
                    <div class="stat-row"><span class="stat-l">Patients Today</span><span class="stat-v num">{{ $labStats['patientsToday'] }}</span></div>
                </div>

                @if (hasPermission('lab_reagent', 'view'))
                <div class="lcard">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltpurple)">🧪</div> Low Reagent Alerts</h3></div>
                    @forelse ($lowReagents as $r)
                        <div class="alert-row"><div class="alert-dot" style="background:{{ $r->statusLevel() === 'critical' || $r->statusLevel() === 'out' ? 'var(--redB)' : 'var(--amberA)' }}"></div><div><div class="alert-title">{{ $r->name }}</div><div class="alert-sub">{{ (int) $r->stock }} {{ $r->unit_label }} remaining{{ $r->statusLevel() === 'critical' ? ' · Reorder urgently' : '' }}</div></div></div>
                    @empty
                        <div class="empty" style="padding:18px">All reagents in stock ✓</div>
                    @endforelse
                    <div style="padding:10px 14px;border-top:1px solid var(--border)"><a href="{{ route('vendor.lab.reagents') }}" class="btn btn-outline btn-sm" style="width:100%">Manage Reagents →</a></div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div></div>

@if (hasPermission('lab_worklist', 'edit'))
    @include('hmis::vendor.handover._modal', ['hoSubjectType' => 'lab_order'])
@endif
@endsection

@push('script_2')
<script>
function wlFilter(){
  var q=(document.getElementById('wlSearch').value||'').toLowerCase();
  var st=document.getElementById('wlStatus').value, pr=document.getElementById('wlPriority').value;
  document.querySelectorAll('#wlBody .tbl-row').forEach(function(r){
    var ok=(!q||(r.dataset.search||'').includes(q))&&(!st||r.dataset.status===st)&&(!pr||r.dataset.priority===pr);
    r.style.display=ok?'grid':'none';
  });
}
</script>
@endpush
