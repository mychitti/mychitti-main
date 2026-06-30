@isset($branches)
    {{-- Preserve the selected date range when the branch (or other filters in this form) change.
         Skipped when the partial is dropped inside a form that already carries date_range. --}}
    @if (!isset($withDateHidden) || $withDateHidden)
        <input type="hidden" name="date_range" value="{{ request('date_range') }}">
        <input type="hidden" name="custom_date_range" value="{{ request('custom_date_range') }}">
    @endif
    <div style="min-width: 200px !important;">
        <select name="branch_id" onchange="this.form.submit()" data-placeholder="Branch"
            class="js-select2-custom form-select">
            <option value="all" {{ in_array((string) request('branch_id', 'all'), ['', 'all'], true) ? 'selected' : '' }}>All Branches</option>
            <option value="main" {{ in_array((string) request('branch_id'), ['main', '0'], true) ? 'selected' : '' }}>Main Store</option>
            @foreach ($branches as $b)
                <option value="{{ $b->id }}" {{ (string) request('branch_id') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
@endisset
