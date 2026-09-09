{{-- The Brand control: a Select2 backed by the admin brand pool, with free text still allowed.

     Lives in one partial because this field has five renderings — the base vendor form plus a
     copy inside HMIS, Laundry, POS and POS Retail, each of which ResolveModuleViews prepends
     ahead of resources/views for its own business_type. Wiring the base copy alone left every
     hospital, laundry and POS store still typing brands by hand, with nothing on screen to say
     why. The label and column wrapper stay with each caller, so per-module styling is untouched.

     $inputId lets a page that renders this twice (add + edit) keep the ids unique. --}}
@php $bpId = $inputId ?? 'brand_select_' . uniqid(); @endphp

{{-- Carries the row's existing pool id on an edit. Without it the field posts empty and
     InventoryController nulls brand_pool_id on every save, quietly unlinking the item. --}}
<input type="hidden" name="brand_pool_id" class="js-brand-pool-id" value="{{ $selectedId ?? '' }}">
<select name="brand" class="form-control js-brand-pool-select2" id="{{ $bpId }}"
        data-ajax-url="{{ route('vendor.inventory.brands.search') }}"
        data-placeholder="{{ $placeholder ?? 'Search or type brand...' }}">
    {{-- Select2 needs an empty option present for the placeholder to show. --}}
    <option value=""></option>
    @if (!empty($selected))
        <option value="{{ $selected }}" selected>{{ $selected }}</option>
    @endif
</select>

@once
    @push('script_2')
        <script>
            // Brand pool autocomplete. Bound on whatever is in the DOM now, and guarded so a page
            // that includes the field twice does not initialise the same select two times.
            (function initBrandPool() {
                if (!window.jQuery || !jQuery.fn.select2) {
                    // The modules load their scripts in different orders, so rather than assume
                    // Select2 is ready, wait for it instead of failing silently.
                    return setTimeout(initBrandPool, 150);
                }

                jQuery('.js-brand-pool-select2').each(function () {
                    var $el = jQuery(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        return;
                    }

                    // The hidden id rides in the same wrapper as the select rather than being
                    // looked up by name: several of these can share a page.
                    var $hidden = $el.closest('.product_inp_group, .form-group, div').find('.js-brand-pool-id').first();

                    $el.select2({
                        placeholder: $el.data('placeholder') || 'Search or type brand...',
                        allowClear: true,
                        tags: true,              // a brand not in the pool is still allowed through
                        width: '100%',
                        // 0, not 1: with a pool this size the whole list on focus is more useful
                        // than making the operator guess the first letter of a brand we may hold.
                        minimumInputLength: 0,
                        createTag: function (params) {
                            var term = jQuery.trim(params.term);
                            return term === '' ? null : { id: term, text: term, isNew: true };
                        },
                        ajax: {
                            url: $el.data('ajax-url'),
                            dataType: 'json',
                            delay: 250,
                            data: function (params) { return { q: params.term || '' }; },
                            processResults: function (data) {
                                return {
                                    results: (data || []).map(function (b) {
                                        return { id: b.name, text: b.name, brand_pool_id: b.id };
                                    })
                                };
                            },
                            cache: true
                        }
                    });

                    // Only a pick from the pool carries an id. Typing a new brand deliberately
                    // leaves it empty, so the raw text is kept and nothing is invented.
                    $el.on('select2:select', function (e) {
                        if ($hidden.length) { $hidden.val(e.params.data.brand_pool_id || ''); }
                    });
                    $el.on('select2:unselect select2:clear', function () {
                        if ($hidden.length) { $hidden.val(''); }
                    });
                });
            })();
        </script>
    @endpush
@endonce
