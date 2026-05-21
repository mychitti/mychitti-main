 @php $offer = _isSubmoduleEnabled('3')['offer']; 
  $pay_warning = _isSubmoduleEnabled('3')['pay_warning'];
     $free_trial = _isSubmoduleEnabled('3')['free_trial'];
 @endphp
 @if($free_trial)
    <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">Free Trial</span>

@elseif ($offer || $pay_warning)
        <div class="background-pattern" style="background-color: #fffcf5;">
            <div class="container py-5">
                <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">
                    <div class="col-md-7">
                        <h1 class="display-4 font-weight-bold">Advanced Billing.<br>Simplified.</h1>
                        <p class="lead mt-4">Let our system handle invoices, payments, and reminders so your team can focus
                            on delivering care—not chasing payments.</p>
                            @if (_isSubmoduleEnabled('3')['expiring_text'])
                        <h4 class="text-danger trial_alert">{{ _isSubmoduleEnabled('3')['expiring_text'] }}</h4>
                    @endif

                    </div>
                @if($offer)

                    <div class="col-md-5">
                        <div class="highlight-box">
                            <h5 class="font-weight-bold">Just at
                                {{ \App\CentralLogics\Helpers::format_currency(_modulePrice(3)) }}/mo
                            </h5>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li>✓ Send automatic payment reminders</li>
                                <li>✓ Generate unpaid bills instantly</li>
                                <li>✓ Create and manage invoices effortlessly</li>
                                <li>✓ Track payment status in real time</li>
                                <li>✓ Secure access to billing records</li>
                            </ul>
                            <a href="{{ route('vendor.sub-module.list', ['billing']) }}" class="btn btn-primary btn-block"
                                style="background-color: #be6c1c !important;">Enable Advanced Billing</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @endif