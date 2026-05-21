{{--
    Reusable image cropper modal with Cropper.js
    Usage:
        1. Include this partial once per page: @include('vendor-views.partials.image-cropper-modal')
        2. Add data-cropable="true" to any file input you want to intercept
        3. Optional: data-aspect="1" (default 1:1), data-output-size="800" (default 800px)

    Example:
        <input type="file" name="logo" accept="image/*"
               data-cropable="true" data-aspect="1" data-output-size="800">
--}}

@push('css_or_js')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
    #imageCropperModal .modal-dialog { max-width: 680px; }
    #cropperContainer {
        max-height: 420px;
        background: repeating-conic-gradient(#e0e0e0 0% 25%, #fff 0% 50%) 0 0 / 16px 16px;
        overflow: hidden;
    }
    #cropperContainer img { display: block; max-width: 100%; }
    .cropper-toolbar { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; padding: 8px 0; }
    .cropper-toolbar .btn { padding: 4px 10px; font-size: 13px; }
    #containPadBtn.active { background: #1b5e20; border-color: #1b5e20; color: #fff; }
</style>
@endpush

<!-- Modal -->
<div class="modal fade" id="imageCropperModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">Crop Image</h5>
                <button type="button" class="close" id="cropperCancelBtn" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-2">
                <!-- Cropper area (hidden when Contain & Pad is active) -->
                <div id="cropperContainer">
                    <img id="cropperImage" src="" alt="Crop preview">
                </div>
                <!-- Contain & Pad preview (replaces cropper area) -->
                <div id="containPadPreviewWrap" style="display:none; background:#555; text-align:center; min-height:200px; display:none; align-items:center; justify-content:center;">
                    <canvas id="containPadPreview" style="max-width:100%; max-height:420px; display:block; margin:0 auto;"></canvas>
                </div>
                <div class="cropper-toolbar">
                    <!-- crop-only controls — hidden in Contain & Pad mode -->
                    <button type="button" class="btn btn-outline-secondary crop-only-ctrl" id="cropZoomIn"  title="Zoom In">  ⊕ + </button>
                    <button type="button" class="btn btn-outline-secondary crop-only-ctrl" id="cropZoomOut" title="Zoom Out"> ⊖ − </button>
                    <button type="button" class="btn btn-outline-secondary crop-only-ctrl" id="cropRotateL" title="Rotate Left">  ↺ </button>
                    <button type="button" class="btn btn-outline-secondary crop-only-ctrl" id="cropRotateR" title="Rotate Right"> ↻ </button>
                    <button type="button" class="btn btn-outline-secondary crop-only-ctrl" id="cropFlipH"   title="Flip Horizontal"> ⇄ </button>
                    <button type="button" class="btn btn-outline-secondary"                id="cropReset"   title="Reset to crop mode"> Reset </button>
                    <button type="button" class="btn btn-success"                          id="containPadBtn" title="Fit entire image with padding">
                        Contain &amp; Pad
                    </button>
                    <!-- bg color — only relevant in Contain & Pad mode, but keep always visible -->
                    <div class="ml-auto d-flex align-items-center">
                        <label class="mb-0 mr-1" style="font-size:12px;">Bg:</label>
                        <input type="color" id="cropBgColor" value="#ffffff" title="Padding background color"
                               style="width:32px;height:28px;padding:2px;border:1px solid #ccc;border-radius:4px;cursor:pointer;">
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <span id="cropperDimLabel" style="font-size:11px; color:#888; margin-right:auto;"></span>
                <button type="button" class="btn btn-secondary btn-sm" id="cropperCancelBtn2">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm"   id="cropperConfirmBtn">Use Cropped Image</button>
            </div>
        </div>
    </div>
</div>

@push('script_2')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function () {
    var cropper        = null;
    var targetInput    = null;
    var containMode    = false;
    var originalDataUrl = null;   // full original image data URL, used for Contain & Pad
    var previewEl      = document.getElementById('cropperImage');
    var dimLabel       = document.getElementById('cropperDimLabel');
    var bgColorInput   = document.getElementById('cropBgColor');

    // ── Intercept any [data-cropable] file input ──────────────────────────────
    $(document).on('change', '[data-cropable="true"]', function (e) {
        var file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        targetInput     = e.target;
        containMode     = false;
        originalDataUrl = null;
        $('#containPadBtn').removeClass('active');

        var reader = new FileReader();
        reader.onload = function (ev) {
            originalDataUrl = ev.target.result;   // store original for Contain & Pad
            previewEl.src   = ev.target.result;
            $('#imageCropperModal').modal('show');
        };
        reader.readAsDataURL(file);
    });

    // ── Init Cropper.js when modal finishes opening ───────────────────────────
    $('#imageCropperModal').on('shown.bs.modal', function () {
        var aspect = parseFloat($(targetInput).data('aspect')) || 1;
        if (cropper) { cropper.destroy(); cropper = null; }
        cropper = new Cropper(previewEl, {
            aspectRatio : aspect,
            viewMode    : 1,
            dragMode    : 'move',
            autoCropArea: 0.9,
            restore     : false,
            background  : true,
            movable     : true,
            rotatable   : true,
            scalable    : true,
            zoomable    : true,
            crop        : updateDimLabel,
        });
    });

    // ── Destroy on close ──────────────────────────────────────────────────────
    $('#imageCropperModal').on('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
        containMode = false;
        $('#containPadBtn').removeClass('active');
        $('#containPadPreviewWrap').hide();
        $('#cropperContainer').show();
        $('.crop-only-ctrl').show();
        dimLabel.textContent = '';
        if (targetInput && !targetInput._cropperConfirmed) {
            targetInput.value = '';
        }
        targetInput && (targetInput._cropperConfirmed = false);
    });

    // ── Cancel ────────────────────────────────────────────────────────────────
    $('#cropperCancelBtn, #cropperCancelBtn2').on('click', function () {
        $('#imageCropperModal').modal('hide');
    });

    // ── Toolbar controls ──────────────────────────────────────────────────────
    $('#cropZoomIn' ).on('click', function () { cropper && cropper.zoom(0.1);   });
    $('#cropZoomOut').on('click', function () { cropper && cropper.zoom(-0.1);  });
    $('#cropRotateL').on('click', function () { cropper && cropper.rotate(-90); });
    $('#cropRotateR').on('click', function () { cropper && cropper.rotate(90);  });
    $('#cropFlipH'  ).on('click', function () { cropper && cropper.scaleX(-(cropper.getData().scaleX || 1)); });
    $('#cropReset').on('click', function () {
        if (!cropper) return;
        containMode = false;
        $('#containPadBtn').removeClass('active');
        $('#containPadPreviewWrap').hide();
        $('#cropperContainer').show();
        $('.crop-only-ctrl').show();
        dimLabel.textContent = '';
        cropper.reset();
    });

    // ── Contain & Pad toggle ──────────────────────────────────────────────────
    $('#containPadBtn').on('click', function () {
        if (!cropper) return;
        containMode = !containMode;
        $(this).toggleClass('active', containMode);
        if (containMode) {
            $('#cropperContainer').hide();
            $('#containPadPreviewWrap').css('display', 'flex');
            $('.crop-only-ctrl').hide();
            dimLabel.textContent = '';
            drawContainPadPreview();
        } else {
            $('#containPadPreviewWrap').hide();
            $('#cropperContainer').show();
            $('.crop-only-ctrl').show();
        }
    });

    // Redraw preview when bg color changes and contain mode is on
    bgColorInput.addEventListener('input', function () {
        if (containMode) drawContainPadPreview();
    });

    function drawContainPadPreview() {
        if (!originalDataUrl) return;
        var aspect     = parseFloat($(targetInput).data('aspect')) || 1;
        var previewCanvas = document.getElementById('containPadPreview');
        var maxW       = 560;
        var outW       = maxW;
        var outH       = Math.round(maxW / aspect);
        previewCanvas.width  = outW;
        previewCanvas.height = outH;
        var ctx = previewCanvas.getContext('2d');

        var img = new Image();
        img.onload = function () {
            ctx.fillStyle = bgColorInput.value || '#ffffff';
            ctx.fillRect(0, 0, outW, outH);
            var scale = Math.min(outW / img.naturalWidth, outH / img.naturalHeight);
            var dw = img.naturalWidth  * scale;
            var dh = img.naturalHeight * scale;
            ctx.drawImage(img, (outW - dw) / 2, (outH - dh) / 2, dw, dh);
        };
        img.src = originalDataUrl;
    }

    // ── Confirm — export canvas ───────────────────────────────────────────────
    $('#cropperConfirmBtn').on('click', function () {
        if (!cropper || !targetInput) return;

        var outputSize = parseInt($(targetInput).data('output-size')) || 800;
        var bgColor    = bgColorInput.value || '#ffffff';
        var mimeType   = 'image/jpeg';
        var quality    = 0.92;

        function finish(canvas) {
            canvas.toBlob(function (blob) {
                var originalName = (targetInput.files[0]
                    ? targetInput.files[0].name.replace(/\.[^.]+$/, '')
                    : 'image') + '.jpg';

                var file = new File([blob], originalName, { type: mimeType });
                var dt   = new DataTransfer();
                dt.items.add(file);

                targetInput._cropperConfirmed = true;
                targetInput.files = dt.files;

                var thumb = $(targetInput).siblings('.cropper-preview-thumb');
                if (thumb.length) {
                    thumb.attr('src', URL.createObjectURL(blob)).show();
                }

                var previewTarget = $(targetInput).data('preview-target');
                if (previewTarget) {
                    $(previewTarget).attr('src', URL.createObjectURL(blob));
                }

                $('#imageCropperModal').modal('hide');
            }, mimeType, quality);
        }

        if (containMode) {
            // Contain & Pad: draw the FULL original image into output canvas with bg padding
            exportContainPad(outputSize, bgColor, finish);
        } else {
            var canvas = cropper.getCroppedCanvas({
                width                 : outputSize,
                height                : outputSize,
                fillColor             : bgColor,
                imageSmoothingEnabled : true,
                imageSmoothingQuality : 'high',
            });
            finish(canvas);
        }
    });

    // ── Contain & Pad export ──────────────────────────────────────────────────
    // Draws the FULL original image (not the crop box) centred into the output
    // canvas with the selected background colour filling the remaining space.
    function exportContainPad(size, bgColor, callback) {
        var aspect = parseFloat($(targetInput).data('aspect')) || 1;
        var outW   = size;
        var outH   = Math.round(size / aspect);

        var img    = new Image();
        img.onload = function () {
            var offscreen    = document.createElement('canvas');
            offscreen.width  = outW;
            offscreen.height = outH;
            var ctx = offscreen.getContext('2d');

            // Fill background colour
            ctx.fillStyle = bgColor;
            ctx.fillRect(0, 0, outW, outH);

            // Scale full image to fit inside outW × outH (contain, not cover)
            var scale = Math.min(outW / img.naturalWidth, outH / img.naturalHeight);
            var dw    = img.naturalWidth  * scale;
            var dh    = img.naturalHeight * scale;
            var dx    = (outW - dw) / 2;
            var dy    = (outH - dh) / 2;

            ctx.drawImage(img, dx, dy, dw, dh);
            callback(offscreen);
        };
        img.src = originalDataUrl;
    }

    // ── Update dimension label ────────────────────────────────────────────────
    function updateDimLabel(e) {
        if (!e || !e.detail || containMode) return;
        var d          = e.detail;
        var outputSize = parseInt($(targetInput).data('output-size')) || 800;
        dimLabel.textContent = 'Crop: ' + Math.round(d.width) + '×' + Math.round(d.height)
                             + '  →  Output: ' + outputSize + 'px';
    }
})();
</script>
@endpush
