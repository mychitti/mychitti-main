@extends('layouts.admin.app')

@section('title', translate('Enquiry Details'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-8 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-comment-text-outlined"></i></span>
                        <span>{{ translate('Enquiry Details') }}</span>
                    </h1>
                </div>
                <div class="col-md-4 col-12 text-md-right">
                    <a href="{{ route('admin.mcvendorhub.enquiries') }}" class="btn btn-outline-secondary">
                        <i class="tio-back-ui"></i> {{ translate('Back to Enquiries') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2 border-0">
                        <h5 class="card-title mb-0">{{ $contact->subject ?: translate('No subject') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $contact->message }}</p>
                        @if ($contact->file)
                            <hr>
                            <a href="{{ asset('storage/app/public/contact/' . $contact->file) }}" target="_blank"
                                class="btn btn-sm btn-outline-primary">
                                <i class="tio-attachment"></i> {{ translate('View Attachment') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-3">
                <div class="card mb-3">
                    <div class="card-header py-2 border-0">
                        <h5 class="card-title mb-0">{{ translate('Sender') }}</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5">{{ translate('Name') }}</dt>
                            <dd class="col-7">{{ $contact->name ?: '-' }}</dd>
                            <dt class="col-5">{{ translate('Business') }}</dt>
                            <dd class="col-7">{{ $contact->business_name ?: '-' }}</dd>
                            <dt class="col-5">{{ translate('Phone') }}</dt>
                            <dd class="col-7">{{ $contact->phone ?: '-' }}</dd>
                            <dt class="col-5">{{ translate('Email') }}</dt>
                            <dd class="col-7">{{ $contact->email ?: '-' }}</dd>
                            <dt class="col-5">{{ translate('Received') }}</dt>
                            <dd class="col-7">{{ $contact->created_at?->format('d M Y, h:i A') }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header py-2 border-0">
                        <h5 class="card-title mb-0">{{ translate('Internal Note') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.mcvendorhub.enquiries.update', $contact->id) }}" method="post">
                            @csrf
                            <div class="form-group">
                                <textarea name="feedback" rows="5" class="form-control"
                                    placeholder="{{ translate('Add a follow-up note') }}">{{ $contact->feedback }}</textarea>
                            </div>
                            <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
