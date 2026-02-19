    <div class="row w-100">
        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    <h5>Add Custom Domain</h5>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{route('vendor.settings.domain.update')}}">
                        @csrf

                        <div class="form-group">
                            <label>Domain</label>
                            <input type="text" name="domain" class="form-control"
                                placeholder="example: myshop.com" value="{{ $domain ?? (old('domain') ?? ''  )}}" required>
                            <small class="text-muted">
                                Enter domain without http:// or https://
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Save Domain
                        </button>

                    </form>

                </div>
            </div>

        </div>

        <!-- Instructions panel -->
        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    <h6>Setup instructions</h6>
                </div>

                <div class="card-body">
                    <ol class="pl-3">
                        <li>
                            Login to your domain provider (for example: Godaddy).
                        </li>
                        <li>
                            Go to DNS settings of your domain.
                        </li>
                        <li>
                      <pre class="mb-2">
                        Type  : A
                        Name  : @
                        Value : 167.71.233.92</pre>
                        </li>
                        <li>
                            If you are using a sub-domain (like <b> shop.myshop.com</b>), add:
                    <pre class="mb-2">
                        Type  : A
                        Name  : shop
                        Value : 167.71.233.92</pre>
                        </li>
                        <li>
                            Remove any other A record pointing to a different server.
                        </li>
                        <li>
                            Wait for DNS to propagate (usually 5–30 minutes, sometimes up to few hours).
                        </li>
                    </ol>

                    <div class="alert alert-warning mt-3">
                        Do not add http:// or https:// while entering domain in admin.
                    </div>

                </div>
            </div>

        </div>
    </div>
