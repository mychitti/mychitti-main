@extends('layouts.admin.app')

@section('title', 'Create Support Ticket')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-support"></i></span>
                <span>Create Support Ticket</span>
            </h1>
        </div>

        <form action="{{ route('admin.ticket.store') }}" method="POST" enctype="multipart/form-data">
            @csrf 

            {{-- Ticket Details --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title"><i class="tio-document-text-outlined mr-1"></i> Ticket Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="input-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" placeholder="Brief summary of the issue" required value="{{ old('subject') }}">
                            </div>
                        </div> 
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="input-label">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                </select> 
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="input-label">Assign To</label>
                                <select name="assigned_to" class="form-control">
                                    <option value="">Unassigned</option>
                                    @foreach($staff as $s)
                                        <option value="{{ $s->id }}">{{ $s->f_name }} {{ $s->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="input-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Describe the issue..." required>{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="input-label">Attachment</label>
                                <div class="custom-file">
                                    <input type="file" name="attachment" class="custom-file-input" id="ticketAttachment"
                                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip">
                                    <label class="custom-file-label" for="ticketAttachment">Choose file...</label>
                                </div>
                                <small class="text-muted">Max 10MB</small>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="department" value="General">
                </div>
            </div>

            {{-- Raise Ticket For --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title"><i class="tio-user-outlined mr-1"></i> Raise Ticket For</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap mb-3" style="gap: 8px;">
                        <label class="ticket-type-option {{ old('ticket_for', 'vendor') == 'vendor' ? 'active' : '' }}">
                            <input type="radio" name="ticket_for" value="vendor" {{ old('ticket_for', 'vendor') == 'vendor' ? 'checked' : '' }} class="d-none ticket-for-radio">
                            <i class="tio-shop-outlined mr-1"></i> Vendor
                        </label>
                        <label class="ticket-type-option {{ old('ticket_for') == 'customer' ? 'active' : '' }}">
                            <input type="radio" name="ticket_for" value="customer" {{ old('ticket_for') == 'customer' ? 'checked' : '' }} class="d-none ticket-for-radio">
                            <i class="tio-user-outlined mr-1"></i> Customer
                        </label>
                        <label class="ticket-type-option {{ old('ticket_for') == 'guest' ? 'active' : '' }}">
                            <input type="radio" name="ticket_for" value="guest" {{ old('ticket_for') == 'guest' ? 'checked' : '' }} class="d-none ticket-for-radio">
                            <i class="tio-incognito mr-1"></i> Guest
                        </label>
                    </div>

                    <div id="vendor-section" class="ticket-for-section" style="display:none;">
                        <div class="col-md-5 px-0">
                            <div class="form-group mb-0">
                                <select name="vendor_id" id="vendorSelect" class="form-control" data-placeholder="Search vendor...">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="customer-section" class="ticket-for-section" style="display:none;">
                        <div class="col-md-5 px-0">
                            <div class="form-group mb-0">
                                <select name="user_id" id="customerSelect" class="form-control" data-placeholder="Search customer...">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="guest-section" class="ticket-for-section" style="display:none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <input type="text" name="guest_name" class="form-control" placeholder="Name" value="{{ old('guest_name') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <input type="text" name="guest_phone" class="form-control" placeholder="Phone" value="{{ old('guest_phone') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <input type="email" name="guest_email" class="form-control" placeholder="Email" value="{{ old('guest_email') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn--container justify-content-end mt-3 mb-3">
                <a href="{{ route('admin.ticket.index') }}" class="btn btn--reset">Cancel</a>
                <button type="submit" class="btn btn--primary">Create Ticket</button>
            </div>
        </form>
    </div>
@endsection

@push('css_or_js')
<style>
    .ticket-type-option {
        cursor: pointer;
        border: 2px solid #e7eaf3;
        border-radius: 8px;
        padding: 8px 18px;
        font-size: 14px;
        transition: all 0.2s;
    }
    .ticket-type-option.active {
        border-color: #005555 !important;
        background-color: #f0fafa;
        font-weight: 600;
    }
    .ticket-type-option:hover {
        border-color: #00aa96 !important;
    }
</style>
@endpush

@push('script_2')
<script>
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName || 'Choose file...');
    });

    function toggleTicketForSection() {
        let selected = $('input[name="ticket_for"]:checked').val();
        $('.ticket-for-section').hide();
        $('.ticket-type-option').removeClass('active');
        $('input[name="ticket_for"]:checked').closest('.ticket-type-option').addClass('active');
        if (selected === 'vendor') $('#vendor-section').show();
        else if (selected === 'customer') $('#customer-section').show();
        else if (selected === 'guest') $('#guest-section').show();
    }

    $(document).ready(function() { toggleTicketForSection(); });
    $('.ticket-for-radio').on('change', function() { toggleTicketForSection(); });

    $('#vendorSelect').select2({
        ajax: {
            url: '{{ route("admin.ticket.search-vendors") }}',
            dataType: 'json', delay: 300,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: 'Search vendor by name or phone...',
        allowClear: true
    });

    $('#customerSelect').select2({
        ajax: {
            url: '{{ route("admin.ticket.search-customers") }}',
            dataType: 'json', delay: 300,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: 'Search customer by name or phone...',
        allowClear: true
    });
</script>
@endpush
