{{--
    "Send on WhatsApp" button + confirm box, shared by every hospital screen that can send a
    patient their own record.

    Required: $action (post URL), $label (button text).
    Optional: $phone (patient's number, pre-filled and editable — a patient often gives a
              relative's number for reports), $note (one line of context in the box),
              $class (button classes), $icon.

    The number is editable rather than fixed because the alternative is staff being unable to send
    at all when the number on file is stale — and the send is logged either way.
--}}
@php
    $waId    = 'waSend' . substr(md5($action), 0, 10);
    $waPhone = trim((string) ($phone ?? ''));
@endphp

<button type="button" class="{{ $class ?? 'btn btn-sm btn-outline-success' }}" data-toggle="modal" data-target="#{{ $waId }}">
    <i class="{{ $icon ?? 'tio-chat' }}"></i> {{ $label }}
</button>

<div class="modal fade" id="{{ $waId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post" action="{{ $action }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size:16px;">{{ $label }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (!empty($note))
                        <p class="text-muted mb-3" style="font-size:13px;">{{ $note }}</p>
                    @endif

                    <label class="font-weight-bold mb-1" style="font-size:13px;">Send to</label>
                    <input type="text" name="phone" class="form-control" value="{{ $waPhone }}"
                           placeholder="Patient's WhatsApp number" required>

                    @if ($waPhone === '')
                        <small class="text-danger d-block mt-1">
                            No number is saved for this patient — type the one to send to.
                        </small>
                    @else
                        <small class="text-muted d-block mt-1">
                            The patient's saved number. Change it if the message should go elsewhere.
                        </small>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn--primary">
                        <i class="tio-send"></i> Send
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
