    @php
        $offer = _isSubmoduleEnabled('5')['offer'];
        $pay_warning = _isSubmoduleEnabled('5')['pay_warning'];
        $free_trial = _isSubmoduleEnabled('5')['free_trial'];
    @endphp
    @if ($free_trial)
        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">Free Trial</span>
    @elseif ($offer || $pay_warning)
        <div class="background-pattern" style="background-color: #fff5f5;">
            <div class="container py-5">
                <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">
                    <div class="col-md-7">
                        <h1 class="display-4 font-weight-bold">Salary Management.<br>Effortless Payroll, Every Time.
                        </h1>
                        <p class="lead mt-4">Focus on your business while we take care of accurate salary processing,
                            tax deductions, and timely payments — hassle-free.</p>
                        @if (_isSubmoduleEnabled('5')['expiring_text'])
                            <h4 class="text-danger trial_alert">{{ _isSubmoduleEnabled('5')['expiring_text'] }}</h4>
                        @endif
                    </div>
                    @if ($offer)
                        <div class="col-md-5">
                            <div class="highlight-box">
                                <h5 class="font-weight-bold">Just at
                                    {{ \App\CentralLogics\Helpers::format_currency(_modulePrice(5)) }}/mo
                                </h5>
                                <ul class="list-unstyled mt-3 mb-4">
                                    <li>✓ Automatic salary calculation & adjustments</li>
                                    <li>✓ Manage bonuses, deductions & incentives</li>
                                    <li>✓ Secure, confidential, and easy to use</li>
                                </ul>
                                <a href="{{ route('vendor.sub-module.list', ['salary-management']) }}"
                                    class="btn btn-primary btn-block"
                                    style="background-color: #a10000 !important;">Enable
                                    Salary Management</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
