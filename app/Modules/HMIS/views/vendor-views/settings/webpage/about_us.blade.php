  <style>
        .form-row {
            margin-top: 6px;
        }

        .ck.ck-reset {
            width: 100% !important;
        }
    </style>
      <form class="w-100 p-0" id="about_us-form" action="{{ route('vendor.business-settings.about-us.save') }}" method="post">
                @csrf

                <div class="col-md-12">
                    <div class="form-row ">
                        <textarea placeholder="Start Typing ..."  id="editor" class="form-control" name="content" >{!! $about_us ?? '' !!}</textarea>
                    </div>
                    <input type="hidden" class="upload_url" value="{{route('vendor.business-settings.image-upload')}}">
                    <button class="btn btn-primary my-2">Update</button>
                    @if (config('services.vendor_ai_tools.enabled'))
                        <button type="button" class="btn btn-outline-primary my-2" data-toggle="modal" data-target="#mcAboutModal"><i class="tio-magic-wand"></i> Write with AI</button>
                    @endif
                </div>
            </form>

            {{-- AI business-description composer (Phase 4 §4.1) --}}
            <div class="modal fade" id="mcAboutModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document"><div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="tio-magic-wand text-primary"></i> Write About Us with AI</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">AI writes a description from your business name and area. Add anything special below (optional).</p>
                        <textarea id="mcAboutInput" class="form-control" rows="3" placeholder="Optional: e.g. AC & fridge repair, 10 yrs experience, same-day service"></textarea>
                        <div id="mcAboutErr" class="text-danger small mt-2" style="display:none;"></div>
                        <p class="text-muted small mb-0 mt-2">This replaces the text in the editor — you can edit before saving.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="mcAboutGen"><i class="tio-magic-wand"></i> Generate</button>
                    </div>
                </div></div>
            </div>

            <script>
                (function () {
                    var genUrl = '{{ route('vendor.business-settings.about-us.ai') }}';
                    function setEditor(html) {
                        var ed = window.mcAboutEditor;
                        if (!ed) { var el = document.querySelector('#editor'); ed = el && el.ckeditorInstance; }
                        if (ed && typeof ed.setData === 'function') { ed.setData(html); return true; }
                        var ta = document.querySelector('#editor'); if (ta) { ta.value = html; return true; }
                        return false;
                    }
                    function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; }
                    function closeModal() { var d = document.querySelector('#mcAboutModal [data-dismiss="modal"]'); if (d) d.click(); }
                    document.addEventListener('click', function (e) {
                        var gen = e.target.closest('#mcAboutGen');
                        if (!gen) return;
                        var input = document.querySelector('#mcAboutInput');
                        var err = document.querySelector('#mcAboutErr');
                        err.style.display = 'none'; err.textContent = '';
                        var orig = gen.innerHTML;
                        gen.disabled = true; gen.innerHTML = '<i class="tio-sync tio-spin"></i> Writing…';
                        var body = new FormData();
                        body.append('input', (input && input.value ? input.value : '').trim());
                        body.append('_token', csrf());
                        fetch(genUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: body })
                            .then(function (r) { return r.json().catch(function () { return null; }); })
                            .then(function (res) {
                                if (res && res.success && res.html) {
                                    if (setEditor(res.html)) { closeModal(); }
                                    else { err.textContent = 'Could not fill the editor — please copy manually.'; err.style.display = 'block'; }
                                } else {
                                    err.textContent = (res && res.message) || 'Could not write a description. Try again.';
                                    err.style.display = 'block';
                                }
                            })
                            .catch(function () { err.textContent = 'Could not reach the server. Please try again.'; err.style.display = 'block'; })
                            .finally(function () { gen.disabled = false; gen.innerHTML = orig; });
                    });
                })();
            </script> 
