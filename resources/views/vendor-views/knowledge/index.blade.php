@extends('layouts.vendor.app')

@section('title', 'Auto-Reply Knowledge')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    <style>
        /* Coverage chips double as the type filter — one control, two jobs. */
        .kn-cov {
            display:flex; align-items:center; gap:7px; padding:9px 12px; border:1px solid var(--wa-line);
            border-radius:10px; background:#fff; font-size:12.5px; color:var(--wa-ink);
            text-decoration:none; transition:border-color .15s, background .15s;
        }
        .kn-cov:hover { border-color:var(--wa-green); color:var(--wa-ink); }
        .kn-cov.active { border-color:var(--wa-green); background:#f0fdf4; }
        .kn-cov.empty { color:var(--wa-mute); background:var(--wa-bg); }
        .kn-cov b { font-size:13px; }
        .kn-cov-n { min-width:22px; height:22px; border-radius:6px; display:inline-flex; align-items:center;
            justify-content:center; font-size:11px; font-weight:700; background:rgba(37,211,102,.16); color:#15803d; }
        .kn-cov.empty .kn-cov-n { background:#e9edf2; color:#94a3b8; }
        .kn-doc-body {
            font-size:12.5px; color:#667781; line-height:1.55; white-space:pre-wrap;
            max-height:60px; overflow:hidden; position:relative;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-book-opened"></i> Auto-Reply Knowledge</h1>
                <span class="wa-sub">
                    What your chatbot knows. Answers are drawn only from documents that are <b>in use</b>.
                </span>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <span class="wa-chip badge-soft-{{ $activeDocs ? 'success' : 'secondary' }}">
                    {{ $activeDocs }} in use
                </span>
                <span class="wa-chip badge-soft-secondary">{{ $totalDocs }} / {{ $maxDocs }} documents</span>
                <a href="{{ route('vendor.whatsapp.bot') }}" class="btn btn-sm btn-outline-primary">
                    <i class="tio-android"></i> Chatbot settings
                </a>
            </div>
        </div>

        @if (!$activeDocs && $totalDocs)
            <div class="wa-card wa-col">
                <div class="wa-card-b d-flex align-items-center flex-wrap" style="gap:12px;">
                    <i class="tio-warning" style="font-size:20px;color:#d97706;"></i>
                    <div style="flex:1 1 280px;font-size:13px;">
                        <b>Every document is paused.</b>
                        The auto-reply has nothing to draw on, so it will fall back to handing the chat to you.
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Coverage ───────────────────────────────────────────────── --}}
        <div class="wa-card wa-col">
            <div class="wa-card-h">
                <span>Coverage</span>
                @if (request('type'))
                    <a href="{{ route('vendor.whatsapp.knowledge') }}" class="wa-sub">Clear filter ✕</a>
                @else
                    <span class="wa-sub">Tap a type to filter</span>
                @endif
            </div>
            <div class="wa-card-b">
                <div class="d-flex flex-wrap" style="gap:8px;">
                    @foreach ($docTypes as $key => $label)
                        @php
                            $row = $typeCounts[$key] ?? null;
                            $n = (int) ($row->total ?? 0);
                        @endphp
                        <a href="{{ $n ? route('vendor.whatsapp.knowledge', ['type' => $key]) : '#tab-add' }}"
                           @if (!$n) data-toggle="tab" @endif
                           class="kn-cov {{ request('type') === $key ? 'active' : '' }} {{ $n ? '' : 'empty' }}">
                            <span class="kn-cov-n">{{ $n }}</span>
                            <b>{{ $label }}</b>
                            @if (!$n)<span>— nothing yet</span>@endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Documents / Add ────────────────────────────────────────── --}}
        <ul class="nav wa-tabs mb-3" role="tablist" style="background:#fff;border:1px solid var(--wa-line);border-radius:14px;">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-docs" role="tab">
                    <i class="tio-folder-outlined"></i> Your knowledge
                    <span class="wa-chip badge-soft-secondary ml-1">{{ $docs->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-add" data-toggle="tab" href="#tab-new" role="tab">
                    <i class="tio-add-circle-outlined"></i> Add a document
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-docs" role="tabpanel">
                @if ($docs->isEmpty())
                    <div class="wa-card">
                        <div class="wa-empty">
                            <i class="tio-book-outlined"></i>
                            <div class="wa-empty-t">
                                {{ request('type') ? 'Nothing of this type yet' : 'No knowledge yet' }}
                            </div>
                            <div class="wa-empty-s mb-3">
                                Start with <b>Services &amp; Pricing</b> and <b>FAQs</b> — between them they answer
                                most of what customers ask.
                            </div>
                            <a href="#tab-new" data-toggle="tab" class="btn btn-sm btn--primary">Add your first document</a>
                        </div>
                    </div>
                @else
                    <div class="row">
                        @foreach ($docs as $doc)
                            <div class="col-lg-6 wa-col">
                                <div class="wa-card h-100" style="{{ $doc->active ? '' : 'background:#fcfcfd;' }}">
                                    <div class="wa-card-b">
                                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:10px;">
                                            <div style="min-width:0;">
                                                <b style="font-size:14px;color:#1e293b;">{{ $doc->title }}</b>
                                                <div class="wa-sub">
                                                    {{ \App\Models\StoreKnowledgeDoc::typeLabel($doc->doc_type) }}
                                                    · updated {{ $doc->updated_at ? $doc->updated_at->diffForHumans() : '—' }}
                                                </div>
                                            </div>
                                            {{-- The toggle is the important control here, so it reads as
                                                 a state you can flip rather than a badge. --}}
                                            <form action="{{ route('vendor.whatsapp.knowledge.toggle') }}" method="post" class="mb-0">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $doc->id }}">
                                                <button type="submit" class="btn btn-sm {{ $doc->active ? 'btn-success' : 'btn-outline-secondary' }} text-nowrap"
                                                        title="{{ $doc->active ? 'Stop using this for auto-replies' : 'Start using this for auto-replies' }}">
                                                    <i class="tio-{{ $doc->active ? 'checkmark-circle' : 'pause-circle-outlined' }}"></i>
                                                    {{ $doc->active ? 'In use' : 'Paused' }}
                                                </button>
                                            </form>
                                        </div>

                                        <div class="kn-doc-body p-2 rounded" style="background:var(--wa-bg);">{{ \Illuminate\Support\Str::limit($doc->content, 260) }}</div>

                                        <div class="d-flex align-items-center mt-2" style="gap:6px;">
                                            <button type="button" class="btn btn-sm btn-outline-primary kn-edit"
                                                    data-id="{{ $doc->id }}" data-type="{{ $doc->doc_type }}"
                                                    data-title="{{ $doc->title }}" data-content="{{ $doc->content }}">
                                                <i class="tio-edit"></i> Edit
                                            </button>
                                            <form action="{{ route('vendor.whatsapp.knowledge.delete') }}" method="post" class="mb-0 ml-auto"
                                                  onsubmit="return confirm('Delete “{{ $doc->title }}”? The auto-reply will stop using it immediately.');">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $doc->id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="tab-pane fade" id="tab-new" role="tabpanel">
                @php $docsFull = $totalDocs >= $maxDocs; @endphp
                <div class="row">
                    <div class="col-lg-8 wa-col">
                        <div class="wa-card">
                            <div class="wa-card-h">New document</div>
                            <div class="wa-card-b">
                                @if ($docsFull)
                                    <div class="alert alert-warning" style="font-size:13px;">
                                        <b>You've reached the {{ $maxDocs }}-document limit.</b>
                                        Delete one you no longer need before adding another.
                                    </div>
                                @endif
                                <form action="{{ route('vendor.whatsapp.knowledge.store') }}" method="post">
                                    @csrf
                                    <fieldset @if ($docsFull) disabled @endif style="border:0;padding:0;margin:0;min-width:0;">
                                        <div class="form-group">
                                            <label class="form-label">Type</label>
                                            <select name="doc_type" class="form-control" required>
                                                @foreach ($docTypes as $key => $label)
                                                    <option value="{{ $key }}" {{ old('doc_type', request('type')) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Helps the auto-reply pick the right document for a question.</small>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Title</label>
                                            <input type="text" name="title" class="form-control" maxlength="200"
                                                   placeholder="e.g. Consultation charges" value="{{ old('title') }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Content</label>
                                            <textarea name="content" class="form-control" rows="10" maxlength="20000" required
                                                      placeholder="Write it in plain language, e.g.&#10;&#10;General consultation: Rs. 300&#10;Specialist consultation: Rs. 600&#10;Open Mon–Sat, 9 AM to 8 PM. Closed on Sundays.">{{ old('content') }}</textarea>
                                            <small class="text-muted">Plain text works best. Up to 20,000 characters.</small>
                                        </div>
                                        <button type="submit" class="btn btn--primary btn-block btn-lg" style="font-size:14px;">
                                            <i class="tio-add"></i> Add document
                                        </button>
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 wa-col">
                        <div class="wa-card">
                            <div class="wa-card-h">Writing good knowledge</div>
                            <div class="wa-card-b">
                                <ul class="pl-3 mb-0 wa-sub" style="line-height:1.7;">
                                    <li><b>One topic per document.</b> Several short documents beat one long one — the
                                        bot retrieves whole documents, so a mixed one dilutes the answer.</li>
                                    <li><b>Write what a customer would ask</b>, in their words, not internal shorthand.</li>
                                    <li><b>Include the specifics</b> — prices, timings, addresses, what's included.
                                        Vague knowledge produces vague replies.</li>
                                    <li><b>Pause instead of deleting</b> when something is seasonal. It keeps the text
                                        for next time.</li>
                                </ul>
                                <div class="wa-note mt-3">
                                    If MyChitti customises your knowledge base for you, <b>you are still responsible
                                    for reviewing it</b> before it answers customers.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="knEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('vendor.whatsapp.knowledge.update') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" id="knId">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit knowledge</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="doc_type" id="knType" class="form-control" required>
                                @foreach ($docTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" id="knTitle" class="form-control" maxlength="200" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Content</label>
                            <textarea name="content" id="knContent" class="form-control" rows="12" maxlength="20000" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn--primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    $(document).on('click', '.kn-edit', function () {
        var d = $(this).data();
        $('#knId').val(d.id);
        $('#knType').val(d.type);
        $('#knTitle').val(d.title);
        $('#knContent').val(d.content);
        $('#knEditModal').modal('show');
    });
</script>
@endpush
