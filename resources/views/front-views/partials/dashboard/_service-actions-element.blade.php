@if ($acceptedReq->current_status == 'Confirmation Request Sent')
    <button class="updateStatus action-button action-primary gatepass-modal-btn"
       data-action = "confirm_service" data-url="{{ route('service.confirm') }}" data-acceptance_id = "{{ $serRun->id }}"
        data-service_id ="{{ $serRun->service_request_id }}">
        <i class="fas fa-check-circle"></i>
        <span>Confirm Service</span>
    </button>
@endif

