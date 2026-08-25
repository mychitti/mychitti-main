{{-- Automation — the three things that reply or send without the vendor doing anything:
     which template each automatic message uses, what the AI Agent may do, and the knowledge
     it answers from. They were three menu items; each was too small to stand on its own and
     fixing one usually meant opening another. --}}
@extends('layouts.vendor.app')

@section('title', translate('Automation'))

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    @include('vendor-views.whatsapp.partials._automation_css')
@endpush

@section('content')
    <div class="content container-fluid wa-page">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-android" style="font-size:22px;"></i></span>
                <span>{{ translate('Automation') }}</span>
            </h1>
            <p class="text-muted mb-0" style="font-size:13px;">
                {{ translate('Everything that answers or sends on your behalf — the templates behind your automatic messages, what your AI Agent is allowed to do, and what it knows.') }}
            </p>
        </div>

        <ul class="nav wa-tabs mb-3" role="tablist"
            style="background:#fff;border:1px solid var(--wa-line);border-radius:14px;">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'automatic' ? 'active' : '' }}" data-toggle="tab" href="#autoAutomatic" role="tab">
                    <i class="tio-message"></i> {{ translate('Automatic Messages') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'chatbot' ? 'active' : '' }}" data-toggle="tab" href="#autoChatbot" role="tab">
                    <i class="tio-android"></i> {{ translate('Chatbot') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'knowledge' ? 'active' : '' }}" data-toggle="tab" href="#autoKnowledge" role="tab">
                    <i class="tio-book-opened"></i> {{ translate('Knowledge') }}
                    <span class="wa-chip badge-soft-{{ $activeDocs ? 'success' : 'secondary' }} ml-1">{{ $activeDocs }}</span>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade {{ $tab === 'automatic' ? 'show active' : '' }}" id="autoAutomatic" role="tabpanel">
                @include('vendor-views.whatsapp.partials._automation_automatic')
            </div>

            <div class="tab-pane fade {{ $tab === 'chatbot' ? 'show active' : '' }}" id="autoChatbot" role="tabpanel">
                @include('vendor-views.whatsapp.partials._automation_chatbot')
            </div>

            <div class="tab-pane fade {{ $tab === 'knowledge' ? 'show active' : '' }}" id="autoKnowledge" role="tabpanel">
                {{-- Counts lived in the old page header; the tab keeps them where they were read. --}}
                <div class="d-flex align-items-center flex-wrap mb-3" style="gap:8px;">
                    <span class="wa-sub mr-auto">
                        {{ translate('What your chatbot knows. Answers are drawn only from documents that are') }} <b>{{ translate('in use') }}</b>.
                    </span>
                    <span class="wa-chip badge-soft-{{ $activeDocs ? 'success' : 'secondary' }}">{{ $activeDocs }} {{ translate('in use') }}</span>
                    <span class="wa-chip badge-soft-secondary">{{ $totalDocs }} / {{ $maxDocs }} {{ translate('documents') }}</span>
                </div>
                @include('vendor-views.whatsapp.partials._automation_knowledge')
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views.whatsapp.partials._automation_js')
    <script>
        // Keep the open tab in the URL so a reload, a save redirect or a shared link comes back
        // to the pane the vendor was on rather than resetting to the first one.
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var map = { '#autoAutomatic': 'automatic', '#autoChatbot': 'chatbot', '#autoKnowledge': 'knowledge' };
            var tab = map[$(e.target).attr('href')];
            if (tab && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }
        });
    </script>
@endpush
