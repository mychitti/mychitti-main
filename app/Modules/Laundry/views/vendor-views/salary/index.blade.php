@extends('layouts.vendor.app')

@section('title', 'Salary')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .al-hero {
            background: linear-gradient(100deg, #1d4ed8 0%, #2563eb 45%, #4f46e5 100%);
            border-radius: 14px; color: #fff; padding: 22px 26px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, .22); margin-bottom: 18px;
        }
        .al-hero h1 { color: #fff; font-weight: 700; font-size: 22px; margin: 0; }
        .al-hero .al-sub { color: rgba(255, 255, 255, .82); font-size: 13px; margin-top: 3px; }
        .al-chip {
            background: rgba(255, 255, 255, .15); border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 10px; padding: 8px 14px; text-align: center; min-width: 92px;
        }
        .al-chip .n { font-size: 18px; font-weight: 700; line-height: 1.1; }
        .al-chip .l { font-size: 11px; opacity: .85; text-transform: uppercase; letter-spacing: .4px; }
        .al-hero .form-control { height: calc(1.5em + .75rem + 2px); }
        .al-card { border: 1px solid #eef0f4; border-top: none; border-radius: 0 0 12px 12px; }
        .al-empty { text-align: center; padding: 44px 16px; color: #9aa1ab; }
        .al-empty img { width: 92px; opacity: .8; margin-bottom: 10px; }
    </style>
@endpush

@section('content')
    @php
        $selectedMonth = $month_year ?? date('Y-m');
        $currentMonth  = date('Y-m');
        $generated     = isset($salary[0]) && $salary[0]->generated_at;
        $totalPayable  = $salary->sum(fn($s) => $s->total_payable ?? 0);
        $paidCount     = $salary->where('pay_status', 'paid')->count();
        $unpaidCount   = $salary->filter(fn($s) => $s->id && $s->pay_status !== 'paid')->count();
    @endphp

    <div class="content container-fluid">

        @include('vendor-views.partials._hr_header')

        <div class="card al-card mb-0">
            @if (hasPermission('salary_manage', 'list'))
                <div class="card-body p-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between px-3 py-2 border-bottom" style="gap:8px;">
                        <div class="d-flex align-items-center flex-wrap" style="gap:12px;">
                            <h5 class="card-title mb-0">Payroll — {{ date('F Y', strtotime($selectedMonth)) }}</h5>
                            <form action="" class="mb-0">
                                <input onchange="this.form.submit()" type="month" value="{{ $selectedMonth }}"
                                    name="month" class="form-control form-control-sm" style="min-width:150px;">
                            </form>
                            <span class="text-muted small">
                                Payable <strong>{{ _price($totalPayable) }}</strong> ·
                                Paid <strong>{{ $paidCount }}</strong> ·
                                Unpaid <strong>{{ $unpaidCount }}</strong>
                            </span>
                        </div>
                        <div class="d-flex align-items-center" style="gap:6px;">
                            @if (hasPermission('salary_manage', 'edit'))
                                <a href="{{ route('vendor.salary.settings') }}" class="btn btn-sm btn-outline-info"
                                    title="EPF / ESI / Professional Tax / TDS"><i class="tio-settings mr-1"></i> Statutory</a>
                            @endif
                            @if ($generated)
                                @if (hasPermission('salary_manage', 'generate'))
                                    <a href="{{ route('vendor.salary.generate-monthly', [$selectedMonth]) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="return confirm('Regenerate salaries for this month? Unpaid entries will be recalculated.')">
                                        <i class="tio-refresh mr-1"></i> Regenerate
                                    </a>
                                @endif
                                @if (hasPermission('salary_manage', 'mark_paid'))
                                    <a href="{{ route('vendor.salary.mark-paid', [$selectedMonth]) }}"
                                        class="btn btn-sm btn-outline-warning"
                                        onclick="return confirm('Mark all unpaid salaries as paid for this month?')">
                                        <i class="tio-checkmark-circle mr-1"></i> Mark All Paid
                                    </a>
                                @endif
                                @if (hasPermission('salary_manage', 'export'))
                                    <a href="{{ route('vendor.salary.export-salaries', [$selectedMonth]) }}"
                                        class="btn btn-sm btn-outline-success"><i class="tio-download-to mr-1"></i> Export</a>
                                @endif
                            @elseif (hasPermission('salary_manage', 'generate'))
                                <a href="{{ route('vendor.salary.generate-monthly', [$selectedMonth]) }}"
                                    class="btn btn-sm btn--primary"><i class="tio-add mr-1"></i> Generate Salaries</a>
                            @endif
                        </div>
                    </div>

                    @unless ($generated)
                        <div class="alert alert-soft-info mb-0 rounded-0 border-0" style="font-size:13px;">
                            <i class="tio-info-outined mr-1"></i>
                            Salaries for {{ date('F Y', strtotime($selectedMonth)) }} haven't been generated yet —
                            the figures below are a <strong>preview</strong>.
                            @if ($selectedMonth === $currentMonth)
                                This month is still in progress, so amounts are calculated <strong>till today</strong>
                                ({{ date('d M') }}), not the full month.
                            @endif
                            Click <strong>Generate Salaries</strong> to create payroll from attendance &amp; base pay.
                        </div>
                    @endunless

                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('sl') }}</th>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Base</th>
                                    <th>Payable</th>
                                    <th>Bonus</th>
                                    <th>Allowance</th>
                                    <th>Deductions</th>
                                    <th>Advance Ded.</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-center">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salary as $key => $lead)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ hasPermission('staff_manage', 'view') ? route('vendor.employee.view', [$lead->ven_id]) : '#' }}"
                                                class="table-rest-info">
                                                {{ trim($lead->f_name . ' ' . $lead->l_name) }}
                                            </a>
                                        </td>
                                        <td><span class="badge badge-soft-secondary">{{ $lead->salary_type }}</span></td>
                                        <td>{{ $lead->salary_type == 'Task-Wise' ? '—' : _price($lead->base_salary ?? $lead->emp_base_salary ?? 0) }}</td>
                                        <td>{{ _price($lead->payable_salary ?? 0) }}</td>
                                        <td>{{ _price($lead->bonus_incentives ?? 0) }}</td>
                                        <td>{{ _price($lead->allowance_amount ?? 0) }}</td>
                                        <td>{{ _price($lead->deductions ?? 0) }}</td>
                                        <td>{{ _price($lead->advance_payment_deductions ?? 0) }}</td>
                                        <td><strong>{{ _price($lead->total_payable ?? 0) }}</strong></td>
                                        <td>
                                            @if ($lead->pay_status == 'paid')
                                                <span class="badge badge-soft-success">Paid</span>
                                            @elseif ($lead->id)
                                                <span class="badge badge-soft-danger">Unpaid</span>
                                            @elseif (!empty($lead->is_preview))
                                                <span class="badge badge-soft-info" title="Estimated — not generated yet">Preview</span>
                                            @else
                                                <span class="badge badge-soft-secondary">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="{{ route('vendor.salary.edit', [$lead->ven_id]) }}?month={{ $selectedMonth }}"
                                                    title="Edit Salary"><i class="tio-edit"></i></a>
                                                @if ($lead->id)
                                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                                        href="{{ route('vendor.salary.payslip', [$lead->id]) }}" target="_blank"
                                                        title="Pay Slip"><i class="tio-receipt"></i></a>
                                                @endif
                                                @if (hasPermission('salary_manage', 'mark_paid') && $lead->id && $lead->pay_status != 'paid')
                                                    <a data-toggle="modal" data-target="#markPaidModal{{ $key }}"
                                                        style="min-width:fit-content; padding:0 5px;"
                                                        class="btn action-btn btn--primary btn-outline-primary"
                                                        title="Pay Salary">Mark Paid</a>
                                                    <div class="modal fade" id="markPaidModal{{ $key }}" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Pay {{ trim($lead->f_name . ' ' . $lead->l_name) }} — {{ _price($lead->total_payable ?? 0) }}</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form enctype="multipart/form-data" action="{{ route('vendor.salary.pay') }}" method="post">
                                                                        @csrf
                                                                        <input type="hidden" value="{{ $lead->id }}" name="salary_id">
                                                                        <label>Payment Receipt / Document <span class="text-muted">(optional)</span></label>
                                                                        <input type="file" name="file" class="form-control mb-3">
                                                                        <div class="d-flex justify-content-end">
                                                                            <button class="btn btn--primary"><i class="tio-checkmark-circle mr-1"></i> Confirm Payment</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif (hasPermission('salary_manage', 'view_document') && $lead->pay_reciept)
                                                    <a target="_blank" style="min-width:fit-content; padding:0 5px;"
                                                        class="btn btn-outline-warning action-btn"
                                                        href="{{ asset('storage/app/public/vendor/documents/') . '/' . $lead->pay_reciept }}">Receipt</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="12">
                                        <div class="al-empty">
                                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                                            <h5>{{ translate('no_data_found') }}</h5>
                                        </div>
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
