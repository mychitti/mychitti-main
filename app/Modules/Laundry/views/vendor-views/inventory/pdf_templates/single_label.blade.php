<div class="label" style="width: max-content; padding:5px; border:1px solid #000; font-size:14px; text-align:center;">
    <div><strong>{{ $label['description'] }}</strong></div>
    <div>Price: {{ $label['price'] }}</div>
    <div>Made in: {{ $label['made_in'] }}</div>
    <div>Item #: {{ $label['item_number'] }}</div>
    <div>
        <barcode code="{{ $label['barcode'] }}" type="C128B" height="1.2" />
    </div>
</div>
