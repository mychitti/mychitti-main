@extends('layouts.admin.app')

@section('title', $doc->title)

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .doc-help {
            font-size: 12px;
            color: #6c757d;
        }

        .doc-body {
            line-height: 1.7;
        }

        .doc-body img {
            max-width: 100%;
            height: auto;
        }

        .doc-body table {
            width: 100%;
            margin-bottom: 1rem;
            border-collapse: collapse;
        }

        .doc-body table td,
        .doc-body table th {
            border: 1px solid #e7eaf3;
            padding: 8px 10px;
        }

        .doc-body pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 14px 16px;
            border-radius: 6px;
            overflow-x: auto;
        }

        .doc-file-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f9;
        }

        .doc-file-row:last-child {
            border-bottom: 0;
        }

        .doc-file-icon {
            font-size: 22px;
            width: 26px;
            text-align: center;
        }

        .doc-version-row {
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f9;
        }

        .doc-version-row:last-child {
            border-bottom: 0;
        }

        .doc-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
        }

        .doc-file-row {
            cursor: pointer;
        }

        .doc-file-row.is-active {
            background: #f4f7ff;
            border-radius: 6px;
        }

        .doc-file-name {
            font-weight: 500;
        }

        #doc-preview-frame {
            width: 100%;
            height: 72vh;
            min-height: 460px;
            border: 0;
        }

        #doc-preview-image {
            max-width: 100%;
            border-radius: 6px;
        }

        #doc-preview-doc {
            max-height: 72vh;
            overflow: auto;
            padding: 4px 2px;
            line-height: 1.7;
        }

        #doc-preview-doc img {
            max-width: 100%;
            height: auto;
        }

        #doc-preview-doc table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        #doc-preview-doc table td,
        #doc-preview-doc table th {
            border: 1px solid #e7eaf3;
            padding: 6px 9px;
        }

        .doc-sheet-tabs .nav-link {
            font-size: 12px;
            padding: 4px 10px;
        }

        .doc-sheet-wrap {
            max-height: 66vh;
            overflow: auto;
        }

        .doc-sheet-wrap table {
            font-size: 12.5px;
            border-collapse: collapse;
            width: 100%;
        }

        .doc-sheet-wrap table td,
        .doc-sheet-wrap table th {
            border: 1px solid #e7eaf3;
            padding: 4px 8px;
            white-space: nowrap;
        }

        #doc-preview-text {
            background: #f8f9fb;
            border: 1px solid #e7eaf3;
            border-radius: 6px;
            padding: 14px 16px;
            font-family: monospace;
            font-size: 12.5px;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 72vh;
            overflow: auto;
            margin: 0;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-7 col-12">
                    <h1 class="page-header-title mb-1">
                        <span class="page-header-icon"><i class="tio-book"></i></span>
                        <span>{{ $doc->title }}</span>
                    </h1>
                    <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                        @if ($doc->category)
                            <span class="doc-cat-pill"
                                style="background: {{ $doc->category->color ?? '#6c5ce7' }}1f; color: {{ $doc->category->color ?? '#6c5ce7' }};">
                                {{ $doc->category->name }}
                            </span>
                        @endif
                        <span class="badge badge-soft-dark">v{{ $doc->version }}</span>
                        <span class="doc-help">
                            {{ translate('Updated') }} {{ $doc->updated_at?->format('d M Y, h:i A') }}
                            @if ($doc->editor)
                                {{ translate('by') }} {{ $doc->editor->f_name }} {{ $doc->editor->l_name }}
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-5 col-12 text-md-right">
                    <a href="{{ route('admin.documentation.index') }}" class="btn btn-outline-secondary">
                        <i class="tio-back-ui"></i> {{ translate('Back') }}
                    </a>
                    <a href="{{ route('admin.documentation.edit', $doc->id) }}" class="btn btn--primary">
                        <i class="tio-edit"></i> {{ translate('Edit') }}
                    </a>
                </div>
            </div>
        </div>

        @if ($doc->summary)
            <div class="alert alert-soft-info">{{ $doc->summary }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-3">
                    {{-- Preview header, shown only while a file is open --}}
                    <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center"
                        id="doc-preview-head" hidden>
                        <div style="min-width:0;">
                            <strong id="doc-preview-name" class="text-truncate d-block"></strong>
                            <span class="doc-help" id="doc-preview-meta"></span>
                        </div>
                        <div style="white-space:nowrap;">
                            <a href="#" class="btn btn-sm btn-outline-primary" id="doc-preview-download">
                                <i class="tio-download"></i> {{ translate('Download') }}
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="doc-preview-close">
                                {{ translate('Close') }}
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- The written body, replaced in place while a file preview is open --}}
                        <div id="doc-body-pane" class="doc-body">
                            @if (filled($doc->content))
                                {!! $doc->content !!}
                            @else
                                <div class="text-center py-5 doc-help">
                                    {{ count($doc->files)
                                        ? translate('This document has no written body — pick a file on the right to read it here.')
                                        : translate('This document has no written body — it lives in its attached files.') }}
                                </div>
                            @endif
                        </div>

                        <div id="doc-preview-pane" hidden></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Attachments --}}
                <div class="card mb-3">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            {{ translate('Files') }}
                            <span class="badge badge-soft-dark ml-1">{{ count($doc->files) }}</span>
                        </h5>
                        <button type="button" class="btn btn-sm btn--primary" data-toggle="modal"
                            data-target="#uploadFileModal">
                            <i class="tio-upload"></i> {{ translate('Upload') }}
                        </button>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($doc->files as $file)
                            @php
                                [$icon, $color] = _documentationFileIcon($file->extension);
                                $kind = _documentationPreviewKind($file->extension);
                            @endphp
                            <div class="doc-file-row doc-file-open" data-id="{{ $file->id }}"
                                data-kind="{{ $kind }}" data-name="{{ $file->file_name }}"
                                data-ext="{{ strtoupper($file->extension) }}"
                                data-size="{{ _documentationReadableSize($file->size) }}"
                                data-view="{{ route('admin.documentation.files.view', $file->id) }}"
                                data-download="{{ route('admin.documentation.files.download', $file->id) }}"
                                title="{{ translate('Click to preview') }}">
                                <i class="{{ $icon }} doc-file-icon" style="color: {{ $color }};"></i>
                                <div class="flex-grow-1" style="min-width:0;">
                                    <div class="text-truncate doc-file-name" title="{{ $file->file_name }}">
                                        {{ $file->file_name }}</div>
                                    <div class="doc-help">
                                        {{ _documentationReadableSize($file->size) }} ·
                                        {{ $file->created_at?->format('d M Y') }}
                                    </div>
                                </div>
                                <a href="{{ route('admin.documentation.files.download', $file->id) }}"
                                    class="btn btn-sm btn-outline-primary doc-file-stop"
                                    title="{{ translate('Download') }}">
                                    <i class="tio-download"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-danger form-alert doc-file-stop" href="javascript:"
                                    data-id="file-{{ $file->id }}"
                                    data-message="{{ translate('Delete this file permanently?') }}"
                                    title="{{ translate('Delete') }}">
                                    <i class="tio-delete-outlined"></i>
                                </a>
                                <form action="{{ route('admin.documentation.files.delete', $file->id) }}" method="post"
                                    id="file-{{ $file->id }}">
                                    @csrf @method('delete')
                                </form>
                            </div>
                        @empty
                            <div class="text-center py-3 doc-help">
                                {{ translate('No files attached yet — upload the Word or PDF here.') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Version history --}}
                <div class="card mb-3">
                    <div class="card-header border-0">
                        <h5 class="card-title mb-0">
                            {{ translate('Version History') }}
                            <span class="badge badge-soft-dark ml-1">{{ count($doc->versions) }}</span>
                        </h5>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($doc->versions as $version)
                            <div class="doc-version-row">
                                <div class="d-flex justify-content-between align-items-start" style="gap:8px;">
                                    <div style="min-width:0;">
                                        <strong>v{{ $version->version }}</strong>
                                        <span
                                            class="badge badge-soft-{{ $version->source == 'file' ? 'info' : 'primary' }} ml-1">
                                            {{ $version->source == 'file' ? translate('File') : translate('Written') }}
                                        </span>
                                        <div class="doc-help">{{ $version->note }}</div>
                                        <div class="doc-help">
                                            {{ $version->created_at?->format('d M Y, h:i A') }}
                                            @if ($version->author)
                                                · {{ $version->author->f_name }} {{ $version->author->l_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right" style="white-space:nowrap;">
                                        @if ($version->source == 'file' && $version->stored_name)
                                            <a href="{{ route('admin.documentation.versions.download', $version->id) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="{{ translate('Download') }}"><i class="tio-download"></i></a>
                                        @else
                                            <form
                                                action="{{ route('admin.documentation.versions.restore', $version->id) }}"
                                                method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning"
                                                    title="{{ translate('Restore this version') }}">
                                                    <i class="tio-restore"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-3 doc-help">{{ translate('No history yet.') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- Meta --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="doc-help">{{ translate('Type') }}</span>
                            <strong>{{ \App\Models\Documentation::DOC_TYPES[$doc->doc_type] ?? $doc->doc_type }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="doc-help">{{ translate('Created') }}</span>
                            <strong>{{ $doc->created_at?->format('d M Y') }}</strong>
                        </div>
                        @if ($doc->author)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="doc-help">{{ translate('Author') }}</span>
                                <strong>{{ $doc->author->f_name }} {{ $doc->author->l_name }}</strong>
                            </div>
                        @endif
                        @if (count($doc->tag_list))
                            <div class="doc-help mb-1">{{ translate('Tags') }}</div>
                            @foreach ($doc->tag_list as $tag)
                                <span class="badge badge-soft-secondary mr-1 mb-1">{{ $tag }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadFileModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.documentation.upload', $doc->id) }}" method="post"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Upload Documents') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="file" name="files[]" class="form-control" multiple required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.md,.json,.zip,.png,.jpg,.jpeg,.gif,.webp">
                        <div class="doc-help mt-2">
                            {{ translate('Word, PDF, Excel, PowerPoint, JSON, ZIP or images. Up to 25 MB each.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Upload') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        (function() {
            var $pane = $('#doc-preview-pane');
            var $body = $('#doc-body-pane');
            var $head = $('#doc-preview-head');

            // .docx and .xlsx are rendered in the page itself, so an internal document never
            // leaves this server. Each library is fetched the first time one is actually opened.
            var LIB = {
                word: 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.8.0/mammoth.browser.min.js',
                sheet: 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js'
            };
            var loading = {};

            function esc(value) {
                return $('<div>').text(value == null ? '' : value).html();
            }

            function loadLib(url) {
                if (!loading[url]) {
                    loading[url] = $.ajax({ url: url, dataType: 'script', cache: true });
                }
                return loading[url];
            }

            function busy(message) {
                $pane.html('<div class="text-center py-5 doc-help">' + esc(message) + '</div>');
            }

            function fallbackCard(d, message) {
                return '' +
                    '<div class="text-center py-5">' +
                    '<i class="fas fa-file-alt" style="font-size:44px;color:#7f8c8d;"></i>' +
                    '<h5 class="mt-3 mb-1">' + esc(d.name) + '</h5>' +
                    '<p class="doc-help mb-4">' + esc(d.ext + ' · ' + d.size) + '<br>' + esc(message) + '</p>' +
                    '<a href="' + d.download + '" class="btn btn--primary btn-sm">' +
                    '<i class="tio-download"></i> {{ translate('Download') }}</a>' +
                    '</div>';
            }

            function fetchBuffer(url) {
                return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', url, true);
                    xhr.responseType = 'arraybuffer';
                    xhr.onload = function() {
                        xhr.status === 200 ? resolve(xhr.response) : reject();
                    };
                    xhr.onerror = reject;
                    xhr.send();
                });
            }

            function renderWord(d) {
                busy('{{ translate('Rendering document…') }}');
                loadLib(LIB.word).done(function() {
                    fetchBuffer(d.view).then(function(buffer) {
                        return mammoth.convertToHtml({ arrayBuffer: buffer });
                    }).then(function(result) {
                        $pane.html('<div id="doc-preview-doc" class="doc-body">' +
                            (result.value || '<p class="doc-help">{{ translate('This document is empty.') }}</p>') +
                            '</div>');
                    }).catch(function() {
                        $pane.html(fallbackCard(d, '{{ translate('Could not read this file. Download it to open in Word.') }}'));
                    });
                }).fail(function() {
                    $pane.html(fallbackCard(d, '{{ translate('The document renderer could not be loaded.') }}'));
                });
            }

            function renderSheet(d) {
                busy('{{ translate('Rendering spreadsheet…') }}');
                loadLib(LIB.sheet).done(function() {
                    fetchBuffer(d.view).then(function(buffer) {
                        var book = XLSX.read(new Uint8Array(buffer), { type: 'array' });
                        var tabs = '';
                        var panes = '';

                        book.SheetNames.forEach(function(name, i) {
                            var id = 'doc-sheet-' + d.id + '-' + i;
                            tabs += '<li class="nav-item"><a class="nav-link' + (i === 0 ? ' active' : '') +
                                '" data-toggle="tab" href="#' + id + '">' + esc(name) + '</a></li>';
                            panes += '<div class="tab-pane fade' + (i === 0 ? ' show active' : '') +
                                '" id="' + id + '"><div class="doc-sheet-wrap">' +
                                XLSX.utils.sheet_to_html(book.Sheets[name]) + '</div></div>';
                        });

                        $pane.html(
                            (book.SheetNames.length > 1 ?
                                '<ul class="nav nav-tabs doc-sheet-tabs mb-2">' + tabs + '</ul>' : '') +
                            '<div class="tab-content">' + panes + '</div>'
                        );
                    }).catch(function() {
                        $pane.html(fallbackCard(d, '{{ translate('Could not read this file. Download it to open in Excel.') }}'));
                    });
                }).fail(function() {
                    $pane.html(fallbackCard(d, '{{ translate('The spreadsheet renderer could not be loaded.') }}'));
                });
            }

            function show(d) {
                $('.doc-file-row').removeClass('is-active');
                $('.doc-file-row[data-id="' + d.id + '"]').addClass('is-active');

                $('#doc-preview-name').text(d.name);
                $('#doc-preview-meta').text(d.ext + ' · ' + d.size);
                $('#doc-preview-download').attr('href', d.download);
                $head.prop('hidden', false);
                $body.prop('hidden', true);
                $pane.prop('hidden', false);

                if (d.kind === 'pdf') {
                    $pane.html('<iframe id="doc-preview-frame" src="' + d.view + '"></iframe>');
                } else if (d.kind === 'image') {
                    $pane.html('<div class="text-center"><img id="doc-preview-image" src="' + d.view + '" alt=""></div>');
                } else if (d.kind === 'word') {
                    renderWord(d);
                } else if (d.kind === 'sheet') {
                    renderSheet(d);
                } else if (d.kind === 'text') {
                    busy('{{ translate('Loading…') }}');
                    $.get(d.view).done(function(content) {
                        $pane.html($('<pre id="doc-preview-text">').text(
                            typeof content === 'string' ? content : JSON.stringify(content, null, 2)
                        ));
                    }).fail(function() {
                        $pane.html(fallbackCard(d, '{{ translate('Could not read this file.') }}'));
                    });
                } else if (d.kind === 'office') {
                    $pane.html(fallbackCard(d, '{{ translate('This older format cannot be rendered in the browser. Re-save it as .docx or .xlsx, or download it.') }}'));
                } else {
                    $pane.html(fallbackCard(d, '{{ translate('No preview available for this file type.') }}'));
                }
            }

            $(document).on('click', '.doc-file-stop', function(e) {
                e.stopPropagation();
            });

            $(document).on('click', '.doc-file-open', function() {
                show($(this).data());
            });

            $('#doc-preview-close').on('click', function() {
                $('.doc-file-row').removeClass('is-active');
                $pane.prop('hidden', true).empty();
                $head.prop('hidden', true);
                $body.prop('hidden', false);
            });

            // A file-only document with a single attachment opens straight into it.
            @if (!filled($doc->content) && count($doc->files) === 1)
                $('.doc-file-open').first().trigger('click');
            @endif
        })();
    </script>
@endpush
