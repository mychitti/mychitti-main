@extends('layouts.vendor.app')

@section('title', 'My Documents')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }

        .ck.ck-reset {
            width: 100% !important;
        }

        .vdp-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .vdp-heading {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .vdp-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .vdp-doc-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .vdp-doc-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .vdp-card-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 16px;
        }

        .vdp-file-icon {
            width: 50px;
            height: 50px;
            background: #e4e4e4ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .vdp-card-info {
            flex: 1;
        }

        .vdp-doc-filename {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .vdp-doc-meta {
            font-size: 13px;
            color: #666;
        }

        .vdp-card-body {
            margin-bottom: 16px;
        }

        .vdp-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .vdp-info-row:last-child {
            border-bottom: none;
        }

        .vdp-info-label {
            font-size: 13px;
            color: #666;
        }

        .vdp-info-value {
            font-size: 13px;
            color: #1a1a1a;
            font-weight: 500;
        }

        .vdp-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .vdp-status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .vdp-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .vdp-status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .vdp-card-footer {
            display: flex;
            gap: 8px;
        }


        .vdp-btn-primary {
            background: var(--primary, #4f46e5);
            color: white;
            border-color: var(--primary, #4f46e5);
        }

        .vdp-btn-primary:hover {
            background: var(--primary-light, #6366f1);
            border-color: var(--primary-light, #6366f1);
            color: white;
        }

        @media (max-width: 768px) {
            .vdp-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        .vdp-empty-state {
            background: white;
            border-radius: 10px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .vdp-empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .vdp-empty-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .vdp-empty-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 24px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .vdp-empty-state .vdp-btn-upload {
            display: inline-block;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-document-text-outlined"></i>My Documents</h1>
        </div>
        <!-- End Page Header -->

        <div class="vdp-cards-grid">
            @php
                $gst_file = $store->gst_doc ?? '';
                $id_proof_file = $store->id_doc ?? '';
            @endphp
            @if ($gst_file)
                <!-- Document Card 1 -->
                <div class="vdp-doc-card">
                    <div class="vdp-card-header">
                        <div class="vdp-file-icon">PDF</div>
                        <div class="vdp-card-info">
                            <div class="vdp-doc-filename">GST Document</div>
                            <div class="vdp-doc-meta">Uploaded on {{ _formatted_date($store->created_at) }}</div>
                        </div>
                    </div>
                    <div class="vdp-card-body">
                        <div class="vdp-info-row">
                            <span class="vdp-info-label">File Type</span>
                            <span class="vdp-info-value">{{ _getFileTypeLabel($gst_file) }}</span>
                        </div>
                        <div class="vdp-info-row">
                            <span class="vdp-info-label">Status</span>
                            <span class="vdp-status-badge vdp-status-approved">Approved</span>
                        </div>
                    </div>

                    <div class="vdp-card-footer">
                        <a href="{{ asset('storage/app/public/store/docs') . '/' . $store->gst_doc }}"
                            class="btn btn-primary">View</a>
                        <a download href="{{ asset('storage/app/public/store/docs') . '/' . $store->gst_doc }}"
                            class="btn btn-outline-primary">Download</a>
                        <button class="btn btn-outline-primary">Update</button>
                    </div>
                </div>
            @endif

            @if ($id_proof_file)
                <!-- Document Card 1 -->
                <div class="vdp-doc-card">
                    <div class="vdp-card-header">
                        <div class="vdp-file-icon">PDF</div>
                        <div class="vdp-card-info">
                            <div class="vdp-doc-filename">ID Proof</div>
                            <div class="vdp-doc-meta">Uploaded on {{ _formatted_date($store->created_at) }}</div>
                        </div>
                    </div>

                    <div class="vdp-card-body">
                        <div class="vdp-info-row">
                            <span class="vdp-info-label">File Type</span>
                            <span class="vdp-info-value">{{ _getFileTypeLabel($id_proof_file) }}</span>
                        </div>
                        <div class="vdp-info-row">
                            <span class="vdp-info-label">Status</span>
                            <span class="vdp-status-badge vdp-status-approved">Approved</span>
                        </div>
                    </div>

                    <div class="vdp-card-footer">
                        <a href="{{ asset('storage/app/public/store/docs') . '/' . $store->id_doc }}"
                            class="btn btn-primary">View</a>
                        <a download href="{{ asset('storage/app/public/store/docs') . '/' . $store->id_doc }}"
                            class="btn btn-outline-primary">Download</a>
                        <button class="btn btn-outline-primary" data-toggle="modal"
                            data-target="#idDocUpdateModal">Update</button>

                    </div>
                </div>
            @endif
            <div class="modal fade" id="idDocUpdateModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">ID Proof Update</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('vendor.business-settings.update-doc') }}">
                            @csrf
                            <div class="modal-body">

                                <input type="hidden" name="file_type" value="id_doc">
                                <div class="form-group">
                                    <label for="id_proof">Upload New ID Proof</label>
                                    <input type="file" class="form-control" id="id_proof" name="id_proof" required>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @if (!$id_proof_file && !$gst_file)
                <!-- Empty State -->
                <div class="vdp-empty-state" id="emptyState">
                    <div class="vdp-empty-icon">📄</div>
                    <div class="vdp-empty-title">No Documents Yet</div>
                    <div class="vdp-empty-text">You haven't uploaded any documents. Get started by uploading your first
                        document.</div>
                    <button class="btn btn-outline-primary" onclick="uploadDocument()">
                        Upload Document
                    </button>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor2'));

        function viewDoc(name) {
            alert('Viewing: ' + name);
        }

        function downloadDoc(name) {
            alert('Downloading: ' + name);
        }

        function deleteDoc(name) {
            if (confirm('Are you sure you want to delete ' + name + '?')) {
                alert('Deleted: ' + name);
            }
        }
    </script>
@endpush
