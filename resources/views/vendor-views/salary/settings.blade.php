@extends('layouts.vendor.app')

@section('title', 'Salary Statutory Settings')

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between">
            <h1 class="page-header-title">Salary Statutory Settings</h1>
            <a href="{{ route('vendor.salary.list') }}" class="btn btn-outline-secondary btn-sm">← Back to Salaries</a>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="text-muted" style="font-size:13px;">
                    Enable the statutory deductions that apply to your staff. They're <b>off by default</b> and only
                    affect salaries generated <b>after</b> you save. Each appears as a line on the pay slip.
                </p>
                <form action="{{ route('vendor.salary.settings.save') }}" method="POST">
                    @csrf

                    {{-- EPF --}}
                    <div class="p-3 border rounded mb-3" style="max-width:640px;">
                        <label class="d-flex align-items-center mb-2" style="gap:8px;font-weight:600;">
                            <input type="checkbox" name="stat_epf" value="1" {{ $storeConfig->stat_epf ? 'checked' : '' }}>
                            EPF — Provident Fund
                        </label>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <span class="small text-muted">Employee share</span>
                            <input type="number" step="0.01" min="0" name="stat_epf_percent" class="form-control"
                                style="max-width:120px;" value="{{ $storeConfig->stat_epf_percent ?? 12 }}"> <span class="small">% of basic (capped at ₹15,000 basic)</span>
                        </div>
                    </div>

                    {{-- ESI --}}
                    <div class="p-3 border rounded mb-3" style="max-width:640px;">
                        <label class="d-flex align-items-center mb-2" style="gap:8px;font-weight:600;">
                            <input type="checkbox" name="stat_esi" value="1" {{ $storeConfig->stat_esi ? 'checked' : '' }}>
                            ESI — Employees' State Insurance
                        </label>
                        <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                            <span class="small text-muted">Employee share</span>
                            <input type="number" step="0.01" min="0" name="stat_esi_percent" class="form-control"
                                style="max-width:120px;" value="{{ $storeConfig->stat_esi_percent ?? 0.75 }}"> <span class="small">% of gross, only if gross ≤</span>
                            <input type="number" step="1" min="0" name="stat_esi_wage_limit" class="form-control"
                                style="max-width:130px;" value="{{ $storeConfig->stat_esi_wage_limit ?? 21000 }}">
                        </div>
                    </div>

                    {{-- Professional Tax --}}
                    <div class="p-3 border rounded mb-3" style="max-width:640px;">
                        <label class="d-flex align-items-center mb-2" style="gap:8px;font-weight:600;">
                            <input type="checkbox" name="stat_pt" value="1" {{ $storeConfig->stat_pt ? 'checked' : '' }}>
                            Professional Tax
                        </label>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <span class="small text-muted">Flat amount</span>
                            <input type="number" step="0.01" min="0" name="stat_pt_amount" class="form-control"
                                style="max-width:140px;" value="{{ $storeConfig->stat_pt_amount ?? 200 }}"> <span class="small">₹ / month</span>
                        </div>
                    </div>

                    {{-- TDS --}}
                    <div class="p-3 border rounded mb-3" style="max-width:640px;">
                        <label class="d-flex align-items-center mb-2" style="gap:8px;font-weight:600;">
                            <input type="checkbox" name="stat_tds" value="1" {{ $storeConfig->stat_tds ? 'checked' : '' }}>
                            TDS — Income Tax
                        </label>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <input type="number" step="0.01" min="0" name="stat_tds_percent" class="form-control"
                                style="max-width:120px;" value="{{ $storeConfig->stat_tds_percent ?? 0 }}"> <span class="small">% of gross</span>
                        </div>
                    </div>

                    <div class="text-right" style="max-width:640px;">
                        <button type="submit" class="btn btn--primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
