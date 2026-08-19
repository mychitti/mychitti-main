@extends('layouts.admin.app')
@section('title', 'OPD Clinical Terms')

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-label" style="font-size:22px;"></i></span>
            OPD Clinical Terms
        </h1>
        <span class="text-muted" style="font-size:13px;">
            Read live by every hospital — an edit here reaches them on the next consultation screen.
        </span>
    </div>

    {{-- Category / type picker --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="get" class="d-flex flex-wrap align-items-end" style="gap:12px;">
                <div>
                    <label class="input-label mb-1" style="font-size:12px;">Category</label>
                    <select name="category" class="form-control form-control-sm" style="min-width:280px;"
                            onchange="this.form.submit()">
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="input-label mb-1" style="font-size:12px;">List</label>
                    <select name="type" class="form-control form-control-sm" style="min-width:160px;"
                            onchange="this.form.submit()">
                        <option value="complaint" {{ $type === 'complaint' ? 'selected' : '' }}>Complaint</option>
                        <option value="diagnosis" {{ $type === 'diagnosis' ? 'selected' : '' }}>Diagnosis</option>
                        <option value="treatment" {{ $type === 'treatment' ? 'selected' : '' }}>Treatment</option>
                    </select>
                </div>
                <div class="ml-auto text-muted" style="font-size:12px;">
                    Applies to <b>{{ number_format($storeCount) }}</b>
                    hospital{{ $storeCount == 1 ? '' : 's' }}
                </div>
            </form>
        </div>
    </div>

    <div class="row" style="row-gap:16px;">
        {{-- Add --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header py-2"><h5 class="card-title mb-0">Add terms</h5></div>
                <div class="card-body">
                    <form method="post" action="{{ route('admin.hmis.opd-terms.store') }}">
                        @csrf
                        <input type="hidden" name="category" value="{{ $category }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div class="form-group">
                            <textarea name="names" rows="8" class="form-control" required
                                placeholder="One per line, or comma separated"></textarea>
                            <small class="text-muted">
                                Duplicates are skipped, so pasting a whole list again is safe.
                            </small>
                        </div>
                        <button class="btn btn-primary btn-sm btn-block">Add to this list</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Existing --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ ucfirst($type) }} — {{ $categories[$category] }}</h5>
                    <span class="text-muted" style="font-size:12px;">
                        {{ $terms->count() }} term{{ $terms->count() == 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="card-body p-0">
                    @if ($terms->isEmpty())
                        <div class="text-center text-muted py-5" style="font-size:13px;">
                            Nothing in this list yet. Add terms on the left.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:55%;">Term</th>
                                        <th class="text-center">Shown</th>
                                        <th class="text-right">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($terms as $term)
                                        <tr class="{{ $term->active ? '' : 'text-muted' }}">
                                            <td>
                                                <form method="post" action="{{ route('admin.hmis.opd-terms.update', $term->id) }}"
                                                      class="d-flex align-items-center" style="gap:6px;">
                                                    @csrf
                                                    <input type="hidden" name="active" value="{{ $term->active ? 1 : 0 }}">
                                                    <input type="text" name="name" value="{{ $term->name }}"
                                                           class="form-control form-control-sm" maxlength="150" required>
                                                    <button class="btn btn-sm btn-outline-secondary">Save</button>
                                                </form>
                                            </td>
                                            <td class="text-center">
                                                <form method="post" action="{{ route('admin.hmis.opd-terms.toggle', $term->id) }}">
                                                    @csrf
                                                    <button class="btn btn-sm {{ $term->active ? 'btn-soft-success' : 'btn-soft-secondary' }}">
                                                        {{ $term->active ? 'On' : 'Off' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-right">
                                                <form method="post" action="{{ route('admin.hmis.opd-terms.destroy', $term->id) }}"
                                                      onsubmit="return confirm('Remove this term from the list? Visits already recorded against it keep their text.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-soft-danger"><i class="tio-delete"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer text-muted" style="font-size:12px;">
                    Switching a term <b>Off</b> stops it being offered but keeps it on record — the safer
                    choice when hospitals may already have used it. Hospitals can also hide any term
                    for themselves without affecting this list.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
