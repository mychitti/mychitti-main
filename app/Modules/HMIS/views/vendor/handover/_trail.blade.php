{{-- Every exchange on one record, newest first.

     Shown in full rather than summarised to the latest one, because the question this answers is
     never "who has it now" — the stage on the job says that. It is "where did it go missing", and
     that is only answerable by seeing the whole chain with the gaps visible in it.

     $handovers    : collection of HmisHandover, already filtered to dated (non-draft) rows
     $hoSubjectId  : the record they belong to
     $hoCanRecord  : whether a handover may still be recorded against this record — now only
                     governs the "Mark confirmed" line below, since recording a new one is
                     started by moving the job's stage rather than by a button of its own --}}
@php
    $hoCanRecord = $hoCanRecord ?? false;
    $hoType      = $hoType ?? 'opd_lab_work';
@endphp

@if($handovers->count())
    <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9;">
        <div class="text-muted mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.04em;">
            Chain of custody ({{ $handovers->count() }})
        </div>

        @foreach($handovers as $ho)
            @php $hoColour = $ho->stateColour(); @endphp
            <div class="d-flex align-items-start mb-1" style="font-size:11.5px;">
                <i class="tio-{{ $ho->is_inbound ? 'arrow-downward text-success' : 'arrow-upward text-warning' }} mr-2"
                   style="margin-top:2px;"></i>
                <div style="min-width:0; flex:1;">
                    <span class="text-dark" style="font-weight:600;">{{ $ho->person_name }}</span>
                    @if(filled($ho->lab_name))
                        <span class="text-muted">of {{ $ho->lab_name }}</span>
                    @endif
                    <span class="text-muted">
                        {{ $ho->is_inbound ? 'delivered' : 'collected' }}
                        {{ trim((string) $ho->purpose) ?: 'work' }}
                        @if($ho->item_count) ({{ $ho->item_count }}) @endif
                        {{ $ho->is_inbound ? 'to' : 'from' }} {{ $ho->staff_name }}
                    </span>

                    <span class="badge ml-1" style="font-weight:600; color:{{ $hoColour[0] }}; background:{{ $hoColour[1] }};">
                        {{ $ho->stateLabel() }}
                    </span>

                    {{-- The override is shown, never buried. A handover recorded against what the
                         record said should be happening is the single most interesting row in the
                         trail when something has gone wrong. --}}
                    @if(!$ho->dispatch_expected && filled($ho->override_reason))
                        <div class="text-danger" style="font-size:11px;">
                            <i class="tio-warning"></i> Recorded against the record: {{ $ho->override_reason }}
                        </div>
                    @endif

                    <div class="text-muted" style="font-size:11px;">
                        {{ optional($ho->happened_at)->format('d M Y · h:i A') }}
                        @if(filled($ho->person_id_ref)) · ID {{ $ho->person_id_ref }} @endif
                        @if(filled($ho->person_phone)) · {{ $ho->person_phone }} @endif
                        @if($ho->otp_verified_at) · code verified @endif

                        @if($ho->mediaUrl('signature_path'))
                            · <a href="{{ $ho->mediaUrl('signature_path') }}" target="_blank">signature</a>
                        @endif
                        @if($ho->mediaUrl('photo_path'))
                            · <a href="{{ $ho->mediaUrl('photo_path') }}" target="_blank">photo</a>
                        @endif
                        · <a href="{{ route('vendor.handover.slip', $ho->id) }}" target="_blank">slip</a>
                    </div>

                    {{-- Only offered where it is actually the remaining action: an arrival taken on
                         trust, still waiting for somebody to ring the lab. --}}
                    @if($hoCanRecord && $ho->verify_state === 'provisional')
                        <form method="POST" action="{{ route('vendor.handover.confirm', $ho->id) }}" class="form-inline mt-1">
                            @csrf
                            <input type="text" name="how" class="form-control form-control-sm" style="font-size:11px; max-width:260px;"
                                   maxlength="255" required placeholder="How did you confirm it? e.g. rang lab, spoke to Anil">
                            <button type="submit" class="btn btn-link btn-sm p-0 ml-2" style="font-size:11px;">
                                Mark confirmed
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
