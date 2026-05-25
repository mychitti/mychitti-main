@extends('layouts.admin.app')

@section('title', $store->name . ' — Staff')

@section('content')
<div class="content container-fluid">

    @include('admin-views.vendor.view.partials._header', ['store' => $store])

    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <h5 class="card-title m-0">
                <i class="tio-group-senior mr-1"></i> Staff Members
                <span class="badge badge-soft-dark ml-2">{{ $staff->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:60px"></th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>ID Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staff as $member)
                            <tr>
                                {{-- Photo --}}
                                <td class="text-center py-2">
                                    @if ($member->image)
                                        <img src="{{ asset('storage/vendor/' . $member->image) }}"
                                            class="rounded-circle"
                                            style="width:42px;height:42px;object-fit:cover;"
                                            onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'">
                                    @else
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                            style="width:42px;height:42px;background:#e9ecef;font-size:16px;color:#6c757d;font-weight:600;">
                                            {{ strtoupper(substr($member->f_name ?? '?', 0, 1)) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Name --}}
                                <td class="py-2">
                                    <strong>{{ $member->f_name }} {{ $member->l_name }}</strong>
                                </td>

                                {{-- Contact --}}
                                <td class="py-2" style="font-size:13px;">
                                    <div>{{ $member->email }}</div>
                                    <div class="text-muted">{{ $member->phone }}</div>
                                </td>

                                {{-- Role --}}
                                <td class="py-2">
                                    @if ($member->role)
                                        <span class="badge badge-soft-primary">{{ $member->role->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-2">
                                    @if ($member->status == 1)
                                        <span class="badge badge-soft-success">Active</span>
                                    @else
                                        <span class="badge badge-soft-danger">Inactive</span>
                                    @endif
                                </td>

                                {{-- ID Document --}}
                                <td class="py-2">
                                    @if ($member->id_document)
                                        @php $ext = strtolower(pathinfo($member->id_document, PATHINFO_EXTENSION)); @endphp
                                        @if ($ext === 'pdf')
                                            <a href="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="tio-file-text-outlined"></i> View PDF
                                            </a>
                                        @else
                                            <a href="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}"
                                                target="_blank">
                                                <img src="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}"
                                                    class="rounded border"
                                                    style="height:48px;width:auto;max-width:90px;object-fit:cover;"
                                                    onerror="this.style.display='none'">
                                            </a>
                                        @endif
                                    @else
                                        <span class="text-muted" style="font-size:12px;">Not uploaded</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No staff members found for this store.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
