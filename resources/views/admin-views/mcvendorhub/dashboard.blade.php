@extends('layouts.admin.app')

@section('title', translate('MC Vendorhub Dashboard'))

@push('css_or_js')
    <style>
        .mcv-wrap {
            --mcv-ink: #1e2022;
            --mcv-muted: #677788;
            --mcv-line: #e7eaf3;
            --mcv-surface: #fff;
        }

        .mcv-page-head {
            display: flex;
            align-items: flex-start;
            gap: .875rem;
            margin-bottom: 1.25rem;
        }

        .mcv-page-icon {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            border-radius: .625rem;
            background: #eef2f7;
            color: var(--mcv-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .mcv-page-head h1 {
            font-size: 1.375rem;
            font-weight: 600;
            color: var(--mcv-ink);
            margin: 0 0 .1875rem;
        }

        .mcv-page-head p {
            color: var(--mcv-muted);
            font-size: .875rem;
            margin: 0;
            max-width: 62ch;
        }

        /* ---- stat strip: one panel, dividers, not 7 loose boxes ---- */
        .mcv-stats {
            display: flex;
            flex-wrap: wrap;
            background: var(--mcv-surface);
            border: 1px solid var(--mcv-line);
            border-radius: .5rem;
            box-shadow: 0 1px 2px rgba(19, 24, 44, .04);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .mcv-stat {
            flex: 1 1 150px;
            padding: .875rem 1.125rem;
            border-right: 1px solid var(--mcv-line);
            border-bottom: 1px solid var(--mcv-line);
        }

        .mcv-stat:last-child {
            border-right: 0;
        }

        .mcv-stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1.1;
            color: var(--mcv-ink);
            letter-spacing: -.01em;
        }

        .mcv-stat-label {
            margin-top: .1875rem;
            color: var(--mcv-muted);
            font-size: .6875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ---- cards ---- */
        .mcv-card {
            background: var(--mcv-surface);
            border: 1px solid var(--mcv-line);
            border-radius: .5rem;
            box-shadow: 0 1px 2px rgba(19, 24, 44, .04);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }

        .mcv-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .875rem 1.125rem;
            border-bottom: 1px solid var(--mcv-line);
        }

        .mcv-card-head h2 {
            font-size: .9375rem;
            font-weight: 600;
            color: var(--mcv-ink);
            margin: 0;
        }

        .mcv-link-all {
            font-size: .8125rem;
            font-weight: 500;
            color: var(--mcv-muted);
            white-space: nowrap;
        }

        .mcv-link-all:hover {
            color: var(--mcv-ink);
            text-decoration: none;
        }

        .mcv-table {
            width: 100%;
            margin: 0;
            font-size: .875rem;
        }

        .mcv-table th {
            padding: .5rem 1.125rem;
            font-size: .6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--mcv-muted);
            background: #fafbfc;
            border-bottom: 1px solid var(--mcv-line);
            white-space: nowrap;
        }

        .mcv-table td {
            padding: .6875rem 1.125rem;
            border-bottom: 1px solid #f1f3f7;
            color: var(--mcv-ink);
            vertical-align: middle;
        }

        .mcv-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .mcv-table tbody tr:hover td {
            background: #fafbfc;
        }

        /* the theme paints bare links red; keep table links as ink */
        .mcv-table a.mcv-name {
            color: var(--mcv-ink);
            font-weight: 500;
        }

        .mcv-table a.mcv-name:hover {
            color: var(--mcv-ink);
            text-decoration: underline;
        }

        .mcv-sub {
            display: block;
            font-size: .75rem;
            color: var(--mcv-muted);
            margin-top: .0625rem;
        }

        .mcv-status {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            font-size: .75rem;
            font-weight: 500;
            color: var(--mcv-ink);
            white-space: nowrap;
        }

        .mcv-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex: 0 0 auto;
        }

        .mcv-dot--new {
            background: #ec9a3c;
        }

        .mcv-dot--seen {
            background: #b9c4d4;
        }

        .mcv-empty {
            padding: 2.5rem 1.125rem;
            text-align: center;
            color: var(--mcv-muted);
            font-size: .875rem;
        }

        .mcv-empty i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: .5rem;
            color: #c6cfdb;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid mcv-wrap">

        <div class="mcv-page-head">
            <span class="mcv-page-icon"><i class="tio-shop"></i></span>
            <div>
                <h1>{{ translate('MC Vendorhub') }}</h1>
                <p>{{ translate('Vendors who use the platform as software only. They have opted out of the MyChitti customer marketplace.') }}
                </p>
            </div>
        </div>

        <div class="mcv-stats">
            <div class="mcv-stat">
                <div class="mcv-stat-value">{{ $data['total_vendors'] }}</div>
                <div class="mcv-stat-label" title="{{ translate('Total Vendors') }}">{{ translate('Total Vendors') }}</div>
            </div>
            <div class="mcv-stat">
                <div class="mcv-stat-value">{{ $data['active_vendors'] }}</div>
                <div class="mcv-stat-label" title="{{ translate('Active') }}">{{ translate('Active') }}</div>
            </div>
            <div class="mcv-stat">
                <div class="mcv-stat-value">{{ $data['pending_vendors'] }}</div>
                <div class="mcv-stat-label" title="{{ translate('Pending Approval') }}">{{ translate('Pending Approval') }}
                </div>
            </div>
            <div class="mcv-stat">
                <div class="mcv-stat-value">{{ $data['new_this_month'] }}</div>
                <div class="mcv-stat-label" title="{{ translate('New This Month') }}">{{ translate('New This Month') }}
                </div>
            </div>
            <div class="mcv-stat">
                <div class="mcv-stat-value">{{ $data['active_subscriptions'] }}</div>
                <div class="mcv-stat-label" title="{{ translate('Active Subscriptions') }}">{{ translate('Active Subs') }}
                </div>
            </div>
            <div class="mcv-stat">
                <div class="mcv-stat-value">{{ $data['expired_subscriptions'] }}</div>
                <div class="mcv-stat-label" title="{{ translate('Expired Subscriptions') }}">{{ translate('Expired Subs') }}
                </div>
            </div>
            <div class="mcv-stat">
                <div class="mcv-stat-value">{{ $data['open_enquiries'] }}</div>
                <div class="mcv-stat-label" title="{{ translate('Unread Enquiries') }}">{{ translate('Unread Enquiries') }}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="mcv-card">
                    <div class="mcv-card-head">
                        <h2>{{ translate('Latest Vendors') }}</h2>
                        <a href="{{ route('admin.mcvendorhub.vendors') }}"
                            class="mcv-link-all">{{ translate('View all') }} &rsaquo;</a>
                    </div>
                    @if (count($recent_vendors))
                        <div class="table-responsive">
                            <table class="mcv-table">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Store') }}</th>
                                        <th>{{ translate('Owner') }}</th>
                                        <th>{{ translate('Joined') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recent_vendors as $vendor)
                                        <tr>
                                            <td>
                                                <a class="mcv-name"
                                                    href="{{ route('admin.store.view', $vendor->id) }}">{{ $vendor->name }}</a>
                                                <span class="mcv-sub">{{ $vendor->module?->module_name ?? '—' }}</span>
                                            </td>
                                            <td>{{ trim(($vendor->vendor?->f_name ?? '') . ' ' . ($vendor->vendor?->l_name ?? '')) ?: '—' }}
                                            </td>
                                            <td>{{ $vendor->created_at?->format('d M Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mcv-empty">
                            <i class="tio-shop"></i>
                            {{ translate('No vendors have opted out of MyChitti yet.') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mcv-card">
                    <div class="mcv-card-head">
                        <h2>{{ translate('Expiring in 30 Days') }}</h2>
                        <a href="{{ route('admin.mcvendorhub.subscriptions') }}"
                            class="mcv-link-all">{{ translate('View all') }} &rsaquo;</a>
                    </div>
                    @if (count($expiring_soon))
                        <div class="table-responsive">
                            <table class="mcv-table">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Store') }}</th>
                                        <th>{{ translate('Plan') }}</th>
                                        <th>{{ translate('Expires') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expiring_soon as $row)
                                        @php($expiry = \Carbon\Carbon::parse($row->plan_expiry))
                                        <tr>
                                            <td>
                                                <a class="mcv-name"
                                                    href="{{ route('admin.store.view', $row->store_id) }}">{{ $row->store_name }}</a>
                                            </td>
                                            <td>{{ $row->plan_name ?? '—' }}</td>
                                            <td>
                                                {{ $expiry->format('d M Y') }}
                                                <span
                                                    class="mcv-sub">{{ translate('in') }}
                                                    {{ max(0, (int) now()->startOfDay()->diffInDays($expiry->startOfDay(), false)) }}
                                                    {{ translate('days') }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mcv-empty">
                            <i class="tio-calendar-note"></i>
                            {{ translate('Nothing expiring in the next 30 days.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mcv-card">
            <div class="mcv-card-head">
                <h2>{{ translate('Latest Enquiries') }}</h2>
                <a href="{{ route('admin.mcvendorhub.enquiries') }}"
                    class="mcv-link-all">{{ translate('View all') }} &rsaquo;</a>
            </div>
            @if (count($recent_enquiries))
                <div class="table-responsive">
                    <table class="mcv-table">
                        <thead>
                            <tr>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Business') }}</th>
                                <th>{{ translate('Subject') }}</th>
                                <th>{{ translate('Received') }}</th>
                                <th>{{ translate('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent_enquiries as $contact)
                                <tr>
                                    <td>
                                        <a class="mcv-name"
                                            href="{{ route('admin.mcvendorhub.enquiries.view', $contact->id) }}">{{ $contact->name ?: '—' }}</a>
                                    </td>
                                    <td>{{ $contact->business_name ?: '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($contact->subject, 55) ?: '—' }}</td>
                                    <td>{{ $contact->created_at?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        <span class="mcv-status">
                                            <span
                                                class="mcv-dot {{ $contact->seen ? 'mcv-dot--seen' : 'mcv-dot--new' }}"></span>
                                            {{ $contact->seen ? translate('Seen') : translate('New') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mcv-empty">
                    <i class="tio-comment-text-outlined"></i>
                    {{ translate('No MC Vendorhub enquiries yet.') }}
                </div>
            @endif
        </div>
    </div>
@endsection
