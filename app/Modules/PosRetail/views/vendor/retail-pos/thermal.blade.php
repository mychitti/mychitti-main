@php
    $receiptTemplate = $receiptTemplate ?? 'standard';
    $receiptTemplate = in_array($receiptTemplate, ['standard', 'modern', 'elegant'], true) ? $receiptTemplate : 'standard';
@endphp
@include('posretail::vendor.retail-pos.receipts.' . $receiptTemplate)
