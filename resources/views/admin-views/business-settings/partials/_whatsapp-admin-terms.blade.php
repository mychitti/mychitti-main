{{--
    How the admin is collecting for one grant. Shared by all four action forms.

    $prefix    — unique per form on the page, so the fields and the toggle script don't collide
    $listPrice — the published price, prefilled into Amount and editable
    $methods   — WhatsAppBilling::ADMIN_METHODS
    $gst       — GST percent, added on the billing path only
--}}
<div class="border-top pt-3 mt-3">
    <div class="form-row">
        <div class="form-group col-md-4 mb-2">
            <label class="input-label mb-1" style="font-size:12px;">{{ translate('Collect as') }}</label>
            <select name="mode" class="form-control form-control-sm js-wa-mode" data-prefix="{{ $prefix }}">
                <option value="billing">{{ translate('Billing — bill to store') }}</option>
                <option value="retail">{{ translate('Retail — no bill') }}</option>
            </select>
        </div>
        <div class="form-group col-md-4 mb-2">
            <label class="input-label mb-1" style="font-size:12px;">
                {{ translate('Amount') }}
                <small class="text-muted js-wa-tax-note" data-prefix="{{ $prefix }}">({{ translate('excl.') }} {{ $gst }}% {{ translate('GST') }})</small>
            </label>
            <input type="number" step="0.01" min="0" name="amount" value="{{ round($listPrice, 2) }}"
                class="form-control form-control-sm">
        </div>
        <div class="form-group col-md-4 mb-2 js-wa-billing-only" data-prefix="{{ $prefix }}">
            <label class="input-label mb-1" style="font-size:12px;">{{ translate('Payment method') }}</label>
            <select name="method" class="form-control form-control-sm">
                @foreach ($methods as $method)
                    <option value="{{ $method }}">
                        {{ $method === 'Wallet' ? translate('Vendor wallet (debits balance)') : translate($method) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-4 mb-2 js-wa-billing-only" data-prefix="{{ $prefix }}">
            <label class="input-label mb-1" style="font-size:12px;">{{ translate('Payment status') }}</label>
            <select name="status" class="form-control form-control-sm">
                <option value="Paid">{{ translate('Paid') }}</option>
                <option value="Unpaid">{{ translate('Unpaid — raise invoice only') }}</option>
            </select>
        </div>
        <div class="form-group col-md-8 mb-2">
            <label class="input-label mb-1" style="font-size:12px;">{{ translate('Note') }} <small class="text-muted">({{ translate('optional') }})</small></label>
            <input type="text" name="note" maxlength="190" class="form-control form-control-sm"
                placeholder="{{ translate('Reference, cheque no., who approved it…') }}">
        </div>
    </div>
</div>
