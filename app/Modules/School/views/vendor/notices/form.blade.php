@extends('layouts.vendor.app')
@section('title', $notice ? 'Edit Notice' : 'New Notice')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-comment mr-1"></i> {{ $notice ? 'Edit Notice' : 'New Notice' }}</h1>
        <a href="{{ route('vendor.school.notices.index') }}" class="btn btn-sm btn-outline-secondary"><i class="tio-back-ui"></i> Back</a>
    </div>

    <form action="{{ $notice ? route('vendor.school.notices.update', $notice->id) : route('vendor.school.notices.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3"><div class="card-body">
                    <div class="form-group">
                        <label class="input-label">Title *</label>
                        <input name="title" class="form-control" value="{{ old('title', $notice?->title) }}" maxlength="190" required>
                        @error('title')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label class="input-label">Message</label>
                        <textarea name="body" class="form-control" rows="8">{{ old('body', $notice?->body) }}</textarea>
                    </div>
                </div></div>
            </div>

            <div class="col-lg-4">
                <div class="card"><div class="card-body">
                    @if($canChooseBranch)
                        @php $scopeDefault = old('branch_id', $notice?->branch_id ?? school_active_branch_id()); @endphp
                        <div class="form-group">
                            <label class="input-label">Scope</label>
                            <select name="branch_id" class="form-control js-select2-custom">
                                <option value="0" @selected(!$scopeDefault)>All Branches (store-wide)</option>
                                @foreach($branches as $b)<option value="{{ $b->id }}" @selected((int)$scopeDefault === (int)$b->id)>{{ $b->name }}</option>@endforeach
                            </select>
                            <small class="text-muted">Store-wide notices show in every branch.</small>
                        </div>
                    @endif
                    <div class="form-group">
                        <label class="input-label">Audience *</label>
                        <select name="audience" id="audience" class="form-control js-select2-custom">
                            @foreach($audiences as $k => $v)<option value="{{ $k }}" @selected(old('audience', $notice?->audience ?? 'all')===$k)>{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group" id="classWrap">
                        <label class="input-label">For Class (optional)</label>
                        <select name="school_class_id" class="form-control js-select2-custom">
                            <option value="">All classes</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id', $notice?->school_class_id)==$c->id)>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">Notice Date</label>
                        <input type="date" name="notice_date" class="form-control" value="{{ old('notice_date', $notice?->notice_date?->format('Y-m-d') ?? now()->toDateString()) }}">
                    </div>
                    <div class="form-group">
                        <label class="input-label">Expires On (optional)</label>
                        <input type="date" name="expires_on" class="form-control" value="{{ old('expires_on', $notice?->expires_on?->format('Y-m-d')) }}">
                    </div>
                    <div class="custom-control custom-switch mb-2">
                        <input type="checkbox" class="custom-control-input" id="is_published" name="is_published" value="1" @checked(old('is_published', $notice?->is_published ?? true))>
                        <label class="custom-control-label" for="is_published">Published</label>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_pinned" name="is_pinned" value="1" @checked(old('is_pinned', $notice?->is_pinned ?? false))>
                        <label class="custom-control-label" for="is_pinned">Pin to top</label>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button class="btn btn--primary"><i class="tio-save"></i> {{ $notice ? 'Update' : 'Publish' }} Notice</button>
                </div></div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
(function(){
    var aud = document.getElementById('audience');
    var wrap = document.getElementById('classWrap');
    function toggle(){ wrap.style.display = (aud.value === 'students' || aud.value === 'parents') ? '' : 'none'; }
    aud.addEventListener('change', toggle); toggle();
})();
</script>
@endpush
