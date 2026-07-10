@extends('layouts.admin.app')

@section('title', 'Edit SEO Page')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-header-title mb-0">
            {{ $combo->item->name ?? $combo->category->name ?? '' }} in {{ $combo->zone->name ?? '' }}
            @if ($combo->item_id)
                <small class="text-muted">({{ $combo->category->name ?? '' }})</small>
            @endif
        </h1>
        <a href="{{ route('admin.seo-pages.index') }}" class="btn btn-outline-secondary">
            <i class="tio-back-ui"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.seo-pages.update', $combo->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Meta title <span class="text-muted small">(max 70 chars)</span></label>
                            <input type="text" name="meta_title" maxlength="70" class="form-control"
                                   value="{{ old('meta_title', $combo->meta_title) }}">
                        </div>

                        <div class="form-group">
                            <label>Meta description <span class="text-muted small">(max 300 chars)</span></label>
                            <textarea name="meta_description" maxlength="300" rows="2"
                                      class="form-control">{{ old('meta_description', $combo->meta_description) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>H1 heading</label>
                            <input type="text" name="h1" maxlength="160" class="form-control"
                                   value="{{ old('h1', $combo->h1) }}">
                        </div>

                        <div class="form-group">
                            <label>Intro paragraph</label>
                            <textarea name="intro_paragraph" rows="4"
                                      class="form-control">{{ old('intro_paragraph', $combo->intro_paragraph) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Keywords <span class="text-muted small">(one per line or comma-separated, used to guide on-page content — not a meta tag)</span></label>
                            <textarea name="keywords" rows="6"
                                      class="form-control">{{ old('keywords', implode("\n", $combo->keywords ?? [])) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>FAQs</label>
                            <div id="faq-rows">
                                @forelse (($combo->faqs ?? []) as $faq)
                                    <div class="row mb-2">
                                        <div class="col-md-5">
                                            <input type="text" name="faq_q[]" class="form-control" placeholder="Question"
                                                   value="{{ $faq['q'] ?? '' }}">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" name="faq_a[]" class="form-control" placeholder="Answer"
                                                   value="{{ $faq['a'] ?? '' }}">
                                        </div>
                                    </div>
                                @empty
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="row mb-2">
                                            <div class="col-md-5">
                                                <input type="text" name="faq_q[]" class="form-control" placeholder="Question">
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" name="faq_a[]" class="form-control" placeholder="Answer">
                                            </div>
                                        </div>
                                    @endfor
                                @endforelse
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" style="max-width:220px;">
                                <option value="draft" {{ $combo->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $combo->status === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="unpublished" {{ $combo->status === 'unpublished' ? 'selected' : '' }}>Unpublished</option>
                            </select>
                            <small class="form-text text-muted">Only "Published" pages are visible on the storefront and included in the sitemap.</small>
                        </div>

                        <button type="submit" class="btn btn--primary">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Details</h5>
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted">Stores</td><td>{{ $combo->store_count }}</td></tr>
                        <tr><td class="text-muted">URL</td><td><a href="{{ url($combo->slug) }}" target="_blank">/{{ $combo->slug }}</a></td></tr>
                        <tr><td class="text-muted">Model</td><td>{{ $combo->model ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Generated</td><td>{{ $combo->generated_at ? $combo->generated_at->diffForHumans() : 'never' }}</td></tr>
                    </table>

                    <hr>
                    <form action="{{ route('admin.seo-pages.generate', $combo->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-block">
                            <i class="tio-repeat-vertical"></i> Regenerate with AI
                        </button>
                        <small class="form-text text-muted">Overwrites the fields on the left with a fresh AI generation.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
