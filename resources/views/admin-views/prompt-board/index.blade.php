@extends('layouts.admin.app')

@section('title', 'Prompt Board')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">Prompt Board</h1>
                <p class="text-muted mb-0">Collaborate on prompts with your team</p>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-primary" data-toggle="modal" data-target="#createModal">
                    <i class="tio-add mr-1"></i> New Prompt
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($boards as $i => $board)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <a href="{{ route('admin.prompt-board.show', $board->id) }}" class="text-dark font-weight-bold">
                                    {{ $board->title }}
                                </a>
                            </td>
                            <td>
                                @if($board->status === 'draft')
                                    <span class="badge badge-secondary">Draft</span>
                                @elseif($board->status === 'reviewing')
                                    <span class="badge badge-warning">Reviewing</span>
                                @else
                                    <span class="badge badge-success">Finalized</span>
                                @endif
                            </td>
                            <td>{{ $board->creator_name ?? 'Admin' }}</td>
                            <td>{{ \Carbon\Carbon::parse($board->created_at)->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.prompt-board.show', $board->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-eye"></i> View
                                </a>
                                <form action="{{ route('admin.prompt-board.destroy', $board->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Delete this prompt?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No prompts yet. Create the first one.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.prompt-board.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New Prompt</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Add vendor onboarding flow" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Initial Prompt</label>
                        <textarea name="initial_prompt" class="form-control" rows="7"
                            placeholder="Describe the feature, task, or idea in detail..." required></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Attachment <small class="text-muted">(image, PDF, txt, doc — max 10MB)</small></label>
                        <div class="custom-file">
                            <input type="file" name="attachment" class="custom-file-input" id="createFileInput"
                                accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.doc,.docx">
                            <label class="custom-file-label" for="createFileInput">Choose file...</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Prompt</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    document.getElementById('createFileInput').addEventListener('change', function() {
        var label = this.nextElementSibling;
        label.textContent = this.files.length ? this.files[0].name : 'Choose file...';
    });
</script>
@endpush
