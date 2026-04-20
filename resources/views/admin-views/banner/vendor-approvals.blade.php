@extends('layouts.admin.app')

@section('title', 'Vendor Banner Approvals')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/banner.png') }}" class="w--26" alt="">
                </span>
                <span>Vendor Banner Approvals</span>
            </h1>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <h5 class="card-title">
                    Vendor Banners
                    @php $pending = $vendor_banners->where('approval', 0)->count() @endphp
                    @if($pending)
                        <span class="badge badge-danger ml-2">{{ $pending }} Pending</span>
                    @endif
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Title</th>
                            <th>Image</th>
                            <th>Approval Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendor_banners as $k => $vb)
                            <tr>
                                <td>{{ $k + $vendor_banners->firstItem() }}</td>
                                <td>{{ $vb->store->name ?? '—' }}</td>
                                <td>{{ Str::limit($vb->title, 35) }}</td>
                                <td>
                                    <img src="{{\App\CentralLogics\Helpers::onerror_image_helper($vb['image'], asset('storage/app/public/banner/').'/'.$vb['image'], asset('/public/assets/admin/img/900x400/img1.jpg'), 'banner/')}}"
                                         style="height:45px;border-radius:4px;"
                                         data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                         class="onerror-image" alt="">
                                </td>
                                <td>
                                    @if($vb->approval === null || $vb->approval == 0)
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($vb->approval == 1)
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-danger">Rejected</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($vb->approval != 1)
                                        <a href="{{ route('admin.banner.vendor.approve', $vb->id) }}"
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Approve this banner?')">
                                            <i class="tio-checkmark-circle"></i> Approve
                                        </a>
                                    @endif
                                    @if($vb->approval != 2)
                                        <a href="{{ route('admin.banner.vendor.reject', $vb->id) }}"
                                           class="btn btn-sm btn-danger ml-1"
                                           onclick="return confirm('Reject this banner?')">
                                            <i class="tio-clear-circle"></i> Reject
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No vendor banners found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $vendor_banners->links() }}</div>
            </div>
        </div>
    </div>
@endsection
