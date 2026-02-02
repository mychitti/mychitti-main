<div class="document-container">
    <div class="document-header">
        <h3>Service Gatepass</h3>
        <span class="document-id">GP-{{ $gatepass->id }}</span>
    </div>
    <div class="document-body">
        <div class="document-info-row">
            <span class="document-info-label">Service Request ID:</span>
            <span class="document-info-value">#{{ $gatepass->service_id }}</span>
        </div>
        <div class="document-info-row">
            <span class="document-info-label">Date Issued:</span>
            <span class="document-info-value">{{ date('d M Y', strtotime($gatepass->created_at)) }}</span>
        </div>

        <div class="">
            <h5>Items</h5>
            @foreach ($gatepass_items as $key => $item)
                <div class="rounded border p-2 my-2 d-flex gap-2">
                @php $images = json_decode($item->image) ?? []; $image = $images[0]; @endphp
                    <img src="{{ asset('storage/app/public/gatepass/'.$image) }}" style="width:60px; height:60px; aspect-ratio:1;"
                        alt="{{ $item->title }} image">
                    <div class="">
                        <span class="document-info-label">{{ $item->title }} </span>
                        <p >{{ $item->description }}</p>
                        <div class="d-flex gap-1 flex-wrap">
                        @if(count($images) > 1)
                        <b>Images : </b>
                        @foreach($images as $key => $value)
                            <img  src="{{ asset('storage/app/public/gatepass/'.$value) }}" class="border rounded" alt="{{ $item->title }} image {{ $key }}"style="width:30px; height:30px; aspect-ratio:1;">
                        @endforeach
                        @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>



    </div>
    <div class="gatepass-cta_{{ $gatepass->id }}">

        @include('front-views.partials.dashboard.service._gatepass_cta', ['gatepass' => $gatepass])
    </div>
    <div class="document-alert alert-info">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Important Notice:</strong><br>
            This gatepass authorizes the designated service staff to access the premises for service delivery. Please
            verify staff identification before granting access.
        </div>
    </div>
</div>
