    <div class="row w-100">
        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    <h5>Add Custom Domain</h5>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ route('vendor.settings.domain.update') }}">
                        @csrf 

                        <div class="form-group">
                            <label>Domain</label>
                            <input type="text" name="domain" class="form-control" placeholder="example: myshop.com"
                                value="{{ $domain ?? (old('domain') ?? '') }}" required>
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
                    <h6>Setup Instructions</h6>
                </div>

                <div class="card-body">
                    <ol class="pl-3">
                        <li>
                            Login to your domain provider (e.g. GoDaddy, Namecheap, BigRock).
                        </li>
                        <li>
                            Go to <b>DNS Settings</b> of your domain.
                        </li>
                        <li>
                            Add the following CNAME record for your <b>www</b> subdomain:
                            <pre class="mb-2 mt-2">
Type  : CNAME
Name  : www
Value : stores.mychitti.net</pre>
                        </li>
                        <li>
                            For your <b>root domain</b> (e.g. yourdomain.com without www), set up a
                            <b>redirect/forwarding</b> to <b>www.yourdomain.com</b>.<br>
                            In GoDaddy: go to <b>My Products → DNS → Forwarding</b> and forward
                            <code>yourdomain.com</code> → <code>https://www.yourdomain.com</code> (Permanent 301).
<pre class="mb-2 mt-2">
host name : yourdomain.com
value : https://www.yourdomain.com
</pre>
                        </li>
                        <li>
                            If you are using a subdomain (like <b>shop.yourdomain.com</b>), add instead:
                            <pre class="mb-2 mt-2">
Type  : CNAME
Name  : shop
Value : stores.mychitti.net</pre>
                            Then enter <b>shop.yourdomain.com</b> below (not the root domain).
                        </li>
                        <li>
                            Remove any existing A or CNAME records for <code>www</code> that point elsewhere.
                        </li>
                        <li>
                            Enter your domain below (use <b>www.yourdomain.com</b>) and click <b>Save Domain</b>.
                            SSL will be automatically issued — no separate purchase needed.
                        </li>
                        <li>
                            Wait for DNS to propagate (usually 5–30 minutes, up to a few hours).
                        </li>
                    </ol>

                    <div class="alert alert-info mt-3">
                        <b>Using Cloudflare for DNS?</b> You can add a CNAME for <code>@</code> (root) directly:
                        <pre class="mb-0 mt-2">
Type  : CNAME
Name  : @
Value : stores.mychitti.net
Proxy : DNS Only (grey cloud — do NOT enable proxy)</pre>
                        Then also add the same for <code>www</code>. No forwarding rule needed.
                    </div>

                    <div class="alert alert-success mt-3">
                        <b>SSL is automatic.</b> Once DNS is set up and your domain is saved here,
                        your SSL certificate will be issued and deployed within a few minutes.
                    </div>

                    <div class="alert alert-warning mt-3">
                        Do not include <code>http://</code> or <code>https://</code> when entering your domain below.
                        Enter it as: <code>www.yourdomain.com</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
