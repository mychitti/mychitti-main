@extends('layouts.vendor.app')

@section('title', $pageTitle ?? 'AI Assistant')

@section('content')
<div class="content container-fluid"> 
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-magic-wand"></i> {{ $pageTitle ?? 'AI Assistant' }} <small class="text-muted">by {{ $agent ?? 'Sam' }}</small></h1>
        <p class="mb-0">{{ $subtitle ?? '' }}</p>
    </div>
 
    <div class="row">
        @foreach ($tools as $key => $t)
            <div class="col-lg-4 mb-3">
                <div class="card h-100 mc-ai-tool" data-tool="{{ $key }}">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="{{ $t['icon'] }} text-primary"></i> {{ $t['title'] }}</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="text-muted small">{{ $t['desc'] }}</p>
                        <textarea class="form-control mc-ai-input mb-2" rows="4" placeholder="{{ $t['ph'] }}"></textarea>
                        <button type="button" class="btn btn-primary btn-sm mc-ai-generate">
                            <i class="tio-magic-wand"></i> Generate
                        </button>

                        <div class="mc-ai-output mt-3" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Result</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary mc-ai-copy">
                                    <i class="tio-copy"></i> Copy
                                </button>
                            </div>
                            <div class="mc-ai-text border rounded p-2 bg-light" style="white-space:pre-wrap; font-size:13.5px; max-height:320px; overflow:auto;"></div>
                            <small class="text-muted d-block mt-1" style="font-size:11px;">
                                AI can make mistakes. Review before you publish or send this.
                            </small>
                        </div>
                        <div class="mc-ai-error text-danger small mt-2" style="display:none;"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('script_2')
<script>
    (function () {
        var url = '{{ route('vendor.ai-tools.generate') }}';
        var csrf = '{{ csrf_token() }}';

        document.querySelectorAll('.mc-ai-tool').forEach(function (card) {
            var tool    = card.getAttribute('data-tool');
            var input   = card.querySelector('.mc-ai-input');
            var btn     = card.querySelector('.mc-ai-generate');
            var outWrap = card.querySelector('.mc-ai-output');
            var outText = card.querySelector('.mc-ai-text');
            var errEl   = card.querySelector('.mc-ai-error');
            var copyBtn = card.querySelector('.mc-ai-copy');

            btn.addEventListener('click', function () {
                var val = (input.value || '').trim();
                errEl.style.display = 'none';
                if (!val) { errEl.textContent = 'Please enter some details first.'; errEl.style.display = 'block'; return; }

                var orig = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="tio-sync tio-spin"></i> Generating…';

                var status = 0;
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ tool: tool, input: val })
                })
                .then(function (r) { status = r.status; return r.text(); })
                .then(function (body) {
                    var data = null;
                    try { data = JSON.parse(body); } catch (e) {}
                    if (data && data.success) {
                        outText.textContent = data.text;
                        outWrap.style.display = 'block';
                        return;
                    }
                    var msg;
                    if (data && data.message) {
                        msg = data.message;
                    } else if (status === 419) {
                        msg = 'Your session expired — please refresh the page and try again.';
                    } else if (status === 404) {
                        msg = 'Feature not available yet (route missing). Please contact support.';
                    } else if (status === 401 || status === 302) {
                        msg = 'You are logged out — please log in again.';
                    } else {
                        msg = 'Could not generate (error ' + status + '). Please try again.';
                    }
                    errEl.textContent = msg;
                    errEl.style.display = 'block';
                })
                .catch(function () { errEl.textContent = 'Could not reach the server. Check your connection and try again.'; errEl.style.display = 'block'; })
                .finally(function () { btn.disabled = false; btn.innerHTML = orig; });
            });

            copyBtn.addEventListener('click', function () {
                var t = outText.textContent || '';
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(t).then(function () {
                        copyBtn.innerHTML = '<i class="tio-done"></i> Copied';
                        setTimeout(function () { copyBtn.innerHTML = '<i class="tio-copy"></i> Copy'; }, 1500);
                    });
                }
            });
        });
    })();
</script>
@endpush
