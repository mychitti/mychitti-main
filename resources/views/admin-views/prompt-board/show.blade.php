@extends('layouts.admin.app')

@section('title', $board->title)

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item"><a href="{{ route('admin.prompt-board.index') }}">Prompt Board</a></li>
                        <li class="breadcrumb-item active">{{ $board->title }}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{ $board->title }}</h1>
            </div>
            <div class="col-sm-auto d-flex align-items-center gap-2">
                <select id="statusSelect" class="form-control form-control-sm" style="min-width:140px;">
                    <option value="draft"      {{ $board->status === 'draft'      ? 'selected' : '' }}>Draft</option>
                    <option value="reviewing"  {{ $board->status === 'reviewing'  ? 'selected' : '' }}>Reviewing</option>
                    <option value="finalized"  {{ $board->status === 'finalized'  ? 'selected' : '' }}>Finalized</option>
                </select>
                <button id="saveStatusBtn" class="btn btn-sm btn-outline-primary ml-2">Save Status</button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <!-- Left: Initial Prompt + Suggestions -->
        <div class="col-lg-7">

            <!-- Initial Prompt -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0"><i class="tio-flash-outlined mr-1"></i> Initial Prompt</h6>
                    <small class="text-muted">by {{ $board->creator_name ?? 'Admin' }} &bull; {{ \Carbon\Carbon::parse($board->created_at)->format('d M Y') }}</small>
                </div>
                <div class="card-body">
                    <pre class="mb-0" style="white-space:pre-wrap; font-family:inherit; font-size:14px; background:transparent; border:none; padding:0;">{{ $board->initial_prompt }}</pre>
                    @if($board->attachment)
                        <div class="mt-3 pt-3 border-top">
                            @php $ext = pathinfo($board->attachment, PATHINFO_EXTENSION); @endphp
                            @if(in_array(strtolower($ext), ['jpg','jpeg','png','webp']))
                                <a href="{{ asset('storage/app/public/' . $board->attachment) }}" target="_blank">
                                    <img src="{{ asset('storage/app/public/' . $board->attachment) }}"
                                        style="max-height:200px; max-width:100%; border-radius:6px; border:1px solid #dee2e6;">
                                </a>
                            @else
                                <a href="{{ asset('storage/app/public/' . $board->attachment) }}" target="_blank"
                                    class="btn btn-sm btn-outline-secondary">
                                    <i class="tio-attachment-diagonal mr-1"></i>{{ basename($board->attachment) }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Suggestions Thread -->
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="tio-comment-outlined mr-1"></i> Suggestions ({{ count($suggestions) }})</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($suggestions as $s)
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="font-weight-bold">{{ $s->admin_name ?? 'Admin' }}</span>
                                <small class="text-muted ml-2">{{ \Carbon\Carbon::parse($s->created_at)->format('d M Y, h:i A') }}</small>
                            </div>
                            <div class="d-flex" style="gap:6px;">
                                <button class="btn btn-xs btn-outline-secondary use-suggestion"
                                    data-text="{{ $s->suggestion }}" title="Use as Final">
                                    <i class="tio-arrow-right"></i> Use
                                </button>
                                <form action="{{ route('admin.prompt-board.suggestion.delete', [$board->id, $s->id]) }}" method="POST"
                                    onsubmit="return confirm('Delete this suggestion?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                </form>
                            </div>
                        </div>
                        <pre style="white-space:pre-wrap; font-family:inherit; font-size:13px; margin:0; color:#555;">{{ $s->suggestion }}</pre>
                        @if($s->attachment)
                            @php $ext = pathinfo($s->attachment, PATHINFO_EXTENSION); @endphp
                            <div class="mt-2">
                                @if(in_array(strtolower($ext), ['jpg','jpeg','png','webp']))
                                    <a href="{{ asset('storage/app/public/' . $s->attachment) }}" target="_blank">
                                        <img src="{{ asset('storage/app/public/' . $s->attachment) }}"
                                            style="max-height:150px; max-width:100%; border-radius:6px; border:1px solid #dee2e6;">
                                    </a>
                                @else
                                    <a href="{{ asset('storage/app/public/' . $s->attachment) }}" target="_blank"
                                        class="btn btn-sm btn-outline-secondary">
                                        <i class="tio-attachment-diagonal mr-1"></i>{{ basename($s->attachment) }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">No suggestions yet. Be the first to add one.</div>
                    @endforelse
                </div>
            </div>

            <!-- Add Suggestion -->
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0">Add Your Suggestion</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.prompt-board.suggestion.add', $board->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <textarea name="suggestion" class="form-control" rows="5"
                                placeholder="Write your suggestion, improvement, or alternate version of this prompt..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold" style="font-size:13px;">Attachment <small class="text-muted">(image, PDF, txt, doc — max 10MB)</small></label>
                            <div class="custom-file">
                                <input type="file" name="attachment" class="custom-file-input" id="suggFileInput"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.doc,.docx">
                                <label class="custom-file-label" for="suggFileInput">Choose file...</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Suggestion</button>
                    </form>
                </div>
            </div>

        </div>

        <!-- Right: Final Prompt -->
        <div class="col-lg-5">
            <div class="card sticky-top" style="top:80px;">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0"><i class="tio-checkmark-circle-outlined mr-1 text-success"></i> Final Prompt</h6>
                    <button id="copyFinalBtn" class="btn btn-xs btn-outline-secondary" title="Copy">
                        <i class="tio-copy"></i> Copy
                    </button>
                </div>
                <div class="card-body">
                    @if($board->status === 'finalized' && $board->final_prompt)
                        <div class="alert alert-success py-2 px-3 mb-2" style="font-size:12px;">
                            <i class="tio-checkmark-circle"></i> This prompt has been finalized.
                        </div>
                    @endif
                    <form action="{{ route('admin.prompt-board.set-final', $board->id) }}" method="POST">
                        @csrf
                        <textarea id="finalPromptArea" name="final_prompt" class="form-control mb-2" rows="14"
                            placeholder="Compose the final prompt here. Use the 'Use' button on any suggestion to copy it here, then edit as needed.">{{ $board->final_prompt }}</textarea>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="tio-checkmark mr-1"></i> Save as Final
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    // Use suggestion → populate final prompt area
    document.querySelectorAll('.use-suggestion').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('finalPromptArea').value = this.dataset.text;
            document.getElementById('finalPromptArea').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Copy final prompt
    document.getElementById('copyFinalBtn').addEventListener('click', function() {
        var text = document.getElementById('finalPromptArea').value;
        if (!text) return;
        navigator.clipboard.writeText(text).then(function() {
            var btn = document.getElementById('copyFinalBtn');
            btn.innerHTML = '<i class="tio-checkmark"></i> Copied';
            setTimeout(function() { btn.innerHTML = '<i class="tio-copy"></i> Copy'; }, 2000);
        });
    });

    // File input label
    document.getElementById('suggFileInput').addEventListener('change', function() {
        this.nextElementSibling.textContent = this.files.length ? this.files[0].name : 'Choose file...';
    });

    // Save status
    document.getElementById('saveStatusBtn').addEventListener('click', function() {
        var status = document.getElementById('statusSelect').value;
        fetch('{{ route("admin.prompt-board.status", $board->id) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ status: status })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                toastr.success('Status updated.');
            }
        });
    });
</script>
@endpush
