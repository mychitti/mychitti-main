@extends('layouts.admin.app')

@section('title', translate('WhatsApp Auto-Reply Knowledge'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-book-opened"></i> {{ translate('WhatsApp Auto-Reply Knowledge') }}</h1>
            <div class="d-flex align-items-center" style="gap:8px;">
                <a href="{{ route('admin.business-settings.third-party.whatsapp-inbox') }}" class="btn btn-sm btn-outline-primary">
                    <i class="tio-chat"></i> {{ translate('Chats') }}
                </a>
                <form method="get" class="mb-0">
                    <select name="type" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="">{{ translate('All types') }}</option>
                        @foreach ($docTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <p class="text-muted" style="font-size:13px;">
            {{ translate('Teach the MyChitti WhatsApp assistant. When a vendor or customer messages the MyChitti number, answers come from your active documents here.') }}
        </p>

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Knowledge') }} ({{ $docs->count() }})</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('Document') }}</th>
                                        <th>{{ translate('Type') }}</th>
                                        <th class="text-center">{{ translate('Auto-reply') }}</th>
                                        <th class="text-right">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($docs as $doc)
                                        <tr>
                                            <td style="max-width:280px;">
                                                <b>{{ $doc->title }}</b>
                                                <small class="text-muted d-block text-truncate">{{ \Illuminate\Support\Str::limit($doc->content, 90) }}</small>
                                            </td>
                                            <td><span class="badge badge-soft-info">{{ \App\Models\StoreKnowledgeDoc::typeLabel($doc->doc_type) }}</span></td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.toggle') }}" method="post" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $doc->id }}">
                                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                                            title="{{ $doc->active ? translate('Pause') : translate('Resume') }}">
                                                        <span class="badge badge-soft-{{ $doc->active ? 'success' : 'secondary' }}">{{ $doc->active ? translate('In use') : translate('Paused') }}</span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-right text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary kn-edit"
                                                        data-id="{{ $doc->id }}" data-type="{{ $doc->doc_type }}"
                                                        data-title="{{ $doc->title }}" data-content="{{ $doc->content }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.delete') }}" method="post" class="d-inline"
                                                      onsubmit="return confirm('{{ translate('Delete this document?') }}');">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $doc->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                {{ translate('No knowledge yet. Add your first document on the right.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                {{-- Test console. Puts the question through the live auto-reply composer and shows
                     the answer it would send, so the knowledge can be checked before a real
                     customer finds the gap. Nothing is sent to WhatsApp. --}}
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ translate('Test the Assistant') }}</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="kbClear">{{ translate('Clear') }}</button>
                    </div>
                    <div class="card-body">
                        <div id="kbChat" style="max-height:340px;overflow-y:auto;margin-bottom:10px;display:none;"></div>
                        <div id="kbEmpty" class="text-muted text-center py-3" style="font-size:13px;">
                            {{ translate('Ask what a vendor or customer might ask. Nothing is sent to WhatsApp.') }}
                        </div>
                        <div class="input-group">
                            <input type="text" id="kbInput" class="form-control" maxlength="1000"
                                   placeholder="{{ translate('e.g. How do I connect my WhatsApp number?') }}" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn--primary" type="button" id="kbSend">{{ translate('Ask') }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Add Knowledge') }}</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.store') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ translate('Document Type') }}</label>
                                <select name="doc_type" class="form-control" required>
                                    @foreach ($docTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('doc_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Title') }}</label>
                                <input type="text" name="title" class="form-control" maxlength="200"
                                       placeholder="{{ translate('e.g. How to connect WhatsApp') }}" value="{{ old('title') }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Content') }}</label>
                                <textarea name="content" class="form-control" rows="8" maxlength="20000" required
                                          placeholder="{{ translate('Write the information in plain language. One topic per document.') }}">{{ old('content') }}</textarea>
                                <small class="text-muted">{{ translate('Plain text works best. Add multiple documents instead of one long one.') }}</small>
                            </div>
                            <button type="submit" class="btn btn--primary btn-block">{{ translate('Add Document') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="knEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.update') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" id="knId">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Edit Knowledge') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Document Type') }}</label>
                            <select name="doc_type" id="knType" class="form-control" required>
                                @foreach ($docTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Title') }}</label>
                            <input type="text" name="title" id="knTitle" class="form-control" maxlength="200" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Content') }}</label>
                            <textarea name="content" id="knContent" class="form-control" rows="10" maxlength="20000" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('css_or_js')
<style>
    .kb-msg { margin-bottom:10px; font-size:13px; line-height:1.5; }
    .kb-msg .kb-bubble { display:inline-block; padding:7px 11px; border-radius:12px; max-width:92%; white-space:pre-wrap; }
    .kb-you { text-align:right; }
    .kb-you .kb-bubble { background:#dcf8c6; border-bottom-right-radius:3px; text-align:left; }
    .kb-bot .kb-bubble { background:#f1f3f6; border-bottom-left-radius:3px; }
    .kb-meta { font-size:11px; color:#8a94a6; margin-top:3px; }
    .kb-meta .kb-toggle { color:#6c7a91; text-decoration:underline; cursor:pointer; }
    .kb-detail { background:#fbfcfd; border:1px solid #eceff3; border-radius:8px; padding:8px 10px;
                 margin-top:6px; font-size:11px; color:#4b5563; max-height:230px; overflow:auto; }
    .kb-detail pre { white-space:pre-wrap; font-size:10.5px; margin:0; color:#4b5563; }
    .kb-chip { display:inline-block; padding:1px 7px; border-radius:999px; font-size:10.5px; margin:2px 3px 2px 0; }
    .kb-chip.on { background:#e8f7ef; color:#128c7e; }
    .kb-chip.off { background:#f4f5f7; color:#98a2b3; text-decoration:line-through; }
</style>
@endpush

@push('script_2')
<script>
    (function () {
        var URL = "{{ route('admin.business-settings.third-party.whatsapp-knowledge.preview') }}";
        var CSRF = "{{ csrf_token() }}";
        var $chat = $('#kbChat'), $input = $('#kbInput'), $send = $('#kbSend');

        function esc(t) {
            return $('<div>').text(t == null ? '' : t).html();
        }

        function add(who, $body) {
            $('#kbEmpty').hide();
            $chat.show().append($('<div class="kb-msg kb-' + who + '"></div>').append($body));
            $chat.scrollTop($chat[0].scrollHeight);
        }

        function bubble(text, cls) {
            return $('<span class="kb-bubble"></span>').addClass(cls || '').text(text);
        }

        function ask() {
            var q = ($input.val() || '').trim();
            if (!q) { return; }

            add('you', bubble(q));
            $input.val('').prop('disabled', true);
            $send.prop('disabled', true).text('...');

            var $pending = $('<div class="kb-msg kb-bot"></div>').append(bubble('Thinking...', 'text-muted'));
            $chat.append($pending);
            $chat.scrollTop($chat[0].scrollHeight);

            $.post(URL, { _token: CSRF, message: q })
                .done(function (res) {
                    $pending.remove();

                    // Which chunks the semantic search returned and which cleared the score
                    // floor. This is the part that explains an answer that looks wrong.
                    var chips = (res.rag || []).map(function (c) {
                        return '<span class="kb-chip ' + (c.kept ? 'on' : 'off') + '">'
                            + esc(c.title) + ' ' + c.score + '</span>';
                    }).join('');

                    var $detail = $('<div class="kb-detail" style="display:none;"></div>');
                    if (chips) {
                        $detail.append('<div class="mb-2">' + chips + '</div>');
                    }
                    $detail.append('<b>Knowledge handed to the model</b>')
                        .append($('<pre></pre>').text(res.knowledge || '(none)'))
                        .append('<hr class="my-2"><b>Full system prompt</b>')
                        .append($('<pre></pre>').text(res.system || ''));

                    var $meta = $('<div class="kb-meta"></div>');
                    if (res.escalated) {
                        $meta.append('<span class="text-danger">Not covered by the knowledge &mdash; would alert the team</span> &middot; ');
                    }
                    $meta.append(document.createTextNode(res.source + ' \u00b7 ' + res.doc_count + ' active docs \u00b7 '));

                    var $toggle = $('<span class="kb-toggle">show what it used</span>').on('click', function () {
                        $detail.toggle();
                    });
                    $meta.append($toggle);

                    add('bot', [bubble(res.reply), $meta, $detail]);
                })
                .fail(function (xhr) {
                    $pending.remove();
                    var m = (xhr.responseJSON && xhr.responseJSON.message) || 'Request failed.';
                    add('bot', bubble(m, 'text-danger'));
                })
                .always(function () {
                    $input.prop('disabled', false).focus();
                    $send.prop('disabled', false).text('Ask');
                });
        }

        $send.on('click', ask);
        $input.on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); ask(); } });
        $('#kbClear').on('click', function () {
            $chat.empty().hide();
            $('#kbEmpty').show();
        });
    })();

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
