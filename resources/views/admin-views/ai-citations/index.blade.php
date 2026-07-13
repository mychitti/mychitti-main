@extends('layouts.admin.app')

@section('title', 'AI Citation Monitoring')

@section('content')
<div class="content container-fluid"> 
    <div class="page-header">
        <h1 class="page-header-title">AI Citation Monitoring <small class="text-muted">GEO KPI</small></h1>
        <p class="mb-0">Track monthly citations across ChatGPT, Perplexity, Gemini &amp; Claude. Target: 50+/month.</p>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Entries for {{ $period }}</h5>
                    <form method="get" class="d-flex">
                        <input type="month" name="period" value="{{ $period }}" class="form-control form-control-sm mr-2">
                        <button class="btn btn-sm btn-primary">View</button>
                    </form>
                </div>
                <div class="table-responsive"> 
                    <table class="table table-align-middle">
                        <thead class="thead-light">
                            <tr><th>Platform</th><th>Citations</th><th>Referral sessions</th><th>Branded search</th><th></th></tr>
                        </thead>
                        <tbody>
                            @php $totalCit = 0; @endphp
                            @foreach($platforms as $p)
                                @php $r = $rows[$p] ?? null; $totalCit += $r->citations ?? 0; @endphp
                                <tr>
                                    <td class="text-capitalize">{{ $p }}</td>
                                    <td>{{ $r->citations ?? 0 }}</td>
                                    <td>{{ $r->referral_sessions ?? 0 }}</td>
                                    <td>{{ $r->branded_search_volume ?? 0 }}</td>
                                    <td class="text-right">
                                        @if($r)
                                            <form action="{{ route('admin.ai-citations.destroy', $r->id) }}" method="post"
                                                  onsubmit="return confirm('Remove?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-weight-bold">
                                <td>Total</td><td colspan="4">{{ $totalCit }} citations
                                    <span class="badge badge-soft-{{ $totalCit >= 50 ? 'success' : 'warning' }} ml-2">
                                        {{ $totalCit >= 50 ? 'On target' : 'Below 50/mo target' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h5 class="card-title mb-0">Last 12 months</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm table-align-middle">
                        <thead class="thead-light"><tr><th>Month</th><th>Citations</th><th>Referrals</th><th>Branded search</th></tr></thead>
                        <tbody>
                            @forelse($rollup as $m)
                                <tr>
                                    <td>{{ $m->period }}</td>
                                    <td>{{ $m->citations }}</td>
                                    <td>{{ $m->referral_sessions }}</td>
                                    <td>{{ $m->branded_search_volume }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-3">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Record / update</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.ai-citations.save') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Month</label>
                            <input type="month" name="period" value="{{ $period }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Platform</label>
                            <select name="platform" class="form-control" required>
                                @foreach($platforms as $p)
                                    <option value="{{ $p }}" class="text-capitalize">{{ ucfirst($p) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Citations</label>
                            <input type="number" min="0" name="citations" class="form-control" value="0" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">GA4 referral sessions</label>
                            <input type="number" min="0" name="referral_sessions" class="form-control" value="0">
                        </div>
                        <div class="form-group">
                            <label class="input-label">Branded search volume (proxy)</label>
                            <input type="number" min="0" name="branded_search_volume" class="form-control" value="0">
                        </div>
                        <div class="form-group">
                            <label class="input-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <button class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
