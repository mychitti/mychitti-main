@extends('front-views.layout')

@section('title', $query ? $query . ' — AI Search | My Chitti' : 'AI Search | My Chitti')
  
@section('content')
<style>  
    .aisr-wrap { max-width: 860px; margin: 0 auto; padding: 18px 16px 60px; }
    .aisr-q { font-size: 14px; color: #6b7280; margin-bottom: 4px; }
    .aisr-q b { color: #1d2a12; }
    .aisr-answer { background: #f3f9ea; border: 1px solid #d5ecb3; border-left: 4px solid #81c408;
        border-radius: 12px; padding: 14px 16px; margin: 14px 0 22px; font-size: 15.5px; line-height: 1.6; color: #24331a; }
    .aisr-answer .aisr-answer-badge { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700;
        color:#5a9e00; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
    .aisr-count { font-size: 13px; color: #6b7280; margin-bottom: 12px; }
    .aisr-card { display: flex; gap: 14px; background: #fff; border: 1px solid #eceff2; border-radius: 14px;
        padding: 14px; margin-bottom: 12px; transition: box-shadow .15s, transform .15s; text-decoration:none; color:inherit; }
    .aisr-card:hover { box-shadow: 0 10px 26px -14px rgba(20,32,47,.28); transform: translateY(-1px); }
    .aisr-logo { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; flex: none; background:#f2f4f6; }
    .aisr-body { flex: 1; min-width: 0; }
    .aisr-name { font-weight: 700; font-size: 16px; color: #14202f; margin: 0 0 2px; }
    .aisr-meta { font-size: 12.5px; color: #6b7280; display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .aisr-rating { color:#b8860b; font-weight:600; }
    .aisr-badges { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
    .aisr-badge { font-size: 11px; padding: 3px 8px; border-radius: 999px; background:#eef7ee; color:#2e7d32; white-space:nowrap; }
    .aisr-badge.gst { background:#e8f0fe; color:#1a56db; } .aisr-badge.trusted{ background:#fff4e5; color:#b45309; }
    .aisr-services { font-size: 12.5px; color: #4b5563; margin-top: 7px; }
    .aisr-offer { display:inline-block; font-size:11.5px; font-weight:600; color:#7e22ce; background:#f6ecff;
        border-radius:6px; padding:2px 7px; margin-top:7px; }
    .aisr-cta { flex: none; align-self: center; font-weight:700; font-size:13px; color:#81c408; white-space:nowrap; }
    .aisr-state { text-align:center; color:#6b7280; padding:48px 12px; font-size:15px; }
    .aisr-spin { width:34px;height:34px;border:3px solid #e5e7eb;border-top-color:#81c408;border-radius:50%;
        margin:0 auto 14px; animation:aisrspin .8s linear infinite; }
    @keyframes aisrspin { to { transform: rotate(360deg); } }
    @media (max-width:575px){ .aisr-logo{width:52px;height:52px;} .aisr-cta{display:none;} }
</style>

<div class="aisr-wrap">
    <div id="aisrState" class="aisr-state" style="display:none;"></div>
    <div id="aisrResults" style="display:none;">
        <div class="aisr-q">Results for <b id="aisrQ"></b></div>
        <div id="aisrAnswer" class="aisr-answer" style="display:none;">
            <div class="aisr-answer-badge"><i class="fas fa-wand-magic-sparkles"></i> MyChitti AI</div>
            <div id="aisrAnswerText"></div>
        </div>
        <div id="aisrCount" class="aisr-count"></div>
        <div id="aisrCards"></div>
    </div>
</div>

<script>
    (function () {
        var QUERY = @json($query);
        var stateEl = document.getElementById('aisrState');
        var resultsEl = document.getElementById('aisrResults');

        function showState(html) { stateEl.style.display = 'block'; stateEl.innerHTML = html; resultsEl.style.display = 'none'; }
        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

        if (!QUERY) { showState('Type what you need in the AI search bar above to get started.'); return; }

        document.getElementById('aisrQ').textContent = QUERY;
        showState('<div class="aisr-spin"></div>Finding the best local vendors for you…');

        fetch('{{ url('api/v1/ai-search') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ query: QUERY })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data || !data.success) { showState('Something went wrong. Please try again.'); return; }
            var results = data.results || [];
            if (!results.length) {
                showState('No matching vendors found for “' + esc(QUERY) + '”. Try rephrasing, or search by category above.');
                return;
            }
            stateEl.style.display = 'none';
            resultsEl.style.display = 'block';

            if (data.answer) {
                document.getElementById('aisrAnswer').style.display = 'block';
                document.getElementById('aisrAnswerText').textContent = data.answer;
            }
            document.getElementById('aisrCount').textContent = results.length + ' vendor' + (results.length > 1 ? 's' : '') + ' found';

            var html = results.map(function (s) {
                var badges = (s.badges || []).map(function (b) {
                    var cls = b.key === 'gst' ? 'gst' : (b.key === 'trusted' ? 'trusted' : '');
                    return '<span class="aisr-badge ' + cls + '">' + esc(b.label) + '</span>';
                }).join('');
                var services = (s.services || []).slice(0, 3).join(' · ');
                var meta = [];
                if (s.city) meta.push(esc(s.city));
                if (s.rating) meta.push('<span class="aisr-rating">★ ' + s.rating + (s.rating_count ? ' (' + s.rating_count + ')' : '') + '</span>');
                if (s.distance_km != null) meta.push('~' + s.distance_km + ' km');
                var offer = (s.offers && s.offers.length) ? '<div class="aisr-offer">🎁 ' + esc(s.offers[0]) + '</div>' : '';
                var logo = s.logo
                    ? '{{ asset('storage/app/public/store') }}/' + esc(s.logo)
                    : '{{ asset('public/assets/admin/img/160x160/img1.jpg') }}';
                return '<a class="aisr-card" href="' + esc(s.booking_url || '#') + '">' +
                    '<img class="aisr-logo" src="' + logo + '" onerror="this.src=\'{{ asset('public/assets/admin/img/160x160/img1.jpg') }}\'" alt="' + esc(s.name) + '">' +
                    '<div class="aisr-body">' +
                        '<div class="aisr-name">' + esc(s.name) + '</div>' +
                        '<div class="aisr-meta">' + meta.join('<span>·</span>') + '</div>' +
                        (badges ? '<div class="aisr-badges">' + badges + '</div>' : '') +
                        (services ? '<div class="aisr-services">' + esc(services) + '</div>' : '') +
                        offer +
                    '</div>' +
                    '<div class="aisr-cta">View →</div>' +
                '</a>';
            }).join('');
            document.getElementById('aisrCards').innerHTML = html;
        })
        .catch(function () { showState('Could not reach AI Search right now. Please try again in a moment.'); });
    })();
</script>
@endsection
