<section class="container my-4">
    <h5 class="mb-3">Store Webpage Template</h5>
    <style>
        .theme_lable.active {
            background: var(--primary) !important;
            color: white !important;
        }
    </style>
    <form action="{{ route('vendor.settings.webpage-template') }}" method="post">
        @csrf
        <div class="row">
            @foreach ($templates as $template)
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100 template-card" style="    border: 1px solid #d7d7d7;">

                        <img src="{{ asset('storage/app/public/uploaded/templates') .'/'. $template->thumbnail }}" class="card-img-top" style="height:150px;object-fit:cover">

                        <div class="card-body p-2 text-center">
                            <p style="font-size: 21px;
                                font-weight: bold;" class="d-block mb-2">{{ $template->name }}</p>
                                <p style="    color: black;">
                                @if($template->price > 0 )
                                {{_price($template->price) }} ({{$template->duration_count . ' ' . $template->duration_unit}})
                                @else Free @endif
                            </p>

                            <div class="btn-group btn-group-sm">

                                <!-- select -->
                                <div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">

                                    <label
                                        class="btn btn-outline-primary theme_lable
                                    {{ $store->template_id == $template->id ? 'active' : '' }}">
                                        <input type="radio" name="template_id" value="{{ $template->id }}"
                                            class="select_theme" autocomplete="off"
                                            {{ $store_template_id == $template->id ? 'checked' : '' }}>
                                        Select
                                    </label>

                                </div>

                                <!-- preview -->
                                <button type="button" class="btn btn-outline-secondary preview-template"
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
        <div class="w-100 d-flex justify-content-end">
            <button class="btn btn-primary">Save Preference</button>
        </div>
    </form>
</section>

<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header py-2">
                <h6 class="modal-title">Template Preview</h6>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body p-0">

                <iframe id="templatePreviewFrame" src="" style="width:100%;height:80vh;border:0;">
                </iframe>

            </div>
        </div>
    </div>
</div>
