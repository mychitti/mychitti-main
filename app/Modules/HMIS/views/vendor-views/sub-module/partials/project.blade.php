 @php $offer = _isSubmoduleEnabled('8')['offer']; 
  $pay_warning = _isSubmoduleEnabled('8')['pay_warning'];
     $free_trial = _isSubmoduleEnabled('8')['free_trial'];
 @endphp
 @if($free_trial)
    <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">Free Trial</span>

@elseif ($offer || $pay_warning)
        <div class="background-pattern" style="background-color: #faffee;">
            <div class="container py-5">
                <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">
                    <div class="col-md-7">
                        <h1 class="display-4 font-weight-bold">Plan Better.<br>Deliver Faster.</h1>
                        <p class="lead mt-4">From planning to delivery — we organize the work, so you can lead the success.</p>
                              @if (_isSubmoduleEnabled('8')['expiring_text'])
                        <h4 class="text-danger trial_alert">{{ _isSubmoduleEnabled('8')['expiring_text'] }}</h4>
                    @endif
                    </div>
                @if($offer)

                    <div class="col-md-5">
                        <div class="highlight-box">
                            <h5 class="font-weight-bold">Just at
                                {{ \App\CentralLogics\Helpers::format_currency(_modulePrice(8)) }}/mo
                            </h5>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li>✓ Create and manage multiple projects</li>
                                <li>✓ Assign tasks, set due dates</li>
                                <li>✓ Real-time progress tracking</li>
                                <li>✓ Visual dashboards & timelines</li>
                                <li>✓ Track project status, delays & completions</li>
                            </ul>
                            <a href="{{ route('vendor.sub-module.list', ['project-management']) }}"
                                class="btn btn-primary btn-block"  style="background-color: #5da100 !important;" >Enable Project Management</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif