    {{-- Razorpay Checkout. The Subscriptions API rejects callback_url, so the hosted page has no
         way back to us; opening the mandate in this modal instead keeps the vendor on MyChitti
         and gives us a handler to return to. Falls back to the hosted page if the script is
         blocked, which is the behaviour we had before. --}}
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function () {
            var MERCHANT = '{{ addslashes(\App\Models\BusinessSetting::where('key', 'business_name')->value('value') ?: 'MyChitti') }}';
            var BACK = '{{ route('vendor.whatsapp.billing') }}?flag=success';
            var CSRF = '{{ csrf_token() }}';

            // Keep the template-slot button honest about what the vendor is about to pay. Mirrors
            // _price(): the currency symbol followed by number_format(x, 2).
            (function () {
                var $form = document.getElementById('wb-tpl-form');
                if (!$form) return;

                var $qty    = document.getElementById('wb-tpl-slots');
                var $count  = document.getElementById('wb-tpl-count');
                var $total  = document.getElementById('wb-tpl-total');
                var $plural = document.getElementById('wb-tpl-plural');
                var unit    = parseFloat($form.dataset.unit) || 0;
                var symbol  = $form.dataset.currency || '';

                function refresh() {
                    var n = Math.max(1, Math.min(50, parseInt($qty.value, 10) || 1));
                    $count.textContent = n;
                    $plural.textContent = n === 1 ? '' : 's';
                    $total.textContent = symbol + (unit * n).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                $qty.addEventListener('input', refresh);
                $qty.addEventListener('change', refresh);
                refresh();
            })();

            // Every plan button on this page goes through the same modal. They differ only in
            // which tier the form carries.
            Array.prototype.forEach.call(document.querySelectorAll('.wb-mandate-form'), function ($form) {
                var $status = $form.querySelector('.wb-mandate-status');
                var $button = $form.querySelector('button[type="submit"]');
                var label   = $button ? $button.innerHTML : '';

                function status(msg, kind) {
                    if (!$status) return;
                    $status.style.display = msg ? 'block' : 'none';
                    $status.className = 'wb-mandate-status mt-2 ' + (kind === 'error' ? 'text-danger' : 'wb-sub');
                    $status.textContent = msg || '';
                }

                function busy(on) {
                    $button.disabled = on;
                    $button.innerHTML = on
                        ? '<span class="spinner-border spinner-border-sm mr-1"></span> Preparing…'
                        : label;
                }

                $form.addEventListener('submit', function (e) {
                    if (typeof Razorpay === 'undefined') return;   // let the plain post through
                    e.preventDefault();

                    // Whatever the form carries — the plan and account_manager — goes to the
                    // endpoint as-is.
                    var body = {};
                    Array.prototype.forEach.call($form.querySelectorAll('input[name]'), function (input) {
                        if (input.name === '_token') return;
                        body[input.name] = input.type === 'checkbox' ? (input.checked ? 1 : 0) : input.value;
                    });

                    busy(true);
                    status('Setting up your auto-debit…');

                    fetch($form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        busy(false);
                        if (!res.ok || !res.d.success) {
                            status(res.d.message || 'Could not start the auto-debit.', 'error');
                            return;
                        }
                        // No key means the gateway isn't readable from here — the hosted page still works.
                        if (!res.d.key) {
                            window.location.href = res.d.url;
                            return;
                        }

                        status('');
                        new Razorpay({
                            key: res.d.key,
                            subscription_id: res.d.subscription_id,
                            name: MERCHANT,
                            description: ($form.dataset.label || 'Subscription') + ' — monthly',
                            handler: function () {
                                // Authorised. The subscription.activated / .charged webhooks are what
                                // actually move our records, so just come back and let the page read them.
                                window.location.href = BACK;
                            },
                            modal: {
                                ondismiss: function () {
                                    status('Auto-debit setup was cancelled — nothing was charged.', 'error');
                                }
                            },
                            theme: { color: '#25d366' }
                        }).open();
                    })
                    .catch(function () {
                        busy(false);
                        status('Network error while starting the auto-debit.', 'error');
                    });
                });
            });
        })();
    </script>
