 @php $offer = _isSubmoduleEnabled('7')['offer']; 
  $pay_warning = _isSubmoduleEnabled('7')['pay_warning'];
     $free_trial = _isSubmoduleEnabled('7')['free_trial'];
 @endphp
 @if($free_trial)
    <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">Free Trial</span>

@elseif ($offer || $pay_warning)
 <div class="background-pattern" style="background-color: #f5f8ff;">
            <div class="container py-5">
                <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">
                    <div class="col-md-7">
                        <h1 class="display-4 font-weight-bold">Quick Quotes. Clear Communication.</h1>
                        <p class="lead mt-4">Quotation creation, tracking, and client approvals — so you can focus on closing deals faster.</p>
                              @if (_isSubmoduleEnabled('7')['expiring_text'])
                        <h4 class="text-danger trial_alert">{{ _isSubmoduleEnabled('7')['expiring_text'] }}</h4>
                    @endif
                    </div>
                @if($offer)

                    <div class="col-md-5">
                        <div class="highlight-box">
                            <h5 class="font-weight-bold">Just at
                                {{ \App\CentralLogics\Helpers::format_currency(_modulePrice(7)) }}/mo
                            </h5>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li>✓ Create professional quotations in minutes</li>
                                <li>✓ Customize pricing, terms, and conditions</li>
                                <li>✓ Track quote status: sent, viewed, approved</li>
                                <li>✓ Maintain a complete quote history for every client</li>
                                <li>✓ Send quotes via email</li>
                            </ul>
                            <a href="{{ route('vendor.sub-module.list', ['quotation-management']) }}"
                                class="btn btn-primary btn-block"  style="background-color: #1d4a85 !important;" >Enable Quotation Management</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
@endif