@extends('layouts.admin.app')

@section('title', translate('MC Vendorhub Support'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-support"></i></span>
                <span>{{ translate('MC Vendorhub Support') }}
                    <span class="badge badge-soft-dark ml-2">{{ $tickets->total() }}</span>
                </span>
            </h1>
            <p class="mb-0 text-muted">
                {{ translate('Support tickets raised by vendors who are not listed on MyChitti.') }}
            </p>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <form class="row g-2 w-100 align-items-end">
                    <div class="col-md-6">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search ticket ID or subject') }}">
                            @if ($status)
                                <input type="hidden" name="status" value="{{ $status }}">
                            @endif
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All Statuses') }}</option>
                            <option value="open" {{ $status === 'open' ? 'selected' : '' }}>{{ translate('Open') }}</option>
                            <option value="reopened" {{ $status === 'reopened' ? 'selected' : '' }}>{{ translate('Reopened') }}</option>
                            <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>{{ translate('Closed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.mcvendorhub.support') }}"
                            class="btn btn-outline-secondary w-100">{{ translate('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Ticket ID') }}</th>
                            <th>{{ translate('Subject') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Priority') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Assigned To') }}</th>
                            <th>{{ translate('Created') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $statusColors = ['open' => 'success', 'closed' => 'danger', 'reopened' => 'warning'];
                            $pColors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger'];
                        @endphp
                        @forelse ($tickets as $key => $ticket)
                            <tr>
                                <td>{{ $tickets->firstItem() + $key }}</td>
                                <td>
                                    <a href="{{ hasPermission('support_ticket', 'view') ? route('admin.ticket.show', $ticket->id) : '#' }}"
                                        class="font-weight-bold text-primary">{{ $ticket->ticket_id }}</a>
                                </td>
                                <td style="max-width: 220px;">
                                    <span class="d-block text-truncate">{{ $ticket->subject }}</span>
                                </td>
                                <td>{{ $ticket->vendor?->f_name }} {{ $ticket->vendor?->l_name }}</td>
                                <td>
                                    <span class="badge badge-soft-{{ $pColors[$ticket->priority] ?? 'secondary' }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }}">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </td>
                                <td>{{ $ticket->assignedTo ? ($ticket->assignedTo->f_name . ' ' . $ticket->assignedTo->l_name) : '-' }}</td>
                                <td>{{ $ticket->created_at?->format('d M Y') }}</td>
                                <td class="text-center">
                                    @if (hasPermission('support_ticket', 'view'))
                                        <a href="{{ route('admin.ticket.show', $ticket->id) }}"
                                            class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                            title="{{ translate('View') }}"><i class="tio-visible"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <img class="w--120px mb-3"
                                        src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                                    <h5 class="mb-0">{{ translate('No tickets here yet') }}</h5>
                                    <p class="text-muted mb-0">
                                        {{ translate('A ticket appears here once a vendor without "List my store on MyChitti" raises one.') }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="card-footer">
                    {!! $tickets->links() !!}
                </div>
            @endif
        </div>
    </div>
@endsection
