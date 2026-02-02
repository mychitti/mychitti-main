<div class="document-info-row">
    <span class="document-info-label">Status:</span>
    @php
        if ($gatepass->returned == 1) {
            $status = 'Returned';
        } elseif ($gatepass->return_approved == 1) {
            $status = 'Return Approved';
        } elseif ($gatepass->approved == 1) {
            $status = 'Approved';
        } elseif ($gatepass->approved == 0) {
            $status = 'Pending Approval';
        } else {
            $status = 'Rejected';
    } @endphp
    <span class="document-info-value">{{ $status }}</span>
</div>
<div class="d-flex justify-content-end gap-2">
@if ($gatepass->approved == 0 && $gatepass->rejected == 0)
    <button class="btn btn-outline-danger updateStatus" data-url="{{ route('service.gatepass.approval') }}"
        data-gatepass_id = "{{ $gatepass->id }}" data-action1="reject" data-action="gatepass_approval">Reject
        Gatepass</button>
    <button class="btn btn-primary updateStatus" data-url="{{ route('service.gatepass.approval') }}"
        data-gatepass_id = "{{ $gatepass->id }}" data-action1="approve" data-action="gatepass_approval">Approve
        Gatepass</button>
@elseif(!$gatepass->return_approved && $gatepass->returned == 1)
    {{-- <button class="btn btn-outline-danger updateStatus" data-url="{{ route('service.gatepass.return-approval')}}" data-gatepass_id = "{{ $gatepass->id }}" data-action1="return_reject"  data-action="gatepass_return_approval">Reject Return</button> --}}
    <button class="btn btn-primary updateStatus" data-url="{{ route('service.gatepass.return-approval') }}"
        data-gatepass_id = "{{ $gatepass->id }}" data-action1="return_approve"
        data-action="gatepass_return_approval">Confirm Return</button>
@endif
</div>
