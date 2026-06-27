@extends('layouts.vendor.app')

@section('title', 'Branches & Counters')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Branches &amp; Counters</h1>
                <div class="sub">Outlets, billing counters and the staff assigned to each</div>
            </div>
        </div>


        @php
            $canViewBranches = hasPermission('pos_branch', 'view') || hasPermission('pos_branch', 'delete') || hasPermission('pos_counter', 'delete');
            $showAddCol = hasPermission('pos_branch', 'create') || hasPermission('pos_counter', 'create');
        @endphp
        <div class="row">
            @if ($showAddCol)
            <div class="col-md-{{ $canViewBranches ? '4' : '12' }}">
                @if (hasPermission('pos_branch', 'create'))
                <div class="rp-card">
                    <div class="hd"><span class="accent">Add Branch</span></div>
                    <div class="bd">
                        <form method="post" action="{{ route('vendor.retail-pos.branches.store') }}">
                            @csrf
                            <div class="rp-field"><label>Branch name *</label><input type="text" name="name" class="rp-input" placeholder="e.g. Main Branch" required></div>
                            <div class="rp-field"><label>Address</label><input type="text" name="address" class="rp-input" placeholder="Location / address"></div>
                            <button class="rp-btn p">Add Branch</button>
                        </form>
                    </div>
                </div>
                @endif

                @if (hasPermission('pos_counter', 'create'))
                <div class="rp-card">
                    <div class="hd"><span class="accent">Add Counter</span></div>
                    <div class="bd">
                        @if ($branches->isEmpty())
                            <p class="text-muted mb-0">Add a branch first.</p>
                        @else
                            <form method="post" action="{{ route('vendor.retail-pos.terminals.store') }}">
                                @csrf
                                <div class="rp-field"><label>Counter name *</label><input type="text" name="name" class="rp-input" placeholder="e.g. Counter 1" required></div>
                                <div class="rp-field"><label>Branch *</label>
                                    <select name="branch_id" class="rp-input" required>
                                        @foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="rp-field"><label>Assign staff (cashier)</label>
                                    <select name="staff_id" class="rp-input">
                                        <option value="">— Unassigned —</option>
                                        @foreach ($staff as $s)<option value="{{ $s->id }}">{{ trim($s->f_name . ' ' . $s->l_name) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col rp-field"><label>Thermal printer</label><input type="text" name="thermal" class="rp-input" placeholder="Name / IP"></div>
                                    <div class="col rp-field"><label>Scale port</label><input type="text" name="scale" class="rp-input" placeholder="COM3"></div>
                                </div>
                                <div class="rp-field">
                                    <label class="d-inline"><input type="checkbox" name="drawer" value="1"> Cash drawer</label>
                                </div>
                                <button class="rp-btn p">Add Counter</button>
                            </form>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endif

            @if ($canViewBranches)
            <div class="col-md-{{ $showAddCol ? '8' : '12' }}">
                @php
                    $staffById = $staff->keyBy('id');
                    $byBranch = $counters->groupBy('branch_id');
                    $canAssign = auth('vendor')->check() || hasPermission('pos_counter', 'create');
                    $shiftLabel = function ($s) {
                        $sh = $s->storeShift ?? null;
                        if (!$sh) return '';
                        $t = fn($v) => $v ? substr($v, 0, 5) : '';
                        return ' · ' . trim(($sh->name ? $sh->name . ' ' : '') . $t($sh->start_time) . '–' . $t($sh->end_time));
                    };
                @endphp
                @forelse ($branches as $b)
                    <div class="rp-card">
                        <div class="hd">
                            <span class="accent">🏬 {{ $b->name }}@if ($b->address)<small class="text-muted font-weight-normal"> · {{ $b->address }}</small>@endif</span>
                            @if (hasPermission('pos_branch', 'delete'))
                            <form method="post" action="{{ route('vendor.retail-pos.branches.delete', $b->id) }}"
                                onsubmit="return confirm('Remove this branch? Its counters will be unassigned.');">
                                @csrf
                                <button class="btn btn-sm btn-danger">Remove branch</button>
                            </form>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="rp-table">
                                <thead><tr><th>Counter</th><th>Code</th><th>Assigned staff</th><th>Hardware</th><th></th></tr></thead>
                                <tbody>
                                    @forelse ($byBranch->get($b->id, collect()) as $c)
                                        @php $hw = json_decode($c->hardware, true) ?: []; $st = $staffById->get($c->staff_id); @endphp
                                        <tr>
                                            <td><b>{{ $c->name }}</b></td>
                                            <td class="text-muted">{{ $c->code }}</td>
                                            <td>
                                                @php
                                                    $isAuto = (int) ($c->auto_shift ?? 0) === 1;
                                                    $rosterIds = $rosterMap[$c->id] ?? [];
                                                @endphp
                                                @if ($canAssign)
                                                    @if ($isAuto)
                                                        @if ($st)
                                                            <div class="small text-muted" style="font-weight:600;">🟢 On duty now</div>
                                                            <span class="rp-badge ok">{{ trim($st->f_name . ' ' . $st->l_name) }}</span>
                                                            @if ($st->storeShift)
                                                                <div class="small text-muted mt-1">🕒 {{ trim($shiftLabel($st), ' ·') }}</div>
                                                            @endif
                                                        @else
                                                            <div class="small text-muted" style="font-weight:600;">⚪ Nobody on duty now</div>
                                                            <span class="text-muted small">No rostered staff is on shift at the moment</span>
                                                        @endif
                                                        <div><span class="rp-badge info mt-1">Auto by shift</span></div>
                                                    @else
                                                        <form method="post" action="{{ route('vendor.retail-pos.terminals.staff', $c->id) }}">
                                                            @csrf
                                                            <select name="staff_id" class="rp-input" style="min-width:200px;padding:5px 8px;" onchange="this.form.submit()" title="Change the staff on this counter (shift shown)">
                                                                <option value="">— Unassigned —</option>
                                                                @foreach ($staff as $s)
                                                                    <option value="{{ $s->id }}" {{ $c->staff_id == $s->id ? 'selected' : '' }}>{{ trim($s->f_name . ' ' . $s->l_name) }}{{ $shiftLabel($s) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                        @if ($st && $st->storeShift)
                                                            <div class="small text-muted mt-1">🕒 {{ trim($shiftLabel($st), ' ·') }}</div>
                                                        @endif
                                                    @endif

                                                    <details class="mt-2" {{ $isAuto ? 'open' : '' }}>
                                                        <summary class="small" style="cursor:pointer;color:var(--accent)">⚙ Auto shift setup</summary>
                                                        <form method="post" action="{{ route('vendor.retail-pos.terminals.roster', $c->id) }}" class="mt-1">
                                                            @csrf
                                                            <label class="d-block small mb-1"><input type="checkbox" name="auto_shift" value="1" {{ $isAuto ? 'checked' : '' }}> Auto-assign staff by shift time</label>
                                                            <div class="small text-muted mb-1" style="max-width:240px;">Tick the staff who rotate on this counter. Whoever's shift covers the current time bills here automatically.</div>
                                                            @forelse ($staff as $s)
                                                                <label class="d-block small mb-0"><input type="checkbox" name="roster[]" value="{{ $s->id }}" {{ in_array($s->id, $rosterIds) ? 'checked' : '' }}> {{ trim($s->f_name . ' ' . $s->l_name) }}{{ $shiftLabel($s) }}</label>
                                                            @empty
                                                                <div class="small text-muted">No staff yet.</div>
                                                            @endforelse
                                                            <button class="rp-btn p sm mt-1">Save</button>
                                                        </form>
                                                    </details>
                                                @else
                                                    @if ($st)<span class="rp-badge info">{{ trim($st->f_name . ' ' . $st->l_name) }}</span>@else<span class="text-muted">Unassigned</span>@endif
                                                @endif
                                            </td>
                                            <td class="small text-muted">
                                                @if (!empty($hw['thermal'])) 🖨 {{ $hw['thermal'] }} @endif
                                                @if (!empty($hw['scale'])) · ⚖ {{ $hw['scale'] }} @endif
                                                @if (!empty($hw['drawer'])) · 💵 @endif
                                            </td>
                                            <td class="text-right">
                                                @if (hasPermission('pos_counter', 'delete'))
                                                <form method="post" action="{{ route('vendor.retail-pos.terminals.delete', $c->id) }}" onsubmit="return confirm('Remove counter?');">
                                                    @csrf
                                                    <button class="rp-btn o sm" style="color:#dc3545">Remove</button>
                                                </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5"><div class="rp-empty">No counters in this branch yet.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="rp-card"><div class="rp-empty">No branches yet. Add one to start creating counters.</div></div>
                @endforelse
            </div>
            @endif
        </div>
    </div>
@endsection
