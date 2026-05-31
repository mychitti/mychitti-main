@extends('layouts.admin.app')

@section('title', $store->name . ' — File History')

@section('content')
<div class="content container-fluid">

    @include('admin-views.vendor.view.partials._header', ['store' => $store])

    <div class="card mt-3">
        <div class="card-header py-2">
            <h5 class="card-title m-0">
                <i class="tio-folder mr-1"></i> File Upload History
                <span class="badge badge-soft-dark ml-2">{{ $fileHistory->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Uploaded By</th>
                            <th>Type</th>
                            <th>File</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fileHistory as $row)
                            @php
                                $typeLabels = [
                                    'profile_image'       => ['Profile Photo', 'primary'],
                                    'store_logo'          => ['Store Logo', 'info'],
                                    'store_cover'         => ['Store Cover', 'info'],
                                    'staff_profile_image' => ['Staff Photo', 'secondary'],
                                    'staff_id_document'   => ['Staff ID Doc', 'warning'],
                                    'store_id_doc'        => ['Store ID Doc', 'warning'],
                                    'store_id_doc_back'   => ['Store ID Doc (Back)', 'warning'],
                                    'store_gst_doc'       => ['GST Doc', 'success'],
                                    'store_gst_doc_back'  => ['GST Doc (Back)', 'success'],
                                ];
                                [$label, $color] = $typeLabels[$row->file_type] ?? [$row->file_type, 'dark'];
                                $ext = strtolower(pathinfo($row->file_path, PATHINFO_EXTENSION));
                                $fileUrl = asset('storage/app/public/' . $row->file_path);
                            @endphp
                            <tr>
                                <td class="text-muted" style="font-size:12px;">{{ $loop->iteration + ($fileHistory->currentPage() - 1) * $fileHistory->perPage() }}</td>
                                <td style="font-size:13px;">
                                    @if ($row->actor_type === 'vendor')
                                        <span class="badge badge-soft-primary mr-1">Vendor</span>
                                    @else
                                        <span class="badge badge-soft-secondary mr-1">Staff</span>
                                    @endif
                                    <span class="text-muted">ID: {{ $row->actor_id }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-{{ $color }}">{{ $label }}</span>
                                </td>
                                <td>
                                    @if (in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                        <a href="{{ $fileUrl }}" target="_blank">
                                            <img src="{{ $fileUrl }}" style="height:48px;width:auto;max-width:80px;border-radius:4px;object-fit:cover;border:1px solid #eee;"
                                                onerror="this.style.display='none'">
                                        </a>
                                    @elseif ($ext === 'pdf')
                                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-xs btn-outline-primary">
                                            <i class="tio-file-text-outlined mr-1"></i> View PDF
                                        </a>
                                    @else
                                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-xs btn-outline-secondary">
                                            <i class="tio-download mr-1"></i> Download
                                        </a>
                                    @endif
                                </td>
                                <td style="font-size:12px;white-space:nowrap;">
                                    {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No file uploads recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($fileHistory->hasPages())
            <div class="card-footer">
                {!! $fileHistory->links() !!}
            </div>
        @endif
    </div>
</div>
@endsection
