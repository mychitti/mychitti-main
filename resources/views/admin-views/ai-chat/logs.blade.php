@extends('layouts.admin.app')

@section('title', 'AI Chat Logs')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="page-title">AI Chat Logs</h4>
            <a href="{{ route('admin.ai-chat.index') }}" class="btn btn-sm btn-primary">Back to AI Chat</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2"> 
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.ai-chat.logs', ['type' => 'all']) }}"
                   class="btn btn-sm {{ $type === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                <a href="{{ route('admin.ai-chat.logs', ['type' => 'user']) }}"
                   class="btn btn-sm {{ $type === 'user' ? 'btn-info' : 'btn-outline-info' }}">Users</a>
                <a href="{{ route('admin.ai-chat.logs', ['type' => 'vendor']) }}"
                   class="btn btn-sm {{ $type === 'vendor' ? 'btn-success' : 'btn-outline-success' }}">Vendors</a>
                <a href="{{ route('admin.ai-chat.logs', ['type' => 'admin']) }}"
                   class="btn btn-sm {{ $type === 'admin' ? 'btn-warning' : 'btn-outline-warning' }}">Admins</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-borderless mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Messages</th>
                        <th>Last Activity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversations as $i => $conv)
                        @php
                            if ($conv->user_id > 0) {
                                $chatType = 'user';
                                $ownerId = $conv->user_id;
                                $ownerName = $users[$conv->user_id] ?? "User #{$conv->user_id}";
                                $badge = 'badge-info';
                            } elseif ($conv->vendor_id > 0) {
                                $chatType = 'vendor';
                                $ownerId = $conv->vendor_id;
                                $ownerName = $vendors[$conv->vendor_id] ?? "Vendor #{$conv->vendor_id}";
                                $badge = 'badge-success';
                            } elseif ($conv->admin_id > 0) {
                                $chatType = 'admin';
                                $ownerId = $conv->admin_id;
                                $ownerName = $admins[$conv->admin_id] ?? "Admin #{$conv->admin_id}";
                                $badge = 'badge-warning';
                            } else {
                                $chatType = 'user';
                                $ownerId = 0;
                                $ownerName = 'Guest';
                                $badge = 'badge-secondary';
                            }
                        @endphp
                        <tr>
                            <td>{{ $conversations->firstItem() + $i }}</td>
                            <td><span class="badge {{ $badge }}">{{ ucfirst($chatType) }}</span></td>
                            <td>{{ $ownerName }}</td>
                            <td>{{ $conv->message_count }}</td>
                            <td>{{ \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans() }}</td>
                            <td>
                                @if($ownerId > 0)
                                    <a href="{{ route('admin.ai-chat.logs.detail', [$chatType, $ownerId]) }}"
                                       class="btn btn-sm btn-outline-primary">View</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No conversations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($conversations->hasPages())
            <div class="card-footer">
                {{ $conversations->appends(['type' => $type])->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
