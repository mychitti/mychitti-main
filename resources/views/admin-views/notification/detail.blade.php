@extends('layouts.admin.app')

@section('title', translate('messages.notification'))

@push('css_or_js')
    <style>
   
        .ad-preview-card {
            max-width: 600px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .ad-header {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ad-id {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .ad-image {
            width: 100%;
            object-fit: cover;
            background: #e5e5e5;
        }

        .ad-content {
            padding: 14px;
        }

        .vendor-name {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }

        .ad-title {
            font-size: 16px;
            font-weight: 600;
            color: #111;
            margin-bottom: 6px;
        }

        .ad-description {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .ad-meta {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid #f0f0f0;
            margin-top: 8px;
        }

        .meta-item {
            flex: 1;
        }

        .meta-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        .ad-actions {
            padding: 10px 14px;
            background: #fafafa;
            display: flex;
            gap: 8px;
        }

        .btn-custom {
            flex: 1;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-approve {
            background: var(--primary);
            color: white;
        }

        .btn-approve:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-reject {
            background: white;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-reject:hover {
            background: #fef2f2;
        }


        .category-tag {
            display: inline-block;
            padding: 3px 8px;
            background: var(--primary);
            color: white;
            font-size: 11px;
            border-radius: 3px;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .icon {
            width: 16px;
            height: 16px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/notification.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.notification') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="ad-preview-card">
                            <div class="ad-header">
                                @if ($notification->approval == 0)
                                    <span class="status-badge status-pending">Pending</span>
                                @elseif($notification->approval == 1)
                                    <span class="status-badge status-approved">Approved</span>
                                @else
                                    <span class="status-badge status-rejected">Rejected</span>
                                @endif
                            </div>

                            <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                $notification['image'] ?? '',
                                asset('storage/app/public/notification') . '/' . $notification['image'],
                                asset('public/assets/admin/img/900x400/1080x1350_img1.jpg'),
                                'notification/',
                            ) }}"
                                data-onerror-image="{{ asset('public/assets/admin/img/900x400/1080x1350_img1.jpg') }}"
                                alt="Ad Preview" class="ad-image">

                            <div class="ad-content">
                                <div class="vendor-name"><a
                                        href="{{ route('admin.store.view', [$notification->vendor_id]) }}">{{ _storeName($notification->vendor_id) }}</a>
                                </div>
                                <div class="category-tag">
                                    {{ $notification->zone_id == null ? translate('messages.all') . ' zones' : ($notification->zone ? $notification->zone->name : translate('messages.zone_deleted')) }}</a>
                                </div>
                                <h3 class="ad-title">{{ $notification->title }}</h3>
                                <p class="ad-description">
                                    {{ $notification->description }}
                                </p>

                                <div class="ad-meta">
                                    <div class="meta-item">
                                        <div class="meta-label">Target</div>
                                        <div class="meta-value">{{ $notification->tergat }}</div>
                                    </div>

                                </div>
                            </div>

                            <div class="ad-actions">
                                @if ($notification->approval == 0)
                                    {{-- <button class="btn btn-approve">
                                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Approve
                                </button>
                                <button class="btn btn-reject">
                                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Reject
                                </button> --}}

                                    <a class="btn btn-approve form-alert" href="javascript:"
                                        style="    width: fit-content !important;padding: 0 8px !important;"
                                        data-id="notification_approve-{{ $notification['id'] }}"
                                        data-message="{{ translate('Want to approve and release this notification ?') }}"
                                        title="{{ translate('messages.approve_notification') }}"> <svg class="icon"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>Approve
                                    </a>
                                    <form
                                        action="{{ route('admin.notification.approval', [$notification['id'], 'approve']) }}"
                                        method="post" id="notification_approve-{{ $notification['id'] }}">
                                        @csrf
                                    </form>
                                    <a class="btn btn-reject form-alert" href="javascript:"
                                        style="    width: fit-content !important;padding: 0 8px !important;"
                                        data-id="notification_reject-{{ $notification['id'] }}"
                                        data-message="{{ translate('Want to reject this notification ?') }}"
                                        title="{{ translate('messages.reject_notification') }}"> <svg class="icon"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg> Reject
                                    </a>
                                    <form
                                        action="{{ route('admin.notification.approval', [$notification['id'], 'reject']) }}"
                                        method="post" id="notification_reject-{{ $notification['id'] }}">
                                        @csrf
                                    </form>
                                @elseif($notification->approval == 1)
                                    <span class="badge badge-soft-success">Approved</span>
                                @else
                                    <span class="badge badge-soft-danger">Rejected</span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('script_2')
    <script></script>
@endpush
