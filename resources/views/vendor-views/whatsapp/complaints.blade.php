@extends('layouts.vendor.app')

@section('title', translate('Feedback & Complaints'))

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
@endpush

@section('content')
    <div class="content container-fluid wa-page">
        <div class="page-header">
            <h1 class="page-header-title mb-0">
                <i class="tio-sentiment-dissatisfied"></i> {{ translate('Feedback & Complaints') }}
            </h1>
            <span class="wa-sub">
                {{ translate('What patients said when they told us their visit did not go well.') }}
            </span>
        </div>

        @php
            $good = (int) ($ratings['good'] ?? 0);
            $okay = (int) ($ratings['okay'] ?? 0);
            $bad  = (int) ($ratings['bad'] ?? 0);
            $all  = $good + $okay + $bad;
        @endphp

        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="wa-card h-100"><div class="wa-card-b">
                    <div class="wa-eyebrow">{{ translate('Answered') }}</div>
                    <h4 class="mb-0">{{ $all }}</h4>
                </div></div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="wa-card h-100"><div class="wa-card-b">
                    <div class="wa-eyebrow">{{ translate('Very good') }}</div>
                    <h4 class="mb-0 text-success">{{ $good }}</h4>
                </div></div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="wa-card h-100"><div class="wa-card-b">
                    <div class="wa-eyebrow">{{ translate('Okay') }}</div>
                    <h4 class="mb-0">{{ $okay }}</h4>
                </div></div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="wa-card h-100"><div class="wa-card-b">
                    <div class="wa-eyebrow">{{ translate('Not good') }}</div>
                    <h4 class="mb-0 text-danger">{{ $bad }}</h4>
                </div></div>
            </div>
        </div>

        @if (hasPermission('whatsapp_complaints', 'list'))
        <div class="wa-card">
            <div class="wa-card-h">
                <ul class="nav wa-tabs" style="border:0;">
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'open' ? 'active' : '' }}"
                           href="{{ route('vendor.whatsapp.complaints', ['status' => 'open']) }}">
                            {{ translate('Open') }} <span class="badge badge-soft-danger ml-1">{{ $counts['open'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'resolved' ? 'active' : '' }}"
                           href="{{ route('vendor.whatsapp.complaints', ['status' => 'resolved']) }}">
                            {{ translate('Resolved') }} <span class="badge badge-soft-secondary ml-1">{{ $counts['resolved'] }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            @if ($complaints->isEmpty())
                <div class="wa-empty">
                    <i class="tio-sentiment-very-satisfied"></i>
                    <div class="wa-empty-t">{{ $status === 'open' ? translate('Nothing open') : translate('Nothing here yet') }}</div>
                    <div class="wa-empty-s">
                        {{ translate('Complaints appear here when a patient answers "Not good" to a feedback request and tells us why.') }}
                    </div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table wa-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('When') }}</th>
                                <th>{{ translate('Patient') }}</th>
                                <th>{{ translate('What they said') }}</th>
                                <th class="text-right">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($complaints as $c)
                                <tr>
                                    <td class="text-nowrap">
                                        {{ \Carbon\Carbon::parse($c->created_at)->format('d M Y') }}
                                        <div class="wa-sub">{{ \Carbon\Carbon::parse($c->created_at)->format('h:i A') }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="font-weight-bold">{{ $c->name ?: translate('Patient') }}</span>
                                        <div class="wa-sub">{{ $c->phone }}</div>
                                    </td>
                                    <td style="max-width:520px; white-space:normal;">{{ $c->issue }}</td>
                                    <td class="text-right text-nowrap">
                                        @if ($c->status === 'open' && hasPermission('whatsapp_complaints', 'status_change'))
                                            <form method="post" class="d-inline"
                                                  action="{{ route('vendor.whatsapp.complaints.resolve', $c->id) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    {{ translate('Mark resolved') }}
                                                </button>
                                            </form>
                                        @elseif ($c->status === 'open')
                                            <span class="wa-chip badge-soft-warning">{{ translate('Open') }}</span>
                                        @else
                                            <span class="wa-chip badge-soft-success">{{ translate('Resolved') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2">{!! $complaints->links() !!}</div>
            @endif
        </div>
        @else
            {{-- Reachable on `status_change` alone, which acts on rows this role cannot be shown. --}}
            <div class="wa-card">
                <div class="wa-card-b">
                    <div class="wa-empty">
                        <i class="tio-lock-outlined"></i>
                        <div class="wa-empty-t">{{ translate('Nothing to show here') }}</div>
                        <div class="wa-empty-s">{{ translate('Your role cannot view the complaints list.') }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
