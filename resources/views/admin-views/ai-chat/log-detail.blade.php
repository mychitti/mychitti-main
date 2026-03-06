@extends('layouts.admin.app')

@section('title', "Chat - $name")

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="page-title">{{ $name }} <small class="text-muted">({{ ucfirst($type) }})</small></h4>
            <a href="{{ route('admin.ai-chat.logs', ['type' => $type]) }}" class="btn btn-sm btn-outline-primary">Back to Logs</a>
        </div>
    </div>

    <div class="card"> 
        <div class="card-body" style="max-height:70vh;overflow-y:auto" id="chat-log-box">
            @forelse($messages as $msg)
                <div class="d-flex mb-3 {{ $msg->role === 'assistant' ? '' : 'justify-content-end' }}">
                    <div style="max-width:75%;padding:10px 14px;border-radius:12px;font-size:14px;line-height:1.5;
                        {{ $msg->role === 'assistant'
                            ? 'background:#f0f0f0;color:#333;border-top-left-radius:2px'
                            : 'background:#4f46e5;color:#fff;border-top-right-radius:2px' }}">
                        @if($msg->type !== 'text')
                            <span class="badge badge-light mb-1" style="font-size:11px">{{ $msg->type }}</span><br>
                        @endif
                        {!! nl2br(e($msg->content)) !!}
                        <div style="font-size:11px;opacity:0.6;margin-top:4px;text-align:right">
                            {{ $msg->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">No messages found.</div>
            @endforelse
        </div>

        @if($messages->hasPages())
            <div class="card-footer">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var box = document.getElementById('chat-log-box');
        box.scrollTop = box.scrollHeight;
    });
</script>
@endsection
