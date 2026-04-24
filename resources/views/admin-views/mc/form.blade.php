@extends('layouts.admin.app')

@section('title', $campaign ? 'Edit Campaign' : 'New Campaign')

@push('css_or_js')
<script src="{{ asset('public/assets/admin/ckeditor/ckeditor.js') }}"></script>
<style>
.mc-layout { display: flex; min-height: calc(100vh - 64px);margin: 25px;
      border-radius: 10px;
    box-shadow: 0px 1px 19px #e9e9e9; }
.mc-sidebar {
        border-radius: 10px;
    width: 220px; min-width: 220px; background: #f8f9fa;
    border-right: 1px solid #e7eaf3; padding: 1.5rem 0;
    position: sticky; top: 64px; height: calc(100vh - 64px);
    overflow-y: auto;
}
.mc-sidebar .mc-section-group { margin-bottom: 1.5rem; }
.mc-sidebar .mc-section-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    color: #97a4af; padding: 0 1rem 0.4rem;
}
.mc-sidebar a {
    display: flex; align-items: center; gap: 8px;
    padding: 0.45rem 1rem; font-size: 13.5px; color: #3d4451;
    text-decoration: none; border-radius: 0; transition: background .15s;
}
.mc-sidebar a:hover { background: #eef0f5; color: #377dff; }
.mc-sidebar a.active { background: #fff; color: #377dff; font-weight: 600;
    border-right: 3px solid #377dff; }
.mc-sidebar a i { font-size: 15px; color: #8c98a4; }
.mc-sidebar a.active i { color: #377dff; }
.mc-main { flex: 1; padding: 2rem; overflow-x: hidden; }
.mc-topbar {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 1.5rem; flex-wrap: wrap; gap: .75rem;
}
.mc-topbar h4 { margin: 0; font-size: 1rem; font-weight: 600; }
.mc-section { display: none; }
.mc-section.active { display: block; }
.mc-section-title {
    font-size: .8rem; font-weight: 700; text-transform: uppercase;
    color: #8c98a4; letter-spacing: .04em; margin-bottom: 1.25rem;
}
/* Inline table add-row */
.mc-inline-table th { font-size: 12px; }
.mc-add-row-form { background: #f8f9fa; border: 1px dashed #d1d5dc; border-radius: 8px; padding: 1rem; margin-top: .75rem; display: none; }
.mc-add-row-form.show { display: block; }
/* Progress bar */
.mc-progress { height: 8px; border-radius: 4px; }
/* Badge tiers */
.tier-title   { background:#FFD700;color:#333; }
.tier-gold    { background:#FFA500;color:#fff; }
.tier-silver  { background:#A8A9AD;color:#fff; }
.tier-bronze  { background:#CD7F32;color:#fff; }
.tier-partner { background:#6c757d;color:#fff; }
</style>
@endpush

@section('content')
@php $id = $campaign?->id; @endphp

<div class="mc-layout">

    {{-- ── LEFT SIDEBAR ──────────────────────────────────────────────── --}}
    <nav class="mc-sidebar">
        <div class="mc-section-group">
            <div class="mc-section-label">Setup</div>
            <a href="#" data-section="basic"   class="mc-nav active"><i class="tio-home-outlined"></i> Basic info</a>
            <a href="#" data-section="content"  class="mc-nav"><i class="tio-document-text-outlined"></i> Page content</a>
            <a href="#" data-section="howto"    class="mc-nav"><i class="tio-help-outlined"></i> How it works</a>
            <a href="#" data-section="faq"      class="mc-nav"><i class="tio-help-outlined"></i> FAQ</a>
            <a href="#" data-section="terms"    class="mc-nav"><i class="tio-document-text-outlined"></i> Terms &amp; conditions</a>
        </div>
        <div class="mc-section-group">
            <div class="mc-section-label">SEO</div>
            <a href="#" data-section="seo"     class="mc-nav"><i class="tio-search"></i> SEO &amp; meta</a>
        </div>
        <div class="mc-section-group">
            <div class="mc-section-label">Campaign</div>
            <a href="#" data-section="winners"  class="mc-nav"><i class="tio-star-outlined"></i> Results &amp; winners</a>
            <a href="#" data-section="settings" class="mc-nav"><i class="tio-settings-outlined"></i> Settings</a>
        </div>
        <div class="mc-section-group">
            <div class="mc-section-label">Partnerships</div>
            <a href="#" data-section="sponsors"      class="mc-nav"><i class="tio-label-outlined"></i> Sponsors</a>
            <a href="#" data-section="influencers"   class="mc-nav"><i class="tio-user-outlined"></i> Influencers</a>
            <a href="#" data-section="guests"        class="mc-nav"><i class="tio-group-junior"></i> Guests</a>
            <a href="#" data-section="collabs"       class="mc-nav"><i class="tio-user-add"></i> Collaborations</a>
        </div>
        <div class="mc-section-group">
            <div class="mc-section-label">Internal</div>
            <a href="#" data-section="budget"   class="mc-nav"><i class="tio-insurance"></i> Budget plan</a>
            <a href="#" data-section="expenses" class="mc-nav"><i class="tio-receipt-outlined"></i> Expenses</a>
            <a href="#" data-section="targets"  class="mc-nav"><i class="tio-chart-bar-3"></i> Targets 🎯</a>
            <a href="#" data-section="team"     class="mc-nav"><i class="tio-group-add"></i> Team</a>
            <a href="#" data-section="plan"     class="mc-nav"><i class="tio-calendar"></i> Plan</a>
        </div>
    </nav>

    {{-- ── MAIN CONTENT ──────────────────────────────────────────────── --}}
    <div class="mc-main">

        {{-- Top bar --}}
        <div class="mc-topbar">
            <div>
                <h4>{{ $campaign ? $campaign->name : 'New campaign' }}</h4>
                @if($campaign)
                <span class="badge badge-soft-{{ ['draft'=>'secondary','active'=>'success','ended'=>'info','cancelled'=>'danger'][$campaign->status] ?? 'dark' }}">
                    {{ ucfirst($campaign->status) }}
                </span>
                @endif
            </div>
            <div class="d-flex gap-2">
                @if($campaign)
                    <button form="mc-form" name="status" value="draft" class="btn btn-outline-secondary btn-sm px-3">Save draft</button>
                    <button form="mc-form" name="status" value="active" class="btn btn--primary btn-sm px-4">Publish →</button>
                @else
                    <button form="mc-form" name="status" value="draft" class="btn btn-outline-secondary btn-sm px-3">Save draft</button>
                    <button form="mc-form" name="status" value="active" class="btn btn--primary btn-sm px-4">Publish →</button>
                @endif
                <a href="{{ route('admin.mc.index') }}" class="btn btn-light btn-sm">← Back</a>
            </div>
        </div>

        {{-- The shared form (basic info + content sections) --}}
        <form id="mc-form" method="POST"
            action="{{ $campaign ? route('admin.mc.update', $id) : route('admin.mc.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if($campaign) @method('POST') @endif

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SECTION: Basic Info                                      --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div id="sec-basic" class="mc-section active">
                <p class="mc-section-title">Basic info — Core campaign details</p>
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Campaign name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" id="mc-name"
                                    value="{{ old('name', $campaign?->name) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">URL slug</label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted" style="font-size:12px">campaign.yourdomain.com/</span>
                                    <input type="text" name="slug" id="mc-slug" class="form-control"
                                        value="{{ old('slug', $campaign?->slug) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start date &amp; time</label>
                                <input type="datetime-local" name="start_date" class="form-control"
                                    value="{{ old('start_date', $campaign?->start_date?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End date &amp; time</label>
                                <input type="datetime-local" name="end_date" class="form-control"
                                    value="{{ old('end_date', $campaign?->end_date?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Draw date</label>
                                <input type="date" name="draw_date" class="form-control"
                                    value="{{ old('draw_date', $campaign?->draw_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    @foreach(['draft','active','ended','cancelled'] as $s)
                                        <option value="{{ $s }}" @selected(old('status', $campaign?->status) === $s)>
                                            {{ ucfirst($s) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">
                        <p class="mc-section-title">Prize details</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Prize title</label>
                                <input type="text" name="prize_title" class="form-control"
                                    placeholder="e.g. 7-night luxury holiday for two"
                                    value="{{ old('prize_title', $campaign?->prize_title) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prize value</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="prize_value" class="form-control" step="0.01"
                                        placeholder="0.00"
                                        value="{{ old('prize_value', $campaign?->prize_value) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Number of winners</label>
                                <input type="number" name="number_of_winners" class="form-control" min="1"
                                    value="{{ old('number_of_winners', $campaign?->number_of_winners ?? 1) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Prize description</label>
                                <textarea name="prize_description" class="form-control" rows="3"
                                    placeholder="Short description of the prize shown on the main page...">{{ old('prize_description', $campaign?->prize_description) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max entries per person</label>
                                <input type="number" name="max_entries_per_person" class="form-control" min="1"
                                    value="{{ old('max_entries_per_person', $campaign?->max_entries_per_person ?? 1) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total entry limit</label>
                                <input type="number" name="total_entry_limit" class="form-control"
                                    placeholder="Leave blank for unlimited"
                                    value="{{ old('total_entry_limit', $campaign?->total_entry_limit) }}">
                                <small class="text-muted">Leave blank for unlimited</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <p class="mc-section-title">Campaign image</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Banner image <small class="text-muted">(shown on campaign listing &amp; hero)</small></label>
                                @if($campaign?->banner_image)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/app/public/'.$campaign->banner_image) }}"
                                        alt="Banner" style="max-height:160px; border-radius:8px; border:1px solid #e7eaf3;">
                                </div>
                                @endif
                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SECTION: Page Content                                    --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div id="sec-content" class="mc-section">
                <p class="mc-section-title">Page content — Main campaign page body</p>
                <div class="card">
                    <div class="card-body">
                        <textarea name="page_content" id="ck-page_content" class="form-control" rows="16">{{ old('page_content', $campaign?->page_content) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SECTION: How it works                                    --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div id="sec-howto" class="mc-section">
                <p class="mc-section-title">How it works</p>
                <div class="card">
                    <div class="card-body">
                        <textarea name="how_it_works" id="ck-how_it_works" class="form-control" rows="12">{{ old('how_it_works', $campaign?->how_it_works) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SECTION: FAQ                                             --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div id="sec-faq" class="mc-section">
                <p class="mc-section-title">FAQ</p>
                <div class="card">
                    <div class="card-body">
                        <div id="faq-list">
                            @foreach(old('faqs', $campaign?->faqs?->toArray() ?? []) as $fi => $faq)
                            <div class="faq-item border rounded p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Q{{ $fi + 1 }}</strong>
                                    <button type="button" class="btn btn-sm btn-outline-danger faq-remove">✕</button>
                                </div>
                                <input type="text" name="faqs[{{ $fi }}][question]" class="form-control mb-2"
                                    placeholder="Question" value="{{ $faq['question'] ?? '' }}">
                                <textarea name="faqs[{{ $fi }}][answer]" class="form-control" rows="2"
                                    placeholder="Answer">{{ $faq['answer'] ?? '' }}</textarea>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="faq-add" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="tio-add"></i> Add FAQ
                        </button>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SECTION: Terms                                           --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div id="sec-terms" class="mc-section">
                <p class="mc-section-title">Terms &amp; conditions</p>
                <div class="card">
                    <div class="card-body">
                        <textarea name="terms" id="ck-terms" class="form-control" rows="14">{{ old('terms', $campaign?->terms) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SECTION: SEO                                             --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div id="sec-seo" class="mc-section">
                <p class="mc-section-title">SEO &amp; meta</p>
                <div class="card">
                    <div class="card-body row g-3">
                        <div class="col-12">
                            <label class="form-label">Meta title</label>
                            <input type="text" name="meta_title" class="form-control"
                                value="{{ old('meta_title', $campaign?->meta_title) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Meta description</label>
                            <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $campaign?->meta_description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">OG / Social image</label>
                            @if($campaign?->og_image)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/'.$campaign->og_image) }}" style="max-height:100px;border-radius:6px">
                                </div>
                            @endif
                            <input type="file" name="og_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SECTION: Settings                                        --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div id="sec-settings" class="mc-section">
                <p class="mc-section-title">Settings</p>
                <div class="card">
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="require_login" value="1" id="rl"
                                {{ old('require_login', $campaign?->require_login ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="rl">Require login to enter</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_entry_count" value="1" id="sec"
                                {{ old('show_entry_count', $campaign?->show_entry_count) ? 'checked' : '' }}>
                            <label class="form-check-label" for="sec">Show public entry count</label>
                        </div>
                    </div>
                </div>
            </div>

        </form>{{-- end main form --}}

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Results & Winners (AJAX)                           --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-winners" class="mc-section">
            <p class="mc-section-title">Results &amp; Winners</p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    <table class="table table-sm mc-inline-table">
                        <thead><tr><th>#</th><th>Winner</th><th>Position</th><th>Prize</th><th>Drawn</th><th></th></tr></thead>
                        <tbody id="winners-tbody">
                        @foreach($campaign->winners as $w)
                        <tr id="winner-row-{{ $w->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $w->winner_name }}</td>
                            <td>{{ $w->position }}</td>
                            <td>{{ $w->prize_detail }}</td>
                            <td>{{ $w->drawn_at?->format('d M Y') }}</td>
                            <td><button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.winners.destroy', [$id, $w->id]) }}"
                                data-target="winner-row-{{ $w->id }}">✕</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('winners-form')">+ Add Winner</button>
                    <div id="winners-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-4"><input type="text" class="form-control" id="w-name" placeholder="Winner name"></div>
                            <div class="col-md-2"><input type="number" class="form-control" id="w-pos" placeholder="Position" value="1"></div>
                            <div class="col-md-3"><input type="text" class="form-control" id="w-prize" placeholder="Prize detail"></div>
                            <div class="col-md-3"><input type="date" class="form-control" id="w-date"></div>
                        </div>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.winners.store', $id) }}',
                                {winner_name:$('#w-name').val(),position:$('#w-pos').val(),prize_detail:$('#w-prize').val(),drawn_at:$('#w-date').val()},
                                'winners-tbody', winnerRow, 'winners-form')">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first to add winners.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Sponsors                                            --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-sponsors" class="mc-section">
            <p class="mc-section-title">Sponsors</p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    <table class="table table-sm mc-inline-table">
                        <thead><tr><th>Name</th><th>Tier</th><th>Website</th><th></th></tr></thead>
                        <tbody id="sponsors-tbody">
                        @foreach($campaign->sponsors as $s)
                        <tr id="sponsor-row-{{ $s->id }}">
                            <td>
                                @if($s->logo)<img src="{{ asset('storage/'.$s->logo) }}" height="28" class="mr-1">@endif
                                {{ $s->name }}
                            </td>
                            <td><span class="badge tier-{{ $s->tier }}">{{ ucfirst($s->tier) }}</span></td>
                            <td>{{ $s->website }}</td>
                            <td><button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.sponsors.destroy', [$id, $s->id]) }}"
                                data-target="sponsor-row-{{ $s->id }}">✕</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('sponsors-form')">+ Add Sponsor</button>
                    <div id="sponsors-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-4"><input type="text" class="form-control" id="sp-name" placeholder="Sponsor name"></div>
                            <div class="col-md-3">
                                <select class="form-control" id="sp-tier">
                                    @foreach(['title','gold','silver','bronze','partner'] as $t)
                                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5"><input type="text" class="form-control" id="sp-site" placeholder="Website URL"></div>
                        </div>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.sponsors.store', $id) }}',
                                {name:$('#sp-name').val(),tier:$('#sp-tier').val(),website:$('#sp-site').val()},
                                'sponsors-tbody', sponsorRow, 'sponsors-form')">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first to add sponsors.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Influencers                                         --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-influencers" class="mc-section">
            <p class="mc-section-title">Influencers</p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    <table class="table table-sm mc-inline-table">
                        <thead><tr><th>Name</th><th>Handle</th><th>Platform</th><th>Followers</th><th>Rate</th><th></th></tr></thead>
                        <tbody id="influencers-tbody">
                        @foreach($campaign->influencers as $inf)
                        <tr id="influencer-row-{{ $inf->id }}">
                            <td>{{ $inf->name }}</td>
                            <td>{{ $inf->handle }}</td>
                            <td><span class="badge badge-soft-primary">{{ ucfirst($inf->platform) }}</span></td>
                            <td>{{ $inf->followers }}</td>
                            <td>{{ $inf->agreed_rate ? '₹'.number_format($inf->agreed_rate,2) : '—' }}</td>
                            <td><button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.influencers.destroy', [$id, $inf->id]) }}"
                                data-target="influencer-row-{{ $inf->id }}">✕</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('influencers-form')">+ Add Influencer</button>
                    <div id="influencers-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-3"><input type="text" class="form-control" id="inf-name" placeholder="Name"></div>
                            <div class="col-md-3"><input type="text" class="form-control" id="inf-handle" placeholder="@handle"></div>
                            <div class="col-md-2">
                                <select class="form-control" id="inf-platform">
                                    @foreach(['instagram','youtube','twitter','tiktok','facebook','other'] as $p)
                                    <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2"><input type="text" class="form-control" id="inf-followers" placeholder="Followers"></div>
                            <div class="col-md-2"><input type="number" class="form-control" id="inf-rate" placeholder="Rate ₹"></div>
                        </div>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.influencers.store', $id) }}',
                                {name:$('#inf-name').val(),handle:$('#inf-handle').val(),platform:$('#inf-platform').val(),followers:$('#inf-followers').val(),agreed_rate:$('#inf-rate').val()},
                                'influencers-tbody', influencerRow, 'influencers-form')">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first to add influencers.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Guests                                              --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-guests" class="mc-section">
            <p class="mc-section-title">Guests</p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    <table class="table table-sm mc-inline-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Type</th><th></th></tr></thead>
                        <tbody id="guests-tbody">
                        @foreach($campaign->guests as $g)
                        <tr id="guest-row-{{ $g->id }}">
                            <td>{{ $g->name }}</td><td>{{ $g->email }}</td>
                            <td>{{ $g->phone }}</td>
                            <td><span class="badge badge-soft-info">{{ ucfirst($g->type) }}</span></td>
                            <td><button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.guests.destroy', [$id, $g->id]) }}"
                                data-target="guest-row-{{ $g->id }}">✕</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('guests-form')">+ Add Guest</button>
                    <div id="guests-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-3"><input type="text" class="form-control" id="g-name" placeholder="Name"></div>
                            <div class="col-md-3"><input type="email" class="form-control" id="g-email" placeholder="Email"></div>
                            <div class="col-md-2"><input type="text" class="form-control" id="g-phone" placeholder="Phone"></div>
                            <div class="col-md-2">
                                <select class="form-control" id="g-type">
                                    @foreach(['vip','general','press','complimentary'] as $t)
                                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.guests.store', $id) }}',
                                {name:$('#g-name').val(),email:$('#g-email').val(),phone:$('#g-phone').val(),type:$('#g-type').val()},
                                'guests-tbody', guestRow, 'guests-form')">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first to add guests.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Collaborations                                      --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-collabs" class="mc-section">
            <p class="mc-section-title">Collaborations</p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    <table class="table table-sm mc-inline-table">
                        <thead><tr><th>Partner</th><th>Type</th><th>Status</th><th>Notes</th><th></th></tr></thead>
                        <tbody id="collabs-tbody">
                        @foreach($campaign->collaborations as $col)
                        <tr id="collab-row-{{ $col->id }}">
                            <td>{{ $col->partner_name }}</td><td>{{ $col->type }}</td>
                            <td><span class="badge badge-soft-{{ ['pending'=>'warning','active'=>'success','completed'=>'info','cancelled'=>'danger'][$col->status] }}">{{ ucfirst($col->status) }}</span></td>
                            <td>{{ Str::limit($col->notes, 40) }}</td>
                            <td><button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.collaborations.destroy', [$id, $col->id]) }}"
                                data-target="collab-row-{{ $col->id }}">✕</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('collabs-form')">+ Add Collaboration</button>
                    <div id="collabs-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-4"><input type="text" class="form-control" id="cl-partner" placeholder="Partner name"></div>
                            <div class="col-md-3"><input type="text" class="form-control" id="cl-type" placeholder="Type (e.g. media, brand)"></div>
                            <div class="col-md-3">
                                <select class="form-control" id="cl-status">
                                    @foreach(['pending','active','completed','cancelled'] as $s)
                                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <textarea class="form-control mt-2" id="cl-notes" rows="2" placeholder="Notes / terms"></textarea>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.collaborations.store', $id) }}',
                                {partner_name:$('#cl-partner').val(),type:$('#cl-type').val(),status:$('#cl-status').val(),notes:$('#cl-notes').val()},
                                'collabs-tbody', collabRow, 'collabs-form')">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first to add collaborations.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Budget Plan                                         --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-budget" class="mc-section">
            <p class="mc-section-title">Budget Plan <span class="text-muted" style="font-size:11px">(Internal)</span></p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    @php $budgetTotal = $campaign->budgetItems->sum('budgeted_amount'); $actualTotal = $campaign->budgetItems->sum('actual_amount'); @endphp
                    <div class="d-flex gap-4 mb-3">
                        <div class="text-center"><div class="h4 mb-0 text-primary">₹{{ number_format($budgetTotal,2) }}</div><small class="text-muted">Total Budgeted</small></div>
                        <div class="text-center"><div class="h4 mb-0 {{ $actualTotal > $budgetTotal ? 'text-danger' : 'text-success' }}">₹{{ number_format($actualTotal,2) }}</div><small class="text-muted">Total Actual</small></div>
                    </div>
                    <table class="table table-sm mc-inline-table">
                        <thead><tr><th>Category</th><th>Item</th><th>Budgeted</th><th>Actual</th><th></th></tr></thead>
                        <tbody id="budget-tbody">
                        @foreach($campaign->budgetItems as $b)
                        <tr id="budget-row-{{ $b->id }}">
                            <td>{{ $b->category }}</td><td>{{ $b->item_description }}</td>
                            <td>₹{{ number_format($b->budgeted_amount,2) }}</td>
                            <td>{{ $b->actual_amount !== null ? '₹'.number_format($b->actual_amount,2) : '—' }}</td>
                            <td><button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.budget.destroy', [$id, $b->id]) }}"
                                data-target="budget-row-{{ $b->id }}">✕</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('budget-form')">+ Add Item</button>
                    <div id="budget-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-3"><input type="text" class="form-control" id="b-cat" placeholder="Category"></div>
                            <div class="col-md-4"><input type="text" class="form-control" id="b-item" placeholder="Item description"></div>
                            <div class="col-md-2"><input type="number" class="form-control" id="b-budgeted" placeholder="Budgeted ₹"></div>
                            <div class="col-md-2"><input type="number" class="form-control" id="b-actual" placeholder="Actual ₹"></div>
                        </div>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.budget.store', $id) }}',
                                {category:$('#b-cat').val(),item_description:$('#b-item').val(),budgeted_amount:$('#b-budgeted').val(),actual_amount:$('#b-actual').val()},
                                'budget-tbody', budgetRow, 'budget-form')">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Expenses                                            --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-expenses" class="mc-section">
            <p class="mc-section-title">Expenses <span class="text-muted" style="font-size:11px">(Internal)</span></p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <span class="h4 text-danger">₹{{ number_format($campaign->expenses->sum('amount'),2) }}</span>
                        <small class="text-muted ml-2">Total Expenses</small>
                    </div>
                    <table class="table table-sm mc-inline-table">
                        <thead><tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th></th></tr></thead>
                        <tbody id="expenses-tbody">
                        @foreach($campaign->expenses as $ex)
                        <tr id="expense-row-{{ $ex->id }}">
                            <td>{{ $ex->title }}</td><td>{{ $ex->category }}</td>
                            <td>₹{{ number_format($ex->amount,2) }}</td>
                            <td>{{ $ex->expense_date?->format('d M Y') }}</td>
                            <td><button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.expenses.destroy', [$id, $ex->id]) }}"
                                data-target="expense-row-{{ $ex->id }}">✕</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('expenses-form')">+ Add Expense</button>
                    <div id="expenses-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-4"><input type="text" class="form-control" id="ex-title" placeholder="Title"></div>
                            <div class="col-md-3"><input type="text" class="form-control" id="ex-cat" placeholder="Category"></div>
                            <div class="col-md-2"><input type="number" class="form-control" id="ex-amount" placeholder="Amount ₹"></div>
                            <div class="col-md-3"><input type="date" class="form-control" id="ex-date"></div>
                        </div>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.expenses.store', $id) }}',
                                {title:$('#ex-title').val(),category:$('#ex-cat').val(),amount:$('#ex-amount').val(),expense_date:$('#ex-date').val()},
                                'expenses-tbody', expenseRow, 'expenses-form')">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Targets                                             --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-targets" class="mc-section">
            <p class="mc-section-title">Targets 🎯 <span class="text-muted" style="font-size:11px">(Internal)</span></p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    @foreach($campaign->targets as $t)
                    <div class="mb-4 p-3 border rounded" id="target-row-{{ $t->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong>{{ $t->metric }}</strong>
                            <button class="btn btn-xs btn-outline-danger mc-del"
                                data-url="{{ route('admin.mc.targets.destroy', [$id, $t->id]) }}"
                                data-target="target-row-{{ $t->id }}">✕</button>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="text-muted">{{ number_format($t->current_value) }} / {{ number_format($t->target_value) }} {{ $t->unit }}</span>
                            <span class="badge badge-soft-primary" id="prog-badge-{{ $t->id }}">{{ $t->progress }}%</span>
                        </div>
                        <div class="progress mc-progress mb-2">
                            <div class="progress-bar bg-primary" id="prog-bar-{{ $t->id }}"
                                style="width:{{ $t->progress }}%"></div>
                        </div>
                        <div class="input-group input-group-sm" style="max-width:220px">
                            <input type="number" class="form-control" id="cur-{{ $t->id }}" value="{{ $t->current_value }}" placeholder="Current value">
                            <button class="btn btn-outline-primary"
                                onclick="updateTarget('{{ route('admin.mc.targets.update', [$id, $t->id]) }}', {{ $t->id }})">
                                Update
                            </button>
                        </div>
                    </div>
                    @endforeach
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleForm('targets-form')">+ Add Target</button>
                    <div id="targets-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-4"><input type="text" class="form-control" id="t-metric" placeholder="Metric (e.g. Total Entries)"></div>
                            <div class="col-md-3"><input type="number" class="form-control" id="t-target" placeholder="Target value"></div>
                            <div class="col-md-2"><input type="text" class="form-control" id="t-unit" placeholder="Unit (e.g. entries)"></div>
                        </div>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.targets.store', $id) }}',
                                {metric:$('#t-metric').val(),target_value:$('#t-target').val(),current_value:0,unit:$('#t-unit').val()},
                                null, null, 'targets-form', function(){ location.reload(); })">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Team                                                --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-team" class="mc-section">
            <p class="mc-section-title">Organisation Team <span class="text-muted" style="font-size:11px">(Internal)</span></p>
            @if($campaign)
            <div class="card">
                <div class="card-body">
                    <div class="row g-3" id="team-cards">
                    @foreach($campaign->teamMembers as $tm)
                    <div class="col-md-4" id="team-row-{{ $tm->id }}">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-0">{{ $tm->member_name }}</h6>
                                        <small class="badge badge-soft-primary">{{ $tm->role }}</small>
                                    </div>
                                    <button class="btn btn-xs btn-outline-danger mc-del"
                                        data-url="{{ route('admin.mc.team.destroy', [$id, $tm->id]) }}"
                                        data-target="team-row-{{ $tm->id }}">✕</button>
                                </div>
                                @if($tm->responsibilities)<p class="mt-2 mb-1 small text-muted">{{ $tm->responsibilities }}</p>@endif
                                @if($tm->phone)<small><i class="tio-phone"></i> {{ $tm->phone }}</small>@endif
                                @if($tm->email)<br><small><i class="tio-email"></i> {{ $tm->email }}</small>@endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                    </div>
                    <button class="btn btn-sm btn-outline-primary mt-3" onclick="toggleForm('team-form')">+ Add Member</button>
                    <div id="team-form" class="mc-add-row-form">
                        <div class="row g-2">
                            <div class="col-md-4"><input type="text" class="form-control" id="tm-name" placeholder="Name"></div>
                            <div class="col-md-3"><input type="text" class="form-control" id="tm-role" placeholder="Role"></div>
                            <div class="col-md-2"><input type="text" class="form-control" id="tm-phone" placeholder="Phone"></div>
                            <div class="col-md-3"><input type="email" class="form-control" id="tm-email" placeholder="Email"></div>
                        </div>
                        <textarea class="form-control mt-2" id="tm-resp" rows="2" placeholder="Responsibilities"></textarea>
                        <button class="btn btn-sm btn--primary mt-2"
                            onclick="ajaxAdd('{{ route('admin.mc.team.store', $id) }}',
                                {member_name:$('#tm-name').val(),role:$('#tm-role').val(),phone:$('#tm-phone').val(),email:$('#tm-email').val(),responsibilities:$('#tm-resp').val()},
                                null, null, 'team-form', function(){ location.reload(); })">Save</button>
                    </div>
                </div>
            </div>
            @else <div class="alert alert-info">Save the campaign first.</div> @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SECTION: Plan                                                --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div id="sec-plan" class="mc-section">
            <p class="mc-section-title">Campaign Plan <span class="text-muted" style="font-size:11px">(Internal)</span></p>
            <div class="card">
                <div class="card-body">
                    <p class="text-muted small mb-2">Internal planning notes, timelines, tasks, and strategy. Not shown publicly.</p>
                    <form method="POST"
                        action="{{ $campaign ? route('admin.mc.update', $id) : route('admin.mc.store') }}"
                        id="plan-form">
                        @csrf
                        @if($campaign) @method('POST') @endif
                        <input type="hidden" name="name" value="{{ $campaign?->name ?? 'Draft' }}">
                        <input type="hidden" name="status" value="{{ $campaign?->status ?? 'draft' }}">
                        <textarea name="plan_notes" class="form-control" rows="18"
                            placeholder="Write your campaign plan here...">{{ $campaign?->plan_notes }}</textarea>
                        <button type="submit" class="btn btn--primary mt-3">Save Plan</button>
                    </form>
                </div>
            </div>
        </div>

    </div>{{-- end mc-main --}}
</div>{{-- end mc-layout --}}

@push('css_or_js')
<script>
// ── Global constants (needed by inline onclick handlers) ────────────────────
const CSRF = '{{ csrf_token() }}';
const MC_ID = {{ $campaign ? $campaign->id : 'null' }};
const DEL_BASE = {
    winner:      MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/winners") : "" }}' : '',
    sponsor:     MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/sponsors") : "" }}' : '',
    influencer:  MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/influencers") : "" }}' : '',
    guest:       MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/guests") : "" }}' : '',
    collab:      MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/collaborations") : "" }}' : '',
    budget:      MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/budget") : "" }}' : '',
    expense:     MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/expenses") : "" }}' : '',
    target:      MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/targets") : "" }}' : '',
    team:        MC_ID ? '{{ $campaign ? url("admin/mc/{$campaign->id}/team") : "" }}' : '',
};

// ── Toggle add-row forms (called from inline onclick) ───────────────────────
function toggleForm(id) {
    const el = document.getElementById(id);
    el?.classList.toggle('show');
}

// ── Generic AJAX add (called from inline onclick) ───────────────────────────
function ajaxAdd(url, data, tbodyId, rowFn, formId, callback) {
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) { alert('Error saving.'); return; }
        if (tbodyId && rowFn) {
            document.getElementById(tbodyId).insertAdjacentHTML('beforeend', rowFn(res.data));
        }
        document.getElementById(formId)?.classList.remove('show');
        if (callback) callback(res);
    })
    .catch(() => alert('Request failed.'));
}

// ── Update target progress (called from inline onclick) ─────────────────────
function updateTarget(url, tid) {
    const val = document.getElementById('cur-' + tid).value;
    fetch(url, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ current_value: val }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('prog-bar-' + tid).style.width = res.progress + '%';
            document.getElementById('prog-badge-' + tid).textContent = res.progress + '%';
        }
    });
}

// ── Row templates (called from ajaxAdd callback) ────────────────────────────
function winnerRow(d) {
    return `<tr id="winner-row-${d.id}"><td>—</td><td>${d.winner_name}</td><td>${d.position}</td><td>${d.prize_detail||'—'}</td><td>${d.drawn_at||'—'}</td>
    <td><button class="btn btn-xs btn-outline-danger mc-del" data-url="${DEL_BASE.winner}/${d.id}" data-target="winner-row-${d.id}">✕</button></td></tr>`;
}
function sponsorRow(d) {
    return `<tr id="sponsor-row-${d.id}"><td>${d.name}</td><td><span class="badge tier-${d.tier}">${d.tier}</span></td><td>${d.website||'—'}</td>
    <td><button class="btn btn-xs btn-outline-danger mc-del" data-url="${DEL_BASE.sponsor}/${d.id}" data-target="sponsor-row-${d.id}">✕</button></td></tr>`;
}
function influencerRow(d) {
    return `<tr id="influencer-row-${d.id}"><td>${d.name}</td><td>${d.handle||''}</td><td>${d.platform}</td><td>${d.followers||'—'}</td><td>${d.agreed_rate?'₹'+d.agreed_rate:'—'}</td>
    <td><button class="btn btn-xs btn-outline-danger mc-del" data-url="${DEL_BASE.influencer}/${d.id}" data-target="influencer-row-${d.id}">✕</button></td></tr>`;
}
function guestRow(d) {
    return `<tr id="guest-row-${d.id}"><td>${d.name}</td><td>${d.email||''}</td><td>${d.phone||''}</td><td><span class="badge badge-soft-info">${d.type}</span></td>
    <td><button class="btn btn-xs btn-outline-danger mc-del" data-url="${DEL_BASE.guest}/${d.id}" data-target="guest-row-${d.id}">✕</button></td></tr>`;
}
function collabRow(d) {
    return `<tr id="collab-row-${d.id}"><td>${d.partner_name}</td><td>${d.type||''}</td><td><span class="badge badge-soft-warning">${d.status}</span></td><td>${d.notes||''}</td>
    <td><button class="btn btn-xs btn-outline-danger mc-del" data-url="${DEL_BASE.collab}/${d.id}" data-target="collab-row-${d.id}">✕</button></td></tr>`;
}
function budgetRow(d) {
    return `<tr id="budget-row-${d.id}"><td>${d.category}</td><td>${d.item_description}</td><td>₹${parseFloat(d.budgeted_amount).toFixed(2)}</td><td>${d.actual_amount?'₹'+parseFloat(d.actual_amount).toFixed(2):'—'}</td>
    <td><button class="btn btn-xs btn-outline-danger mc-del" data-url="${DEL_BASE.budget}/${d.id}" data-target="budget-row-${d.id}">✕</button></td></tr>`;
}
function expenseRow(d) {
    return `<tr id="expense-row-${d.id}"><td>${d.title}</td><td>${d.category||''}</td><td>₹${parseFloat(d.amount).toFixed(2)}</td><td>${d.expense_date||'—'}</td>
    <td><button class="btn btn-xs btn-outline-danger mc-del" data-url="${DEL_BASE.expense}/${d.id}" data-target="expense-row-${d.id}">✕</button></td></tr>`;
}

// ── DOM-dependent initialization ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

// Sidebar navigation
document.querySelectorAll('.mc-nav').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const sec = link.dataset.section;
        document.querySelectorAll('.mc-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.mc-nav').forEach(l => l.classList.remove('active'));
        document.getElementById('sec-' + sec)?.classList.add('active');
        link.classList.add('active');
    });
});

// CKEditor 4 init
['ck-page_content', 'ck-how_it_works', 'ck-terms'].forEach(function(id) {
    if (document.getElementById(id)) {
        CKEDITOR.replace(id, { height: 400 });
    }
});

// Auto-generate slug
const nameEl = document.getElementById('mc-name');
const slugEl = document.getElementById('mc-slug');
if (nameEl && slugEl && !slugEl.value) {
    nameEl.addEventListener('input', () => {
        slugEl.value = nameEl.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    });
}

// FAQ add/remove
let faqCount = {{ count(old('faqs', $campaign?->faqs?->toArray() ?? [])) }};
document.getElementById('faq-add')?.addEventListener('click', () => {
    const div = document.createElement('div');
    div.className = 'faq-item border rounded p-3 mb-2';
    div.innerHTML = `<div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Q${faqCount + 1}</strong>
        <button type="button" class="btn btn-sm btn-outline-danger faq-remove">✕</button></div>
        <input type="text" name="faqs[${faqCount}][question]" class="form-control mb-2" placeholder="Question">
        <textarea name="faqs[${faqCount}][answer]" class="form-control" rows="2" placeholder="Answer"></textarea>`;
    document.getElementById('faq-list').appendChild(div);
    faqCount++;
    div.querySelector('.faq-remove').onclick = () => div.remove();
});
document.querySelectorAll('.faq-remove').forEach(btn => btn.onclick = () => btn.closest('.faq-item').remove());

// Delete rows
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.mc-del');
    if (!btn) return;
    if (!confirm('Remove this item?')) return;
    fetch(btn.dataset.url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(res => { if (res.success) document.getElementById(btn.dataset.target)?.remove(); })
    .catch(() => alert('Delete failed.'));
});

}); // DOMContentLoaded
</script>
@endpush
@endsection
