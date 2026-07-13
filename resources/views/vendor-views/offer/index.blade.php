@extends('layouts.vendor.app')

@section('title', 'Local Offers')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">Local Offers</h1>
        <p class="mb-0">Publish time-limited offers. Active offers appear in AI Search results and on your store page.</p>
    </div>

    <div class="row"> 
        <div class="col-lg-5 mb-3">
            <div class="card">
                <div class="card-header"><h5 class="card-title">Add Offer</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.offer.store') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Title <span class="text-danger">*</span></label> 
                            <input type="text" name="title" class="form-control" maxlength="150" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label class="input-label">Type</label>
                                <select name="discount_type" class="form-control" onchange="toggleValue(this)">
                                    <option value="info">Info / Campaign</option>
                                    <option value="percent">% Off</option>
                                    <option value="flat">Flat ₹ Off</option>
                                </select>
                            </div>
                            <div class="col-sm-6 form-group js-value" style="display:none;">
                                <label class="input-label">Value</label>
                                <input type="number" step="0.01" min="0" name="discount_value" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label class="input-label">Start date</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="col-sm-6 form-group">
                                <label class="input-label">End date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Publish Offer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="card">
                <div class="card-header"><h5 class="card-title">Your Offers</h5></div>
                <div class="table-responsive">
                    <table class="table table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Title</th>
                                <th>Offer</th>
                                <th>Window</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($offers as $offer)
                                <tr>
                                    <td>
                                        <strong>{{ $offer->title }}</strong>
                                        @if($offer->description)<div class="text-muted small">{{ $offer->description }}</div>@endif
                                    </td>
                                    <td>{{ $offer->label }}</td>
                                    <td class="small">
                                        {{ $offer->start_date ? $offer->start_date->format('d M') : '—' }}
                                        &rarr;
                                        {{ $offer->end_date ? $offer->end_date->format('d M') : '—' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.offer.status', $offer->id) }}"
                                           class="badge badge-soft-{{ $offer->status ? 'success' : 'secondary' }}">
                                            {{ $offer->status ? 'Active' : 'Off' }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('vendor.offer.destroy', $offer->id) }}" method="post"
                                              onsubmit="return confirm('Delete this offer?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No offers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $offers->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function toggleValue(sel) {
        document.querySelector('.js-value').style.display = sel.value === 'info' ? 'none' : 'block';
    }
</script>
@endpush
