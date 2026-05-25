<section class="container my-4">
    <h5 class="mb-3">Store Webpage Template</h5>
    <style>
        .template-card {
            transition: box-shadow 0.2s;
        }

        .template-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .template-active-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 1;
        }

        @media (max-width: 576px) {
            .template-card .card-img-top {
                height: 90px !important;
            }

            .template-card .card-body {
                padding: 6px !important;
            }

            .template-card .card-body p {
                font-size: 11px !important;
                margin-bottom: 2px !important;
            }

            .template-card .card-body .font-weight-bold {
                font-size: 12px !important;
            }

            .template-card .btn-sm {
                font-size: 10px;
                padding: 3px 7px;
            }

            .template-card .mt-auto {
                gap: 4px !important;
            }
        }
    </style>

    <div class="row">
        @foreach ($templates as $template)
            @php
                $isPurchased = $template->price == 0 || in_array($template->id, $purchasedTemplateIds);
                $isSelected = $store_template_id == $template->id;
            @endphp

            <div class="col-6 col-md-3 mb-3">
                <div class="card h-100 template-card position-relative"
                    style="border: {{ $isSelected ? '2px solid var(--primary)' : '1px solid #d7d7d7' }};">

                    @if ($isSelected)
                        <span class="badge badge-primary template-active-badge">Active</span>
                    @endif

                    <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($template->thumbnail, asset('storage/app/public/uploaded/templates') . '/' . $template->thumbnail, asset('public/assets/admin/img/160x160/img2.jpg'), 'uploaded/templates/') }}"
                        class="card-img-top onerror-image" style="height:150px;object-fit:cover"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">

                    <div class="card-body p-2 d-flex flex-column text-center">

                        {{-- Name --}}
                        <p class="mb-1 font-weight-bold" style="font-size:15px;">{{ $template->name }}</p>

                        {{-- Price / Free --}}
                        @if ($template->price > 0)
                            <p class="mb-0" style="font-size:13px;color:#333;">
                                {{ _price($template->price) }}
                                 <span class="text-muted" style="font-size:11px;"> +gst</span>
                                <span class="text-muted" style="font-size:11px;">({{ $template->duration_count }} {{ $template->duration_unit }})</span>
                            </p> 
                        @else
                            <p class="mb-0"><span class="badge badge-success" style="font-size:11px;">Free</span></p>
                        @endif 

                        {{-- Expiry (only for purchased paid templates) --}}
                        @if ($template->price > 0 && isset($purchases[$template->id]) && $purchases[$template->id]->expires_at)
                            <p class="mb-0 mt-1" style="font-size:11px;color:#888;">
                                <i class="fa fa-clock-o"></i>
                                Expires {{ $purchases[$template->id]->expires_at->format('d M Y') }}
                            </p>
                        @else
                            <p class="mb-0 mt-1" style="font-size:11px;color:transparent;">—</p>
                        @endif

                        {{-- Buttons pinned to bottom --}}
                        <div class="mt-auto pt-2 d-flex justify-content-center" style="gap:6px;">

                            @if ($isPurchased)
                                <form action="{{ route('vendor.settings.webpage-template') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="template_id" value="{{ $template->id }}">
                                    <button type="submit"
                                        class="btn btn-sm {{ $isSelected ? 'btn-primary' : 'btn-outline-primary' }}"
                                        {{ $isSelected ? 'disabled' : '' }}>
                                        {{ $isSelected ? 'Selected' : 'Select' }}
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('vendor.settings.purchase-template') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="template_id" value="{{ $template->id }}">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        Purchase
                                    </button>
                                </form>
                            @endif

                            <button type="button" class="btn btn-sm btn-outline-secondary preview-template"
                                data-toggle="modal" data-target="#templatePreviewModal"
                                data-preview-url="{{ route('store.details', ['tirupati', $store->slug]) }}?template={{ $template->template_id }}">
                                Preview
                            </button>

                        </div>
                    </div>

                </div>
            </div>
        @endforeach
    </div>
</section>

<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-preview-fullscreen">
        <div class="modal-content" style="height:100vh;border-radius:0;border:0;">

            <div class="modal-header py-2" style="flex-shrink:0;">
                <h6 class="modal-title">Template Preview</h6>
                <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal"
                    style="font-size:16px;line-height:1;padding:4px 12px;">
                    &times; Close
                </button>
            </div>

            <div class="modal-body p-0" style="flex:1;overflow:hidden;">
                <iframe id="templatePreviewFrame" src="" style="width:100%;height:100%;border:0;display:block;"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
.modal-preview-fullscreen {
    max-width: 100vw;
    width: 100vw;
    margin: 0;
    height: 100vh;
}
.modal-preview-fullscreen .modal-content {
    display: flex;
    flex-direction: column;
}
</style>

@push('script')
<script>
    $('#templatePreviewModal').on('show.bs.modal', function (e) {
        $('#templatePreviewFrame').attr('src', $(e.relatedTarget).data('preview-url'));
    });
    $('#templatePreviewModal').on('hidden.bs.modal', function () {
        $('#templatePreviewFrame').attr('src', '');
    });

    @if(request('flag') === 'success')
        toastr.success('Template purchased and activated successfully!');
    @elseif(request('flag') === 'fail')
        toastr.error('Payment failed. Please try again.');
    @endif
</script>
@endpush
