@extends('layouts.vendor.app')

@section('title', _isHospital() ? 'My Appointments' : 'Assigned Leads')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        .content.container-fluid { background: #f7f7f7;min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; }
        .lp { padding: 0 4px 28px; font-family: 'Plus Jakarta Sans', sans-serif; }

        /* ── Grid ── */
        .lp-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 1200px) { .lp-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 680px)  { .lp-grid { grid-template-columns: 1fr; } }

        /* ── Lead card ── */
        .lead-card {
            height: 100%;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e4e4e7;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            display: flex;
            flex-direction: column;
            transition: box-shadow .18s, border-color .18s, transform .15s;
            overflow: hidden;
        }
        .lead-card:hover {
            box-shadow: 0 5px 18px rgba(0,0,0,.10);
            border-color: #18181b;
            transform: translateY(-1px);
        }

        .lc-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 7px; flex: 1; }

        /* head */
        .lc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .lc-id { font-size: 10px; font-weight: 700; color: #a1a1aa; letter-spacing: .07em; text-transform: uppercase; margin-bottom: 4px; }
        .lc-name { font-size: 14px; font-weight: 700; color: #18181b; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lc-head-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

        /* status pills */
        .status-pill { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; letter-spacing: .02em; }
        .pill-confirmed    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .pill-completed    { background: #f0fdf4; color: #14532d; border: 1px solid #86efac; }
        .pill-cancelled    { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .pill-accepted     { background: #fefce8; color: #854d0e; border: 1px solid #fde68a; }
        .pill-pending      { background: #fafafa;  color: #52525b;  border: 1px solid #e4e4e7; }

        .lc-divider { height: 1px; background: #f4f4f5; }

        /* client row */
        .lc-client { display: flex; align-items: center; gap: 10px; }
        .lc-avatar {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            background: #18181b; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800;
        }
        .lc-client-name { font-size: 13px; font-weight: 700; color: #18181b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lc-date { font-size: 11px; color: #a1a1aa; display: flex; align-items: center; gap: 5px; }

        /* phone row */
        .lc-phone-actions { display: flex; gap: 6px; }
        .lc-btn-copy, .lc-btn-call {
            display: inline-flex; align-items: center; justify-content: center; gap: 5px;
            padding: 6px 10px; border-radius: 7px; border: 1px solid #e4e4e7;
            background: #fff; font-size: 12px; font-weight: 600; color: #18181b;
            cursor: pointer; font-family: inherit; transition: all .15s; text-decoration: none;
        }
        .lc-btn-copy { flex: 1; overflow: hidden; }
        .lc-btn-copy span.num { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; display: inline-block; }
        .lc-btn-call { flex: 0 0 auto; background: #4ec03f; color: #fff !important; border-color: #4ec03f; }
        .lc-btn-call:hover, .lc-btn-copy:hover { background: #18181b; color: #fff; border-color: #18181b; }

        /* footer */
        .lc-footer { padding: 0 18px 16px; display: flex; flex-wrap: wrap; gap: 7px; }
        .lc-footer .btn-lp {
            flex: 1; min-width: 80px; padding: 8px 10px;
            border-radius: 8px; font-size: 12px; font-weight: 700;
            border: 1px solid #e4e4e7; background: #fafafa; color: #18181b;
            cursor: pointer; font-family: inherit; transition: all .15s;
            text-align: center; text-decoration: none; display: inline-flex;
            align-items: center; justify-content: center; gap: 5px;
        }
        .lc-footer .btn-lp:hover { border-color: #18181b; background: #18181b; color: #fff; text-decoration: none; }
        .lc-footer .btn-lp.accept { background: #18181b; color: #fff; border-color: #18181b; }
        .lc-footer .btn-lp.accept:hover { opacity: .8; }
        .lc-footer .btn-lp.danger  { border-color: #fecaca; color: #991b1b; }
        .lc-footer .btn-lp.danger:hover  { background: #fef2f2; border-color: #991b1b; }

        /* status select in footer */
        .lc-status-select {
            flex: 1; min-width: 120px;
            padding: 7px 10px; border-radius: 8px; border: 1px solid #e4e4e7;
            background: #fafafa; font-size: 12px; font-weight: 600; color: #52525b;
            font-family: inherit; cursor: pointer; outline: none;
        }

        /* ── New / needs-action highlight ── */
        .lp-new-outer { display: flex; flex-direction: column; min-width: 0; }
        .lp-new-tag {
            background: #fff7e6; color: #92680a;
            font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
            padding: 5px 14px; border-radius: 10px 10px 0 0;
            border: 2px solid #f5a623; border-bottom: none;
            display: flex; align-items: center; gap: 6px;
        }
        .lp-new-outer .lead-card {
            border: 2px solid #f5a623 !important;
            border-top: none !important;
            border-radius: 0 0 12px 12px !important;
            height: 100%;
        }
        .lp-pulse {
            width: 8px; height: 8px; border-radius: 50%;
            background: #f5a623; flex-shrink: 0;
            position: relative; display: inline-block;
        }
        .lp-pulse::after {
            content: ''; position: absolute; inset: -3px; border-radius: 50%;
            border: 2px solid #f5a623; animation: lp-pulse 1.6s ease-out infinite; opacity: 0;
        }
        @keyframes lp-pulse {
            0%   { transform: scale(.5); opacity: .9; }
            100% { transform: scale(2);  opacity: 0;  }
        }

        /* refresh btn */
        .btn-refresh {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px; border: 1px solid #e4e4e7;
            background: #fff; font-size: 13px; font-weight: 600; color: #18181b;
            cursor: pointer; font-family: inherit; text-decoration: none; transition: all .15s;
        }
        .btn-refresh:hover { background: #18181b; color: #fff; border-color: #18181b; text-decoration: none; }

        /* empty */
        .empty--data { text-align: center; padding: 60px 20px; }

        /* OTP */
        .otp-input { width: 55px; height: 55px; margin: 3px; text-align: center; font-size: 26px; border: 1px solid #ccc; border-radius: 5px; }
        .otp-input:focus { border-color: #007bff; outline: none; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <i class="tio-filter-list"></i>
                {{ _isHospital() ? 'My Appointments' : 'Assigned Leads' }}
                <span class="badge badge-soft-dark ml-2">{{ count($assignedServices) }}</span>
            </h1>
            <div class="page-header-select-wrapper">
                <a href="{{ route('vendor.service.assigned_services') }}" class="btn-refresh">
                    <i class="tio-refresh"></i> Refresh
                </a>
            </div>
        </div>

        <div class="lp">
            @if (count($assignedServices))
                <div class="lp-grid">
                    @foreach ($assignedServices as $conf)
                        @php
                            $initials    = strtoupper(substr($conf->f_name, 0, 1) . substr($conf->l_name, 0, 1));
                            $fmtDate     = \Carbon\Carbon::parse($conf->assigned_at)->format('j M, g:i a');
                            $statusLabel = $conf->current_status ?: ($conf->accepted_by_staff ? 'Accepted' : 'Pending');
                            $pillClass   = match(true) {
                                str_contains($statusLabel, 'Cancel') => 'pill-cancelled',
                                $statusLabel === 'Completed'         => 'pill-completed',
                                $statusLabel === 'Confirmed'         => 'pill-confirmed',
                                $conf->accepted_by_staff == 1        => 'pill-accepted',
                                default                              => 'pill-pending',
                            };
                        @endphp

                        @if (!$conf->accepted_by_staff)
                        <div class="lp-new-outer" style="height:100%;">
                            <div class="lp-new-tag">
                                <span class="lp-pulse"></span> Needs Acceptance
                            </div>
                        @else
                        <div style="height:100%;display:flex;flex-direction:column;">
                        @endif
                            <div class="lead-card" style="flex:1;{{ hasPermission('leads_manage', 'view') ? 'cursor:pointer;' : '' }}"
                                @if (hasPermission('leads_manage', 'view')) data-href="{{ route('vendor.service.lead-details', [$conf->service_id]) }}" @endif>
                                <div class="lc-body">
                                    {{-- Head --}}
                                    <div class="lc-head">
                                        <div style="flex:1;min-width:0;">
                                            <div class="lc-id">#{{ $conf->service_id }}</div>
                                            <div class="lc-name" title="{{ $conf->item_name }}">{{ $conf->item_name }}</div>
                                        </div>
                                        <div class="lc-head-right">
                                            <span class="status-pill {{ $pillClass }}">{{ $statusLabel }}</span>
                                        </div>
                                    </div>

                                    <div class="lc-divider"></div>

                                    {{-- Client --}}
                                    <div class="lc-client">
                                        <div class="lc-avatar">{{ $initials }}</div>
                                        <div style="flex:1;min-width:0;">
                                            <div class="lc-client-name">{{ $conf->f_name . ' ' . $conf->l_name }}</div>
                                            <div class="lc-date"><i class="tio-time"></i> {{ $fmtDate }}</div>
                                        </div>
                                    </div>

                                    {{-- Phone --}}
                                    @if ($conf->phone)
                                        <div class="lc-phone-actions">
                                            <button class="lc-btn-copy" onclick="navigator.clipboard.writeText('{{ trim($conf->phone) }}'); toastr.success('Copied!')">
                                                <span class="num">{{ $conf->phone }}</span>
                                                <i class="tio-copy"></i>
                                            </button>
                                            <a href="tel:{{ trim($conf->phone) }}" class="lc-btn-call">
                                                <i class="tio-call"></i> Call
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Footer actions --}}
                                <div class="lc-footer">
                                    @if ($conf->accepted_by_staff == 1)
                                        @if ($conf->current_status === 'Completed')
                                            <span class="status-pill pill-completed" style="padding:7px 14px;font-size:.8rem;">Completed</span>
                                        @elseif(str_contains($conf->current_status ?? '', 'Cancel'))
                                            <span class="status-pill pill-cancelled" style="padding:7px 14px;font-size:.8rem;">Cancelled</span>
                                        @else
                                            @if (hasPermission('leads_manage', 'status_change'))
                                                @php $jobInfo = _getWhere('vendor_emp_jobs', ['service_id' => $conf->service_id])[0] ?? null; @endphp
                                                <select class="lc-status-select js-select2-custom"
                                                    onchange="changeStatus(this.value, {{ $conf->service_id }})"
                                                    title="Change Status">
                                                    <option value="">Status...</option>
                                                    @foreach ($default_statuses as $st)
                                                        <option value="{{ $st->id }}" {{ $jobInfo && $jobInfo->status == $st->id ? 'selected' : '' }}>{{ $st->status }}</option>
                                                    @endforeach
                                                    @foreach ($statuses as $st)
                                                        <option value="{{ $st->id }}" {{ $jobInfo && $jobInfo->status == $st->id ? 'selected' : '' }}>{{ $st->status }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        @endif

                                        @if (hasPermission('leads_manage', 'edit'))
                                            <a href="{{ route('vendor.service.lead-details', [$conf->service_id]) }}" class="btn-lp">
                                                <i class="tio-visible-outlined"></i> View
                                            </a>
                                        @endif
                                        @if (hasAnyModulePermission(['leads_gatepass']))
                                            <a href="{{ route('vendor.service.gatepass-details', [$conf->service_id]) }}" class="btn-lp">
                                                <i class="tio-document-outlined"></i> Gatepass
                                            </a>
                                        @endif
                                        @if (hasAnyModulePermission(['leads_quotation']))
                                            <a href="{{ route('vendor.service.quotations', [$conf->service_id]) }}" class="btn-lp">
                                                <i class="tio-document-text-outlined"></i> Quotation
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('vendor.service.task', [$conf->service_id, 'accept', $conf->id]) }}" class="btn-lp accept">
                                            <i class="tio-checkmark-circle-outlined"></i> Accept
                                        </a>
                                        <a href="{{ route('vendor.service.task', [$conf->service_id, 'reject', $conf->id]) }}" class="btn-lp danger">
                                            <i class="tio-clear-circle-outlined"></i> Reject
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Hidden OTP trigger + modal (unchanged) --}}
                            <button type="button" style="display:none;"
                                class="btn btn-primary start_job_btn start btn_{{ $conf->service_id }}"
                                data-value="{{ $conf->id }}" data-toggle="modal"
                                data-target="#jobModal_{{ $conf->id }}">Start Job</button>

                            <div class="modal fade" id="jobModal_{{ $conf->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="container py-5">
                                                <h2 class="text-center">Enter OTP</h2>
                                                <div class="p-5 bg-light rounded" style="max-width:550px;margin:0 auto;">
                                                    <form class="otpForm" action="{{ route('vendor.lead.job-otp-verify2') }}" method="post">
                                                        <p>OTP has been sent to customer. Please verify.</p>
                                                        <div class="otp-error alert alert-danger py-2" style="display:none;"></div>
                                                        @csrf
                                                        <input type="hidden" name="status" id="sttas_{{ $conf->service_id }}" value="">
                                                        <input type="hidden" name="acc_id" value="{{ $conf->id }}">
                                                        <input type="hidden" name="phone" value="{{ $conf->phone }}">
                                                        <input type="hidden" name="action" value="">
                                                        <div class="d-flex justify-content-center w-100">
                                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                                        </div>
                                                        <input type="hidden" value="1" name="ajax" />
                                                        <button type="submit" class="btn btn-lg btn-block btn--primary mt-3">Submit</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="" style="width:120px;opacity:.6;margin-bottom:16px;">
                    <h5>{{ translate('no_data_found') }}</h5>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        function changeStatus(status, service_id) {
            if (status == 12 || status == 14) {
                $(".btn_" + service_id).click();
                $('#sttas_' + service_id).val(status);
                sendOtp(status, service_id);
            } else {
                Swal.fire({
                    title: 'Are you sure?', text: "Do you want to change status?", type: 'warning',
                    showCancelButton: true, cancelButtonColor: 'default', confirmButtonColor: '#FC6A57',
                    cancelButtonText: 'No', confirmButtonText: 'Yes', reverseButtons: true
                }).then((result) => { if (result.value) updateStatus(status, service_id); });
            }
        }

        function sendOtp(status, service_id) {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.post({ url: "{{ route('vendor.lead.change-job-status') }}", data: { service_id, status } });
        }

        function verifyOtp() {}

        function updateStatus(status, service_id) {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.post({
                url: '{{ route('vendor.service.change-status') }}',
                data: { status, service_id },
                beforeSend: () => $('#loading').show(),
                success: (data) => data.status ? toastr.success(data.message) : toastr.error(data.message),
                error: (xhr) => {
                    const msg = xhr.responseJSON?.message || 'Access denied';
                    toastr.error(msg);
                },
                complete: () => $('#loading').hide()
            });
        }

        $(".otpForm").on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $errBox = $form.find('.otp-error');
            $errBox.hide().text('');
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.post({
                url: $form.attr('action'), data: $form.serialize(),
                success: (data) => {
                    if (data.status) {
                        toastr.success(data.message || 'Status updated.');
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        $errBox.text(data.message || 'Something went wrong.').show();
                    }
                },
                error: (xhr) => {
                    const msg = xhr.responseJSON?.message || 'Request failed.';
                    $errBox.text(msg).show();
                },
                complete: () => $('#loading').hide()
            });
        });

        $(document).on('input', '.otp-input', function () {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);
            if (this.value.length === this.maxLength && index < $inputs.length - 1) $inputs.eq(index + 1).focus();
        });

        $(document).on('click', '.lead-card[data-href]', function (e) {
            if ($(e.target).closest('a, button, select, input, .select2-container, .lc-status-select').length) return;
            window.location.href = $(this).data('href');
        });

        $(document).ready(function () {
            $('.js-select2-custom').each(function () { $.HSCore.components.HSSelect2.init($(this)); });
        });
    </script>
@endpush
