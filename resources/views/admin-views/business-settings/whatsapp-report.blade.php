@extends('layouts.admin.app')

@section('title', translate('WhatsApp Delivery Report'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-chart-bar-4" style="font-size:22px;"></i></span>
                <span>{{ translate('WhatsApp Delivery Report') }}</span>
            </h1>
            <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="btn btn-sm btn-outline-secondary">← {{ translate('WhatsApp Setup') }}</a>
        </div>

        <div class="card">
            <div class="card-header">
                <form class="row align-items-center w-100" method="get">
                    <div class="col-sm-4">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ translate('Search number / context / text') }}">
                    </div>
                    <div class="col-sm-3">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">{{ translate('All statuses') }}</option>
                            @foreach (['accepted','sent','delivered','read','failed','received'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <button class="btn btn-sm btn-primary">{{ translate('Filter') }}</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle table-nowrap mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('When') }}</th>
                                <th>{{ translate('Dir') }}</th>
                                <th>{{ translate('To / From') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Context') }}</th>
                                <th>{{ translate('Message') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Error') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $badge = ['accepted'=>'secondary','sent'=>'info','delivered'=>'primary','read'=>'success','failed'=>'danger','received'=>'dark'];
                            @endphp
                            @forelse ($messages as $m)
                                <tr>
                                    <td><small>{{ \Carbon\Carbon::parse($m->sent_at ?? $m->created_at)->format('d-m-Y h:i A') }}</small></td>
                                    <td><span class="badge badge-soft-{{ $m->direction === 'in' ? 'dark' : 'secondary' }}">{{ strtoupper($m->direction) }}</span></td>
                                    <td>{{ $m->recipient }}</td>
                                    <td>{{ $m->type }}</td>
                                    <td><small>{{ $m->context }}</small></td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($m->body, 60) }}</small></td>
                                    <td><span class="badge badge-{{ $badge[$m->status] ?? 'secondary' }}">{{ ucfirst($m->status ?? '—') }}</span></td>
                                    <td><small class="text-danger">{{ \Illuminate\Support\Str::limit($m->error, 80) }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">{{ translate('No WhatsApp messages logged yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {!! $messages->links() !!}
            </div>
        </div>

        <div class="alert alert-info mt-3" style="font-size:12px;">
            <b>{{ translate('Statuses') }}:</b>
            {{ translate('accepted (queued by Meta) → sent → delivered → read. "failed" shows the reason. Live status requires the webhook to be configured on the setup page.') }}
        </div>
    </div>
@endsection
