<div class="document-info-row">
    <span class="document-info-label">Status:</span>
    @php
        if ($quotation->approved == 1) {
            $status = 'Approved';
        } elseif ($quotation->approved == 0) {
            $status = 'Pending Approval';
        } else {
            $status = 'Rejected';
    } @endphp
    
    <span class="document-info-value">{{ $status }}</span>
</div>
<div class="d-flex justify-content-end gap-2">
@if ($quotation->approved == 0)
    <button class="btn btn-outline-danger updateStatus" data-url="{{ route('service.quotation.approval') }}"
        data-quotation_id = "{{ $quotation->id }}" data-action1="reject" data-action="quotation_approval">Reject
        Quotation</button>
    <button class="btn btn-primary updateStatus" data-url="{{ route('service.quotation.approval') }}"
        data-quotation_id = "{{ $quotation->id }}" data-action1="approve" data-action="quotation_approval">Approve
        Quotation</button>
@endif
</div>
