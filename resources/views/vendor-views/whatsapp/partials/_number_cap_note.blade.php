{{-- How many numbers WhatsApp itself allows, stated wherever a vendor is about to add one.

     It has to be said unconditionally rather than only when the cap is hit: Meta sends the cap
     on the business_capability_update webhook, which fires when the cap CHANGES, so for a store
     that has never had one we know nothing and $limit is 0. Saying nothing there would let a
     vendor walk into Facebook's signup window and be refused with no explanation from us. --}}
<div class="alert alert-light border mb-0" style="font-size:12.5px;">
    <i class="tio-info-outined text-muted mr-1"></i>
    @if ($metaCap > 0)
        {{ translate('WhatsApp currently allows your business') }}
        <b>{{ $metaCap }} {{ $metaCap === 1 ? translate('number') : translate('numbers') }}</b>.
        @if ($metaCap < 20)
            {{ translate('That rises to 20 once your business is verified with Meta, or once you pass a 2,000-message limit — we are told the new cap automatically.') }}
        @endif
    @else
        {{ translate('WhatsApp allows 2 numbers per business to start with. That rises to 20 once your business is verified with Meta, or once you pass a 2,000-message limit.') }}
        {{ translate('Meta tells us your new cap when it changes, and this page updates itself.') }}
    @endif
</div>
